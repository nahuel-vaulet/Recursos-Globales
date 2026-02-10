<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch Vehicles with Cuadrilla assignment
$sql = "SELECT v.*, c.nombre_cuadrilla 
        FROM vehiculos v
        LEFT JOIN cuadrillas c ON c.id_vehiculo_asignado = v.id_vehiculo
        ORDER BY v.patente ASC";
$vehiculos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Constants
$tipos = ['Camioneta', 'Utilitario', 'Camión', 'Moto'];
$estados = ['Operativo', 'En Taller', 'Baja'];

// Metrics
$total = count($vehiculos);
$operativos = 0;
$en_taller = 0;
$alertas = 0;
$hoy = date('Y-m-d');
$en_30_dias = date('Y-m-d', strtotime('+30 days'));

foreach ($vehiculos as &$v) {
    if ($v['estado'] === 'Operativo')
        $operativos++;
    if ($v['estado'] === 'En Taller')
        $en_taller++;

    // Check for alerts
    $v['_alertas'] = [];
    if (!empty($v['vencimiento_vtv']) && $v['vencimiento_vtv'] <= $en_30_dias) {
        $v['_alertas'][] = 'VTV ' . ($v['vencimiento_vtv'] <= $hoy ? 'vencida' : 'por vencer');
        if ($v['vencimiento_vtv'] <= $hoy)
            $alertas++;
    }
    if (!empty($v['vencimiento_seguro']) && $v['vencimiento_seguro'] <= $en_30_dias) {
        $v['_alertas'][] = 'Seguro ' . ($v['vencimiento_seguro'] <= $hoy ? 'vencido' : 'por vencer');
        if ($v['vencimiento_seguro'] <= $hoy)
            $alertas++;
    }
}
unset($v);
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;"><i class="fas fa-truck"></i> Gestión de Vehículos</h2>
            <p style="margin: 5px 0 0; color: #666;">Flota y Documentación</p>
        </div>
        <a href="form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nuevo Vehículo</a>
    </div>

    <!-- KPI Cards con Glow -->
    <div class="metrics-row">
        <div class="metric-mini">
            <div class="metric-icon primary">
                <i class="fas fa-truck"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $total; ?></span>
                <span class="metric-lbl">Total Vehículos</span>
            </div>
        </div>
        <div class="metric-mini">
            <div class="metric-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $operativos; ?></span>
                <span class="metric-lbl">Operativos</span>
            </div>
        </div>
        <div class="metric-mini">
            <div class="metric-icon warning">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $en_taller; ?></span>
                <span class="metric-lbl">En Taller</span>
            </div>
        </div>
        <?php if ($alertas > 0): ?>
            <div class="metric-mini">
                <div class="metric-icon danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="metric-content">
                    <span class="metric-val"><?php echo $alertas; ?></span>
                    <span class="metric-lbl">Vencidos</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Card -->
    <div class="card" style="border-top: 4px solid var(--color-primary);">

        <!-- Filters -->
        <div class="filter-bar"
            style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Patente..."
                    class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Tipo</label>
                <select id="filterTipo" onchange="filterTable()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Estado</label>
                <select id="filterEstado" onchange="filterTable()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?php echo $e; ?>"><?php echo $e; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="display: flex; align-items: flex-end;">
                <button onclick="resetFilters()" class="btn btn-outline btn-sm" title="Limpiar Filtros">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table class="table" id="vehiculosTable">
                <thead>
                    <tr>
                        <th>Patente</th>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>VTV</th>
                        <th>Seguro</th>
                        <th>Cuadrilla</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehiculos)): ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 40px; color: #999;">
                                <i class="fas fa-truck" style="font-size: 2em; margin-bottom: 10px;"></i><br>
                                No hay vehículos registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehiculos as $v): ?>
                            <tr data-patente="<?php echo strtolower($v['patente']); ?>" data-tipo="<?php echo $v['tipo']; ?>"
                                data-estado="<?php echo $v['estado']; ?>">
                                <td>
                                    <code
                                        style="font-size: 1.1em; font-weight: bold;"><?php echo htmlspecialchars($v['patente']); ?></code>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo']); ?>
                                    </div>
                                    <?php if ($v['anio']): ?>
                                        <small style="color: #888;"><?php echo $v['anio']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-tipo <?php echo strtolower($v['tipo']); ?>">
                                        <?php
                                        $iconos = ['Camioneta' => 'fa-truck-pickup', 'Utilitario' => 'fa-shuttle-van', 'Camión' => 'fa-truck', 'Moto' => 'fa-motorcycle'];
                                        echo '<i class="fas ' . ($iconos[$v['tipo']] ?? 'fa-car') . '"></i> ';
                                        echo $v['tipo'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-estado <?php echo strtolower(str_replace(' ', '-', $v['estado'])); ?>">
                                        <?php echo $v['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['vencimiento_vtv']):
                                        $vtvClass = '';
                                        if ($v['vencimiento_vtv'] <= $hoy)
                                            $vtvClass = 'vencido';
                                        elseif ($v['vencimiento_vtv'] <= $en_30_dias)
                                            $vtvClass = 'por-vencer';
                                        ?>
                                        <span class="fecha-venc <?php echo $vtvClass; ?>">
                                            <?php echo date('d/m/Y', strtotime($v['vencimiento_vtv'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #ccc;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['vencimiento_seguro']):
                                        $segClass = '';
                                        if ($v['vencimiento_seguro'] <= $hoy)
                                            $segClass = 'vencido';
                                        elseif ($v['vencimiento_seguro'] <= $en_30_dias)
                                            $segClass = 'por-vencer';
                                        ?>
                                        <span class="fecha-venc <?php echo $segClass; ?>">
                                            <?php echo date('d/m/Y', strtotime($v['vencimiento_seguro'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #ccc;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['nombre_cuadrilla']): ?>
                                        <span class="badge-cuadrilla">
                                            <i class="fas fa-hard-hat"></i> <?php echo $v['nombre_cuadrilla']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="view.php?id=<?php echo $v['id_vehiculo']; ?>" class="btn-icon" title="Ver Ficha">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="form.php?id=<?php echo $v['id_vehiculo']; ?>" class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        onclick="confirmDelete(<?php echo $v['id_vehiculo']; ?>, '<?php echo addslashes($v['patente']); ?>')"
                                        class="btn-icon btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="noResults" style="display:none; padding: 20px; text-align: center; color: #999;">
            No se encontraron vehículos con los filtros aplicados.
        </div>
    </div>
</div>

<style>
    .metrics-row {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .metric-mini {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        min-width: 130px;
    }

    .metric-mini i {
        font-size: 1.3em;
    }

    .metric-val {
        font-size: 1.4em;
        font-weight: 700;
        color: #333;
    }

    .metric-lbl {
        font-size: 0.8em;
        color: #888;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }

    .filter-group label {
        font-size: 0.8em;
        font-weight: bold;
        color: #666;
        margin-bottom: 4px;
    }

    .form-control-sm {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9em;
    }

    .badge-tipo {
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .badge-tipo.camioneta {
        background: #e3f2fd;
        color: #1565c0;
    }

    .badge-tipo.utilitario {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .badge-tipo.camión {
        background: #fff3e0;
        color: #ef6c00;
    }

    .badge-tipo.moto {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-estado {
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .badge-estado.operativo {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-estado.en-taller {
        background: #fff3e0;
        color: #ef6c00;
    }

    .badge-estado.baja {
        background: #ffebee;
        color: #c62828;
    }

    .badge-cuadrilla {
        background: #e3f2fd;
        color: #1565c0;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.85em;
    }

    .fecha-venc {
        font-size: 0.9em;
    }

    .fecha-venc.vencido {
        color: #c62828;
        font-weight: bold;
        background: #ffebee;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .fecha-venc.por-vencer {
        color: #ef6c00;
        background: #fff3e0;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .btn-icon {
        background: none;
        border: 1px solid #eee;
        border-radius: 6px;
        padding: 6px 10px;
        color: var(--color-primary);
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 0 2px;
        transition: all 0.2s;
    }

    .btn-icon:hover {
        background: var(--color-primary);
        color: white;
    }

    .btn-icon.btn-danger {
        color: #dc3545;
        border-color: #fdd;
    }

    .btn-icon.btn-danger:hover {
        background: #dc3545;
        color: white;
    }

    .text-center {
        text-align: center;
    }

    /* ==========================================
       RESPONSIVE VEHÍCULOS - MÓVIL
       ========================================== */
    @media (max-width: 767px) {

        /* Métricas en 2 columnas */
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .metric-mini {
            min-width: auto;
            padding: 12px 15px;
            flex-direction: column;
            text-align: center;
            gap: 5px;
        }

        .metric-val {
            font-size: 1.2em;
        }

        .metric-lbl {
            font-size: 0.7em;
        }

        /* Filtros verticales */
        .filter-bar {
            flex-direction: column !important;
            gap: 10px !important;
            padding: 12px !important;
        }

        .filter-group {
            min-width: 100%;
        }

        .form-control-sm {
            width: 100%;
            padding: 12px;
            font-size: 16px;
        }

        /* Tabla a cards en móvil */
        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 15px;
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            border-left: 4px solid var(--color-primary);
            padding: 15px;
        }

        .table tbody tr td {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .table tbody tr td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px;
        }

        .table tbody tr td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #666;
            font-size: 0.85em;
        }

        /* Botones táctiles */
        .btn-icon {
            padding: 10px 14px;
            min-height: 44px;
            min-width: 44px;
        }

        /* Header de página móvil */
        .container-fluid>div:first-child {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 15px !important;
        }

        .container-fluid h2 {
            font-size: 1.3rem;
        }

        .container-fluid p {
            font-size: 0.85em;
        }
    }
</style>

<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const tipo = document.getElementById('filterTipo').value;
        const estado = document.getElementById('filterEstado').value;

        const rows = document.querySelectorAll('#vehiculosTable tbody tr[data-patente]');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = row.dataset.patente.includes(search);
            const matchTipo = !tipo || row.dataset.tipo === tipo;
            const matchEstado = !estado || row.dataset.estado === estado;

            const show = matchSearch && matchTipo && matchEstado;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterTipo').value = '';
        document.getElementById('filterEstado').value = '';
        filterTable();
    }

    function confirmDelete(id, patente) {
        if (confirm('¿Eliminar vehículo "' + patente + '"?\n\nEsta acción no se puede deshacer.')) {
            window.location.href = 'delete.php?id=' + id;
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>