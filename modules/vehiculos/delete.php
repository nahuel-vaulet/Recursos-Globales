<?php
// [!] ARQUITECTURA: Endpoint de eliminación con verificación de integridad
// [→] EDITAR AQUÍ: Rutas de inclusión si cambia la estructura
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDITORÍA CRUD: Verificación de sesión obligatoria
verificarSesion();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?msg=error");
    exit;
}

$id = intval($_GET['id']);

try {
    // Check if vehicle is assigned to a cuadrilla
    $checkStmt = $pdo->prepare("SELECT c.nombre_cuadrilla FROM cuadrillas c WHERE c.id_vehiculo_asignado = ?");
    $checkStmt->execute([$id]);
    $cuadrilla = $checkStmt->fetchColumn();

    if ($cuadrilla) {
        $error = urlencode("No se puede eliminar: el vehículo está asignado a la cuadrilla '$cuadrilla'");
        header("Location: index.php?msg=error&details=$error");
        exit;
    }

    // [!] ARQUITECTURA: Obtener datos antes de eliminar para auditoría
    $infoStmt = $pdo->prepare("SELECT patente FROM vehiculos WHERE id_vehiculo = ?");
    $infoStmt->execute([$id]);
    $patente = $infoStmt->fetchColumn();

    // Delete
    $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id_vehiculo = ?");
    $stmt->execute([$id]);

    // [✓] AUDITORÍA CRUD: Registrar eliminación
    registrarAccion('ELIMINAR', 'vehiculos', "Vehículo eliminado: $patente", $id);

    header("Location: index.php?msg=deleted");

} catch (PDOException $e) {
    $error = urlencode($e->getMessage());
    header("Location: index.php?msg=error&details=$error");
}
?>