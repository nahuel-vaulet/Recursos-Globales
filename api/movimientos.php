<?php
/**
 * Movimientos API Endpoint
 * CRUD operations for stock movements (entries/exits)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getMovimiento((int) $_GET['id']);
            } else {
                getMovimientos();
            }
            break;
        case 'POST':
            createMovimiento();
            break;
        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function getMovimientos()
{
    $where = ['1=1'];
    $params = [];

    // Filter by material
    if (!empty($_GET['material_id'])) {
        $where[] = 'm.material_id = :material_id';
        $params['material_id'] = (int) $_GET['material_id'];
    }

    // Filter by squad
    if (!empty($_GET['cuadrilla_id'])) {
        $where[] = 'm.cuadrilla_id = :cuadrilla_id';
        $params['cuadrilla_id'] = (int) $_GET['cuadrilla_id'];
    }

    // Filter by type
    if (!empty($_GET['tipo']) && in_array($_GET['tipo'], ['entrada', 'salida'])) {
        $where[] = 'm.tipo = :tipo';
        $params['tipo'] = $_GET['tipo'];
    }

    // Filter by date range
    if (!empty($_GET['fecha_desde'])) {
        $where[] = 'm.fecha >= :fecha_desde';
        $params['fecha_desde'] = $_GET['fecha_desde'] . ' 00:00:00';
    }

    if (!empty($_GET['fecha_hasta'])) {
        $where[] = 'm.fecha <= :fecha_hasta';
        $params['fecha_hasta'] = $_GET['fecha_hasta'] . ' 23:59:59';
    }

    $whereClause = implode(' AND ', $where);

    // Pagination
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM movimientos m WHERE {$whereClause}";
    $total = Database::queryOne($countSql, $params)['total'];

    $sql = "SELECT m.*, 
                   mat.nombre as material_nombre, 
                   mat.codigo as material_codigo,
                   mat.unidad_medida,
                   c.nombre as cuadrilla_nombre,
                   c.zona_trabajo,
                   u.nombre as usuario_nombre
            FROM movimientos m
            JOIN materiales mat ON m.material_id = mat.id
            LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE {$whereClause}
            ORDER BY m.fecha DESC
            LIMIT {$limit} OFFSET {$offset}";

    $movimientos = Database::query($sql, $params);

    jsonResponse([
        'success' => true,
        'data' => $movimientos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getMovimiento(int $id)
{
    $movimiento = Database::queryOne(
        "SELECT m.*, 
                mat.nombre as material_nombre, mat.unidad_medida,
                c.nombre as cuadrilla_nombre,
                u.nombre as usuario_nombre
         FROM movimientos m
         JOIN materiales mat ON m.material_id = mat.id
         LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id
         JOIN usuarios u ON m.usuario_id = u.id
         WHERE m.id = :id",
        ['id' => $id]
    );

    if (!$movimiento) {
        jsonResponse(['error' => 'Movimiento no encontrado'], 404);
    }

    jsonResponse([
        'success' => true,
        'data' => $movimiento
    ]);
}

function createMovimiento()
{
    $data = getJsonBody();

    // Validate required fields
    $errors = validateRequired($data, ['material_id', 'tipo', 'cantidad']);

    // Validate type
    if (!in_array($data['tipo'] ?? '', ['entrada', 'salida'])) {
        $errors[] = 'Tipo debe ser "entrada" o "salida"';
    }

    // For exits, squad is required (RF-03)
    if (($data['tipo'] ?? '') === 'salida' && empty($data['cuadrilla_id'])) {
        $errors[] = 'La cuadrilla es obligatoria para las salidas';
    }

    // Validate quantity
    if ((float) ($data['cantidad'] ?? 0) <= 0) {
        $errors[] = 'La cantidad debe ser mayor a 0';
    }

    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 400);
    }

    $materialId = (int) $data['material_id'];
    $cantidad = (float) $data['cantidad'];
    $tipo = $data['tipo'];

    // Check material exists
    $material = Database::queryOne(
        "SELECT * FROM materiales WHERE id = :id AND estado = 1",
        ['id' => $materialId]
    );

    if (!$material) {
        jsonResponse(['error' => 'Material no encontrado'], 404);
    }

    // For exits, check sufficient stock
    if ($tipo === 'salida' && $material['stock_actual'] < $cantidad) {
        jsonResponse([
            'error' => "Stock insuficiente. Stock actual: {$material['stock_actual']} {$material['unidad_medida']}"
        ], 400);
    }

    // Begin transaction
    Database::beginTransaction();

    try {
        // Create movement
        $sql = "INSERT INTO movimientos (material_id, cuadrilla_id, usuario_id, tipo, cantidad, observaciones)
                VALUES (:material_id, :cuadrilla_id, :usuario_id, :tipo, :cantidad, :observaciones)";

        $params = [
            'material_id' => $materialId,
            'cuadrilla_id' => !empty($data['cuadrilla_id']) ? (int) $data['cuadrilla_id'] : null,
            'usuario_id' => 1, // TODO: Get from session
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'observaciones' => !empty($data['observaciones']) ? sanitize($data['observaciones']) : null,
        ];

        Database::execute($sql, $params);
        $movimientoId = Database::lastInsertId();

        // Update material stock
        $newStock = $tipo === 'entrada'
            ? $material['stock_actual'] + $cantidad
            : $material['stock_actual'] - $cantidad;

        Database::execute(
            "UPDATE materiales SET stock_actual = :stock WHERE id = :id",
            ['stock' => $newStock, 'id' => $materialId]
        );

        Database::commit();

        // Check for low stock alert
        $alertMessage = null;
        if ($newStock <= $material['stock_minimo']) {
            $alertMessage = "⚠️ Alerta: {$material['nombre']} está bajo el stock mínimo";
        }

        jsonResponse([
            'success' => true,
            'message' => 'Movimiento registrado correctamente',
            'id' => $movimientoId,
            'new_stock' => $newStock,
            'alert' => $alertMessage
        ], 201);

    } catch (Exception $e) {
        Database::rollback();
        throw $e;
    }
}
