<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch Personal with Cuadrilla names
$sql = "SELECT p.*, c.nombre_cuadrilla 
        FROM personal p
        LEFT JOIN cuadrillas c ON p.id_cuadrilla = c.id_cuadrilla
        ORDER BY p.nombre_apellido ASC";
$personal = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Get unique roles and cuadrillas for filters
$roles = ['Oficial', 'Ayudante', 'Administrativo', 'Supervisor', 'Chofer'];
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$total_personal = count($personal);
$by_role = [];
$alertas = 0;
$hoy = date('Y-m-d');
$en_30_dias = date('Y-m-d', strtotime('+30 days'));

foreach ($personal as &$p) {
    $by_role[$p['rol']] = ($by_role[$p['rol']] ?? 0) + 1;

    // Check for alerts
    $p['_alertas'] = [];
    if (!empty($p['vencimiento_carnet_conducir']) && $p['vencimiento_carnet_conducir'] <= $en_30_dias) {
        $p['_alertas'][] = 'Carnet ' . ($p['vencimiento_carnet_conducir'] <= $hoy ? 'vencido' : 'por vencer');
        $alertas++;
    }
}
unset($p);
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;"><i class="fas fa-users"></i> Gestión de Personal</h2>
            <p style="margin: 5px 0 0; color: #666;">Legajo Digital de Empleados</p>
        </div>
        <a href="form.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Nuevo Personal</a>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-row">
        <div class="metric-mini">
            <i class="fas fa-users" style="color: #1976d2;"></i>
            <span class="metric-val"><?php echo $total_personal; ?></span>
            <span class="metric-lbl">Total</span>
        </div>
        <div class="metric-mini">
            <i class="fas fa-hard-hat" style="color: #388e3c;"></i>
            <span class="metric-val"><?php echo $by_role['Oficial'] ?? 0; ?></span>
            <span class="metric-lbl">Oficiales</span>
        </div>
        <div class="metric-mini">
            <i class="fas fa-hands-helping" style="color: #f57c00;"></i>
            <span class="metric-val"><?php echo $by_role['Ayudante'] ?? 0; ?></span>
            <span class="metric-lbl">Ayudantes</span>
        </div>
        <div class="metric-mini">
            <i class="fas fa-user-tie" style="color: #7b1fa2;"></i>
            <span
                class="metric-val"><?php echo ($by_role['Supervisor'] ?? 0) + ($by_role['Administrativo'] ?? 0); ?></span>
            <span class="metric-lbl">Admin/Sup</span>
        </div>
        <?php if ($alertas > 0): ?>
            <div class="metric-mini" style="background: #fff3e0; border-left: 3px solid #ff9800;">
                <i class="fas fa-exclamation-triangle" style="color: #ff9800;"></i>
                <span class="metric-val" style="color: #e65100;"><?php echo $alertas; ?></span>
                <span class="metric-lbl">Alertas</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Card -->
    <div class="card" style="border-top: 4px solid var(--color-primary);">

        <!-- Filters -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Nombre o DNI..."
                    class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Rol</label>
                <select id="filterRol" onchange="filterTable()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Cuadrilla</label>
                <select id="filterCuadrilla" onchange="filterTable()" class="form-control-sm">
                    <option value="">Todas</option>
                    <option value="SIN_ASIGNAR">Sin Asignar</option>
                    <?php foreach ($cuadrillas as $c): ?>
                        <option value="<?php echo $c['nombre_cuadrilla']; ?>"><?php echo $c['nombre_cuadrilla']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="flex: 0 0 auto;">
                <button onclick="resetFilters()" class="btn btn-outline btn-sm" title="Limpiar Filtros">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table class="table" id="personalTable">
                <thead>
                    <tr>
                        <th>Nombre y Apellido</th>
                        <th>DNI</th>
                        <th>Rol</th>
                        <th>Cuadrilla</th>
                        <th>Teléfono</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($personal)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 40px; color: #999;">
                                <i class="fas fa-user-slash" style="font-size: 2em; margin-bottom: 10px;"></i><br>
                                No hay personal registrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personal as $p): ?>
                            <tr data-name="<?php echo strtolower($p['nombre_apellido'] . ' ' . $p['dni']); ?>"
                                data-rol="<?php echo $p['rol']; ?>"
                                data-cuadrilla="<?php echo $p['nombre_cuadrilla'] ?? 'SIN_ASIGNAR'; ?>">
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($p['nombre_apellido']); ?></div>
                                    <?php if (!empty($p['fecha_ingreso'])): ?>
                                        <small style="color: #888;">Ingreso:
                                            <?php echo date('d/m/Y', strtotime($p['fecha_ingreso'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($p['dni']); ?></code></td>
                                <td>
                                    <span class="badge-rol <?php echo strtolower($p['rol']); ?>">
                                        <?php echo $p['rol']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['nombre_cuadrilla']): ?>
                                        <span class="badge-cuadrilla">
                                            <i class="fas fa-hard-hat"></i> <?php echo $p['nombre_cuadrilla']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['telefono_personal']): ?>
                                        <a href="tel:<?php echo $p['telefono_personal']; ?>" style="color: #666;">
                                            <i class="fas fa-phone"></i> <?php echo $p['telefono_personal']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #ccc;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($p['_alertas'])): ?>
                                        <span class="badge-alert" title="<?php echo implode(', ', $p['_alertas']); ?>">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-ok"><i class="fas fa-check"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="form.php?id=<?php echo $p['id_personal']; ?>" class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        onclick="confirmDelete(<?php echo $p['id_personal']; ?>, '<?php echo addslashes($p['nombre_apellido']); ?>')"
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
            No se encontró personal con los filtros aplicados.
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

    .badge-rol {
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .badge-rol.oficial {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-rol.ayudante {
        background: #fff3e0;
        color: #ef6c00;
    }

    .badge-rol.supervisor {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .badge-rol.administrativo {
        background: #e0f7fa;
        color: #00838f;
    }

    .badge-rol.chofer {
        background: #e8eaf6;
        color: #3949ab;
    }

    .badge-cuadrilla {
        background: #e3f2fd;
        color: #1565c0;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.85em;
    }

    .badge-alert {
        background: #fff3e0;
        color: #ff9800;
        padding: 4px 8px;
        border-radius: 50%;
    }

    .badge-ok {
        color: #4caf50;
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
</style>

<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const rol = document.getElementById('filterRol').value;
        const cuadrilla = document.getElementById('filterCuadrilla').value;

        const rows = document.querySelectorAll('#personalTable tbody tr[data-name]');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = row.dataset.name.includes(search);
            const matchRol = !rol || row.dataset.rol === rol;
            const matchCuadrilla = !cuadrilla || row.dataset.cuadrilla === cuadrilla;

            const show = matchSearch && matchRol && matchCuadrilla;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterRol').value = '';
        document.getElementById('filterCuadrilla').value = '';
        filterTable();
    }

    function confirmDelete(id, nombre) {
        if (confirm('¿Eliminar a "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
            window.location.href = 'delete.php?id=' + id;
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>