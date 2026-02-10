<?php
/**
 * Save handler for vehicles — handles new fields: insurance, photo, Gestya, maintenance
 * [!] ARCH: File uploads + bulk maintenance insert/update
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

try {
    $isEdit = !empty($_POST['id_vehiculo']);
    $idVehiculo = $isEdit ? intval($_POST['id_vehiculo']) : null;

    // ─── 1. Handle File Uploads ───
    $uploadDir = '../../uploads/vehiculos/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    $polizaDir = $uploadDir . 'polizas/';
    if (!is_dir($polizaDir))
        mkdir($polizaDir, 0777, true);

    // Photo upload
    $fotoEstado = null;
    if (!empty($_FILES['foto_estado']['name']) && $_FILES['foto_estado']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_estado']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $fotoEstado = 'vehiculo_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto_estado']['tmp_name'], $uploadDir . $fotoEstado);
        }
    }

    // PDF poliza upload
    $polizaPdf = null;
    if (!empty($_FILES['seguro_poliza_pdf']['name']) && $_FILES['seguro_poliza_pdf']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['seguro_poliza_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $polizaPdf = 'poliza_' . time() . '_' . uniqid() . '.pdf';
            move_uploaded_file($_FILES['seguro_poliza_pdf']['tmp_name'], $polizaDir . $polizaPdf);
        }
    }

    // ─── 2. Prepare Vehicle Data ───
    $data = [
        'patente' => strtoupper(trim($_POST['patente'])),
        'tipo' => $_POST['tipo'],
        'tipo_combustible' => $_POST['tipo_combustible'] ?? 'Diesel',
        'id_cuadrilla' => !empty($_POST['id_cuadrilla']) ? intval($_POST['id_cuadrilla']) : null,
        'marca' => trim($_POST['marca']) ?: null,
        'modelo' => trim($_POST['modelo']) ?: null,
        'anio' => !empty($_POST['anio']) ? intval($_POST['anio']) : null,
        'estado' => $_POST['estado'],
        'vencimiento_vtv' => !empty($_POST['vencimiento_vtv']) ? $_POST['vencimiento_vtv'] : null,
        'vencimiento_seguro' => !empty($_POST['vencimiento_seguro']) ? $_POST['vencimiento_seguro'] : null,
        'nivel_aceite' => $_POST['nivel_aceite'] ?? 'OK',
        'nivel_combustible' => $_POST['nivel_combustible'] ?? 'Medio',
        'estado_frenos' => $_POST['estado_frenos'] ?? 'OK',
        'km_actual' => !empty($_POST['km_actual']) ? intval($_POST['km_actual']) : 0,
        'proximo_service_km' => !empty($_POST['proximo_service_km']) ? intval($_POST['proximo_service_km']) : null,
        'fecha_ultimo_inventario' => !empty($_POST['fecha_ultimo_inventario']) ? $_POST['fecha_ultimo_inventario'] : null,
        'costo_reposicion' => !empty($_POST['costo_reposicion']) ? floatval($_POST['costo_reposicion']) : null,
        'observaciones' => trim($_POST['observaciones']) ?: null,
        // Insurance
        'seguro_nombre' => trim($_POST['seguro_nombre'] ?? '') ?: null,
        'seguro_telefono' => trim($_POST['seguro_telefono'] ?? '') ?: null,
        'seguro_grua_telefono' => trim($_POST['seguro_grua_telefono'] ?? '') ?: null,
        'seguro_cobertura' => $_POST['seguro_cobertura'] ?: null,
        'seguro_franquicia' => !empty($_POST['seguro_franquicia']) ? floatval($_POST['seguro_franquicia']) : null,
        'seguro_valor' => !empty($_POST['seguro_valor']) ? floatval($_POST['seguro_valor']) : null,
        // Gestya
        'gestya_instalado' => isset($_POST['gestya_instalado']) ? 1 : 0,
        'gestya_fecha_instalacion' => !empty($_POST['gestya_fecha_instalacion']) ? $_POST['gestya_fecha_instalacion'] : null,
        'gestya_lugar' => trim($_POST['gestya_lugar'] ?? '') ?: null,
    ];

    // Only update photo/poliza if new files were uploaded
    if ($fotoEstado)
        $data['foto_estado'] = $fotoEstado;
    if ($polizaPdf)
        $data['seguro_poliza_pdf'] = $polizaPdf;

    // ─── 3. Build SQL ───
    if ($isEdit) {
        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "$key = :$key";
        }
        $sql = "UPDATE vehiculos SET " . implode(', ', $setClauses) . " WHERE id_vehiculo = :id_vehiculo";
        $data['id_vehiculo'] = $idVehiculo;
    } else {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO vehiculos ($columns) VALUES ($placeholders)";
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    if (!$isEdit) {
        $idVehiculo = $pdo->lastInsertId();
    }

    // ─── 4. Process Maintenance Items ───
    $mantenimientoTypes = [
        'Filtro Aceite',
        'Filtro Aire',
        'Filtro Aire Secundario',
        'Filtro Combustible 1rio',
        'Filtro Combustible 2rio',
        'Filtro Habitáculo',
        'Filtro Hidráulico 1',
        'Filtro Hidráulico 2',
        'Aceite Motor',
        'Aceite Diferencial',
        'Aceite Caja'
    ];

    // Delete existing maintenance for this vehicle and re-insert
    $pdo->prepare("DELETE FROM vehiculos_mantenimiento WHERE id_vehiculo = ?")->execute([$idVehiculo]);

    $stmtMant = $pdo->prepare("
        INSERT INTO vehiculos_mantenimiento 
        (id_vehiculo, tipo, codigo, marca, equivalencia, tipo_aceite, cantidad, precio_usd, precio_ars) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($mantenimientoTypes as $tipo) {
        $prefix = 'mant_' . str_replace(' ', '_', $tipo);

        $codigo = trim($_POST[$prefix . '_codigo'] ?? '');
        $marca = trim($_POST[$prefix . '_marca'] ?? '');
        $equivalencia = trim($_POST[$prefix . '_equivalencia'] ?? '');
        $tipoAceite = trim($_POST[$prefix . '_tipo_aceite'] ?? '');
        $cantidad = !empty($_POST[$prefix . '_cantidad']) ? floatval($_POST[$prefix . '_cantidad']) : null;
        $precioUsd = !empty($_POST[$prefix . '_precio_usd']) ? floatval($_POST[$prefix . '_precio_usd']) : null;
        $precioArs = !empty($_POST[$prefix . '_precio_ars']) ? floatval($_POST[$prefix . '_precio_ars']) : null;

        // Only insert if there's at least some data
        if ($codigo || $marca || $equivalencia || $tipoAceite || $cantidad || $precioUsd || $precioArs) {
            $stmtMant->execute([
                $idVehiculo,
                $tipo,
                $codigo ?: null,
                $marca ?: null,
                $equivalencia ?: null,
                $tipoAceite ?: null,
                $cantidad,
                $precioUsd,
                $precioArs
            ]);
        }
    }

    $pdo->commit();

    // Audit
    $action = $isEdit ? 'EDITAR' : 'CREAR';
    registrarAccion($action, 'vehiculos', "Vehículo $action: " . $data['patente'], $idVehiculo);

    header("Location: index.php?msg=success");

} catch (PDOException $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();

    if ($e->getCode() == 23000) {
        $error = urlencode("La patente ya existe en el sistema.");
    } else {
        $error = urlencode($e->getMessage());
    }

    $redirect = isset($_POST['id_vehiculo']) && $_POST['id_vehiculo']
        ? "form.php?id=" . $_POST['id_vehiculo']
        : "form.php";

    header("Location: $redirect&msg=error&details=$error");
}
?>