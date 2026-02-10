<?php
// Load necessary data for the form
require_once '../../../config/database.php';

// Fetch Tanks
$tanques = $pdo->query("SELECT * FROM combustibles_tanques WHERE estado='Activo'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Squads
$cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Vehicles (including new columns id_cuadrilla and tipo_combustible)
$vehiculos = $pdo->query("SELECT id_vehiculo, marca, modelo, patente, id_cuadrilla, tipo_combustible FROM vehiculos WHERE estado = 'Operativo' ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Personnel for Drivers list
$personal = $pdo->query("SELECT id_personal, nombre_apellido, id_cuadrilla FROM personal ORDER BY nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);

// Prepare JSON for JS
$json_cuadrillas = json_encode($cuadrillas);
$json_vehiculos = json_encode($vehiculos); // New full list
$json_personal = json_encode($personal);
?>

<form id="formDespacho" onsubmit="submitDespacho(event)">

    <!-- 1. Select Tank (unchanged) -->
    <div class="form-group">
        <label>Tanque de Origen</label>
        <select name="id_tanque" class="form-control" required>
            <?php foreach ($tanques as $t): ?>
                <option value="<?php echo $t['id_tanque']; ?>" data-tipo="<?php echo $t['tipo_combustible']; ?>">
                    <?php echo $t['nombre']; ?> (Disp: <?php echo $t['stock_actual']; ?> L) -
                    <?php echo $t['tipo_combustible']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 2. Select Squad (New Parent) -->
    <div class="form-group">
        <label>Cuadrilla Asignada</label>
        <select id="select_cuadrilla" class="form-control" onchange="onCuadrillaChange()" required>
            <option value="">Seleccione Cuadrilla...</option>
            <?php foreach ($cuadrillas as $c): ?>
                <option value="<?php echo $c['id_cuadrilla']; ?>">
                    <?php echo $c['nombre_cuadrilla']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 3. Auto-Select Vehicle -->
    <div class="form-group">
        <label>Vehículo / Maquinaria</label>
        <select name="id_vehiculo" id="select_vehiculo" class="form-control" required>
            <option value="">Seleccione primero una cuadrilla...</option>
            <!-- Auto-filled by JS -->
        </select>
    </div>

    <div class="row" style="display: flex; gap: 10px;">
        <div class="col" style="flex:1;">
            <div class="form-group">
                <label>Litros Despachados</label>
                <input type="number" step="0.01" name="litros" class="form-control" required placeholder="0.00">
            </div>
        </div>
        <div class="col" style="flex:1;">
            <div class="form-group">
                <label>Odómetro Actual (km/hs)</label>
                <input type="number" step="0.1" name="odometro_actual" class="form-control" required
                    placeholder="Ej: 150000">
            </div>
        </div>
    </div>

    <!-- 4. Select Driver (Filtered) -->
    <div class="form-group">
        <label>Conductor / Responsable</label>
        <select name="usuario_conductor" id="select_conductor" class="form-control" required>
            <option value="">Seleccione conductor...</option>
            <!-- Auto-filled by JS -->
        </select>
    </div>

    <div class="form-group">
        <label>Destino / Obra (Opcional)</label>
        <input type="text" name="destino_obra" class="form-control" placeholder="Ej: Obra Centro">
    </div>

    <div class="form-group">
        <label>Fecha Hora</label>
        <input type="datetime-local" name="fecha_hora" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>"
            required>
    </div>

    <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeCombustibleModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Registrar Salida</button>
    </div>

    <!-- HIDDEN DATA PAYLOAD -->
    <div id="server-data-despacho"
        data-cuadrillas='<?php echo htmlspecialchars($json_cuadrillas, ENT_QUOTES, 'UTF-8'); ?>'
        data-personal='<?php echo htmlspecialchars($json_personal, ENT_QUOTES, 'UTF-8'); ?>'
        data-vehiculos='<?php echo htmlspecialchars($json_vehiculos, ENT_QUOTES, 'UTF-8'); ?>' style="display:none;">
    </div>
</form>