<?php
/**
 * [!] ARCH: GastoController — Gestión de gastos generales
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class GastoController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("SELECT * FROM gastos ORDER BY fecha DESC");
        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['monto']) || empty($body['categoria'])) {
            Response::json(['error' => 'ERR-EXP-FIELD', 'message' => 'Monto y categoría requeridos'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO gastos (categoria, monto, descripcion, fecha)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $body['categoria'],
            $body['monto'],
            $body['descripcion'] ?? null
        ]);

        Response::json(['message' => 'Gasto registrado', 'id' => $pdo->lastInsertId()], 201);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
