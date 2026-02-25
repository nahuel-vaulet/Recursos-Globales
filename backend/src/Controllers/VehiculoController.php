<?php
/**
 * [!] ARCH: VehiculoController — CRUD vehículos
 * [✓] AUDIT: JWT auth, soft delete
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class VehiculoController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['v.estado = 1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(v.patente LIKE :search OR v.marca LIKE :search OR v.modelo LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        if (!empty($_GET['cuadrilla_id'])) {
            $where[] = "v.id_cuadrilla = :crew";
            $params['crew'] = (int) $_GET['cuadrilla_id'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT v.*, c.nombre_cuadrilla
            FROM vehiculos v
            LEFT JOIN cuadrillas c ON v.id_cuadrilla = c.id_cuadrilla
            WHERE {$whereClause}
            ORDER BY v.patente ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT v.*, c.nombre_cuadrilla
            FROM vehiculos v 
            LEFT JOIN cuadrillas c ON v.id_cuadrilla = c.id_cuadrilla
            WHERE v.id_vehiculo = ?
        ");
        $stmt->execute([$id]);
        $v = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$v) {
            Response::json(['error' => 'ERR-VEH-404', 'message' => 'Vehículo no encontrado'], 404);
            return;
        }

        Response::json(['data' => $v]);
    }

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['patente'])) {
            Response::json(['error' => 'ERR-VEH-FIELD', 'message' => 'patente requerida'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehiculos (patente, marca, modelo, anio, tipo, id_cuadrilla, km_actual, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $body['patente'],
            $body['marca'] ?? null,
            $body['modelo'] ?? null,
            $body['anio'] ?? null,
            $body['tipo'] ?? null,
            !empty($body['id_cuadrilla']) ? (int) $body['id_cuadrilla'] : null,
            (int) ($body['km_actual'] ?? 0),
        ]);

        Response::json(['message' => 'Vehículo creado', 'id' => $pdo->lastInsertId()], 201);
    }

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id_vehiculo = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$current) {
            Response::json(['error' => 'ERR-VEH-404', 'message' => 'Vehículo no encontrado'], 404);
            return;
        }

        $pdo->prepare("
            UPDATE vehiculos 
            SET patente = ?, marca = ?, modelo = ?, anio = ?, tipo = ?, id_cuadrilla = ?, km_actual = ?
            WHERE id_vehiculo = ?
        ")->execute([
                    $body['patente'] ?? $current['patente'],
                    $body['marca'] ?? $current['marca'],
                    $body['modelo'] ?? $current['modelo'],
                    $body['anio'] ?? $current['anio'],
                    $body['tipo'] ?? $current['tipo'],
                    isset($body['id_cuadrilla']) ? (int) $body['id_cuadrilla'] : $current['id_cuadrilla'],
                    $body['km_actual'] ?? $current['km_actual'],
                    $id,
                ]);

        Response::json(['message' => 'Vehículo actualizado']);
    }

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE vehiculos SET estado = 0 WHERE id_vehiculo = ?")->execute([$id]);
        Response::json(['message' => 'Vehículo eliminado']);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
