<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión
verificarSesion();

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Obtener nombre antes de eliminar para el log
        $nameStmt = $pdo->prepare("SELECT nombre FROM maestro_materiales WHERE id_material = ?");
        $nameStmt->execute([$id]);
        $materialNombre = $nameStmt->fetchColumn();

        // Eliminar
        $stmt = $pdo->prepare("DELETE FROM maestro_materiales WHERE id_material = ?");
        $stmt->execute([$id]);

        // Registrar auditoría
        registrarAccion('ELIMINAR', 'materiales', "Material eliminado: $materialNombre", $id);

        header("Location: index.php?msg=deleted");
    } catch (PDOException $e) {
        header("Location: index.php?msg=error");
    }
} else {
    header("Location: index.php");
}
exit();
?>