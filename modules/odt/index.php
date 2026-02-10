<?php
/**
 * [!] ARCH: Módulo de Gestión de ODTs para Inspector ASSA
 * [→] EDITAR: Configurar permisos según roles
 * [✓] AUDIT: Lista con filtros, métricas y alerta de vencimiento
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// [✓] AUDIT: Verificar permisos
if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

// [!] ARCH: Obtener Rol Actual
$rolActual = $_SESSION['usuario_rol'] ?? '';

// [✓] NEW: Lógica para Jefe de Cuadrilla
$idCuadrillaUsuario = null;
if ($rolActual === 'Jefe de Cuadrilla') {
    // Asumiendo que el ID de cuadrilla está en la sesión o se busca en la DB
    // Opción A: Sesión (Ideal si se guarda al login)
    // $idCuadrillaUsuario = $_SESSION['usuario_id_cuadrilla'] ?? 0;

    // Opción B: Query directa (Más robusta por ahora)
    $stmt = $pdo->prepare("SELECT id_cuadrilla FROM personal WHERE id_personal = (SELECT id_personal FROM usuarios WHERE id_usuario = ?)");
    $stmt->execute([$_SESSION['usuario_id']]);
    $idCuadrillaUsuario = $stmt->fetchColumn();
}

// [!] ARCH: Obtener ODTs con info de tipología y cuadrilla asignada
// Base SQL
$sql = "SELECT o.*, t.nombre as tipo_trabajo, t.codigo_trabajo, 
               ps.fecha_programada, ps.turno, c.nombre_cuadrilla, ps.id_cuadrilla, c.color_hex
        FROM odt_maestro o
        LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
        LEFT JOIN (
            SELECT id_odt, id_cuadrilla, fecha_programada, turno 
            FROM programacion_semanal 
            WHERE id_programacion IN (SELECT MAX(id_programacion) FROM programacion_semanal GROUP BY id_odt)
        ) ps ON o.id_odt = ps.id_odt
        LEFT JOIN cuadrillas c ON ps.id_cuadrilla = c.id_cuadrilla
        WHERE 1=1 ";

// Filtros por Rol
if ($rolActual === 'Inspector ASSA') {
    $sql .= "AND o.estado_gestion IN ('Sin Programar', 'Ejecución', 'Ejecutado', 'Aprobado por inspector', 'Retrabajo') ";
} elseif ($rolActual === 'Jefe de Cuadrilla') {
    $sql .= "AND (ps.id_cuadrilla = $idCuadrillaUsuario OR ps.id_cuadrilla IS NULL)
             AND o.estado_gestion IN ('Sin Programar', 'Programado', 'Ejecución', 'Retrabajo', 'Postergado') ";
}

$sql .= "ORDER BY 
            CASE o.prioridad WHEN 'Urgente' THEN 0 ELSE 1 END,
            o.fecha_vencimiento ASC";

$odts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// [!] ARCH: Obtener Cuadrillas para Filtros y Asignación
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// [→] EDITAR: Tipos de trabajo para filtro
$tipos = $pdo->query("SELECT id_tipologia, nombre, codigo_trabajo FROM tipos_trabajos WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Métricas
$total = count($odts);
$sinProgramar = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Sin Programar'));
$progSolicitada = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Programación Solicitada'));
$programados = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Programado'));
$ejecucion = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Ejecución'));
$ejecutados = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Ejecutado'));
$precertificada = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Precertificada'));
$aprobado = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Aprobado por inspector'));
$retrabajo = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Retrabajo'));
$postergado = count(array_filter($odts, fn($o) => $o['estado_gestion'] === 'Postergado')); // New Metric
$urgentes = count(array_filter($odts, fn($o) => $o['prioridad'] === 'Urgente'));

// [!] ARCH: Calcular ODTs próximas a vencer (7 días)
$hoy = new DateTime();
$proximasVencer = count(array_filter($odts, function ($o) use ($hoy) {
    if (empty($o['fecha_vencimiento']) || $o['estado_gestion'] === 'Finalizado')
        return false;
    $venc = new DateTime($o['fecha_vencimiento']);
    $diff = $hoy->diff($venc)->days;
    return $venc >= $hoy && $diff <= 7;
}));
?>

<!-- [!] PWA-OFFLINE: Indicador de estado de conexión -->
<div id="offlineIndicator" class="offline-indicator">
    📶 Sin conexión - Los cambios se guardarán localmente
</div>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="margin: 0; font-size: 1.4em; color: var(--text-primary);"><i class="fas fa-clipboard-list"
                    style="color: var(--accent-primary);"></i> Gestión de ODTs</h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary); font-size: 0.9em;">
                <?php echo ($rolActual === 'Inspector ASSA') ? 'Modo de Carga Directa - Inspector' : 'Control de Órdenes de Trabajo ASSA'; ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- [!] PWA-OFFLINE: Badge de pendientes -->
            <div id="pendingBadge" class="pending-badge" style="display: none;">
                📱 <span id="pendingCount">0</span> pendientes
            </div>

            <!-- Exportar PDF -->
            <button onclick="exportTablePDF()" class="btn"
                style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); color: var(--accent-primary); display: flex; align-items: center; gap: 8px; font-weight: 600;"
                title="Exportar tabla a PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </button>

            <!-- Imprimir con materiales -->
            <button onclick="printODTs()" class="btn"
                style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid var(--text-secondary); color: var(--text-primary); display: flex; align-items: center; gap: 8px; font-weight: 600;"
                title="Imprimir ODTs con materiales">
                <i class="fas fa-print"></i> Imprimir
            </button>

            <!-- [!] ARCH: Botón "Nueva ODT" prominente para Inspector -->
            <?php if (tienePermiso('odt')): ?>
                <a href="form.php" class="btn btn-primary"
                    style="min-height: 55px; min-width: 140px; display: flex; align-items: center; gap: 10px; font-weight: 600; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);">
                    <i class="fas fa-plus-circle" style="font-size: 1.2em;"></i> Nueva ODT
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Métricas (ocultas para Inspector ASSA) -->
    <?php if ($rolActual !== 'Inspector ASSA'): ?>
    <div class="metrics-row">
        <!-- Total -->
        <div class="metric-mini" onclick="setQuickFilter('estado', '')">
            <div class="metric-icon primary">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $total; ?></span>
                <span class="metric-lbl">Total</span>
            </div>
        </div>

        <!-- Sin Programar -->
        <div class="metric-mini" onclick="setQuickFilter('estado', 'Sin Programar')">
            <div class="metric-icon warning">
                <i class="fas fa-stopwatch"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $sinProgramar; ?></span>
                <span class="metric-lbl">Sin Prog.</span>
            </div>
        </div>

        <?php if ($rolActual !== 'Inspector ASSA'): ?>
            <!-- Programación Solicitada (solo para roles no-Inspector) -->
            <div class="metric-mini" onclick="setQuickFilter('estado', 'Programación Solicitada')">
                <div class="metric-icon info">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $progSolicitada; ?></span>
                    <span class="metric-lbl">Prog. Solicit.</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Programados -->
        <div class="metric-mini" onclick="setQuickFilter('estado', 'Programado')">
            <div class="metric-icon info">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $programados; ?></span>
                <span class="metric-lbl">Programados</span>
            </div>
        </div>

        <!-- Ejecución -->
        <div class="metric-mini" onclick="setQuickFilter('estado', 'Ejecución')">
            <div class="metric-icon success">
                <i class="fas fa-running"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $ejecucion; ?></span>
                <span class="metric-lbl">Ejecución</span>
            </div>
        </div>

        <!-- Ejecutados -->
        <div class="metric-mini" onclick="setQuickFilter('estado', 'Ejecutado')">
            <div class="metric-icon success">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $ejecutados; ?></span>
                <span class="metric-lbl">Ejecutados</span>
            </div>
        </div>

        <?php if ($rolActual !== 'Inspector ASSA'): ?>
            <!-- Precertificada (solo para roles no-Inspector) -->
            <div class="metric-mini" onclick="setQuickFilter('estado', 'Precertificada')">
                <div class="metric-icon" style="background: rgba(0, 150, 136, 0.15); color: #009688;">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $precertificada; ?></span>
                    <span class="metric-lbl">Precertif.</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Aprobado por Inspector -->
        <div class="metric-mini" onclick="setQuickFilter('estado', 'Aprobado por inspector')">
            <div class="metric-icon" style="background: rgba(0, 150, 136, 0.15); color: #006064;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $aprobado; ?></span>
                <span class="metric-lbl">Aprobados</span>
            </div>
        </div>

        <!-- Retrabajo -->
        <div class="metric-mini" onclick="setQuickFilter('estado', 'Retrabajo')">
            <div class="metric-icon" style="background: rgba(211, 47, 47, 0.15); color: #bf360c;">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $retrabajo; ?></span>
                <span class="metric-lbl">Retrabajo</span>
            </div>
        </div>

        <!-- Postergado (Solo Jefe de Cuadrilla o general, no Inspector) -->
        <?php if ($postergado > 0 && $rolActual !== 'Inspector ASSA'): ?>
            <div class="metric-mini" onclick="setQuickFilter('estado', 'Postergado')">
                <div class="metric-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $postergado; ?></span>
                    <span class="metric-lbl">Postergado</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Urgentes -->
        <?php if ($urgentes > 0): ?>
            <div class="metric-mini" onclick="setQuickFilter('prioridad', 'Urgente')">
                <div class="metric-icon danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $urgentes; ?></span>
                    <span class="metric-lbl">Urgentes</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; /* fin ocultar métricas Inspector */ ?>


    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="card"
            style="padding: 15px; margin-bottom: 20px; border-left: 4px solid <?php echo $_GET['msg'] == 'saved' ? 'var(--color-success)' : ($_GET['msg'] == 'deleted' ? 'var(--color-warning)' : 'var(--color-danger)'); ?>; 
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
    <div class="card" style="margin-bottom: 25px; padding: 15px;">
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <!-- Búsqueda Principal -->
            <div style="flex: 2; min-width: 250px;">
                <input type="text" id="searchInput" onkeyup="filterODTs()"
                    placeholder="🔍 Buscar por Nro ODT, Dirección o Inspector..." class="filter-select"
                    style="margin-bottom: 0;">
            </div>

            <!-- Filtro por Estado (oculto para Inspector ASSA) -->
            <?php if ($rolActual !== 'Inspector ASSA'): ?>
                <div style="flex: 1; min-width: 180px;">
                    <select id="filterEstado" onchange="handleFilterChange()" class="filter-select">
                        <option value="">Estado: Todos</option>
                        <option value="Sin Programar">Sin Programar</option>
                        <option value="Programación Solicitada">Programación Solicitada</option>
                        <option value="Programado">Programado</option>
                        <option value="Ejecución">Ejecución</option>
                        <option value="Ejecutado">Ejecutado</option>
                        <option value="Precertificada">Precertificada</option>
                        <option value="Aprobado por inspector">Aprobado por inspector</option>
                        <option value="Retrabajo">Retrabajo</option>
                        <option value="Postergado">Postergado</option>
                        <option value="Finalizado">Finalizado</option>
                    </select>
                </div>
            <?php else: ?>
                <!-- Input oculto para mantener el JS funcionando -->
                <input type="hidden" id="filterEstado" value="">
            <?php endif; ?>

            <?php if ($rolActual !== 'Inspector ASSA'): ?>
            <!-- Filtro por Cuadrillas -->
            <div style="flex: 1; min-width: 200px;">
                <select id="filterCuadrilla" onchange="handleFilterChange()" class="filter-select">
                    <option value="">Cuadrilla: Todas</option>
                    <option value="SIN_ASIGNAR">-- Sin Asignar --</option>
                    <?php foreach ($cuadrillas as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>">
                            <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Trabajos -->
            <div style="flex: 1; min-width: 200px;">
                <select id="filterTrabajo" onchange="handleFilterChange()" class="filter-select">
                    <option value="">Trabajo: Todos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['nombre']); ?>">
                            <?php echo $t['codigo_trabajo'] ? "[{$t['codigo_trabajo']}] " : ""; ?>
                            <?php echo htmlspecialchars($t['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" id="filterCuadrilla" value="">
                <input type="hidden" id="filterTrabajo" value="">
            <?php endif; ?>
        </div>

        <!-- Bulk Actions Toolbar (Themed) -->
        <div id="bulkActions" class="card"
            style="display: none; margin-bottom: 20px; padding: 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; border-radius: var(--border-radius-md);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: bold; color: var(--accent-primary);"><i class="fas fa-check-double"></i>
                    Seleccionados:
                    <span id="selectedCount">0</span></span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">

                <div style="display: flex; gap: 5px; align-items: center; padding-right: 15px; margin-right: 10px;">
                    <select id="bulkCuadrilla" class="form-control-sm" style="min-width: 150px; padding: 8px;">
                        <option value="">-- Asignar Cuadrilla --</option>
                        <?php foreach ($cuadrillas as $c): ?>
                            <option value="<?php echo $c['id_cuadrilla']; ?>">
                                <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" id="bulkFecha" class="form-control-sm" value="<?php echo date('Y-m-d'); ?>"
                        style="padding: 6px;">
                </div>

                <select id="bulkStatus" class="form-control-sm" style="min-width: 150px; padding: 8px;">
                    <option value="">-- Cambiar Estado --</option>
                    <option value="Sin Programar">Sin Programar</option>
                    <option value="Programación Solicitada">Programación Solicitada</option>
                    <option value="Programado">Programado</option>
                    <option value="Ejecución">Ejecución</option>
                    <option value="Ejecutado">Ejecutado</option>
                    <option value="Precertificada">Precertificada</option>
                    <option value="Aprobado por inspector">Aprobado por inspector</option>
                    <option value="Retrabajo">Retrabajo</option>
                    <option value="Finalizado">Finalizado</option>
                </select>
                <button onclick="applyUnifiedActions()" class="btn btn-primary" style="min-height: 40px;">
                    <i class="fas fa-check-double"></i> Aplicar Cambios
                </button>
            </div>
        </div>

        <!-- Tabla de ODTs (DESKTOP) -->
        <div class="card desktop-table" style="padding: 15px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--color-primary-dark); color: white;">
                        <th style="padding: 12px; text-align: center; width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()"
                                style="transform: scale(1.3);">
                        </th>
                        <th style="padding: 12px; text-align: left; width: 120px;">Nro ODT</th>
                        <th style="padding: 12px; text-align: left;">Dirección</th>
                        <th style="padding: 12px; text-align: left;">Tipo de Trabajo</th>
                        <?php if ($rolActual !== 'Inspector ASSA'): ?>
                        <th style="padding: 12px; text-align: center; width: 120px;">Estado</th>
                        <?php endif; ?>
                        <th style="padding: 12px; text-align: center; width: 100px;">Prioridad</th>
                        <th style="padding: 12px; text-align: left; width: 110px;">Vencimiento</th>
                        <th style="padding: 12px; text-align: center; width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="odtTableBody">
                    <?php if (empty($odts)): ?>
                        <tr>
                            <td colspan="8" style="padding: 40px; text-align: center; color: #999;">
                                <i class="fas fa-clipboard"
                                    style="font-size: 3em; margin-bottom: 15px; display: block;"></i>
                                No hay ODTs registradas
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($odts as $o):
                            // [!] VISUAL: Calcular alerta de vencimiento (Custom User Request)
                            // Verde: > 5 días | Amarillo: 3-5 días | Rojo: < 3 días
                            $vencBadge = '';
                            $diasRestantes = null;

                            if (!empty($o['fecha_vencimiento']) && $o['estado_gestion'] !== 'Finalizado') {
                                $venc = new DateTime($o['fecha_vencimiento']);
                                $diff = $hoy->diff($venc);
                                $diasRestantes = $venc < $hoy ? -$diff->days : $diff->days;

                                if ($diasRestantes < 3) {
                                    $color = '#d32f2f'; // Rojo
                                    $bg = '#ffebee';
                                    $icon = '🔴';
                                } elseif ($diasRestantes <= 5) {
                                    $color = '#f57c00'; // Amarillo/Naranja
                                    $bg = '#fff3e0';
                                    $icon = '🟡';
                                } else {
                                    $color = '#2e7d32'; // Verde
                                    $bg = '#e8f5e9';
                                    $icon = '🟢';
                                }

                                $vencBadge = "
                                <div style='background: $bg; color: $color; border: 1px solid $color; padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 0.8em; display: inline-flex; align-items: center; gap: 5px; width: fit-content;'>
                                    <span>$icon</span>
                                    <span>" . ($diasRestantes < 0 ? "Vencida ($diasRestantes d)" : "$diasRestantes días") . "</span>
                                </div>";
                            }

                            // [!] VISUAL: Colores Personalizados por Cuadrilla
                            $rowStyle = "border-bottom: 1px solid #333;"; // Default dark mode border
                            $squadTitle = "";
                            if (!empty($o['id_cuadrilla'])) {
                                // Usar color personalizado DB o fallback
                                $baseColor = $o['color_hex'] ?? '#2196F3';

                                // Convertir HEX a RGBA para opacidad
                                list($r, $g, $b) = sscanf($baseColor, "#%02x%02x%02x");
                                $bgColor = "rgba($r, $g, $b, 0.18)"; // 18% opacity
                                $borderColor = $baseColor;

                                // Usar !important para forzar el color sobre cualquier estilo de tabla base
                                $rowStyle = "background-color: $bgColor !important; border-left: 5px solid $borderColor !important; border-bottom: 1px solid #444;";
                                $squadTitle = "title='👷 Asignada a: " . htmlspecialchars($o['nombre_cuadrilla']) . "'";
                            }

                            // Colores por estado
                            $estadoColors = [
                                'Sin Programar' => ['bg' => '#fff3e0', 'color' => '#e65100'],
                                'Programación Solicitada' => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
                                'Programado' => ['bg' => '#f3e5f5', 'color' => '#7b1fa2'],
                                'Ejecución' => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
                                'Ejecutado' => ['bg' => '#f1f8e9', 'color' => '#33691e'],
                                'Precertificada' => ['bg' => '#e0f2f1', 'color' => '#00796b'],
                                'Finalizado' => ['bg' => '#f5f5f5', 'color' => '#616161'],
                                'Aprobado por inspector' => ['bg' => '#e0f7fa', 'color' => '#006064'],
                                'Retrabajo' => ['bg' => '#fbe9e7', 'color' => '#bf360c'],
                                'Postergado' => ['bg' => '#fff8e1', 'color' => '#ff8f00']
                            ];
                            $ec = $estadoColors[$o['estado_gestion']] ?? ['bg' => '#eee', 'color' => '#666'];
                            ?>
                            <tr class="odt-row <?php echo $vencClass; ?>"
                                data-search="<?php echo strtolower($o['nro_odt_assa'] . ' ' . $o['direccion'] . ' ' . ($o['inspector'] ?? '') . ' ' . ($o['nombre_cuadrilla'] ?? '')); ?>"
                                data-estado="<?php echo $o['estado_gestion']; ?>"
                                data-prioridad="<?php echo $o['prioridad']; ?>"
                                data-trabajo="<?php echo htmlspecialchars($o['tipo_trabajo'] ?: ''); ?>"
                                data-cuadrilla="<?php echo htmlspecialchars($o['nombre_cuadrilla'] ?? 'SIN_ASIGNAR'); ?>"
                                style="<?php echo $rowStyle; ?> <?php echo ($rolActual === 'Inspector ASSA') ? 'cursor:pointer;' : ''; ?>"
                                <?php echo $squadTitle; ?>
                                <?php if ($rolActual === 'Inspector ASSA'): ?>onclick="window.location='form.php?id=<?php echo $o['id_odt']; ?>'"<?php endif; ?>>
                                <td style="padding: 12px; text-align: center;">
                                    <input type="checkbox" class="odt-checkbox" value="<?php echo $o['id_odt']; ?>"
                                        onclick="updateBulkActionUI()" style="transform: scale(1.3);">
                                </td>
                                <td style="padding: 12px;">
                                    <strong><?php echo htmlspecialchars($o['nro_odt_assa']); ?></strong>
                                </td>
                                <td style="padding: 12px;">
                                    <div style="font-size: 0.95em;"><?php echo htmlspecialchars($o['direccion'] ?: '-'); ?></div>
                                    <?php if (!empty($o['fecha_programada'])): ?>
                                    <div style="font-size: 0.7em; color: var(--accent-primary); margin-top: 3px;">
                                        <i class="fas fa-calendar-check"></i> Asignada: <?php echo date('d/m/Y', strtotime($o['fecha_programada'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <div class="work-pill">
                                        <?php echo $o['codigo_trabajo'] ? "<strong>[{$o['codigo_trabajo']}]</strong> " : ""; ?>
                                        <?php echo htmlspecialchars($o['tipo_trabajo'] ?: '-'); ?>
                                    </div>
                                </td>
                                <?php if ($rolActual !== 'Inspector ASSA'): ?>
                                <td style="padding: 12px; text-align: center;">
                                    <span class="status-pill"
                                        style="background: <?php echo $ec['bg']; ?>; color: <?php echo $ec['color']; ?>;">
                                        <?php echo $o['estado_gestion']; ?>
                                    </span>
                                </td>
                                <?php endif; ?>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($o['prioridad'] === 'Urgente'): ?>
                                        <span style="color: #d32f2f; font-weight: bold; font-size: 0.85em;">🔴 Urgente</span>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 0.85em;">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php if ($o['fecha_vencimiento']): ?>
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <?php echo $vencBadge; ?>
                                            <div style="font-size: 0.75em; color: #666; margin-left: 5px;">
                                                <?php echo date('d/m/Y', strtotime($o['fecha_vencimiento'])); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <?php if ($rolActual === 'Inspector ASSA' && $o['estado_gestion'] === 'Ejecutado'): ?>
                                            <button onclick="updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Aprobado por inspector')"
                                                class="btn btn-success"
                                                style="min-height: 35px; min-width: 35px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                                title="Aprobar (OK)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Retrabajo')"
                                                class="btn btn-warning"
                                                style="min-height: 35px; min-width: 35px; padding: 0; display: flex; align-items: center; justify-content: center; color: white;"
                                                title="Solicitar Retrabajo">
                                                <i class="fas fa-wrench"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($rolActual === 'Jefe de Cuadrilla'): ?>
                                            <?php if (in_array($o['estado_gestion'], ['Programado', 'Postergado', 'Retrabajo'])): ?>
                                                <button onclick="updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Ejecución')"
                                                    class="btn btn-info"
                                                    style="min-height: 35px; min-width: 35px; padding: 0; display: flex; align-items: center; justify-content: center; color: white;"
                                                    title="Iniciar Ejecución">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php elseif ($o['estado_gestion'] === 'Ejecución'): ?>
                                                <button onclick="updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Ejecutado')"
                                                    class="btn btn-success"
                                                    style="min-height: 35px; min-width: 35px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                                    title="Finalizar (Ejecutado)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Postergado')"
                                                    class="btn btn-warning"
                                                    style="min-height: 35px; min-width: 35px; padding: 0; display: flex; align-items: center; justify-content: center; color: white;"
                                                    title="Postergar">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($rolActual !== 'Inspector ASSA'): ?>
                                        <a href="form.php?id=<?php echo $o['id_odt']; ?>" class="btn btn-outline"
                                            style="min-height: 35px; min-width: 35px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button
                                            onclick="confirmDelete(<?php echo $o['id_odt']; ?>, '<?php echo addslashes($o['nro_odt_assa']); ?>')"
                                            class="btn"
                                            style="min-height: 35px; min-width: 35px; padding: 0; background: #fee; color: #d32f2f; border: 1px solid #fcc;"
                                            title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tarjetas de ODTs (MOBILE) -->
        <div class="mobile-cards" id="mobileCardsContainer">
            <?php if (empty($odts)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    <i class="fas fa-clipboard" style="font-size: 3em; margin-bottom: 15px; display: block;"></i>
                    No hay ODTs registradas
                </div>
            <?php else: ?>
                <?php foreach ($odts as $o):
                    $estadoColors = [
                        'Sin Programar' => ['bg' => '#fff3e0', 'color' => '#e65100'],
                        'Programación Solicitada' => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
                        'Programado' => ['bg' => '#f3e5f5', 'color' => '#7b1fa2'],
                        'Ejecución' => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
                        'Ejecutado' => ['bg' => '#f1f8e9', 'color' => '#33691e'],
                        'Precertificada' => ['bg' => '#e0f2f1', 'color' => '#00796b'],
                        'Finalizado' => ['bg' => '#f5f5f5', 'color' => '#616161'],
                        'Aprobado por inspector' => ['bg' => '#e0f7fa', 'color' => '#006064'],
                        'Retrabajo' => ['bg' => '#fbe9e7', 'color' => '#bf360c'],
                        'Postergado' => ['bg' => '#fff8e1', 'color' => '#ff8f00']
                    ];
                    $ec = $estadoColors[$o['estado_gestion']] ?? ['bg' => '#eee', 'color' => '#666'];
                    $esUrgente = $o['prioridad'] === 'Urgente';
                    ?>
                    <div class="mobile-odt-card <?php echo $esUrgente ? 'urgente' : ''; ?>"
                        data-search="<?php echo strtolower($o['nro_odt_assa'] . ' ' . $o['direccion']); ?>"
                        data-estado="<?php echo $o['estado_gestion']; ?>" data-prioridad="<?php echo $o['prioridad']; ?>"
                        <?php if ($rolActual === 'Inspector ASSA'): ?>onclick="window.location='form.php?id=<?php echo $o['id_odt']; ?>'" style="cursor:pointer;"<?php endif; ?>>

                        <div class="mobile-odt-header">
                            <div class="mobile-odt-nro">
                                <?php echo $esUrgente ? '🔴 ' : ''; ?>
                                <?php echo htmlspecialchars($o['nro_odt_assa']); ?>
                            </div>
                            <?php if ($rolActual !== 'Inspector ASSA'): ?>
                            <span class="mobile-odt-estado"
                                style="background: <?php echo $ec['bg']; ?>; color: <?php echo $ec['color']; ?>;">
                                <?php echo $o['estado_gestion']; ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-odt-direccion">
                            📍 <?php echo htmlspecialchars($o['direccion'] ?: 'Sin dirección'); ?>
                        </div>

                        <div class="mobile-odt-meta">
                            <?php if ($o['tipo_trabajo']): ?>
                                <span><i class="fas fa-tools"></i> <?php echo htmlspecialchars($o['tipo_trabajo']); ?></span>
                            <?php endif; ?>
                            <?php if ($o['fecha_vencimiento']): ?>
                                <span><i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y', strtotime($o['fecha_vencimiento'])); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($o['fecha_programada'])): ?>
                                <span style="color: var(--accent-primary);">
                                    <i class="fas fa-calendar-check"></i> Asignada: <?php echo date('d/m/Y', strtotime($o['fecha_programada'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-odt-actions">
                            <?php if ($rolActual === 'Inspector ASSA' && $o['estado_gestion'] === 'Ejecutado'): ?>
                                <button onclick="event.stopPropagation(); updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Aprobado por inspector')"
                                    class="btn btn-success" style="min-height: 44px;">
                                    <i class="fas fa-check"></i> Aprobar
                                </button>
                                <button onclick="event.stopPropagation(); updateOdtStatus(<?php echo $o['id_odt']; ?>, 'Retrabajo')" class="btn btn-warning"
                                    style="min-height: 44px; color: white;">
                                    <i class="fas fa-wrench"></i> Retrabajo
                                </button>
                            <?php endif; ?>

                            <?php if ($rolActual !== 'Inspector ASSA'): ?>
                            <a href="form.php?id=<?php echo $o['id_odt']; ?>" class="btn btn-outline" style="min-height: 44px;">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="noResults" style="display:none; padding: 30px; text-align: center; color: #999;">
            No se encontraron ODTs con los filtros aplicados.
        </div>
    </div>

    <style>
        /* [!] MOBILE: Prevenir overflow horizontal */
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

        /* [→] EDITAR INTERFAZ: Estilos PWA Mobile-First */
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

        /* Alertas de vencimiento */
        .odt-card.vencida {
            background: #ffebee !important;
        }

        .odt-card.por-vencer {
            background: #fff8e1 !important;
        }

        .odt-card .vencida {
            color: #d32f2f;
            font-weight: bold;
        }

        .odt-card .por-vencer {
            color: #f57c00;
            font-weight: bold;
        }

        .odt-card .proxima {
            color: #fbc02d;
        }

        /* Botones táctiles */
        .btn {
            min-height: 44px;
        }

        /* [✓] NEW: Estilos para Pastillas en Tabla */
        .work-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.8em;
            font-weight: 500;
            border: 1px solid #c7d2fe;
            white-space: normal;
            max-width: 250px;
            line-height: 1.4;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            white-space: nowrap;
            font-weight: 500;
            display: inline-block;
        }

        /* Estilos para Selects de Filtro (Modo Oscuro optimizado) */
        .filter-select {
            width: 100%;
            height: 46px;
            /* Altura fija para consistencia táctil y visual */
            padding: 0 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 15px;
            background-color: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            transition: all 0.2s ease;
            cursor: pointer;
            outline: none;
            box-shadow: var(--shadow-sm);
            box-sizing: border-box;
        }

        .filter-select:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.15);
        }

        /* Ajuste para el placeholder del input */
        .filter-select::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.8;
        }

        /* [!] ARCH: Reset select arrow for dark mode visibility */
        select.filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364b5f6' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        /* Métricas en una sola fila compacta (Sin scroll) */
        /* Métricas responsive: 4 columnas en desktop, 2 en móvil */
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .metrics-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .metric-mini {
                padding: 12px !important;
            }

            .metric-icon {
                width: 35px !important;
                height: 35px !important;
                min-width: 35px;
                font-size: 1em !important;
            }

            .metric-val {
                font-size: 1.2em !important;
            }

            .metric-lbl {
                font-size: 0.65rem !important;
            }

            .container-fluid {
                padding: 0 10px !important;
            }

            /* OCULTAR TABLA EN MÓVIL */
            .desktop-table {
                display: none !important;
            }

            /* MOSTRAR TARJETAS EN MÓVIL */
            .mobile-cards {
                display: block !important;
            }

            .mobile-odt-card {
                background: var(--bg-card);
                border-radius: var(--border-radius-md);
                padding: 15px;
                margin-bottom: 12px;
                border-left: 4px solid var(--accent-primary);
                box-shadow: var(--shadow-sm);
            }

            .mobile-odt-card.urgente {
                border-left-color: #d32f2f;
                background: rgba(211, 47, 47, 0.05);
            }

            .mobile-odt-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 10px;
            }

            .mobile-odt-nro {
                font-weight: 700;
                font-size: 1.1em;
                color: var(--text-primary);
            }

            .mobile-odt-estado {
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 0.75em;
                font-weight: 600;
            }

            .mobile-odt-direccion {
                color: var(--text-secondary);
                font-size: 0.9em;
                margin-bottom: 10px;
                line-height: 1.4;
            }

            .mobile-odt-meta {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                font-size: 0.8em;
                color: var(--text-muted);
                margin-bottom: 12px;
            }

            .mobile-odt-meta span {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .mobile-odt-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .mobile-odt-actions .btn {
                flex: 1;
                min-width: 80px;
                justify-content: center;
            }
        }

        /* Desktop: mostrar tabla, ocultar cards */
        @media (min-width: 769px) {
            .mobile-cards {
                display: none !important;
            }
        }

        .metric-mini {
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 18px !important;
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--bg-card);
            border: 1px solid rgba(100, 181, 246, 0.1);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .metric-mini:hover {
            transform: translateY(-4px);
            background: var(--bg-secondary);
            border-color: var(--accent-primary);
            box-shadow: var(--glow-primary);
        }

        .metric-icon {
            width: 45px !important;
            height: 45px !important;
            min-width: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25em !important;
        }

        .metric-content {
            display: flex;
            flex-direction: column;
        }

        .metric-val {
            font-size: 1.5em !important;
            font-weight: 700;
            line-height: 1;
            color: var(--text-primary);
        }

        .metric-lbl {
            font-size: 0.75rem !important;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }
    </style>

    <script>
        // [!] ARCH: Detección de conexión
        function updateConnectionStatus() {
            document.body.classList.toggle('offline', !navigator.onLine);
        }
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        updateConnectionStatus();

        // [!] PWA-OFFLINE: Mostrar pendientes
        function updatePendingBadge() {
            const pending = JSON.parse(localStorage.getItem('odt_pending_sync') || '[]');
            const badge = document.getElementById('pendingBadge');
            const count = document.getElementById('pendingCount');
            if (pending.length > 0) {
                badge.style.display = 'block';
                count.textContent = pending.length;
            } else {
                badge.style.display = 'none';
            }
        }
        updatePendingBadge();

        // Variables globales de filtro
        let currentFilters = {
            search: '',
            estado: '',
            trabajo: '',
            cuadrilla: '',
            prioridad: ''
        };

        // [✓] AUDIT: Filtrar ODTs
        function filterODTs() {
            currentFilters.search = document.getElementById('searchInput').value.toLowerCase();
            currentFilters.estado = document.getElementById('filterEstado').value;
            currentFilters.trabajo = document.getElementById('filterTrabajo').value;
            currentFilters.cuadrilla = document.getElementById('filterCuadrilla').value;

            const rows = document.querySelectorAll('.odt-row');
            let visible = 0;

            rows.forEach(row => {
                const matchSearch = row.dataset.search.includes(currentFilters.search);
                const matchEstado = !currentFilters.estado || row.dataset.estado === currentFilters.estado;
                const matchTrabajo = !currentFilters.trabajo || row.dataset.trabajo === currentFilters.trabajo;
                const matchPrioridad = !currentFilters.prioridad || row.dataset.prioridad === currentFilters.prioridad;

                let matchCuadrilla = true;
                if (currentFilters.cuadrilla === 'SIN_ASIGNAR') {
                    matchCuadrilla = row.dataset.cuadrilla === 'SIN_ASIGNAR';
                } else if (currentFilters.cuadrilla) {
                    matchCuadrilla = row.dataset.cuadrilla === currentFilters.cuadrilla;
                }

                const show = matchSearch && matchEstado && matchTrabajo && matchPrioridad && matchCuadrilla;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
        }

        // [✓] NEW: Manejo de cambios en dropdowns
        function handleFilterChange() {
            filterODTs();
        }

        // [✓] NEW: Filtro rápido desde métricas
        function setQuickFilter(type, value) {
            if (type === 'estado') {
                const select = document.getElementById('filterEstado');
                select.value = value;
                currentFilters.prioridad = ''; // Limpiar prioridad
            } else if (type === 'prioridad') {
                currentFilters.prioridad = value;
                document.getElementById('filterEstado').value = '';
            }

            filterODTs();
        }

        // [✓] BULK ACTIONS: Checkbox logic
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.odt-checkbox');
            checkboxes.forEach(cb => {
                if (cb.closest('.odt-row').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
            updateBulkActionUI();
        }

        function updateBulkActionUI() {
            const checkboxes = document.querySelectorAll('.odt-checkbox:checked');
            const bulkDiv = document.getElementById('bulkActions');
            const countSpan = document.getElementById('selectedCount');

            if (checkboxes.length > 0) {
                bulkDiv.style.display = 'flex';
                countSpan.textContent = checkboxes.length;
            } else {
                bulkDiv.style.display = 'none';
                document.getElementById('selectAll').checked = false;
            }
        }

        // [✓] NEW: Actualización rápida de estado (Inspector)
        function updateOdtStatus(id, newStatus) {
            if (!confirm('¿Está seguro de cambiar el estado a: ' + newStatus + '?')) return;

            fetch('bulk_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ids: [id],
                    estado: newStatus
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar para ver cambios
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
        }

        async function applyUnifiedActions() {
            const checkboxes = document.querySelectorAll('.odt-checkbox:checked');

            // Get values
            const cuadrillaId = document.getElementById('bulkCuadrilla').value;
            const fecha = document.getElementById('bulkFecha').value;
            const newStatus = document.getElementById('bulkStatus').value;

            if (!checkboxes.length) {
                alert("Seleccione al menos una ODT");
                return;
            }

            // Check if ANY action is selected
            if (!cuadrillaId && !newStatus) {
                alert('Seleccione una Cuadrilla para asignar O un Estado para cambiar.');
                return;
            }

            // Confirm
            let msg = `¿Aplicar cambios a ${checkboxes.length} ODTs?`;
            if (cuadrillaId && newStatus) {
                msg = `¿Asignar cuadrilla y cambiar estado a ${checkboxes.length} ODTs?`;
            } else if (cuadrillaId) {
                msg = `¿Asignar ${checkboxes.length} ODTs a la cuadrilla seleccionada?`;
            } else if (newStatus) {
                msg = `¿Cambiar estado de ${checkboxes.length} ODTs a "${newStatus}"?`;
            }

            if (!confirm(msg)) return;

            const ids = Array.from(checkboxes).map(cb => cb.value);

            try {
                const response = await fetch('bulk_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'unified_update',
                        ids: ids,
                        cuadrilla: cuadrillaId,
                        fecha: fecha,
                        estado: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Cambios aplicados correctamente.');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al procesar la solicitud.');
            }
        }

        // [✓] AUDIT: Confirmación triple para eliminar
        function confirmDelete(id, nro) {
            if (!confirm('¿Eliminar ODT "' + nro + '"?')) return;
            if (!confirm('⚠️ Esta acción no se puede deshacer. ¿Confirmar eliminación?')) return;

            window.location.href = 'delete.php?id=' + id;
        }

        // =============================================
        // EXPORTAR PDF + IMPRIMIR
        // =============================================
        function getVisibleOdtIds() {
            // Obtener IDs de las filas visibles (respetando filtros)
            const rows = document.querySelectorAll('.odt-row');
            const ids = [];
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cb = row.querySelector('.odt-checkbox');
                    if (cb) ids.push(cb.value);
                }
            });
            return ids;
        }

        function exportTablePDF() {
            // Priorizar seleccionados, luego visibles
            const checked = document.querySelectorAll('.odt-checkbox:checked');
            let ids;
            if (checked.length > 0) {
                ids = Array.from(checked).map(cb => cb.value).join(',');
            } else {
                ids = getVisibleOdtIds().join(',');
            }
            if (!ids) { alert('No hay ODTs visibles para exportar.'); return; }
            window.open('print_odts.php?mode=pdf&ids=' + ids, '_blank');
        }

        function printODTs() {
            const checked = document.querySelectorAll('.odt-checkbox:checked');
            if (checked.length > 0) {
                const ids = Array.from(checked).map(cb => cb.value).join(',');
                window.open('print_odts.php?ids=' + ids, '_blank');
            } else {
                if (confirm('No hay ODTs seleccionadas. ¿Imprimir todas las finalizadas/aprobadas?')) {
                    window.open('print_odts.php', '_blank');
                }
            }
        }
    </script>

    <?php require_once '../../includes/footer.php'; ?>