<?php
/**
 * [!] ARCH: PriorityUtil — Utilidades de prioridad y urgencia para ODT
 * [✓] AUDIT: Migrado al namespace App\Services, sin cambios de lógica
 */

declare(strict_types=1);

namespace App\Services;

class PriorityUtil
{
    public const PRIORIDAD_URGENTE = 1;
    public const PRIORIDAD_ALTA = 2;
    public const PRIORIDAD_NORMAL = 3;
    public const PRIORIDAD_BAJA = 4;
    public const PRIORIDAD_MINIMA = 5;

    private const ETIQUETAS = [
        1 => 'Urgente',
        2 => 'Alta',
        3 => 'Normal',
        4 => 'Baja',
        5 => 'Mínima',
    ];

    private const COLORES = [
        1 => ['bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '🔴'],
        2 => ['bg' => '#fff3e0', 'color' => '#e65100', 'icon' => '🟠'],
        3 => ['bg' => '#f5f5f5', 'color' => '#616161', 'icon' => '🔵'],
        4 => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '🟢'],
        5 => ['bg' => '#fafafa', 'color' => '#9e9e9e', 'icon' => '⚪'],
    ];

    public static function calcular(int $prioridad, bool $urgenteFlag): int
    {
        if ($urgenteFlag) {
            return self::PRIORIDAD_URGENTE;
        }
        return max(1, min(5, $prioridad));
    }

    public static function esUrgente(array $odt): bool
    {
        return !empty($odt['urgente_flag']) || (int) ($odt['prioridad'] ?? 3) === self::PRIORIDAD_URGENTE;
    }

    public static function etiqueta(int $nivel): string
    {
        return self::ETIQUETAS[$nivel] ?? 'Desconocida';
    }

    public static function colores(int $nivel): array
    {
        return self::COLORES[$nivel] ?? self::COLORES[3];
    }

    /**
     * ORDER BY clause — works for both MySQL and PostgreSQL
     */
    public static function orderByClause(string $alias = 'o'): string
    {
        return "{$alias}.urgente_flag DESC, {$alias}.prioridad ASC, {$alias}.orden ASC";
    }
}
