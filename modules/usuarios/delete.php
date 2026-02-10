<?php
/**
 * Módulo: Usuarios del Sistema
 * Endpoint para eliminar
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión y permisos
verificarSesion();
if (!tienePermiso('usuarios')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit();
}

// No permitir eliminar el propio usuario
if ($id == ($_SESSION['user_id'] ?? 0)) {
    header("Location: index.php?msg=self");
    exit();
}

try {
    // Obtener datos antes de eliminar
    $infoStmt = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
    $infoStmt->execute([$id]);
    $usuario = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header("Location: index.php?msg=error");
        exit();
    }

    // Eliminar
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);

    // Auditoría
    registrarAccion('ELIMINAR', 'usuarios', "Usuario eliminado: {$usuario['nombre']} ({$usuario['email']})", $id);

    header("Location: index.php?msg=deleted");

} catch (PDOException $e) {
    error_log("Error en usuarios/delete.php: " . $e->getMessage());
    header("Location: index.php?msg=error");
}

exit();
?>