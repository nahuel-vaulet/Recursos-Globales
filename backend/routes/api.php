<?php
/**
 * [!] ARCH: API Routes Registration — ALL modules
 * [✓] AUDIT: All REST endpoints registered here
 */

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\OdtController;
use App\Controllers\UsuarioController;
use App\Controllers\CuadrillaController;
use App\Controllers\MaterialController;
use App\Controllers\MovimientoController;
use App\Controllers\DashboardController;
use App\Controllers\VehiculoController;
use App\Controllers\CombustibleController;
use App\Controllers\HerramientaController;
use App\Controllers\ProveedorController;
use App\Controllers\ParteController;
use App\Controllers\TareaController;
use App\Controllers\CalendarController;
use App\Controllers\PersonalController;
use App\Controllers\CompraController;
use App\Controllers\GastoController;
use App\Controllers\ReporteController;
use App\Controllers\SpotController;

return function (Router $router): void {

    // ─── Auth (public routes) ──────────────────────────
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->get('/api/auth/me', [AuthController::class, 'me']);
    $router->post('/api/auth/refresh', [AuthController::class, 'refresh']);

    // ─── Health Check ──────────────────────────────────
    $router->get('/api/health', function () {
        \App\Core\Response::json([
            'status' => 'ok',
            'timestamp' => date('c'),
            'version' => '2.0.0',
        ]);
    });

    // ─── ODT ───────────────────────────────────────────
    $router->get('/api/odt', [OdtController::class, 'index']);
    $router->get('/api/odt/states', [OdtController::class, 'states']);
    $router->get('/api/odt/{id}', [OdtController::class, 'show']);
    $router->post('/api/odt/{id}/status', [OdtController::class, 'changeStatus']);
    $router->post('/api/odt/{id}/assign', [OdtController::class, 'assign']);
    $router->post('/api/odt/{id}/urgent', [OdtController::class, 'toggleUrgent']);
    $router->post('/api/odt/bulk', [OdtController::class, 'bulk']);

    // ─── Usuarios ──────────────────────────────────────
    $router->get('/api/usuarios', [UsuarioController::class, 'index']);
    $router->get('/api/usuarios/{id}', [UsuarioController::class, 'show']);
    $router->post('/api/usuarios', [UsuarioController::class, 'store']);
    $router->put('/api/usuarios/{id}', [UsuarioController::class, 'update']);
    $router->delete('/api/usuarios/{id}', [UsuarioController::class, 'destroy']);

    // ─── Cuadrillas ────────────────────────────────────
    $router->get('/api/cuadrillas', [CuadrillaController::class, 'index']);
    $router->get('/api/cuadrillas/activas', [CuadrillaController::class, 'activas']);
    $router->get('/api/cuadrillas/resumen', [CuadrillaController::class, 'resumen']);
    $router->get('/api/cuadrillas/{id}', [CuadrillaController::class, 'show']);
    $router->get('/api/cuadrillas/{id}/odts', [CuadrillaController::class, 'odts']);
    $router->post('/api/cuadrillas', [CuadrillaController::class, 'store']);
    $router->put('/api/cuadrillas/{id}', [CuadrillaController::class, 'update']);
    $router->delete('/api/cuadrillas/{id}', [CuadrillaController::class, 'destroy']);

    // ─── Materiales ────────────────────────────────────
    $router->get('/api/materiales', [MaterialController::class, 'index']);
    $router->get('/api/materiales/alertas', [MaterialController::class, 'alertas']);
    $router->get('/api/materiales/{id}', [MaterialController::class, 'show']);
    $router->post('/api/materiales', [MaterialController::class, 'store']);
    $router->put('/api/materiales/{id}', [MaterialController::class, 'update']);
    $router->delete('/api/materiales/{id}', [MaterialController::class, 'destroy']);

    // ─── Movimientos ───────────────────────────────────
    $router->get('/api/movimientos', [MovimientoController::class, 'index']);
    $router->get('/api/movimientos/{id}', [MovimientoController::class, 'show']);
    $router->post('/api/movimientos', [MovimientoController::class, 'store']);

    // ─── Dashboard ─────────────────────────────────────
    $router->get('/api/dashboard/stats', [DashboardController::class, 'stats']);
    $router->get('/api/dashboard/alerts', [DashboardController::class, 'alerts']);
    $router->get('/api/dashboard/recent', [DashboardController::class, 'recent']);
    $router->get('/api/dashboard/consumption', [DashboardController::class, 'consumption']);
    $router->get('/api/dashboard/trends', [DashboardController::class, 'trends']);

    // ─── Vehículos ─────────────────────────────────────
    $router->get('/api/vehiculos', [VehiculoController::class, 'index']);
    $router->get('/api/vehiculos/{id}', [VehiculoController::class, 'show']);
    $router->post('/api/vehiculos', [VehiculoController::class, 'store']);
    $router->put('/api/vehiculos/{id}', [VehiculoController::class, 'update']);
    $router->delete('/api/vehiculos/{id}', [VehiculoController::class, 'destroy']);

    // ─── Combustibles ──────────────────────────────────
    $router->get('/api/combustibles/stock', [CombustibleController::class, 'stock']);
    $router->get('/api/combustibles/historial', [CombustibleController::class, 'historial']);
    $router->post('/api/combustibles/carga', [CombustibleController::class, 'carga']);
    $router->post('/api/combustibles/despacho', [CombustibleController::class, 'despacho']);

    // ─── Herramientas ──────────────────────────────────
    $router->get('/api/herramientas', [HerramientaController::class, 'index']);
    $router->get('/api/herramientas/{id}', [HerramientaController::class, 'show']);
    $router->post('/api/herramientas', [HerramientaController::class, 'store']);
    $router->put('/api/herramientas/{id}', [HerramientaController::class, 'update']);
    $router->delete('/api/herramientas/{id}', [HerramientaController::class, 'destroy']);

    // ─── Proveedores ───────────────────────────────────
    $router->get('/api/proveedores', [ProveedorController::class, 'index']);
    $router->get('/api/proveedores/{id}', [ProveedorController::class, 'show']);
    $router->post('/api/proveedores', [ProveedorController::class, 'store']);
    $router->put('/api/proveedores/{id}', [ProveedorController::class, 'update']);
    $router->delete('/api/proveedores/{id}', [ProveedorController::class, 'destroy']);

    // ─── Partes Diarios ────────────────────────────────
    $router->get('/api/partes', [ParteController::class, 'index']);
    $router->get('/api/partes/{id}', [ParteController::class, 'show']);
    $router->post('/api/partes', [ParteController::class, 'store']);

    // ─── Tareas ────────────────────────────────────────
    $router->get('/api/tareas', [TareaController::class, 'index']);
    $router->get('/api/tareas/{id}', [TareaController::class, 'show']);
    $router->post('/api/tareas', [TareaController::class, 'store']);
    $router->put('/api/tareas/{id}', [TareaController::class, 'update']);
    $router->delete('/api/tareas/{id}', [TareaController::class, 'destroy']);

    // ─── Calendario ────────────────────────────────────
    $router->get('/api/calendar/month', [CalendarController::class, 'month']);
    $router->get('/api/calendar/week', [CalendarController::class, 'week']);
    $router->get('/api/calendar/day', [CalendarController::class, 'day']);

    // ─── Personal ──────────────────────────────────────
    $router->get('/api/personal', [PersonalController::class, 'index']);
    $router->get('/api/personal/{id}', [PersonalController::class, 'show']);
    $router->post('/api/personal', [PersonalController::class, 'store']);
    $router->put('/api/personal/{id}', [PersonalController::class, 'update']);
    $router->delete('/api/personal/{id}', [PersonalController::class, 'destroy']);

    // ─── Compras ───────────────────────────────────────
    $router->get('/api/compras', [CompraController::class, 'index']);
    $router->get('/api/compras/{id}', [CompraController::class, 'show']);
    $router->post('/api/compras', [CompraController::class, 'store']);

    // ─── Gastos ────────────────────────────────────────
    $router->get('/api/gastos', [GastoController::class, 'index']);
    $router->post('/api/gastos', [GastoController::class, 'store']);

    // ─── Reportes ──────────────────────────────────────
    $router->get('/api/reportes/odt-efficiency', [ReporteController::class, 'odtEfficiency']);
    $router->get('/api/reportes/consumption', [ReporteController::class, 'consumption']);

    // ─── Spot ──────────────────────────────────────────
    $router->get('/api/spot', [SpotController::class, 'index']);
    $router->get('/api/spot/{id}', [SpotController::class, 'show']);
};
