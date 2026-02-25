<?php
/**
 * [!] ARCH: Módulo de Gestión de ODTs v2 — Vista Principal
 * [✓] AUDIT: Tabla con 14 estados, calendario, asignación, filtros avanzados
 * Roles: Gerente (full), Administrativo (full), JefeCuadrilla (hoy/mañana)
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../services/ODTService.php';
require_once '../../services/CrewService.php';
require_once '../../services/StateMachine.php';
require_once '../../services/PriorityUtil.php';
require_once '../../services/DateUtil.php';

// [✓] AUDIT: Verificar permisos
if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

// Rol y cuadrilla del usuario
$rolActual = $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '';
$idUsuario = $_SESSION['usuario_id'] ?? 0;
$idCuadrillaUsuario = $_SESSION['usuario_id_cuadrilla'] ?? null;
// Roles operativos ven columnas extra: Prioridad, Orden, F. Asignación
$esVistaOperativa = in_array($rolActual, ['JefeCuadrilla', 'Jefe de Cuadrilla', 'Inspector ASSA']);

// Si es JefeCuadrilla sin cuadrilla en sesión, buscar en DB
if ($rolActual === 'JefeCuadrilla' && !$idCuadrillaUsuario) {
    $stmt = $pdo->prepare("SELECT id_cuadrilla FROM personal WHERE id_personal = (SELECT id_personal FROM usuarios WHERE id_usuario = ?)");
    $stmt->execute([$idUsuario]);
    $idCuadrillaUsuario = $stmt->fetchColumn() ?: null;
}

// Inicializar servicios
$odtService = new ODTService($pdo);
$crewService = new CrewService($pdo);

// Obtener filtros desde GET
$filtros = [
    'estado' => $_GET['estado'] ?? '',
    'prioridad' => $_GET['prioridad'] ?? '',
    'cuadrilla' => $_GET['cuadrilla'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'urgente' => $_GET['urgente'] ?? '',
    'search' => $_GET['search'] ?? '',
    'vencimiento' => $_GET['vencimiento'] ?? '',
];

// Obtener ODTs
try {
    $odts = $odtService->listarConFiltros($filtros, $rolActual, $idCuadrillaUsuario ? (int) $idCuadrillaUsuario : null);
} catch (\Exception $e) {
    $odts = [];
    $errorMsg = $e->getMessage();
}

// Métricas
$metricas = $odtService->getMetricas($odts);

// Cuadrillas activas para dropdowns
$cuadrillas = $crewService->listarActivas();

// Tipos de trabajo para filtro
$tipos = $pdo->query("SELECT id_tipologia, nombre, codigo_trabajo FROM tipos_trabajos WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Colores de estados
$estadoColors = StateMachine::getStateColors();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<!-- [!] PWA-OFFLINE: Indicador de conexión -->
<div id="offlineIndicator" class="offline-indicator">
    📶 Sin conexión - Los cambios se guardarán localmente
</div>

<style>
    /* [!] ARCH: Override para que la tabla ODT ocupe el 95% del ancho de pantalla */
    .container {
        max-width: 95% !important;
        width: 95% !important;
    }

    /* Column Selector - Dark Mode (default) */
    #colSelectorBtn {
        background: var(--bg-secondary) !important;
        border: 1px solid rgba(100, 181, 246, 0.2) !important;
        color: var(--text-secondary) !important;
    }

    #colSelectorDropdown {
        background: linear-gradient(145deg, #1b263b 0%, #0d1b2a 100%) !important;
        border: 1px solid rgba(100, 181, 246, 0.2) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
    }

    #colSelectorDropdown label {
        color: var(--text-primary) !important;
    }

    #colSelectorDropdown label:hover {
        background: rgba(100, 181, 246, 0.1) !important;
    }

    #colSelectorDropdown .col-hint {
        color: var(--text-muted) !important;
        border-bottom: 1px solid rgba(100, 181, 246, 0.1) !important;
    }

    /* Column Selector - Light Mode */
    [data-theme="light"] #colSelectorBtn {
        background: #f8fafc !important;
        border: 1px solid #d1d5db !important;
        color: #4a5568 !important;
    }

    [data-theme="light"] #colSelectorDropdown {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    }

    [data-theme="light"] #colSelectorDropdown label {
        color: #1e293b !important;
    }

    [data-theme="light"] #colSelectorDropdown label:hover {
        background: rgba(37, 99, 235, 0.06) !important;
    }

    [data-theme="light"] #colSelectorDropdown .col-hint {
        color: #94a3b8 !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
</style>

<div class="container-fluid" style="padding: 0 10px;">

    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="margin: 0; font-size: 1.4em; color: var(--text-primary);">
                <i class="fas fa-clipboard-list" style="color: var(--accent-primary);"></i> Gestión de ODTs
            </h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary); font-size: 0.9em;">
                <?php echo ($rolActual === 'JefeCuadrilla')
                    ? 'Panel de Cuadrilla — Hoy y Mañana'
                    : 'Control Integral de Órdenes de Trabajo'; ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- PWA Badge -->
            <div id="pendingBadge" class="pending-badge" style="display: none;">
                📱 <span id="pendingCount">0</span> pendientes
            </div>

            <!-- Botón Calendario -->
            <?php if ($rolActual !== 'JefeCuadrilla'): ?>
                <a href="calendar.php" class="btn"
                    style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); color: var(--accent-primary); display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-calendar-alt"></i> Calendario
                </a>
            <?php endif; ?>

            <!-- Botón Panel Cuadrillas -->
            <?php if (in_array($rolActual, ['Gerente', 'Administrativo', 'Coordinador ASSA'])): ?>
                <a href="crew_panel.php" class="btn"
                    style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid #00bcd4; color: #00bcd4; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-users"></i> Cuadrillas
                </a>
            <?php endif; ?>

            <!-- Importar desde Excel -->
            <?php if (tienePermiso('odt') && $rolActual !== 'JefeCuadrilla'): ?>
                <a href="importar_odt.php" class="btn"
                    style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid var(--color-success); color: var(--color-success); display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-file-upload"></i> Importar
                </a>
            <?php endif; ?>

            <!-- Exportar a Excel -->
            <?php if (tienePermiso('odt')): ?>
                <button onclick="document.getElementById('exportModal').style.display='flex'" class="btn"
                    style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid #66bb6a; color: #66bb6a; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-file-download"></i> Exportar
                </button>
            <?php endif; ?>

            <!-- Nueva ODT -->
            <?php if (tienePermiso('odt') && $rolActual !== 'JefeCuadrilla'): ?>
                <a href="form.php" class="btn btn-primary"
                    style="min-height: 50px; min-width: 140px; display: flex; align-items: center; gap: 10px; font-weight: 600; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);">
                    <i class="fas fa-plus-circle" style="font-size: 1.2em;"></i> Nueva ODT
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Métricas -->
    <?php if ($rolActual !== 'JefeCuadrilla'): ?>
        <div class="metrics-row">
            <div class="metric-mini" onclick="setQuickFilter('estado', '')">
                <div class="metric-icon primary"><i class="fas fa-layer-group"></i></div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $metricas['total']; ?></span>
                    <span class="metric-lbl">Total</span>
                </div>
            </div>
            <?php
            // Métricas dinámicas por estado: mostrar solo los que tienen count > 0 o son clave
            $metricasEstado = [
                'Nuevo' => ['icon' => 'fas fa-plus-circle', 'class' => 'warning'],
                'Inspeccionar' => ['icon' => 'fas fa-search', 'class' => 'info'],
                'Priorizado' => ['icon' => 'fas fa-sort-amount-up', 'class' => 'danger'],
                'Programado' => ['icon' => 'fas fa-calendar-alt', 'class' => 'info'],
                'Asignado' => ['icon' => 'fas fa-user-check', 'class' => 'success'],
                'En ejecución' => ['icon' => 'fas fa-running', 'class' => 'success'],
                'Ejecutado' => ['icon' => 'fas fa-check-double', 'class' => 'success'],
                'Auditar' => ['icon' => 'fas fa-eye', 'class' => 'info'],
                'Precertificar' => ['icon' => 'fas fa-certificate', 'class' => 'info'],
            ];
            foreach ($metricasEstado as $estado => $cfg):
                $key = str_replace(' ', '_', strtolower($estado));
                $val = $metricas[$key] ?? 0;
                if ($val > 0):
                    ?>
                    <div class="metric-mini" onclick="setQuickFilter('estado', '<?php echo htmlspecialchars($estado); ?>')">
                        <div class="metric-icon <?php echo $cfg['class']; ?>"><i class="<?php echo $cfg['icon']; ?>"></i></div>
                        <div class="metric-content">
                            <span class="metric-val"><?php echo $val; ?></span>
                            <span class="metric-lbl"><?php echo $estado; ?></span>
                        </div>
                    </div>
                <?php endif; endforeach; ?>

            <?php if ($metricas['urgentes'] > 0): ?>
                <div class="metric-mini" onclick="setQuickFilter('urgente', '1')">
                    <div class="metric-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="metric-content">
                        <span class="metric-val"><?php echo $metricas['urgentes']; ?></span>
                        <span class="metric-lbl">Urgentes</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($metricas['proximas_vencer'] > 0): ?>
                <div class="metric-mini" onclick="setQuickFilter('vencimiento', 'proximas')">
                    <div class="metric-icon warning"><i class="fas fa-hourglass-half"></i></div>
                    <div class="metric-content">
                        <span class="metric-val"><?php echo $metricas['proximas_vencer']; ?></span>
                        <span class="metric-lbl">Por Vencer</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="card"
            style="padding: 15px; margin-bottom: 20px; border-left: 4px solid <?php echo $_GET['msg'] == 'saved' ? 'var(--color-success)' : 'var(--color-danger)'; ?>;
            background: var(--bg-secondary); color: var(--text-primary); display: flex; align-items: center; gap: 12px;">
            <?php
            if ($_GET['msg'] == 'saved')
                echo "<i class='fas fa-check-circle' style='color: var(--color-success)'></i> ODT guardada correctamente.";
            if ($_GET['msg'] == 'deleted')
                echo "<i class='fas fa-trash' style='color: var(--color-warning)'></i> ODT eliminada.";
            if ($_GET['msg'] == 'error')
                echo "<i class='fas fa-exclamation-circle' style='color: var(--color-danger)'></i> Error en la operación.";
            ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Búsqueda -->
            <div style="flex: 2; min-width: 220px;">
                <input type="text" id="searchInput" onkeyup="filterODTs()"
                    placeholder="🔍 Buscar por Nro ODT, Dirección..." class="filter-select" style="margin-bottom: 0;"
                    value="<?php echo htmlspecialchars($filtros['search']); ?>">
            </div>

            <!-- Estado -->
            <div style="flex: 1; min-width: 160px;">
                <select id="filterEstado" onchange="handleFilterChange()" class="filter-select">
                    <option value="">Estado: Todos</option>
                    <?php foreach (StateMachine::ESTADOS as $est): ?>
                        <option value="<?php echo htmlspecialchars($est); ?>" <?php echo ($filtros['estado'] === $est) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($est); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cuadrilla -->
            <div style="flex: 1; min-width: 160px;">
                <select id="filterCuadrilla" onchange="handleFilterChange()" class="filter-select">
                    <option value="">Cuadrilla: Todas</option>
                    <option value="SIN_ASIGNAR" <?php echo ($filtros['cuadrilla'] === 'SIN_ASIGNAR') ? 'selected' : ''; ?>>-- Sin Asignar --</option>
                    <?php foreach ($cuadrillas as $c): ?>
                        <option value="<?php echo $c['id_cuadrilla']; ?>" <?php echo ($filtros['cuadrilla'] == $c['id_cuadrilla']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Desde -->
            <div style="flex: 0.8; min-width: 130px;">
                <input type="date" id="filterDesde" onchange="filterODTs()" class="filter-select"
                    style="margin-bottom:0;" value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>"
                    title="Desde">
            </div>

            <!-- Hasta -->
            <div style="flex: 0.8; min-width: 130px;">
                <input type="date" id="filterHasta" onchange="filterODTs()" class="filter-select"
                    style="margin-bottom:0;" value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>"
                    title="Hasta">
            </div>

            <!-- Urgente -->
            <div style="flex: 0; min-width: 50px;">
                <button onclick="toggleUrgentFilter()" id="btnUrgente" class="btn"
                    style="min-height: 40px; padding: 0 12px; border: 1px solid var(--text-muted); color: var(--text-secondary); background: var(--bg-secondary); font-size: 0.85em;"
                    title="Filtrar urgentes">
                    🔴
                </button>
            </div>

            <!-- Limpiar filtros -->
            <div style="flex: 0; min-width: 50px;">
                <button onclick="limpiarFiltros()" class="btn"
                    style="min-height: 40px; padding: 0 12px; border: 1px solid var(--text-muted); color: var(--text-secondary); background: var(--bg-secondary); font-size: 0.85em;"
                    title="Limpiar todos los filtros">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Toolbar -->
    <div id="bulkActions" class="card"
        style="display: none; margin-bottom: 20px; padding: 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; border-radius: var(--border-radius-md);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-weight: bold; color: var(--accent-primary);">
                <i class="fas fa-check-double"></i> Seleccionados: <span id="selectedCount">0</span>
            </span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <select id="bulkCuadrilla" class="form-control-sm" style="min-width: 150px; padding: 8px;">
                <option value="">-- Cuadrilla --</option>
                <?php foreach ($cuadrillas as $c): ?>
                    <option value="<?php echo $c['id_cuadrilla']; ?>">
                        <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" id="bulkFecha" class="form-control-sm" value="<?php echo date('Y-m-d'); ?>"
                style="padding: 6px;">
            <input type="number" id="bulkOrden" class="form-control-sm" placeholder="Orden" min="1"
                style="width: 80px; padding: 6px;">
            <select id="bulkStatus" class="form-control-sm" style="min-width: 150px; padding: 8px;">
                <option value="">-- Estado --</option>
                <?php foreach (StateMachine::ESTADOS as $est): ?>
                    <option value="<?php echo htmlspecialchars($est); ?>"><?php echo htmlspecialchars($est); ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="applyUnifiedActions()" class="btn btn-primary" style="min-height: 40px;">
                <i class="fas fa-check-double"></i> Aplicar
            </button>
            <button onclick="eliminarSeleccionadas()" class="btn"
                style="min-height: 40px; background: rgba(239,68,68,0.15); border: 1px solid var(--color-danger); color: var(--color-danger); font-weight: 600;">
                <i class="fas fa-trash-alt"></i> Eliminar
            </button>
        </div>
    </div>

    <!-- Tabla ODTs (DESKTOP) -->
    <div class="card desktop-table" style="padding: 10px; overflow-x: auto; position: relative;">
        <!-- Column Selector -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 6px;">
            <div style="position: relative;" id="colSelectorWrap">
                <button onclick="toggleColSelector()" class="btn" id="colSelectorBtn"
                    style="min-height: 32px; padding: 0 10px; font-size: 0.8em; display: flex; align-items: center; gap: 5px; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-columns"></i> Columnas <i class="fas fa-caret-down" style="font-size: 0.75em;"></i>
                </button>
                <div id="colSelectorDropdown" style="display: none; position: absolute; right: 0; top: 36px;
                    border-radius: 8px; padding: 8px 0; min-width: 190px; z-index: 200;">
                    <div class="col-hint" style="padding: 5px 12px; font-size: 0.72em; margin-bottom: 4px;">
                        Mín. 3 columnas visibles
                    </div>
                    <div id="colSelectorItems"></div>
                </div>
            </div>
        </div>
        <table id="odtTable" style="width: 100%; border-collapse: collapse; font-size: 0.82em;">
            <thead>
                <tr style="background: var(--color-primary-dark); color: white;">
                    <th data-col="check" style="padding: 6px 4px; text-align: center; white-space: nowrap;">
                        <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()"
                            style="transform: scale(1.1);">
                    </th>
                    <th data-col="nro_odt" onclick="sortTable('nro_odt')"
                        style="padding: 6px 5px; text-align: left; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">Nº ODT <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i></th>
                    <th data-col="direccion" onclick="sortTable('direccion')"
                        style="padding: 6px 5px; text-align: left; cursor:pointer;" title="Ordenar">Dirección <i
                            class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i></th>
                    <th data-col="tipo" onclick="sortTable('tipo')"
                        style="padding: 6px 5px; text-align: left; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">Tipo <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i></th>
                    <th data-col="estado" onclick="sortTable('estado')"
                        style="padding: 6px 5px; text-align: center; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">Estado <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i></th>
                    <th data-col="prioridad" onclick="sortTable('prioridad')"
                        style="padding: 6px 5px; text-align: center; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">Prior. <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i>
                    </th>
                    <th data-col="orden" onclick="sortTable('orden')"
                        style="padding: 6px 3px; text-align: center; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">Ord <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i></th>
                    <th data-col="vencimiento" style="padding: 6px 5px; text-align: center; white-space: nowrap;">Venc.
                    </th>
                    <th data-col="fecha_asig" onclick="sortTable('fecha_asig')"
                        style="padding: 6px 5px; text-align: center; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">F. Asign. <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i></th>
                    <th data-col="cuadrilla" onclick="sortTable('cuadrilla')"
                        style="padding: 6px 5px; text-align: left; white-space: nowrap; cursor:pointer;"
                        title="Ordenar">Cuadrilla <i class="fas fa-sort" style="font-size:0.7em; opacity:0.5;"></i>
                    </th>
                    <th data-col="acciones" style="padding: 6px 4px; text-align: center; white-space: nowrap;">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="odtTableBody">
                <?php if (empty($odts)): ?>
                    <tr id="emptyRow">
                        <td colspan="11" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-clipboard" style="font-size: 3em; margin-bottom: 15px; display: block;"></i>
                            No hay ODTs registradas
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($odts as $o):
                        // Vencimiento badge
                        $diasVenc = DateUtil::diasHastaVencimiento($o['fecha_vencimiento'] ?? null);
                        $nivelAlerta = DateUtil::nivelAlertaVencimiento($o['fecha_vencimiento'] ?? null);

                        // Estado color
                        $ec = $estadoColors[$o['estado_gestion']] ?? ['bg' => '#eee', 'color' => '#666', 'icon' => 'fas fa-circle'];

                        // Row style por cuadrilla
                        $rowStyle = "border-bottom: 1px solid var(--border-color);";
                        if (!empty($o['id_cuadrilla']) && !empty($o['color_hex'])) {
                            $hex = $o['color_hex'];
                            list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
                            $rowStyle = "background-color: rgba($r,$g,$b,0.12) !important; border-left: 4px solid {$hex} !important; border-bottom: 1px solid var(--border-color);";
                        }

                        $esUrgente = PriorityUtil::esUrgente($o);
                        ?>
                        <tr class="odt-row"
                            data-search="<?php echo strtolower($o['nro_odt_assa'] . ' ' . $o['direccion'] . ' ' . ($o['nombre_cuadrilla'] ?? '')); ?>"
                            data-estado="<?php echo $o['estado_gestion']; ?>" data-prioridad="<?php echo $o['prioridad']; ?>"
                            data-urgente="<?php echo $esUrgente ? '1' : '0'; ?>" data-vencimiento="<?php echo $nivelAlerta; ?>"
                            data-cuadrilla="<?php echo htmlspecialchars($o['nombre_cuadrilla'] ?? 'SIN_ASIGNAR'); ?>"
                            data-fecha="<?php echo $o['fecha_asignacion'] ?? ''; ?>" style="<?php echo $rowStyle; ?>">

                            <td data-col="check" style="padding: 6px 4px; text-align: center;">
                                <input type="checkbox" class="odt-checkbox" value="<?php echo $o['id_odt']; ?>"
                                    onclick="updateBulkActionUI()" style="transform: scale(1.1);">
                            </td>

                            <td data-col="nro_odt" style="padding: 6px 5px; white-space: nowrap;">
                                <strong><?php echo htmlspecialchars($o['nro_odt_assa']); ?></strong>
                                <?php if ($esUrgente): ?>
                                    <i class="fas fa-bolt" style="color: #d32f2f; font-size: 0.75em; margin-left: 3px;"
                                        title="Urgente"></i>
                                <?php endif; ?>
                            </td>

                            <td data-col="direccion"
                                style="padding: 6px 5px; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($o['direccion'] ?: '-'); ?>
                            </td>

                            <td data-col="tipo" style="padding: 6px 5px; white-space: nowrap;">
                                <span class="work-pill">
                                    <?php echo $o['codigo_trabajo'] ? "<strong>[{$o['codigo_trabajo']}]</strong> " : ""; ?>
                                    <?php echo htmlspecialchars($o['tipo_trabajo'] ?: '-'); ?>
                                </span>
                            </td>

                            <td data-col="estado" style="padding: 6px 4px; text-align: center; white-space: nowrap;">
                                <span class="status-pill"
                                    style="background: <?php echo $ec['bg']; ?>; color: <?php echo $ec['color']; ?>; font-size: 0.72em; white-space: nowrap;">
                                    <?php echo $o['estado_gestion']; ?>
                                </span>
                            </td>

                            <td data-col="prioridad" style="padding: 6px 4px; text-align: center; white-space: nowrap;">
                                <?php echo PriorityUtil::renderBadge((int) $o['prioridad'], (bool) $o['urgente_flag']); ?>
                            </td>

                            <td data-col="orden"
                                style="padding: 6px 3px; text-align: center; font-weight: 600; color: var(--text-secondary); white-space: nowrap;">
                                <?php echo $o['orden'] ? $o['orden'] : '-'; ?>
                            </td>

                            <td data-col="vencimiento" style="padding: 6px 4px; text-align: center; white-space: nowrap;">
                                <?php if ($o['fecha_vencimiento']):
                                    $vencColors = [
                                        'vencida' => ['bg' => '#ffebee', 'color' => '#d32f2f', 'label' => 'Vencida'],
                                        'critica' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => $diasVenc . 'd'],
                                        'proxima' => ['bg' => '#fff8e1', 'color' => '#f57c00', 'label' => $diasVenc . 'd'],
                                        'normal' => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'label' => $diasVenc . 'd'],
                                    ];
                                    $vc = $vencColors[$nivelAlerta] ?? $vencColors['normal'];
                                    ?>
                                    <span style="background: <?php echo $vc['bg']; ?>; color: <?php echo $vc['color']; ?>;
                                padding: 1px 5px; border-radius: 8px; font-size: 0.72em; font-weight: 600;">
                                        <?php echo $vc['label']; ?>
                                    </span>
                                    <div style="font-size: 0.62em; color: var(--text-muted); margin-top: 1px;">
                                        <?php echo DateUtil::formatear($o['fecha_vencimiento']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>

                            <td data-col="fecha_asig"
                                style="padding: 6px 4px; text-align: center; font-size: 0.9em; white-space: nowrap;">
                                <?php echo $o['fecha_asignacion'] ? DateUtil::formatear($o['fecha_asignacion']) : '-'; ?>
                            </td>

                            <td data-col="cuadrilla" style="padding: 6px 5px; white-space: nowrap;">
                                <?php if (!empty($o['nombre_cuadrilla'])): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 3px;">
                                        <span
                                            style="width: 7px; height: 7px; border-radius: 50%;
                                    background: <?php echo $o['color_hex'] ?? '#2196F3'; ?>; display: inline-block; flex-shrink: 0;"></span>
                                        <?php echo htmlspecialchars($o['nombre_cuadrilla']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">Sin asignar</span>
                                <?php endif; ?>
                            </td>

                            <td data-col="acciones" style="padding: 6px 3px; text-align: center; white-space: nowrap;">
                                <div style="display: flex; gap: 3px; justify-content: center; flex-wrap: nowrap;">
                                    <!-- Editar -->
                                    <a href="form.php?id=<?php echo $o['id_odt']; ?>" class="btn btn-outline"
                                        style="min-height: 28px; min-width: 28px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.8em;"
                                        title="Editar"><i class="fas fa-edit"></i></a>

                                    <!-- Asignar -->
                                    <?php if ($rolActual !== 'JefeCuadrilla'): ?>
                                        <button
                                            onclick="openAssignModal(<?php echo $o['id_odt']; ?>, '<?php echo addslashes($o['nro_odt_assa']); ?>')"
                                            class="btn"
                                            style="min-height: 28px; min-width: 28px; padding: 0; background: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb; display: flex; align-items: center; justify-content: center; font-size: 0.8em;"
                                            title="Asignar Cuadrilla"><i class="fas fa-user-plus"></i></button>
                                    <?php endif; ?>

                                    <!-- Toggle Urgente -->
                                    <?php if ($rolActual !== 'JefeCuadrilla'): ?>
                                        <button onclick="toggleUrgent(<?php echo $o['id_odt']; ?>)" class="btn" style="min-height: 28px; min-width: 28px; padding: 0;
                                    background: <?php echo $esUrgente ? '#ffebee' : 'var(--bg-secondary)'; ?>;
                                    color: <?php echo $esUrgente ? '#d32f2f' : 'var(--text-muted)'; ?>;
                                    border: 1px solid <?php echo $esUrgente ? '#ffcdd2' : 'var(--border-color)'; ?>;
                                    display: flex; align-items: center; justify-content: center; font-size: 0.8em;"
                                            title="<?php echo $esUrgente ? 'Quitar urgencia' : 'Marcar urgente'; ?>">
                                            <i class="fas fa-bolt"></i></button>
                                    <?php endif; ?>

                                    <!-- Eliminar -->
                                    <button
                                        onclick="confirmDelete(<?php echo $o['id_odt']; ?>, '<?php echo addslashes($o['nro_odt_assa']); ?>')"
                                        class="btn"
                                        style="min-height: 28px; min-width: 28px; padding: 0; background: #fee; color: #d32f2f; border: 1px solid #fcc; display: flex; align-items: center; justify-content: center; font-size: 0.8em;"
                                        title="Eliminar"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="mobile-cards" id="mobileCardsContainer">
        <?php if (empty($odts)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-clipboard" style="font-size: 3em; margin-bottom: 15px; display: block;"></i>
                No hay ODTs registradas
            </div>
        <?php else: ?>
            <?php foreach ($odts as $o):
                $ec = $estadoColors[$o['estado_gestion']] ?? ['bg' => '#eee', 'color' => '#666'];
                $esUrgente = PriorityUtil::esUrgente($o);
                $nivelAlertaMobile = DateUtil::nivelAlertaVencimiento($o['fecha_vencimiento'] ?? null);
                ?>
                <div class="mobile-odt-card <?php echo $esUrgente ? 'urgente' : ''; ?>"
                    data-search="<?php echo strtolower($o['nro_odt_assa'] . ' ' . $o['direccion']); ?>"
                    data-estado="<?php echo $o['estado_gestion']; ?>" data-urgente="<?php echo $esUrgente ? '1' : '0'; ?>"
                    data-vencimiento="<?php echo $nivelAlertaMobile; ?>">

                    <div class="mobile-odt-header">
                        <div class="mobile-odt-nro">
                            <?php echo $esUrgente ? '🔴 ' : ''; ?>
                            <?php echo htmlspecialchars($o['nro_odt_assa']); ?>
                        </div>
                        <span class="mobile-odt-estado"
                            style="background: <?php echo $ec['bg']; ?>; color: <?php echo $ec['color']; ?>;">
                            <?php echo $o['estado_gestion']; ?>
                        </span>
                    </div>

                    <div class="mobile-odt-direccion">
                        📍 <?php echo htmlspecialchars($o['direccion'] ?: 'Sin dirección'); ?>
                    </div>

                    <div class="mobile-odt-meta">
                        <?php if ($o['tipo_trabajo']): ?>
                            <span><i class="fas fa-tools"></i> <?php echo htmlspecialchars($o['tipo_trabajo']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($o['nombre_cuadrilla'])): ?>
                            <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($o['nombre_cuadrilla']); ?></span>
                        <?php endif; ?>
                        <?php if ($o['fecha_asignacion']): ?>
                            <span><i class="fas fa-calendar-check"></i>
                                <?php echo DateUtil::formatear($o['fecha_asignacion']); ?></span>
                        <?php endif; ?>
                        <?php if ($o['orden']): ?>
                            <span><i class="fas fa-sort-numeric-up"></i> Orden: <?php echo $o['orden']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="mobile-odt-actions">
                        <a href="form.php?id=<?php echo $o['id_odt']; ?>" class="btn btn-outline" style="min-height: 44px;">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <?php if ($rolActual !== 'JefeCuadrilla'): ?>
                            <button
                                onclick="openAssignModal(<?php echo $o['id_odt']; ?>, '<?php echo addslashes($o['nro_odt_assa']); ?>')"
                                class="btn"
                                style="min-height: 44px; background: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb;">
                                <i class="fas fa-user-plus"></i> Asignar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="noResults" style="display:none; padding: 30px; text-align: center; color: var(--text-muted);">
        No se encontraron ODTs con los filtros aplicados.
    </div>
</div>

<!-- Modal de Asignación a Cuadrilla -->
<div id="assignModal" class="error-modal-overlay" style="display: none;">
    <div class="error-modal-content" style="max-width: 450px;">
        <div class="error-modal-header"
            style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-color);">
            <span style="color: var(--text-primary);"><i class="fas fa-user-plus"></i> Asignar ODT a Cuadrilla</span>
            <button onclick="closeAssignModal()" class="error-modal-close">&times;</button>
        </div>
        <div class="error-modal-body" style="padding: 20px;">
            <input type="hidden" id="assignOdtId">
            <p style="margin-bottom: 15px; color: var(--text-secondary);">
                ODT: <strong id="assignOdtNro"></strong>
            </p>

            <div style="margin-bottom: 15px;">
                <label
                    style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Cuadrilla
                    *</label>
                <select id="assignCuadrilla" class="filter-select" style="width: 100%;" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($cuadrillas as $c): ?>
                        <option value="<?php echo $c['id_cuadrilla']; ?>">
                            <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Fecha
                    de Asignación *</label>
                <input type="date" id="assignFecha" class="filter-select" style="width: 100%;"
                    value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Orden
                    de Ejecución *</label>
                <input type="number" id="assignOrden" class="filter-select" style="width: 100%;" min="1" value="1"
                    required>
            </div>
        </div>
        <div class="error-modal-footer" style="padding: 15px; display: flex; gap: 10px; justify-content: flex-end;">
            <button onclick="closeAssignModal()" class="btn" style="min-height: 40px;">Cancelar</button>
            <button onclick="submitAssignment()" class="btn btn-primary" style="min-height: 40px;">
                <i class="fas fa-check"></i> Asignar
            </button>
        </div>
    </div>
</div>

<style>
    /* Prevenir overflow */
    * {
        box-sizing: border-box;
    }

    html,
    body {
        overflow-x: hidden;
        width: 100%;
        max-width: 100vw;
    }

    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
    }

    /* PWA Offline */
    .offline-indicator {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        padding: 10px;
        background: #f39c12;
        color: white;
        text-align: center;
        font-weight: bold;
        z-index: 9999;
        display: none;
    }

    body.offline .offline-indicator {
        display: block;
    }

    body.offline {
        padding-top: 45px;
    }

    .pending-badge {
        background: #d32f2f;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
    }

    .work-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 0.78em;
        font-weight: 500;
        border: 1px solid var(--border-color);
        white-space: normal;
        max-width: 200px;
        line-height: 1.3;
    }

    .status-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* Responsive */
    .desktop-table {
        display: block;
    }

    .mobile-cards {
        display: none;
    }

    @media (max-width: 1024px) {
        .desktop-table {
            display: none;
        }

        .mobile-cards {
            display: block;
        }
    }

    .mobile-odt-card {
        background: var(--bg-secondary);
        border-radius: var(--border-radius-md);
        padding: 15px;
        margin-bottom: 12px;
        border: 1px solid var(--border-color);
        transition: box-shadow 0.2s;
    }

    .mobile-odt-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .mobile-odt-card.urgente {
        border-left: 4px solid #d32f2f;
    }

    .mobile-odt-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .mobile-odt-nro {
        font-weight: 700;
        font-size: 1.05em;
        color: var(--text-primary);
    }

    .mobile-odt-estado {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.75em;
        font-weight: 600;
    }

    .mobile-odt-direccion {
        font-size: 0.9em;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }

    .mobile-odt-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 0.8em;
        color: var(--text-muted);
        margin-bottom: 10px;
    }

    .mobile-odt-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .mobile-odt-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Modal overlay */
    .error-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }

    .error-modal-content {
        background: var(--bg-primary);
        border-radius: var(--border-radius-md);
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    }

    .error-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }

    .error-modal-close {
        background: none;
        border: none;
        font-size: 1.5em;
        cursor: pointer;
        color: var(--text-muted);
        line-height: 1;
    }

    .error-modal-body {
        padding: 20px;
    }

    .error-modal-footer {
        padding: 15px 20px;
        border-top: 1px solid var(--border-color);
    }

    .error-stack-pre {
        background: var(--bg-secondary);
        padding: 10px;
        border-radius: 6px;
        font-size: 0.75em;
        overflow-x: auto;
        max-height: 200px;
    }

    /* Metrics */
    .metrics-row {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .metric-mini {
        background: var(--bg-secondary);
        border-radius: var(--border-radius-md);
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        border: 1px solid var(--border-color);
        transition: transform 0.2s, box-shadow 0.2s;
        min-width: 100px;
    }

    .metric-mini:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .metric-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9em;
    }

    .metric-icon.primary {
        background: rgba(33, 150, 243, 0.15);
        color: #2196F3;
    }

    .metric-icon.success {
        background: rgba(76, 175, 80, 0.15);
        color: #4CAF50;
    }

    .metric-icon.warning {
        background: rgba(255, 152, 0, 0.15);
        color: #FF9800;
    }

    .metric-icon.info {
        background: rgba(0, 188, 212, 0.15);
        color: #00BCD4;
    }

    .metric-icon.danger {
        background: rgba(244, 67, 54, 0.15);
        color: #F44336;
    }

    .metric-content {
        display: flex;
        flex-direction: column;
    }

    .metric-val {
        font-weight: 700;
        font-size: 1.1em;
        color: var(--text-primary);
    }

    .metric-lbl {
        font-size: 0.7em;
        color: var(--text-muted);
        white-space: nowrap;
    }

    /* Filter select */
    .filter-select {
        padding: 8px 12px;
        border-radius: var(--border-radius-md);
        border: 1px solid var(--border-color);
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 0.9em;
        width: 100%;
    }

    .odt-row {
        transition: background-color 0.15s;
    }

    .odt-row:hover {
        filter: brightness(0.95);
    }
</style>

<script>
    // CSRF Token
    const CSRF_TOKEN = '<?php echo $csrfToken; ?>';

    // ── Filtros ──
    let activeQuickFilter = { field: null, value: nu        ll };

    function filterODTs() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const estado = document.getElementById('filterEstado').value;
        const desde = document.getElementById('filterDesde').value;
        const hasta = document.getElementById('filterHasta').value;
        const filterUrgente = activeQuickFilter.field === 'urgente';
        const filterVencimiento = activeQuickFilter.field === 'vencimiento';

        document.querySelectorAll('.odt-row, .mobile-odt-card').forEach(el => {
            const matchSearch = !search || el.dataset.search.includes(search);
            const matchEstado = !estado || el.dataset.estado === estado;
            const matchUrgente = !filterUrgente || el.dataset.urgente === '1';
            const matchVenc = !filterVencimiento || (el.dataset.vencimiento === 'vencida' || el.dataset.vencimiento === 'critica' || el.dataset.vencimiento === 'proxima');
            const fecha = el.dataset.fecha || '';
            const matchDesde = !desde || fecha >= desde;
            const matchHasta = !hasta || fecha <= hasta;
            el.style.display = (matchSearch && matchEstado && matchUrgente && matchVenc && matchDesde && matchHasta) ? '' : 'none';
        });

        const visible = document.querySelectorAll('.odt-row:not([style*="display: none"])').length;
        const noResults = document.getElementById('noResults');
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    function handleFilterChange() {
        filterODTs();
    }

    function limpiarFiltros() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterEstado').value = '';
        document.getElementById('filterDesde').value = '';
        document.getElementById('filterHasta').value = '';
        activeQuickFilter = { field: null, value: null };
        updateMetricHighlights();
        filterODTs();
    }

    function setQuickFilter(field, value) {
        // Toggle: click again to clear
        if (activeQuickFilter.field === field && activeQuickFilter.value === value) {
            activeQuickFilter = { field: null, value: null };
        } else {
            activeQuickFilter = { field, value };
        }
        // For estado, also set the dropdown
        if (field === 'estado') {
            document.getElementById('filterEstado').value = activeQuickFilter.field === 'estado' ? value : '';
        }
        // Visual: highlight active metric card
        updateMetricHighlights();
        filterODTs();
    }

    function updateMetricHighlights() {
        document.querySelectorAll('.metric-mini[onclick]').forEach(card => {
            const onclick = card.getAttribute('onclick') || '';
            const isActive = activeQuickFilter.field &&
                onclick.includes("'" + activeQuickFilter.field + "'") &&
                onclick.includes("'" + activeQuickFilter.value + "'");
            card.style.outline = isActive ? '2px solid var(--accent-primary, #64b5f6)' : '';
            card.style.outlineOffset = isActive ? '2px' : '';
            card.style.transform = isActive ? 'translateY(-2px)' : '';
        });
    }

    function toggleUrgentFilter() {
        setQuickFilter('urgente', '1');
    }

    // ── Bulk Actions ──
    function toggleAllCheckboxes() {
        const all = document.getElementById('selectAll').checked;
        document.querySelectorAll('.odt-checkbox').forEach(cb => {
            if (cb.closest('.odt-row').style.display !== 'none') {
                cb.checked = all;
            }
        });
        updateBulkActionUI();
    }

    function updateBulkActionUI() {
        const checked = document.querySelectorAll('.odt-checkbox:checked');
        const bar = document.getElementById('bulkActions');
        document.getElementById('selectedCount').textContent = checked.length;
        bar.style.display = checked.length > 0 ? 'flex' : 'none';
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.odt-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    function applyUnifiedActions() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;

        const payload = {
            action: 'unified_update',
            ids: ids,
            cuadrilla: document.getElementById('bulkCuadrilla').value,
            fecha: document.getElementById('bulkFecha').value,
            orden: document.getElementById('bulkOrden')?.value || '',
            estado: document.getElementById('bulkStatus').value,
            csrf: CSRF_TOKEN
        };

        if (!payload.cuadrilla && !payload.estado) {
            alert('Seleccioná al menos una cuadrilla o un estado para aplicar.');
            return;
        }

        fetch('bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo aplicar'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de red al aplicar cambios.');
            });
    }

    function eliminarSeleccionadas() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;

        if (!confirm(`¿Está seguro de ELIMINAR ${ids.length} ODT(s)?\n\nEsta acción no se puede deshacer.`)) return;

        fetch('bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'bulk_delete', ids: ids, csrf: CSRF_TOKEN })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo eliminar'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de red al eliminar ODTs.');
            });
    }

    // ── Asignación Modal ──
    function openAssignModal(odtId, odtNro) {
        document.getElementById('assignOdtId').value = odtId;
        document.getElementById('assignOdtNro').textContent = odtNro;
        document.getElementById('assignModal').style.display = 'flex';
    }

    function closeAssignModal() {
        document.getElementById('assignModal').style.display = 'none';
    }

    function submitAssignment() {
        const idOdt = document.getElementById('assignOdtId').value;
        const cuadrilla = document.getElementById('assignCuadrilla').value;
        const fecha = document.getElementById('assignFecha').value;
        const orden = document.getElementById('assignOrden').value;

        if (!cuadrilla || !fecha || !orden) {
            alert('Todos los campos son obligatorios: Cuadrilla, Fecha y Orden.');
            return;
        }

        fetch('bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'unified_update',
                ids: [parseInt(idOdt)],
                cuadrilla: cuadrilla,
                fecha: fecha,
                orden: parseInt(orden),
                estado: 'Asignado',
                csrf: CSRF_TOKEN
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeAssignModal();
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo asignar'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de red al asignar.');
            });
    }

    // ── Toggle Urgente ──
    function toggleUrgent(odtId) {
        fetch('bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_urgent',
                ids: [odtId],
                csrf: CSRF_TOKEN
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || ''));
                }
            });
    }

    // ── Eliminar ──
    function confirmDelete(id, nro) {
        if (confirm('¿Eliminar ODT ' + nro + '? Esta acción no se puede deshacer.')) {
            window.location = 'delete.php?id=' + id;
        }
    }

    // ── Status update (para acciones rápidas) ──
    function updateOdtStatus(odtId, nuevoEstado) {
        fetch('bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'unified_update',
                ids: [odtId],
                estado: nuevoEstado,
                csrf: CSRF_TOKEN
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + (data.message || ''));
            });
    }

    // ── Column Selector ──
    const COL_CONFIG = [
        { key: 'check', label: 'Selección' },
        { key: 'nro_odt', label: 'Nº ODT', fixed: true },
        { key: 'direccion', label: 'Dirección', fixed: true },
        { key: 'tipo', label: 'Tipo Trabajo' },
        { key: 'estado', label: 'Estado' },
        { key: 'prioridad', label: 'Prioridad' },
        { key: 'orden', label: 'Orden' },
        { key: 'vencimiento', label: 'Vencimiento' },
        { key: 'fecha_asig', label: 'F. Asignación' },
        { key: 'cuadrilla', label: 'Cuadrilla' },
        { key: 'acciones', label: 'Acciones' }
    ];
    const COL_STORAGE_KEY = 'odt_visible_cols_' + (document.body.dataset.role || 'default');
    const MIN_VISIBLE_COLS = 0; // solo las 2 fixed (Nº ODT, Dirección) son obligatorias

    // Default visible columns per role (non-operative hides prioridad, orden, fecha_asig)
    const DEFAULT_HIDDEN = <?php echo json_encode(
        $esVistaOperativa ? [] : ['prioridad', 'orden', 'fecha_asig']
    ); ?>;

    function getVisibleCols() {
        const saved = localStorage.getItem(COL_STORAGE_KEY);
        if (saved) {
            try { return JSON.parse(saved); } catch (e) { }
        }
        // Default: all except DEFAULT_HIDDEN
        return COL_CONFIG.filter(c => !DEFAULT_HIDDEN.includes(c.key)).map(c => c.key);
    }

    function saveVisibleCols(cols) {
        localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(cols));
    }

    function applyColumnVisibility() {
        const visible = getVisibleCols();
        const style = document.getElementById('colVisibilityStyle');
        let css = '';
        COL_CONFIG.forEach(c => {
            if (!visible.includes(c.key)) {
                css += `[data-col="${c.key}"] { display: none !important; }\n`;
            }
        });
        style.textContent = css;
    }

    function buildColSelectorItems() {
        const container = document.getElementById('colSelectorItems');
        if (!container) return;
        const visible = getVisibleCols();
        const toggleable = COL_CONFIG.filter(c => !c.fixed);
        const visibleToggleable = toggleable.filter(c => visible.includes(c.key)).length;

        container.innerHTML = '';
        COL_CONFIG.forEach(c => {
            const isVisible = visible.includes(c.key);
            const isFixed = !!c.fixed;
            const canUncheck = !isFixed && (visibleToggleable > MIN_VISIBLE_COLS || !isVisible);

            const item = document.createElement('label');
            item.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 6px 14px; cursor: pointer; font-size: 0.82em; transition: background 0.15s;';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = isFixed || isVisible;
            cb.disabled = isFixed || (isVisible && !canUncheck);
            cb.style.cssText = 'transform: scale(1.1); accent-color: var(--accent-primary, #64b5f6);';
            cb.onchange = () => {
                let cols = getVisibleCols();
                if (cb.checked) {
                    if (!cols.includes(c.key)) cols.push(c.key);
                } else {
                    cols = cols.filter(k => k !== c.key);
                }
                saveVisibleCols(cols);
                applyColumnVisibility();
                buildColSelectorItems(); // Rebuild to update disabled state
            };

            const span = document.createElement('span');
            span.textContent = c.label;

            item.appendChild(cb);
            item.appendChild(span);
            container.appendChild(item);
        });
    }

    function toggleColSelector() {
        const dd = document.getElementById('colSelectorDropdown');
        const isOpen = dd.style.display !== 'none';
        dd.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) buildColSelectorItems();
    }

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        const wrap = document.getElementById('colSelectorWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('colSelectorDropdown').style.display = 'none';
        }
    });

    // Init: inject style tag and apply
    (function initColumnSelector() {
        if (!document.getElementById('colVisibilityStyle')) {
            const s = document.createElement('style');
            s.id = 'colVisibilityStyle';
            document.head.appendChild(s);
        }
        applyColumnVisibility();
    })();

    // ── Online/Offline detection ──
    function updateOnlineStatus() {
        document.body.classList.toggle('offline', !navigator.onLine);
    }
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
</script>

<script>
// ── Sorting (global scope for onclick access) ──
let currentSort = { col: null, asc: true };

function sortTable(colKey) {
    const tbody = document.getElementById('odtTableBody');
    const rows = Array.from(tbody.querySelectorAll('.odt-row'));
    if (rows.length === 0) return;

    if (currentSort.col === colKey) {
        currentSort.asc = !currentSort.asc;
    } else {
        currentSort.col = colKey;
        currentSort.asc = true;
    }

    const colMap = { nro_odt: 1, direccion: 2, tipo: 3, estado: 4, prioridad: 5, orden: 6, vencimiento: 7, fecha_asig: 8, cuadrilla: 9 };
    const idx = colMap[colKey];
    if (idx === undefined) return;

    rows.sort((a, b) => {
        const aText = (a.cells[idx]?.textContent || '').trim().toLowerCase();
        const bText = (b.cells[idx]?.textContent || '').trim().toLowerCase();
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        let cmp;
        if (!isNaN(aNum) && !isNaN(bNum)) {
            cmp = aNum - bNum;
        } else {
            cmp = aText.localeCompare(bText, 'es');
        }
        return currentSort.asc ? cmp : -cmp;
    });

    rows.forEach(r => tbody.appendChild(r));

    // Update sort icons
    document.querySelectorAll('#odtTable thead th[onclick] i').forEach(icon => {
        icon.className = 'fas fa-sort';
        icon.style.opacity = '0.5';
    });
    const activeHeader = document.querySelector('#odtTable thead th[onclick*="' + colKey + '"] i');
    if (activeHeader) {
        activeHeader.className = currentSort.asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
        activeHeader.style.opacity = '1';
    }
}
</script>

<!-- ═══════ MODAL: Exportar a Excel ═══════ -->
<div id="exportModal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(3px);"
    onclick="if(event.target===this)this.style.display='none'">
    <div
        style="background:var(--bg-card); border-radius:var(--border-radius-lg); padding:28px; width:92%; max-width:580px; box-shadow:0 12px 40px rgba(0,0,0,0.4); border:1px solid var(--border-color); max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; color:var(--text-primary); font-size:1.15em;">
                <i class="fas fa-file-download" style="color:#66bb6a;"></i> Exportar ODTs a Excel
            </h3>
            <button onclick="document.getElementById('exportModal').style.display='none'"
                style="background:none; border:none; color:var(--text-muted); font-size:1.3em; cursor:pointer;">&times;</button>
        </div>
        <p style="color:var(--text-secondary); font-size:0.85em; margin:0 0 6px;">Seleccioná columnas y opcionalmente
            filtrá cada una:</p>
        <div style="display:flex; gap:8px; margin-bottom:12px;">
            <button onclick="toggleExportCols(true)" class="btn btn-outline"
                style="font-size:0.78em; padding:4px 10px;">Todas</button>
            <button onclick="toggleExportCols(false)" class="btn btn-outline"
                style="font-size:0.78em; padding:4px 10px;">Ninguna</button>
        </div>
        <div
            style="background:var(--bg-secondary); border-radius:var(--border-radius-md); border:1px solid var(--border-color); padding:6px 8px; max-height:380px; overflow-y:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.82em;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <th style="text-align:left; padding:5px; color:var(--text-secondary); width:40%;">Columna</th>
                        <th style="text-align:left; padding:5px; color:var(--text-secondary);">Filtro (opcional)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $exportableCols = [
                        'nro_odt_assa' => ['label' => 'Nº ODT', 'filter' => 'text'],
                        'direccion' => ['label' => 'Dirección', 'filter' => 'text'],
                        'tipo_trabajo' => ['label' => 'Tipo de Trabajo', 'filter' => 'text'],
                        'codigo_trabajo' => ['label' => 'Código Trabajo', 'filter' => 'text'],
                        'estado_gestion' => ['label' => 'Estado', 'filter' => 'select_estado'],
                        'prioridad' => ['label' => 'Prioridad', 'filter' => 'select_prioridad'],
                        'orden' => ['label' => 'Orden', 'filter' => 'text'],
                        'nombre_cuadrilla' => ['label' => 'Cuadrilla', 'filter' => 'select_cuadrilla'],
                        'fecha_asignacion' => ['label' => 'F. Asignación', 'filter' => 'daterange'],
                        'fecha_vencimiento' => ['label' => 'F. Vencimiento', 'filter' => 'daterange'],
                        'urgente_flag' => ['label' => 'Urgente', 'filter' => 'select_sn'],
                        'fecha_creacion' => ['label' => 'F. Creación', 'filter' => 'daterange'],
                        'observaciones' => ['label' => 'Observaciones', 'filter' => 'text'],
                    ];
                    foreach ($exportableCols as $key => $cfg):
                        $label = $cfg['label'];
                        $filterType = $cfg['filter'];
                        ?>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <td style="padding:5px;">
                                <label
                                    style="display:flex; align-items:center; gap:5px; cursor:pointer; color:var(--text-primary); white-space:nowrap;">
                                    <input type="checkbox" class="export-col-cb" value="<?= $key ?>" checked
                                        style="transform:scale(1.05);">
                                    <?= $label ?>
                                </label>
                            </td>
                            <td style="padding:4px 5px;">
                                <?php if ($filterType === 'text'): ?>
                                    <input type="text" class="export-filter" data-col="<?= $key ?>" placeholder="Contiene..."
                                        style="width:100%; padding:4px 6px; font-size:0.9em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                <?php elseif ($filterType === 'select_estado'): ?>
                                    <select class="export-filter" data-col="<?= $key ?>"
                                        style="width:100%; padding:4px 6px; font-size:0.9em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                        <option value="">-- Todos --</option>
                                        <?php foreach (StateMachine::ESTADOS as $est): ?>
                                            <option value="<?= htmlspecialchars($est) ?>"><?= htmlspecialchars($est) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($filterType === 'select_prioridad'): ?>
                                    <select class="export-filter" data-col="<?= $key ?>"
                                        style="width:100%; padding:4px 6px; font-size:0.9em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                        <option value="">-- Todas --</option>
                                        <option value="0">Normal</option>
                                        <option value="1">Media</option>
                                        <option value="2">Alta</option>
                                        <option value="3">Crítica</option>
                                    </select>
                                <?php elseif ($filterType === 'select_cuadrilla'): ?>
                                    <select class="export-filter" data-col="<?= $key ?>"
                                        style="width:100%; padding:4px 6px; font-size:0.9em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                        <option value="">-- Todas --</option>
                                        <?php foreach ($cuadrillas as $c): ?>
                                            <option value="<?= htmlspecialchars($c['nombre_cuadrilla']) ?>">
                                                <?= htmlspecialchars($c['nombre_cuadrilla']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($filterType === 'select_sn'): ?>
                                    <select class="export-filter" data-col="<?= $key ?>"
                                        style="width:100%; padding:4px 6px; font-size:0.9em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                        <option value="">-- Todos --</option>
                                        <option value="1">Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                <?php elseif ($filterType === 'daterange'): ?>
                                    <div style="display:flex; gap:4px;">
                                        <input type="date" class="export-filter" data-col="<?= $key ?>_desde" title="Desde"
                                            style="flex:1; padding:3px 4px; font-size:0.85em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                        <input type="date" class="export-filter" data-col="<?= $key ?>_hasta" title="Hasta"
                                            style="flex:1; padding:3px 4px; font-size:0.85em; border:1px solid var(--border-color); border-radius:4px; background:var(--bg-card); color:var(--text-primary);">
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px;">
            <button onclick="document.getElementById('exportModal').style.display='none'" class="btn btn-outline"
                style="min-height:38px;">Cancelar</button>
            <button onclick="exportarExcel()" class="btn"
                style="min-height:38px; background:#66bb6a; color:#fff; font-weight:600; border:none; padding:0 20px;">
                <i class="fas fa-download"></i> Descargar .xlsx
            </button>
        </div>
    </div>
</div>

<script>
    function toggleExportCols(state) {
        document.querySelectorAll('.export-col-cb').forEach(cb => cb.checked = state);
    }
    function exportarExcel() {
        const selected = Array.from(document.querySelectorAll('.export-col-cb:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Seleccioná al menos una columna para exportar.');
            return;
        }
        const params = new URLSearchParams();
        params.set('cols', selected.join(','));
        document.querySelectorAll('.export-filter').forEach(el => {
            const val = el.value.trim();
            if (val) {
                params.set('f_' + el.dataset.col, val);
            }
        });
        window.location.href = 'exportar_odt.php?' + params.toString();
        document.getElementById('exportModal').style.display = 'none';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>