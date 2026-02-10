<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Verificar permiso para este módulo
verificarPermiso('cuadrillas');

// Obtener usuario actual
$usuarioActual = obtenerUsuarioActual();
$esJefeCuadrilla = in_array($usuarioActual['rol'] ?? '', ['JefeCuadrilla', 'Jefe de Cuadrilla']);
$idCuadrillaUsuario = $usuarioActual['id_cuadrilla'] ?? null;

// 1. Fetch Specialties (for Filter)
$tiposTrabajo = $pdo->query("SELECT * FROM tipologias ORDER BY codigo_trabajo ASC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Cuadrillas with Vehicle, Count, and Joined Specialties
// LEFT JOIN with cuadrilla_tipos_trabajo and tipologias
// REFACTOR: joined table is 'tipologias' (id_tipologia, nombre, codigo_trabajo)
// Intermediate table `cuadrilla_tipos_trabajo` uses id_tipologia
$sqlBase = "SELECT c.*, 
            v.patente, v.marca, v.modelo,
            (SELECT COUNT(*) FROM personal p WHERE p.id_cuadrilla = c.id_cuadrilla) as total_personal,
            GROUP_CONCAT(tt.id_tipologia, '#', COALESCE(tt.codigo_trabajo, ''), '#', tt.nombre SEPARATOR '|') as tipos_concatenados
            FROM cuadrillas c
            LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id_vehiculo
            LEFT JOIN cuadrilla_tipos_trabajo ctt ON c.id_cuadrilla = ctt.id_cuadrilla
            LEFT JOIN tipologias tt ON ctt.id_tipologia = tt.id_tipologia";

if ($esJefeCuadrilla && $idCuadrillaUsuario) {
    $sql = $sqlBase . " WHERE c.id_cuadrilla = ? GROUP BY c.id_cuadrilla ORDER BY c.nombre_cuadrilla ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idCuadrillaUsuario]);
    $cuadrillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = $sqlBase . " GROUP BY c.id_cuadrilla ORDER BY c.nombre_cuadrilla ASC";
    $cuadrillas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Helper to format types for display and data-attribute
foreach ($cuadrillas as &$c) {
    $c['tipos_display'] = [];
    $c['tipos_data'] = [];
    $c['tipos_ids'] = []; // [NEW] For ID-based filtering

    if (!empty($c['tipos_concatenados'])) {
        $entries = explode('|', $c['tipos_concatenados']);
        foreach ($entries as $entry) {
            // Format: ID#CODE#DESC
            $parts = explode('#', $entry);
            $id = $parts[0] ?? '';
            $code = $parts[1] ?? '';
            $desc = $parts[2] ?? '';

            if ($id) {
                // Label for display
                $label = ($code ? "[$code] " : "") . $desc;
                $c['tipos_display'][] = $label;

                // Data for search properties
                $c['tipos_data'][] = $desc;
                $c['tipos_ids'][] = $id;
            }
        }
    }
}
unset($c);

$estados = ['Programada', 'Activa', 'Mantenimiento', 'Baja', 'Suspendida'];

// Metrics
$total = count($cuadrillas);
$activas = 0;
$mantenimiento = 0;
$sin_vehiculo = 0;

foreach ($cuadrillas as $c) {
    if ($c['estado_operativo'] === 'Activa')
        $activas++;
    if ($c['estado_operativo'] === 'Mantenimiento')
        $mantenimiento++;
    if (empty($c['id_vehiculo_asignado']))
        $sin_vehiculo++;
}

// [✓] NEW: Obtener ODTs activas asignadas a las cuadrillas
// ... (Keep existing ODT logic unchanged) ...
$sqlOdts = "SELECT ps.id_cuadrilla, o.id_odt, o.nro_odt_assa, o.estado_gestion, o.prioridad, o.direccion, 
                   o.fecha_vencimiento, t.nombre as tipo_trabajo, t.codigo_trabajo, o.id_tipologia
            FROM odt_maestro o
            JOIN programacion_semanal ps ON o.id_odt = ps.id_odt
            LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
            WHERE ps.id_programacion IN (SELECT MAX(id_programacion) FROM programacion_semanal GROUP BY id_odt)
            AND o.estado_gestion IN ('Programado', 'Ejecución', 'Ejecutado', 'Retrabajo', 'Postergado')";
$activeOdts = $pdo->query($sqlOdts)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

// Asociar ODTs a cada cuadrilla
foreach ($cuadrillas as &$c) {
    $c['odts'] = $activeOdts[$c['id_cuadrilla']] ?? [];
}
unset($c);

// [NEW] Contar ODTs por estado y prioridad para métricas
$odtProgramados = 0;
$odtEjecucion = 0;
$odtEjecutados = 0;
$odtRetrabajo = 0;
$odtUrgentes = 0;

foreach ($cuadrillas as $c) {
    foreach ($c['odts'] as $odt) {
        switch ($odt['estado_gestion']) {
            case 'Programado':
                $odtProgramados++;
                break;
            case 'Ejecución':
                $odtEjecucion++;
                break;
            case 'Ejecutado':
                $odtEjecutados++;
                break;
            case 'Retrabajo':
                $odtRetrabajo++;
                break;
        }
        if ($odt['prioridad'] === 'Urgente')
            $odtUrgentes++;
    }
}
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;"><i class="fas fa-hard-hat"></i> Gestión de Cuadrillas</h2>
            <p style="margin: 5px 0 0; color: #666;">Estructura de Trabajo y Equipos</p>
        </div>
        <?php if (!$esJefeCuadrilla): ?>
            <a href="form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nueva Cuadrilla</a>
        <?php endif; ?>
    </div>

    <!-- KPI Cards con Glow (Solo para perfiles que NO son Jefe de Cuadrilla) -->
    <?php if (!$esJefeCuadrilla): ?>
        <div class="metrics-row">
            <div class="metric-mini">
                <div class="metric-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $total; ?></span>
                    <span class="metric-lbl">Total Cuadrillas</span>
                </div>
            </div>
            <div class="metric-mini">
                <div class="metric-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $activas; ?></span>
                    <span class="metric-lbl">Activas</span>
                </div>
            </div>
            <div class="metric-mini">
                <div class="metric-icon warning">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $mantenimiento; ?></span>
                    <span class="metric-lbl">En Mantenimiento</span>
                </div>
            </div>
            <?php if ($sin_vehiculo > 0): ?>
                <div class="metric-mini">
                    <div class="metric-icon danger">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="metric-content">
                        <span class="metric-val"><?php echo $sin_vehiculo; ?></span>
                        <span class="metric-lbl">Sin Vehículo</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- [NEW] ODT Status Filters (Visible para todos los perfiles) -->
    <!-- ... ODT KPI BADGES ... -->
    <div class="metrics-row" style="margin-top: 15px;">
        <!-- Programados -->
        <div class="metric-mini odt-filter-badge" data-estado="Programado" onclick="filterOdtByStatus('Programado')"
            style="cursor: pointer;">
            <div class="metric-icon info" style="background: rgba(123, 31, 162, 0.15); color: #ce93d8;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $odtProgramados; ?></span>
                <span class="metric-lbl">PROGRAMADOS</span>
            </div>
        </div>
        <!-- Ejecución -->
        <div class="metric-mini odt-filter-badge" data-estado="Ejecución" onclick="filterOdtByStatus('Ejecución')"
            style="cursor: pointer;">
            <div class="metric-icon success">
                <i class="fas fa-running"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $odtEjecucion; ?></span>
                <span class="metric-lbl">EJECUCIÓN</span>
            </div>
        </div>
        <!-- Ejecutados -->
        <div class="metric-mini odt-filter-badge" data-estado="Ejecutado" onclick="filterOdtByStatus('Ejecutado')"
            style="cursor: pointer;">
            <div class="metric-icon success" style="background: rgba(46, 125, 50, 0.15); color: #4caf50;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $odtEjecutados; ?></span>
                <span class="metric-lbl">EJECUTADOS</span>
            </div>
        </div>
        <!-- Retrabajo -->
        <div class="metric-mini odt-filter-badge" data-estado="Retrabajo" onclick="filterOdtByStatus('Retrabajo')"
            style="cursor: pointer;">
            <div class="metric-icon warning" style="background: rgba(191, 54, 12, 0.15); color: #ffab91;">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $odtRetrabajo; ?></span>
                <span class="metric-lbl">RETRABAJO</span>
            </div>
        </div>
        <!-- Urgentes -->
        <div class="metric-mini odt-filter-badge" data-prioridad="Urgente" onclick="filterOdtByStatus('Urgente')"
            style="cursor: pointer;">
            <div class="metric-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $odtUrgentes; ?></span>
                <span class="metric-lbl">URGENTES</span>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card" style="border-top: 4px solid var(--color-primary);">

        <!-- Filters -->
        <div class="filter-bar"
            style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    placeholder="Buscar nombre, tipo de trabajo..." class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Tipo de Trabajo</label>
                <select id="filterEsp" onchange="filterTable()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($tiposTrabajo as $tt): ?>
                        <option value="<?php echo $tt['id_tipologia']; ?>">
                            <?php echo ($tt['codigo_trabajo'] ? '[' . $tt['codigo_trabajo'] . '] ' : '') . htmlspecialchars($tt['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="display: flex; align-items: flex-end;">
                <button onclick="resetFilters()" class="btn btn-outline btn-sm" title="Limpiar Filtros">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="cuadrillas-grid" id="cuadrillasGrid">
            <?php if (empty($cuadrillas)): ?>
                <div class="empty-state" style="grid-column: 1/-1; text-align: center; padding: 60px; color: #999;">
                    <i class="fas fa-hard-hat" style="font-size: 3em; margin-bottom: 15px;"></i><br>
                    No hay cuadrillas registradas
                </div>
            <?php else: ?>
                <?php foreach ($cuadrillas as $c): ?>
                    <div class="cuadrilla-card <?php echo strtolower($c['estado_operativo']); ?>"
                        data-nombre="<?php echo strtolower($c['nombre_cuadrilla']); ?>"
                        data-especialidad="<?php echo htmlspecialchars(implode(',', $c['tipos_data'])); ?>"
                        data-type-ids="<?php echo implode(',', $c['tipos_ids']); ?>"
                        data-estado="<?php echo $c['estado_operativo']; ?>">

                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-hard-hat"></i>
                                <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                            </div>

                            <!-- [✓] NEW: Info consolidada en cabecera -->
                            <div class="header-details"
                                style="display: flex; gap: 15px; align-items: center; margin-left: auto; margin-right: 15px; font-size: 0.9em;">
                                <?php foreach ($c['tipos_display'] as $label): ?>
                                    <span class="badge-esp"><?php echo htmlspecialchars($label); ?></span>
                                <?php endforeach; ?>

                                <?php if ($c['patente']): ?>
                                    <span class="badge-vehiculo">
                                        <i class="fas fa-truck" style="margin-right: 4px;"></i>
                                        <?php echo $c['patente']; ?>
                                    </span>
                                <?php endif; ?>

                                <span class="text-secondary" style="font-weight: 500;">
                                    <i class="fas fa-users" style="margin-right: 4px;"></i>
                                    <?php echo $c['total_personal']; ?>
                                </span>
                            </div>

                            <span class="badge-estado <?php echo strtolower($c['estado_operativo']); ?>">
                                <?php echo $c['estado_operativo']; ?>
                            </span>
                        </div>

                        <div class="card-body" style="padding: 0;">
                            <!-- Info antigua eliminada, solo queda la tabla abajo -->

                            <!-- [✓] NEW: Columna Derecha: Tareas asignadas -->
                            <?php if (!empty($c['odts'])): ?>
                                <div class="card-tasks" style="width: 100%; border-top: none; padding: 20px; overflow-x: auto;">

                                    <!-- [✓] Toolbar de Acciones Masivas - Estilo ODT Dashboard -->
                                    <?php if ($esJefeCuadrilla): ?>
                                        <div id="bulkActionsToolbar-<?php echo $c['id_cuadrilla']; ?>" class="card"
                                            style="display: none; margin-bottom: 15px; padding: 12px 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; border-radius: var(--border-radius-md);">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <span style="font-weight: bold; color: var(--accent-primary);">
                                                    <i class="fas fa-check-double"></i>
                                                    Seleccionados:
                                                    <span id="selectedCount-<?php echo $c['id_cuadrilla']; ?>">0</span>
                                                </span>
                                            </div>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <select class="form-control form-control-sm"
                                                    id="bulkActionState-<?php echo $c['id_cuadrilla']; ?>"
                                                    style="min-width: 180px; padding: 8px;">
                                                    <option value="">-- Cambiar Estado --</option>
                                                    <option value="Ejecución">Ejecución</option>
                                                    <option value="Ejecutado">Ejecutado</option>
                                                    <option value="Postergado">Postergado</option>
                                                </select>
                                                <button type="button" class="btn btn-primary"
                                                    style="min-height: 38px; white-space: nowrap;"
                                                    onclick="applyBulkAction(<?php echo $c['id_cuadrilla']; ?>)">
                                                    <i class="fas fa-check"></i> Aplicar
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <table class="table table-borderless table-hover custom-table"
                                        style="font-size: 0.85em; width: 100%; margin-bottom: 0;">
                                        <thead>
                                            <tr>
                                                <th style="width: 30px;">
                                                    <input type="checkbox"
                                                        onchange="toggleAllOdt(this, <?php echo $c['id_cuadrilla']; ?>)">
                                                </th>
                                                <th>Nro ODT</th>
                                                <th>Dirección</th>
                                                <th>Tipo de Trabajo</th>
                                                <th class="text-center">Estado</th>
                                                <th>Prioridad</th>
                                                <th>Vencimiento</th>
                                                <th class="text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($c['odts'] as $odt):
                                                // Calcular días vencimiento
                                                $vencimiento = (!empty($odt['fecha_vencimiento'])) ? new DateTime($odt['fecha_vencimiento']) : null;
                                                $hoy = new DateTime();

                                                if ($vencimiento) {
                                                    $dias = $hoy->diff($vencimiento)->format('%r%a');
                                                    $classVenc = ($dias < 0) ? 'danger' : (($dias < 3) ? 'warning' : 'success');
                                                    $textVenc = abs($dias) . " días";
                                                    $dateShow = $vencimiento->format('d/m/Y');
                                                } else {
                                                    $classVenc = 'secondary';
                                                    $textVenc = 'S/F';
                                                    $dateShow = '-';
                                                }
                                                ?>
                                                <tr class="odt-row" data-estado="<?php echo $odt['estado_gestion']; ?>"
                                                    data-prioridad="<?php echo $odt['prioridad'] ?? 'Normal'; ?>"
                                                    data-tipo-id="<?php echo $odt['id_tipologia']; ?>">
                                                    <td>
                                                        <input type="checkbox"
                                                            class="odt-checkbox-<?php echo $c['id_cuadrilla']; ?> form-check-input"
                                                            value="<?php echo $odt['id_odt']; ?>"
                                                            onchange="console.log('Checkbox changed'); updateSelectedCount(<?php echo $c['id_cuadrilla']; ?>)"
                                                            <?php echo (!$esJefeCuadrilla) ? 'disabled' : ''; ?>>
                                                    </td>
                                                    <td style="font-weight: 700; color: var(--text-primary); white-space: nowrap;">
                                                        #<?php echo $odt['nro_odt_assa']; ?>
                                                    </td>
                                                    <td style="color: var(--text-secondary);">
                                                        <?php echo htmlspecialchars($odt['direccion']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge-trabajo"
                                                            style="background: rgba(100, 181, 246, 0.1); color: var(--accent-primary); padding: 2px 8px; border-radius: 6px; font-weight: 500;">
                                                            <?php if (!empty($odt['codigo_trabajo'])): ?>
                                                                [<?php echo $odt['codigo_trabajo']; ?>]
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($odt['tipo_trabajo'] ?? 'Sin tipo'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge-estado-pill"
                                                            data-estado="<?php echo $odt['estado_gestion']; ?>">
                                                            <?php echo $odt['estado_gestion']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($odt['prioridad'] === 'Urgente'): ?>
                                                            <span
                                                                style="color: var(--color-danger); font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                                                <i class="fas fa-circle" style="font-size: 8px;"></i> Urgente
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="color: var(--text-secondary);">Normal</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge-vencimiento <?php echo $classVenc; ?>">
                                                            <i class="fas fa-clock"></i> <?php echo $textVenc; ?>
                                                            <small
                                                                style="display: block; font-size: 0.8em; opacity: 0.8;"><?php echo $dateShow; ?></small>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                                            <!-- [NEW] Botón Gestión Móvil (Jefe) -->
                                                            <a href="../odt/view_mobile.php?id=<?php echo $odt['id_odt']; ?>"
                                                                class="btn-icon-action info" title="Gestionar / Fotos / Avance">
                                                                <i class="fas fa-eye"></i>
                                                            </a>

                                                            <?php if (in_array($odt['estado_gestion'], ['Programado', 'Postergado', 'Retrabajo'])): ?>
                                                                <button
                                                                    onclick="updateOdtStatus(<?php echo $odt['id_odt']; ?>, 'Ejecución')"
                                                                    class="btn-icon-action info" title="Iniciar">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            <?php elseif ($odt['estado_gestion'] == 'Ejecución'): ?>
                                                                <button
                                                                    onclick="updateOdtStatus(<?php echo $odt['id_odt']; ?>, 'Ejecutado')"
                                                                    class="btn-icon-action success" title="Finalizar">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button
                                                                    onclick="updateOdtStatus(<?php echo $odt['id_odt']; ?>, 'Postergado')"
                                                                    class="btn-icon-action warning" title="Postergar">
                                                                    <i class="fas fa-clock"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer">
                            <a href="asistencia.php?id=<?php echo $c['id_cuadrilla']; ?>" class="btn-action" title="Asistencia">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                            <a href="herramientas.php?id=<?php echo $c['id_cuadrilla']; ?>" class="btn-action"
                                title="Herramientas">
                                <i class="fas fa-tools"></i>
                            </a>
                            <?php if (!$esJefeCuadrilla): ?>
                                <a href="form.php?id=<?php echo $c['id_cuadrilla']; ?>" class="btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button
                                    onclick="confirmDelete(<?php echo $c['id_cuadrilla']; ?>, '<?php echo addslashes($c['nombre_cuadrilla']); ?>', <?php echo $c['total_personal']; ?>)"
                                    class="btn-action btn-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="noResults" style="display:none; padding: 40px; text-align: center; color: #999;">
            No se encontraron cuadrillas con los filtros aplicados.
        </div>
    </div>
</div>

<style>
    /* ============================================
       ESTILOS PREMIUM - CUADRILLAS (THEME AWARE)
       ============================================ */

    /* Métricas Premium */
    .metrics-row {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .metric-mini {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: var(--shadow-sm);
        min-width: 150px;
        transition: all 0.3s ease;
        border: 1px solid rgba(100, 181, 246, 0.1);
        flex: 1;
    }

    .metric-mini:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
        border-color: var(--accent-primary);
    }

    .metric-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .metric-icon.primary {
        background: rgba(0, 115, 168, 0.1);
        color: var(--color-primary);
    }

    .metric-icon.success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--color-success);
    }

    .metric-icon.warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--color-warning);
    }

    .metric-icon.danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--color-danger);
    }

    .metric-content {
        display: flex;
        flex-direction: column;
    }

    .metric-val {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .metric-lbl {
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    /* Filtros */
    .filter-bar {
        background: var(--bg-card) !important;
        border: 1px solid rgba(100, 181, 246, 0.1);
    }

    .filter-group label {
        color: var(--text-secondary);
        font-weight: 600;
    }

    .form-control-sm {
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border: 1px solid rgba(100, 181, 246, 0.15);
        border-radius: 12px;
    }

    .form-control-sm:focus {
        background: var(--bg-card);
        border-color: var(--accent-primary);
        color: var(--text-primary);
        box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.15);
    }

    /* Cards Grid */
    .cuadrillas-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .cuadrilla-card {
        background: var(--bg-card);
        border-radius: 20px;
        overflow: hidden;
        border-left: 5px solid var(--color-success);
        /* Activa default */
        transition: all 0.3s ease;
        border: 1px solid rgba(100, 181, 246, 0.1);
        box-shadow: var(--shadow-sm);
        position: relative;
    }

    .cuadrilla-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--accent-primary);
    }

    .cuadrilla-card.mantenimiento {
        border-left-color: var(--color-warning);
    }

    .cuadrilla-card.baja {
        border-left-color: var(--text-muted);
        opacity: 0.8;
    }

    .card-header {
        padding: 18px 22px;
        background: var(--bg-secondary);
        border-bottom: 1px solid rgba(100, 181, 246, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-weight: 600;
        font-size: 1.1em;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-title i {
        color: var(--accent-primary);
    }

    .badge-estado {
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.75em;
        font-weight: 600;
    }

    .badge-estado.activa {
        background: rgba(16, 185, 129, 0.15);
        color: var(--color-success);
    }

    .badge-estado.mantenimiento {
        background: rgba(245, 158, 11, 0.15);
        color: var(--color-warning);
    }

    .badge-estado.baja {
        background: rgba(156, 163, 175, 0.15);
        color: var(--text-muted);
    }

    .card-body {
        padding: 20px;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(100, 181, 246, 0.05);
        color: var(--text-secondary);
        font-size: 0.95em;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row i {
        width: 20px;
        text-align: center;
        color: var(--text-muted);
    }

    .info-row strong {
        color: var(--text-primary);
    }

    .badge-esp {
        background: rgba(100, 181, 246, 0.1);
        color: var(--accent-primary);
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 0.85em;
        font-weight: 500;
    }

    .badge-vehiculo {
        background: rgba(156, 39, 176, 0.1);
        color: #ce93d8;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 0.85em;
    }

    .cuadrilla-card {
        width: 100%;
        max-width: 100%;
    }

    [data-theme="light"] .badge-vehiculo {
        color: #9c27b0;
    }

    .whatsapp-link {
        color: #25d366;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
        transition: opacity 0.2s;
    }

    .whatsapp-link:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    .card-footer {
        padding: 15px 22px;
        background: var(--bg-tertiary);
        border-top: 1px solid rgba(100, 181, 246, 0.1);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        background: var(--bg-card);
        color: var(--text-secondary);
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
        cursor: pointer;
    }

    .btn-action:hover {
        background: var(--accent-primary);
        color: white;
        transform: translateY(-2px);
    }

    .btn-action.btn-danger:hover {
        background: var(--color-danger);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .metric-mini {
            min-width: auto;
            padding: 12px;
            flex-direction: column;
            text-align: center;
        }

        .metric-icon {
            margin-bottom: 5px;
        }

        .filter-bar {
            padding: 12px;
        }

        .cuadrillas-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Estilos adicionales para tabla ODTs */
    .custom-table thead th {
        background: rgba(0, 0, 0, 0.2);
        color: var(--text-secondary);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8em;
        padding: 10px 15px;
        border-bottom: 2px solid rgba(100, 181, 246, 0.1);
        vertical-align: middle;
    }

    .custom-table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(100, 181, 246, 0.05);
        color: var(--text-secondary);
    }

    .custom-table tbody tr:last-child td {
        border-bottom: none;
    }

    .badge-estado-pill {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
        display: inline-block;
        background: var(--bg-tertiary);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .badge-estado-pill[data-estado="Ejecución"] {
        background: rgba(46, 125, 50, 0.2);
        color: #4caf50;
        border-color: #4caf50;
    }

    .badge-estado-pill[data-estado="Programado"] {
        background: rgba(123, 31, 162, 0.2);
        color: #ce93d8;
        border-color: #ce93d8;
    }

    .badge-estado-pill[data-estado="Postergado"] {
        background: rgba(255, 143, 0, 0.2);
        color: #ffb74d;
        border-color: #ffb74d;
    }

    .badge-estado-pill[data-estado="Retrabajo"] {
        background: rgba(191, 54, 12, 0.2);
        color: #ffab91;
        border-color: #ffab91;
    }

    .badge-vencimiento {
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        line-height: 1.2;
    }

    .badge-vencimiento.success {
        background: rgba(76, 175, 80, 0.1);
        color: #81c784;
    }

    .badge-vencimiento.warning {
        background: rgba(255, 152, 0, 0.1);
        color: #ffb74d;
    }

    .badge-vencimiento.danger {
        background: rgba(244, 67, 54, 0.1);
        color: #e57373;
    }

    .btn-icon-action {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn-icon-action:hover {
        transform: scale(1.1);
    }

    .btn-icon-action.info:hover {
        background: var(--color-info);
        color: white;
    }

    .btn-icon-action.success:hover {
        background: var(--color-success);
        color: white;
    }

    .btn-icon-action.warning:hover {
        background: var(--color-warning);
        color: white;
    }

    @media(max-width: 900px) {
        .card-body {
            flex-direction: column;
        }

        .card-tasks {
            width: 100%;
            border-left: none;
            border-top: 1px solid rgba(100, 181, 246, 0.1);
            padding-left: 0;
            padding-top: 20px;
        }
    }
</style>

<script>
    // ============================================
    // FUNCIONES DE CUADRILLAS - JAVASCRIPT LIMPIO
    // ============================================

    function filterTable() {
        // [MODIFIED] Deep Search & Deep Type Filtering
        const search = document.getElementById('searchInput').value.toLowerCase().trim();
        const filterId = document.getElementById('filterEsp').value; // Work Type ID

        const cards = document.querySelectorAll('.cuadrilla-card');
        let visibleCards = 0;

        cards.forEach(card => {
            // 1. Initial Visibility Check (Before drilling down)
            // We start by assuming the card might be visible, then we check its content.

            const cardName = (card.dataset.nombre || '').toLowerCase();
            const cardEsp = (card.dataset.especialidad || '').toLowerCase();
            const nameMatch = !search || cardName.includes(search) || cardEsp.includes(search);

            let hasVisibleContent = false;
            const rows = card.querySelectorAll('.odt-row');

            if (rows.length > 0) {
                let visibleRowsCount = 0;
                rows.forEach(row => {
                    // Get Row Data
                    const rowText = row.innerText.toLowerCase();
                    const rowTypeId = row.dataset.tipoId || ''; // Needs to be added to PHP

                    // Filter Logic
                    const matchSearch = !search || rowText.includes(search);
                    const matchType = !filterId || rowTypeId == filterId;

                    // Row is visible only if matches BOTH search AND type filter
                    // (Unless the search matches the Squad Name, in which case we might be more lenient with search, 
                    // BUT for the Type Filter, the user explicitly wants to see only that type of work).

                    // Decision: Type Filter is strict/exclusive. Search is inclusive context.
                    // If I select "Water Fix", I want to see ONLY Water Fix rows.

                    if (matchType && (nameMatch || matchSearch)) {
                        row.style.display = '';
                        visibleRowsCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (visibleRowsCount > 0) hasVisibleContent = true;

            } else {
                // Empty Squad (No ODTs)
                // Visible only if no type filter is selected AND name matches search
                if (!filterId && nameMatch) {
                    hasVisibleContent = true;
                }
            }

            // Final Card Visibility
            if (hasVisibleContent) {
                card.style.display = '';
                visibleCards++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('noResults').style.display = visibleCards === 0 ? 'block' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterEsp').value = '';
        document.getElementById('filterEstado').value = '';
        filterTable();
    }

    function confirmDelete(id, nombre, personal) {
        if (personal > 0) {
            alert('No se puede eliminar: la cuadrilla tiene ' + personal + ' integrante(s) asignados.\n\nReasigne el personal primero.');
            return;
        }
        if (confirm('¿Eliminar cuadrilla "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
            window.location.href = 'delete.php?id=' + id;
        }
    }

    // ============================================
    // ODT STATUS FILTER - FILTRAR POR ESTADO
    // ============================================

    var currentOdtFilter = null;

    function filterOdtByStatus(filter) {
        var allRows = document.querySelectorAll('.odt-row');
        var allBadges = document.querySelectorAll('.odt-filter-badge');

        // Toggle filter: if clicking same filter, clear it
        if (currentOdtFilter === filter) {
            currentOdtFilter = null;
            // Show all rows
            allRows.forEach(function (row) {
                row.style.display = '';
            });
            // Remove active state from all badges
            allBadges.forEach(function (badge) {
                badge.style.boxShadow = '';
                badge.style.transform = '';
            });
            return;
        }

        currentOdtFilter = filter;

        // Filter rows
        allRows.forEach(function (row) {
            var estado = row.getAttribute('data-estado');
            var prioridad = row.getAttribute('data-prioridad');

            var matches = false;
            if (filter === 'Urgente') {
                matches = (prioridad === 'Urgente');
            } else {
                matches = (estado === filter);
            }

            row.style.display = matches ? '' : 'none';
        });

        // Highlight active badge
        allBadges.forEach(function (badge) {
            var badgeEstado = badge.getAttribute('data-estado');
            var badgePrioridad = badge.getAttribute('data-prioridad');
            var isActive = (badgeEstado === filter) || (badgePrioridad === filter);

            if (isActive) {
                badge.style.boxShadow = '0 0 0 2px var(--accent-primary), 0 4px 15px rgba(100, 181, 246, 0.4)';
                badge.style.transform = 'scale(1.05)';
            } else {
                badge.style.boxShadow = '';
                badge.style.transform = '';
            }
        });
    }

    // ============================================
    // BULK ACTIONS - ACCIONES MASIVAS ODT
    // ============================================

    function toggleAllOdt(source, squadId) {
        var checkboxes = document.querySelectorAll('.odt-checkbox-' + squadId);
        checkboxes.forEach(function (cb) {
            if (!cb.disabled) cb.checked = source.checked;
        });
        updateSelectedCount(squadId);
    }

    function updateSelectedCount(squadId) {
        try {
            var checkboxes = document.querySelectorAll('.odt-checkbox-' + squadId + ':checked');
            var count = checkboxes.length;

            var counterEl = document.getElementById('selectedCount-' + squadId);
            if (counterEl) {
                counterEl.textContent = count + ' seleccionados';
            }

            var toolbar = document.getElementById('bulkActionsToolbar-' + squadId);
            if (toolbar) {
                if (count > 0) {
                    toolbar.style.display = 'flex';
                } else {
                    toolbar.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('Error in updateSelectedCount:', e);
        }
    }

    // ============================================
    // APPLY BULK ACTION - APLICAR ACCIÓN MASIVA
    // ============================================

    function applyBulkAction(squadId) {
        var checkboxes = document.querySelectorAll('.odt-checkbox-' + squadId + ':checked');
        var statusSelect = document.getElementById('bulkActionState-' + squadId);
        var newStatus = statusSelect.value;

        if (checkboxes.length === 0) {
            alert('Seleccione al menos una ODT.');
            return;
        }
        if (!newStatus) {
            alert('Seleccione un estado para aplicar.');
            return;
        }
        if (!confirm('¿Actualizar ' + checkboxes.length + ' ODTs a estado "' + newStatus + '"?')) {
            return;
        }

        var ids = [];
        checkboxes.forEach(function (cb) {
            ids.push(cb.value);
        });

        fetch('/APP-Prueba/modules/odt/bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids, estado: newStatus })
        })
            .then(function (res) {
                return res.text();
            })
            .then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta del servidor: ' + text.substring(0, 100));
                }
            })
            .then(function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(function (err) {
                console.error(err);
                alert(err.message);
            });
    }

    // ============================================
    // UPDATE ODT STATUS - ACTUALIZAR ESTADO INDIVIDUAL
    // ============================================

    function updateOdtStatus(id, newStatus) {
        if (!confirm('¿Cambiar estado de ODT a: ' + newStatus + '?')) {
            return;
        }

        fetch('/APP-Prueba/modules/odt/bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: [id], estado: newStatus })
        })
            .then(function (res) {
                return res.text();
            })
            .then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta del servidor: ' + text.substring(0, 100));
                }
            })
            .then(function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo actualizar'));
                }
            })
            .catch(function (err) {
                console.error('Error:', err);
                alert(err.message);
            });
    }

</script>

<?php require_once '../../includes/footer.php'; ?>