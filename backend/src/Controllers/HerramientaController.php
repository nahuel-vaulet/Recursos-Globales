<?php
/**
 * [!] ARCH: HerramientaController — CRUD herramientas
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class HerramientaController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['h.estado = 1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(h.nombre LIKE :search OR h.codigo LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        if (!empty($_GET['cuadrilla_id'])) {
            $where[] = "h.id_cuadrilla = :crew";
            $params['crew'] = (int) $_GET['cuadrilla_id'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT h.*, c.nombre_cuadrilla
            FROM herramientas h
            LEFT JOIN cuadrillas c ON h.id_cuadrilla = c.id_cuadrilla
            WHERE {$whereClause}
            ORDER BY h.nombre ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT h.*, c.nombre_cuadrilla FROM herramientas h LEFT JOIN cuadrillas c ON h.id_cuadrilla = c.id_cuadrilla WHERE h.id_herramienta = ?");
        $stmt->execute([$id]);
        $h = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$h) {
            Response::json(['error' => 'ERR-TOOL-404', 'message' => 'Herramienta no encontrada'], 404);
            return;
        }

        Response::json(['data' => $h]);
    }

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['nombre'])) {
            Response::json(['error' => 'ERR-TOOL-FIELD', 'message' => 'nombre requerido'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO herramientas (nombre, codigo, marca, id_cuadrilla, proveedor, estado)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $body['nombre'],
            $body['codigo'] ?? null,
            $body['marca'] ?? null,
            !empty($body['id_cuadrilla']) ? (int) $body['id_cuadrilla'] : null,
            $body['proveedor'] ?? null,
        ]);

        Response::json(['message' => 'Herramienta creada', 'id' => $pdo->lastInsertId()], 201);
    }

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM herramientas WHERE id_herramienta = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$current) {
            Response::json(['error' => 'ERR-TOOL-404', 'message' => 'Herramienta no encontrada'], 404);
            return;
        }

        $pdo->prepare("
            UPDATE herramientas SET nombre = ?, codigo = ?, marca = ?, id_cuadrilla = ?, proveedor = ? WHERE id_herramienta = ?
        ")->execute([
                    $body['nombre'] ?? $current['nombre'],
                    $body['codigo'] ?? $current['codigo'],
                    $body['marca'] ?? $current['marca'],
                    isset($body['id_cuadrilla']) ? (int) $body['id_cuadrilla'] : $current['id_cuadrilla'],
                    $body['proveedor'] ?? $current['proveedor'],
                    $id,
                ]);

        Response::json(['message' => 'Herramienta actualizada']);
    }

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE herramientas SET estado = 0 WHERE id_herramienta = ?")->execute([$id]);
        Response::json(['message' => 'Herramienta eliminada']);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
