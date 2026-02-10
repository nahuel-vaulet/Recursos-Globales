<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// 1. Fetch Office Stock (Static Panel)
$sql_office = "SELECT m.id_material, m.nombre, m.codigo, m.unidad_medida, 
               COALESCE(s.stock_oficina, 0) as stock_oficina
        FROM maestro_materiales m
        LEFT JOIN stock_saldos s ON m.id_material = s.id_material
        ORDER BY m.nombre ASC";
$office_stock = $pdo->query($sql_office)->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Active Squads Meta Data (Name, Vehicle)
$sql_squads_meta = "SELECT c.id_cuadrilla, c.nombre_cuadrilla, c.id_vehiculo_asignado, 
                           v.marca, v.modelo, v.patente
                    FROM cuadrillas c
                    LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id_vehiculo
                    WHERE c.estado_operativo = 'Activa'
                    ORDER BY c.nombre_cuadrilla";
$squads_meta = $pdo->query($sql_squads_meta)->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Today's Fuel Consumption Grouped by Vehicle
// We sum both Dispatches (from Tank) AND Direct Purchases (combustibles_cargas to vehicle)
$today = date('Y-m-d');
$sql_fuel = "
    SELECT id_vehiculo, SUM(litros) as litros_hoy FROM (
        SELECT id_vehiculo, litros FROM combustibles_despachos WHERE DATE(fecha_hora) = '$today' AND id_vehiculo IS NOT NULL
        UNION ALL
        SELECT id_vehiculo, litros FROM combustibles_cargas WHERE DATE(fecha_hora) = '$today' AND destino_tipo = 'vehiculo' AND id_vehiculo IS NOT NULL
    ) as combined_fuel
    GROUP BY id_vehiculo";

$fuel_data = $pdo->query($sql_fuel)->fetchAll(PDO::FETCH_KEY_PAIR); // [id_vehiculo => litros]

// 4. Fetch Stock Items for Squads
$sql_squads_items = "SELECT sc.id_cuadrilla, m.nombre as material, sc.cantidad, m.unidad_medida
                     FROM stock_cuadrilla sc
                     JOIN maestro_materiales m ON sc.id_material = m.id_material
                     WHERE sc.cantidad > 0
                     ORDER BY m.nombre";
$raw_squad_items = $pdo->query($sql_squads_items)->fetchAll(PDO::FETCH_ASSOC);

// 5. Build Final Data Structure
$squads_data = [];
// Initialize with meta
foreach ($squads_meta as $meta) {
    $id = $meta['id_cuadrilla'];
    $vid = $meta['id_vehiculo_asignado'];

    $squads_data[$id] = [
        'name' => $meta['nombre_cuadrilla'],
        'vehicle' => $meta['patente'] ? ($meta['marca'] . ' ' . $meta['patente']) : null,
        'fuel_today' => ($vid && isset($fuel_data[$vid])) ? $fuel_data[$vid] : 0,
        'items' => []
    ];
}

// Attach items
foreach ($raw_squad_items as $item) {
    $id = $item['id_cuadrilla'];
    if (isset($squads_data[$id])) {
        $squads_data[$id]['items'][] = $item;
    }
}

// Fetch Dropdowns for Modal
$cuadrillas_all = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa'")->fetchAll(PDO::FETCH_ASSOC);
// 3. Fetch Movements History (Merged from Movimientos Module)
// Fetch Limit increased for Dashboard context
$sql_movs = "SELECT mov.*, m.nombre as material, c.nombre_cuadrilla, p.razon_social as proveedor, o.nro_odt_assa 
        FROM movimientos mov
        JOIN maestro_materiales m ON mov.id_material = m.id_material
        LEFT JOIN cuadrillas c ON mov.id_cuadrilla = c.id_cuadrilla
        LEFT JOIN proveedores p ON mov.id_proveedor = p.id_proveedor
        LEFT JOIN odt_maestro o ON mov.id_odt = o.id_odt
        ORDER BY mov.fecha_hora DESC LIMIT 500";
$movimientos = $pdo->query($sql_movs)->fetchAll(PDO::FETCH_ASSOC);

// Unique Lists for Dropdown Filters (Movimientos)
$types = array_unique(array_column($movimientos, 'tipo_movimiento'));

// Derivar Orígenes y Destinos únicos para el Filtro
$origins = [];
$destinations = [];

foreach ($movimientos as $m) {
    if ($m['tipo_movimiento'] == 'Compra_Material') {
        $origins[] = $m['proveedor'];
        $destinations[] = 'Oficina Central';
    } elseif ($m['tipo_movimiento'] == 'Recepcion_ASSA_Oficina') {
        $origins[] = 'ASSA';
        $destinations[] = 'Oficina Central';
    } elseif ($m['tipo_movimiento'] == 'Entrega_Oficina_Cuadrilla') {
        $origins[] = 'Oficina Central';
        $destinations[] = $m['nombre_cuadrilla'];
    } elseif ($m['tipo_movimiento'] == 'Consumo_Cuadrilla_Obra') {
        $origins[] = $m['nombre_cuadrilla'];
        $destinations[] = 'Obra / Consumo';
    }
}
$origins = array_unique(array_filter($origins));
$destinations = array_unique(array_filter($destinations));
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Top Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;"><i class="fas fa-boxes"></i> Stock</h2>
        <a href="../movimientos/form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nueva Operación</a>
    </div>

    <!-- FUEL DASHBOARD (Integrated) -->
    <?php include '../combustibles/views/dashboard.php'; ?>

    <!-- MAIN GRID LAYOUT -->
    <div class="smart-dashboard-grid">

        <!-- PANEL A: OFICINA (Priority) -->
        <div class="card panel-office">
            <div class="panel-header">
                <h3><i class="fas fa-building"></i> Stock Oficina</h3>
                <input type="text" id="officeSearch" onkeyup="filterOffice()" placeholder="Buscar material..."
                    class="search-input">
            </div>

            <div class="table-container custom-scrollbar">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th class="text-right">Stock</th>
                            <th class="text-center">Mover</th>
                        </tr>
                    </thead>
                    <tbody id="tableOffice">
                        <?php foreach ($office_stock as $item): ?>
                            <tr class="office-item"
                                data-name="<?php echo strtolower($item['nombre'] . ' ' . $item['codigo']); ?>">
                                <td>
                                    <div class="mat-name"><?php echo $item['nombre']; ?></div>
                                    <div class="mat-code"><?php echo $item['codigo']; ?></div>
                                </td>
                                <td class="text-right">
                                    <span
                                        class="stock-badge <?php echo $item['stock_oficina'] > 0 ? 'instock' : 'empty'; ?>">
                                        <?php echo number_format($item['stock_oficina'], 2); ?>
                                    </span>
                                    <small><?php echo $item['unidad_medida']; ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['stock_oficina'] > 0): ?>
                                        <button
                                            onclick="openTransferModal(<?php echo $item['id_material']; ?>, '<?php echo $item['nombre']; ?>', <?php echo $item['stock_oficina']; ?>)"
                                            class="btn-icon" title="Transferir">
                                            <i class="fas fa-share"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PANEL B: CUADRILLAS (Smart Carousel/Grid) -->
        <div class="panel-squads-container">
            <div class="panel-header-squads">
                <h3><i class="fas fa-shipping-fast"></i> En Tránsito / Cuadrillas (<?php echo count($squads_data); ?>
                    Activas)</h3>
            </div>

            <!-- Horizontal Scroll / Carousel for Cards -->
            <div class="squads-carousel custom-scrollbar">
                <?php if (empty($squads_data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i> Todo el material está en depósito central.
                    </div>
                <?php endif; ?>

                <?php foreach ($squads_data as $id_cuadrilla => $squad): ?>
                    <div class="squad-card fade-in">
                        <div class="squad-card-header">
                            <div>
                                <i class="fas fa-hard-hat"></i> <?php echo $squad['name']; ?>
                            </div>
                            <a href="../stock_cuadrilla/detalle.php?id=<?php echo $id_cuadrilla; ?>" class="btn-detail"
                                title="Ver Detalle">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>

                        <!-- NEW: Vehicle & Fuel Info -->
                        <?php if ($squad['vehicle']): ?>
                            <div
                                style="padding: 8px 15px; background: rgba(0,0,0,0.02); border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 0.85em; display: flex; justify-content: space-between; align-items: center;">
                                <span title="Vehículo Asignado" style="color: #666;">
                                    <i class="fas fa-truck" style="margin-right: 4px;"></i> <?php echo $squad['vehicle']; ?>
                                </span>

                                <?php
                                if ($squad['fuel_today'] > 0):
                                    $fClass = 'success'; // > 15 (Green)
                                    if ($squad['fuel_today'] < 15)
                                        $fClass = 'warning'; // < 15 (Yellow)
                                    if ($squad['fuel_today'] < 10)
                                        $fClass = 'danger';  // < 10 (Red)
                                    ?>
                                    <span class="badge-fuel <?php echo $fClass; ?>" title="Carga Diaria">
                                        <i class="fas fa-gas-pump"></i> <?php echo number_format($squad['fuel_today'], 1); ?> L
                                    </span>
                                <?php else: ?>
                                    <span class="badge-fuel danger" title="Sin carga registrada hoy">
                                        <i class="fas fa-gas-pump"></i> 0.0 L
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="squad-card-body custom-scrollbar">
                            <table class="mini-table">
                                <?php foreach ($squad['items'] as $mat): ?>
                                    <tr>
                                        <td><?php echo $mat['material']; ?></td>
                                        <td class="text-right">
                                            <strong><?php echo number_format($mat['cantidad'], 2); ?></strong>
                                            <?php echo $mat['unidad_medida']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <!-- END TOP PANELS -->

    <!-- SECTION: MOVIMIENTOS HISTORY (Integrated) -->
    <div class="card"
        style="box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 8px; margin-top: 30px; border-top: 4px solid var(--color-neutral-dark);">

        <!-- HEADER & ACTIONS -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding: 15px;">
            <h3 style="margin: 0; color: var(--color-neutral-dark);"><i class="fas fa-history"></i> Historial de
                Movimientos</h3>
            <div style="display: flex; gap: 10px;">
                <button onclick="exportTable()" class="btn btn-outline" style="color: #217346; border-color: #217346;">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
            </div>
        </div>

        <!-- FILTERS BAR -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Fecha Desde</label>
                <input type="date" id="f_date_from" onchange="applyFilters()" class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Fecha Hasta</label>
                <input type="date" id="f_date_to" onchange="applyFilters()" class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Tipo Operación</label>
                <select id="f_type" onchange="applyFilters()" class="form-control-sm">
                    <option value="">Todos</option>
                    <option value="Compra_Material">🛒 Compra Material</option>
                    <option value="Recepcion_ASSA_Oficina">🏢 Recepción ASSA</option>
                    <option value="Entrega_Oficina_Cuadrilla">🚚 Entrega Cuadrilla</option>
                    <option value="Consumo_Cuadrilla_Obra">📉 Consumo (Cuadrilla -> Obra)</option>
                </select>
            </div>

            <!-- New Filter: Origin -->
            <div class="filter-group">
                <label>Origen</label>
                <select id="f_origin" onchange="applyFilters()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($origins as $o): ?>
                        <option value="<?php echo htmlspecialchars($o); ?>"><?php echo htmlspecialchars($o); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- New Filter: Destination -->
            <div class="filter-group">
                <label>Destino</label>
                <select id="f_dest" onchange="applyFilters()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group" style="flex: 0 0 auto;">
                <button onclick="resetFilters()" class="btn btn-outline btn-sm" title="Limpiar Filtros">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div style="overflow-x: auto; padding: 0 15px 20px 15px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85em;" id="movesTable">
                <thead>
                    <tr style="background: #f1f3f5; color: #495057;">
                        <th style="padding: 12px; text-align: left;">Fecha</th>
                        <th style="padding: 12px; text-align: left;">Tipo</th>
                        <th style="padding: 12px; text-align: left;">Material</th>
                        <th style="padding: 12px; text-align: right;">Cant</th>
                        <th style="padding: 12px; text-align: left;">Origen</th> <!-- Split -->
                        <th style="padding: 12px; text-align: left;">Destino</th> <!-- Split -->
                        <th style="padding: 12px; text-align: left;">Doc / Usuarios</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($movimientos as $mov):
                        // Visual Logic
                        $dateRaw = date('Y-m-d', strtotime($mov['fecha_hora']));
                        $typeRaw = $mov['tipo_movimiento'];
                        $squadRaw = $mov['nombre_cuadrilla'] ?? '';

                        // Logic for Origin/Dest
                        $originTxt = '-';
                        $destTxt = '-';

                        if ($typeRaw == 'Compra_Material') {
                            $originTxt = $mov['proveedor'] ?? 'Proveedor Ext.';
                            $destTxt = 'Oficina Central';
                        } elseif ($typeRaw == 'Recepcion_ASSA_Oficina') {
                            $originTxt = 'ASSA';
                            $destTxt = 'Oficina Central';
                        } elseif ($typeRaw == 'Entrega_Oficina_Cuadrilla') {
                            $originTxt = 'Oficina Central';
                            $destTxt = $mov['nombre_cuadrilla'];
                        } elseif ($typeRaw == 'Consumo_Cuadrilla_Obra') {
                            $originTxt = $mov['nombre_cuadrilla'];
                            $destTxt = 'Obra / Consumo';
                        }

                        // Icons & Colors
                        $color = '#333';
                        $icon = '';
                        $typeName = '';
                        if ($typeRaw == 'Compra_Material') {
                            $color = '#28a745';
                            $icon = '🛒';
                            $typeName = 'Compra';
                        } elseif ($typeRaw == 'Recepcion_ASSA_Oficina') {
                            $color = '#17a2b8';
                            $icon = '🏢';
                            $typeName = 'Recepción ASSA';
                        } elseif ($typeRaw == 'Entrega_Oficina_Cuadrilla') {
                            $color = '#007bff';
                            $icon = '🚚';
                            $typeName = 'Entrega';
                        } elseif ($typeRaw == 'Consumo_Cuadrilla_Obra') {
                            $color = '#6c757d';
                            $icon = '📉';
                            $typeName = 'Consumo';
                        } else {
                            $color = '#666';
                            $icon = '🔧';
                            $typeName = str_replace('_', ' ', $typeRaw);
                        }
                        ?>
                        <tr style="border-bottom: 1px solid #efefef;" data-date="<?php echo $dateRaw; ?>"
                            data-type="<?php echo $typeRaw; ?>" data-origin="<?php echo htmlspecialchars($originTxt); ?>"
                            data-dest="<?php echo htmlspecialchars($destTxt); ?>">

                            <td style="padding: 12px; color: #555;">
                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha_hora'])); ?>
                            </td>
                            <td style="padding: 12px; font-weight: 600; color: <?php echo $color; ?>;">
                                <?php echo $icon . ' ' . $typeName; ?>
                            </td>
                            <td style="padding: 12px;"><strong><?php echo $mov['material']; ?></strong></td>
                            <td
                                style="padding: 12px; text-align: right; font-weight: bold; font-family: monospace; font-size: 1.1em;">
                                <?php echo $mov['cantidad']; ?>
                            </td>
                            <!-- Split Columns -->
                            <td style="padding: 12px;">
                                <span class="badge-prov"><?php echo $originTxt; ?></span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge-squad"><?php echo $destTxt; ?></span>
                            </td>

                            <td style="padding: 12px; color: #777;">
                                <?php if ($mov['nro_documento'])
                                    echo "<div><i class='fas fa-file-alt'></i> " . $mov['nro_documento'] . "</div>"; ?>
                                <?php if ($mov['nro_odt_assa'])
                                    echo "<div style='margin-top:2px;'><span class='badge-odt'>ODT: " . $mov['nro_odt_assa'] . "</span></div>"; ?>

                                <div style="font-size: 0.85em; margin-top: 4px;">
                                    <?php if ($mov['usuario_despacho']): ?><span title="Despachó"><i
                                                class="fas fa-user-tag"></i>
                                            <?php echo $mov['usuario_despacho']; ?></span><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="noResults" style="display:none; padding: 20px; text-align: center; color: #999;">No se encontraron
                movimientos con los filtros aplicados.</div>
        </div>
    </div>
</div>
<!-- SheetJS -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<style>
    /* Merged Styles for Movements Table */
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
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.9em;
    }

    .badge-prov {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85em;
    }

    .badge-squad {
        background: #e3f2fd;
        color: #1565c0;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85em;
    }

    .badge-fuel {
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9em;
        border: 1px solid transparent;
    }

    .badge-fuel.success {
        background: #e8f5e9;
        color: #2e7d32;
        border-color: #c8e6c9;
    }

    .badge-fuel.warning {
        background: #fff3e0;
        color: #e65100;
        border-color: #ffe0b2;
    }

    .badge-fuel.danger {
        background: #ffebee;
        color: #c62828;
        border-color: #ffcdd2;
    }

    .badge-odt {
        background: #fff3e0;
        color: #ef6c00;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85em;
    }

    #tableBody tr:hover {
        background-color: #f9f9f9;
        cursor: default;
    }
</style>

<script>
    // --- Movements Logic ---
    function applyFilters() {
        const fDateFrom = document.getElementById('f_date_from').value;
        const fDateTo = document.getElementById('f_date_to').value;
        const fType = document.getElementById('f_type').value;
        const fOrigin = document.getElementById('f_origin').value;
        const fDest = document.getElementById('f_dest').value;

        const rows = document.querySelectorAll('#tableBody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowDate = row.dataset.date;
            const rowType = row.dataset.type;
            const rowOrigin = row.dataset.origin;
            const rowDest = row.dataset.dest;

            let show = true;
            if (fDateFrom && rowDate < fDateFrom) show = false;
            if (fDateTo && rowDate > fDateTo) show = false;
            if (fType && rowType !== fType) show = false;
            if (fOrigin && rowOrigin !== fOrigin) show = false;
            if (fDest && rowDest !== fDest) show = false;

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
    }

    function resetFilters() {
        document.getElementById('f_date_from').value = '';
        document.getElementById('f_date_to').value = '';
        document.getElementById('f_type').value = '';
        document.getElementById('f_origin').value = '';
        document.getElementById('f_dest').value = '';
        applyFilters();
    }

    function exportTable() {
        const rows = document.querySelectorAll('#tableBody tr');
        const data = [];
        // Header Updated
        data.push(["Fecha", "Tipo Movimiento", "Material", "Cantidad", "Origen", "Destino", "Documento", "Usuarios"]);

        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                data.push([
                    cells[0].innerText.trim(),
                    cells[1].innerText.trim(),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(),
                    cells[4].innerText.trim(), // Origen
                    cells[5].innerText.trim(), // Destino
                    cells[6].innerText.split('\n')[0].trim(), // Documento
                    cells[6].innerText.replace(cells[6].innerText.split('\n')[0].trim(), '').trim() // User
                ]);
            }
        });

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Movimientos");
        XLSX.writeFile(wb, `Stock_Movimientos_${new Date().toISOString().split('T')[0]}.xlsx`);
    }
</script>
<div id="transferModal" class="modal-overlay">
    <div class="modal-content">
        <h3>Transferencia Rápida</h3>
        <p>Enviando: <strong id="modalMatName" class="highlight-text"></strong></p>

        <form action="../movimientos/save.php" method="POST">
            <input type="hidden" name="tipo_movimiento" value="Entrega_Oficina_Cuadrilla">
            <input type="hidden" name="id_material[]" id="modalMatId">
            <input type="hidden" name="nro_documento" value="FAST-TRANSFER">
            <input type="hidden" name="usuario_despacho" value="Sistema (Quick)">
            <input type="hidden" name="fecha_movimiento" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="usuario_recepcion" value="">
            <input type="hidden" name="redirect_to_remito" value="1">

            <div class="form-group">
                <label>Destino</label>
                <select name="id_cuadrilla" required class="form-control">
                    <?php foreach ($cuadrillas_all as $c): ?>
                        <option value="<?php echo $c['id_cuadrilla']; ?>"><?php echo $c['nombre_cuadrilla']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cantidad (Máx: <span id="modalMaxQty"></span>)</label>
                <input type="number" step="0.01" name="cantidad[]" id="modalQty" required class="form-control">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Smart Dashboard CSS - Themed */
    .smart-dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    /* Office Panel */
    .panel-office {
        background: var(--bg-card);
        color: var(--text-primary);
        border-radius: 8px;
        box-shadow: var(--shadow-md);
        display: flex;
        flex-direction: column;
        border-top: 4px solid var(--color-primary);
        border: 1px solid rgba(100, 181, 246, 0.15);
    }

    .panel-header {
        padding: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .panel-header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-container {
        flex-grow: 1;
    }

    .table th {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.85rem;
        padding: 10px 15px;
    }

    .table td {
        padding: 12px 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Squads Panel */
    .panel-squads-container {
        display: flex;
        flex-direction: column;
    }

    .panel-header-squads {
        margin-bottom: 15px;
    }

    .panel-header-squads h3 {
        color: var(--text-primary);
        font-size: 1.1rem;
        margin: 0;
    }

    .squads-carousel {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        grid-auto-rows: max-content;
        gap: 15px;
        padding-bottom: 20px;
        align-content: start;
    }

    .squad-card {
        background: var(--bg-card);
        color: var(--text-primary);
        border-radius: 8px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        border-left: 4px solid var(--accent-primary);
        transition: transform 0.2s;
        border: 1px solid rgba(100, 181, 246, 0.1);
        max-height: 300px;
    }

    .squad-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        border-color: var(--accent-primary);
    }

    .squad-card-header {
        background: var(--bg-secondary);
        padding: 10px 15px;
        font-weight: bold;
        color: var(--accent-primary);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-detail {
        background: var(--accent-primary);
        color: white;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-detail:hover {
        background: var(--color-primary);
        transform: scale(1.1);
    }

    .squad-card-body {
        padding: 10px;
        overflow-y: auto;
        flex-grow: 1;
    }

    .mini-table {
        width: 100%;
        font-size: 0.85em;
    }

    .mini-table td {
        padding: 4px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: var(--text-secondary);
    }

    .mini-table td strong {
        color: var(--text-primary);
    }

    /* Styles */
    .search-input {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: var(--bg-tertiary);
        color: var(--text-primary);
        margin-top: 10px;
    }

    .search-input:focus {
        border-color: var(--accent-primary);
        outline: none;
    }

    .stock-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: bold;
        font-size: 0.85rem;
        display: inline-block;
        min-width: 60px;
        text-align: center;
    }

    .stock-badge.instock {
        color: #2e7d32;
        /* Verde fuerte legible */
        background: #e8f5e9;
        /* Verde muy claro */
        border: 1px solid #c8e6c9;
    }

    .stock-badge.empty {
        color: #c62828;
        /* Rojo fuerte legible */
        background: #ffebee;
        /* Rojo muy claro */
        border: 1px solid #ffcdd2;
    }

    /* Unidad de medida */
    small {
        color: var(--text-secondary) !important;
        font-weight: 500;
        margin-left: 4px;
        font-size: 0.8rem;
    }

    .mat-name {
        font-weight: 500;
        font-size: 0.95em;
        color: var(--text-primary);
    }

    .mat-code {
        font-size: 0.75em;
        color: var(--text-muted);
    }

    .btn-icon {
        background: var(--bg-tertiary);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        color: var(--text-secondary);
        padding: 4px 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-icon:hover {
        background: var(--accent-primary);
        color: white;
        border-color: var(--accent-primary);
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 999;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: var(--bg-card);
        color: var(--text-primary);
        width: 400px;
        margin: 10% auto;
        padding: 25px;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        border: 1px solid rgba(100, 181, 246, 0.2);
    }

    .modal-content h3 {
        margin-top: 0;
        color: var(--text-primary);
    }

    .modal-content p {
        color: var(--text-secondary);
    }

    .highlight-text {
        color: var(--accent-primary);
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        margin-top: 5px;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .form-control:focus {
        border-color: var(--accent-primary);
        outline: none;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Merged Styles for Movements Table */
    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }

    .filter-group label {
        font-size: 0.8em;
        font-weight: bold;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .form-control-sm {
        padding: 6px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        font-size: 0.9em;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .badge-prov {
        background: rgba(46, 125, 50, 0.15);
        color: #66bb6a;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.85em;
        border: 1px solid rgba(46, 125, 50, 0.3);
    }

    .badge-squad {
        background: rgba(21, 101, 192, 0.15);
        color: #42a5f5;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.85em;
        border: 1px solid rgba(21, 101, 192, 0.3);
    }

    .badge-odt {
        background: rgba(239, 108, 0, 0.15);
        color: #ffa726;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.85em;
        border: 1px solid rgba(239, 108, 0, 0.3);
    }

    #tableBody tr:hover {
        background-color: var(--bg-secondary);
        cursor: default;
    }

    /* MODO CLARO - Override específico */
    [data-theme="light"] .panel-office,
    [data-theme="light"] .squad-card,
    [data-theme="light"] .modal-content {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #333;
    }

    [data-theme="light"] .panel-header,
    [data-theme="light"] .panel-header-squads h3,
    [data-theme="light"] .modal-content h3 {
        color: #333;
    }

    [data-theme="light"] .table th {
        background: #f8f9fa;
        color: #555;
    }

    [data-theme="light"] .table td,
    [data-theme="light"] .squad-card-header,
    [data-theme="light"] .mini-table td {
        border-color: #f0f0f0;
        color: #333;
    }

    [data-theme="light"] .search-input,
    [data-theme="light"] .form-control,
    [data-theme="light"] .form-control-sm {
        background: #ffffff;
        border: 1px solid #ddd;
        color: #333;
    }

    [data-theme="light"] .squad-card-header {
        background: #f4f8f9;
        color: cadetblue;
    }

    [data-theme="light"] small,
    [data-theme="light"] .mat-code,
    [data-theme="light"] .filter-group label {
        color: #666 !important;
    }

    [data-theme="light"] .btn-icon {
        background: #fff;
        border-color: #ddd;
        color: #666;
    }

    [data-theme="light"] .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #ccc;
    }

    [data-theme="light"] .badge-prov {
        background: #e8f5e9;
        color: #2e7d32;
        border: none;
    }

    [data-theme="light"] .badge-squad {
        background: #e3f2fd;
        color: #1565c0;
        border: none;
    }

    [data-theme="light"] .badge-odt {
        background: #fff3e0;
        color: #ef6c00;
        border: none;
    }
</style>

<script>
    function filterOffice() {
        const term = document.getElementById('officeSearch').value.toLowerCase();
        const rows = document.querySelectorAll('.office-item');
        rows.forEach(row => {
            row.style.display = row.dataset.name.includes(term) ? '' : 'none';
        });
    }

    function openTransferModal(id, name, max) {
        document.getElementById('transferModal').style.display = 'block';
        document.getElementById('modalMatId').value = id;
        document.getElementById('modalMatName').innerText = name;
        document.getElementById('modalMaxQty').innerText = max;
        document.getElementById('modalQty').max = max;
        document.getElementById('modalQty').value = '';
    }

    function closeModal() {
        document.getElementById('transferModal').style.display = 'none';
    }

    // Close modal on outside click
    window.onclick = function (event) {
        if (event.target == document.getElementById('transferModal')) {
            closeModal();
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>