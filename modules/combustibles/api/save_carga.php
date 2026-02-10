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

    $destino_tipo = $_POST['destino_tipo'] ?? 'stock';
    $id_tanque = !empty($_POST['id_tanque']) ? $_POST['id_tanque'] : null;

    // Validation
    if ($destino_tipo === 'stock' && empty($id_tanque)) {
        throw new Exception("Debe seleccionar un tanque de destino para cargas a stock.");
    }

    // For direct vehicle charge, we might not have a tank, so we need fuel type
    $tipo_combustible = $_POST['tipo_combustible'] ?? null;

    $id_cuadrilla = !empty($_POST['id_cuadrilla']) ? $_POST['id_cuadrilla'] : null;
    $id_vehiculo = !empty($_POST['id_vehiculo']) ? $_POST['id_vehiculo'] : null;
    $conductor = !empty($_POST['conductor']) ? $_POST['conductor'] : null;

    $litros = floatval($_POST['litros']);
    $precio = floatval($_POST['precio_unitario'] ?? 0);
    $proveedor = $_POST['proveedor'] ?? '';
    $factura = $_POST['nro_factura'] ?? '';
    $fecha = $_POST['fecha_hora'] ?? date('Y-m-d H:i:s');
    $usuario_id = $_SESSION['usuario_id'] ?? 0;

    // 0. Handle File Upload
    $foto_path = null;
    if (isset($_FILES['foto_ticket']) && $_FILES['foto_ticket']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../../uploads/tickets/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['foto_ticket']['name'], PATHINFO_EXTENSION);
        $filename = 'ticket_' . time() . '_' . uniqid() . '.' . $ext;
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['foto_ticket']['tmp_name'], $targetFile)) {
            $foto_path = 'uploads/tickets/' . $filename;
        }
    }

    // 0.1 Check if column exists logic REMOVED. Schema must be consistent.
    // Migration script `sql/migration_consistency.sql` ensures foto_ticket column exists.

    // 1. Insert Carga
    $stmt = $pdo->prepare("INSERT INTO combustibles_cargas (id_tanque, destino_tipo, tipo_combustible, id_cuadrilla, id_vehiculo, conductor, fecha_hora, litros, precio_unitario, proveedor, nro_factura, foto_ticket, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_tanque, $destino_tipo, $tipo_combustible, $id_cuadrilla, $id_vehiculo, $conductor, $fecha, $litros, $precio, $proveedor, $factura, $foto_path, $usuario_id]);

    // 2. Update Tank Stock (ONLY IF DESTINATION IS STOCK)
    if ($destino_tipo === 'stock' && $id_tanque) {
        $stmtUpd = $pdo->prepare("UPDATE combustibles_tanques SET stock_actual = stock_actual + ? WHERE id_tanque = ?");
        $stmtUpd->execute([$litros, $id_tanque]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Carga registrada correctamente']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>