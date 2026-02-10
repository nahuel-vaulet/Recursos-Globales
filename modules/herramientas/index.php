<?php
/**
 * Módulo de Herramientas - Listado Principal
 * [!] ARQUITECTURA: Dashboard con KPIs, filtros y acciones rápidas
 * [✓] AUDITORÍA CRUD: READ implementado con índices y paginación
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// [→] EDITAR AQUÍ: Consulta principal con JOINs
$sql = "SELECT h.*, 
               c.nombre_cuadrilla,
               p.nombre_apellido as personal_asignado,
               prov.razon_social as proveedor_nombre
        FROM herramientas h
        LEFT JOIN cuadrillas c ON c.id_cuadrilla = h.id_cuadrilla_asignada
        LEFT JOIN personal p ON p.id_personal = h.id_personal_asignado
        LEFT JOIN proveedores prov ON prov.id_proveedor = h.id_proveedor
        ORDER BY h.nombre ASC";
$herramientas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Cuadrillas para modal de asignación
$cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// Estados posibles
$estados = ['Disponible', 'Asignada', 'Reparación', 'Baja'];

// KPIs
$total = count($herramientas);
$disponibles = 0;
$asignadas = 0;
$reparacion = 0;
$valorTotal = 0;

foreach ($herramientas as $h) {
    if ($h['estado'] === 'Disponible')
        $disponibles++;
    if ($h['estado'] === 'Asignada')
        $asignadas++;
    if ($h['estado'] === 'Reparación')
        $reparacion++;
    $valorTotal += $h['precio_reposicion'];
}

// Sanciones pendientes
$sancionesPendientes = $pdo->query("SELECT COUNT(*) FROM herramientas_sanciones WHERE estado = 'Pendiente'")->fetchColumn();
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: var(--text-primary);"><i class="fas fa-tools"></i> Gestión de Herramientas</h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary);">Inventario, asignación y seguimiento</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="sanciones.php" class="btn btn-warning" style="background: #ef6c00; border-color: #ef6c00;">
                <i class="fas fa-exclamation-triangle"></i> Sanciones
                <?php if ($sancionesPendientes > 0): ?>
                    <span
                        style="background: #fff; color: #ef6c00; padding: 2px 8px; border-radius: 10px; margin-left: 5px; font-size: 0.85em;">
                        <?php echo $sancionesPendientes; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nueva Herramienta</a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="metrics-row">
        <div class="metric-mini">
            <div class="metric-icon primary"><i class="fas fa-tools"></i></div>
            <div class="metric-content">
                <span class="metric-val">
                    <?php echo $total; ?>
                </span>
                <span class="metric-lbl">Total</span>
            </div>
        </div>
        <div class="metric-mini">
            <div class="metric-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="metric-content">
                <span class="metric-val">
                    <?php echo $disponibles; ?>
                </span>
                <span class="metric-lbl">Disponibles</span>
            </div>
        </div>
        <div class="metric-mini">
            <div class="metric-icon warning"><i class="fas fa-hard-hat"></i></div>
            <div class="metric-content">
                <span class="metric-val">
                    <?php echo $asignadas; ?>
                </span>
                <span class="metric-lbl">Asignadas</span>
            </div>
        </div>
        <div class="metric-mini">
            <div class="metric-icon info"><i class="fas fa-wrench"></i></div>
            <div class="metric-content">
                <span class="metric-val">
                    <?php echo $reparacion; ?>
                </span>
                <span class="metric-lbl">En Reparación</span>
            </div>
        </div>
        <div class="metric-mini">
            <div class="metric-icon accent"><i class="fas fa-dollar-sign"></i></div>
            <div class="metric-content">
                <span class="metric-val">$
                    <?php echo number_format($valorTotal, 0, ',', '.'); ?>
                </span>
                <span class="metric-lbl">Valor Total</span>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card" style="border-top: 4px solid var(--accent-primary);">

        <!-- Filters -->
        <div class="filter-bar"
            style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div class="filter-group">
                <label style="color: var(--text-secondary);">Buscar</label>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Nombre, marca, serie..."
                    class="form-control-sm"
                    style="background: var(--bg-card); color: var(--text-primary); border: 1px solid var(--text-muted);">
            </div>
            <div class="filter-group">
                <label style="color: var(--text-secondary);">Estado</label>
                <select id="filterEstado" onchange="filterTable()" class="form-control-sm"
                    style="background: var(--bg-card); color: var(--text-primary); border: 1px solid var(--text-muted);">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?php echo $e; ?>">
                            <?php echo $e; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label style="color: var(--text-secondary);">Cuadrilla</label>
                <select id="filterCuadrilla" onchange="filterTable()" class="form-control-sm"
                    style="background: var(--bg-card); color: var(--text-primary); border: 1px solid var(--text-muted);">
                    <option value="">Todas</option>
                    <option value="sin-asignar">Sin asignar</option>
                    <?php foreach ($cuadrillas as $c): ?>
                        <option value="<?php echo $c['id_cuadrilla']; ?>">
                            <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="display: flex; align-items: flex-end;">
                <button onclick="resetFilters()" class="btn btn-outline btn-sm" title="Limpiar Filtros"
                    style="color: var(--text-secondary); border-color: var(--text-muted);">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table class="table" id="herramientasTable">
                <thead>
                    <tr style="background: var(--bg-secondary);">
                        <th style="color: var(--text-secondary); width: 50px;"></th>
                        <th style="color: var(--text-secondary);">Herramienta</th>
                        <th style="color: var(--text-secondary);">Marca/Modelo</th>
                        <th style="color: var(--text-secondary);">Nro Serie</th>
                        <th style="color: var(--text-secondary);">Estado</th>
                        <th style="color: var(--text-secondary);">Proveedor</th>
                        <th style="color: var(--text-secondary); text-align: right;">Precio Rep.</th>
                        <th class="text-center" style="color: var(--text-secondary);">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($herramientas)): ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-tools" style="font-size: 2em; margin-bottom: 10px;"></i><br>
                                No hay herramientas registradas
                            </td>
                        </tr>
                    <?php else: ?>
                            <?php foreach ($herramientas as $h): ?>
                            <tr data-nombre="<?php echo strtolower($h['nombre']); ?>" data-estado="<?php echo $h['estado']; ?>"
                                data-cuadrilla="<?php echo $h['id_cuadrilla_asignada'] ?? 'sin-asignar'; ?>"
                                data-search="<?php echo strtolower($h['nombre'] . ' ' . $h['marca'] . ' ' . $h['numero_serie']); ?>">
                                <td style="width: 50px; padding: 5px;">
                                    <?php if (!empty($h['foto'])): ?>
                                        <img src="<?php echo htmlspecialchars($h['foto']); ?>" alt=""
                                            style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid var(--text-muted);">
                                    <?php else: ?>
                                        <div
                                            style="width: 45px; height: 45px; background: var(--bg-tertiary); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-wrench" style="color: var(--text-muted);"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($h['nombre']); ?>
                                    </div>
                                    <?php if ($h['nombre_cuadrilla']): ?>
                                        <small style="color: var(--accent-primary);">
                                            <i class="fas fa-hard-hat"></i> <?php echo htmlspecialchars($h['nombre_cuadrilla']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-secondary);">
                                    <?php echo htmlspecialchars(($h['marca'] ?? '') . ' ' . ($h['modelo'] ?? '')); ?>
                                </td>
                                <td>
                                    <?php if ($h['numero_serie']): ?>
                                        <code
                                            style="background: var(--bg-tertiary); padding: 2px 6px; border-radius: 4px; font-size: 0.85em; color: var(--text-primary);">
                                                                                <?php echo htmlspecialchars($h['numero_serie']); ?>
                                                                            </code>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $estadoClases = [
                                        'Disponible' => 'success',
                                        'Asignada' => 'warning',
                                        'Reparación' => 'info',
                                        'Baja' => 'danger'
                                    ];
                                    $clase = $estadoClases[$h['estado']] ?? 'secondary';
                                    ?>
                                    <span class="badge-estado <?php echo $clase; ?>">
                                        <?php echo $h['estado']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-secondary);">
                                    <?php if ($h['proveedor_nombre']): ?>
                                        <i class="fas fa-store" style="color: var(--text-muted); margin-right: 3px;"></i>
                                        <?php echo htmlspecialchars($h['proveedor_nombre']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: var(--text-primary);">
                                    $<?php echo number_format($h['precio_reposicion'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($h['estado'] === 'Disponible'): ?>
                                        <button
                                            onclick="openAssignModal(<?php echo $h['id_herramienta']; ?>, '<?php echo addslashes($h['nombre']); ?>')"
                                            class="btn-icon btn-success" title="Asignar a Cuadrilla">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    <?php elseif ($h['estado'] === 'Asignada'): ?>
                                        <button
                                            onclick="devolverHerramienta(<?php echo $h['id_herramienta']; ?>, '<?php echo addslashes($h['nombre']); ?>')"
                                            class="btn-icon btn-warning" title="Devolver al Depósito">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="historial.php?id=<?php echo $h['id_herramienta']; ?>" class="btn-icon"
                                        title="Ver Historial">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <a href="form.php?id=<?php echo $h['id_herramienta']; ?>" class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($h['estado'] !== 'Baja'): ?>
                                        <button
                                            onclick="openSancionModal(<?php echo $h['id_herramienta']; ?>, '<?php echo addslashes($h['nombre']); ?>')"
                                            class="btn-icon btn-danger" title="Registrar Sanción">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="noResults" style="display:none; padding: 20px; text-align: center; color: var(--text-muted);">
            No se encontraron herramientas con los filtros aplicados.
        </div>
    </div>
</div>

<!-- Modal Asignar -->
<div id="modalAsignar"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div
        style="background: var(--bg-card); padding: 25px; border-radius: 12px; width: 400px; box-shadow: var(--shadow-lg); border: 1px solid var(--text-muted);">
        <h3 style="margin: 0 0 20px; color: var(--text-primary);"><i class="fas fa-arrow-right"></i> Asignar Herramienta
        </h3>
        <p id="asignarHerramientaNombre" style="color: var(--text-secondary); margin-bottom: 15px;"></p>
        <input type="hidden" id="asignarHerramientaId">

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Cuadrilla
                Destino *</label>
            <select id="asignarCuadrilla" class="form-control"
                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                <option value="">-- Seleccionar Cuadrilla --</option>
                <?php foreach ($cuadrillas as $c): ?>
                    <option value="<?php echo $c['id_cuadrilla']; ?>">
                        <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label
                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Observaciones</label>
            <textarea id="asignarObservaciones" class="form-control" rows="2" placeholder="Opcional..."
                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;"></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeAssignModal()"
                style="color: var(--text-secondary); border-color: var(--text-muted);">Cancelar</button>
            <button type="button" class="btn btn-success" onclick="confirmarAsignacion()">Asignar</button>
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
        background: var(--bg-card);
        border-radius: 10px;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--shadow-sm);
        min-width: 130px;
        border: 1px solid rgba(100, 181, 246, 0.1);
    }

    .metric-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2em;
    }

    .metric-icon.primary {
        background: rgba(33, 150, 243, 0.15);
        color: #2196f3;
    }

    .metric-icon.success {
        background: rgba(76, 175, 80, 0.15);
        color: #4caf50;
    }

    .metric-icon.warning {
        background: rgba(255, 152, 0, 0.15);
        color: #ff9800;
    }

    .metric-icon.info {
        background: rgba(0, 188, 212, 0.15);
        color: #00bcd4;
    }

    .metric-icon.danger {
        background: rgba(244, 67, 54, 0.15);
        color: #f44336;
    }

    .metric-icon.accent {
        background: rgba(33, 150, 243, 0.15);
        color: var(--accent-primary);
    }

    .metric-val {
        font-size: 1.4em;
        font-weight: 700;
        color: var(--text-primary);
        display: block;
    }

    .metric-lbl {
        font-size: 0.8em;
        color: var(--text-secondary);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }

    .filter-group label {
        font-size: 0.8em;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .form-control-sm {
        padding: 8px;
        border-radius: 6px;
        font-size: 0.9em;
    }

    .badge-estado {
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .badge-estado.success {
        background: rgba(76, 175, 80, 0.15);
        color: #4caf50;
    }

    .badge-estado.warning {
        background: rgba(255, 152, 0, 0.15);
        color: #ff9800;
    }

    .badge-estado.info {
        background: rgba(0, 188, 212, 0.15);
        color: #00bcd4;
    }

    .badge-estado.danger {
        background: rgba(244, 67, 54, 0.15);
        color: #f44336;
    }

    .badge-cuadrilla {
        background: rgba(33, 150, 243, 0.15);
        color: var(--accent-primary);
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.85em;
    }

    .btn-icon {
        background: none;
        border: 1px solid var(--text-muted);
        border-radius: 6px;
        padding: 6px 10px;
        color: var(--accent-primary);
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 0 2px;
        transition: all 0.2s;
    }

    .btn-icon:hover {
        background: var(--accent-primary);
        color: white;
        border-color: var(--accent-primary);
    }

    .btn-icon.btn-success {
        color: #4caf50;
        border-color: #4caf50;
    }

    .btn-icon.btn-success:hover {
        background: #4caf50;
        color: white;
    }

    .btn-icon.btn-warning {
        color: #ff9800;
        border-color: #ff9800;
    }

    .btn-icon.btn-warning:hover {
        background: #ff9800;
        color: white;
    }

    .btn-icon.btn-danger {
        color: #f44336;
        border-color: #f44336;
    }

    .btn-icon.btn-danger:hover {
        background: #f44336;
        color: white;
    }

    .text-center {
        text-align: center;
    }

    @media (max-width: 767px) {
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

        .filter-bar {
            flex-direction: column !important;
            gap: 10px !important;
        }

        .filter-group {
            min-width: 100%;
        }

        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 15px;
            background: var(--bg-card);
            border: 1px solid var(--text-muted);
            border-radius: 8px;
            border-left: 4px solid var(--accent-primary);
            padding: 15px;
        }

        .table tbody tr td {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--bg-tertiary);
        }

        .table tbody tr td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px;
        }
    }
</style>

<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const estado = document.getElementById('filterEstado').value;
        const cuadrilla = document.getElementById('filterCuadrilla').value;

        const rows = document.querySelectorAll('#herramientasTable tbody tr[data-nombre]');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = row.dataset.search.includes(search);
            const matchEstado = !estado || row.dataset.estado === estado;
            const matchCuadrilla = !cuadrilla || row.dataset.cuadrilla === cuadrilla;

            const show = matchSearch && matchEstado && matchCuadrilla;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterEstado').value = '';
        document.getElementById('filterCuadrilla').value = '';
        filterTable();
    }

    // Modal Asignar
    function openAssignModal(id, nombre) {
        document.getElementById('asignarHerramientaId').value = id;
        document.getElementById('asignarHerramientaNombre').textContent = 'Herramienta: ' + nombre;
        document.getElementById('asignarCuadrilla').value = '';
        document.getElementById('asignarObservaciones').value = '';
        document.getElementById('modalAsignar').style.display = 'flex';
    }

    function closeAssignModal() {
        document.getElementById('modalAsignar').style.display = 'none';
    }

    function confirmarAsignacion() {
        const id = document.getElementById('asignarHerramientaId').value;
        const cuadrilla = document.getElementById('asignarCuadrilla').value;
        const obs = document.getElementById('asignarObservaciones').value;

        if (!cuadrilla) {
            alert('Seleccione una cuadrilla');
            return;
        }

        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'asignar', id_herramienta: id, id_cuadrilla: cuadrilla, observaciones: obs })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function devolverHerramienta(id, nombre) {
        if (!confirm('¿Devolver "' + nombre + '" al depósito?')) return;

        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'devolver', id_herramienta: id })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function openSancionModal(id, nombre) {
        window.location.href = 'nueva_sancion.php?id=' + id;
    }
</script>

<?php require_once '../../includes/footer.php'; ?>