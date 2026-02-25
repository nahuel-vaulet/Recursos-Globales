<?php
/**
 * [!] ARCH: CalendarService — Agregaciones de ODT para calendario
 * [✓] AUDIT: Migrado con dual-mode SQL
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;

class CalendarService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Resumen mensual: por cada día, lista de {cuadrilla, count_odt}
     */
    public function resumenMensual(int $anio, int $mes): array
    {
        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));

        $stmt = $this->pdo->prepare("
            SELECT o.fecha_asignacion as fecha,
                   c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex,
                   COUNT(*) as count_odt
            FROM odt_maestro o
            LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
            WHERE o.fecha_asignacion BETWEEN :inicio AND :fin
              AND o.estado_gestion != 'Certificar'
            GROUP BY o.fecha_asignacion, c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
            ORDER BY o.fecha_asignacion ASC, c.nombre_cuadrilla ASC
        ");
        $stmt->execute(['inicio' => $fechaInicio, 'fin' => $fechaFin]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group by date
        $dias = [];
        foreach ($rows as $row) {
            $fecha = $row['fecha'];
            if (!isset($dias[$fecha])) {
                $dias[$fecha] = [];
            }
            $dias[$fecha][] = [
                'cuadrilla' => $row['nombre_cuadrilla'],
                'color' => $row['color_hex'],
                'count' => (int) $row['count_odt'],
            ];
        }

        return ['dias' => $dias, 'meta' => ['anio' => $anio, 'mes' => $mes]];
    }

    /**
     * Resumen semanal: por cuadrilla por día
     */
    public function resumenSemanal(string $fecha): array
    {
        // Get Monday of that week
        $dt = new \DateTime($fecha);
        $dow = (int) $dt->format('N');
        $lunes = (clone $dt)->modify('-' . ($dow - 1) . ' days')->format('Y-m-d');
        $domingo = (clone $dt)->modify('+' . (7 - $dow) . ' days')->format('Y-m-d');

        $stmt = $this->pdo->prepare("
            SELECT o.fecha_asignacion as fecha,
                   c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex,
                   COUNT(*) as count_odt
            FROM odt_maestro o
            LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
            WHERE o.fecha_asignacion BETWEEN :lunes AND :domingo
              AND o.estado_gestion != 'Certificar'
            GROUP BY o.fecha_asignacion, c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex
            ORDER BY o.fecha_asignacion ASC
        ");
        $stmt->execute(['lunes' => $lunes, 'domingo' => $domingo]);

        return [
            'rango' => ['desde' => $lunes, 'hasta' => $domingo],
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
        ];
    }

    /**
     * Detalle diario: por cuadrilla, lista de ODTs
     */
    public function detalleDiario(string $fecha): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion,
                   o.prioridad, o.urgente_flag, o.orden,
                   c.id_cuadrilla, c.nombre_cuadrilla, c.color_hex,
                   t.nombre as tipo_trabajo
            FROM odt_maestro o
            LEFT JOIN cuadrillas c ON o.id_cuadrilla = c.id_cuadrilla
            LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
            WHERE o.fecha_asignacion = :fecha
            ORDER BY c.nombre_cuadrilla ASC, o.orden ASC
        ");
        $stmt->execute(['fecha' => $fecha]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group by squad
        $cuadrillas = [];
        foreach ($rows as $row) {
            $crewId = $row['id_cuadrilla'] ?? 0;
            if (!isset($cuadrillas[$crewId])) {
                $cuadrillas[$crewId] = [
                    'id_cuadrilla' => $crewId,
                    'nombre_cuadrilla' => $row['nombre_cuadrilla'] ?? 'Sin asignar',
                    'color_hex' => $row['color_hex'],
                    'odts' => [],
                ];
            }
            $cuadrillas[$crewId]['odts'][] = $row;
        }

        return ['fecha' => $fecha, 'cuadrillas' => array_values($cuadrillas)];
    }
}
