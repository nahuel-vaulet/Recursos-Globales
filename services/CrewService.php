<?php
/**
 * [!] ARCH: CrewService — Visibilidad y asignación de ODT para Cuadrillas
 * Restricción: cuadrillas solo ven ODT de hoy y mañana.
 */

require_once __DIR__ . '/DateUtil.php';
require_once __DIR__ . '/ErrorService.php';

class CrewService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene ODTs asignadas a una cuadrilla, restringidas a hoy y mañana
     *
     * @param int $idCuadrilla ID de cuadrilla
     * @return array Lista de ODTs
     */
    public function obtenerODTsCuadrilla(int $idCuadrilla): array
    {
        try {
            $rango = DateUtil::rangeHoyYManana();

            $stmt = $this->pdo->prepare("
                SELECT o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion,
                       o.prioridad, o.urgente_flag, o.orden, o.fecha_asignacion,
                       o.fecha_vencimiento,
                       t.nombre AS tipo_trabajo, t.codigo_trabajo
                FROM odt_maestro o
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                WHERE o.id_cuadrilla = :cuadrilla
                  AND o.fecha_asignacion IN (:hoy, :manana)
                  AND o.estado_gestion NOT IN ('Certificar')
                ORDER BY o.fecha_asignacion ASC, o.orden ASC, o.urgente_flag DESC
            ");
            $stmt->execute([
                'cuadrilla' => $idCuadrilla,
                'hoy' => $rango['hoy'],
                'manana' => $rango['manana'],
            ]);

            $odts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por fecha (hoy/mañana)
            $agrupadas = ['hoy' => [], 'manana' => []];
            foreach ($odts as $odt) {
                $key = ($odt['fecha_asignacion'] === $rango['hoy']) ? 'hoy' : 'manana';
                $agrupadas[$key][] = $odt;
            }

            return [
                'cuadrilla_id' => $idCuadrilla,
                'rango' => $rango,
                'hoy' => $agrupadas['hoy'],
                'manana' => $agrupadas['manana'],
                'total' => count($odts),
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'crew_odts', ['cuadrilla' => $idCuadrilla]);
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Obtiene resumen de todas las cuadrillas activas con count de ODT para hoy y mañana
     *
     * @return array
     */
    public function resumenCuadrillas(): array
    {
        try {
            $rango = DateUtil::rangeHoyYManana();

            $stmt = $this->pdo->prepare("
                SELECT c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex, c.tipo_especialidad,
                       COUNT(CASE WHEN o.fecha_asignacion = :hoy1 THEN 1 END) AS odts_hoy,
                       COUNT(CASE WHEN o.fecha_asignacion = :manana1 THEN 1 END) AS odts_manana
                FROM cuadrillas c
                LEFT JOIN odt_maestro o ON o.id_cuadrilla = c.id_cuadrilla
                    AND o.fecha_asignacion IN (:hoy2, :manana2)
                    AND o.estado_gestion NOT IN ('Certificar')
                WHERE c.estado_operativo = 'Activa'
                GROUP BY c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex, c.tipo_especialidad
                ORDER BY c.nombre_cuadrilla ASC
            ");
            $stmt->execute([
                'hoy1' => $rango['hoy'],
                'manana1' => $rango['manana'],
                'hoy2' => $rango['hoy'],
                'manana2' => $rango['manana'],
            ]);

            return [
                'rango' => $rango,
                'cuadrillas' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            ];

        } catch (\PDOException $e) {
            $error = ErrorService::captureDbError($e, 'crew_summary');
            throw new \RuntimeException($error['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Obtiene las cuadrillas activas (para dropdowns)
     *
     * @return array
     */
    public function listarActivas(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT id_cuadrilla, nombre_cuadrilla, color_hex, tipo_especialidad
                FROM cuadrillas 
                WHERE estado_operativo = 'Activa' 
                ORDER BY nombre_cuadrilla ASC
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            ErrorService::captureDbError($e, 'crew_list');
            return [];
        }
    }
}
