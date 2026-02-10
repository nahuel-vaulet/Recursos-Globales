<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Get Tank ID from URL
$id_tanque = isset($_GET['id_tanque']) ? $_GET['id_tanque'] : null;
$tank = null;

if ($id_tanque) {
    $stmt = $pdo->prepare("SELECT * FROM combustibles_tanques WHERE id_tanque = ?");
    $stmt->execute([$id_tanque]);
    $tank = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$tank) {
    echo "<div class='alert alert-danger'>Tanque no encontrado.</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch Squads
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);
$cuadrillas_json = json_encode($cuadrillas);

// Fetch Personal
$personal = $pdo->query("SELECT id_personal, nombre_apellido, id_cuadrilla FROM personal ORDER BY nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);
$personal_json = json_encode($personal);
?>

<div class="container" style="max-width: 900px; padding-top: 20px;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-file-invoice"></i> Nuevo Remito de Combustible</h2>
        <a href="index.php" class="btn btn-outline-secondary">Volver</a>
    </div>

    <!-- TANK INFO CARD -->
    <div class="card mb-4" style="border-left: 5px solid #007bff;">
        <div class="card-body">
            <h5 class="card-title">Origen:
                <?php echo htmlspecialchars($tank['nombre']); ?> (
                <?php echo $tank['tipo_combustible']; ?>)
            </h5>
            <p class="card-text">
                Stock Disponible: <strong>
                    <?php echo number_format($tank['stock_actual'], 2); ?> L
                </strong>
            </p>
        </div>
    </div>

    <form id="formRemito" onsubmit="submitRemito(event)">
        <input type="hidden" name="id_tanque" value="<?php echo $id_tanque; ?>">
        <input type="hidden" name="tipo_combustible" value="<?php echo $tank['tipo_combustible']; ?>">

        <!-- FLOW PANEL -->
        <div class="card mb-4 p-3">
            <h5 class="mb-3"><i class="fas fa-route"></i> Destinatario</h5>
            <div class="row">
                <div class="col-md-6">
                    <label>Cuadrilla (Destino)</label>
                    <select name="id_cuadrilla" id="select_cuadrilla" class="form-control"
                        onchange="onCuadrillaChange()" required>
                        <option value="">Seleccione Cuadrilla...</option>
                        <?php foreach ($cuadrillas as $c): ?>
                            <option value="<?php echo $c['id_cuadrilla']; ?>">
                                <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Vehículo / Equipo</label>
                    <select name="id_vehiculo" id="select_vehiculo" class="form-control" required>
                        <option value="">-- Pendiente de Cuadrilla --</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label>Conductor / Responsable</label>
                    <select name="usuario_conductor" id="select_conductor" class="form-control" required>
                        <option value="">-- Pendiente de Cuadrilla --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Destino / Obra (Opcional)</label>
                    <input type="text" name="destino_obra" class="form-control" placeholder="Ej: Obra Centro">
                </div>
            </div>
        </div>

        <!-- DETAILS PANEL -->
        <div class="card mb-4 p-3">
            <h5 class="mb-3"><i class="fas fa-gas-pump"></i> Detalle de Carga</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Cantidad (Litros)</label>
                        <input type="number" step="0.01" name="litros" class="form-control form-control-lg" required
                            placeholder="0.00" style="font-weight: bold;">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Odómetro (Km/Hs)</label>
                        <input type="number" step="1" name="odometro_actual" class="form-control" required
                            placeholder="Ej: 15000">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fecha y Hora</label>
                        <input type="datetime-local" name="fecha_hora" class="form-control"
                            value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" style="height: 60px; font-size: 1.25em;">
            <i class="fas fa-save"></i> Generar Remito
        </button>

    </form>
</div>

<!-- DATA ISLAND -->
<div id="server-data" data-cuadrillas='<?php echo htmlspecialchars($cuadrillas_json, ENT_QUOTES, 'UTF-8'); ?>'
    data-personal='
    <?php echo htmlspecialchars($personal_json, ENT_QUOTES, 'UTF-8'); ?>'
    style="display:none;">
</div>

<script>
    function onCuadrillaChange() {
        const idCuadrilla = document.getElementById('select_cuadrilla').value;
        const serverDataEl = document.getElementById('server-data');
        const cuadrillas = JSON.parse(serverDataEl.dataset.cuadrillas || '[]');
        const personal = JSON.parse(serverDataEl.dataset.personal || '[]');

        // Update Vehicle
        const selectVehiculo = document.getElementById('select_vehiculo');
        selectVehiculo.innerHTML = '<option value="">-- Seleccione --</option>';

        const squad = cuadrillas.find(c => c.id_cuadrilla == idCuadrilla);
        if (squad && squad.id_vehiculo) {
            const opt = document.createElement('option');
            opt.value = squad.id_vehiculo;
            opt.textContent = `${squad.marca} ${squad.modelo} (${squad.patente})`;
            opt.selected = true;
            selectVehiculo.appendChild(opt);
        }

        // Update Driver
        const selectConductor = document.getElementById('select_conductor');
        selectConductor.innerHTML = '<option value="">-- Seleccione conductor --</option>';

        const drivers = personal.filter(p => p.id_cuadrilla == idCuadrilla);
        drivers.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.nombre_apellido;
            opt.textContent = p.nombre_apellido;
            selectConductor.appendChild(opt);
        });
    }

    function submitRemito(event) {
        event.preventDefault();
        if (!confirm('¿Confirma la generación del remito? Esto descontará stock.')) return;

        const form = event.target;
        const formData = new FormData(form);

        fetch('api/save_despacho.php', { // Reusing logic
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to success / print page or dashboard
                    alert('Remito generado exitosamente.');
                    window.location.href = 'index.php';
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión.');
            });
    }
</script>

<?php require_once '../../includes/footer.php'; ?>