<?php
/**
 * Matriz de Permisos por Rol
 * ERP Recursos Globales
 * 
 * Define qué módulos puede ver cada rol.
 * '*' = acceso total a todos los módulos
 */

$PERMISOS = [
    // Gerente: Acceso total a todos los módulos
    'Gerente' => ['*'],

    // Administrativo: Todo excepto reportes y usuarios del sistema (pero ve su dashboard)
    'Administrativo' => [
        'materiales',
        'proveedores',
        'tipos_trabajos',
        'stock',
        'combustibles',
        'movimientos',
        'stock_cuadrilla',
        'personal',
        'vehiculos',
        'cuadrillas',
        'herramientas',
        'odt',
        'programacion',
        'partes',
        'partes',
        'reportes',
        'tareas',
        'gastos'
    ],

    // Administrativo ASSA (Nuevo): Dashboard Operativo + ODTs + Stock + Asistencia
    'Administrativo ASSA' => [
        'odt',
        'stock',
        'combustibles',
        'personal',
        'asistencia', // Módulo de asistencia
        'reportes'
    ],

    // Inspector ASSA (Nuevo): Solo ODTs (Carga forzada)
    'Inspector ASSA' => [
        'odt',
        'tareas'
    ],

    // Coordinador ASSA (Nuevo): Dashboard + Administrativo + Inspector
    'Coordinador ASSA' => [
        'odt',
        'stock',
        'combustibles',
        'personal',
        'asistencia',
        'reportes',
        'materiales',
        'materiales',
        'proveedores',
        'tareas'
    ],

    // Jefe de Cuadrilla: Cuadrillas + Partes Diarios (filtrado a la suya)
    'JefeCuadrilla' => [
        'cuadrillas',
        'partes'
    ]
];

/**
 * Módulos que requieren acceso especial o tienen comportamiento distinto por rol
 */
$MODULOS_ESPECIALES = [
    'cuadrillas' => [
        'JefeCuadrilla' => 'solo_propia' // Solo puede ver su cuadrilla
    ],
    'reportes' => [
        'Gerente' => 'completo',
        'Coordinador ASSA' => 'estratega',
        'Administrativo ASSA' => 'operativo'
    ],
    'usuarios' => [
        'Gerente' => 'completo',
        'Coordinador ASSA' => 'completo' // Coordinador también administra usuarios
    ],
    'odt' => [
        'Inspector ASSA' => 'solo_lectura_carga_forzada'
    ]
];

/**
 * Verifica si el usuario tiene un modificador especial para un módulo
 * 
 * @param string $modulo Nombre del módulo
 * @param string $rol Rol del usuario
 * @return string|null Modificador especial o null
 */
function obtenerModificadorModulo($modulo, $rol)
{
    global $MODULOS_ESPECIALES;

    if (isset($MODULOS_ESPECIALES[$modulo][$rol])) {
        return $MODULOS_ESPECIALES[$modulo][$rol];
    }

    return null;
}

/**
 * Obtiene la lista de módulos visibles para un rol específico
 * 
 * @param string $rol Rol del usuario
 * @return array Lista de módulos permitidos
 */
function obtenerModulosVisibles($rol)
{
    global $PERMISOS;

    if (!isset($PERMISOS[$rol])) {
        return [];
    }

    // Si tiene wildcard, retornar todos los módulos conocidos
    if (in_array('*', $PERMISOS[$rol])) {
        return [
            'materiales',
            'proveedores',
            'tipos_trabajos',
            'stock',
            'movimientos',
            'stock_cuadrilla',
            'personal',
            'vehiculos',
            'cuadrillas',
            'herramientas',
            'odt',
            'programacion',
            'partes',
            'reportes',
            'usuarios',
            'tareas',
            'asistencia',
            'gastos'
        ];
    }

    return $PERMISOS[$rol];
}

/**
 * Estructura del menú de navegación con control de permisos
 * // ARCH: Centralización de rutas para Sidebar
 */
$MENU_ESTRUCTURA = [
    'dashboard' => [
        'titulo' => 'Principal',
        'icono' => 'fas fa-home',
        'items' => [
            ['modulo' => 'index', 'nombre' => 'Inicio', 'url' => '/APP-Prueba/index.php', 'icono' => 'fas fa-th-large'],
        ]
    ],
    'operativa' => [
        'titulo' => 'Operativa ASSA',
        'icono' => 'fas fa-tools',
        'items' => [
            ['modulo' => 'odt', 'nombre' => 'Gestión ODTs', 'url' => '/APP-Prueba/modules/odt/index.php', 'icono' => 'fas fa-clipboard-list'],
            ['modulo' => 'programacion', 'nombre' => 'Programación', 'url' => '/APP-Prueba/modules/programacion/index.php', 'icono' => 'fas fa-calendar-alt'],
            ['modulo' => 'partes', 'nombre' => 'Partes Diarios', 'url' => '/APP-Prueba/modules/partes/index.php', 'icono' => 'fas fa-hard-hat'],
        ]
    ],
    'maestros' => [
        'titulo' => 'Maestros',
        'icono' => 'fas fa-layer-group',
        'items' => [
            ['modulo' => 'materiales', 'nombre' => 'Materiales', 'url' => '/APP-Prueba/modules/materiales/index.php', 'icono' => 'fas fa-boxes'],
            ['modulo' => 'proveedores', 'nombre' => 'Proveedores', 'url' => '/APP-Prueba/modules/proveedores/index.php', 'icono' => 'fas fa-truck'],
            ['modulo' => 'tipos_trabajos', 'nombre' => 'Tipos de Trabajos', 'url' => '/APP-Prueba/modules/tipos_trabajos/index.php', 'icono' => 'fas fa-hard-hat']
        ]
    ],
    'logistica' => [
        'titulo' => 'Logística',
        'icono' => 'fas fa-truck-loading',
        'items' => [
            ['modulo' => 'stock', 'nombre' => 'Stock', 'url' => '/APP-Prueba/modules/stock/index.php', 'icono' => 'fas fa-warehouse'],
            ['modulo' => 'herramientas', 'nombre' => 'Herramientas', 'url' => '/APP-Prueba/modules/herramientas/index.php', 'icono' => 'fas fa-tools'],
            ['modulo' => 'combustibles', 'nombre' => 'Combustibles', 'url' => '/APP-Prueba/modules/combustibles/index.php', 'icono' => 'fas fa-gas-pump'],
            ['modulo' => 'movimientos', 'nombre' => 'Movimientos', 'url' => '/APP-Prueba/modules/movimientos/index.php', 'icono' => 'fas fa-exchange-alt']
        ]
    ],
    'gestion' => [
        'titulo' => 'Administración',
        'icono' => 'fas fa-users-cog',
        'items' => [
            ['modulo' => 'personal', 'nombre' => 'Personal', 'url' => '/APP-Prueba/modules/personal/index.php', 'icono' => 'fas fa-users'],
            ['modulo' => 'cuadrillas', 'nombre' => 'Cuadrillas', 'url' => '/APP-Prueba/modules/cuadrillas/index.php', 'icono' => 'fas fa-user-friends'],
            ['modulo' => 'vehiculos', 'nombre' => 'Vehículos', 'url' => '/APP-Prueba/modules/vehiculos/index.php', 'icono' => 'fas fa-car'],
            ['modulo' => 'usuarios', 'nombre' => 'Usuarios', 'url' => '/APP-Prueba/modules/usuarios/index.php', 'icono' => 'fas fa-user-shield'],
            ['modulo' => 'tareas', 'nombre' => 'Gestión de Tareas', 'url' => '/APP-Prueba/modules/tareas/index.php', 'icono' => 'fas fa-tasks'],
            ['modulo' => 'gastos', 'nombre' => 'Gastos y Caja Chica', 'url' => '/APP-Prueba/modules/gastos/index.php', 'icono' => 'fas fa-money-bill-wave']
        ]
    ],
    'reportes' => [
        'titulo' => 'Analytics',
        'icono' => 'fas fa-chart-bar',
        'items' => [
            ['modulo' => 'reportes', 'nombre' => 'Dashboard', 'url' => '/APP-Prueba/modules/reportes/index.php', 'icono' => 'fas fa-chart-line']
        ]
    ]
];

/**
 * Genera el menú de navegación filtrado por permisos del usuario
 * 
 * @param string $rol Rol del usuario actual
 * @return array Menú filtrado
 */
function generarMenuFiltrado($rol)
{
    global $MENU_ESTRUCTURA;

    $menuFiltrado = [];

    foreach ($MENU_ESTRUCTURA as $seccion => $datos) {
        $itemsFiltrados = [];

        foreach ($datos['items'] as $item) {
            // Caso especial para el index
            if ($item['modulo'] === 'index' || tienePermiso($item['modulo'])) {
                $itemsFiltrados[] = $item;
            }
        }

        // Solo incluir la sección si tiene items visibles
        if (!empty($itemsFiltrados)) {
            $menuFiltrado[$seccion] = [
                'titulo' => $datos['titulo'],
                'icono' => $datos['icono'],
                'items' => $itemsFiltrados
            ];
        }
    }

    return $menuFiltrado;
}
?>