<?php
/**
 * Módulo Herramientas - Guardar Sanción
 * [!] ARQUITECTURA: Procesa el formulario de nueva sanción
 * [✓] AUDITORÍA CRUD: Registra sanción y movimiento
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sanciones.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $data = [
        'id_herramienta' => intval($_POST['id_herramienta']),
        'id_personal' => intval($_POST['id_personal']),
        'id_cuadrilla' => !empty($_POST['id_cuadrilla']) ? intval($_POST['id_cuadrilla']) : null,
        'tipo_sancion' => $_POST['tipo_sancion'],
        'descripcion' => trim($_POST['descripcion']),
        'monto_descuento' => floatval($_POST['monto_descuento'] ?? 0),
        'fecha_incidente' => $_POST['fecha_incidente'],
        'created_by' => $_SESSION['user_id'] ?? null
    ];

    // Insertar sanción
    $sql = "INSERT INTO herramientas_sanciones 
            (id_herramienta, id_personal, id_cuadrilla, tipo_sancion, descripcion, monto_descuento, fecha_incidente, created_by)
            VALUES 
            (:id_herramienta, :id_personal, :id_cuadrilla, :tipo_sancion, :descripcion, :monto_descuento, :fecha_incidente, :created_by)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id_sancion = $pdo->lastInsertId();

    // Registrar movimiento
    $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, id_personal, observaciones, monto, created_by) VALUES (?, 'Sancion', ?, ?, ?, ?)")
        ->execute([$data['id_herramienta'], $data['id_personal'], $data['tipo_sancion'] . ': ' . $data['descripcion'], $data['monto_descuento'], $data['created_by']]);

    // Si es pérdida, dar de baja la herramienta
    if ($data['tipo_sancion'] === 'Perdida') {
        $pdo->prepare("UPDATE herramientas SET estado = 'Baja', id_cuadrilla_asignada = NULL WHERE id_herramienta = ?")
            ->execute([$data['id_herramienta']]);

        $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, observaciones, created_by) VALUES (?, 'Baja', 'Baja por pérdida', ?)")
            ->execute([$data['id_herramienta'], $data['created_by']]);
    }

    // Obtener nombre herramienta para auditoría
    $stmt = $pdo->prepare("SELECT nombre FROM herramientas WHERE id_herramienta = ?");
    $stmt->execute([$data['id_herramienta']]);
    $h = $stmt->fetch();

    registrarAccion('CREAR', 'herramientas_sanciones', "Sanción registrada: " . $data['tipo_sancion'] . " - " . $h['nombre'], $id_sancion);

    $pdo->commit();
    header("Location: sanciones.php?msg=success");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: nueva_sancion.php?id=" . ($_POST['id_herramienta'] ?? '') . "&msg=error&details=" . urlencode($e->getMessage()));
}
?>