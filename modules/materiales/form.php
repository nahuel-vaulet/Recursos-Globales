<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id = $_GET['id'] ?? null;
$material = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM maestro_materiales WHERE id_material = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Contacts for Dropdowns
$contactos = $pdo->query("SELECT c.id_contacto, c.nombre_vendedor, p.razon_social 
                          FROM proveedores_contactos c 
                          JOIN proveedores p ON c.id_proveedor = p.id_proveedor 
                          ORDER BY p.razon_social")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <h1><?php echo $id ? 'Editar' : 'Nuevo'; ?> Material</h1>

    <form action="save.php" method="POST">
        <?php if ($id): ?>
            <input type="hidden" name="id_material" value="<?php echo $id; ?>">
        <?php endif; ?>

        <!-- Sección 1: Básico -->
        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">Detalles Generales</h3>
        <div
            style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
            <div>
                <label style="display: block; font-weight: 500;">Nombre del Material *</label>
                <input type="text" name="nombre" required value="<?php echo $material['nombre'] ?? ''; ?>"
                    class="form-control-sm" style="width: 100%;">
            </div>
            <div>
                <label style="display: block; font-weight: 500;">Código (Opcional)</label>
                <input type="text" name="codigo" disabled placeholder="Autogenerado" class="form-control-sm"
                    style="width: 100%; opacity: 0.7;">
            </div>
        </div>

        <div style="margin-bottom: var(--spacing-md);">
            <label style="display: block; font-weight: 500;">Descripción</label>
            <textarea name="descripcion" rows="2" placeholder="Ej: Arena fina lavada..." class="form-control-sm"
                style="width: 100%; height: auto;"><?php echo $material['descripcion'] ?? ''; ?></textarea>
        </div>

        <div
            style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
            <div>
                <label style="display: block; font-weight: 500;">Unidad de Medida *</label>
                <select name="unidad_medida" required class="form-control-sm" style="width: 100%;">
                    <option value="">Seleccione...</option>
                    <?php
                    $unidades = ['M3', 'M2', 'ML', 'Bolsas', 'Unidades', 'Kg', 'Litros'];
                    foreach ($unidades as $u) {
                        $sel = ($material['unidad_medida'] ?? '') == $u ? 'selected' : '';
                        echo "<option value='$u' $sel>$u</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 500;">Punto de Pedido (Alerta)</label>
                <input type="number" step="0.01" name="punto_pedido" class="form-control-sm"
                    value="<?php echo $material['punto_pedido'] ?? ''; ?>" style="width: 100%;">
            </div>
        </div>

        <!-- Sección 2: Costos y Proveedores -->
        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">Proveedores y Costos</h3>

        <div
            style="background: var(--bg-secondary); padding: 15px; border: 1px solid rgba(100, 181, 246, 0.15); border-radius: 8px; margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; color: var(--color-primary);">Proveedor Primario</label>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                <select name="id_contacto_primario" class="form-control-sm" style="width: 100%;">
                    <option value="">-- Seleccionar Contacto --</option>
                    <?php foreach ($contactos as $c): ?>
                        <option value="<?php echo $c['id_contacto']; ?>" <?php echo ($material['id_contacto_primario'] ?? '') == $c['id_contacto'] ? 'selected' : ''; ?>>
                            <?php echo $c['razon_social'] . ' (' . $c['nombre_vendedor'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" name="costo_primario" placeholder="$ Costo"
                    value="<?php echo $material['costo_primario'] ?? ''; ?>" class="form-control-sm"
                    style="width: 100%;">
            </div>
        </div>

        <div
            style="background: var(--bg-secondary); padding: 15px; border: 1px solid rgba(100, 181, 246, 0.15); border-radius: 8px; margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; color: var(--text-secondary);">Proveedor Secundario</label>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                <select name="id_contacto_secundario" class="form-control-sm" style="width: 100%;">
                    <option value="">-- Seleccionar Contacto --</option>
                    <?php foreach ($contactos as $c): ?>
                        <option value="<?php echo $c['id_contacto']; ?>" <?php echo ($material['id_contacto_secundario'] ?? '') == $c['id_contacto'] ? 'selected' : ''; ?>>
                            <?php echo $c['razon_social'] . ' (' . $c['nombre_vendedor'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" name="costo_secundario" placeholder="$ Costo"
                    value="<?php echo $material['costo_secundario'] ?? ''; ?>" class="form-control-sm"
                    style="width: 100%;">
            </div>
        </div>

        <?php if ($id): ?>
            <div style="text-align: right; color: #666; font-size: 0.8em; margin-bottom: 10px;">
                Última Cotización: <?php echo $material['fecha_ultima_cotizacion'] ?? '-'; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: var(--spacing-lg); text-align: right;">
            <a href="index.php" class="btn btn-outline" style="margin-right: var(--spacing-md);">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>