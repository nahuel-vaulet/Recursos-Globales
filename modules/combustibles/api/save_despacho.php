<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo->beginTransaction();

    $id_tanque = $_POST['id_tanque'] ?? null;
    $id_vehiculo = $_POST['id_vehiculo'] ?? null;
    $id_cuadrilla = $_POST['id_cuadrilla'] ?? null;
    $litros = floatval($_POST['litros'] ?? 0);
    $odometro = floatval($_POST['odometro_actual'] ?? 0);
    $conductor = $_POST['usuario_conductor'] ?? '';
    $destino = $_POST['destino_obra'] ?? '';
    $fecha = $_POST['fecha_hora'] ?? date('Y-m-d H:i:s');

    // Allow manual selection of dispatcher, fallback to current session user
    $usuario_despacho = !empty($_POST['usuario_despacho']) ? $_POST['usuario_despacho'] : ($_SESSION['usuario_id'] ?? 0);

    if (empty($id_tanque)) {
        throw new Exception("ID de Tanque no recibido.");
    }

    // Validation: Check Stock
    $stmtCheck = $pdo->prepare("SELECT stock_actual, nombre, tipo_combustible FROM combustibles_tanques WHERE id_tanque = ?");
    $stmtCheck->execute([$id_tanque]);
    $tank = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$tank) {
        throw new Exception("Tanque no encontrado en base de datos (ID: $id_tanque)");
    }

    $stock = floatval($tank['stock_actual']);

    if ($stock < $litros) {
        throw new Exception("Stock insuficiente. Disponible: " . number_format($stock, 2) . " L. Solicitado: " . number_format($litros, 2) . " L");
    }

    // 1. Insert Despacho
    // [OPTIMIZED] Schema is now fixed via migration. id_cuadrilla is assumed to exist.
    $stmt = $pdo->prepare("INSERT INTO combustibles_despachos (id_tanque, id_vehiculo, id_cuadrilla, fecha_hora, litros, odometro_actual, usuario_despacho, usuario_conductor, destino_obra) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_tanque, $id_vehiculo, $id_cuadrilla, $fecha, $litros, $odometro, $usuario_despacho, $conductor, $destino]);

    $id_despacho = $pdo->lastInsertId();

    // 2. Update Tank Stock
    $stmtUpd = $pdo->prepare("UPDATE combustibles_tanques SET stock_actual = stock_actual - ? WHERE id_tanque = ?");
    $stmtUpd->execute([$litros, $id_tanque]);

    // 3. Update Vehicle Odometer
    if ($id_vehiculo && $odometro > 0) {
        $stmtVeh = $pdo->prepare("UPDATE vehiculos SET km_actual = ? WHERE id_vehiculo = ?");
        $stmtVeh->execute([$odometro, $id_vehiculo]);
    }

    // 4. Record movement in legacy 'movimientos' table and update squad stock
    if ($id_cuadrilla) {
        // Resolve Material ID
        $tType = strtolower($tank['tipo_combustible'] ?? '');
        $idMaterial = (strpos($tType, 'nafta') !== false) ? 1000 : 1001;

        // Final sanity check for Material ID in DB
        $checkMat = $pdo->prepare("SELECT id_material FROM maestro_materiales WHERE id_material = ?");
        $checkMat->execute([$idMaterial]);
        if (!$checkMat->fetch())
            $idMaterial = 999; // Fallback to Universal

        // A. Record Movement
        $stmtMov = $pdo->prepare("
            INSERT INTO movimientos (nro_documento, tipo_movimiento, id_material, cantidad, id_cuadrilla, usuario_despacho, fecha_hora)
            VALUES (?, 'Entrega_Oficina_Cuadrilla', ?, ?, ?, ?, NOW())
        ");
        $stmtMov->execute([
            'DESP-LGC-' . $id_despacho,
            $idMaterial,
            $litros,
            $id_cuadrilla,
            $usuario_despacho
        ]);

        // B. Update Squad Stock (UPSERT)
        $stmtCheckStk = $pdo->prepare("SELECT COUNT(*) FROM stock_cuadrilla WHERE id_cuadrilla = ? AND id_material = ?");
        $stmtCheckStk->execute([$id_cuadrilla, $idMaterial]);
        if ((int) $stmtCheckStk->fetchColumn() > 0) {
            $stmtUpStk = $pdo->prepare("UPDATE stock_cuadrilla SET cantidad = cantidad + ? WHERE id_cuadrilla = ? AND id_material = ?");
            $stmtUpStk->execute([$litros, $id_cuadrilla, $idMaterial]);
        } else {
            $stmtInsStk = $pdo->prepare("INSERT INTO stock_cuadrilla (id_cuadrilla, id_material, cantidad) VALUES (?, ?, ?)");
            $stmtInsStk->execute([$id_cuadrilla, $idMaterial, $litros]);
        }
    }

    $pdo->commit();

    // Get cuadrilla name for remito if available
    $nombre_cuadrilla = '';
    if ($id_cuadrilla) {
        $stmtC = $pdo->prepare("SELECT nombre_cuadrilla FROM cuadrillas WHERE id_cuadrilla = ?");
        $stmtC->execute([$id_cuadrilla]);
        $cuad = $stmtC->fetch(PDO::FETCH_ASSOC);
        $nombre_cuadrilla = $cuad ? $cuad['nombre_cuadrilla'] : '';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Despacho registrado correctamente',
        'id_despacho' => $id_despacho,
        'tank_name' => $tank['nombre'],
        'fuel_type' => $tank['tipo_combustible'],
        'nombre_cuadrilla' => $nombre_cuadrilla
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>