<?php
/**
 * [!] ARCH: CalendarController — Vista calendario de ODTs
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\CalendarService;

class CalendarController
{
    // GET /api/calendar/month?anio=2026&mes=2
    public static function month(): void
    {
        AuthMiddleware::authenticate();
        $anio = (int) ($_GET['anio'] ?? date('Y'));
        $mes = (int) ($_GET['mes'] ?? date('n'));

        $service = new CalendarService(Database::getConnection());
        Response::json($service->resumenMensual($anio, $mes));
    }

    // GET /api/calendar/week?fecha=2026-02-25
    public static function week(): void
    {
        AuthMiddleware::authenticate();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $service = new CalendarService(Database::getConnection());
        Response::json($service->resumenSemanal($fecha));
    }

    // GET /api/calendar/day?fecha=2026-02-25
    public static function day(): void
    {
        AuthMiddleware::authenticate();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $service = new CalendarService(Database::getConnection());
        Response::json($service->detalleDiario($fecha));
    }
}
