<?php
/**
 * Módulo: Usuarios del Sistema
 * Listado de usuarios con roles y estados
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Solo Gerente puede acceder a este módulo
if (!tienePermiso('usuarios')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

// Obtener usuarios con nombres de cuadrilla
$sql = "SELECT u.*, c.nombre_cuadrilla 
        FROM usuarios u
        LEFT JOIN cuadrillas c ON u.id_cuadrilla = c.id_cuadrilla
        ORDER BY u.nombre ASC";
$usuarios = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Obtener cuadrillas para filtros
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// Métricas
$total = count($usuarios);
$activos = count(array_filter($usuarios, fn($u) => $u['estado'] == 1));
$gerentes = count(array_filter($usuarios, fn($u) => $u['tipo_usuario'] === 'Gerente'));
$administrativos = count(array_filter($usuarios, fn($u) => $u['tipo_usuario'] === 'Administrativo' || $u['tipo_usuario'] === 'Administrativo ASSA'));
$jefes = count(array_filter($usuarios, fn($u) => $u['tipo_usuario'] === 'JefeCuadrilla'));
$inspectores = count(array_filter($usuarios, fn($u) => $u['tipo_usuario'] === 'Inspector ASSA'));
$coordinadores = count(array_filter($usuarios, fn($u) => $u['tipo_usuario'] === 'Coordinador ASSA'));
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;"><i class="fas fa-users-cog"></i> Usuarios del Sistema</h2>
            <p style="margin: 5px 0 0; color: #666;">Gestión de accesos y perfiles</p>
        </div>
        <a href="form.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Nuevo Usuario</a>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-row">
        <!-- Total -->
        <div class="metric-mini">
            <div class="metric-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $total; ?></span>
                <span class="metric-lbl">Total</span>
            </div>
        </div>

        <!-- Activos -->
        <div class="metric-mini">
            <div class="metric-icon success">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $activos; ?></span>
                <span class="metric-lbl">Activos</span>
            </div>
        </div>

        <!-- Gerentes -->
        <div class="metric-mini">
            <div class="metric-icon warning">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $gerentes; ?></span>
                <span class="metric-lbl">Gerentes</span>
            </div>
        </div>

        <!-- Administrativos -->
        <div class="metric-mini">
            <div class="metric-icon info">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $administrativos; ?></span>
                <span class="metric-lbl">Administrativos</span>
            </div>
        </div>

        <!-- Jefes -->
        <div class="metric-mini">
            <div class="metric-icon danger">
                <i class="fas fa-hard-hat"></i>
            </div>
            <div class="metric-content">
                <span class="metric-val"><?php echo $jefes; ?></span>
                <span class="metric-lbl">Jefes Cuadrilla</span>
            </div>
        </div>
    </div>

    <!-- Los Toasts dinámicos manejarán los mensajes -->

    <!-- Tarjeta Principal -->
    <div class="card" style="border-top: 4px solid var(--color-primary);">

        <!-- Filtros -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Nombre o email..."
                    class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Tipo de Usuario</label>
                <select id="filterTipo" onchange="filterTable()" class="form-control-sm">
                    <option value="">Todos</option>
                    <option value="Gerente">Gerente</option>
                    <option value="Coordinador ASSA">Coordinador ASSA</option>
                    <option value="Administrativo">Administrativo Gral.</option>
                    <option value="Administrativo ASSA">Administrativo ASSA</option>
                    <option value="Inspector ASSA">Inspector ASSA</option>
                    <option value="JefeCuadrilla">Jefe Cuadrilla</option>
                </select>
            </div>
            <div>
                <label
                    style="font-size: 0.8em; font-weight: bold; color: #666; display: block; margin-bottom: 4px;">Estado</label>
                <select id="filterEstado" onchange="filterTable()"
                    style="padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Todos</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end;">
                <button onclick="resetFilters()"
                    style="padding: 8px 12px; background: none; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;" id="usuariosTable">
                <thead>
                    <tr style="background: var(--color-primary-dark); color: white;">
                        <th style="padding: 12px; text-align: left;">Nombre</th>
                        <th style="padding: 12px; text-align: left;">Email</th>
                        <th style="padding: 12px; text-align: center;">Tipo de Usuario</th>
                        <th style="padding: 12px; text-align: left;">Cuadrilla</th>
                        <th style="padding: 12px; text-align: center;">Estado</th>
                        <th style="padding: 12px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #999;">
                                <i class="fas fa-user-slash" style="font-size: 2em; margin-bottom: 10px;"></i><br>
                                No hay usuarios registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr data-search="<?php echo strtolower($u['nombre'] . ' ' . $u['email']); ?>"
                                data-tipo="<?php echo $u['tipo_usuario']; ?>" data-estado="<?php echo $u['estado']; ?>"
                                style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($u['nombre']); ?></div>
                                </td>
                                <td style="padding: 12px;">
                                    <a href="mailto:<?php echo $u['email']; ?>" style="color: #1976d2;">
                                        <?php echo htmlspecialchars($u['email']); ?>
                                    </a>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php
                                    $tipoDisplay = $u['tipo_usuario'];
                                    if ($u['tipo_usuario'] === 'JefeCuadrilla')
                                        $tipoDisplay = 'Jefe Cuadrilla';

                                    $badgeClass = obtenerColorTipoUsuario($u['tipo_usuario']);

                                    // Mapeo de colores específico para este módulo si no se usa la clase
                                    $colores = [
                                        'Gerente' => '#ffd700',
                                        'Coordinador ASSA' => '#81d4fa',
                                        'Administrativo' => '#e0e0e0',
                                        'Administrativo ASSA' => '#a5d6a7',
                                        'Inspector ASSA' => '#ffcc80',
                                        'JefeCuadrilla' => '#ffab91'
                                    ];
                                    $bg = $colores[$u['tipo_usuario']] ?? '#eee';
                                    $color = '#333';
                                    ?>
                                    <span
                                        style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 4px 12px; border-radius: 15px; font-size: 0.85em; font-weight: 500;">
                                        <?php echo $tipoDisplay; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <?php if ($u['nombre_cuadrilla']): ?>
                                        <span
                                            style="background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 15px; font-size: 0.85em;">
                                            <i class="fas fa-hard-hat"></i> <?php echo $u['nombre_cuadrilla']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($u['estado']): ?>
                                        <span
                                            style="background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 15px; font-size: 0.8em;">Activo</span>
                                    <?php else: ?>
                                        <span
                                            style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 15px; font-size: 0.8em;">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="form.php?id=<?php echo $u['id_usuario']; ?>"
                                        style="padding: 6px 10px; border: 1px solid #eee; border-radius: 6px; color: var(--color-primary); text-decoration: none; display: inline-block; margin: 0 2px;"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($u['id_usuario'] != ($_SESSION['usuario_id'] ?? 0)): ?>
                                        <button
                                            onclick="confirmDelete(<?php echo $u['id_usuario']; ?>, '<?php echo addslashes($u['nombre']); ?>')"
                                            style="padding: 6px 10px; border: 1px solid #fdd; border-radius: 6px; color: #dc3545; background: none; cursor: pointer;"
                                            title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="noResults" style="display:none; padding: 20px; text-align: center; color: #999;">
            No se encontraron usuarios con los filtros aplicados.
        </div>
    </div>
</div>

<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const tipo = document.getElementById('filterTipo').value;
        const estado = document.getElementById('filterEstado').value;

        const rows = document.querySelectorAll('#usuariosTable tbody tr[data-search]');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = row.dataset.search.includes(search);
            const matchTipo = !tipo || row.dataset.tipo === tipo;
            const matchEstado = estado === '' || row.dataset.estado === estado;

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

    function confirmDelete(id, nombre) {
        if (confirm('¿Eliminar al usuario "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
            window.location.href = 'delete.php?id=' + id;
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>