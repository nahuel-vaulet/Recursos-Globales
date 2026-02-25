<?php
/**
 * [!] ARCH: ODTService — Lógica de negocio para Órdenes de Trabajo
 * [✓] AUDIT: Migrado al namespace App\Services con SQL dual-mode (MySQL/PostgreSQL)
 * Separación estricta: este servicio NO sabe de HTTP ni de UI.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;

class ODTService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── Helpers SQL dual-mode ──────────────────────────

    /** Returns CURRENT_DATE for PostgreSQL, CURDATE() for MySQL */
    private function sqlCurrentDate(): string
    {
        return Database::isPostgres() ? 'CURRENT_DATE' : 'CURDATE()';
    }

    /** Returns CURRENT_TIMESTAMP for PostgreSQL, NOW() for MySQL */
    private function sqlNow(): string
    {
        return Database::isPostgres() ? 'CURRENT_TIMESTAMP' : 'NOW()';
    }

    /** Returns date addition syntax */
    private function sqlDateAdd(string $column, int $days): string
    {
        if (Database::isPostgres()) {
            return "{$column} + INTERVAL '{$days} days'";
        }
        return "DATE_ADD({$column}, INTERVAL {$days} DAY)";
    }

    // ─── CRUD ───────────────────────────────────────────

    /**
     * Lista ODTs con filtros avanzados
     *
     * @param array $filtros  estado, prioridad, cuadrilla, fecha_desde, fecha_hasta, search, urgente, vencimiento
     * @param string|null $rol Rol del usuario actual
     * @param int|null $idCuadrilla ID de cuadrilla (para JefeCuadrilla)
     */
    public function listarConFiltros(array $filtros = [], ?string $rol = null, ?int $idCuadrilla = null): array
    {
        $where = ['1=1'];
        $params = [];

        $sql = "SELECT o.*, 
                    t.nombre as tipo_trabajo, t.codigo_trabajo,
                    c.nombre_cuadrilla, c.color_hex
                FROM odt_maestro o
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                WHERE ";

        // JefeCuadrilla: solo su cuadrilla, hoy y mañana
        if ($rol === 'JefeCuadrilla' && $idCuadrilla) {
            $hoy = date('Y-m-d');
            $manana = date('Y-m-d', strtotime('+1 day'));
            $where[] = "o.id_cuadrilla = :crew_id";
            $where[] = "o.fecha_asignacion IN (:fecha_hoy, :fecha_manana)";
            $params['crew_id'] = $idCuadrilla;
            $params['fecha_hoy'] = $hoy;
            $params['fecha_manana'] = $manana;
        }

        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $where[] = "o.estado_gestion = :estado";
            $params['estado'] = $filtros['estado'];
        }

        // Filtro por prioridad
        if (!empty($filtros['prioridad'])) {
            $where[] = "o.prioridad = :prioridad";
            $params['prioridad'] = (int) $filtros['prioridad'];
        }

        // Filtro urgente
        if (!empty($filtros['urgente'])) {
            $where[] = "o.urgente_flag = 1";
        }

        // Filtro por cuadrilla
        if (!empty($filtros['cuadrilla'])) {
            if ($filtros['cuadrilla'] === 'SIN_ASIGNAR') {
                $where[] = "o.id_cuadrilla IS NULL";
            } else {
                $where[] = "o.id_cuadrilla = :filter_cuadrilla";
                $params['filter_cuadrilla'] = (int) $filtros['cuadrilla'];
            }
        }

        // Rango de fechas
        if (!empty($filtros['fecha_desde'])) {
            $where[] = "o.fecha_asignacion >= :fecha_desde";
            $params['fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "o.fecha_asignacion <= :fecha_hasta";
            $params['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Filtro por vencimiento (dual-mode SQL)
        if (!empty($filtros['vencimiento'])) {
            $curDate = $this->sqlCurrentDate();
            switch ($filtros['vencimiento']) {
                case 'vencidas':
                    $where[] = "o.fecha_vencimiento < {$curDate}";
                    break;
                case 'proximas':
                    $dateAdd = $this->sqlDateAdd($curDate, 7);
                    $where[] = "o.fecha_vencimiento BETWEEN {$curDate} AND {$dateAdd}";
                    break;
            }
        }

        // Búsqueda textual
        if (!empty($filtros['search'])) {
            $where[] = "(o.nro_odt_assa LIKE :search OR o.direccion LIKE :search OR o.inspector LIKE :search)";
            $params['search'] = '%' . $filtros['search'] . '%';
        }

        $sql .= implode(' AND ', $where);
        $sql .= " ORDER BY " . PriorityUtil::orderByClause('o') . ", o.fecha_vencimiento ASC";

        // Limit/offset
        $limit = (int) ($filtros['limit'] ?? 100);
        $offset = (int) ($filtros['offset'] ?? 0);
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una ODT por ID
     */
    public function obtenerPorId(int $idOdt): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, t.nombre as tipo_trabajo, t.codigo_trabajo,
                   c.nombre_cuadrilla, c.color_hex
            FROM odt_maestro o
            LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
            LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
            WHERE o.id_odt = ?
        ");
        $stmt->execute([$idOdt]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Cambia el estado validando la transición con StateMachine
     */
    public function cambiarEstado(int $idOdt, string $nuevoEstado, int $idUsuario, string $observacion = ''): bool
    {
        $odt = $this->obtenerPorId($idOdt);
        if (!$odt) {
            throw new \InvalidArgumentException("ODT #{$idOdt} no encontrada");
        }

        $estadoActual = $odt['estado_gestion'];
        $esUrgente = PriorityUtil::esUrgente($odt);

        StateMachine::validate($estadoActual, $nuevoEstado, $esUrgente);

        $now = $this->sqlNow();

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE odt_maestro SET estado_gestion = ?, updated_at = {$now} WHERE id_odt = ?");
            $stmt->execute([$nuevoEstado, $idOdt]);

            $this->registrarHistorial($idOdt, $estadoActual, $nuevoEstado, $idUsuario, $observacion);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Asigna una ODT a una cuadrilla con fecha y orden
     */
    public function asignar(int $idOdt, int $idCuadrilla, string $fechaAsignacion, int $orden, int $idUsuario): bool
    {
        if (empty($fechaAsignacion)) {
            throw new \InvalidArgumentException("La fecha de asignación es obligatoria");
        }
        if ($orden < 1) {
            throw new \InvalidArgumentException("El orden de ejecución debe ser mayor a 0");
        }

        $odt = $this->obtenerPorId($idOdt);
        if (!$odt) {
            throw new \InvalidArgumentException("ODT #{$idOdt} no encontrada");
        }

        $now = $this->sqlNow();

        $this->pdo->beginTransaction();
        try {
            // Update ODT
            $stmt = $this->pdo->prepare("
                UPDATE odt_maestro 
                SET id_cuadrilla = ?, fecha_asignacion = ?, orden = ?, 
                    estado_gestion = 'Asignado', updated_at = {$now}
                WHERE id_odt = ?
            ");
            $stmt->execute([$idCuadrilla, $fechaAsignacion, $orden, $idOdt]);

            // Upsert into programacion_semanal (dual-mode)
            if (Database::isPostgres()) {
                $stmtProg = $this->pdo->prepare("
                    INSERT INTO programacion_semanal (id_odt, id_cuadrilla, fecha_programada)
                    VALUES (?, ?, ?)
                    ON CONFLICT (id_odt) DO UPDATE SET id_cuadrilla = EXCLUDED.id_cuadrilla, fecha_programada = EXCLUDED.fecha_programada
                ");
            } else {
                $stmtProg = $this->pdo->prepare("
                    INSERT INTO programacion_semanal (id_odt, id_cuadrilla, fecha_programada)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE id_cuadrilla = VALUES(id_cuadrilla), fecha_programada = VALUES(fecha_programada)
                ");
            }
            $stmtProg->execute([$idOdt, $idCuadrilla, $fechaAsignacion]);

            // Historial
            $estadoAnterior = $odt['estado_gestion'];
            $this->registrarHistorial(
                $idOdt,
                $estadoAnterior,
                'Asignado',
                $idUsuario,
                "Asignada a cuadrilla #{$idCuadrilla}, fecha: {$fechaAsignacion}, orden: {$orden}"
            );

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Toggle urgente flag
     */
    public function toggleUrgente(int $idOdt, int $idUsuario): bool
    {
        $odt = $this->obtenerPorId($idOdt);
        if (!$odt) {
            throw new \InvalidArgumentException("ODT #{$idOdt} no encontrada");
        }

        $nuevoFlag = $odt['urgente_flag'] ? 0 : 1;
        $nuevaPrioridad = $nuevoFlag ? PriorityUtil::PRIORIDAD_URGENTE : PriorityUtil::PRIORIDAD_NORMAL;
        $now = $this->sqlNow();

        $stmt = $this->pdo->prepare("UPDATE odt_maestro SET urgente_flag = ?, prioridad = ?, updated_at = {$now} WHERE id_odt = ?");
        $stmt->execute([$nuevoFlag, $nuevaPrioridad, $idOdt]);

        $this->registrarHistorial(
            $idOdt,
            $odt['estado_gestion'],
            $odt['estado_gestion'],
            $idUsuario,
            $nuevoFlag ? 'Marcada como URGENTE' : 'Urgencia removida'
        );

        return true;
    }

    // ─── Métricas ───────────────────────────────────────

    public function getMetricas(array $odts): array
    {
        $metricas = [
            'total' => count($odts),
            'urgentes' => 0,
            'proximas_vencer' => 0,
        ];

        foreach (StateMachine::ESTADOS as $estado) {
            $key = str_replace(' ', '_', strtolower($estado));
            $metricas[$key] = 0;
        }

        foreach ($odts as $odt) {
            $key = str_replace(' ', '_', strtolower($odt['estado_gestion']));
            if (isset($metricas[$key])) {
                $metricas[$key]++;
            }

            if (PriorityUtil::esUrgente($odt)) {
                $metricas['urgentes']++;
            }

            // Próximas a vencer (7 días)
            $fechaVenc = $odt['fecha_vencimiento'] ?? null;
            if ($fechaVenc) {
                $dias = (int) round((strtotime($fechaVenc) - time()) / 86400);
                if ($dias >= 0 && $dias <= 7) {
                    $metricas['proximas_vencer']++;
                }
            }
        }

        return $metricas;
    }

    // ─── Historial ──────────────────────────────────────

    public function getHistorial(int $idOdt): array
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, u.nombre as usuario_nombre
            FROM odt_historial h
            LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
            WHERE h.id_odt = ?
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([$idOdt]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function registrarHistorial(
        int $idOdt,
        ?string $estadoAnterior,
        string $estadoNuevo,
        int $idUsuario,
        string $observacion = ''
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO odt_historial (id_odt, estado_anterior, estado_nuevo, id_usuario, observacion)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$idOdt, $estadoAnterior, $estadoNuevo, $idUsuario, $observacion]);
    }
}
