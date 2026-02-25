<?php
/**
 * [!] ARCH: API Calendar — Endpoints REST para vistas de calendario ODT
 * GET /api/calendar.php?view=month&year=2026&month=2
 * GET /api/calendar.php?view=week&date=2026-02-20
 * GET /api/calendar.php?view=day&date=2026-02-20
 */

// [!] ARCH: Buffer para capturar output de includes
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/CalendarService.php';
require_once __DIR__ . '/../services/ErrorService.php';

// Limpiar cualquier output generado por los includes
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar sesión (auth.php ya inicia la sesión)
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $calendarService = new CalendarService($pdo);
    $view = $_GET['view'] ?? 'month';
    $mode = $_GET['mode'] ?? 'assigned'; // assigned | duedate
    $isDueDate = ($mode === 'duedate');

    switch ($view) {
        case 'month':
            $year = (int) ($_GET['year'] ?? date('Y'));
            $month = (int) ($_GET['month'] ?? date('n'));

            if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Parámetros de fecha inválidos']);
                exit;
            }

            $data = $isDueDate
                ? $calendarService->resumenMensualVencimientos($year, $month)
                : $calendarService->resumenMensual($year, $month);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'week':
            $date = $_GET['date'] ?? date('Y-m-d');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido (YYYY-MM-DD)']);
                exit;
            }

            $data = $isDueDate
                ? $calendarService->resumenSemanalVencimientos($date)
                : $calendarService->resumenSemanal($date);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'day':
            $date = $_GET['date'] ?? date('Y-m-d');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido (YYYY-MM-DD)']);
                exit;
            }

            $data = $isDueDate
                ? $calendarService->detalleDiarioVencimientos($date)
                : $calendarService->detalleDiario($date);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Vista no válida. Opciones: month, week, day']);
    }

} catch (\Exception $e) {
    ob_clean();
    $error = ErrorService::capture($e, 'CalendarController', 'api_calendar', $_GET);
    ErrorService::respondWithError($error);
}