<?php
/**
 * [!] ARCH: JSON Response Helper
 * [✓] AUDIT: Consistent JSON output across all controllers
 */

declare(strict_types=1);

namespace App\Core;

class Response
{
    /**
     * Send a JSON success response.
     */
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send a JSON error response with structured error codes.
     */
    public static function error(string $code, string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => $code,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send a paginated JSON response.
     */
    public static function paginated(array $items, int $total, int $page, int $perPage): void
    {
        self::json([
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => (int) ceil($total / max($perPage, 1)),
            ],
        ]);
    }
}
