<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión
verificarSesion();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?msg=error");
    exit;
}

$id = intval($_GET['id']);

try {
    // Obtener nombre antes de eliminar para el log
    $nameStmt = $pdo->prepare("SELECT nombre_cuadrilla FROM cuadrillas WHERE id_cuadrilla = ?");
    $nameStmt->execute([$id]);
    $cuadrillaNombre = $nameStmt->fetchColumn();

    // Check if cuadrilla has personal assigned
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM personal WHERE id_cuadrilla = ?");
    $checkStmt->execute([$id]);
    $personalCount = $checkStmt->fetchColumn();

    if ($personalCount > 0) {
        $error = urlencode("No se puede eliminar: la cuadrilla tiene $personalCount integrante(s) asignados");
        header("Location: index.php?msg=error&details=$error");
        exit;
    }

    // Delete
    $stmt = $pdo->prepare("DELETE FROM cuadrillas WHERE id_cuadrilla = ?");
    $stmt->execute([$id]);

    // Registrar auditoría
    registrarAccion('ELIMINAR', 'cuadrillas', "Cuadrilla eliminada: $cuadrillaNombre", $id);

    header("Location: index.php?msg=deleted");

} catch (PDOException $e) {
    $error = urlencode($e->getMessage());
    header("Location: index.php?msg=error&details=$error");
}
?>