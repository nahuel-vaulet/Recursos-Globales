<?php
/**
 * [!] ARCH: Endpoint para eliminar ODT
 * [→] EDITAR: Cambiar a borrado lógico si es necesario
 * [✓] AUDIT: Confirmación requerida en frontend + verificación de existencia
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDIT: Verificar sesión
verificarSesion();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: index.php?msg=error');
    exit;
}

$id = intval($id);

try {
    // [!] ARCH: Obtener datos antes de eliminar para auditoría
    $infoStmt = $pdo->prepare("SELECT nro_odt_assa FROM odt_maestro WHERE id_odt = ?");
    $infoStmt->execute([$id]);
    $nro_odt = $infoStmt->fetchColumn();

    if (!$nro_odt) {
        $_SESSION['error'] = 'ODT no encontrada.';
        header('Location: index.php?msg=error');
        exit;
    }

    // [!] ARCH: Verificar integridad referencial
    // Verificar si existen Partes Diarios asociados
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM partes_diarios WHERE id_odt = ?");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
        $_SESSION['error'] = 'No se puede eliminar la ODT porque tiene Partes Diarios asociados.';
        header('Location: index.php?msg=error');
        exit;
    }

    // [✓] AUDIT: Eliminar ODT
    $stmt = $pdo->prepare("DELETE FROM odt_maestro WHERE id_odt = ?");
    $stmt->execute([$id]);

    // [✓] AUDIT: Registrar eliminación
    registrarAccion('ELIMINAR', 'odt_maestro', "ODT eliminada: $nro_odt", $id);

    header('Location: index.php?msg=deleted');

} catch (PDOException $e) {
    // [!] ARCH: No exponer errores SQL
    error_log("Error en odt/delete.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error al eliminar la ODT.';
    header('Location: index.php?msg=error');
}

exit;
?>