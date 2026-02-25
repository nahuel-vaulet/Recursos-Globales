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
// 3. Fetch Today's Fuel Consumption Grouped by Vehicle
// 4. Fetch Stock Items for Squads
// 5. Build Final Data Structure
require_once '../combustibles/views/squads_data_loader.php';

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
        <div style="display: flex; gap: 10px;">
            <a href="../movimientos/form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nueva
                Operación</a>
        </div>
    </div>

    <!-- FUEL DASHBOARD (Integrated) -->
    <?php include '../combustibles/views/dashboard.php'; ?>

    <!-- MAIN GRID LAYOUT -->
    <div class="smart-dashboard-grid">

        <!-- PANEL A: OFICINA (Priority) -->
        <div class="card panel-office">
            <div class="panel-header"
                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0;"><i class="fas fa-building"></i> Stock Oficina</h3>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label class="switch-container"
                        style="display: flex; align-items: center; gap: 8px; font-size: 0.85em; cursor: pointer;">
                        <input type="checkbox" id="multiSelectToggle" onchange="toggleMultiSelectMode()">
                        <span class="checkmark"></span>
                        Modo Entrega
                    </label>
                    <input type="text" id="officeSearch" onkeyup="filterOffice()" placeholder="Buscar material..."
                        class="search-input">
                </div>
            </div>

            <div class="table-container custom-scrollbar">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-check" style="display: none; width: 40px;">Sel.</th>
                            <th>Material</th>
                            <th class="text-right">Stock</th>
                            <th class="text-center col-action">Mover</th>
                            <th class="text-center col-qty" style="display: none; width: 100px;">Cant.</th>
                        </tr>
                    </thead>
                    <tbody id="tableOffice">
                        <?php foreach ($office_stock as $item): ?>
                            <tr class="office-item"
                                data-name="<?php echo strtolower($item['nombre'] . ' ' . $item['codigo']); ?>">
                                <td class="col-check" style="display: none;">
                                    <?php if ($item['stock_oficina'] > 0): ?>
                                        <input type="checkbox" class="mat-checkbox"
                                            data-id="<?php echo $item['id_material']; ?>" onchange="updateSelectedCount()">
                                    <?php endif; ?>
                                </td>
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
                                <td class="text-center col-action">
                                    <?php if ($item['stock_oficina'] > 0): ?>
                                        <button
                                            onclick="openTransferModal(<?php echo $item['id_material']; ?>, '<?php echo $item['nombre']; ?>', <?php echo $item['stock_oficina']; ?>)"
                                            class="btn-icon" title="Transferir">
                                            <i class="fas fa-share"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="col-qty" style="display: none;">
                                    <input type="number" step="0.01" class="form-control-sm mat-qty"
                                        id="qty_<?php echo $item['id_material']; ?>" style="width: 80px;"
                                        placeholder="0.00">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PANEL B: CUADRILLAS (Smart Carousel/Grid) -->

        <!-- END TOP PANELS -->
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
                    <button onclick="exportTable()" class="btn btn-outline"
                        style="color: #217346; border-color: #217346;">
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
                                data-type="<?php echo $typeRaw; ?>"
                                data-origin="<?php echo htmlspecialchars($originTxt); ?>"
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
                <div id="noResults" style="display:none; padding: 20px; text-align: center; color: #999;">No se
                    encontraron
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
            grid-template-columns: 1fr;
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

    <!-- MULTI-SELECT ACTION FOOTER -->
    <div id="multiSelectFooter"
        style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--bg-card); padding: 15px 30px; border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1000; border: 2px solid var(--accent-primary); align-items: center; gap: 20px;">
        <div style="color: var(--text-primary); font-weight: bold;">
            <i class="fas fa-check-circle"></i> <span id="selectedCount">0</span> seleccionados
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <select id="multiSquadDest" class="form-control-sm" style="width: 180px;" onchange="filterPersonnelDest()">
                <option value="">Cuadrilla Destino...</option>
                <?php foreach ($cuadrillas_all as $c): ?>
                    <option value="<?php echo $c['id_cuadrilla']; ?>">
                        <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="multiPersDesp" class="form-control-sm" style="width: 150px;">
                <option value="">Despachado por...</option>
                <?php
                $pers_office = $pdo->query("SELECT id_personal, nombre_apellido FROM personal WHERE id_cuadrilla IS NULL")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($pers_office as $p): ?>
                    <option value="<?= $p['id_personal'] ?>"><?= $p['nombre_apellido'] ?></option>
                <?php endforeach; ?>
            </select>
            <select id="multiPersRec" class="form-control-sm" style="width: 150px;">
                <option value="">Recibido por...</option>
                <!-- Dynamic Content -->
            </select>
            <button onclick="submitMultiRemito()" id="btnSubmitMulti" class="btn btn-primary btn-sm"
                style="border-radius: 20px;">
                Generar Remito Múltiple
            </button>
        </div>
    </div>

    <!-- MODAL: TRASPASO COMBUSTIBLE (SPOT FLOW) -->
    <div id="fuelModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 650px; border-top: 4px solid var(--color-warning);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;"><i class="fas fa-gas-pump"></i> Traspaso de Combustible</h3>
                <button onclick="closeFuelModal()"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- STEP 1: VEHICLE & KM POPUP -->
            <div id="fuelStep1">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Cuadrilla Destino</label>
                    <select id="fuelSquad" class="form-control" onchange="filterFuelContext()">
                        <option value="">Seleccione cuadrilla...</option>
                        <?php foreach ($cuadrillas_all as $c): ?>
                            <option value="<?= $c['id_cuadrilla'] ?>"><?= htmlspecialchars($c['nombre_cuadrilla']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Vehículo</label>
                    <select id="fuelVeh" class="form-control" onchange="loadFuelVehicleData()">
                        <option value="">Seleccione vehículo...</option>
                        <!-- Dynamic -->
                    </select>
                </div>
                <div id="kmPopup"
                    style="display: none; background: rgba(255,193,7,0.1); padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1;">
                            <label>Último KM</label>
                            <input type="number" id="km_ant" class="form-control" readonly>
                        </div>
                        <div style="flex: 1;">
                            <label>KM Actual</label>
                            <input type="number" id="km_curr" class="form-control" oninput="validateKmStep()">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px 0 0 0; text-align: right;">
                    <button type="button" class="btn btn-primary" id="btnNextFuel" disabled
                        onclick="toFuelStep2()">Siguiente <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 2: LOAD & CALC -->
            <form id="fuelForm" style="display: none;">
                <input type="hidden" name="id_vehiculo" id="hiddenVeh">
                <input type="hidden" name="km_ultimo" id="hiddenKmAnt">
                <input type="hidden" name="km_actual" id="hiddenKmCurr">
                <input type="hidden" name="id_cuadrilla_recepcion" id="hiddenSquad">

                <div
                    style="background: rgba(100,181,246,0.1); padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em;">
                    Km Recorridos: <strong id="km_diff">0</strong> | Sugerencia: <strong id="fuel_est_val">0.00</strong>
                    L
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Tanque Origen</label>
                        <select name="id_tanque" id="fuelSelectTank" class="form-control" required>
                            <option value="">Seleccione tanque...</option>
                            <?php
                            $tanks = $pdo->query("SELECT * FROM combustibles_tanques")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($tanks as $t): ?>
                                <option value="<?= $t['id_tanque'] ?>" data-tipo="<?= $t['tipo_combustible'] ?>">
                                    <?= $t['nombre'] ?> (<?= $t['stock_actual'] ?>L) - <?= $t['tipo_combustible'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php
                    $fuel_item = $pdo->query("SELECT costo_primario FROM maestro_materiales WHERE id_material = 999")->fetch(PDO::FETCH_ASSOC);
                    $default_price = $fuel_item['costo_primario'] ?? 1200;
                    ?>
                    <div class="form-group">
                        <label>Precio Unitario ($)</label>
                        <input type="number" step="0.01" name="precio_unitario" id="fuel_price" class="form-control"
                            value="<?= $default_price ?>" oninput="calcFuelImporte()">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Litros Físicos Cargados</label>
                        <input type="number" step="0.01" id="litros_cargo" name="litros_cargados" class="form-control"
                            required oninput="verifyFuelCons(); calcFuelImporte();">
                    </div>
                    <div class="form-group">
                        <label>Importe Total ($)</label>
                        <input type="number" id="fuel_total" name="importe_total" class="form-control" readonly>
                    </div>
                </div>

                <!-- ALARM & OBSERVATION -->
                <div id="fuelVerifBox" style="display: none; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div id="alarmHeader"
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span id="fuelVerifMsg" style="font-weight: bold;"></span>
                        <label id="alarmCheck" style="display: none; color: var(--color-danger); font-weight: bold;">
                            <input type="checkbox" name="es_alerta" id="checkAlerta" checked readonly
                                onclick="return false;"> ALARMA
                        </label>
                    </div>
                    <textarea name="observaciones_alerta" id="fuelObs" class="form-control"
                        placeholder="Observaciones de la carga..." rows="2"></textarea>
                </div>

                <!-- PERSONNEL -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group">
                        <label>Despachado Por</label>
                        <select name="id_personal_entrega" class="form-control" required>
                            <?php foreach ($pers_office as $p): ?>
                                <option value="<?= $p['id_personal'] ?>"><?= $p['nombre_apellido'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quien Recibe (Chofer)</label>
                        <select name="id_personal_recepcion" id="fuelPersRec" class="form-control" required>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer"
                    style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 0 0 0;">
                    <button type="button" class="btn btn-outline" onclick="backToFuelStep1()">Atrás</button>
                    <button type="submit" class="btn btn-warning" id="btnSaveFuel"
                        style="color: #333; font-weight: bold;">Registrar Carga</button>
                </div>
            </form>
        </div>
    </div>

    <!-- UNIFIED HISTORY SECTION -->
    <div class="card"
        style="box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 12px; margin-top: 30px; border-top: 4px solid var(--color-neutral-dark); padding: 0; overflow: hidden;">

        <!-- CARD HEADER WITH TOGGLE -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h3 style="margin: 0; color: var(--text-primary); font-size: 1.1rem;"><i class="fas fa-history"></i>
                    Historial</h3>

                <div class="history-toggle"
                    style="display: flex; background: var(--bg-tertiary); padding: 4px; border-radius: 20px; gap: 4px;">
                    <button onclick="switchHistory('mat')" id="tabMat" class="btn-toggle active"
                        style="border: none; padding: 6px 16px; border-radius: 16px; font-size: 0.85rem; font-weight: bold; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-boxes"></i> Materiales
                    </button>
                    <button onclick="switchHistory('fuel')" id="tabFuel" class="btn-toggle"
                        style="border: none; padding: 6px 16px; border-radius: 16px; font-size: 0.85rem; font-weight: bold; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-gas-pump"></i> Combustible
                    </button>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button onclick="exportTable()" class="btn btn-outline btn-sm"
                    style="color: #217346; border-color: #217346;">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
            </div>
        </div>

        <!-- SECCION HISTORIAL MATERIALES -->
        <div id="historyMat" style="display: block;">
            <table class="table" style="margin:0;">
                <thead style="background: rgba(0,0,0,0.01);">
                    <tr>
                        <th>Fecha</th>
                        <th>Remito</th>
                        <th>Material</th>
                        <th>Cant.</th>
                        <th>Destino</th>
                        <th>Despachado</th>
                        <th>Recibido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $moves = $pdo->query("SELECT m.*, mat.nombre as material, c.nombre_cuadrilla, p1.nombre_apellido as desp, p2.nombre_apellido as rec
                                         FROM movimientos m
                                         JOIN maestro_materiales mat ON m.id_material = mat.id_material
                                         LEFT JOIN cuadrillas c ON m.id_cuadrilla = c.id_cuadrilla
                                         LEFT JOIN personal p1 ON m.usuario_despacho = p1.id_personal
                                         LEFT JOIN personal p2 ON (SELECT id_personal_recepcion FROM spot_remitos WHERE nro_remito = m.nro_documento LIMIT 1) = p2.id_personal
                                         WHERE m.id_material != 999
                                         ORDER BY m.fecha_hora DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($moves as $m): ?>
                        <tr>
                            <td style="font-size: 0.8em;"><?= date('d/m/y H:i', strtotime($m['fecha_hora'])) ?></td>
                            <td><small><?= $m['nro_documento'] ?></small></td>
                            <td class="mat-name"><?= $m['material'] ?></td>
                            <td style="font-weight: bold; color: var(--color-danger);">
                                -<?= number_format($m['cantidad'], 1) ?></td>
                            <td><span class="badge-squad"><?= $m['nombre_cuadrilla'] ?: 'Oficina' ?></span></td>
                            <td style="font-size: 0.85em;"><?= $m['desp'] ?></td>
                            <td style="font-size: 0.85em;"><?= $m['rec'] ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECCION HISTORIAL COMBUSTIBLE -->
        <div id="historyFuel" style="display: none;">
            <table class="table" style="margin:0;">
                <thead style="background: rgba(0,0,0,0.01);">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo / Detalle</th>
                        <th>Origen</th>
                        <th>Cant.</th>
                        <th>Importe</th>
                        <th>Info / KM</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // MEGA-UNION: 4 Sources of truth for fuel
                    $sql_fuel = "
                        (SELECT 
                            r.fecha_emision as fecha,
                            'Despacho' as tipo,
                            CONCAT(v.patente, ' (', v.marca, ')') as detalle,
                            tk.nombre as origen,
                            t.litros_cargados as litros,
                            t.importe_total as importe,
                            CONCAT(t.km_actual, ' km') as extra,
                            t.es_alerta as alerta,
                            t.observaciones_alerta as obs,
                            r.id_remito
                        FROM spot_traspasos_combustible t
                        JOIN vehiculos v ON t.id_vehiculo = v.id_vehiculo
                        JOIN combustibles_tanques tk ON t.id_tanque = tk.id_tanque
                        JOIN spot_remitos r ON t.id_remito = r.id_remito)
                        
                        UNION ALL
                        
                        (SELECT 
                            m.fecha_hora as fecha,
                            CASE 
                                WHEN m.tipo_movimiento = 'Compra_Material' THEN '🛒 Compra Material'
                                WHEN m.tipo_movimiento = 'Transferencia_Interna' THEN '🔄 Abastecer Tanque'
                                WHEN m.tipo_movimiento = 'Entrega_Oficina_Cuadrilla' THEN '🚚 Entrega Cuadrilla'
                                ELSE '🔧 Movimiento'
                            END as tipo,
                            COALESCE(c.nombre_cuadrilla, 'Oficina Central') as detalle,
                            'Stock Oficina' as origen,
                            m.cantidad as litros,
                            0 as importe,
                            m.nro_documento as extra,
                            0 as alerta,
                            '' as obs,
                            NULL as id_remito
                        FROM movimientos m
                        LEFT JOIN cuadrillas c ON m.id_cuadrilla = c.id_cuadrilla
                        WHERE m.id_material IN (999, 1000, 1001))

                        UNION ALL

                        (SELECT 
                            c.fecha_hora as fecha,
                            '📥 Abastecimiento (L)' as tipo,
                            COALESCE(c.proveedor, 'Proveedor') as detalle,
                            t.nombre as origen,
                            c.litros,
                            0 as importe,
                            'Módulo Antiguo' as extra,
                            0 as alerta,
                            '' as obs,
                            NULL as id_remito
                        FROM combustibles_cargas c
                        JOIN combustibles_tanques t ON c.id_tanque = t.id_tanque)

                        UNION ALL

                        (SELECT 
                            d.fecha_hora as fecha,
                            '📤 Despacho (L)' as tipo,
                            CONCAT(v.patente, ' (', d.usuario_conductor, ')') as detalle,
                            t.nombre as origen,
                            d.litros,
                            0 as importe,
                            'Módulo Antiguo' as extra,
                            0 as alerta,
                            '' as obs,
                            NULL as id_remito
                        FROM combustibles_despachos d
                        JOIN combustibles_tanques t ON d.id_tanque = t.id_tanque
                        JOIN vehiculos v ON d.id_vehiculo = v.id_vehiculo)
                        
                        ORDER BY fecha DESC LIMIT 50";

                    $fuel_moves = $pdo->query($sql_fuel)->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($fuel_moves as $f): ?>
                        <tr>
                            <td style="font-size: 0.8em;"><?= date('d/m/y H:i', strtotime($f['fecha'])) ?></td>
                            <td class="mat-name">
                                <span class="badge"
                                    style="background: var(--bg-tertiary); color: var(--text-secondary); font-size: 0.75em;"><?= $f['tipo'] ?></span><br>
                                <?= $f['detalle'] ?>
                            </td>
                            <td><?= $f['origen'] ?></td>
                            <td style="font-weight: bold;"><?= number_format($f['litros'], 1) ?> L</td>
                            <td style="color: var(--color-success); font-weight: 500;">
                                <?= $f['importe'] > 0 ? '$' . number_format($f['importe'], 2) : '-' ?>
                            </td>
                            <td style="font-size: 0.85em;"><?= $f['extra'] ?></td>
                            <td>
                                <?php if ($f['alerta']): ?>
                                    <span class="badge-prov"
                                        style="background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.2);"
                                        title="<?= $f['obs'] ?>">
                                        <i class="fas fa-exclamation-triangle"></i> ALARMA
                                    </span>
                                <?php else: ?>
                                    <span class="badge-prov" title="OK"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($f['id_remito']): ?>
                                    <a href="presentation/remito_print.php?id=<?= $f['id_remito'] ?>" target="_blank"
                                        class="btn btn-sm" style="background: var(--bg-tertiary); color: var(--accent-primary);"
                                        title="Imprimir Remito">
                                        <i class="fas fa-print"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            // CONFIG
            const TOL_WARNING_PCT = 0.15; // 15%
            const TOL_ALERT_PCT = 0.20;   // 20%

            // --- PERSONNEL PICKERS ---
            async function filterPersonnelDest() {
                const squadId = document.getElementById('multiSquadDest').value;
                const selectRec = document.getElementById('multiPersRec');
                const fuelSelectRec = document.getElementById('fuelPersRec');

                if (!squadId) {
                    if (selectRec) selectRec.innerHTML = '<option value="">Recibido por...</option>';
                    if (fuelSelectRec) fuelSelectRec.innerHTML = '<option value="">Seleccione...</option>';
                    return;
                }

                try {
                    // We use a small inline query to fetch personnel by squad
                    const res = await fetch(`presentation/api/get_personnel.php?squad_id=${squadId}`);
                    const personnel = await res.json();

                    let html = '<option value="">Recibido por...</option>';
                    personnel.forEach(p => {
                        html += `<option value="${p.id_personal}">${p.nombre_apellido}</option>`;
                    });

                    if (selectRec) selectRec.innerHTML = html;
                    if (fuelSelectRec) fuelSelectRec.innerHTML = html.replace('Recibido por...', 'Seleccione...');
                } catch (e) {
                    console.error("Error fetching personnel", e);
                }
            }

            function toggleMultiSelectMode() {
                const isMulti = document.getElementById('multiSelectToggle').checked;
                const colsCheck = document.querySelectorAll('.col-check');
                const colsQty = document.querySelectorAll('.col-qty');
                const colsAction = document.querySelectorAll('.col-action');
                const footer = document.getElementById('multiSelectFooter');

                colsCheck.forEach(el => el.style.display = isMulti ? '' : 'none');
                colsQty.forEach(el => el.style.display = isMulti ? '' : 'none');
                colsAction.forEach(el => el.style.display = isMulti ? 'none' : '');
                footer.style.display = isMulti ? 'flex' : 'none';

                if (!isMulti) {
                    document.querySelectorAll('.mat-checkbox').forEach(cb => cb.checked = false);
                    document.querySelectorAll('.mat-qty').forEach(inpt => inpt.value = '');
                    updateSelectedCount();
                }
            }

            function updateSelectedCount() {
                const count = document.querySelectorAll('.mat-checkbox:checked').length;
                document.getElementById('selectedCount').innerText = count;
            }

            async function submitMultiRemito() {
                const squadId = document.getElementById('multiSquadDest').value;
                const despId = document.getElementById('multiPersDesp').value;
                const recId = document.getElementById('multiPersRec').value;

                if (!squadId || !despId || !recId) {
                    showToast('Seleccione cuadrilla y personal (Despacho/Recepción).', 'error');
                    return;
                }

                const items = [];
                document.querySelectorAll('.mat-checkbox:checked').forEach(cb => {
                    const id = cb.dataset.id;
                    const qty = document.getElementById('qty_' + id).value;
                    if (qty > 0) {
                        items.push({ id_material: id, cantidad: qty });
                    }
                });

                if (items.length === 0) {
                    showToast('Seleccione materiales con cantidad > 0.', 'error');
                    return;
                }

                const btn = document.getElementById('btnSubmitMulti');
                btn.disabled = true;
                btn.innerText = 'Procesando...';

                try {
                    const res = await fetch('presentation/api/save_remito.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_cuadrilla_destino: squadId,
                            id_personal_entrega: despId,
                            id_personal_recepcion: recId,
                            items: items
                        })
                    });

                    const result = await res.json();
                    if (result.status === 'success') {
                        showToast('✓ Remito generado: ' + result.nro_remito, 'success');
                        // Redirect to printable remito view
                        setTimeout(() => {
                            window.open('presentation/remito_print.php?id=' + result.id_remito, '_blank');
                            location.reload();
                        }, 800);
                    } else {
                        alert('Error: ' + result.message);
                        btn.disabled = false;
                        btn.innerText = 'Generar Remito Múltiple';
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error de conexión o datos inválidos.');
                    btn.disabled = false;
                    btn.innerText = 'Generar Remito Múltiple';
                }
            }

            // --- FUEL MODAL REFINED ---
            function openFuelTransferModal(tankId = null) {
                document.getElementById('fuelModal').style.display = 'flex';
                resetFuelModal();
                if (tankId) {
                    const selTank = document.getElementById('fuelSelectTank');
                    if (selTank) selTank.value = tankId;
                }
            }

            function closeFuelModal() {
                document.getElementById('fuelModal').style.display = 'none';
            }

             function resetFuelModal() {
                document.getElementById('fuelStep1').style.display = 'block';
                document.getElementById('fuelForm').style.display = 'none';
                document.getElementById('kmPopup').style.display = 'none';
                document.getElementById('fuelSquad').value = '';
                document.getElementById('fuelVeh').innerHTML = '<option value="">-- Seleccione Cuadrilla Primero --</option>';
                document.getElementById('fuelPersRec').innerHTML = '<option value="">-- Seleccione Cuadrilla Primero --</option>';
                document.getElementById('km_curr').value = '';
                document.getElementById('btnNextFuel').disabled = true;
            }

            // Global data for contextual filtering
            window.STOCK_DATA = {
                vehicles: <?= json_encode($pdo->query("SELECT id_vehiculo, patente, marca, id_cuadrilla, km_actual, consumo_promedio, tipo_combustible FROM vehiculos WHERE estado = 'Operativo'")->fetchAll(PDO::FETCH_ASSOC)) ?>,
                personnel: <?= json_encode($pdo->query("SELECT id_personal, nombre_apellido, id_cuadrilla FROM personal")->fetchAll(PDO::FETCH_ASSOC)) ?>
            };

            function filterFuelContext() {
                const squadId = document.getElementById('fuelSquad').value;
                const vSel = document.getElementById('fuelVeh');
                const pSel = document.getElementById('fuelPersRec');

                // Filter Vehicles
                vSel.innerHTML = '<option value="">Seleccione vehículo...</option>';
                const filteredVehs = window.STOCK_DATA.vehicles.filter(v => !squadId || v.id_cuadrilla == squadId);
                filteredVehs.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id_vehiculo;
                    opt.text = `${v.patente} - ${v.marca} (${v.tipo_combustible || 'Indefinido'})`;
                    opt.dataset.km = v.km_actual;
                    opt.dataset.cons = v.consumo_promedio;
                    opt.dataset.tipo = v.tipo_combustible || 'Indefinido';
                    opt.dataset.squad = v.id_cuadrilla;
                    vSel.appendChild(opt);
                });

                // Filter Personnel
                pSel.innerHTML = '<option value="">Seleccione chofer...</option>';
                const filteredPers = window.STOCK_DATA.personnel.filter(p => !squadId || p.id_cuadrilla == squadId);
                filteredPers.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id_personal;
                    opt.text = p.nombre_apellido;
                    pSel.appendChild(opt);
                });

                // Auto-select if only one
                if (filteredVehs.length === 1) {
                    vSel.value = filteredVehs[0].id_vehiculo;
                    loadFuelVehicleData();
                }
                if (filteredPers.length === 1) {
                    pSel.value = filteredPers[0].id_personal;
                }
            }

            function validateFuelMatch() {
                const tank = document.getElementById('fuelSelectTank');
                const veh = document.getElementById('fuelVeh');
                
                if (!tank.value || !veh.value || veh.selectedIndex < 0) return true;

                const tankType = tank.options[tank.selectedIndex].dataset.tipo || "";
                const vehType = veh.options[veh.selectedIndex].dataset.tipo || "";
                
                if (!tankType || !vehType || vehType === 'Indefinido') return true;

                let t = tankType.toLowerCase();
                let v = vehType.toLowerCase();

                // Normalización para comparación
                if (t.includes('gasoil') || t.includes('diesel')) t = 'diesel';
                if (v.includes('gasoil') || v.includes('diesel')) v = 'diesel';
                
                if (t !== v) {
                    showToast(`⛔ INCOMPATIBLE: El vehículo es ${vehType} y el tanque es ${tankType}`, 'error');
                    veh.style.borderColor = "#ef4444";
                    veh.style.background = "rgba(239,68,68,0.1)";
                    return false;
                }

                veh.style.borderColor = "";
                veh.style.background = "";
                return true;
            }

            function loadFuelVehicleData() {
                const sel = document.getElementById('fuelVeh');
                if (!sel.value || sel.selectedIndex < 0) {
                    document.getElementById('kmPopup').style.display = 'none';
                    return;
                }
                
                // Active fuel type check
                if (!validateFuelMatch()) {
                    // We don't block yet, but visual warning is active
                }

                const km = sel.options[sel.selectedIndex].dataset.km || 0;
                document.getElementById('km_ant').value = km;
                document.getElementById('km_curr').min = km;
                document.getElementById('kmPopup').style.display = 'block';
                validateKmStep();
            }

            function validateKmStep() {
                const kma = parseInt(document.getElementById('km_ant').value) || 0;
                const kmc = parseInt(document.getElementById('km_curr').value) || 0;
                document.getElementById('btnNextFuel').disabled = (kmc <= kma);
            }

            function toFuelStep2() {
                const sel = document.getElementById('fuelVeh');
                if (!sel.value) return;

                // FINAL SAFETY BLOCK
                if (!validateFuelMatch()) {
                    showToast('⛔ Operación Bloqueada: Incompatibilidad de combustible.', 'error');
                    return;
                }

                const kma = parseInt(document.getElementById('km_ant').value) || 0;
                const kmc = parseInt(document.getElementById('km_curr').value) || 0;
                const cons_factor = parseFloat(sel.options[sel.selectedIndex].dataset.cons) || 15.0;

                document.getElementById('hiddenVeh').value = sel.value;
                document.getElementById('hiddenKmAnt').value = kma;
                document.getElementById('hiddenKmCurr').value = kmc;
                
                // Ensure we use the squad assigned to the vehicle if not explicitly selected
                const vSquad = sel.options[sel.selectedIndex].dataset.squad;
                const sSquad = document.getElementById('fuelSquad').value;
                document.getElementById('hiddenSquad').value = sSquad || vSquad;

                const diff = kmc - kma;
                document.getElementById('km_diff').innerText = diff;
                const sug = ((diff * cons_factor) / 100).toFixed(2);
                document.getElementById('fuel_est_val').innerText = sug;

                document.getElementById('fuelStep1').style.display = 'none';
                document.getElementById('fuelForm').style.display = 'block';

                // Auto-load personnel for destination
                const squad_id = sel.options[sel.selectedIndex].dataset.squad || "";
                // We need squad_id for rec picker. Let's assume we can fetch it or use the one from vehicle.
                // For now, reuse filterPersonnelDest if we had a squad mapping.
                // Mocking a call for the vehicle's specific driver context if needed.
            }

            function backToFuelStep1() {
                document.getElementById('fuelStep1').style.display = 'block';
                document.getElementById('fuelForm').style.display = 'none';
            }

            function calcFuelImporte() {
                const qty = parseFloat(document.getElementById('litros_cargo').value) || 0;
                const price = parseFloat(document.getElementById('fuel_price').value) || 0;
                document.getElementById('fuel_total').value = (qty * price).toFixed(2);
            }

            function verifyFuelCons() {
                const est = parseFloat(document.getElementById('fuel_est_val').innerText) || 0;
                const real = parseFloat(document.getElementById('litros_cargo').value) || 0;
                calcFuelImporte();

                if (real <= 0 || est <= 0) {
                    document.getElementById('fuelVerifBox').style.display = 'none';
                    return;
                }

                const diff = real - est;
                const diffPct = diff / est;
                const box = document.getElementById('fuelVerifBox');
                const msg = document.getElementById('fuelVerifMsg');
                const alarm = document.getElementById('alarmCheck');
                const check = document.getElementById('checkAlerta');

                box.style.display = 'block';
                
                if (diffPct <= TOL_WARNING_PCT) {
                    // GREEN: OK
                    box.style.background = 'rgba(16,185,129,0.1)';
                    box.style.color = '#fff';
                    msg.innerHTML = '<i class="fas fa-check-circle"></i> Consumo OK (' + (diffPct * 100).toFixed(1) + '%)';
                    alarm.style.display = 'none';
                    check.checked = false;
                    document.getElementById('fuelObs').required = false;
                } else if (diffPct <= TOL_ALERT_PCT) {
                    // YELLOW: WARNING (15% - 20%)
                    box.style.background = 'rgba(255,193,7,0.1)';
                    box.style.color = '#ffc107';
                    msg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ALARMA: Exceso ' + (diffPct * 100).toFixed(1) + '%';
                    alarm.style.display = 'block';
                    check.checked = true;
                    document.getElementById('fuelObs').required = true;
                } else {
                    // RED: ALERT (> 20%)
                    box.style.background = 'rgba(239,68,68,0.1)';
                    box.style.color = '#ef4444';
                    msg.innerHTML = '<i class="fas fa-radiation"></i> ALERTA CRÍTICA: +' + (diffPct * 100).toFixed(1) + '%';
                    alarm.style.display = 'block';
                    check.checked = true;
                    document.getElementById('fuelObs').required = true;
                    document.getElementById('fuelObs').focus();
                }
            }

            document.getElementById('fuelForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const btn = document.getElementById('btnSaveFuel');
                btn.disabled = true;

                const formData = new FormData(this);
                const payload = Object.fromEntries(formData.entries());
                payload.litros_estimados = document.getElementById('fuel_est_val').innerText;
                payload.km_ultimo = document.getElementById('km_ant').value;
                // Personal values are already in the form via name attributes

                try {
                    const res = await fetch('presentation/api/save_fuel.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (result.status === 'success') {
                        showToast('✓ Carga registrada: ' + result.nro_remito);
                        // Auto-open remito
                        if (result.id_remito) {
                            window.open('presentation/remito_print.php?id=' + result.id_remito, '_blank');
                        }
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Error: ' + result.message);
                        btn.disabled = false;
                        btn.innerText = 'Registrar Carga';
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error de conexión.');
                    btn.disabled = false;
                    btn.innerText = 'Registrar Carga';
                }
            });

            // MODAL TRANSFER (ORGINAL)
            function openTransferModal(id, name, max) {
                // INTERCEPT FUEL (By ID 999 or by Name) -> SPOT FLOW
                if (parseInt(id) === 999 || name.toLowerCase().includes('combustible')) {
                    return openFuelTransferModal();
                }

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

            window.onclick = function (event) {
                if (event.target == document.getElementById('transferModal') || event.target == document.getElementById('fuelModal')) {
                    closeModal();
                    closeFuelModal();
                }
            }

            function switchHistory(type) {
                const hMat = document.getElementById('historyMat');
                const hFuel = document.getElementById('historyFuel');
                const tMat = document.getElementById('tabMat');
                const tFuel = document.getElementById('tabFuel');

                if (type === 'mat') {
                    hMat.style.display = 'block';
                    hFuel.style.display = 'none';

                    tMat.classList.add('active');
                    tFuel.classList.remove('active');

                    tMat.style.background = 'var(--accent-primary)';
                    tMat.style.color = '#fff';
                    tFuel.style.background = 'transparent';
                    tFuel.style.color = 'var(--text-secondary)';
                } else {
                    hMat.style.display = 'none';
                    hFuel.style.display = 'block';

                    tFuel.classList.add('active');
                    tMat.classList.remove('active');

                    tFuel.style.background = 'var(--color-warning)';
                    tFuel.style.color = '#333';
                    tMat.style.background = 'transparent';
                    tMat.style.color = 'var(--text-secondary)';
                }
            }

            // Initialize first tab
            switchHistory('mat');
        </script>

        <?php require_once '../../includes/footer.php'; ?>