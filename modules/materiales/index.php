<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch materials
$query = "SELECT * FROM maestro_materiales ORDER BY nombre ASC";
$stmt = $pdo->query($query);
$materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="padding: 0 20px;">
    <div class="card">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h1><i class="fas fa-box"></i> Maestro de Materiales</h1>
            <a href="form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Material</a>
        </div>

        <!-- Feedback Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-md); border-radius: var(--border-radius-md); 
                background: <?php echo $_GET['msg'] == 'saved' ? '#d4edda' : '#f8d7da'; ?>; 
                color: <?php echo $_GET['msg'] == 'saved' ? '#155724' : '#721c24'; ?>;">
                <?php
                if ($_GET['msg'] == 'saved')
                    echo "Material guardado correctamente.";
                if ($_GET['msg'] == 'deleted')
                    echo "Material eliminado.";
                if ($_GET['msg'] == 'error')
                    echo "Ha ocurrido un error.";
                ?>
            </div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--color-primary-dark); color: white;">
                        <th style="padding: var(--spacing-md); text-align: left;">Nombre</th>
                        <th style="padding: var(--spacing-md); text-align: left;">Descripción</th>
                        <th style="padding: var(--spacing-md); text-align: center;">Unidad</th>
                        <th style="padding: var(--spacing-md); text-align: right;">Costo Primario</th>
                        <th style="padding: var(--spacing-md); text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materiales as $mat): ?>
                        <tr style="border-bottom: 1px solid var(--color-neutral-light);">
                            <td style="padding: var(--spacing-md);">
                                <?php echo htmlspecialchars($mat['nombre']); ?>
                            </td>
                            <td style="padding: var(--spacing-md);">
                                <?php echo htmlspecialchars($mat['descripcion']); ?>
                            </td>
                            <td style="padding: var(--spacing-md); text-align: center;">
                                <?php echo htmlspecialchars($mat['unidad_medida']); ?>
                            </td>
                            <td style="padding: var(--spacing-md); text-align: right;">$
                                <?php echo number_format($mat['costo_primario'], 2); ?>
                            </td>
                            <td style="padding: var(--spacing-md); text-align: center;">
                                <a href="form.php?id=<?php echo $mat['id_material']; ?>" class="btn btn-outline"
                                    style="padding: 4px 8px; font-size: 0.9em;"><i class="fas fa-edit"></i></a>
                                <a href="delete.php?id=<?php echo $mat['id_material']; ?>"
                                    onclick="return confirm('¿Está seguro de eliminar este material?');"
                                    class="btn btn-outline"
                                    style="padding: 4px 8px; font-size: 0.9em; border-color: var(--color-danger); color: var(--color-danger);"><i
                                        class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($materiales)): ?>
                        <tr>
                            <td colspan="5"
                                style="padding: var(--spacing-lg); text-align: center; color: var(--color-neutral);">No hay
                                materiales registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>