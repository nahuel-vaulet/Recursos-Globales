<?php
/**
 * Materiales API Endpoint
 * CRUD operations for materials
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getMaterial((int) $_GET['id']);
            } else {
                getMateriales();
            }
            break;
        case 'POST':
            createMaterial();
            break;
        case 'PUT':
            updateMaterial();
            break;
        case 'DELETE':
            deleteMaterial();
            break;
        default:
            jsonResponse(['error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

/**
 * Get all materials with optional filters
 */
function getMateriales()
{
    $where = ['m.estado = 1'];
    $params = [];

    // Search filter
    if (!empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $where[] = '(m.nombre LIKE :search OR m.codigo LIKE :search)';
        $params['search'] = $search;
    }

    // Category filter
    if (!empty($_GET['categoria'])) {
        $where[] = 'm.categoria = :categoria';
        $params['categoria'] = sanitize($_GET['categoria']);
    }

    // Low stock filter
    if (isset($_GET['bajo_stock']) && $_GET['bajo_stock'] === '1') {
        $where[] = 'm.stock_actual <= m.stock_minimo';
    }

    $whereClause = implode(' AND ', $where);

    $sql = "SELECT m.*, 
                   CASE 
                       WHEN m.stock_actual <= m.stock_minimo * 0.5 THEN 'critical'
                       WHEN m.stock_actual <= m.stock_minimo THEN 'warning'
                       ELSE 'ok'
                   END as stock_status
            FROM materiales m
            WHERE {$whereClause}
            ORDER BY m.nombre ASC";

    $materiales = Database::query($sql, $params);

    // Get categories for filters
    $categorias = Database::query(
        "SELECT DISTINCT categoria FROM materiales WHERE categoria IS NOT NULL ORDER BY categoria"
    );

    jsonResponse([
        'success' => true,
        'data' => $materiales,
        'categorias' => array_column($categorias, 'categoria')
    ]);
}

/**
 * Get single material by ID
 */
function getMaterial(int $id)
{
    $material = Database::queryOne(
        "SELECT * FROM materiales WHERE id = :id AND estado = 1",
        ['id' => $id]
    );

    if (!$material) {
        jsonResponse(['error' => 'Material no encontrado'], 404);
    }

    // Get recent movements
    $movimientos = Database::query(
        "SELECT m.*, u.nombre as usuario_nombre, c.nombre as cuadrilla_nombre
         FROM movimientos m
         JOIN usuarios u ON m.usuario_id = u.id
         LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id
         WHERE m.material_id = :id
         ORDER BY m.fecha DESC
         LIMIT 10",
        ['id' => $id]
    );

    $material['movimientos'] = $movimientos;

    jsonResponse([
        'success' => true,
        'data' => $material
    ]);
}

/**
 * Create new material
 */
function createMaterial()
{
    $data = getJsonBody();

    // Validate required fields
    $errors = validateRequired($data, ['nombre', 'unidad_medida']);
    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 400);
    }

    // Check for duplicate code
    if (!empty($data['codigo'])) {
        $exists = Database::queryOne(
            "SELECT id FROM materiales WHERE codigo = :codigo",
            ['codigo' => $data['codigo']]
        );
        if ($exists) {
            jsonResponse(['error' => 'El código ya existe'], 400);
        }
    }

    $sql = "INSERT INTO materiales (nombre, codigo, unidad_medida, stock_actual, stock_minimo, categoria)
            VALUES (:nombre, :codigo, :unidad_medida, :stock_actual, :stock_minimo, :categoria)";

    $params = [
        'nombre' => sanitize($data['nombre']),
        'codigo' => !empty($data['codigo']) ? sanitize($data['codigo']) : null,
        'unidad_medida' => sanitize($data['unidad_medida']),
        'stock_actual' => (float) ($data['stock_actual'] ?? 0),
        'stock_minimo' => (float) ($data['stock_minimo'] ?? 0),
        'categoria' => !empty($data['categoria']) ? sanitize($data['categoria']) : null,
    ];

    Database::execute($sql, $params);
    $id = Database::lastInsertId();

    // Audit log
    logAudit('crear', 'materiales', $id, null, $params);

    jsonResponse([
        'success' => true,
        'message' => 'Material creado correctamente',
        'id' => $id
    ], 201);
}

/**
 * Update existing material
 */
function updateMaterial()
{
    $data = getJsonBody();

    if (empty($data['id'])) {
        jsonResponse(['error' => 'ID es requerido'], 400);
    }

    $id = (int) $data['id'];

    // Get current data for audit
    $current = Database::queryOne(
        "SELECT * FROM materiales WHERE id = :id",
        ['id' => $id]
    );

    if (!$current) {
        jsonResponse(['error' => 'Material no encontrado'], 404);
    }

    // Check for duplicate code
    if (!empty($data['codigo'])) {
        $exists = Database::queryOne(
            "SELECT id FROM materiales WHERE codigo = :codigo AND id != :id",
            ['codigo' => $data['codigo'], 'id' => $id]
        );
        if ($exists) {
            jsonResponse(['error' => 'El código ya existe'], 400);
        }
    }

    $sql = "UPDATE materiales SET 
                nombre = :nombre,
                codigo = :codigo,
                unidad_medida = :unidad_medida,
                stock_minimo = :stock_minimo,
                categoria = :categoria
            WHERE id = :id";

    $params = [
        'id' => $id,
        'nombre' => sanitize($data['nombre'] ?? $current['nombre']),
        'codigo' => !empty($data['codigo']) ? sanitize($data['codigo']) : $current['codigo'],
        'unidad_medida' => sanitize($data['unidad_medida'] ?? $current['unidad_medida']),
        'stock_minimo' => (float) ($data['stock_minimo'] ?? $current['stock_minimo']),
        'categoria' => !empty($data['categoria']) ? sanitize($data['categoria']) : $current['categoria'],
    ];

    Database::execute($sql, $params);

    // Audit log
    logAudit('actualizar', 'materiales', $id, $current, $params);

    jsonResponse([
        'success' => true,
        'message' => 'Material actualizado correctamente'
    ]);
}

/**
 * Delete material (soft delete)
 */
function deleteMaterial()
{
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$id) {
        jsonResponse(['error' => 'ID es requerido'], 400);
    }

    // Check if material exists
    $material = Database::queryOne(
        "SELECT * FROM materiales WHERE id = :id AND estado = 1",
        ['id' => $id]
    );

    if (!$material) {
        jsonResponse(['error' => 'Material no encontrado'], 404);
    }

    // Soft delete
    Database::execute(
        "UPDATE materiales SET estado = 0 WHERE id = :id",
        ['id' => $id]
    );

    // Audit log
    logAudit('eliminar', 'materiales', $id, $material, null);

    jsonResponse([
        'success' => true,
        'message' => 'Material eliminado correctamente'
    ]);
}

/**
 * Log audit trail
 */
function logAudit($accion, $tabla, $registroId, $valorAnterior, $valorNuevo)
{
    try {
        $sql = "INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, valor_anterior, valor_nuevo, ip_address)
                VALUES (:usuario_id, :accion, :tabla, :registro_id, :valor_anterior, :valor_nuevo, :ip)";

        Database::execute($sql, [
            'usuario_id' => 1, // TODO: Get from session
            'accion' => $accion,
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'valor_anterior' => $valorAnterior ? json_encode($valorAnterior) : null,
            'valor_nuevo' => $valorNuevo ? json_encode($valorNuevo) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        // Don't fail main operation if audit fails
        error_log('Audit log error: ' . $e->getMessage());
    }
}
