<?php
/**
 * [!] ARCH: SpotController — Gestión de Puntos de Interés
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class SpotController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("SELECT * FROM spots ORDER BY nombre ASC");
        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM spots WHERE id = ?");
        $stmt->execute([$id]);
        $spot = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$spot) {
            Response::json(['error' => 'ERR-SPOT-404', 'message' => 'Punto no encontrado'], 404);
            return;
        }

        Response::json(['data' => $spot]);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
