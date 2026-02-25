<?php
/**
 * [!] ARCH: OdtController — REST API for ODT management
 * [✓] AUDIT: All endpoints use JWT auth via AuthMiddleware
 * [→] EDITAR: Add bulk actions and export endpoints
 *
 * Endpoints:
 *   GET    /api/odt           — List with filters + metrics
 *   GET    /api/odt/{id}      — Get single ODT with history
 *   POST   /api/odt/{id}/status  — Change state
 *   POST   /api/odt/{id}/assign  — Assign to squad
 *   POST   /api/odt/{id}/urgent  — Toggle urgent
 *   POST   /api/odt/bulk      — Bulk status/assign
 *   GET    /api/odt/states    — Get state machine config
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\ODTService;
use App\Services\StateMachine;

class OdtController
{
    private static function getService(): ODTService
    {
        return new ODTService(Database::getConnection());
    }

    private static function requireAuth(): array
    {
        $user = AuthMiddleware::authenticate();
        // Map 'id' from JWT to 'id_usuario' for service compatibility
        $user['id_usuario'] = $user['id'] ?? 0;
        $user['id_cuadrilla'] = $user['cuadrilla'] ?? null;
        return $user;
    }

    // ─── GET /api/odt ───────────────────────────────────

    /**
     * List ODTs with filters. Returns data + metrics.
     *
     * Query params: estado, prioridad, cuadrilla, fecha_desde, fecha_hasta,
     *               search, urgente, vencimiento, limit, offset
     */
    public static function index(): void
    {
        $user = self::requireAuth();
        $service = self::getService();

        $filtros = [
            'estado' => $_GET['estado'] ?? null,
            'prioridad' => $_GET['prioridad'] ?? null,
            'cuadrilla' => $_GET['cuadrilla'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            'search' => $_GET['search'] ?? null,
            'urgente' => $_GET['urgente'] ?? null,
            'vencimiento' => $_GET['vencimiento'] ?? null,
            'limit' => $_GET['limit'] ?? 100,
            'offset' => $_GET['offset'] ?? 0,
        ];

        // Clean empty values
        $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

        try {
            $rol = $user['tipo'] ?? null;
            $idCuadrilla = $user['id_cuadrilla'] ?? null;

            $odts = $service->listarConFiltros($filtros, $rol, $idCuadrilla ? (int) $idCuadrilla : null);
            $metricas = $service->getMetricas($odts);

            Response::json([
                'data' => $odts,
                'metrics' => $metricas,
                'count' => count($odts),
                'filters' => $filtros,
            ]);

        } catch (\RuntimeException $e) {
            Response::json(['error' => 'ERR-ODT-LIST', 'message' => $e->getMessage()], 500);
        }
    }

    // ─── GET /api/odt/{id} ──────────────────────────────

    public static function show(int $id): void
    {
        self::requireAuth();
        $service = self::getService();

        $odt = $service->obtenerPorId($id);
        if (!$odt) {
            Response::json(['error' => 'ERR-ODT-404', 'message' => "ODT #{$id} no encontrada"], 404);
            return;
        }

        // Include history
        $historial = $service->getHistorial($id);

        // Include allowed transitions
        $esUrgente = \App\Services\PriorityUtil::esUrgente($odt);
        $transiciones = StateMachine::getTransicionesPermitidas($odt['estado_gestion'], $esUrgente);

        Response::json([
            'data' => $odt,
            'historial' => $historial,
            'transitions' => $transiciones,
        ]);
    }

    // ─── POST /api/odt/{id}/status ──────────────────────

    public static function changeStatus(int $id): void
    {
        $user = self::requireAuth();
        $body = self::getBody();

        $nuevoEstado = $body['estado'] ?? null;
        $observacion = $body['observacion'] ?? '';

        if (!$nuevoEstado) {
            Response::json(['error' => 'ERR-ODT-FIELD', 'message' => 'Campo "estado" requerido'], 400);
            return;
        }

        try {
            $service = self::getService();
            $service->cambiarEstado($id, $nuevoEstado, (int) $user['id_usuario'], $observacion);

            $odt = $service->obtenerPorId($id);
            Response::json([
                'message' => "Estado cambiado a '{$nuevoEstado}'",
                'data' => $odt,
            ]);

        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => 'ERR-ODT-TRANSITION', 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            Response::json(['error' => 'ERR-ODT-STATUS', 'message' => $e->getMessage()], 500);
        }
    }

    // ─── POST /api/odt/{id}/assign ──────────────────────

    public static function assign(int $id): void
    {
        $user = self::requireAuth();
        $body = self::getBody();

        $idCuadrilla = (int) ($body['id_cuadrilla'] ?? 0);
        $fechaAsignacion = $body['fecha_asignacion'] ?? '';
        $orden = (int) ($body['orden'] ?? 1);

        if (!$idCuadrilla || !$fechaAsignacion) {
            Response::json([
                'error' => 'ERR-ODT-FIELD',
                'message' => 'Campos "id_cuadrilla" y "fecha_asignacion" requeridos',
            ], 400);
            return;
        }

        try {
            $service = self::getService();
            $service->asignar($id, $idCuadrilla, $fechaAsignacion, $orden, (int) $user['id_usuario']);

            $odt = $service->obtenerPorId($id);
            Response::json([
                'message' => 'ODT asignada correctamente',
                'data' => $odt,
            ]);

        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => 'ERR-ODT-ASSIGN', 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            Response::json(['error' => 'ERR-ODT-ASSIGN', 'message' => $e->getMessage()], 500);
        }
    }

    // ─── POST /api/odt/{id}/urgent ──────────────────────

    public static function toggleUrgent(int $id): void
    {
        $user = self::requireAuth();

        try {
            $service = self::getService();
            $service->toggleUrgente($id, (int) $user['id_usuario']);

            $odt = $service->obtenerPorId($id);
            Response::json([
                'message' => $odt['urgente_flag'] ? 'Marcada como urgente' : 'Urgencia removida',
                'data' => $odt,
            ]);

        } catch (\InvalidArgumentException $e) {
            Response::json(['error' => 'ERR-ODT-URGENT', 'message' => $e->getMessage()], 422);
        }
    }

    // ─── POST /api/odt/bulk ─────────────────────────────

    public static function bulk(): void
    {
        $user = self::requireAuth();
        $body = self::getBody();

        $ids = $body['ids'] ?? [];
        $accion = $body['accion'] ?? '';
        $estado = $body['estado'] ?? null;
        $idCuadrilla = $body['id_cuadrilla'] ?? null;
        $fecha = $body['fecha_asignacion'] ?? '';

        if (empty($ids) || !is_array($ids)) {
            Response::json(['error' => 'ERR-ODT-BULK', 'message' => 'Se requiere al menos un ID'], 400);
            return;
        }

        $service = self::getService();
        $exitosos = 0;
        $errores = [];

        foreach ($ids as $idOdt) {
            try {
                $idOdt = (int) $idOdt;

                if ($estado) {
                    $service->cambiarEstado($idOdt, $estado, (int) $user['id_usuario']);
                }

                if ($idCuadrilla && $fecha) {
                    $service->asignar($idOdt, (int) $idCuadrilla, $fecha, 1, (int) $user['id_usuario']);
                }

                $exitosos++;
            } catch (\Exception $e) {
                $errores[] = ['id' => $idOdt, 'error' => $e->getMessage()];
            }
        }

        Response::json([
            'message' => "{$exitosos}/" . count($ids) . " ODTs procesadas",
            'exitosos' => $exitosos,
            'errores' => $errores,
        ]);
    }

    // ─── GET /api/odt/states ────────────────────────────

    public static function states(): void
    {
        self::requireAuth();

        Response::json([
            'states' => StateMachine::getAllStates(),
            'colors' => StateMachine::getStateColors(),
            'transitions' => self::getFullTransitionsMap(),
        ]);
    }

    // ─── Helpers ────────────────────────────────────────

    private static function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: [];
    }

    private static function getFullTransitionsMap(): array
    {
        $map = [];
        foreach (StateMachine::getAllStates() as $state) {
            $map[$state] = StateMachine::getTransicionesPermitidas($state);
        }
        return $map;
    }
}
