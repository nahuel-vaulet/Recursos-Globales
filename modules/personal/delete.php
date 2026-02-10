<?php
// [!] ARQUITECTURA: Endpoint de eliminación de personal con auditoría
// [→] EDITAR AQUÍ: Rutas de inclusión si cambia la estructura
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDITORÍA CRUD: Verificación de sesión obligatoria
verificarSesion();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // [!] ARQUITECTURA: Obtener nombre antes de eliminar para auditoría
    $infoStmt = $pdo->prepare("SELECT nombre_apellido FROM personal WHERE id_personal = ?");
    $infoStmt->execute([$id]);
    $nombre = $infoStmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM personal WHERE id_personal = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Personal eliminado correctamente.';
        // [✓] AUDITORÍA CRUD: Registrar eliminación
        registrarAccion('ELIMINAR', 'personal', "Personal eliminado: $nombre", $id);
    } else {
        $_SESSION['error'] = 'No se encontró el registro a eliminar.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
}

header('Location: index.php');
exit;
