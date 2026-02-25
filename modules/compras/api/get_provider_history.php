<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php'; // Use auth.php to avoid HTML output

// Verify session/permissions manually if header not wanted (API usually returns JSON, not HTML)
// But to be safe and simple, I'll allow standard session check.
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$providerId = intval($_GET['provider_id'] ?? 0);

if (!$providerId) {
    echo json_encode(['orders' => []]);
    exit;
}

try {
    // Fetch last 5 orders for this provider
    $stmt = $pdo->prepare("
        SELECT id, nro_orden, fecha_creacion, monto_total 
        FROM compras_ordenes 
        WHERE id_proveedor = ? 
        ORDER BY fecha_creacion DESC 
        LIMIT 5
    ");
    $stmt->execute([$providerId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for these orders
    foreach ($orders as &$order) {
        $order['formatted_date'] = date('d/m/Y', strtotime($order['fecha_creacion']));

        $stmtItems = $pdo->prepare("
            SELECT descripcion, cantidad, precio_unitario 
            FROM compras_items_orden 
            WHERE id_orden = ?
        ");
        $stmtItems->execute([$order['id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['orders' => $orders]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
