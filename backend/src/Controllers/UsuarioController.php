<?php
/**
 * [!] ARCH: UsuarioController — CRUD de usuarios + gestión de roles
 * [✓] AUDIT: Migrado desde api/usuarios.php, JWT auth en todos los endpoints
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class UsuarioController
{
    // ─── GET /api/usuarios ──────────────────────────────

    public static function index(): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $where = ['u.estado = 1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(u.nombre LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        if (!empty($_GET['tipo_usuario'])) {
            $where[] = "u.tipo_usuario = :tipo";
            $params['tipo'] = $_GET['tipo_usuario'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.nombre, u.email, u.tipo_usuario, u.estado, u.created_at
            FROM usuarios u
            WHERE {$whereClause}
            ORDER BY u.nombre ASC
        ");
        $stmt->execute($params);

        Response::json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    // ─── GET /api/usuarios/{id} ─────────────────────────

    public static function show(int $id): void
    {
        AuthMiddleware::authenticate();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, tipo_usuario, estado, created_at FROM usuarios WHERE id_usuario = ? AND estado = 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            Response::json(['error' => 'ERR-USR-404', 'message' => 'Usuario no encontrado'], 404);
            return;
        }

        Response::json(['data' => $user]);
    }

    // ─── POST /api/usuarios ─────────────────────────────

    public static function store(): void
    {
        $auth = AuthMiddleware::authenticate();
        AuthMiddleware::requireRole($auth, 'Gerente');

        $pdo = Database::getConnection();
        $body = self::getBody();

        // Validate
        $required = ['nombre', 'email', 'password', 'tipo_usuario'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                Response::json(['error' => 'ERR-USR-FIELD', 'message' => "Campo '{$field}' requerido"], 400);
                return;
            }
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'ERR-USR-EMAIL', 'message' => 'Email no válido'], 400);
            return;
        }

        if (strlen($body['password']) < 6) {
            Response::json(['error' => 'ERR-USR-PASS', 'message' => 'Contraseña mínimo 6 caracteres'], 400);
            return;
        }

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$body['email']]);
        if ($stmt->fetch()) {
            Response::json(['error' => 'ERR-USR-DUP', 'message' => 'Email ya registrado'], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, password_hash, tipo_usuario)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $body['nombre'],
            $body['email'],
            password_hash($body['password'], PASSWORD_DEFAULT),
            $body['tipo_usuario'],
        ]);

        Response::json(['message' => 'Usuario creado', 'id' => $pdo->lastInsertId()], 201);
    }

    // ─── PUT /api/usuarios/{id} ─────────────────────────

    public static function update(int $id): void
    {
        $auth = AuthMiddleware::authenticate();
        AuthMiddleware::requireRole($auth, 'Gerente');

        $pdo = Database::getConnection();
        $body = self::getBody();

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$current) {
            Response::json(['error' => 'ERR-USR-404', 'message' => 'Usuario no encontrado'], 404);
            return;
        }

        // Check duplicate email
        if (!empty($body['email']) && $body['email'] !== $current['email']) {
            $check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
            $check->execute([$body['email'], $id]);
            if ($check->fetch()) {
                Response::json(['error' => 'ERR-USR-DUP', 'message' => 'Email ya registrado'], 400);
                return;
            }
        }

        $updates = ['nombre = ?', 'email = ?', 'tipo_usuario = ?'];
        $params = [
            $body['nombre'] ?? $current['nombre'],
            $body['email'] ?? $current['email'],
            $body['tipo_usuario'] ?? $current['tipo_usuario'],
        ];

        if (!empty($body['password'])) {
            if (strlen($body['password']) < 6) {
                Response::json(['error' => 'ERR-USR-PASS', 'message' => 'Contraseña mínimo 6 caracteres'], 400);
                return;
            }
            $updates[] = 'password_hash = ?';
            $params[] = password_hash($body['password'], PASSWORD_DEFAULT);
        }

        $params[] = $id;
        $updateClause = implode(', ', $updates);
        $pdo->prepare("UPDATE usuarios SET {$updateClause} WHERE id_usuario = ?")->execute($params);

        Response::json(['message' => 'Usuario actualizado']);
    }

    // ─── DELETE /api/usuarios/{id} ──────────────────────

    public static function destroy(int $id): void
    {
        $auth = AuthMiddleware::authenticate();
        AuthMiddleware::requireRole($auth, 'Gerente');

        $pdo = Database::getConnection();

        // Don't allow deleting last Gerente
        $stmt = $pdo->prepare("SELECT tipo_usuario FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && $user['tipo_usuario'] === 'Gerente') {
            $count = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'Gerente' AND estado = 1")->fetchColumn();
            if ((int) $count <= 1) {
                Response::json(['error' => 'ERR-USR-LAST', 'message' => 'No se puede eliminar el último Gerente'], 400);
                return;
            }
        }

        // Soft delete
        $pdo->prepare("UPDATE usuarios SET estado = 0 WHERE id_usuario = ?")->execute([$id]);
        Response::json(['message' => 'Usuario eliminado']);
    }

    private static function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}
