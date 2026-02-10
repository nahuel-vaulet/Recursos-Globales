<?php
/**
 * Módulo Herramientas - Eliminar (Borrado Lógico)
 * [!] ARQUITECTURA: Cambia estado a 'Baja' en lugar de eliminar físicamente
 * [✓] AUDITORÍA CRUD: DELETE con confirmación y trazabilidad
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    // Obtener nombre para auditoría
    $stmt = $pdo->prepare("SELECT nombre FROM herramientas WHERE id_herramienta = ?");
    $stmt->execute([$id]);
    $h = $stmt->fetch();

    if ($h) {
        // Borrado lógico
        $pdo->prepare("UPDATE herramientas SET estado = 'Baja', id_cuadrilla_asignada = NULL WHERE id_herramienta = ?")->execute([$id]);

        // Registrar movimiento
        $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, observaciones, created_by) VALUES (?, 'Baja', 'Herramienta dada de baja', ?)")->execute([$id, $_SESSION['user_id'] ?? null]);

        registrarAccion('ELIMINAR', 'herramientas', "Herramienta dada de baja: " . $h['nombre'], $id);
    }

    header("Location: index.php?msg=deleted");
} catch (PDOException $e) {
    header("Location: index.php?msg=error&details=" . urlencode($e->getMessage()));
}
?>