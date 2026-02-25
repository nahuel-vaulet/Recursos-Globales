<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch tanks
$tanques = $pdo->query("SELECT * FROM spot_tanques ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch vehicles
$vehiculos = $pdo->query("SELECT id_vehiculo, patente, marca, modelo, km_actual FROM vehiculos WHERE estado = 'Operativo' ORDER BY patente ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch providers
$proveedores = $pdo->query("SELECT id_proveedor, razon_social FROM proveedores ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch personnel
$personal = $pdo->query("SELECT id_personal, nombre_apellido FROM personal ORDER BY nombre_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="padding: 20px;">
    <div class="card" style="padding: 20px; border-top: 4px solid var(--color-warning);">
        <h2 style="margin-bottom: 20px;"><i class="fas fa-gas-pump"></i> Traspaso de Combustible</h2>

        <form id="fuelForm">
            <!-- Step 1: Selection & Odometer -->
            <div class="section-card"
                style="margin-bottom: 20px; padding: 15px; background: rgba(255,152,0,0.05); border-radius: 12px; border: 1px solid rgba(255,152,0,0.1);">
                <h4 style="margin-top: 0; color: var(--color-warning);"><i class="fas fa-tachometer-alt"></i> 1.
                    Vehículo y Odómetro</h4>
                <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label">Vehículo</label>
                        <select name="id_vehiculo" id="id_vehiculo" class="form-control" required
                            onchange="updateKmUltimo()">
                            <option value="">Seleccione vehículo...</option>
                            <?php foreach ($vehiculos as $v): ?>
                                <option value="<?= $v['id_vehiculo'] ?>" data-km="<?= $v['km_actual'] ?>">
                                    <?= htmlspecialchars($v['patente']) ?> -
                                    <?= htmlspecialchars($v['marca']) ?>
                                    <?= htmlspecialchars($v['modelo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label">Tanque Origen</label>
                        <select name="id_tanque" class="form-control" required>
                            <option value="">Seleccione tanque...</option>
                            <?php foreach ($tanques as $t): ?>
                                <option value="<?= $t['id_tanque'] ?>">
                                    <?= htmlspecialchars($t['nombre']) ?> (Stock:
                                    <?= $t['stock_actual'] ?>L)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 15px;">
                    <div style="flex: 1;">
                        <label class="form-label">KM Último</label>
                        <input type="number" id="km_ultimo" name="km_ultimo" class="form-control" readonly
                            style="background: rgba(255,255,255,0.05);">
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label">KM Actual</label>
                        <input type="number" id="km_actual" name="km_actual" class="form-control" required
                            oninput="calculateEstimation()">
                    </div>
                </div>
            </div>

            <!-- Step 2: Estimation -->
            <div id="estimationSection" class="section-card"
                style="margin-bottom: 20px; padding: 15px; background: rgba(100,181,246,0.05); border-radius: 12px; border: 1px solid rgba(100,181,246,0.1); display: none;">
                <h4 style="margin-top: 0; color: var(--accent-primary);"><i class="fas fa-calculator"></i> 2. Estimación
                </h4>
                <div style="display: flex; justify-content: space-around; text-align: center;">
                    <div>
                        <div class="text-muted" style="font-size: 0.8em;">KM Recorridos</div>
                        <div id="km_diff" style="font-size: 1.5em; font-weight: bold; color: var(--text-primary);">0
                        </div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.8em;">Litros Estimados</div>
                        <div id="litros_est" style="font-size: 1.5em; font-weight: bold; color: var(--accent-primary);">
                            0.00</div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Load -->
            <div class="section-card"
                style="margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <h4 style="margin-top: 0; color: var(--text-primary);"><i class="fas fa-fill-drip"></i> 3. Carga Física
                </h4>
                <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label">Litros Cargados</label>
                        <input type="number" step="0.01" id="litros_cargados" name="litros_cargados"
                            class="form-control" required oninput="runVerification()">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label">Proveedor / Precio</label>
                        <select name="id_proveedor" class="form-control">
                            <option value="">Seleccione proveedor...</option>
                            <?php foreach ($proveedores as $p): ?>
                                <option value="<?= $p['id_proveedor'] ?>">
                                    <?= htmlspecialchars($p['razon_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 15px;">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label">Personal Entrega</label>
                        <select name="id_personal_entrega" class="form-control" required>
                            <?php foreach ($personal as $p): ?>
                                <option value="<?= $p['id_personal'] ?>">
                                    <?= htmlspecialchars($p['nombre_apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label">Personal Recepción (Chofer)</label>
                        <select name="id_personal_recepcion" class="form-control" required>
                            <?php foreach ($personal as $p): ?>
                                <option value="<?= $p['id_personal'] ?>">
                                    <?= htmlspecialchars($p['nombre_apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 4: Verification -->
            <div id="verificationSection" class="section-card"
                style="margin-bottom: 30px; padding: 15px; border-radius: 12px; display: none;">
                <h4 style="margin-top: 0;"><i class="fas fa-check-double"></i> 4. Verificación</h4>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div id="verif_status" style="font-weight: bold; font-size: 1.2em;">-</div>
                    <div id="verif_diff" style="font-family: monospace;">-</div>
                </div>
            </div>

            <div style="text-align: right;">
                <button type="button" class="btn btn-outline"
                    onclick="window.location.href='index.php'">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="fas fa-save"></i> Registrar Traspaso y Generar Remito
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const TOLERANCIA = 2.0; // Liters
    const FACTOR_EFICIENCIA = 0.15; // L/Km

    function updateKmUltimo() {
        const select = document.getElementById('id_vehiculo');
        const option = select.options[select.selectedIndex];
        const km = option.dataset.km || 0;
        document.getElementById('km_ultimo').value = km;
        document.getElementById('km_actual').min = km;
        calculateEstimation();
    }

    function calculateEstimation() {
        const kmu = parseInt(document.getElementById('km_ultimo').value) || 0;
        const kma = parseInt(document.getElementById('km_actual').value) || 0;
        const diff = kma - kmu;

        if (kma > kmu) {
            document.getElementById('estimationSection').style.display = 'block';
            document.getElementById('km_diff').textContent = diff;
            const est = (diff * FACTOR_EFICIENCIA).toFixed(2);
            document.getElementById('litros_est').textContent = est;
            runVerification();
        } else {
            document.getElementById('estimationSection').style.display = 'none';
        }
    }

    function runVerification() {
        const est = parseFloat(document.getElementById('litros_est').textContent) || 0;
        const carg = parseFloat(document.getElementById('litros_cargados').value) || 0;

        if (carg <= 0) {
            document.getElementById('verificationSection').style.display = 'none';
            return;
        }

        const diff = Math.abs(carg - est);
        const section = document.getElementById('verificationSection');
        const status = document.getElementById('verif_status');
        const diffText = document.getElementById('verif_diff');

        section.style.display = 'block';
        if (diff <= TOLERANCIA) {
            section.style.background = 'rgba(16,185,129,0.1)';
            section.style.border = '1px solid var(--color-success)';
            status.innerHTML = '<i class="fas fa-check-circle" style="color: var(--color-success);"></i> VERIFICA';
            status.style.color = 'var(--color-success)';
        } else {
            section.style.background = 'rgba(239,68,68,0.1)';
            section.style.border = '1px solid var(--color-danger)';
            status.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: var(--color-danger);"></i> ALERTA DE CONSUMO';
            status.style.color = 'var(--color-danger)';
        }
        diffText.textContent = 'Diferencia: ' + diff.toFixed(2) + ' litros';
    }

    document.getElementById('fuelForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());
        payload.litros_estimados = document.getElementById('litros_est').textContent;

        try {
            const response = await fetch('api/save_fuel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (result.status === 'success') {
                showToast('✓ Traspaso exitoso', 'success');
                setTimeout(() => window.location.href = 'index.php', 1500);
            } else {
                alert('Error [' + result.errorId + ']: ' + result.message);
                btn.disabled = false;
            }
        } catch (err) {
            alert('Error de conexión.');
            btn.disabled = false;
        }
    });
</script>

<style>
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-primary {
        background: var(--color-warning);
        color: var(--bg-primary);
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--text-muted);
        color: var(--text-secondary);
    }
</style>

<?php require_once '../../includes/footer.php'; ?>