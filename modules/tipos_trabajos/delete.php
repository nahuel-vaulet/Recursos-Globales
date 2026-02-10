<?php
/**
 * Módulo: Tipos de Trabajos
 * Endpoint para eliminar
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Verificar sesión
verificarSesion();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit();
}

try {
    // Obtener datos antes de eliminar para el log
    $infoStmt = $pdo->prepare("SELECT codigo_trabajo, nombre FROM tipos_trabajos WHERE id_tipologia = ?");
    $infoStmt->execute([$id]);
    $trabajo = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$trabajo) {
        header("Location: index.php?msg=error");
        exit();
    }

    // TODO: Verificar si hay dependencias (órdenes de trabajo que usen este tipo)
    // Por ahora solo eliminamos

    // Eliminar
    $stmt = $pdo->prepare("DELETE FROM tipos_trabajos WHERE id_tipologia = ?");
    $stmt->execute([$id]);

    // Registrar auditoría
    registrarAccion('ELIMINAR', 'tipos_trabajos', "Tipo de trabajo eliminado: [{$trabajo['codigo_trabajo']}] {$trabajo['nombre']}", $id);

    header("Location: index.php?msg=deleted");

} catch (PDOException $e) {
    error_log("Error en tipos_trabajos/delete.php: " . $e->getMessage());
    header("Location: index.php?msg=error");
}

exit();
?>