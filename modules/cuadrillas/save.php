<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $isEdit = !empty($_POST['id_cuadrilla']);

    // 1. Basic Data Preparation
    $data = [
        'nombre_cuadrilla' => trim($_POST['nombre_cuadrilla']),
        'tipo_especialidad' => null, // Legacy field, keeping null or maybe concatenating names? Let's leave null or empty string.
        'estado_operativo' => $_POST['estado_operativo'] ?? 'Activa',
        'id_vehiculo_asignado' => !empty($_POST['id_vehiculo_asignado']) ? intval($_POST['id_vehiculo_asignado']) : null,
        'id_celular_asignado' => !empty($_POST['id_celular_asignado']) ? trim($_POST['id_celular_asignado']) : null,
        'zona_asignada' => !empty($_POST['zona_asignada']) ? trim($_POST['zona_asignada']) : null,
        'url_grupo_whatsapp' => !empty($_POST['url_grupo_whatsapp']) ? trim($_POST['url_grupo_whatsapp']) : null,
        'color_hex' => $_POST['color_hex'] ?? '#0073A8'
    ];

    // Determine Squad ID
    if ($isEdit) {
        $id_cuadrilla = $_POST['id_cuadrilla'];
        $sql = "UPDATE cuadrillas SET 
                nombre_cuadrilla = :nombre_cuadrilla,
                tipo_especialidad = :tipo_especialidad,
                estado_operativo = :estado_operativo,
                id_vehiculo_asignado = :id_vehiculo_asignado,
                id_celular_asignado = :id_celular_asignado,
                zona_asignada = :zona_asignada,
                url_grupo_whatsapp = :url_grupo_whatsapp,
                color_hex = :color_hex
                WHERE id_cuadrilla = :id_cuadrilla";
        $data['id_cuadrilla'] = $id_cuadrilla;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    } else {
        $sql = "INSERT INTO cuadrillas (
                    nombre_cuadrilla, tipo_especialidad, estado_operativo,
                    id_vehiculo_asignado, id_celular_asignado,
                    zona_asignada, url_grupo_whatsapp, color_hex
                ) VALUES (
                    :nombre_cuadrilla, :tipo_especialidad, :estado_operativo,
                    :id_vehiculo_asignado, :id_celular_asignado,
                    :zona_asignada, :url_grupo_whatsapp, :color_hex
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $id_cuadrilla = $pdo->lastInsertId();
    }

    // 2. Process WORK TYPES (cuadrilla_tipos_trabajo)
    // First, clear existing
    $pdo->prepare("DELETE FROM cuadrilla_tipos_trabajo WHERE id_cuadrilla = ?")->execute([$id_cuadrilla]);

    // Insert new
    $tipos_trabajo = $_POST['tipos_trabajo'] ?? [];
    if (!empty($tipos_trabajo)) {
        // Updated table uses (id_cuadrilla, id_tipologia)
        $stmtType = $pdo->prepare("INSERT INTO cuadrilla_tipos_trabajo (id_cuadrilla, id_tipologia) VALUES (?, ?)");

        // Fetch descriptions from Global Table (tipos_trabajos) to update legacy field
        $ids = $tipos_trabajo;
        foreach ($ids as $tid) {
            $stmtType->execute([$id_cuadrilla, $tid]);
        }

        // OPTIONAL: Update legacy field for backward compatibility
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        // Using 'nombre' from 'tipos_trabajos' (plural)
        $stmtNames = $pdo->prepare("SELECT nombre FROM tipos_trabajos WHERE id_tipologia IN ($placeholders)");
        $stmtNames->execute($ids);
        $names = $stmtNames->fetchAll(PDO::FETCH_COLUMN);
        $legacyString = implode(', ', $names); // e.g. "Veredas, Hidráulica"

        $pdo->prepare("UPDATE cuadrillas SET tipo_especialidad = ? WHERE id_cuadrilla = ?")->execute([$legacyString, $id_cuadrilla]);
    }

    // 3. Process MEMBERS (personal)
    // Strategy: 
    // a. Remove this squad from ALL personnel (Reset all to NULL where id_cuadrilla = THIS).
    // b. Set this squad for the IDs sent in the form.

    // Reset current members
    $pdo->prepare("UPDATE personal SET id_cuadrilla = NULL WHERE id_cuadrilla = ?")->execute([$id_cuadrilla]);

    // Assign new members
    if (!empty($_POST['miembros']) && is_array($_POST['miembros'])) {
        $memberIds = $_POST['miembros'];
        $stmtMember = $pdo->prepare("UPDATE personal SET id_cuadrilla = ? WHERE id_personal = ?");
        foreach ($memberIds as $mid) {
            $stmtMember->execute([$id_cuadrilla, $mid]);
        }
    }

    // 4. Process HERRAMIENTAS (tools)
    // Strategy:
    // a. Remove this squad from ALL tools (Reset to Disponible where id_cuadrilla_asignada = THIS).
    // b. Set this squad for the IDs sent in the form.

    // Reset current tools
    $pdo->prepare("UPDATE herramientas SET id_cuadrilla_asignada = NULL, estado = 'Disponible', fecha_asignacion = NULL WHERE id_cuadrilla_asignada = ?")->execute([$id_cuadrilla]);

    // Assign new tools
    if (!empty($_POST['herramientas']) && is_array($_POST['herramientas'])) {
        $toolIds = $_POST['herramientas'];
        $stmtTool = $pdo->prepare("UPDATE herramientas SET id_cuadrilla_asignada = ?, estado = 'Asignada', fecha_asignacion = CURDATE() WHERE id_herramienta = ?");
        foreach ($toolIds as $tid) {
            $stmtTool->execute([$id_cuadrilla, $tid]);
        }
    }

    // 5. Audit
    $action = $isEdit ? 'EDITAR' : 'CREAR';
    registrarAccion($action, 'cuadrillas', "Cuadrilla procesada: " . $data['nombre_cuadrilla'], $id_cuadrilla);

    $pdo->commit();
    header("Location: index.php?msg=success");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = urlencode($e->getMessage());
    $redirect = !empty($_POST['id_cuadrilla']) ? "form.php?id=" . $_POST['id_cuadrilla'] : "form.php";
    header("Location: $redirect&msg=error&details=$error");
}
?>