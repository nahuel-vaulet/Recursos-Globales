<?php
require_once '../../../config/database.php';
require_once '../../../config/database.php';
require_once '../../../includes/auth.php'; // Use auth.php instead of header.php to avoid output

if (!tienePermiso('compras')) {
    header('Location: ../../../index.php?msg=forbidden');
    exit;
}

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

if (!$id || !$action) {
    header('Location: index.php?msg=error');
    exit;
}

try {
    // Get current order status
    $stmt = $pdo->prepare("SELECT estado FROM compras_ordenes WHERE id = ?");
    $stmt->execute([$id]);
    $currentStatus = $stmt->fetchColumn();

    if (!$currentStatus) {
        throw new Exception("Orden no encontrada");
    }

    if ($action === 'update_status') {
        // Validate transition
        $valid = false;

        if ($currentStatus === 'emitida' && $status === 'enviada')
            $valid = true;
        if ($currentStatus === 'enviada' && $status === 'confirmada')
            $valid = true;
        if ($currentStatus === 'confirmada' && $status === 'entregada')
            $valid = true;

        // Allow backtracking? Usually no.
        // Allow jump to delivered? Maybe manually.

        if ($valid) {
            $stmt = $pdo->prepare("UPDATE compras_ordenes SET estado = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $msg = 'updated';
        } else {
            throw new Exception("Transición de estado no válida");
        }

    } elseif ($action === 'cancel') {
        if (in_array($currentStatus, ['emitida', 'enviada', 'confirmada'])) {
            $stmt = $pdo->prepare("UPDATE compras_ordenes SET estado = 'cancelada' WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'cancelled';
        } else {
            throw new Exception("No se puede cancelar una orden entregada o ya cancelada");
        }
    }

    header("Location: index.php?msg=$msg");
    exit;

} catch (Exception $e) {
    header("Location: index.php?msg=error&error=" . urlencode($e->getMessage()));
    exit;
}
