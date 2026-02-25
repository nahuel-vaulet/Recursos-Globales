<?php
/**
 * [!] ARCH: StateMachine — Validación de transiciones de estado ODT
 * Define los 14 estados válidos y la matriz de transiciones permitidas.
 * Urgentes pueden saltar validación para transición directa.
 */

class StateMachine
{
    /**
     * Estados válidos del ciclo operativo ODT
     */
    public const ESTADOS = [
        'Nuevo',
        'Inspeccionar',
        'Inspeccionado',
        'Priorizado',
        'Programado',
        'Asignado',
        'En ejecución',
        'Ejecutado',
        'Retorno',
        'Corregido',
        'Auditar',
        'Precertificar',
        'Certificar',
        'Reprogramar por visita fallida',
    ];

    /**
     * Matriz de transiciones válidas: estado_origen => [estados_destino_permitidos]
     */
    private const TRANSICIONES = [
        'Nuevo' => ['Inspeccionar', 'Priorizado', 'Programado'],
        'Inspeccionar' => ['Inspeccionado'],
        'Inspeccionado' => ['Priorizado'],
        'Priorizado' => ['Programado'],
        'Programado' => ['Asignado', 'Reprogramar por visita fallida'],
        'Asignado' => ['En ejecución', 'Reprogramar por visita fallida'],
        'En ejecución' => ['Ejecutado', 'Reprogramar por visita fallida'],
        'Ejecutado' => ['Retorno', 'Auditar'],
        'Retorno' => ['Corregido'],
        'Corregido' => ['Auditar'],
        'Auditar' => ['Precertificar', 'Retorno'],
        'Precertificar' => ['Certificar', 'Retorno'],
        'Certificar' => [], // Estado final
        'Reprogramar por visita fallida' => ['Programado', 'Asignado'],
    ];

    /**
     * Estados a los que un ODT urgente puede saltar directamente
     */
    private const SALTOS_URGENTE = [
        'Programado',
        'Asignado',
        'En ejecución',
    ];

    /**
     * Verifica si una transición es válida
     *
     * @param string $estadoActual Estado actual de la ODT
     * @param string $estadoNuevo Estado al que se quiere transicionar
     * @param bool $esUrgente Si la ODT es urgente
     * @return bool
     */
    public static function canTransition(string $estadoActual, string $estadoNuevo, bool $esUrgente = false): bool
    {
        // Validar que ambos estados sean válidos
        if (!self::isValidState($estadoActual) || !self::isValidState($estadoNuevo)) {
            return false;
        }

        // Si es urgente, puede saltar directamente a ciertos estados
        if ($esUrgente && in_array($estadoNuevo, self::SALTOS_URGENTE, true)) {
            return true;
        }

        // Verificar en la matriz de transiciones
        $permitidos = self::TRANSICIONES[$estadoActual] ?? [];
        return in_array($estadoNuevo, $permitidos, true);
    }

    /**
     * Valida la transición y lanza excepción si no es válida
     *
     * @throws \InvalidArgumentException
     */
    public static function validate(string $estadoActual, string $estadoNuevo, bool $esUrgente = false): void
    {
        if (!self::canTransition($estadoActual, $estadoNuevo, $esUrgente)) {
            $urgMsg = $esUrgente ? ' (urgente)' : '';
            throw new \InvalidArgumentException(
                "Transición de estado inválida{$urgMsg}: '{$estadoActual}' → '{$estadoNuevo}'. " .
                "Transiciones permitidas desde '{$estadoActual}': " .
                implode(', ', self::getTransicionesPermitidas($estadoActual, $esUrgente))
            );
        }
    }

    /**
     * Verifica si un estado es válido
     */
    public static function isValidState(string $estado): bool
    {
        return in_array($estado, self::ESTADOS, true);
    }

    /**
     * Obtiene las transiciones permitidas desde un estado dado
     *
     * @return string[]
     */
    public static function getTransicionesPermitidas(string $estadoActual, bool $esUrgente = false): array
    {
        $transiciones = self::TRANSICIONES[$estadoActual] ?? [];

        if ($esUrgente) {
            $transiciones = array_unique(array_merge($transiciones, self::SALTOS_URGENTE));
        }

        return array_values($transiciones);
    }

    /**
     * Obtiene todos los estados válidos como array
     */
    public static function getAllStates(): array
    {
        return self::ESTADOS;
    }

    /**
     * Obtiene colores y badges para cada estado (UI)
     */
    public static function getStateColors(): array
    {
        return [
            'Nuevo' => ['bg' => '#fff3e0', 'color' => '#e65100', 'icon' => 'fas fa-plus-circle'],
            'Inspeccionar' => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'icon' => 'fas fa-search'],
            'Inspeccionado' => ['bg' => '#e8eaf6', 'color' => '#283593', 'icon' => 'fas fa-clipboard-check'],
            'Priorizado' => ['bg' => '#fce4ec', 'color' => '#c62828', 'icon' => 'fas fa-sort-amount-up'],
            'Programado' => ['bg' => '#f3e5f5', 'color' => '#7b1fa2', 'icon' => 'fas fa-calendar-alt'],
            'Asignado' => ['bg' => '#e0f2f1', 'color' => '#00695c', 'icon' => 'fas fa-user-check'],
            'En ejecución' => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => 'fas fa-running'],
            'Ejecutado' => ['bg' => '#f1f8e9', 'color' => '#33691e', 'icon' => 'fas fa-check-double'],
            'Retorno' => ['bg' => '#fbe9e7', 'color' => '#bf360c', 'icon' => 'fas fa-undo'],
            'Corregido' => ['bg' => '#fff8e1', 'color' => '#ff8f00', 'icon' => 'fas fa-wrench'],
            'Auditar' => ['bg' => '#ede7f6', 'color' => '#4a148c', 'icon' => 'fas fa-eye'],
            'Precertificar' => ['bg' => '#e0f7fa', 'color' => '#006064', 'icon' => 'fas fa-certificate'],
            'Certificar' => ['bg' => '#e8f5e9', 'color' => '#1b5e20', 'icon' => 'fas fa-award'],
            'Reprogramar por visita fallida' => ['bg' => '#fff3e0', 'color' => '#e65100', 'icon' => 'fas fa-calendar-times'],
        ];
    }
}
