<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$orderId = intval($_GET['id'] ?? 0);
$newStatus = $_GET['status'] ?? '';

$validStatuses = ['emitida', 'enviada', 'confirmada', 'entregada', 'cancelada'];

if ($orderId && in_array($newStatus, $validStatuses)) {
    $stmt = $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
}

header('Location: list.php?updated=1');
exit;
?>