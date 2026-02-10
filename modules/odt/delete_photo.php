<?php
/**
 * [!] ARCH: Endpoint para eliminar fotos de ODT
 * [✓] AUDIT: Verifica sesión y registra eliminación
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$idFoto = $_POST['id_foto'] ?? null;

if (!$idFoto) {
    echo json_encode(['success' => false, 'error' => 'ID de foto no proporcionado']);
    exit;
}

try {
    // Obtener ruta del archivo antes de eliminar
    $stmt = $pdo->prepare("SELECT ruta_archivo, id_odt FROM odt_fotos WHERE id_foto = ?");
    $stmt->execute([$idFoto]);
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$foto) {
        echo json_encode(['success' => false, 'error' => 'Foto no encontrada']);
        exit;
    }

    // Eliminar de la base de datos
    $deleteStmt = $pdo->prepare("DELETE FROM odt_fotos WHERE id_foto = ?");
    $deleteStmt->execute([$idFoto]);

    // Intentar eliminar el archivo físico
    $filePath = '../../' . $foto['ruta_archivo'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Registrar acción
    registrarAccion('ELIMINAR', 'odt_fotos', "Foto eliminada ID: $idFoto", $foto['id_odt']);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Error en delete_photo.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
}
?>