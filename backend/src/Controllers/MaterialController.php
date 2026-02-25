<?php
/**
 * [!] ARCH: MaterialController — CRUD materiales + alertas de stock bajo
 * [✓] AUDIT: Migrado desde api/materiales.php con dual-mode SQL
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class MaterialController
{
    // ─── GET /api/materiales ────────────────────────────

    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['m.estado = 1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(m.nombre LIKE :search OR m.codigo LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        if (!empty($_GET['categoria'])) {
            $where[] = "m.categoria = :cat";
            $params['cat'] = $_GET['categoria'];
        }

        if (!empty($_GET['alerta'])) {
            $where[] = "m.stock_actual <= m.stock_minimo";
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT m.*
            FROM materiales m
            WHERE {$whereClause}
            ORDER BY m.nombre ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/materiales/{id} ───────────────────────

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM materiales WHERE id = ? AND estado = 1");
        $stmt->execute([$id]);
        $mat = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$mat) {
            Response::json(['error' => 'ERR-MAT-404', 'message' => 'Material no encontrado'], 404);
            return;
        }

        Response::json(['data' => $mat]);
    }

    // ─── POST /api/materiales ───────────────────────────

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['nombre'])) {
            Response::json(['error' => 'ERR-MAT-FIELD', 'message' => 'nombre requerido'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO materiales (nombre, codigo, unidad_medida, categoria, stock_actual, stock_minimo, estado)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $body['nombre'],
            $body['codigo'] ?? null,
            $body['unidad_medida'] ?? 'UND',
            $body['categoria'] ?? null,
            (float) ($body['stock_actual'] ?? 0),
            (float) ($body['stock_minimo'] ?? 0),
        ]);

        Response::json(['message' => 'Material creado', 'id' => $pdo->lastInsertId()], 201);
    }

    // ─── PUT /api/materiales/{id} ───────────────────────

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM materiales WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$current) {
            Response::json(['error' => 'ERR-MAT-404', 'message' => 'Material no encontrado'], 404);
            return;
        }

        $pdo->prepare("
            UPDATE materiales 
            SET nombre = ?, codigo = ?, unidad_medida = ?, categoria = ?, stock_minimo = ?
            WHERE id = ?
        ")->execute([
                    $body['nombre'] ?? $current['nombre'],
                    $body['codigo'] ?? $current['codigo'],
                    $body['unidad_medida'] ?? $current['unidad_medida'],
                    $body['categoria'] ?? $current['categoria'],
                    (float) ($body['stock_minimo'] ?? $current['stock_minimo']),
                    $id,
                ]);

        Response::json(['message' => 'Material actualizado']);
    }

    // ─── DELETE /api/materiales/{id} ────────────────────

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        // Soft delete
        $pdo->prepare("UPDATE materiales SET estado = 0 WHERE id = ?")->execute([$id]);
        Response::json(['message' => 'Material eliminado']);
    }

    // ─── GET /api/materiales/alertas ────────────────────

    public static function alertas(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $nullif = Database::isPostgres() ? 'NULLIF(stock_minimo, 0)' : 'NULLIF(stock_minimo, 0)';

        $stmt = $pdo->query("
            SELECT id, nombre, codigo, unidad_medida, stock_actual, stock_minimo,
                   ROUND((stock_actual / {$nullif}) * 100, 1) as porcentaje
            FROM materiales 
            WHERE stock_actual <= stock_minimo AND estado = 1
            ORDER BY (stock_actual / {$nullif}) ASC
            LIMIT 20
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
