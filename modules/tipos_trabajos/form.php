<?php
/**
 * Módulo: Tipos de Trabajos
 * Formulario de creación/edición
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id = $_GET['id'] ?? null;
$trabajo = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM tipos_trabajos WHERE id_tipologia = ?");
    $stmt->execute([$id]);
    $trabajo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trabajo) {
        header("Location: index.php?msg=error");
        exit();
    }
}
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
        <a href="index.php" style="color: var(--color-neutral); font-size: 1.2em;"><i class="fas fa-arrow-left"></i></a>
        <h1 style="margin: 0;"><i class="fas fa-hard-hat"></i> <?php echo $id ? 'Editar' : 'Nuevo'; ?> Tipo de Trabajo
        </h1>
    </div>

    <form action="save.php" method="POST">
        <?php if ($id): ?>
            <input type="hidden" name="id_tipologia" value="<?php echo $id; ?>">
        <?php endif; ?>

        <!-- Sección: Identificación -->
        <h3 style="border-bottom: 2px solid var(--color-primary); padding-bottom: 8px; color: var(--color-primary);">
            <i class="fas fa-tag"></i> Identificación
        </h3>

        <div
            style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px;">
                    Código de Trabajo * <small style="color: #666;">(Ej: 3.1, 22.5)</small>
                </label>
                <input type="text" name="codigo_trabajo" required
                    value="<?php echo htmlspecialchars($trabajo['codigo_trabajo'] ?? ''); ?>" placeholder="Ej: 3.1"
                    style="">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px;">Nombre del Trabajo *</label>
                <input type="text" name="nombre" required
                    value="<?php echo htmlspecialchars($trabajo['nombre'] ?? ''); ?>"
                    placeholder="Ej: Reparación de veredas comunes" style="">
            </div>
        </div>

        <!-- Sección: Descripciones -->
        <h3 style="border-bottom: 2px solid var(--color-primary); padding-bottom: 8px; color: var(--color-primary);">
            <i class="fas fa-align-left"></i> Descripciones
        </h3>

        <div style="margin-bottom: var(--spacing-md);">
            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Descripción Breve</label>
            <input type="text" name="descripcion_breve"
                value="<?php echo htmlspecialchars($trabajo['descripcion_breve'] ?? ''); ?>"
                placeholder="Descripción corta para listados y reportes" maxlength="255" style="">
        </div>

        <div style="margin-bottom: var(--spacing-lg);">
            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Descripción Larga</label>
            <textarea name="descripcion_larga" rows="3"
                placeholder="Descripción detallada del trabajo, incluyendo especificaciones técnicas..."
                style=""><?php echo htmlspecialchars($trabajo['descripcion_larga'] ?? ''); ?></textarea>
        </div>

        <!-- Sección: Parámetros -->
        <h3 style="border-bottom: 2px solid var(--color-primary); padding-bottom: 8px; color: var(--color-primary);">
            <i class="fas fa-cogs"></i> Parámetros
        </h3>

        <div
            style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px;">Unidad de Medida *</label>
                <select name="unidad_medida" required style="">
                    <option value="">Seleccione...</option>
                    <?php
                    $unidades = [
                        'M2' => 'M² (Metro Cuadrado)',
                        'M3' => 'M³ (Metro Cúbico)',
                        'ML' => 'ML (Metro Lineal)',
                        'U' => 'U (Unidad)'
                    ];
                    foreach ($unidades as $val => $label) {
                        $sel = ($trabajo['unidad_medida'] ?? '') == $val ? 'selected' : '';
                        echo "<option value='$val' $sel>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px;">Tiempo Límite (días)</label>
                <input type="number" name="tiempo_limite_dias" min="0"
                    value="<?php echo $trabajo['tiempo_limite_dias'] ?? ''; ?>" placeholder="Ej: 30" style="">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px;">Precio Unitario (OM 2026)</label>
                <div style="position: relative;">
                    <span class="input-prefix">$</span>
                    <input type="number" step="0.01" name="precio_unitario"
                        value="<?php echo $trabajo['precio_unitario'] ?? ''; ?>" placeholder="0.00" style="">
                </div>
            </div>
        </div>

        <!-- Estado -->
        <div class="status-box">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="estado" value="1" <?php echo ($trabajo['estado'] ?? 1) ? 'checked' : ''; ?>
                    style="width: 20px; height: 20px;">
                <span style="font-weight: 500;">Tipo de trabajo activo</span>
                <small style="color: #666;">(Los inactivos no aparecen en listados de selección)</small>
            </label>
        </div>

        <!-- Botones -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; padding-top: var(--spacing-md); border-top: 1px solid #eee;">
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 30px;">
                <i class="fas fa-save"></i> Guardar Tipo de Trabajo
            </button>
        </div>
    </form>
</div>

<style>
    /* Estilos del Formulario - Tema */
    .card {
        background: var(--bg-card);
        color: var(--text-primary);
        box-shadow: var(--shadow-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
        padding: 25px;
        border-radius: 12px;
    }

    /* Inputs y Selects */
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="number"],
    select,
    textarea {
        width: 100%;
        padding: 12px 14px !important;
        background: var(--bg-tertiary) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 8px !important;
        color: var(--text-primary) !important;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.2s;
        font-family: inherit;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: var(--color-primary) !important;
        box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.2);
        background: var(--bg-secondary) !important;
    }

    label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    small {
        color: var(--text-muted) !important;
    }

    /* Caja de Estado (Checkbox) */
    .status-box {
        background: var(--bg-tertiary);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Headers de secciones */
    h1,
    h3 {
        color: var(--text-primary);
    }

    /* Precios */
    .input-prefix {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        z-index: 2;
    }

    input[name="precio_unitario"] {
        padding-left: 25px !important;
    }

    /* MODO CLARO */
    [data-theme="light"] input,
    [data-theme="light"] select,
    [data-theme="light"] textarea {
        background: #ffffff !important;
        border: 1px solid #ddd !important;
        color: #333 !important;
    }

    [data-theme="light"] input:focus,
    [data-theme="light"] select:focus,
    [data-theme="light"] textarea:focus {
        border-color: var(--color-primary) !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    [data-theme="light"] .status-box {
        background: #f8f9fa;
        border: 1px solid #e2e8f0;
    }

    [data-theme="light"] .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #333;
    }
</style>

<?php require_once '../../includes/footer.php'; ?>