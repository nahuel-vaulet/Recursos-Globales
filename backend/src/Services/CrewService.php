<?php
/**
 * [!] ARCH: CrewService — Visibilidad ODT por cuadrilla (hoy+mañana)
 * [✓] AUDIT: Migrado al namespace App\Services, sin DateUtil dependency
 */

declare(strict_types=1);

namespace App\Services;

class CrewService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ODTs asignadas a una cuadrilla, restringidas a hoy y mañana
     */
    public function obtenerODTsCuadrilla(int $idCuadrilla): array
    {
        $hoy = date('Y-m-d');
        $manana = date('Y-m-d', strtotime('+1 day'));

        $stmt = $this->pdo->prepare("
            SELECT o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion,
                   o.prioridad, o.urgente_flag, o.orden, o.fecha_asignacion,
                   o.fecha_vencimiento,
                   t.nombre AS tipo_trabajo, t.codigo_trabajo
            FROM odt_maestro o
            LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
            WHERE o.id_cuadrilla = :cuadrilla
              AND o.fecha_asignacion IN (:hoy, :manana)
              AND o.estado_gestion != 'Certificar'
            ORDER BY o.fecha_asignacion ASC, o.orden ASC, o.urgente_flag DESC
        ");
        $stmt->execute([
            'cuadrilla' => $idCuadrilla,
            'hoy' => $hoy,
            'manana' => $manana,
        ]);

        $odts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $agrupadas = ['hoy' => [], 'manana' => []];
        foreach ($odts as $odt) {
            $key = ($odt['fecha_asignacion'] === $hoy) ? 'hoy' : 'manana';
            $agrupadas[$key][] = $odt;
        }

        return [
            'cuadrilla_id' => $idCuadrilla,
            'rango' => ['hoy' => $hoy, 'manana' => $manana],
            'hoy' => $agrupadas['hoy'],
            'manana' => $agrupadas['manana'],
            'total' => count($odts),
        ];
    }

    /**
     * Resumen de cuadrillas activas con count de ODT hoy/mañana
     */
    public function resumenCuadrillas(): array
    {
        $hoy = date('Y-m-d');
        $manana = date('Y-m-d', strtotime('+1 day'));

        $stmt = $this->pdo->prepare("
            SELECT c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex, c.tipo_especialidad,
                   COUNT(CASE WHEN o.fecha_asignacion = :hoy1 THEN 1 END) AS odts_hoy,
                   COUNT(CASE WHEN o.fecha_asignacion = :manana1 THEN 1 END) AS odts_manana
            FROM cuadrillas c
            LEFT JOIN odt_maestro o ON o.id_cuadrilla = c.id_cuadrilla
                AND o.fecha_asignacion IN (:hoy2, :manana2)
                AND o.estado_gestion != 'Certificar'
            WHERE c.estado_operativo = 'Activa'
            GROUP BY c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex, c.tipo_especialidad
            ORDER BY c.nombre_cuadrilla ASC
        ");
        $stmt->execute([
            'hoy1' => $hoy,
            'manana1' => $manana,
            'hoy2' => $hoy,
            'manana2' => $manana,
        ]);

        return [
            'rango' => ['hoy' => $hoy, 'manana' => $manana],
            'cuadrillas' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
        ];
    }

    /**
     * Cuadrillas activas para dropdowns
     */
    public function listarActivas(): array
    {
        $stmt = $this->pdo->query("
            SELECT id_cuadrilla, nombre_cuadrilla, color_hex, tipo_especialidad
            FROM cuadrillas 
            WHERE estado_operativo = 'Activa' 
            ORDER BY nombre_cuadrilla ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
