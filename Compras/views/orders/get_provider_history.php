<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

$providerId = intval($_GET['provider_id'] ?? 0);

if (!$providerId) {
    echo json_encode(['orders' => []]);
    exit;
}

// Get recent orders for this provider with items
$stmt = $pdo->prepare("
    SELECT po.id, po.po_number, po.status, po.total_amount, po.created_at
    FROM purchase_orders po 
    WHERE po.provider_id = ?
    ORDER BY po.created_at DESC 
    LIMIT 10
");
$stmt->execute([$providerId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get items for each order
foreach ($orders as &$order) {
    $stmtItems = $pdo->prepare("SELECT item_description, quantity, price_unit FROM po_items WHERE po_id = ?");
    $stmtItems->execute([$order['id']]);
    $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    $order['created_at'] = date('d/m/Y', strtotime($order['created_at']));
}

echo json_encode(['orders' => $orders]);
?>