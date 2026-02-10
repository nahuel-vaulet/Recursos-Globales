<?php
/**
 * Módulo: Personal - Guardar (Create/Update)
 * Maneja subida de archivos, nuevos campos de legajo y workflow de Onboarding.
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id_personal'] ?? null;
    $action_type = $_POST['action_type'] ?? 'save_partial'; // save_partial, finalize, skip

    // Datos Básicos
    $nombre_apellido = $_POST['nombre_apellido'];
    $dni = $_POST['dni'];
    $cuil = $_POST['cuil'] ?? '';
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $estado_civil = $_POST['estado_civil'] ?? '';
    $telefono_personal = $_POST['telefono_personal'] ?? '';
    $domicilio = $_POST['domicilio'] ?? '';

    // Asignación
    $rol = $_POST['rol'];
    $id_cuadrilla = !empty($_POST['id_cuadrilla']) ? $_POST['id_cuadrilla'] : null;
    $fecha_ingreso = !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null;

    // Tareas (Array to string)
    $tareas = isset($_POST['tareas_desempenadas']) ? implode(',', $_POST['tareas_desempenadas']) : '';

    // Seguridad / Carnet
    $tiene_carnet = isset($_POST['tiene_carnet']) ? 1 : 0;
    $tipo_carnet = $tiene_carnet ? ($_POST['tipo_carnet'] ?? '') : null;
    $vencimiento_carnet_conducir = ($tiene_carnet && !empty($_POST['vencimiento_carnet_conducir'])) ? $_POST['vencimiento_carnet_conducir'] : null;

    // EPP
    $talle_camisa = $_POST['talle_camisa'] ?? '';
    $talle_pantalon = $_POST['talle_pantalon'] ?? '';
    $talle_remera = $_POST['talle_remera'] ?? '';
    $talle_calzado = $_POST['talle_calzado'] ?? '';
    $seguro_art = $_POST['seguro_art'] ?? '';
    $fecha_ultima_entrega_epp = !empty($_POST['fecha_ultima_entrega_epp']) ? $_POST['fecha_ultima_entrega_epp'] : null;

    // Salud
    $obra_social = $_POST['obra_social'] ?? '';
    $obra_social_telefono = $_POST['obra_social_telefono'] ?? '';
    $obra_social_lugar_atencion = $_POST['obra_social_lugar_atencion'] ?? '';
    $grupo_sanguineo = $_POST['grupo_sanguineo'] ?? '';
    $alergias_condiciones = $_POST['alergias_condiciones'] ?? '';

    // Familia
    $contacto_emergencia_nombre = $_POST['contacto_emergencia_nombre'] ?? '';
    $numero_emergencia = $_POST['numero_emergencia'] ?? '';
    $contacto_emergencia_parentesco = $_POST['contacto_emergencia_parentesco'] ?? '';
    $personas_a_cargo = $_POST['personas_a_cargo'] ?? '';

    // Admin
    $cbu_alias = $_POST['cbu_alias'] ?? '';
    $link_legajo_digital = $_POST['link_legajo_digital'] ?? '';

    // Onboarding Workflow Fields
    $fecha_examen_preocupacional = !empty($_POST['fecha_examen_preocupacional']) ? $_POST['fecha_examen_preocupacional'] : null;
    $empresa_examen_preocupacional = $_POST['empresa_examen_preocupacional'] ?? '';
    $fecha_firm_hys = !empty($_POST['fecha_firma_hys']) ? $_POST['fecha_firma_hys'] : null;
    $motivo_pendiente = $_POST['motivo_pendiente'] ?? null;

    // Determine Status Logic
    // Default to existing or Incompleto if new
    $estado_documentacion = 'Incompleto';
    if ($action_type === 'finalize') {
        $estado_documentacion = 'Completo';
        $motivo_pendiente = null; // Clear reason if finalizing
    } elseif ($action_type === 'skip') {
        $estado_documentacion = 'Pendiente';
        // Motivo sets from POST
    } else {
        // Save Partial. Keep existing status if editing, or Incompleto if new.
        if ($id) {
            // Fetch existing status to preserve it
            $stmt = $pdo->prepare("SELECT estado_documentacion FROM personal WHERE id_personal = ?");
            $stmt->execute([$id]);
            $curr = $stmt->fetchColumn();
            $estado_documentacion = $curr ?: 'Incompleto';
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Manejo de Archivos
        $foto_usuario = null;
        $foto_carnet = null;
        $planilla_epp = null;
        $documento_preocupacional = null;
        $documento_firmado = null;

        // Helper upload function
        function uploadFile($inputName, $targetDir, $prefix)
        {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
                $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
                $filename = $prefix . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetDir . '/' . $filename)) {
                    return $filename;
                }
            }
            return null;
        }

        // Uploads
        $new_foto = uploadFile('foto_usuario', '../../uploads/personal/fotos', 'user');
        $new_carnet = uploadFile('foto_carnet', '../../uploads/personal/carnets', 'carnet');
        $new_epp = uploadFile('planilla_epp', '../../uploads/personal/epp', 'epp');
        $new_preocupacional = uploadFile('documento_preocupacional', '../../uploads/personal/legajos', 'medico');
        $new_firmado = uploadFile('documento_firmado', '../../uploads/personal/legajos', 'ficha');

        // Logic to keep old files if no new upload
        if ($id) {
            $stmt = $pdo->prepare("SELECT foto_usuario, foto_carnet, planilla_epp, documento_preocupacional, documento_firmado FROM personal WHERE id_personal = ?");
            $stmt->execute([$id]);
            $currentFiles = $stmt->fetch(PDO::FETCH_ASSOC);

            $foto_usuario = $new_foto ?: $currentFiles['foto_usuario'];
            $foto_carnet = $new_carnet ?: $currentFiles['foto_carnet'];
            $planilla_epp = $new_epp ?: $currentFiles['planilla_epp'];
            $documento_preocupacional = $new_preocupacional ?: $currentFiles['documento_preocupacional'];
            $documento_firmado = $new_firmado ?: $currentFiles['documento_firmado'];
        } else {
            $foto_usuario = $new_foto;
            $foto_carnet = $new_carnet;
            $planilla_epp = $new_epp;
            $documento_preocupacional = $new_preocupacional;
            $documento_firmado = $new_firmado;
        }

        // Insert / Update
        if ($id) {
            $sql = "UPDATE personal SET 
                    nombre_apellido = ?, dni = ?, cuil = ?, fecha_nacimiento = ?, estado_civil = ?, 
                    telefono_personal = ?, domicilio = ?, rol = ?, id_cuadrilla = ?, fecha_ingreso = ?,
                    foto_usuario = ?, tiene_carnet = ?, tipo_carnet = ?, vencimiento_carnet_conducir = ?, foto_carnet = ?,
                    talle_camisa = ?, talle_pantalon = ?, talle_remera = ?, talle_calzado = ?, 
                    seguro_art = ?, fecha_ultima_entrega_epp = ?, planilla_epp = ?,
                    obra_social = ?, obra_social_telefono = ?, obra_social_lugar_atencion = ?, 
                    grupo_sanguineo = ?, alergias_condiciones = ?,
                    contacto_emergencia_nombre = ?, numero_emergencia = ?, contacto_emergencia_parentesco = ?, personas_a_cargo = ?,
                    cbu_alias = ?, link_legajo_digital = ?, tareas_desempenadas = ?,
                    estado_documentacion = ?, motivo_pendiente = ?, fecha_examen_preocupacional = ?, 
                    empresa_examen_preocupacional = ?, fecha_firma_hys = ?, documento_preocupacional = ?, documento_firmado = ?
                    WHERE id_personal = ?";
            $params = [
                $nombre_apellido,
                $dni,
                $cuil,
                $fecha_nacimiento,
                $estado_civil,
                $telefono_personal,
                $domicilio,
                $rol,
                $id_cuadrilla,
                $fecha_ingreso,
                $foto_usuario,
                $tiene_carnet,
                $tipo_carnet,
                $vencimiento_carnet_conducir,
                $foto_carnet,
                $talle_camisa,
                $talle_pantalon,
                $talle_remera,
                $talle_calzado,
                $seguro_art,
                $fecha_ultima_entrega_epp,
                $planilla_epp,
                $obra_social,
                $obra_social_telefono,
                $obra_social_lugar_atencion,
                $grupo_sanguineo,
                $alergias_condiciones,
                $contacto_emergencia_nombre,
                $numero_emergencia,
                $contacto_emergencia_parentesco,
                $personas_a_cargo,
                $cbu_alias,
                $link_legajo_digital,
                $tareas,
                $estado_documentacion,
                $motivo_pendiente,
                $fecha_examen_preocupacional,
                $empresa_examen_preocupacional,
                $fecha_firm_hys,
                $documento_preocupacional,
                $documento_firmado,
                $id
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "INSERT INTO personal (
                    nombre_apellido, dni, cuil, fecha_nacimiento, estado_civil, 
                    telefono_personal, domicilio, rol, id_cuadrilla, fecha_ingreso,
                    foto_usuario, tiene_carnet, tipo_carnet, vencimiento_carnet_conducir, foto_carnet,
                    talle_camisa, talle_pantalon, talle_remera, talle_calzado, 
                    seguro_art, fecha_ultima_entrega_epp, planilla_epp,
                    obra_social, obra_social_telefono, obra_social_lugar_atencion,
                    grupo_sanguineo, alergias_condiciones,
                    contacto_emergencia_nombre, numero_emergencia, contacto_emergencia_parentesco, personas_a_cargo,
                    cbu_alias, link_legajo_digital, tareas_desempenadas,
                    estado_documentacion, motivo_pendiente, fecha_examen_preocupacional, 
                    empresa_examen_preocupacional, fecha_firma_hys, documento_preocupacional, documento_firmado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $nombre_apellido,
                $dni,
                $cuil,
                $fecha_nacimiento,
                $estado_civil,
                $telefono_personal,
                $domicilio,
                $rol,
                $id_cuadrilla,
                $fecha_ingreso,
                $foto_usuario,
                $tiene_carnet,
                $tipo_carnet,
                $vencimiento_carnet_conducir,
                $foto_carnet,
                $talle_camisa,
                $talle_pantalon,
                $talle_remera,
                $talle_calzado,
                $seguro_art,
                $fecha_ultima_entrega_epp,
                $planilla_epp,
                $obra_social,
                $obra_social_telefono,
                $obra_social_lugar_atencion,
                $grupo_sanguineo,
                $alergias_condiciones,
                $contacto_emergencia_nombre,
                $numero_emergencia,
                $contacto_emergencia_parentesco,
                $personas_a_cargo,
                $cbu_alias,
                $link_legajo_digital,
                $tareas,
                $estado_documentacion,
                $motivo_pendiente,
                $fecha_examen_preocupacional,
                $empresa_examen_preocupacional,
                $fecha_firm_hys,
                $documento_preocupacional,
                $documento_firmado
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $redirect_id = $pdo->lastInsertId();
        }

        if (!empty($id)) {
            $redirect_id = $id;
        }

        $pdo->commit();

        // Redirect Logic based on Action Type
        if ($action_type === 'save_partial') {
            // Stay on form, go to legajo tab (or keeping current tab would be better, but legajo is likely where they want to be)
            header("Location: form.php?id=$redirect_id&tab=legajo&msg=saved");
        } else {
            // Finalize or Skip -> Go to Dashboard
            header("Location: index.php?msg=saved");
        }
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error al guardar: " . $e->getMessage());
    }
}
?>