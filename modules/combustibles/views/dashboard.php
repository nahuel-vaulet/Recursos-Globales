<?php
// Retrieve Tank Data
try {
    $stmtTanques = $pdo->query("SELECT * FROM combustibles_tanques ORDER BY id_tanque ASC");
    $tanques = $stmtTanques->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tanques = [];
}

// Fetch Data for Dropdowns (Dashboard Context)
try {
    $cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cuadrillas = [];
}

try {
    $personal = $pdo->query("SELECT id_personal, nombre_apellido, id_cuadrilla FROM personal ORDER BY nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $personal = [];
}

try {
    $vehiculos = $pdo->query("SELECT id_vehiculo, marca, modelo, patente, id_cuadrilla, tipo_combustible FROM vehiculos WHERE estado = 'Operativo' ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $vehiculos = [];
}

?>

<script>
    window.FUEL_DATA = {
        cuadrillas: <?php echo json_encode($cuadrillas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]'; ?>,
        personal: <?php echo json_encode($personal, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]'; ?>,
        vehiculos: <?php echo json_encode($vehiculos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]'; ?>
    };
</script>

<style>
    /* Critical Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
        /* Hidden by default */
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }

    .modal-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        margin: 5% auto;
        /* Fallback for flex centering */
        position: relative;
        max-height: 90vh;
        /* Prevent overflow */
        overflow-y: auto;
    }

    /* Dark theme support if applicable */
    [data-theme="dark"] .modal-content {
        background: #2d3436;
        color: #dfe6e9;
    }
</style>

<!-- Fuel Dashboard Panel -->
<div class="card panel-fuel" style="margin-top: 20px; border-top: 4px solid #ff9800;">
    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3><i class="fas fa-gas-pump"></i> Control de Combustibles</h3>
        <div class="fuel-actions">
            <button onclick="openCombustibleModal('carga')" class="btn btn-sm btn-success">
                <i class="fas fa-plus"></i> Ingreso (Compra)
            </button>
            <button onclick="openCombustibleModal('despacho')" class="btn btn-sm btn-primary">
                <i class="fas fa-truck-moving"></i> Despacho (Surtidor)
            </button>
        </div>
    </div>

    <div class="fuel-tanks-container" style="display: flex; gap: 20px; padding: 15px; overflow-x: auto;">
        <?php foreach ($tanques as $t):
            $percent = ($t['capacidad_maxima'] > 0) ? ($t['stock_actual'] / $t['capacidad_maxima']) * 100 : 0;
            $color = ($percent < 20) ? '#dc3545' : (($percent < 50) ? '#ffc107' : '#28a745');
            ?>
            <div class="card h-100 shadow-sm panel-tank" style="min-width: 200px; position: relative; overflow: hidden;">
                <div class="tank-level"
                    style="position: absolute; bottom: 0; left: 0; width: 100%; height: <?php echo $percent; ?>%; background: <?php echo $color; ?>; opacity: 0.15; z-index: 0;">
                </div>

                <div class="card-body p-3" style="position: relative; z-index: 1;">
                    <div class="font-weight-bold opacity-75">
                        <?php echo htmlspecialchars($t['nombre']); ?>
                    </div>
                    <div class="text-muted small mb-2">
                        <?php echo htmlspecialchars($t['tipo_combustible']); ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <div style="font-size: 1.5em; font-weight: bold;">
                            <?php echo number_format($t['stock_actual'], 0); ?> <span style="font-size: 0.5em;">L</span>
                        </div>
                        <div class="text-muted small">
                            / <?php echo number_format($t['capacidad_maxima'], 0); ?> L
                        </div>
                    </div>

                    <div class="progress" style="height: 6px; margin-top: 8px;">
                        <div class="progress-bar" role="progressbar"
                            style="width: <?php echo $percent; ?>%; background-color: <?php echo $color; ?>;">
                        </div>
                    </div>

                    <button class="btn btn-primary btn-sm btn-block mt-3 btn-mover-tank"
                        data-tank-id="<?php echo $t['id_tanque']; ?>"
                        data-tank-name="<?php echo htmlspecialchars($t['nombre'], ENT_QUOTES); ?>"
                        data-tank-max="<?php echo $t['stock_actual']; ?>"
                        data-tank-type="<?php echo htmlspecialchars($t['tipo_combustible'], ENT_QUOTES); ?>"
                        style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-share"></i> Mover
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- SQUADS PANEL (Integrated) -->
    <div class="panel-squads-container"
        style="border-top: 1px solid rgba(0,0,0,0.1); margin-top: 20px; padding-top: 20px; padding-left: 15px; padding-right: 15px;">
        <div class="panel-header-squads" style="margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; color: var(--text-primary); margin: 0;">
                <i class="fas fa-shipping-fast"></i> En Tránsito / Cuadrillas (<?php echo count($squads_data); ?>
                Activas)
            </h3>
        </div>

        <div class="squads-carousel custom-scrollbar">
            <?php if (empty($squads_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i> Todo el material está en depósito central.
                </div>
            <?php endif; ?>

            <?php foreach ($squads_data as $id_cuadrilla => $squad): ?>
                <div class="squad-card fade-in">
                    <div class="squad-card-header">
                        <div><i class="fas fa-hard-hat"></i> <?php echo $squad['name']; ?></div>
                        <a href="../stock_cuadrilla/detalle.php?id=<?php echo $id_cuadrilla; ?>" class="btn-detail"
                            title="Ver Detalle">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>

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

<!-- Modal Combustibles (Loader) -->
<div id="modalCombustibles" class="modal-overlay">
    <div class="modal-content" style="width: 500px; max-width: 95%;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <h3 id="modalCombTitle">Registrar Operación</h3>
            <button onclick="closeCombustibleModal()" style="background:none; border:none; cursor:pointer;"><i
                    class="fas fa-times"></i></button>
        </div>
        <div id="modalCombBody">
            <p>Cargando formulario...</p>
        </div>
    </div>
</div>

<!-- REDIRECT TANK MOVER TO UNIFIED FUEL FLOW -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-mover-tank').forEach(btn => {
            btn.onclick = function () {
                const tankId = this.getAttribute('data-tank-id');
                if (typeof openFuelTransferModal === 'function') {
                    openFuelTransferModal(tankId);
                } else {
                    console.error('Unified Fuel Modal function not found.');
                }
            };
        });
    });
</script>

<script>
    function openCombustibleModal(type) {
        var modal = document.getElementById('modalCombustibles');
        var title = document.getElementById('modalCombTitle');
        var body = document.getElementById('modalCombBody');

        modal.style.display = 'block';

        if (type === 'carga') {
            title.innerHTML = '<i class="fas fa-cart-plus"></i> Nueva Carga de Combustible';
            fetch('../combustibles/views/form_carga.php').then(function (res) { return res.text(); }).then(function (html) {
                body.innerHTML = html;
            });
        } else {
            title.innerHTML = '<i class="fas fa-gas-pump"></i> Nuevo Despacho';
            fetch('../combustibles/views/form_despacho.php').then(function (res) { return res.text(); }).then(function (html) {
                body.innerHTML = html;
            });
        }
    }

    function closeCombustibleModal() {
        document.getElementById('modalCombustibles').style.display = 'none';
        location.reload();
    }

    function setMode(mode) {
        var btnStock = document.getElementById('btnStock');
        var btnVehicle = document.getElementById('btnVehicle');

        if (mode === 'stock') {
            btnStock.classList.remove('btn-light');
            btnStock.classList.add('btn-primary');
            btnStock.style = 'flex: 1; border-radius: 0;';

            btnVehicle.classList.remove('btn-primary');
            btnVehicle.classList.add('btn-light');
            btnVehicle.style = 'flex: 1; border-radius: 0;';

            document.getElementById('sectionStock').style.display = 'block';
            document.getElementById('sectionVehicle').style.display = 'none';
        } else {
            btnVehicle.classList.remove('btn-light');
            btnVehicle.classList.add('btn-primary');
            btnVehicle.style = 'flex: 1; border-radius: 0;';

            btnStock.classList.remove('btn-primary');
            btnStock.classList.add('btn-light');
            btnStock.style = 'flex: 1; border-radius: 0;';

            document.getElementById('sectionStock').style.display = 'none';
            document.getElementById('sectionVehicle').style.display = 'block';
        }
        document.getElementById('inputDestinoTipo').value = mode;
    }

    function onCuadrillaCargaChange() {
        var idCuadrilla = document.getElementById('select_cuadrilla_carga').value;
        var serverDataEl = document.getElementById('server-data-carga'); // Updated ID
        if (!serverDataEl) return;

        var personal = JSON.parse(serverDataEl.dataset.personal || '[]');
        var vehiculos = JSON.parse(serverDataEl.dataset.vehiculos || '[]');

        var selectVehiculo = document.getElementById('select_vehiculo_carga');
        selectVehiculo.innerHTML = '<option value="">-- Sin Vehículo --</option>';

        // Filter vehicles by squad
        var squadVehicles = vehiculos.filter(function (v) { return String(v.id_cuadrilla) === String(idCuadrilla); });

        if (squadVehicles.length > 0) {
            squadVehicles.forEach(function (v) {
                var opt = document.createElement('option');
                opt.value = v.id_vehiculo;
                opt.textContent = v.marca + ' ' + v.modelo + ' (' + v.patente + ')';
                opt.dataset.tipo = v.tipo_combustible || '';
                selectVehiculo.appendChild(opt);
            });
        }

        // Setup Validation
        selectVehiculo.onchange = validateDirectLoadFuel;
        var fuelTypeSelect = document.querySelector('select[name="tipo_combustible"]');
        if (fuelTypeSelect) fuelTypeSelect.onchange = validateDirectLoadFuel;

        var selectConductor = document.getElementById('select_conductor_carga');
        selectConductor.innerHTML = '<option value="">-- Seleccione --</option>';

        var drivers = personal.filter(function (p) { return String(p.id_cuadrilla) === String(idCuadrilla); });
        drivers.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.nombre_apellido;
            opt.textContent = p.nombre_apellido;
            selectConductor.appendChild(opt);
        });
    }

    function validateDirectLoadFuel() {
        var selectType = document.querySelector('select[name="tipo_combustible"]');
        var selectVehiculo = document.getElementById('select_vehiculo_carga');

        if (!selectType || !selectVehiculo) return;

        var selectedFuel = selectType.value;
        var optionVehiculo = selectVehiculo.options[selectVehiculo.selectedIndex];

        if (!optionVehiculo || !optionVehiculo.value) return;

        var vehicleFuel = optionVehiculo.dataset.tipo;

        // Map basic types if necessary (e.g., Gasoil vs Diesel)
        var fuelMap = {
            'Gasoil': 'Diesel',
            'Diesel': 'Diesel',
            'Nafta': 'Nafta',
            'GNC': 'GNC'
        };

        var mappedSelected = fuelMap[selectedFuel] || selectedFuel;
        var mappedVehicle = fuelMap[vehicleFuel] || vehicleFuel;

        if (mappedVehicle && mappedSelected && mappedSelected !== mappedVehicle) {
            alert('⛔ ERROR FATAL: Incompatibilidad de Combustible.\n\n' +
                'Seleccionado: ' + selectedFuel + '\n' +
                'Vehículo: ' + vehicleFuel + '\n\n' +
                'Operación bloqueada para evitar daños.');

            // BLOCKING ACTION
            selectVehiculo.value = "";
            selectVehiculo.style.borderColor = "red";
        } else {
            selectVehiculo.style.borderColor = "";
        }
    }

    function submitCarga(event) {
        event.preventDefault();
        var form = event.target;
        var formData = new FormData(form);

        fetch('../combustibles/api/save_carga.php', {
            method: 'POST',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    alert('Carga registrada correctamente.');
                    closeCombustibleModal();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                alert('Error de conexión al guardar.');
            });
    }

    function onCuadrillaChange() {
        var idCuadrilla = document.getElementById('select_cuadrilla').value;
        var serverDataEl = document.getElementById('server-data-despacho'); // Updated ID
        if (!serverDataEl) {
            console.error('SERVER DATA DESPACHO NOT FOUND');
            return;
        }

        var personal = JSON.parse(serverDataEl.dataset.personal || '[]');
        var vehiculos = JSON.parse(serverDataEl.dataset.vehiculos || '[]');

        var selectVehiculo = document.getElementById('select_vehiculo');
        selectVehiculo.innerHTML = '<option value="">-- Seleccione --</option>';

        // Filter vehicles by squad (1-to-N relationship support) - Strict string comparison for safety
        var squadVehicles = vehiculos.filter(function (v) { return String(v.id_cuadrilla) === String(idCuadrilla); });

        if (squadVehicles.length > 0) {
            squadVehicles.forEach(function (v) {
                var opt = document.createElement('option');
                opt.value = v.id_vehiculo;
                opt.textContent = v.marca + ' ' + v.modelo + ' (' + v.patente + ')';
                opt.dataset.tipo = v.tipo_combustible || 'Indefinido';
                selectVehiculo.appendChild(opt);
            });
        } else {
            var opt = document.createElement('option');
            opt.textContent = "-- No hay vehículos asignados --";
            opt.disabled = true;
            selectVehiculo.appendChild(opt);
        }

        // Setup Validation on Change matches the select
        selectVehiculo.onchange = validateFuelType;

        // Conductor Logic
        var selectConductor = document.getElementById('select_conductor');
        selectConductor.innerHTML = '<option value="">-- Seleccione conductor --</option>';

        var drivers = personal.filter(function (p) { return String(p.id_cuadrilla) === String(idCuadrilla); });
        drivers.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.nombre_apellido;
            opt.textContent = p.nombre_apellido;
            selectConductor.appendChild(opt);
        });
    }

    // --- VALIDATION LOGIC (BLOCKING) ---
    function validateFuelType() {
        var selectTanque = document.querySelector('select[name="id_tanque"]');
        // Handle both modal contexts
        var selectVehiculo = document.getElementById('select_vehiculo') || document.getElementById('fuelModalVehiculo');

        if (!selectTanque || !selectVehiculo) return;

        var optionTanque = selectTanque.options[selectTanque.selectedIndex];
        var optionVehiculo = selectVehiculo.options[selectVehiculo.selectedIndex];

        // Reset styles
        selectVehiculo.style.borderColor = "";

        if (!optionTanque || !optionVehiculo || !optionVehiculo.value) return;

        // Ensure we have data-tipo (Transfer modal might need it added dynamically)
        var fuelTanque = optionTanque.dataset.tipo;
        var fuelVehiculo = optionVehiculo.dataset.tipo;

        // If Transfer Modal, Tank is fixed usually, need to get type from hidden input or global data? 
        // Actually Transfer modal doesn't have a SELECT for tank, it has a hidden input/fixed text.
        // Let's handle the Despacho form (main one) primarily here.
        // For Transfer modal we interpret 'fuelModalVehiculo' logic separately or adapt this.

        // ADAPTATION FOR DESPACHO FORM (Standard)
        if (selectVehiculo.id === 'select_vehiculo') {
            if (fuelTanque && fuelVehiculo) {
                var t = fuelTanque.toLowerCase();
                var v = fuelVehiculo.toLowerCase();
                if (t.includes('gasoil') || t.includes('diesel')) t = 'diesel';
                if (v.includes('gasoil') || v.includes('diesel')) v = 'diesel';
                if (t.includes('nafta') && v.includes('nafta')) { t = 'nafta'; v = 'nafta'; }

                if (t !== v) {
                    alert('⛔ ERROR FATAL: Incompatibilidad de Combustible.\n\n' +
                        'El vehículo requiere: ' + fuelVehiculo.toUpperCase() + '\n' +
                        'El tanque surte: ' + fuelTanque.toUpperCase() + '\n\n' +
                        'No se permite la carga para evitar daños al motor.');

                    // BLOCKING ACTION
                    selectVehiculo.value = ""; // Reset selection
                    selectVehiculo.style.borderColor = "red"; // Visual cue
                }
            }
        }
    }

    // Attach listener to Tank select
    document.addEventListener('change', function (e) {
        if (e.target.name === 'id_tanque') {
            validateFuelType();
        }
    });

    // --- TRANSFER MODAL LOGIC FIXES ---
    // Override the previous onFuelModalCuadrillaChange to include FILTERING
    window.onFuelModalCuadrillaChange = function () {
        var idCuadrilla = document.getElementById('fuelModalCuadrilla').value;
        var selectVehiculo = document.getElementById('fuelModalVehiculo');
        var selectConductor = document.getElementById('fuelModalConductor');

        // Use Global Data
        var data = window.FUEL_DATA || { cuadrillas: [], personal: [], vehiculos: [] };

        // 1. FILTER VEHICLES
        selectVehiculo.innerHTML = '<option value="">-- Seleccione Vehículo --</option>';
        var squadVehicles = data.vehiculos.filter(function (v) { return String(v.id_cuadrilla) === String(idCuadrilla); });

        squadVehicles.forEach(function (v) {
            var opt = document.createElement('option');
            opt.value = v.id_vehiculo;
            opt.textContent = v.marca + ' ' + v.modelo + ' (' + v.patente + ')';
            opt.dataset.tipo = v.tipo_combustible || 'Indefinido'; // Add data-tipo for validation
            selectVehiculo.appendChild(opt);
        });

        // 2. FILTER DRIVERS
        selectConductor.innerHTML = '<option value="">-- Seleccione Chofer --</option>';
        var squadDrivers = data.personal.filter(function (p) { return String(p.id_cuadrilla) === String(idCuadrilla); });

        squadDrivers.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.nombre_apellido;
            opt.textContent = p.nombre_apellido;
            selectConductor.appendChild(opt);
        });

        // 3. AUTO-SELECT ASSIGNED (if matches)
        var squad = data.cuadrillas.find(function (c) { return c.id_cuadrilla == idCuadrilla; });
        if (squad) {
            var val = squad.id_vehiculo_asignado || squad.id_vehiculo;
            if (val && selectVehiculo.querySelector('option[value="' + val + '"]')) {
                selectVehiculo.value = val;
                // Force validation immediately on auto-select
                setTimeout(validateTransferFuel, 100);
            }
        }

        // 4. Attach Validation to new options
        selectVehiculo.onchange = function () {
            validateTransferFuel();
        };
    };

    function validateTransferFuel() {
        var modal = document.getElementById('fuelTransferModal');
        var tankType = modal.getAttribute('data-tank-type'); // Need to ensure this is set on Open
        var selectVehiculo = document.getElementById('fuelModalVehiculo');

        if (!selectVehiculo.value) return true; // No vehicle selected yet, allow but valid check comes later
        var option = selectVehiculo.options[selectVehiculo.selectedIndex];
        var vehicleFuel = option.dataset.tipo;

        console.log('Validating Transfer:', tankType, 'vs', vehicleFuel);

        if (tankType && vehicleFuel) {
            var t = tankType.toLowerCase();
            var v = vehicleFuel.toLowerCase();
            if (t.includes('gasoil') && v.includes('diesel')) t = 'diesel';
            if (v.includes('gasoil') && t.includes('diesel')) v = 'diesel';
            if (t.includes('nafta') && v.includes('nafta')) { t = 'nafta'; v = 'nafta'; }

            if (t !== v) {
                alert('⛔ ERROR FATAL: Incompatibilidad de Combustible.\n\n' +
                    'El vehículo requiere: ' + vehicleFuel.toUpperCase() + '\n' +
                    'El tanque surte: ' + tankType.toUpperCase() + '\n\n' +
                    'Operación Cancelada.');
                selectVehiculo.value = "";
                selectVehiculo.style.borderColor = "red";
                return false; // Invalid
            } else {
                selectVehiculo.style.borderColor = "";
                return true; // Valid
            }
        }
        return true; // Assume valid if sufficient data not present (or handle as error if rigorous)
    }

    function submitDespacho(event) {
        event.preventDefault();
        var form = event.target;
        var formData = new FormData(form);

        fetch('../combustibles/api/save_despacho.php', {
            method: 'POST',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    alert('Despacho registrado correctamente.');
                    closeCombustibleModal();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                alert('Error de conexión al guardar.');
            });
    }
</script>