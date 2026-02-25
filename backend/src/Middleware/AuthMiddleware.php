<?php
/**
 * [!] ARCH: JWT Authentication Middleware
 * [✓] AUDIT: Replaces PHP sessions for stateless cross-domain auth
 * [→] EDITAR: Configure JWT_SECRET in .env before production
 */

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class AuthMiddleware
{
    /**
     * Validate JWT from Authorization header.
     * Returns decoded user payload or sends 401/403 and exits.
     *
     * @return array{id: int, email: string, tipo: string, cuadrilla: ?int}
     */
    public static function authenticate(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($header)) {
            self::reject('ERR-CX-401', 'Authorization header missing', 401);
        }

        // Support "Bearer <token>" format
        if (!str_starts_with($header, 'Bearer ')) {
            self::reject('ERR-CX-401', 'Invalid authorization format', 401);
        }

        $token = substr($header, 7);
        $secret = $_ENV['JWT_SECRET'] ?? '';

        if (empty($secret)) {
            error_log('[ERR-AUTH-CONFIG] JWT_SECRET not configured');
            self::reject('ERR-CX-500', 'Auth configuration error', 500);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $payload = (array) $decoded;

            return [
                'id' => (int) ($payload['sub'] ?? 0),
                'email' => $payload['email'] ?? '',
                'tipo' => $payload['tipo'] ?? '',
                'cuadrilla' => isset($payload['cuadrilla']) ? (int) $payload['cuadrilla'] : null,
                'nombre' => $payload['nombre'] ?? '',
            ];

        } catch (ExpiredException $e) {
            self::reject('ERR-CX-401', 'Token expired', 401);
        } catch (\Exception $e) {
            error_log('[ERR-AUTH-DECODE] ' . $e->getMessage());
            self::reject('ERR-CX-401', 'Invalid token', 401);
        }

        // Unreachable but satisfies static analysis
        return [];
    }

    /**
     * Generate a JWT token for a user.
     *
     * @param array{id_usuario: int, email: string, tipo_usuario: string, id_cuadrilla: ?int, nombre: string} $user
     * @return string Signed JWT
     */
    public static function generateToken(array $user): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        $expiration = (int) ($_ENV['JWT_EXPIRATION'] ?? 3600);

        $payload = [
            'iss' => 'erp-rg-backend',
            'sub' => $user['id_usuario'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'tipo' => $user['tipo_usuario'],
            'cuadrilla' => $user['id_cuadrilla'] ?? null,
            'iat' => time(),
            'exp' => time() + $expiration,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Optional: check if user has a required permission/role.
     *
     * @param array  $user     Decoded user payload
     * @param string $required Required role or permission key
     */
    public static function requireRole(array $user, string $required): void
    {
        $adminRoles = ['Administrador', 'Gerente', 'Supervisor'];

        if (in_array($user['tipo'], $adminRoles, true)) {
            return; // Admin always passes
        }

        if ($user['tipo'] !== $required) {
            self::reject('ERR-CX-403', "Insufficient permissions: requires {$required}", 403);
        }
    }

    /**
     * Send an error response and terminate.
     */
    private static function reject(string $code, string $message, int $httpStatus): never
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => $code,
            'message' => $message,
        ]);
        exit;
    }
}
