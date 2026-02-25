<?php
/**
 * [!] ARCH: DateUtil — Utilidades de fecha para ODT
 * Rangos hoy/mañana, cálculos de vencimiento, rangos de semana/mes.
 */

class DateUtil
{
    /**
     * Retorna rango [hoy, mañana] en formato Y-m-d
     * Usado para restricción de visibilidad de cuadrillas
     *
     * @return array{hoy: string, manana: string}
     */
    public static function rangeHoyYManana(): array
    {
        $hoy = new \DateTime();
        $manana = (clone $hoy)->modify('+1 day');

        return [
            'hoy' => $hoy->format('Y-m-d'),
            'manana' => $manana->format('Y-m-d'),
        ];
    }

    /**
     * Calcula los días hasta el vencimiento desde hoy
     *
     * @param string|null $fechaVencimiento Fecha en formato Y-m-d
     * @return int|null Días restantes (negativo si vencida), null si no hay fecha
     */
    public static function diasHastaVencimiento(?string $fechaVencimiento): ?int
    {
        if (empty($fechaVencimiento)) {
            return null;
        }

        $hoy = new \DateTime('today');
        $venc = new \DateTime($fechaVencimiento);
        $diff = $hoy->diff($venc);

        return $venc < $hoy ? -$diff->days : $diff->days;
    }

    /**
     * Retorna el nivel de alerta de vencimiento
     *
     * @return string 'vencida'|'critica'|'proxima'|'normal'|'sin_fecha'
     */
    public static function nivelAlertaVencimiento(?string $fechaVencimiento): string
    {
        $dias = self::diasHastaVencimiento($fechaVencimiento);

        if ($dias === null) {
            return 'sin_fecha';
        }
        if ($dias < 0) {
            return 'vencida';
        }
        if ($dias <= 3) {
            return 'critica';
        }
        if ($dias <= 7) {
            return 'proxima';
        }
        return 'normal';
    }

    /**
     * Retorna rango lunes-domingo para la semana que contiene la fecha dada
     *
     * @return array{inicio: string, fin: string}
     */
    public static function rangoDeSemana(string $fecha): array
    {
        $dt = new \DateTime($fecha);
        $dayOfWeek = (int) $dt->format('N'); // 1=Lunes, 7=Domingo

        $inicio = (clone $dt)->modify('-' . ($dayOfWeek - 1) . ' days');
        $fin = (clone $dt)->modify('+' . (7 - $dayOfWeek) . ' days');

        return [
            'inicio' => $inicio->format('Y-m-d'),
            'fin' => $fin->format('Y-m-d'),
        ];
    }

    /**
     * Retorna rango primer día - último día del mes
     *
     * @return array{inicio: string, fin: string, dias: int}
     */
    public static function rangoDeMes(int $anio, int $mes): array
    {
        $inicio = new \DateTime("{$anio}-{$mes}-01");
        $fin = (clone $inicio)->modify('last day of this month');

        return [
            'inicio' => $inicio->format('Y-m-d'),
            'fin' => $fin->format('Y-m-d'),
            'dias' => (int) $fin->format('d'),
        ];
    }

    /**
     * Genera un array de días para un mes dado con metadata de calendario
     *
     * @return array Lista de días con fecha, díaSemana, esHoy
     */
    public static function diasDelMes(int $anio, int $mes): array
    {
        $rango = self::rangoDeMes($anio, $mes);
        $hoy = date('Y-m-d');
        $dias = [];

        $current = new \DateTime($rango['inicio']);
        $fin = new \DateTime($rango['fin']);

        while ($current <= $fin) {
            $dias[] = [
                'fecha' => $current->format('Y-m-d'),
                'dia' => (int) $current->format('d'),
                'dia_semana' => (int) $current->format('N'),
                'nombre_dia' => self::nombreDia((int) $current->format('N')),
                'es_hoy' => $current->format('Y-m-d') === $hoy,
            ];
            $current->modify('+1 day');
        }

        return $dias;
    }

    /**
     * Nombre del día de la semana en español
     */
    public static function nombreDia(int $diaSemana): string
    {
        $nombres = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
        return $nombres[$diaSemana] ?? '';
    }

    /**
     * Nombre del mes en español
     */
    public static function nombreMes(int $mes): string
    {
        $nombres = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
        return $nombres[$mes] ?? '';
    }

    /**
     * Formatea una fecha Y-m-d al formato argentino dd/mm/YYYY
     */
    public static function formatear(?string $fecha): string
    {
        if (empty($fecha)) {
            return '-';
        }
        try {
            return (new \DateTime($fecha))->format('d/m/Y');
        } catch (\Exception $e) {
            return '-';
        }
    }
}
