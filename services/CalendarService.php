<?php
/**
 * [!] ARCH: CalendarService — Agregaciones de ODT para vistas de calendario
 * Mes: resumen por cuadrilla → count. Semana: cuadrilla por día → count. Día: detalle por cuadrilla.
 */

require_once __DIR__ . '/DateUtil.php';
require_once __DIR__ . '/ErrorService.php';

class CalendarService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Resumen mensual: por cada día del mes, lista de {cuadrilla, count_odt}
     *
     * @param int $anio Año (ej: 2026)
     * @param int $mes Mes (1-12)
     * @return array ['dias' => [...], 'meta' => [...]]
     */
    public function resumenMensual(int $anio, int $mes): array
    {
        try {
            $rango = DateUtil::rangoDeMes($anio, $mes);

            $stmt = $this->pdo->prepare("
                SELECT 
                    o.fecha_asignacion AS fecha,
                    c.id_cuadrilla,
                    c.nombre_cuadrilla,
                    c.color_hex,
                    COUNT(o.id_odt) AS count_odt
                FROM odt_maestro o
                INNER JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                WHERE o.fecha_asignacion BETWEEN :inicio AND :fin
                  AND o.estado_gestion NOT IN ('Certificar')
                GROUP BY o.fecha_asignacion, c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
                ORDER BY o.fecha_asignacion ASC, c.nombre_cuadrilla ASC
            ");
            $stmt->execute([
                'inicio' => $rango['inicio'],
                'fin' => $rango['fin'],
            ]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por día
            $porDia = [];
            foreach ($rows as $row) {
                $fecha = $row['fecha'];
                if (!isset($porDia[$fecha])) {
                    $porDia[$fecha] = [];
                }
                $porDia[$fecha][] = [
                    'id_cuadrilla' => (int) $row['id_cuadrilla'],
                    'nombre' => $row['nombre_cuadrilla'],
                    'color' => $row['color_hex'] ?? '#2196F3',
                    'count' => (int) $row['count_odt'],
                ];
            }

            return [
                'anio' => $anio,
                'mes' => $mes,
                'nombre_mes' => DateUtil::nombreMes($mes),
                'rango' => $rango,
                'dias' => $porDia,
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'calendar_month', ['anio' => $anio, 'mes' => $mes]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Resumen semanal: por cuadrilla por día, count ODT
     *
     * @param string $fecha Cualquier fecha de la semana (Y-m-d)
     * @return array
     */
    public function resumenSemanal(string $fecha): array
    {
        try {
            $rango = DateUtil::rangoDeSemana($fecha);

            $stmt = $this->pdo->prepare("
                SELECT 
                    o.fecha_asignacion AS fecha,
                    c.id_cuadrilla,
                    c.nombre_cuadrilla,
                    c.color_hex,
                    COUNT(o.id_odt) AS count_odt
                FROM odt_maestro o
                INNER JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                WHERE o.fecha_asignacion BETWEEN :inicio AND :fin
                  AND o.estado_gestion NOT IN ('Certificar')
                GROUP BY o.fecha_asignacion, c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
                ORDER BY c.nombre_cuadrilla ASC, o.fecha_asignacion ASC
            ");
            $stmt->execute([
                'inicio' => $rango['inicio'],
                'fin' => $rango['fin'],
            ]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por cuadrilla
            $porCuadrilla = [];
            foreach ($rows as $row) {
                $cId = (int) $row['id_cuadrilla'];
                if (!isset($porCuadrilla[$cId])) {
                    $porCuadrilla[$cId] = [
                        'id' => $cId,
                        'nombre' => $row['nombre_cuadrilla'],
                        'color' => $row['color_hex'] ?? '#2196F3',
                        'dias' => [],
                    ];
                }
                $porCuadrilla[$cId]['dias'][$row['fecha']] = (int) $row['count_odt'];
            }

            return [
                'rango' => $rango,
                'cuadrillas' => array_values($porCuadrilla),
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'calendar_week', ['fecha' => $fecha]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Detalle diario: por cuadrilla, lista de ODTs con tipo de trabajo
     *
     * @param string $fecha Fecha específica (Y-m-d)
     * @return array
     */
    public function detalleDiario(string $fecha): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion,
                    o.prioridad, o.urgente_flag, o.orden,
                    t.nombre AS tipo_trabajo, t.codigo_trabajo,
                    c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
                FROM odt_maestro o
                INNER JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                WHERE o.fecha_asignacion = :fecha
                ORDER BY c.nombre_cuadrilla ASC, o.orden ASC, o.prioridad ASC
            ");
            $stmt->execute(['fecha' => $fecha]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por cuadrilla
            $porCuadrilla = [];
            foreach ($rows as $row) {
                $cId = (int) $row['id_cuadrilla'];
                if (!isset($porCuadrilla[$cId])) {
                    $porCuadrilla[$cId] = [
                        'id' => $cId,
                        'nombre' => $row['nombre_cuadrilla'],
                        'color' => $row['color_hex'] ?? '#2196F3',
                        'odts' => [],
                    ];
                }
                $porCuadrilla[$cId]['odts'][] = [
                    'id' => (int) $row['id_odt'],
                    'numero' => $row['nro_odt_assa'],
                    'direccion' => $row['direccion'],
                    'estado' => $row['estado_gestion'],
                    'tipo_trabajo' => $row['tipo_trabajo'],
                    'codigo_trabajo' => $row['codigo_trabajo'],
                    'prioridad' => (int) $row['prioridad'],
                    'urgente' => (bool) $row['urgente_flag'],
                    'orden' => (int) $row['orden'],
                ];
            }

            return [
                'fecha' => $fecha,
                'fecha_formato' => DateUtil::formatear($fecha),
                'nombre_dia' => DateUtil::nombreDia((int) (new \DateTime($fecha))->format('N')),
                'cuadrillas' => array_values($porCuadrilla),
                'total_odts' => count($rows),
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'calendar_day', ['fecha' => $fecha]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    // ── VENCIMIENTOS ──────────────────────────────────────────

    /**
     * Resumen mensual por fecha de vencimiento
     * Agrupa ODTs por día de vencimiento y cuadrilla, incluye nivel de alerta
     */
    public function resumenMensualVencimientos(int $anio, int $mes): array
    {
        try {
            $rango = DateUtil::rangoDeMes($anio, $mes);

            $stmt = $this->pdo->prepare("
                SELECT 
                    o.fecha_vencimiento AS fecha,
                    c.id_cuadrilla,
                    c.nombre_cuadrilla,
                    c.color_hex,
                    COUNT(o.id_odt) AS count_odt
                FROM odt_maestro o
                LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                WHERE o.fecha_vencimiento BETWEEN :inicio AND :fin
                  AND o.estado_gestion NOT IN ('Certificar', 'Aprobado por inspector')
                GROUP BY o.fecha_vencimiento, c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
                ORDER BY o.fecha_vencimiento ASC, c.nombre_cuadrilla ASC
            ");
            $stmt->execute([
                'inicio' => $rango['inicio'],
                'fin' => $rango['fin'],
            ]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $porDia = [];
            foreach ($rows as $row) {
                $fecha = $row['fecha'];
                if (!isset($porDia[$fecha])) {
                    $porDia[$fecha] = [];
                }
                $nivel = DateUtil::nivelAlertaVencimiento($fecha);
                $porDia[$fecha][] = [
                    'id_cuadrilla' => (int) ($row['id_cuadrilla'] ?? 0),
                    'nombre' => $row['nombre_cuadrilla'] ?? 'Sin asignar',
                    'color' => $row['color_hex'] ?? '#9E9E9E',
                    'count' => (int) $row['count_odt'],
                    'nivel_alerta' => $nivel,
                ];
            }

            return [
                'anio' => $anio,
                'mes' => $mes,
                'nombre_mes' => DateUtil::nombreMes($mes),
                'rango' => $rango,
                'dias' => $porDia,
                'mode' => 'duedate',
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'calendar_month_venc', ['anio' => $anio, 'mes' => $mes]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Resumen semanal por fecha de vencimiento
     */
    public function resumenSemanalVencimientos(string $fecha): array
    {
        try {
            $rango = DateUtil::rangoDeSemana($fecha);

            $stmt = $this->pdo->prepare("
                SELECT 
                    o.fecha_vencimiento AS fecha,
                    c.id_cuadrilla,
                    c.nombre_cuadrilla,
                    c.color_hex,
                    COUNT(o.id_odt) AS count_odt
                FROM odt_maestro o
                LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                WHERE o.fecha_vencimiento BETWEEN :inicio AND :fin
                  AND o.estado_gestion NOT IN ('Certificar', 'Aprobado por inspector')
                GROUP BY o.fecha_vencimiento, c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
                ORDER BY c.nombre_cuadrilla ASC, o.fecha_vencimiento ASC
            ");
            $stmt->execute([
                'inicio' => $rango['inicio'],
                'fin' => $rango['fin'],
            ]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $porCuadrilla = [];
            foreach ($rows as $row) {
                $cId = (int) ($row['id_cuadrilla'] ?? 0);
                if (!isset($porCuadrilla[$cId])) {
                    $porCuadrilla[$cId] = [
                        'id' => $cId,
                        'nombre' => $row['nombre_cuadrilla'] ?? 'Sin asignar',
                        'color' => $row['color_hex'] ?? '#9E9E9E',
                        'dias' => [],
                    ];
                }
                $porCuadrilla[$cId]['dias'][$row['fecha']] = (int) $row['count_odt'];
            }

            return [
                'rango' => $rango,
                'cuadrillas' => array_values($porCuadrilla),
                'mode' => 'duedate',
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'calendar_week_venc', ['fecha' => $fecha]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Detalle diario por fecha de vencimiento
     * Incluye dias_restantes y nivel_alerta para cada ODT
     */
    public function detalleDiarioVencimientos(string $fecha): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion,
                    o.prioridad, o.urgente_flag, o.orden, o.fecha_vencimiento,
                    o.fecha_asignacion,
                    t.nombre AS tipo_trabajo, t.codigo_trabajo,
                    c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
                FROM odt_maestro o
                LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                WHERE o.fecha_vencimiento = :fecha
                  AND o.estado_gestion NOT IN ('Certificar', 'Aprobado por inspector')
                ORDER BY c.nombre_cuadrilla ASC, o.prioridad ASC, o.orden ASC
            ");
            $stmt->execute(['fecha' => $fecha]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $porCuadrilla = [];
            foreach ($rows as $row) {
                $cId = (int) ($row['id_cuadrilla'] ?? 0);
                $cKey = $cId ?: 'sin_asignar';
                if (!isset($porCuadrilla[$cKey])) {
                    $porCuadrilla[$cKey] = [
                        'id' => $cId,
                        'nombre' => $row['nombre_cuadrilla'] ?? 'Sin asignar',
                        'color' => $row['color_hex'] ?? '#9E9E9E',
                        'odts' => [],
                    ];
                }
                $diasRestantes = DateUtil::diasHastaVencimiento($row['fecha_vencimiento']);
                $porCuadrilla[$cKey]['odts'][] = [
                    'id' => (int) $row['id_odt'],
                    'numero' => $row['nro_odt_assa'],
                    'direccion' => $row['direccion'],
                    'estado' => $row['estado_gestion'],
                    'tipo_trabajo' => $row['tipo_trabajo'],
                    'codigo_trabajo' => $row['codigo_trabajo'],
                    'prioridad' => (int) $row['prioridad'],
                    'urgente' => (bool) $row['urgente_flag'],
                    'orden' => (int) $row['orden'],
                    'fecha_vencimiento' => $row['fecha_vencimiento'],
                    'fecha_asignacion' => $row['fecha_asignacion'],
                    'dias_restantes' => $diasRestantes,
                    'nivel_alerta' => DateUtil::nivelAlertaVencimiento($row['fecha_vencimiento']),
                ];
            }

            return [
                'fecha' => $fecha,
                'fecha_formato' => DateUtil::formatear($fecha),
                'nombre_dia' => DateUtil::nombreDia((int) (new \DateTime($fecha))->format('N')),
                'cuadrillas' => array_values($porCuadrilla),
                'total_odts' => count($rows),
                'mode' => 'duedate',
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'calendar_day_venc', ['fecha' => $fecha]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }
}
