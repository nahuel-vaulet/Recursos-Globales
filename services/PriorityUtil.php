<?php
/**
 * [!] ARCH: PriorityUtil — Utilidades de prioridad y urgencia para ODT
 */

class PriorityUtil
{
    /** Niveles de prioridad: 1 = máxima (urgente), 5 = mínima */
    public const PRIORIDAD_URGENTE = 1;
    public const PRIORIDAD_ALTA = 2;
    public const PRIORIDAD_NORMAL = 3;
    public const PRIORIDAD_BAJA = 4;
    public const PRIORIDAD_MINIMA = 5;

    /**
     * Etiquetas legibles por nivel
     */
    private const ETIQUETAS = [
        1 => 'Urgente',
        2 => 'Alta',
        3 => 'Normal',
        4 => 'Baja',
        5 => 'Mínima',
    ];

    /**
     * Colores de badge por nivel
     */
    private const COLORES = [
        1 => ['bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '🔴'],
        2 => ['bg' => '#fff3e0', 'color' => '#e65100', 'icon' => '🟠'],
        3 => ['bg' => '#f5f5f5', 'color' => '#616161', 'icon' => '🔵'],
        4 => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '🟢'],
        5 => ['bg' => '#fafafa', 'color' => '#9e9e9e', 'icon' => '⚪'],
    ];

    /**
     * Calcula el nivel de prioridad efectivo
     *
     * @param int $prioridad Nivel numérico (1-5)
     * @param bool $urgenteFlag Flag de urgencia
     * @return int Nivel efectivo (1 si urgente, sino el valor original)
     */
    public static function calcular(int $prioridad, bool $urgenteFlag): int
    {
        if ($urgenteFlag) {
            return self::PRIORIDAD_URGENTE;
        }
        return max(1, min(5, $prioridad));
    }

    /**
     * Determina si una ODT es urgente
     *
     * @param array $odt Array con campos 'prioridad' y 'urgente_flag'
     * @return bool
     */
    public static function esUrgente(array $odt): bool
    {
        return !empty($odt['urgente_flag']) || (int) ($odt['prioridad'] ?? 3) === self::PRIORIDAD_URGENTE;
    }

    /**
     * Obtiene la etiqueta de una prioridad
     */
    public static function etiqueta(int $nivel): string
    {
        return self::ETIQUETAS[$nivel] ?? 'Desconocida';
    }

    /**
     * Obtiene los colores de badge para una prioridad
     */
    public static function colores(int $nivel): array
    {
        return self::COLORES[$nivel] ?? self::COLORES[3];
    }

    /**
     * Genera HTML del badge de prioridad
     */
    public static function renderBadge(int $nivel, bool $urgenteFlag = false): string
    {
        $efectivo = self::calcular($nivel, $urgenteFlag);
        $colors = self::colores($efectivo);
        $label = self::etiqueta($efectivo);

        return "<span style='background:{$colors['bg']}; color:{$colors['color']}; padding:3px 8px; border-radius:10px; font-size:0.8em; font-weight:600; display:inline-flex; align-items:center; gap:4px;'>{$colors['icon']} {$label}</span>";
    }

    /**
     * Genera la cláusula ORDER BY para prioridad en SQL
     */
    public static function orderByClause(string $alias = 'o'): string
    {
        return "{$alias}.urgente_flag DESC, {$alias}.prioridad ASC, {$alias}.orden ASC";
    }
}
