<?php
/**
 * [!] ARCH: CuadrillaController — CRUD cuadrillas + resumen ODTs
 * [✓] AUDIT: Migrado desde api/cuadrillas.php + CrewService
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\CrewService;

class CuadrillaController
{
    // ─── GET /api/cuadrillas ────────────────────────────

    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['c.estado_operativo = \'Activa\''];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(c.nombre_cuadrilla LIKE :search OR c.tipo_especialidad LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT c.*
            FROM cuadrillas c
            WHERE {$whereClause}
            ORDER BY c.nombre_cuadrilla ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/cuadrillas/{id} ───────────────────────

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM cuadrillas WHERE id_cuadrilla = ?");
        $stmt->execute([$id]);
        $cuadrilla = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$cuadrilla) {
            Response::json(['error' => 'ERR-CRW-404', 'message' => 'Cuadrilla no encontrada'], 404);
            return;
        }

        Response::json(['data' => $cuadrilla]);
    }

    // ─── POST /api/cuadrillas ───────────────────────────

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['nombre_cuadrilla'])) {
            Response::json(['error' => 'ERR-CRW-FIELD', 'message' => 'nombre_cuadrilla requerido'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO cuadrillas (nombre_cuadrilla, color_hex, tipo_especialidad, estado_operativo)
            VALUES (?, ?, ?, 'Activa')
        ");
        $stmt->execute([
            $body['nombre_cuadrilla'],
            $body['color_hex'] ?? '#607D8B',
            $body['tipo_especialidad'] ?? null,
        ]);

        Response::json(['message' => 'Cuadrilla creada', 'id' => $pdo->lastInsertId()], 201);
    }

    // ─── PUT /api/cuadrillas/{id} ───────────────────────

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM cuadrillas WHERE id_cuadrilla = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$current) {
            Response::json(['error' => 'ERR-CRW-404', 'message' => 'Cuadrilla no encontrada'], 404);
            return;
        }

        $pdo->prepare("
            UPDATE cuadrillas 
            SET nombre_cuadrilla = ?, color_hex = ?, tipo_especialidad = ?
            WHERE id_cuadrilla = ?
        ")->execute([
                    $body['nombre_cuadrilla'] ?? $current['nombre_cuadrilla'],
                    $body['color_hex'] ?? $current['color_hex'],
                    $body['tipo_especialidad'] ?? $current['tipo_especialidad'],
                    $id,
                ]);

        Response::json(['message' => 'Cuadrilla actualizada']);
    }

    // ─── DELETE /api/cuadrillas/{id} ────────────────────

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        // Soft delete
        $pdo->prepare("UPDATE cuadrillas SET estado_operativo = 'Inactiva' WHERE id_cuadrilla = ?")->execute([$id]);
        Response::json(['message' => 'Cuadrilla desactivada']);
    }

    // ─── GET /api/cuadrillas/resumen ────────────────────

    public static function resumen(): void
    {
        AuthMiddleware::authenticate();
        $service = new CrewService(Database::getConnection());
        Response::json($service->resumenCuadrillas());
    }

    // ─── GET /api/cuadrillas/{id}/odts ──────────────────

    public static function odts(int $id): void
    {
        AuthMiddleware::authenticate();
        $service = new CrewService(Database::getConnection());
        Response::json($service->obtenerODTsCuadrilla($id));
    }

    // ─── GET /api/cuadrillas/activas ────────────────────

    public static function activas(): void
    {
        AuthMiddleware::authenticate();
        $service = new CrewService(Database::getConnection());
        Response::json(['data' => $service->listarActivas()]);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
