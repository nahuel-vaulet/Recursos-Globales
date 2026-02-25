<?php
/**
 * [!] ARCH: Auth Controller — Login / Token Refresh
 * [✓] AUDIT: Replaces session-based login with JWT token issuance
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

class AuthController
{
    /**
     * POST /api/auth/login
     * Validates credentials, returns JWT token.
     */
    public static function login(array $params): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            Response::error('ERR-AUTH-400', 'Email y contraseña son obligatorios', 400);
            return;
        }

        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare("
                SELECT u.id_usuario, u.nombre, u.email, u.password_hash, u.tipo_usuario, u.estado, u.id_cuadrilla
                FROM usuarios u
                WHERE u.email = :email AND u.estado = 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                Response::error('ERR-AUTH-401', 'Credenciales inválidas', 401);
                return;
            }

            // Generate JWT
            $token = AuthMiddleware::generateToken($user);

            Response::json([
                'token' => $token,
                'user' => [
                    'id' => $user['id_usuario'],
                    'nombre' => $user['nombre'],
                    'email' => $user['email'],
                    'tipo' => $user['tipo_usuario'],
                    'cuadrilla' => $user['id_cuadrilla'],
                ],
            ]);

        } catch (\PDOException $e) {
            error_log('[ERR-AUTH-DB] ' . $e->getMessage());
            Response::error('ERR-DB-500', 'Error interno en autenticación', 500);
        }
    }

    /**
     * GET /api/auth/me
     * Returns current user info from JWT (requires auth).
     */
    public static function me(array $params): void
    {
        $user = AuthMiddleware::authenticate();
        Response::json(['user' => $user]);
    }

    /**
     * POST /api/auth/refresh
     * Issues a new JWT with extended expiration (requires valid token).
     */
    public static function refresh(array $params): void
    {
        $user = AuthMiddleware::authenticate();

        // Re-issue token with fresh fields
        $token = AuthMiddleware::generateToken([
            'id_usuario' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'tipo_usuario' => $user['tipo'],
            'id_cuadrilla' => $user['cuadrilla'],
        ]);

        Response::json(['token' => $token]);
    }
}
