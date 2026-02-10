<?php
/**
 * API: Rendición de Gastos
 * 
 * Marca un lote de gastos como "Rendidos" y genera un resumen.
 * Crea un registro de rendición para tracking.
 * 
 * @method POST
 * @body JSON {ids: int[]}
 * @return JSON {success: bool, id_rendicion?: int, pdf_url?: string, error?: string}
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
    $ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

    if (empty($ids)) {
        throw new Exception('Debe seleccionar al menos un gasto para rendir');
    }

    // Sanitizar IDs
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function ($id) {
        return $id > 0; });

    if (empty($ids)) {
        throw new Exception('IDs inválidos');
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Verificar que todos los gastos existen y están pendientes
    $stmt = $pdo->prepare("
        SELECT id_gasto, monto, estado 
        FROM Administracion_Gastos 
        WHERE id_gasto IN ($placeholders)
    ");
    $stmt->execute($ids);
    $gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($gastos) !== count($ids)) {
        throw new Exception('Algunos gastos no fueron encontrados');
    }

    $montoTotal = 0;
    foreach ($gastos as $g) {
        if ($g['estado'] !== 'Pendiente') {
            throw new Exception('Solo se pueden rendir gastos en estado Pendiente');
        }
        $montoTotal += floatval($g['monto']);
    }

    $pdo->beginTransaction();

    // 1. Crear registro de rendición
    $stmtRend = $pdo->prepare("
        INSERT INTO Administracion_Rendiciones 
        (monto_total, cantidad_comprobantes, usuario_rendicion, estado)
        VALUES (?, ?, ?, 'Pendiente_Reposicion')
    ");

    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $stmtRend->execute([$montoTotal, count($ids), $usuario_id]);
    $id_rendicion = $pdo->lastInsertId();

    // 2. Actualizar gastos como rendidos
    $stmtUpdate = $pdo->prepare("
        UPDATE Administracion_Gastos 
        SET estado = 'Rendido', id_rendicion = ?
        WHERE id_gasto IN ($placeholders)
    ");
    $stmtUpdate->execute(array_merge([$id_rendicion], $ids));

    $pdo->commit();

    // Generar resumen (simplificado - en producción se generaría PDF)
    $resumen = [
        'id_rendicion' => $id_rendicion,
        'fecha' => date('Y-m-d H:i:s'),
        'cantidad' => count($ids),
        'monto_total' => $montoTotal
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Rendición realizada correctamente',
        'id_rendicion' => $id_rendicion,
        'resumen' => $resumen,
        // En producción: 'pdf_url' => 'api/generate_pdf.php?id=' . $id_rendicion
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
