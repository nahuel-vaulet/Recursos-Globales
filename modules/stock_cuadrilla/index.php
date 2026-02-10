<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Fetch Stock per Squad
$sql = "SELECT c.nombre_cuadrilla, m.nombre as material, m.unidad_medida, sc.cantidad 
        FROM stock_cuadrilla sc
        JOIN cuadrillas c ON sc.id_cuadrilla = c.id_cuadrilla
        JOIN maestro_materiales m ON sc.id_material = m.id_material
        ORDER BY c.nombre_cuadrilla, m.nombre";
$stmt = $pdo->query($sql);
$stocks = $stmt->fetchAll(PDO::FETCH_GROUP); // Group by Cuadrilla Name naturally if first column? 
// Actually FETCH_GROUP groups by first column. So Key = Nombre Cuadrilla, Value = Array of rows.

?>

<div class="card">
    <h1><i class="fas fa-truck-pickup"></i> Stock en Cuadrillas</h1>
    <p style="color: #666; margin-bottom: 20px;">Materiales actualmente en poder de los equipos móviles (No consumidos).
    </p>

    <?php if (empty($stocks)): ?>
        <p>No hay materiales asignados a cuadrillas actualmente.</p>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach ($stocks as $cuadrilla => $items): ?>
            <div
                style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="background: var(--color-primary); color: white; padding: 10px 15px; font-weight: bold;">
                    <i class="fas fa-hard-hat"></i>
                    <?php echo htmlspecialchars($cuadrilla); ?>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;">
                                <?php echo htmlspecialchars($item['material']); ?>
                            </td>
                            <td style="padding: 10px; text-align: right; font-weight: 500;">
                                <?php echo number_format($item['cantidad'], 2); ?> <span style="font-size: 0.8em; color: #888;">
                                    <?php echo $item['unidad_medida']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>