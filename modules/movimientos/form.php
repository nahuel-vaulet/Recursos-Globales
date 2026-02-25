<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch Data for Dropdowns
$materiales = $pdo->query("SELECT * FROM maestro_materiales ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa'")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
$contactos = $pdo->query("SELECT * FROM proveedores_contactos ORDER BY nombre_vendedor")->fetchAll(PDO::FETCH_ASSOC);

// Serialize for JS
$contactos_json = json_encode($contactos);
$materiales_json = json_encode($materiales);
$proveedores_json = json_encode($proveedores);
?>

<style>
    /* Panel de Flujo Visual */
    .flow-panel {
        background: var(--bg-secondary);
        border: 1px solid rgba(100, 181, 246, 0.15);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        display: none;
        box-shadow: var(--shadow-sm);
    }

    [data-theme="light"] .flow-panel {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
    }

    .flow-panel.visible {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .flow-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    .flow-box {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 15px 25px;
        min-width: 200px;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        border: 2px solid transparent;
        transition: all 0.3s ease;
        color: var(--text-primary);
    }

    [data-theme="light"] .flow-box {
        background: white;
        color: #333;
    }

    .flow-box.origin {
        border-color: #28a745;
    }

    .flow-box.destination {
        border-color: #007bff;
    }

    .flow-box label {
        display: block;
        font-size: 0.75em;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        margin-bottom: 8px;
        font-weight: bold;
    }

    .flow-box .value {
        font-size: 1.1em;
        font-weight: 600;
        color: var(--text-primary);
    }

    .flow-box .value.fixed {
        color: var(--text-muted);
    }

    .flow-box select {
        width: 100%;
        padding: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: var(--bg-secondary);
        color: var(--text-primary);
        border-radius: 6px;
        font-size: 0.95em;
    }

    [data-theme="light"] .flow-box select {
        border: 1px solid #ddd;
        background: white;
        color: #333;
    }

    .flow-arrow {
        font-size: 2em;
        color: var(--text-muted);
    }

    /* Indicador de estado bloqueado */
    .locked-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.05);
        padding: 8px 15px;
        border-radius: 20px;
        color: var(--text-secondary);
    }

    [data-theme="light"] .locked-indicator {
        background: #f1f3f5;
        color: #495057;
    }

    .locked-indicator i {
        color: var(--text-muted);
        font-size: 0.9em;
    }

    /* Tipo Operación Cards */
    .operation-type-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    .op-card {
        background: var(--bg-card);
        border: 2px solid rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--text-primary);
    }

    [data-theme="light"] .op-card {
        background: white;
        border: 2px solid #e9ecef;
        color: #333;
    }

    .op-card:hover {
        border-color: var(--color-primary);
        transform: translateY(-2px);
    }

    .op-card.selected {
        border-color: var(--color-primary);
        background: rgba(100, 181, 246, 0.1);
    }

    [data-theme="light"] .op-card.selected {
        background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
    }

    .op-card .icon {
        font-size: 1.8em;
        margin-bottom: 8px;
    }

    .op-card .label {
        font-size: 0.85em;
        font-weight: 600;
        color: var(--text-primary);
    }

    [data-theme="light"] .op-card .label {
        color: #333;
    }

    .op-card .sublabel {
        font-size: 0.75em;
        color: var(--text-muted);
        margin-top: 4px;
    }
</style>

<div class="card" style="max-width: 1000px; margin: 0 auto;">
    <h1><i class="fas fa-exchange-alt"></i> Nuevo Movimiento (Carga por Lote)</h1>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
        <div
            style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
            <strong>Error:</strong> <?php echo htmlspecialchars($_GET['details']); ?>
        </div>
    <?php endif; ?>

    <form action="save.php" method="POST" id="moveForm">

        <!-- PASO 1: Selección de Tipo de Operación -->
        <div style="margin-bottom: 25px;">
            <label style="font-weight: bold; font-size: 1.1em; margin-bottom: 15px; display: block;">
                <i class="fas fa-hand-pointer"></i> 1. Seleccione el Tipo de Operación
            </label>

            <div class="operation-type-cards">
                <div class="op-card" data-value="Compra_Material" onclick="selectOperation(this)">
                    <div class="icon">🛒</div>
                    <div class="label">Compra de Material</div>
                    <div class="sublabel">Proveedor → Oficina</div>
                </div>
                <div class="op-card" data-value="Recepcion_ASSA_Oficina" onclick="selectOperation(this)">
                    <div class="icon">🏢</div>
                    <div class="label">Recepción ASSA</div>
                    <div class="sublabel">ASSA → Oficina</div>
                </div>
                <div class="op-card" data-value="Devolucion_ASSA" onclick="selectOperation(this)">
                    <div class="icon">↩️</div>
                    <div class="label">Devolución a ASSA</div>
                    <div class="sublabel">Oficina → ASSA</div>
                </div>
                <div class="op-card" data-value="Devolucion_Compra" onclick="selectOperation(this)">
                    <div class="icon">🔄</div>
                    <div class="label">Devolución de Compra</div>
                    <div class="sublabel">Oficina → Proveedor</div>
                </div>
            </div>

            <!-- Hidden input for form submission -->
            <input type="hidden" name="tipo_movimiento" id="tipo" required>
        </div>

        <!-- PASO 2: Panel de Flujo Visual (Origen / Destino) -->
        <div class="flow-panel" id="flowPanel">
            <label style="font-weight: bold; margin-bottom: 15px; display: block;">
                <i class="fas fa-route"></i> 2. Flujo del Movimiento
            </label>
            <div class="flow-container">
                <div class="flow-box origin" id="originBox">
                    <label>Origen</label>
                    <div class="value" id="originValue">--</div>
                    <select id="originSelect" name="id_proveedor_origen" style="display:none;"></select>
                </div>
                <div class="flow-arrow">→</div>
                <div class="flow-box destination" id="destBox">
                    <label>Destino</label>
                    <div class="value" id="destValue">--</div>
                    <select id="destSelect" name="id_proveedor" style="display:none;"></select>
                </div>
            </div>
        </div>

        <!-- PASO 3: Datos de Operación -->
        <div class="card"
            style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div>
                <label style="font-weight: bold;">3.1 Fecha de Operación</label>
                <input type="date" name="fecha_movimiento" value="<?php echo date('Y-m-d'); ?>" required
                    class="form-control-sm" style="width: 100%;">
            </div>
            <div>
                <label style="font-weight: bold;">3.2 Nº Documento / Remito</label>
                <input type="text" name="nro_documento" placeholder="Remito / Factura" class="form-control-sm"
                    style="width: 100%;">
            </div>
        </div>

        <!-- Usuarios -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
            <div>
                <label>Despachado Por (Proveedor / Empresa)</label>
                <select name="usuario_despacho" class="form-control-sm" style="width: 100%;" required>
                    <option value="">Seleccione Proveedor...</option>
                    <option value="Oficina Central">Oficina Central</option>
                    <?php foreach ($proveedores as $p): ?>
                        <option value="<?= htmlspecialchars($p['razon_social']) ?>">
                            <?= htmlspecialchars($p['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Recibido Por (Personal)</label>
                <select name="usuario_recepcion" class="form-control-sm" style="width: 100%;" required>
                    <option value="">Seleccione Personal...</option>
                    <?php
                    $personal = $pdo->query("SELECT id_personal, nombre_apellido FROM personal ORDER BY nombre_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($personal as $per): ?>
                        <option value="<?= htmlspecialchars($per['nombre_apellido']) ?>">
                            <?= htmlspecialchars($per['nombre_apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- GRILLA DE MATERIALES -->
        <div style="margin-bottom: 20px;">
            <h3><i class="fas fa-list"></i> 4. Detalle de Materiales</h3>
            <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0;" id="gridMateriales">
                <thead>
                    <tr>
                        <th style="padding: 10px; width: 15%;">Código</th>
                        <th style="padding: 10px; width: 45%;">Material (Descripción)</th>
                        <th style="padding: 10px; width: 20%;">Cantidad</th>
                        <th style="padding: 10px; width: 10%;">Unidad</th>
                        <th style="padding: 10px; width: 10%;"></th>
                    </tr>
                </thead>
                <tbody id="gridBody">
                    <!-- Rows added via JS -->
                </tbody>
            </table>
            <button type="button" class="btn btn-outline" onclick="addRow()"
                style="margin-top: 10px; width: 100%; border-style: dashed;">
                <i class="fas fa-plus"></i> Agregar Línea
            </button>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.2em;">
            <i class="fas fa-check-circle"></i> Procesar Movimiento
        </button>
    </form>
</div>

<!-- DATA ISLANDS -->
<script>
    const contactosDB = <?php echo $contactos_json; ?>;
    const materialesDB = <?php echo $materiales_json; ?>;
    const proveedoresDB = <?php echo $proveedores_json; ?>;

    // Reglas de Negocio para cada Tipo de Operación
    const OPERATION_RULES = {
        'Compra_Material': {
            origin: { type: 'dropdown', source: 'proveedores', label: 'Proveedor' },
            destination: { type: 'fixed', value: 'Oficina Central' }
        },
        'Recepcion_ASSA_Oficina': {
            origin: { type: 'fixed', value: 'ASSA' },
            destination: { type: 'fixed', value: 'Oficina Central' }
        },
        'Devolucion_ASSA': {
            origin: { type: 'fixed', value: 'Oficina Central' },
            destination: { type: 'fixed', value: 'ASSA' }
        },
        'Devolucion_Compra': {
            origin: { type: 'fixed', value: 'Oficina Central' },
            destination: { type: 'dropdown', source: 'proveedores', label: 'Proveedor' }
        }
    };

    // Selección de Tipo de Operación (Cards)
    function selectOperation(card) {
        // Deseleccionar todas
        document.querySelectorAll('.op-card').forEach(c => c.classList.remove('selected'));
        // Seleccionar la clickeada
        card.classList.add('selected');

        const tipo = card.dataset.value;
        document.getElementById('tipo').value = tipo;

        // Aplicar reglas de flujo
        applyFlowRules(tipo);
    }

    // Aplicar Reglas de Flujo según Tipo
    function applyFlowRules(tipo) {
        const rules = OPERATION_RULES[tipo];
        if (!rules) return;

        const flowPanel = document.getElementById('flowPanel');
        const originValue = document.getElementById('originValue');
        const originSelect = document.getElementById('originSelect');
        const destValue = document.getElementById('destValue');
        const destSelect = document.getElementById('destSelect');

        // Mostrar panel
        flowPanel.classList.add('visible');

        // --- ORIGEN ---
        if (rules.origin.type === 'fixed') {
            originValue.innerHTML = `<span class="locked-indicator"><i class="fas fa-lock"></i> ${rules.origin.value}</span>`;
            originValue.style.display = 'block';
            originSelect.style.display = 'none';
            originSelect.name = ''; // No enviar en form
        } else if (rules.origin.type === 'dropdown') {
            originValue.style.display = 'none';
            originSelect.style.display = 'block';
            originSelect.name = 'id_proveedor'; // Provider for Compra
            populateDropdown(originSelect, rules.origin.source);
        }

        // --- DESTINO ---
        if (rules.destination.type === 'fixed') {
            destValue.innerHTML = `<span class="locked-indicator"><i class="fas fa-lock"></i> ${rules.destination.value}</span>`;
            destValue.style.display = 'block';
            destSelect.style.display = 'none';
            destSelect.name = '';
        } else if (rules.destination.type === 'dropdown') {
            destValue.style.display = 'none';
            destSelect.style.display = 'block';
            destSelect.name = 'id_proveedor'; // Provider for Devolución
            populateDropdown(destSelect, rules.destination.source);
        }
    }

    // Poblar Dropdown según fuente de datos
    function populateDropdown(selectEl, source) {
        selectEl.innerHTML = '<option value="">-- Seleccione --</option>';

        if (source === 'proveedores') {
            proveedoresDB.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id_proveedor;
                opt.text = p.razon_social;
                selectEl.appendChild(opt);
            });
        }
    }

    // Grid de Materiales
    function addRow() {
        const tbody = document.getElementById('gridBody');

        let optionsCode = '<option value="">Cod</option>';
        let optionsName = '<option value="">Seleccionar Material...</option>';

        materialesDB.forEach(m => {
            optionsCode += `<option value="${m.id_material}">${m.codigo || m.id_material}</option>`;
            optionsName += `<option value="${m.id_material}">${m.nombre}</option>`;
        });

        const row = document.createElement('tr');
        row.innerHTML = `
        <td style="padding: 5px;">
            <select name="id_material[]" class="sync-code form-control-sm" onchange="syncRow(this, 'code')" style="width:100%;">${optionsCode}</select>
        </td>
        <td style="padding: 5px;">
            <select name="id_material_dummy[]" class="sync-name form-control-sm" onchange="syncRow(this, 'name')" style="width:100%;">${optionsName}</select>
        </td>
        <td style="padding: 5px;">
            <input type="number" step="0.01" name="cantidad[]" required placeholder="0.00" class="form-control-sm" style="width:100%;">
        </td>
        <td style="padding: 5px; text-align: center;">
            <span class="unit-label text-muted">-</span>
        </td>
        <td style="padding: 5px; text-align: center;">
            <button type="button" onclick="this.closest('tr').remove()" style="color:red; background:none; border:none; cursor:pointer;"><i class="fas fa-trash"></i></button>
        </td>
    `;
        tbody.appendChild(row);
    }

    function syncRow(el, source) {
        const row = el.closest('tr');
        const selCode = row.querySelector('.sync-code');
        const selName = row.querySelector('.sync-name');
        const unitLabel = row.querySelector('.unit-label');
        const val = el.value;

        if (source === 'code') {
            selName.value = val;
        } else {
            selCode.value = val;
        }

        const mat = materialesDB.find(m => m.id_material == val);
        if (mat) {
            unitLabel.innerText = mat.unidad_medida;
        } else {
            unitLabel.innerText = '-';
        }
    }

    // Inicialización
    window.onload = function () {
        addRow();
    };
</script>

<?php require_once '../../includes/footer.php'; ?>