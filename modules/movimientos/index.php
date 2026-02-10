<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch ALL Data for Client-Side Filtering (Limit increased for history depth)
$sql = "SELECT mov.*, m.nombre as material, c.nombre_cuadrilla, p.razon_social as proveedor, o.nro_odt_assa 
        FROM movimientos mov
        JOIN maestro_materiales m ON mov.id_material = m.id_material
        LEFT JOIN cuadrillas c ON mov.id_cuadrilla = c.id_cuadrilla
        LEFT JOIN proveedores p ON mov.id_proveedor = p.id_proveedor
        LEFT JOIN odt_maestro o ON mov.id_odt = o.id_odt
        ORDER BY mov.fecha_hora DESC LIMIT 1000";
$movimientos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Unique Lists for Dropdown Filters
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

<!-- SheetJS for Excel Export -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<div class="container-fluid" style="padding: 0 20px;">
    <div class="card" style="box-shadow: var(--box-shadow-medium); border-radius: var(--border-radius-lg);">

        <!-- HEADER & ACTIONS -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
            <h1 style="margin: 0; color: var(--color-neutral-dark);"><i class="fas fa-history"></i> Historial de
                Movimientos
            </h1>
            <div style="display: flex; gap: 10px;">
                <a href="form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nueva Operación</a>
                <button onclick="exportTable()" class="btn btn-outline" style="color: #217346; border-color: #217346;">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
            </div>
        </div>

        <!-- FILTERS BAR -->
        <div class="filter-bar">
            <!-- Filter: Date Range -->
            <div class="filter-group">
                <label>Fecha Desde</label>
                <input type="date" id="f_date_from" onchange="applyFilters()" class="form-control-sm">
            </div>
            <div class="filter-group">
                <label>Fecha Hasta</label>
                <input type="date" id="f_date_to" onchange="applyFilters()" class="form-control-sm">
            </div>

            <!-- Filter: Type -->
            <div class="filter-group">
                <label>Tipo Operación</label>
                <select id="f_type" onchange="applyFilters()" class="form-control-sm">
                    <option value="">Todos</option>
                    <option value="Compra_Material">🛒 Compra Material</option>
                    <option value="Recepcion_ASSA_Oficina">🏢 Recepción ASSA</option>
                    <option value="Entrega_Oficina_Cuadrilla">🚚 Entrega Cuadrilla</option>
                    <option value="Consumo_Cuadrilla_Obra">📉 Consumo (C. -&gt; Obra)</option>
                </select>
            </div>

            <!-- Filter: Origin -->
            <div class="filter-group">
                <label>Origen</label>
                <select id="f_origin" onchange="applyFilters()" class="form-control-sm">
                    <option value="">Todos</option>
                    <?php foreach ($origins as $o): ?>
                        <option value="<?php echo htmlspecialchars($o); ?>"><?php echo htmlspecialchars($o); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter: Destination -->
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
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85em;" id="movesTable">
                <thead>
                    <tr style="background: var(--color-primary-dark); color: white;">
                        <th style="padding: 12px; text-align: left;">Fecha</th>
                        <th style="padding: 12px; text-align: left;">Tipo</th>
                        <th style="padding: 12px; text-align: left;">Material</th>
                        <th style="padding: 12px; text-align: right;">Cant</th>
                        <th style="padding: 12px; text-align: left;">Origen / Destino</th>
                        <th style="padding: 12px; text-align: left;">Doc / Usuarios</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($movimientos as $mov):
                        // Formatting Data Attributes for JS Filtering
                        $dateRaw = date('Y-m-d', strtotime($mov['fecha_hora']));
                        $typeRaw = $mov['tipo_movimiento'];

                        // Derivar Origen/Destino para esta fila
                        $rowOrigin = '';
                        $rowDest = '';
                        if ($typeRaw == 'Compra_Material') {
                            $rowOrigin = $mov['proveedor'];
                            $rowDest = 'Oficina Central';
                        } elseif ($typeRaw == 'Recepcion_ASSA_Oficina') {
                            $rowOrigin = 'ASSA';
                            $rowDest = 'Oficina Central';
                        } elseif ($typeRaw == 'Entrega_Oficina_Cuadrilla') {
                            $rowOrigin = 'Oficina Central';
                            $rowDest = $mov['nombre_cuadrilla'];
                        } elseif ($typeRaw == 'Consumo_Cuadrilla_Obra') {
                            $rowOrigin = $mov['nombre_cuadrilla'];
                            $rowDest = 'Obra / Consumo';
                        }
                        // Display Vars
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
                        } else {
                            $color = '#666';
                            $icon = '🔧';
                            $typeName = str_replace('_', ' ', $typeRaw);
                        }
                        ?>
                        <tr style="border-bottom: 1px solid #efefef;" data-date="<?php echo $dateRaw; ?>"
                            data-type="<?php echo $typeRaw; ?>" data-origin="<?php echo htmlspecialchars($rowOrigin); ?>"
                            data-dest="<?php echo htmlspecialchars($rowDest); ?>">

                            <td style="padding: 12px; color: #555;">
                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha_hora'])); ?>
                            </td>
                            <td style="padding: 12px; font-weight: 600; color: <?php echo $color; ?>;">
                                <?php echo $icon . ' ' . $typeName; ?>
                            </td>
                            <td style="padding: 12px;">
                                <strong><?php echo $mov['material']; ?></strong>
                            </td>
                            <td
                                style="padding: 12px; text-align: right; font-weight: bold; font-family: monospace; font-size: 1.1em;">
                                <?php echo $mov['cantidad']; ?>
                            </td>
                            <td style="padding: 12px;">
                                <?php
                                if ($mov['proveedor'])
                                    echo "<span class='badge-prov'>Prov: " . $mov['proveedor'] . "</span>";
                                if ($mov['nombre_cuadrilla'])
                                    echo "<span class='badge-squad'>Dest: " . $mov['nombre_cuadrilla'] . "</span>";
                                if ($mov['nro_odt_assa'])
                                    echo "<br><span class='badge-odt'>ODT: " . $mov['nro_odt_assa'] . "</span>";
                                ?>
                            </td>
                            <td style="padding: 12px; coloe: #777;">
                                <?php if ($mov['nro_documento'])
                                    echo "<div><i class='fas fa-file-alt'></i> " . $mov['nro_documento'] . "</div>"; ?>
                                <div style="font-size: 0.85em; margin-top: 4px;">
                                    <span title="Despachó"><i class="fas fa-user-tag"></i>
                                        <?php echo $mov['usuario_despacho']; ?></span>
                                    <?php if ($mov['usuario_recepcion']): ?>
                                        <span title="Recibió">➜ <i class="fas fa-user-check"></i>
                                            <?php echo $mov['usuario_recepcion']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="noResults" style="display:none; padding: 20px; text-align: center; color: #999;">
                No se encontraron movimientos con los filtros aplicados.
            </div>
        </div>
    </div>
</div>

<script>
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

            // Conditions
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
        // We clone the table to clean it up for export (remove HTML tags in cells if needed, or SheetJS logic handles it)
        // SheetJS table_to_book is easiest but exports hidden rows too unless we filter data manually.
        // Better: Construct data array from VISIBLE rows.

        const rows = document.querySelectorAll('#tableBody tr');
        const data = [];

        // Header
        data.push(["Fecha", "Tipo Movimiento", "Material", "Cantidad", "Origen/Destino", "Documento", "Despacho", "Recepción"]);

        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                // Parse cell text content cleanly
                data.push([
                    cells[0].innerText.trim(),
                    cells[1].innerText.trim(),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(), // Quantity
                    cells[4].innerText.replace(/\n/g, ' ').trim(), // Clean newlines
                    cells[5].innerText.split('\n')[0].trim(), // Doc
                    cells[5].innerText.includes('Desp:') ? cells[5].innerText.split('Desp:')[1].split('\n')[0].trim() : '',
                    cells[5].innerText.includes('Rec:') ? cells[5].innerText.split('Rec:')[1].trim() : ''
                ]);
            }
        });

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Movimientos");

        // Filename: Movimientos_ERP_YYYY-MM-DD.xlsx
        const dateStr = new Date().toISOString().split('T')[0];
        XLSX.writeFile(wb, `Movimientos_ERP_${dateStr}.xlsx`);
    }
</script>

<?php require_once '../../includes/footer.php'; ?>