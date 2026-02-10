<?php
/**
 * Módulo Herramientas - Formulario Crear/Editar
 * [!] ARQUITECTURA: Formulario unificado para creación y edición
 * [✓] AUDITORÍA CRUD: CREATE/UPDATE con validación
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

$isEdit = false;
$herramienta = [
    'id_herramienta' => '',
    'nombre' => '',
    'descripcion' => '',
    'numero_serie' => '',
    'marca' => '',
    'modelo' => '',
    'precio_reposicion' => '',
    'id_proveedor' => '',
    'foto' => '',
    'estado' => 'Disponible',
    'fecha_compra' => '',
    'fecha_calibracion' => ''
];

if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM herramientas WHERE id_herramienta = ?");
    $stmt->execute([$_GET['id']]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($found) {
        $herramienta = $found;
        $isEdit = true;
    }
}

$estados = ['Disponible', 'Asignada', 'Reparación', 'Baja'];

// Obtener proveedores
$proveedores = $pdo->query("SELECT id_proveedor, razon_social FROM proveedores ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0; color: var(--text-primary);">
                <i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $isEdit ? 'Editar Herramienta' : 'Nueva Herramienta'; ?>
            </h2>
        </div>
        <a href="index.php" class="btn btn-outline"
            style="color: var(--text-secondary); border-color: var(--text-muted);">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="save.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_herramienta" value="<?php echo $herramienta['id_herramienta']; ?>">
        <input type="hidden" name="foto_actual" value="<?php echo $herramienta['foto'] ?? ''; ?>">

        <div style="display: grid; grid-template-columns: 1fr 200px; gap: 25px;">
            <!-- Columna Principal -->
            <div>
                <!-- Información Básica -->
                <div class="form-section"
                    style="border: 1px solid var(--accent-primary); border-left: 5px solid var(--accent-primary); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-info-circle"></i>
                        Información Básica</h3>

                    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Nombre
                                *</label>
                            <input type="text" name="nombre" class="form-control" required
                                value="<?php echo htmlspecialchars($herramienta['nombre']); ?>"
                                placeholder="Ej: Taladro Percutor"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Estado</label>
                            <select name="estado" class="form-control"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                                <?php foreach ($estados as $e): ?>
                                    <option value="<?php echo $e; ?>" <?php echo $herramienta['estado'] === $e ? 'selected' : ''; ?>>
                                        <?php echo $e; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row"
                        style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Marca</label>
                            <input type="text" name="marca" class="form-control"
                                value="<?php echo htmlspecialchars($herramienta['marca'] ?? ''); ?>"
                                placeholder="Ej: Makita"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Modelo</label>
                            <input type="text" name="modelo" class="form-control"
                                value="<?php echo htmlspecialchars($herramienta['modelo'] ?? ''); ?>"
                                placeholder="Ej: HP1630"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Nro
                                Serie</label>
                            <input type="text" name="numero_serie" class="form-control"
                                value="<?php echo htmlspecialchars($herramienta['numero_serie'] ?? ''); ?>"
                                placeholder="Opcional"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label
                            style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"
                            placeholder="Descripción adicional, accesorios, características..."
                            style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;"><?php echo htmlspecialchars($herramienta['descripcion'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Información Financiera -->
                <div class="form-section"
                    style="border: 1px solid var(--color-success); border-left: 5px solid var(--color-success); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-dollar-sign"></i> Compra y
                        Proveedor</h3>

                    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Precio
                                Reposición ($)</label>
                            <input type="number" step="0.01" name="precio_reposicion" class="form-control"
                                value="<?php echo $herramienta['precio_reposicion']; ?>" placeholder="0.00"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Proveedor</label>
                            <select name="id_proveedor" class="form-control"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                                <option value="">-- Sin proveedor --</option>
                                <?php foreach ($proveedores as $p): ?>
                                    <option value="<?php echo $p['id_proveedor']; ?>" <?php echo ($herramienta['id_proveedor'] ?? '') == $p['id_proveedor'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['razon_social']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Fecha
                                de Compra</label>
                            <input type="date" name="fecha_compra" class="form-control"
                                value="<?php echo $herramienta['fecha_compra'] ?? ''; ?>"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Fecha
                                Calibración</label>
                            <input type="date" name="fecha_calibracion" class="form-control"
                                value="<?php echo $herramienta['fecha_calibracion'] ?? ''; ?>"
                                style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted); border-radius: 6px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Foto -->
            <div>
                <div class="form-section"
                    style="border: 1px solid var(--text-muted); padding: 15px; border-radius: 8px; text-align: center;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-camera"></i> Foto
                    </label>

                    <div id="photoPreview"
                        style="width: 100%; height: 180px; background: var(--bg-tertiary); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; overflow: hidden; border: 2px dashed var(--text-muted);">
                        <?php if (!empty($herramienta['foto'])): ?>
                            <img src="<?php echo htmlspecialchars($herramienta['foto']); ?>" alt="Foto"
                                style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <div style="color: var(--text-muted); text-align: center;">
                                <i class="fas fa-image" style="font-size: 3em; margin-bottom: 10px;"></i><br>
                                <small>Sin foto</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <input type="file" name="foto" id="inputFoto" accept="image/*" style="display: none;"
                        onchange="previewPhoto(this)">
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="document.getElementById('inputFoto').click()"
                        style="width: 100%; color: var(--accent-primary); border-color: var(--accent-primary);">
                        <i class="fas fa-upload"></i> Subir Foto
                    </button>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <a href="index.php" class="btn btn-outline"
                style="color: var(--text-secondary); border-color: var(--text-muted);">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                <?php echo $isEdit ? 'Guardar Cambios' : 'Crear Herramienta'; ?>
            </button>
        </div>
    </form>
</div>

<script>
    function previewPhoto(input) {
        const preview = document.getElementById('photoPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>