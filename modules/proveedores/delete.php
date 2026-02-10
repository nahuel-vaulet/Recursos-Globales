<?php
/**
 * Módulo: Proveedores
 * Endpoint para eliminar
 * 
 * [!] ARQUITECTURA: Verifica integridad antes de eliminar
 * [→] EDITAR AQUÍ: Rutas de inclusión si cambia la estructura
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDITORÍA CRUD: Verificación de sesión obligatoria
verificarSesion();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: index.php?msg=error');
    exit;
}

$id = intval($id);

try {
    // [!] ARQUITECTURA: Obtener datos antes de eliminar
    $infoStmt = $pdo->prepare("SELECT razon_social FROM proveedores WHERE id_proveedor = ?");
    $infoStmt->execute([$id]);
    $razon = $infoStmt->fetchColumn();

    if (!$razon) {
        header('Location: index.php?msg=error');
        exit;
    }

    // [!] ARQUITECTURA: Verificar integridad referencial
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM maestro_materiales WHERE id_contacto_primario IN (SELECT id_contacto FROM proveedores_contactos WHERE id_proveedor = ?) OR id_contacto_secundario IN (SELECT id_contacto FROM proveedores_contactos WHERE id_proveedor = ?)");
    $checkStmt->execute([$id, $id]);
    $materialesCount = $checkStmt->fetchColumn();

    if ($materialesCount > 0) {
        $_SESSION['error'] = "No se puede eliminar: el proveedor tiene $materialesCount material(es) asociado(s).";
        header('Location: index.php?msg=error');
        exit;
    }

    // Eliminar contactos primero (FK)
    $pdo->prepare("DELETE FROM proveedores_contactos WHERE id_proveedor = ?")->execute([$id]);

    // Eliminar proveedor
    $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id_proveedor = ?");
    $stmt->execute([$id]);

    // [✓] AUDITORÍA CRUD: Registrar eliminación
    registrarAccion('ELIMINAR', 'proveedores', "Proveedor eliminado: $razon", $id);

    header('Location: index.php?msg=deleted');

} catch (PDOException $e) {
    // [!] ARQUITECTURA: No exponer errores SQL
    error_log("Error en proveedores/delete.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error al eliminar el proveedor.';
    header('Location: index.php?msg=error');
}

exit;
?>