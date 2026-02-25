<?php
/**
 * [!] ARCH: API Logs — Endpoint para recepción de errores del frontend/SW
 * POST /api/logs.php — Recibe Error Stack y almacena en log
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../services/ErrorService.php';

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);

            if (!$data || !isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Error stack inválido: falta campo id']);
                exit;
            }

            // Enriquecer con metadata del servidor
            $data['server_timestamp'] = date('Y-m-d H:i:s');
            $data['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $data['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200);

            // Almacenar en archivo de log
            $logFile = $logDir . '/errors_' . date('Y-m-d') . '.log';
            $line = json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
            file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

            echo json_encode([
                'success' => true,
                'message' => 'Error registrado',
                'id' => $data['id'],
            ]);
            break;

        case 'GET':
            // Leer errores del día actual (solo para roles administrativos)
            session_start();
            require_once __DIR__ . '/../includes/auth.php';

            if (!isset($_SESSION['usuario_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                exit;
            }

            $rol = $_SESSION['usuario_rol'] ?? $_SESSION['usuario_tipo'] ?? '';
            if (!in_array($rol, ['Gerente', 'Administrador'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos para ver logs']);
                exit;
            }

            $date = $_GET['date'] ?? date('Y-m-d');
            $logFile = $logDir . '/errors_' . $date . '.log';

            if (!file_exists($logFile)) {
                echo json_encode(['success' => true, 'errors' => [], 'date' => $date]);
                exit;
            }

            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $errors = array_map(fn($line) => json_decode($line, true), $lines);
            $errors = array_filter($errors); // Remove failed parses
            $errors = array_reverse($errors); // Newest first

            echo json_encode([
                'success' => true,
                'errors' => array_values($errors),
                'count' => count($errors),
                'date' => $date,
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }

} catch (\Exception $e) {
    error_log('[LOGS API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno al procesar log']);
}
