<?php
/**
 * API: Guardar Gasto
 * 
 * Recibe datos del formulario de gasto y los guarda en la base de datos.
 * Incluye:
 * - Validación de saldo disponible
 * - Detección de duplicados
 * - Subida de comprobante (imagen)
 * 
 * @method POST
 * @return JSON {success: bool, error?: string, id_gasto?: int}
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
    // RECOGER DATOS
    // ========================================================================

    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    $tipo_gasto = $_POST['tipo_gasto'] ?? '';
    $id_responsable = isset($_POST['id_responsable']) ? intval($_POST['id_responsable']) : 0;
    $fecha_gasto = $_POST['fecha_gasto'] ?? date('Y-m-d');
    $descripcion = $_POST['descripcion'] ?? '';

    // ========================================================================
    // VALIDACIONES BÁSICAS
    // ========================================================================

    if ($monto <= 0) {
        throw new Exception('El monto debe ser mayor a cero');
    }

    $tiposValidos = ['Ferreteria', 'Comida', 'Peajes', 'Combustible_Emergencia', 'Insumos_Oficina', 'Otros'];
    if (!in_array($tipo_gasto, $tiposValidos)) {
        throw new Exception('Tipo de gasto inválido');
    }

    if ($id_responsable <= 0) {
        throw new Exception('Debe seleccionar un responsable');
    }

    // Validar fecha
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha_gasto);
    if (!$fechaObj) {
        throw new Exception('Fecha inválida');
    }

    // ========================================================================
    // VALIDAR SALDO DISPONIBLE
    // ========================================================================

    // Obtener fondo fijo
    $stmtFondo = $pdo->query("SELECT monto_fondo FROM Administracion_Fondo_Fijo LIMIT 1");
    $montoFondo = floatval($stmtFondo->fetchColumn() ?: 100000);

    // Calcular gastos pendientes
    $stmtPendientes = $pdo->query("
        SELECT COALESCE(SUM(monto), 0) as total 
        FROM Administracion_Gastos 
        WHERE estado = 'Pendiente'
    ");
    $gastosPendientes = floatval($stmtPendientes->fetchColumn());

    $saldoDisponible = $montoFondo - $gastosPendientes;

    if ($monto > $saldoDisponible) {
        throw new Exception(
            "El monto ($" . number_format($monto, 2) . ") supera el saldo disponible " .
            "($" . number_format($saldoDisponible, 2) . "). " .
            "Se requiere realizar una rendición para reponer el fondo."
        );
    }

    // ========================================================================
    // DETECTAR DUPLICADOS
    // ========================================================================

    // Buscar gastos similares en los últimos 3 días
    $stmtDup = $pdo->prepare("
        SELECT id_gasto, fecha_gasto 
        FROM Administracion_Gastos 
        WHERE monto = ? 
          AND tipo_gasto = ? 
          AND fecha_gasto BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)
        LIMIT 1
    ");
    $stmtDup->execute([$monto, $tipo_gasto, $fecha_gasto, $fecha_gasto]);
    $duplicado = $stmtDup->fetch(PDO::FETCH_ASSOC);

    if ($duplicado) {
        throw new Exception(
            "Posible duplicado detectado: Ya existe un gasto de $" .
            number_format($monto, 2) . " del tipo '" . $tipo_gasto .
            "' con fecha " . date('d/m/Y', strtotime($duplicado['fecha_gasto'])) .
            ". Si es un gasto diferente, modifique el monto ligeramente o use otra categoría."
        );
    }

    // ========================================================================
    // PROCESAR COMPROBANTE (IMAGEN)
    // ========================================================================

    $comprobante_path = '';

    if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debe adjuntar una imagen del comprobante');
    }

    $file = $_FILES['comprobante'];

    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('El archivo debe ser una imagen (JPG, PNG, GIF o WebP)');
    }

    // Validar tamaño (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande (máximo 10MB)');
    }

    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        $extension = 'jpg';
    }
    $filename = 'gasto_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;

    // Ruta de destino
    $uploadDir = __DIR__ . '/../../../uploads/comprobantes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Error al guardar el comprobante');
    }

    $comprobante_path = $filename;

    // ========================================================================
    // GUARDAR EN BASE DE DATOS
    // ========================================================================

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO Administracion_Gastos 
        (monto, tipo_gasto, id_responsable, comprobante_path, fecha_gasto, descripcion, estado, usuario_creacion)
        VALUES (?, ?, ?, ?, ?, ?, 'Pendiente', ?)
    ");

    $usuario_id = $_SESSION['usuario_id'] ?? null;

    $stmt->execute([
        $monto,
        $tipo_gasto,
        $id_responsable,
        $comprobante_path,
        $fecha_gasto,
        $descripcion,
        $usuario_id
    ]);

    $id_gasto = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Gasto registrado correctamente',
        'id_gasto' => $id_gasto,
        'nuevo_saldo' => $saldoDisponible - $monto
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Eliminar archivo si se subió pero falló el guardado en BD
    if (!empty($comprobante_path) && isset($destPath) && file_exists($destPath)) {
        unlink($destPath);
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
