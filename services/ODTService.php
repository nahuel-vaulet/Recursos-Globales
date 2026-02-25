<?php
/**
 * [!] ARCH: ODTService — Lógica de negocio para Órdenes de Trabajo
 * Separación estricta: este servicio NO sabe de HTTP ni de UI.
 */

require_once __DIR__ . '/StateMachine.php';
require_once __DIR__ . '/DateUtil.php';
require_once __DIR__ . '/PriorityUtil.php';
require_once __DIR__ . '/ErrorService.php';

class ODTService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista ODTs con filtros avanzados
     *
     * @param array $filtros Filtros opcionales: estado, prioridad, cuadrilla, fecha_desde, fecha_hasta, search, urgente
     * @param string|null $rol Rol del usuario actual
     * @param int|null $idCuadrilla ID de cuadrilla del usuario (para JefeCuadrilla)
     * @return array Lista de ODTs
     */
    public function listarConFiltros(array $filtros = [], ?string $rol = null, ?int $idCuadrilla = null): array
    {
        try {
            $where = ['1=1'];
            $params = [];

            // Base query con JOINs
            $sql = "SELECT o.*, 
                        t.nombre as tipo_trabajo, t.codigo_trabajo,
                        c.nombre_cuadrilla, c.color_hex
                    FROM odt_maestro o
                    LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                    LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                    WHERE ";

            // Filtro por rol: JefeCuadrilla solo ve su cuadrilla, hoy y mañana
            if ($rol === 'JefeCuadrilla' && $idCuadrilla) {
                $rango = DateUtil::rangeHoyYManana();
                $where[] = "o.id_cuadrilla = :crew_id";
                $where[] = "o.fecha_asignacion IN (:fecha_hoy, :fecha_manana)";
                $params['crew_id'] = $idCuadrilla;
                $params['fecha_hoy'] = $rango['hoy'];
                $params['fecha_manana'] = $rango['manana'];
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

            // Filtro por urgente
            if (isset($filtros['urgente']) && $filtros['urgente']) {
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

            // Filtro por rango de fechas (fecha_asignacion)
            if (!empty($filtros['fecha_desde'])) {
                $where[] = "o.fecha_asignacion >= :fecha_desde";
                $params['fecha_desde'] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $where[] = "o.fecha_asignacion <= :fecha_hasta";
                $params['fecha_hasta'] = $filtros['fecha_hasta'];
            }

            // Filtro por vencimiento
            if (!empty($filtros['vencimiento'])) {
                switch ($filtros['vencimiento']) {
                    case 'vencidas':
                        $where[] = "o.fecha_vencimiento < CURDATE()";
                        break;
                    case 'proximas':
                        $where[] = "o.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
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

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'odt_list', $filtros);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Obtiene una ODT por ID
     */
    public function obtenerPorId(int $idOdt): ?array
    {
        try {
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

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'odt_get', ['id' => $idOdt]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Cambia el estado de una ODT validando la transición
     *
     * @param int $idOdt ID de la ODT
     * @param string $nuevoEstado Estado destino
     * @param int $idUsuario ID del usuario que hace el cambio
     * @param string $observacion Observación opcional
     * @return bool
     */
    public function cambiarEstado(int $idOdt, string $nuevoEstado, int $idUsuario, string $observacion = ''): bool
    {
        try {
            $odt = $this->obtenerPorId($idOdt);
            if (!$odt) {
                throw new \InvalidArgumentException("ODT #{$idOdt} no encontrada");
            }

            $estadoActual = $odt['estado_gestion'];
            $esUrgente = PriorityUtil::esUrgente($odt);

            // Validar transición
            StateMachine::validate($estadoActual, $nuevoEstado, $esUrgente);

            // Ejecutar cambio
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE odt_maestro SET estado_gestion = ?, updated_at = NOW() WHERE id_odt = ?");
            $stmt->execute([$nuevoEstado, $idOdt]);

            // Registrar en historial
            $this->registrarHistorial($idOdt, $estadoActual, $nuevoEstado, $idUsuario, $observacion);

            $this->pdo->commit();
            return true;

        } catch (\InvalidArgumentException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (\PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $error = ErrorService::captureDbError($e, 'odt_transition', ['id' => $idOdt, 'estado' => $nuevoEstado]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Asigna una ODT a una cuadrilla con fecha y orden
     *
     * @param int $idOdt ID de la ODT
     * @param int $idCuadrilla ID de la cuadrilla
     * @param string $fechaAsignacion Fecha en formato Y-m-d
     * @param int $orden Orden de ejecución
     * @param int $idUsuario ID del usuario
     * @return bool
     */
    public function asignar(int $idOdt, int $idCuadrilla, string $fechaAsignacion, int $orden, int $idUsuario): bool
    {
        try {
            // Validar inputs
            if (empty($fechaAsignacion)) {
                throw new \InvalidArgumentException("La fecha de asignación es obligatoria al asignar una ODT");
            }
            if ($orden < 1) {
                throw new \InvalidArgumentException("El orden de ejecución debe ser mayor a 0");
            }

            $odt = $this->obtenerPorId($idOdt);
            if (!$odt) {
                throw new \InvalidArgumentException("ODT #{$idOdt} no encontrada");
            }

            $this->pdo->beginTransaction();

            // Actualizar ODT
            $stmt = $this->pdo->prepare("
                UPDATE odt_maestro 
                SET id_cuadrilla = ?, fecha_asignacion = ?, orden = ?, 
                    estado_gestion = 'Asignado', updated_at = NOW()
                WHERE id_odt = ?
            ");
            $stmt->execute([$idCuadrilla, $fechaAsignacion, $orden, $idOdt]);

            // Insertar/Actualizar en programacion_semanal para compatibilidad
            $stmtProg = $this->pdo->prepare("
                INSERT INTO programacion_semanal (id_odt, id_cuadrilla, fecha_programada)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE id_cuadrilla = VALUES(id_cuadrilla), fecha_programada = VALUES(fecha_programada)
            ");
            $stmtProg->execute([$idOdt, $idCuadrilla, $fechaAsignacion]);

            // Registrar historial
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

        } catch (\InvalidArgumentException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (\PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $error = ErrorService::captureDbError($e, 'odt_assign', [
                'id' => $idOdt,
                'cuadrilla' => $idCuadrilla,
                'fecha' => $fechaAsignacion,
            ]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Toggle urgente flag
     */
    public function toggleUrgente(int $idOdt, int $idUsuario): bool
    {
        try {
            $odt = $this->obtenerPorId($idOdt);
            if (!$odt) {
                throw new \InvalidArgumentException("ODT #{$idOdt} no encontrada");
            }

            $nuevoFlag = $odt['urgente_flag'] ? 0 : 1;
            $nuevaPrioridad = $nuevoFlag ? PriorityUtil::PRIORIDAD_URGENTE : PriorityUtil::PRIORIDAD_NORMAL;

            $stmt = $this->pdo->prepare("UPDATE odt_maestro SET urgente_flag = ?, prioridad = ?, updated_at = NOW() WHERE id_odt = ?");
            $stmt->execute([$nuevoFlag, $nuevaPrioridad, $idOdt]);

            $this->registrarHistorial(
                $idOdt,
                $odt['estado_gestion'],
                $odt['estado_gestion'],
                $idUsuario,
                $nuevoFlag ? 'Marcada como URGENTE' : 'Urgencia removida'
            );

            return true;

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'odt_urgent', ['id' => $idOdt]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Calcula métricas agregadas
     */
    public function getMetricas(array $odts): array
    {
        $metricas = [
            'total' => count($odts),
            'urgentes' => 0,
            'proximas_vencer' => 0,
        ];

        // Inicializar contadores por estado
        foreach (StateMachine::ESTADOS as $estado) {
            $key = str_replace(' ', '_', strtolower($estado));
            $metricas[$key] = 0;
        }

        foreach ($odts as $odt) {
            // Conteo por estado
            $key = str_replace(' ', '_', strtolower($odt['estado_gestion']));
            if (isset($metricas[$key])) {
                $metricas[$key]++;
            }

            // Urgentes
            if (PriorityUtil::esUrgente($odt)) {
                $metricas['urgentes']++;
            }

            // Próximas a vencer (7 días)
            $dias = DateUtil::diasHastaVencimiento($odt['fecha_vencimiento'] ?? null);
            if ($dias !== null && $dias >= 0 && $dias <= 7) {
                $metricas['proximas_vencer']++;
            }
        }

        return $metricas;
    }

    /**
     * Obtiene el historial de una ODT
     */
    public function getHistorial(int $idOdt): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT h.*, u.nombre as usuario_nombre
                FROM odt_historial h
                LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
                WHERE h.id_odt = ?
                ORDER BY h.created_at DESC
            ");
            $stmt->execute([$idOdt]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            ErrorService::captureDbError($e, 'odt_historial', ['id' => $idOdt]);
            return [];
        }
    }

    /**
     * Registra entrada en el historial de cambios de estado
     */
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
