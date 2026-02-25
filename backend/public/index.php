<?php
/**
 * [!] ARCH: Application Entry Point (Front Controller)
 * [✓] AUDIT: All HTTP requests funneled through this file
 * [→] EDITAR: Configure web server to rewrite URLs to this file
 *
 * Apache: RewriteRule ^(.*)$ public/index.php [QSA,L]
 * Nginx:  try_files $uri /public/index.php$is_args$args;
 */

declare(strict_types=1);

// ─── Bootstrap ──────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ─── Global error handling ──────────────────────────────
set_exception_handler(function (\Throwable $e) {
    $isDev = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

    error_log('[ERR-UNHANDLED] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'ERR-SRV-500',
        'message' => $isDev ? $e->getMessage() : 'Internal server error',
        'trace' => $isDev ? $e->getTraceAsString() : null,
    ]);
});

// ─── CORS (must run before any output) ──────────────────
\App\Middleware\CorsMiddleware::handle();

// ─── Content-Type ───────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

// ─── Router ─────────────────────────────────────────────
$router = new \App\Core\Router();

// Register routes
$registerRoutes = require __DIR__ . '/../routes/api.php';
$registerRoutes($router);

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip base path depending on environment
// Docker/Render: /api/odt → /api/odt (vhost already routes to this file)
// Local XAMPP:   /APP-Prueba/backend/public/api/odt → /api/odt
$basePaths = ['/APP-Prueba/backend/public'];
foreach ($basePaths as $base) {
    if (str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
        break;
    }
}
if (empty($uri)) {
    $uri = '/';
}

$router->dispatch($method, $uri);
