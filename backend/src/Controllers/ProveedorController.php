<?php
/**
 * [!] ARCH: ProveedorController — CRUD proveedores
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class ProveedorController
{
    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['p.estado = 1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(p.nombre LIKE :search OR p.cuit LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $pdo->prepare("SELECT p.* FROM proveedores p WHERE {$whereClause} ORDER BY p.nombre ASC");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$p) {
            Response::json(['error' => 'ERR-PROV-404', 'message' => 'Proveedor no encontrado'], 404);
            return;
        }
        Response::json(['data' => $p]);
    }

    public static function store(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        if (empty($body['nombre'])) {
            Response::json(['error' => 'ERR-PROV-FIELD', 'message' => 'nombre requerido'], 400);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, cuit, telefono, email, direccion, estado) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $body['nombre'],
            $body['cuit'] ?? null,
            $body['telefono'] ?? null,
            $body['email'] ?? null,
            $body['direccion'] ?? null,
        ]);
        Response::json(['message' => 'Proveedor creado', 'id' => $pdo->lastInsertId()], 201);
    }

    public static function update(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$current) {
            Response::json(['error' => 'ERR-PROV-404', 'message' => 'No encontrado'], 404);
            return;
        }

        $pdo->prepare("UPDATE proveedores SET nombre=?, cuit=?, telefono=?, email=?, direccion=? WHERE id_proveedor=?")->execute([
            $body['nombre'] ?? $current['nombre'],
            $body['cuit'] ?? $current['cuit'],
            $body['telefono'] ?? $current['telefono'],
            $body['email'] ?? $current['email'],
            $body['direccion'] ?? $current['direccion'],
            $id,
        ]);
        Response::json(['message' => 'Proveedor actualizado']);
    }

    public static function destroy(int $id): void
    {
        AuthMiddleware::authenticate();
        Database::getConnection()->prepare("UPDATE proveedores SET estado = 0 WHERE id_proveedor = ?")->execute([$id]);
        Response::json(['message' => 'Proveedor eliminado']);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
