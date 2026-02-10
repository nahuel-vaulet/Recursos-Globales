<?php
/**
 * Dashboard API Endpoint
 * Provides KPIs, alerts, and statistics for the dashboard
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$action = $_GET['action'] ?? 'stats';

try {
    switch ($action) {
        case 'stats':
            getStats();
            break;
        case 'alerts':
            getAlerts();
            break;
        case 'recent':
            getRecentMovements();
            break;
        case 'consumption':
            getConsumptionBySquad();
            break;
        case 'trends':
            getMonthlyTrends();
            break;
        default:
            jsonResponse(['error' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

/**
 * Get main dashboard statistics
 */
function getStats()
{
    // Total materials
    $totalMateriales = Database::queryOne(
        "SELECT COUNT(*) as total FROM materiales WHERE estado = 1"
    );

    // Total stock value (sum of stock_actual)
    $stockTotal = Database::queryOne(
        "SELECT COALESCE(SUM(stock_actual), 0) as total FROM materiales WHERE estado = 1"
    );

    // Low stock alerts count
    $alertas = Database::queryOne(
        "SELECT COUNT(*) as total FROM materiales WHERE stock_actual <= stock_minimo AND estado = 1"
    );

    // Today's movements
    $movimientosHoy = Database::queryOne(
        "SELECT COUNT(*) as total FROM movimientos WHERE DATE(fecha) = CURDATE()"
    );

    // Total squads
    $cuadrillas = Database::queryOne(
        "SELECT COUNT(*) as total FROM cuadrillas WHERE estado = 1"
    );

    // Month comparison
    $thisMonth = Database::queryOne(
        "SELECT COALESCE(SUM(cantidad), 0) as total FROM movimientos 
         WHERE tipo = 'salida' AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())"
    );

    $lastMonth = Database::queryOne(
        "SELECT COALESCE(SUM(cantidad), 0) as total FROM movimientos 
         WHERE tipo = 'salida' AND MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
         AND YEAR(fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
    );

    $changePercent = 0;
    if ($lastMonth['total'] > 0) {
        $changePercent = round((($thisMonth['total'] - $lastMonth['total']) / $lastMonth['total']) * 100, 1);
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'total_materiales' => (int) $totalMateriales['total'],
            'stock_total' => (float) $stockTotal['total'],
            'alertas_count' => (int) $alertas['total'],
            'movimientos_hoy' => (int) $movimientosHoy['total'],
            'cuadrillas_activas' => (int) $cuadrillas['total'],
            'consumo_mensual' => (float) $thisMonth['total'],
            'variacion_mensual' => $changePercent,
        ]
    ]);
}

/**
 * Get low stock alerts
 */
function getAlerts()
{
    $alerts = Database::query(
        "SELECT id, nombre, codigo, unidad_medida, stock_actual, stock_minimo,
                ROUND((stock_actual / NULLIF(stock_minimo, 0)) * 100, 1) as porcentaje
         FROM materiales 
         WHERE stock_actual <= stock_minimo AND estado = 1
         ORDER BY (stock_actual / NULLIF(stock_minimo, 0)) ASC
         LIMIT 10"
    );

    jsonResponse([
        'success' => true,
        'data' => $alerts
    ]);
}

/**
 * Get recent movements
 */
function getRecentMovements()
{
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
    $limit = min($limit, 20); // Max 20

    $movements = Database::query(
        "SELECT m.id, m.tipo, m.cantidad, m.fecha, m.observaciones,
                mat.nombre as material_nombre, mat.unidad_medida,
                c.nombre as cuadrilla_nombre,
                u.nombre as usuario_nombre
         FROM movimientos m
         JOIN materiales mat ON m.material_id = mat.id
         LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id
         JOIN usuarios u ON m.usuario_id = u.id
         ORDER BY m.fecha DESC
         LIMIT :limit",
        ['limit' => $limit]
    );

    jsonResponse([
        'success' => true,
        'data' => $movements
    ]);
}

/**
 * Get consumption by squad
 */
function getConsumptionBySquad()
{
    $consumption = Database::query(
        "SELECT c.id, c.nombre, c.zona_trabajo,
                COALESCE(SUM(CASE WHEN m.tipo = 'salida' THEN m.cantidad ELSE 0 END), 0) as total_consumo
         FROM cuadrillas c
         LEFT JOIN movimientos m ON c.id = m.cuadrilla_id 
            AND m.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         WHERE c.estado = 1
         GROUP BY c.id, c.nombre, c.zona_trabajo
         ORDER BY total_consumo DESC"
    );

    // Calculate max for percentage
    $maxConsumo = 0;
    foreach ($consumption as $c) {
        if ($c['total_consumo'] > $maxConsumo) {
            $maxConsumo = $c['total_consumo'];
        }
    }

    // Add percentage
    foreach ($consumption as &$c) {
        $c['porcentaje'] = $maxConsumo > 0
            ? round(($c['total_consumo'] / $maxConsumo) * 100, 1)
            : 0;
    }

    jsonResponse([
        'success' => true,
        'data' => $consumption
    ]);
}

/**
 * Get monthly trends for chart
 */
function getMonthlyTrends()
{
    $trends = Database::query(
        "SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes,
            DATE_FORMAT(fecha, '%b %Y') as mes_label,
            SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
            SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END) as salidas
         FROM movimientos
         WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(fecha, '%Y-%m'), DATE_FORMAT(fecha, '%b %Y')
         ORDER BY mes ASC"
    );

    jsonResponse([
        'success' => true,
        'data' => $trends
    ]);
}
