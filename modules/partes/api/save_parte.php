<?php
/**
 * API: Guardar Parte Diario
 * 
 * Guarda un parte de trabajo con materiales, personal y fotos.
 * Actualiza automáticamente el estado de la ODT y descuenta stock.
 * 
 * @method POST
 * @body FormData
 * @return JSON {success: bool, id_parte?: int, error?: string}
 */

require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // ========================================================================
    // VALIDACIÓN DE DATOS
    // ========================================================================

    $id_parte = isset($_POST['id_parte']) && !empty($_POST['id_parte']) ? intval($_POST['id_parte']) : null;
    $id_odt = intval($_POST['id_odt'] ?? 0);
    $id_cuadrilla = intval($_POST['id_cuadrilla'] ?? 0);
    // Compatibilidad: 'id_tipo_trabajo' (nuevo) vs 'id_tipologia' (legacy DB)
    $id_tipologia = intval($_POST['id_tipo_trabajo'] ?? $_POST['id_tipologia'] ?? 0);
    $fecha_ejecucion = $_POST['fecha_ejecucion'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $largo = floatval($_POST['largo'] ?? 0);
    $ancho = floatval($_POST['ancho'] ?? 0);
    $profundidad = floatval($_POST['profundidad'] ?? 0);
    $id_vehiculo = !empty($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : null;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $estado = $_POST['estado'] ?? 'Borrador';

    // Validaciones básicas
    if ($id_odt <= 0) {
        throw new Exception('Debe seleccionar una ODT');
    }

    if ($id_cuadrilla <= 0) {
        throw new Exception('Cuadrilla no válida');
    }

    if ($id_tipologia <= 0) {
        throw new Exception('Debe seleccionar un tipo de trabajo');
    }

    if (empty($fecha_ejecucion) || empty($hora_inicio) || empty($hora_fin)) {
        throw new Exception('Debe completar fecha y horarios');
    }

    // Validar estado
    $estadosValidos = ['Borrador', 'Enviado', 'Aprobado', 'Rechazado'];
    if (!in_array($estado, $estadosValidos)) {
        $estado = 'Borrador';
    }

    // ========================================================================
    // INICIO DE TRANSACCIÓN
    // ========================================================================

    $pdo->beginTransaction();

    // ========================================================================
    // GUARDAR PARTE PRINCIPAL
    // ========================================================================

    if ($id_parte) {
        // Actualizar parte existente
        $stmt = $pdo->prepare("
            UPDATE partes_diarios SET
                id_odt = ?,
                id_cuadrilla = ?,
                id_tipologia = ?,
                fecha_ejecucion = ?,
                hora_inicio = ?,
                hora_fin = ?,
                largo = ?,
                ancho = ?,
                profundidad = ?,
                id_vehiculo = ?,
                observaciones = ?,
                estado = ?
            WHERE id_parte = ?
        ");
        $stmt->execute([
            $id_odt,
            $id_cuadrilla,
            $id_tipologia,
            $fecha_ejecucion,
            $hora_inicio,
            $hora_fin,
            $largo,
            $ancho,
            $profundidad,
            $id_vehiculo,
            $observaciones,
            $estado,
            $id_parte
        ]);
    } else {
        // Crear nuevo parte
        $stmt = $pdo->prepare("
            INSERT INTO partes_diarios 
            (id_odt, id_cuadrilla, id_tipologia, fecha_ejecucion, hora_inicio, hora_fin,
             largo, ancho, profundidad, id_vehiculo, observaciones, estado, usuario_creacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id_odt,
            $id_cuadrilla,
            $id_tipologia,
            $fecha_ejecucion,
            $hora_inicio,
            $hora_fin,
            $largo,
            $ancho,
            $profundidad,
            $id_vehiculo,
            $observaciones,
            $estado,
            $_SESSION['usuario_id'] ?? null
        ]);
        $id_parte = $pdo->lastInsertId();
    }

    // ========================================================================
    // GUARDAR PERSONAL INTERVINIENTE
    // ========================================================================

    // Eliminar personal anterior
    $pdo->prepare("DELETE FROM partes_personal WHERE id_parte = ?")->execute([$id_parte]);

    // Insertar nuevo personal
    if (!empty($_POST['personal']) && is_array($_POST['personal'])) {
        $stmtPersonal = $pdo->prepare("INSERT INTO partes_personal (id_parte, id_personal) VALUES (?, ?)");
        foreach ($_POST['personal'] as $id_personal) {
            $stmtPersonal->execute([$id_parte, intval($id_personal)]);
        }
    }

    // ========================================================================
    // GUARDAR MATERIALES CONSUMIDOS (Lógica Explícita sin Triggers)
    // ========================================================================

    // 1. Obtener materiales previos para revertir consumo (Devolver al stock)
    $stmtOldMat = $pdo->prepare("SELECT id_material, cantidad FROM partes_materiales WHERE id_parte = ?");
    $stmtOldMat->execute([$id_parte]);
    $oldMaterials = $stmtOldMat->fetchAll(PDO::FETCH_ASSOC);

    $stmtRevertStock = $pdo->prepare("
        UPDATE stock_cuadrilla 
        SET cantidad = cantidad + ? 
        WHERE id_cuadrilla = ? AND id_material = ?
    ");

    foreach ($oldMaterials as $old) {
        $stmtRevertStock->execute([$old['cantidad'], $id_cuadrilla, $old['id_material']]);
    }

    // 2. Eliminar registros previos
    $pdo->prepare("DELETE FROM partes_materiales WHERE id_parte = ?")->execute([$id_parte]);

    // 3. Insertar nuevos materiales y descontar stock
    if (!empty($_POST['materiales']) && is_array($_POST['materiales'])) {
        $stmtMaterial = $pdo->prepare("INSERT INTO partes_materiales (id_parte, id_material, cantidad) VALUES (?, ?, ?)");
        $stmtDeductStock = $pdo->prepare("
            UPDATE stock_cuadrilla 
            SET cantidad = cantidad - ? 
            WHERE id_cuadrilla = ? AND id_material = ?
        ");

        foreach ($_POST['materiales'] as $mat) {
            if (!empty($mat['id']) && !empty($mat['cantidad']) && floatval($mat['cantidad']) > 0) {
                $cantidad = floatval($mat['cantidad']);
                $id_material = intval($mat['id']);

                // Insertar registro
                $stmtMaterial->execute([$id_parte, $id_material, $cantidad]);

                // Descontar stock explícitamente
                $stmtDeductStock->execute([$cantidad, $id_cuadrilla, $id_material]);
            }
        }
    }

    // ========================================================================
    // GUARDAR FOTOS
    // ========================================================================

    $uploadDir = __DIR__ . '/../../../uploads/partes/' . $id_parte . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $tiposFoto = ['foto_inicio' => 'Inicio', 'foto_proceso' => 'Proceso', 'foto_fin' => 'Fin'];

    foreach ($tiposFoto as $inputName => $tipoFoto) {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$inputName];

            // Validar tipo de archivo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowedTypes)) {
                continue; // Saltar archivos no válidos
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = strtolower($tipoFoto) . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Guardar referencia en BD (usar REPLACE para actualizar si ya existe)
                $stmtFoto = $pdo->prepare("
                    REPLACE INTO partes_fotos (id_parte, tipo_foto, ruta_archivo, fecha_captura)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmtFoto->execute([
                    $id_parte,
                    $tipoFoto,
                    $id_parte . '/' . $filename
                ]);
            }
        }
    }

    // ========================================================================
    // ACTUALIZAR ESTADO DE ODT (si el parte fue enviado)
    // ========================================================================

    if ($estado === 'Enviado') {
        $pdo->prepare("
            UPDATE ODT_Maestro 
            SET estado_gestion = 'Ejecutado' 
            WHERE id_odt = ? AND estado_gestion != 'Finalizado'
        ")->execute([$id_odt]);
    }

    // ========================================================================
    // COMMIT
    // ========================================================================

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id_parte' => $id_parte,
        'message' => $estado === 'Borrador' ? 'Borrador guardado' : 'Parte enviado correctamente'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
