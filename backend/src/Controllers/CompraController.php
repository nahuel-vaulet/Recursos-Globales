<?php
/**
 * [!] ARCH: CompraController — Gestión de compras y órdenes
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class CompraController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT oc.*, p.nombre as proveedor_nombre
            FROM ordenes_compras oc
            LEFT JOIN proveedores p ON oc.id_proveedor = p.id
            ORDER BY oc.fecha DESC
        ");

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM ordenes_compras WHERE id = ?");
        $stmt->execute([$id]);
        $compra = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$compra) {
            Response::json(['error' => 'ERR-PUR-404', 'message' => 'Orden no encontrada'], 404);
            return;
        }

        Response::json(['data' => $compra]);
    }

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['id_proveedor']) || empty($body['monto_total'])) {
            Response::json(['error' => 'ERR-PUR-FIELD', 'message' => 'Proveedor y monto requeridos'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO ordenes_compras (id_proveedor, monto_total, observaciones, estado, fecha)
            VALUES (?, ?, ?, 'pendiente', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $body['id_proveedor'],
            $body['monto_total'],
            $body['observaciones'] ?? null
        ]);

        Response::json(['message' => 'Orden creada', 'id' => $pdo->lastInsertId()], 201);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
