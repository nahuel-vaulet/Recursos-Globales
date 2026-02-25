<?php
/**
 * [!] ARCH: MovimientoController — Entradas/salidas de stock
 * [✓] AUDIT: Migrado desde api/movimientos.php con transacciones y stock update
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class MovimientoController
{
    // ─── GET /api/movimientos ───────────────────────────

    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['material_id'])) {
            $where[] = "m.material_id = :mat_id";
            $params['mat_id'] = (int) $_GET['material_id'];
        }

        if (!empty($_GET['cuadrilla_id'])) {
            $where[] = "m.cuadrilla_id = :crew_id";
            $params['crew_id'] = (int) $_GET['cuadrilla_id'];
        }

        if (!empty($_GET['tipo']) && in_array($_GET['tipo'], ['entrada', 'salida'])) {
            $where[] = "m.tipo = :tipo";
            $params['tipo'] = $_GET['tipo'];
        }

        if (!empty($_GET['fecha_desde'])) {
            $where[] = "m.fecha >= :f_desde";
            $params['f_desde'] = $_GET['fecha_desde'] . ' 00:00:00';
        }

        if (!empty($_GET['fecha_hasta'])) {
            $where[] = "m.fecha <= :f_hasta";
            $params['f_hasta'] = $_GET['fecha_hasta'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $stmt = $pdo->prepare("
            SELECT m.*, 
                   mat.nombre as material_nombre, mat.codigo as material_codigo, mat.unidad_medida,
                   c.nombre_cuadrilla,
                   u.nombre as usuario_nombre
            FROM movimientos m
            JOIN materiales mat ON m.material_id = mat.id
            LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id_cuadrilla
            LEFT JOIN usuarios u ON m.usuario_id = u.id_usuario
            WHERE {$whereClause}
            ORDER BY m.fecha DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/movimientos/{id} ──────────────────────

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT m.*, 
                   mat.nombre as material_nombre, mat.unidad_medida,
                   c.nombre_cuadrilla,
                   u.nombre as usuario_nombre
            FROM movimientos m
            JOIN materiales mat ON m.material_id = mat.id
            LEFT JOIN cuadrillas c ON m.cuadrilla_id = c.id_cuadrilla
            LEFT JOIN usuarios u ON m.usuario_id = u.id_usuario
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $mov = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$mov) {
            Response::json(['error' => 'ERR-MOV-404', 'message' => 'Movimiento no encontrado'], 404);
            return;
        }

        Response::json(['data' => $mov]);
    }

    // ─── POST /api/movimientos ──────────────────────────

    public static function store(): void
    {
        $auth = AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        // Validate
        if (empty($body['material_id']) || empty($body['tipo']) || empty($body['cantidad'])) {
            Response::json(['error' => 'ERR-MOV-FIELD', 'message' => 'material_id, tipo y cantidad requeridos'], 400);
            return;
        }

        if (!in_array($body['tipo'], ['entrada', 'salida'])) {
            Response::json(['error' => 'ERR-MOV-TYPE', 'message' => 'Tipo debe ser entrada o salida'], 400);
            return;
        }

        if ($body['tipo'] === 'salida' && empty($body['cuadrilla_id'])) {
            Response::json(['error' => 'ERR-MOV-CREW', 'message' => 'Cuadrilla obligatoria para salidas'], 400);
            return;
        }

        $cantidad = (float) $body['cantidad'];
        if ($cantidad <= 0) {
            Response::json(['error' => 'ERR-MOV-QTY', 'message' => 'Cantidad debe ser mayor a 0'], 400);
            return;
        }

        $materialId = (int) $body['material_id'];

        // Check material
        $stmt = $pdo->prepare("SELECT * FROM materiales WHERE id = ? AND estado = 1");
        $stmt->execute([$materialId]);
        $material = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$material) {
            Response::json(['error' => 'ERR-MOV-MAT', 'message' => 'Material no encontrado'], 404);
            return;
        }

        // Check stock for exits
        if ($body['tipo'] === 'salida' && $material['stock_actual'] < $cantidad) {
            Response::json([
                'error' => 'ERR-MOV-STOCK',
                'message' => "Stock insuficiente. Actual: {$material['stock_actual']} {$material['unidad_medida']}",
            ], 400);
            return;
        }

        $pdo->beginTransaction();
        try {
            $userId = $auth['id'] ?? 1;

            $stmt = $pdo->prepare("
                INSERT INTO movimientos (material_id, cuadrilla_id, usuario_id, tipo, cantidad, observaciones)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $materialId,
                !empty($body['cuadrilla_id']) ? (int) $body['cuadrilla_id'] : null,
                $userId,
                $body['tipo'],
                $cantidad,
                $body['observaciones'] ?? null,
            ]);
            $movId = $pdo->lastInsertId();

            // Update stock
            $newStock = $body['tipo'] === 'entrada'
                ? $material['stock_actual'] + $cantidad
                : $material['stock_actual'] - $cantidad;

            $pdo->prepare("UPDATE materiales SET stock_actual = ? WHERE id = ?")->execute([$newStock, $materialId]);

            $pdo->commit();

            $alert = null;
            if ($newStock <= $material['stock_minimo']) {
                $alert = "⚠️ {$material['nombre']} bajo stock mínimo";
            }

            Response::json([
                'message' => 'Movimiento registrado',
                'id' => $movId,
                'new_stock' => $newStock,
                'alert' => $alert,
            ], 201);

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
