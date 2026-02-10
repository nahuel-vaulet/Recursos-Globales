<?php
/**
 * Módulo Herramientas - Nueva Sanción
 * [!] ARQUITECTURA: Formulario para registrar sanciones
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id_herramienta = $_GET['id'] ?? null;
$herramienta = null;

if ($id_herramienta) {
    $stmt = $pdo->prepare("SELECT * FROM herramientas WHERE id_herramienta = ?");
    $stmt->execute([$id_herramienta]);
    $herramienta = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener personal
$personal = $pdo->query("SELECT id_personal, nombre_apellido FROM personal WHERE activo = 1 ORDER BY nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);

// Obtener cuadrillas
$cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// Obtener herramientas si no viene por GET
$herramientas = [];
if (!$herramienta) {
    $herramientas = $pdo->query("SELECT id_herramienta, nombre, marca FROM herramientas WHERE estado != 'Baja' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
}

$tipos = ['Perdida', 'Rotura', 'Mal Uso'];
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0; color: #f44336;">
                <i class="fas fa-exclamation-triangle"></i> Registrar Sanción
            </h2>
            <?php if ($herramienta): ?>
                <p style="margin: 5px 0 0; color: var(--text-secondary);">
                    Herramienta: <strong>
                        <?php echo htmlspecialchars($herramienta['nombre']); ?>
                    </strong>
                </p>
            <?php endif; ?>
        </div>
        <a href="<?php echo $herramienta ? 'index.php' : 'sanciones.php'; ?>" class="btn btn-outline"
            style="color: var(--text-secondary); border-color: var(--text-muted);">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="guardar_sancion.php" method="POST">
        <?php if ($herramienta): ?>
            <input type="hidden" name="id_herramienta" value="<?php echo $herramienta['id_herramienta']; ?>">
        <?php else: ?>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Herramienta
                    *</label>
                <select name="id_herramienta" class="form-control" required
                    style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                    <option value="">-- Seleccionar Herramienta --</option>
                    <?php foreach ($herramientas as $h): ?>
                        <option value="<?php echo $h['id_herramienta']; ?>">
                            <?php echo htmlspecialchars($h['nombre']); ?>
                            <?php if ($h['marca']): ?>(
                                <?php echo htmlspecialchars($h['marca']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-section"
            style="border: 1px solid #f44336; border-left: 5px solid #f44336; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-info-circle"></i> Detalles de la
                Sanción</h3>

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label
                        style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Tipo
                        de Sanción *</label>
                    <select name="tipo_sancion" class="form-control" required
                        style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?php echo $t; ?>">
                                <?php echo $t; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label
                        style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Fecha
                        del Incidente *</label>
                    <input type="date" name="fecha_incidente" class="form-control" required
                        value="<?php echo date('Y-m-d'); ?>"
                        style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                </div>
            </div>

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label
                        style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Personal
                        Responsable *</label>
                    <select name="id_personal" class="form-control" required
                        style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?php echo $p['id_personal']; ?>">
                                <?php echo htmlspecialchars($p['nombre_apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label
                        style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Cuadrilla
                        (opcional)</label>
                    <select name="id_cuadrilla" class="form-control"
                        style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        <option value="">-- Sin cuadrilla --</option>
                        <?php foreach ($cuadrillas as $c): ?>
                            <option value="<?php echo $c['id_cuadrilla']; ?>">
                                <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Monto a
                    Descontar ($)</label>
                <input type="number" step="0.01" name="monto_descuento" class="form-control"
                    value="<?php echo $herramienta['precio_reposicion'] ?? ''; ?>" placeholder="0.00"
                    style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                <small style="color: var(--text-muted);">Se pre-carga el precio de reposición de la herramienta</small>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label
                    style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Descripción
                    del Incidente *</label>
                <textarea name="descripcion" class="form-control" rows="4" required
                    placeholder="Describa qué ocurrió, circunstancias, testigos, etc."
                    style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;"></textarea>
            </div>
        </div>

        <!-- Botones -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <a href="<?php echo $herramienta ? 'index.php' : 'sanciones.php'; ?>" class="btn btn-outline"
                style="color: var(--text-secondary); border-color: var(--text-muted);">Cancelar</a>
            <button type="submit" class="btn" style="background: #f44336; color: white; border: none;">
                <i class="fas fa-exclamation-triangle"></i> Registrar Sanción
            </button>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>