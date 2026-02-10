<?php
/**
 * API: Eliminar Gasto
 * 
 * Elimina un gasto pendiente y su comprobante asociado.
 * Solo permite eliminar gastos en estado 'Pendiente'.
 * 
 * @method POST
 * @body JSON {id: int}
 * @return JSON {success: bool, error?: string}
 */

require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Leer JSON del body
    $input = json_decode(file_get_contents('php://input'), true);
    $id_gasto = isset($input['id']) ? intval($input['id']) : 0;

    if ($id_gasto <= 0) {
        throw new Exception('ID de gasto inválido');
    }

    // Verificar que el gasto existe y está pendiente
    $stmt = $pdo->prepare("
        SELECT id_gasto, comprobante_path, estado 
        FROM Administracion_Gastos 
        WHERE id_gasto = ?
    ");
    $stmt->execute([$id_gasto]);
    $gasto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gasto) {
        throw new Exception('Gasto no encontrado');
    }

    if ($gasto['estado'] !== 'Pendiente') {
        throw new Exception('Solo se pueden eliminar gastos en estado Pendiente');
    }

    $pdo->beginTransaction();

    // Eliminar registro
    $stmtDel = $pdo->prepare("DELETE FROM Administracion_Gastos WHERE id_gasto = ?");
    $stmtDel->execute([$id_gasto]);

    // Eliminar archivo de comprobante
    if (!empty($gasto['comprobante_path'])) {
        $filePath = __DIR__ . '/../../../uploads/comprobantes/' . $gasto['comprobante_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Gasto eliminado correctamente'
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
