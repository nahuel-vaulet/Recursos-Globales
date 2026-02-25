<?php
/**
 * [!] ARCH: CORS Middleware
 * [✓] AUDIT: Reads allowed origins from CORS_ORIGINS env var
 * [→] EDITAR: Restrict origins for production
 */

declare(strict_types=1);

namespace App\Middleware;

class CorsMiddleware
{
    /**
     * Apply CORS headers to the response.
     * Must be called before any output.
     */
    public static function handle(): void
    {
        $allowedRaw = $_ENV['CORS_ORIGINS'] ?? '*';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($allowedRaw === '*') {
            header('Access-Control-Allow-Origin: *');
        } else {
            $allowed = array_map('trim', explode(',', $allowedRaw));
            if (in_array($origin, $allowed, true)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Vary: Origin');
            } else {
                // Reject unknown origin silently
                http_response_code(403);
                echo json_encode([
                    'error' => 'ERR-CX-CORS',
                    'message' => 'Origin not allowed',
                ]);
                exit;
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Preflight: respond immediately
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
