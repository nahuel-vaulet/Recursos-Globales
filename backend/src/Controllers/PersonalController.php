<?php
/**
 * [!] ARCH: PersonalController — Gestión de legajos y RRHH
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class PersonalController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['estado = 1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(nombre LIKE :search OR dni LIKE :search OR legajo LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT * FROM personal
            WHERE {$whereClause}
            ORDER BY nombre ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ? AND estado = 1");
        $stmt->execute([$id]);
        $person = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$person) {
            Response::json(['error' => 'ERR-PER-404', 'message' => 'Legajo no encontrado'], 404);
            return;
        }

        Response::json(['data' => $person]);
    }

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['nombre']) || empty($body['dni'])) {
            Response::json(['error' => 'ERR-PER-FIELD', 'message' => 'Nombre y DNI requeridos'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO personal (nombre, dni, legajo, puesto, fecha_ingreso, estado)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $body['nombre'],
            $body['dni'],
            $body['legajo'] ?? null,
            $body['puesto'] ?? null,
            $body['fecha_ingreso'] ?? date('Y-m-d'),
        ]);

        Response::json(['message' => 'Legajo creado', 'id' => $pdo->lastInsertId()], 201);
    }

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::json(['error' => 'ERR-PER-404', 'message' => 'No encontrado'], 404);
            return;
        }

        $pdo->prepare("
            UPDATE personal SET nombre=?, dni=?, legajo=?, puesto=?, fecha_ingreso=?
            WHERE id=?
        ")->execute([
                    $body['nombre'],
                    $body['dni'],
                    $body['legajo'],
                    $body['puesto'],
                    $body['fecha_ingreso'],
                    $id
                ]);

        Response::json(['message' => 'Legajo actualizado']);
    }

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        Database::getConnection()->prepare("UPDATE personal SET estado = 0 WHERE id = ?")->execute([$id]);
        Response::json(['message' => 'Legajo eliminado']);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
