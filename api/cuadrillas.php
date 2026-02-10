<?php
/**
 * Cuadrillas API Endpoint
 * CRUD operations for work squads
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
                getCuadrilla((int) $_GET['id']);
            } else {
                getCuadrillas();
            }
            break;
        case 'POST':
            createCuadrilla();
            break;
        case 'PUT':
            updateCuadrilla();
            break;
        case 'DELETE':
            deleteCuadrilla();
            break;
        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function getCuadrillas()
{
    $where = ['c.estado = 1'];
    $params = [];

    if (!empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $where[] = '(c.nombre LIKE :search OR c.zona_trabajo LIKE :search)';
        $params['search'] = $search;
    }

    $whereClause = implode(' AND ', $where);

    $sql = "SELECT c.*,
                   (SELECT COUNT(*) FROM movimientos m WHERE m.cuadrilla_id = c.id) as total_movimientos,
                   (SELECT COALESCE(SUM(cantidad), 0) FROM movimientos m 
                    WHERE m.cuadrilla_id = c.id AND m.tipo = 'salida' 
                    AND m.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as consumo_mensual
            FROM cuadrillas c
            WHERE {$whereClause}
            ORDER BY c.nombre ASC";

    $cuadrillas = Database::query($sql, $params);

    jsonResponse([
        'success' => true,
        'data' => $cuadrillas
    ]);
}

function getCuadrilla(int $id)
{
    $cuadrilla = Database::queryOne(
        "SELECT * FROM cuadrillas WHERE id = :id AND estado = 1",
        ['id' => $id]
    );

    if (!$cuadrilla) {
        jsonResponse(['error' => 'Cuadrilla no encontrada'], 404);
    }

    // Get recent movements
    $movimientos = Database::query(
        "SELECT m.*, mat.nombre as material_nombre, mat.unidad_medida
         FROM movimientos m
         JOIN materiales mat ON m.material_id = mat.id
         WHERE m.cuadrilla_id = :id
         ORDER BY m.fecha DESC
         LIMIT 10",
        ['id' => $id]
    );

    $cuadrilla['movimientos'] = $movimientos;

    jsonResponse([
        'success' => true,
        'data' => $cuadrilla
    ]);
}

function createCuadrilla()
{
    $data = getJsonBody();

    $errors = validateRequired($data, ['nombre']);
    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 400);
    }

    $sql = "INSERT INTO cuadrillas (nombre, zona_trabajo, responsable)
            VALUES (:nombre, :zona_trabajo, :responsable)";

    $params = [
        'nombre' => sanitize($data['nombre']),
        'zona_trabajo' => !empty($data['zona_trabajo']) ? sanitize($data['zona_trabajo']) : null,
        'responsable' => !empty($data['responsable']) ? sanitize($data['responsable']) : null,
    ];

    Database::execute($sql, $params);
    $id = Database::lastInsertId();

    jsonResponse([
        'success' => true,
        'message' => 'Cuadrilla creada correctamente',
        'id' => $id
    ], 201);
}

function updateCuadrilla()
{
    $data = getJsonBody();

    if (empty($data['id'])) {
        jsonResponse(['error' => 'ID es requerido'], 400);
    }

    $id = (int) $data['id'];

    $current = Database::queryOne(
        "SELECT * FROM cuadrillas WHERE id = :id",
        ['id' => $id]
    );

    if (!$current) {
        jsonResponse(['error' => 'Cuadrilla no encontrada'], 404);
    }

    $sql = "UPDATE cuadrillas SET 
                nombre = :nombre,
                zona_trabajo = :zona_trabajo,
                responsable = :responsable
            WHERE id = :id";

    $params = [
        'id' => $id,
        'nombre' => sanitize($data['nombre'] ?? $current['nombre']),
        'zona_trabajo' => isset($data['zona_trabajo']) ? sanitize($data['zona_trabajo']) : $current['zona_trabajo'],
        'responsable' => isset($data['responsable']) ? sanitize($data['responsable']) : $current['responsable'],
    ];

    Database::execute($sql, $params);

    jsonResponse([
        'success' => true,
        'message' => 'Cuadrilla actualizada correctamente'
    ]);
}

function deleteCuadrilla()
{
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        jsonResponse(['error' => 'ID es requerido'], 400);
    }

    // Check if has movements
    $hasMovements = Database::queryOne(
        "SELECT COUNT(*) as total FROM movimientos WHERE cuadrilla_id = :id",
        ['id' => $id]
    );

    if ($hasMovements['total'] > 0) {
        // Soft delete if has movements
        Database::execute(
            "UPDATE cuadrillas SET estado = 0 WHERE id = :id",
            ['id' => $id]
        );
    } else {
        // Hard delete if no movements
        Database::execute(
            "DELETE FROM cuadrillas WHERE id = :id",
            ['id' => $id]
        );
    }

    jsonResponse([
        'success' => true,
        'message' => 'Cuadrilla eliminada correctamente'
    ]);
}
