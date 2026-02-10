<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Get Cuadrilla ID
$id_cuadrilla = $_GET['id'] ?? null;

if (!$id_cuadrilla) {
    header('Location: /APP-Prueba/modules/stock/index.php');
    exit;
}

// 1. Cuadrilla Basic Info
$stmt = $pdo->prepare("SELECT * FROM cuadrillas WHERE id_cuadrilla = ?");
$stmt->execute([$id_cuadrilla]);
$cuadrilla = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuadrilla) {
    echo "<div class='card'><p>Cuadrilla no encontrada.</p></div>";
    require_once '../../includes/footer.php';
    exit;
}

// 2. Stock Actual de la Cuadrilla
$sql_stock = "SELECT m.id_material, m.nombre, m.codigo, m.unidad_medida, sc.cantidad
              FROM stock_cuadrilla sc
              JOIN maestro_materiales m ON sc.id_material = m.id_material
              WHERE sc.id_cuadrilla = ? AND sc.cantidad > 0
              ORDER BY m.nombre";
$stmt_stock = $pdo->prepare($sql_stock);
$stmt_stock->execute([$id_cuadrilla]);
$stock_items = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

// 3. Movimientos Recientes (últimos 30 días)
$sql_movs = "SELECT mov.*, m.nombre as material, m.codigo
             FROM movimientos mov
             JOIN maestro_materiales m ON mov.id_material = m.id_material
             WHERE mov.id_cuadrilla = ?
             ORDER BY mov.fecha_hora DESC
             LIMIT 50";
$stmt_movs = $pdo->prepare($sql_movs);
$stmt_movs->execute([$id_cuadrilla]);
$movimientos = $stmt_movs->fetchAll(PDO::FETCH_ASSOC);

// 4. Métricas
$total_materiales = count($stock_items);
$total_unidades = array_sum(array_column($stock_items, 'cantidad'));
$total_entregas = 0;
$total_consumos = 0;
foreach ($movimientos as $mov) {
    if ($mov['tipo_movimiento'] == 'Entrega_Oficina_Cuadrilla')
        $total_entregas++;
    if ($mov['tipo_movimiento'] == 'Consumo_Cuadrilla_Obra')
        $total_consumos++;
}
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Back Button & Header -->
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
        <a href="/APP-Prueba/modules/stock/index.php" class="btn btn-outline" style="padding: 8px 12px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-hard-hat" style="color: var(--color-primary);"></i>
                <?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla']); ?>
                <span
                    class="badge-status <?php echo $cuadrilla['estado_operativo'] == 'Activa' ? 'active' : 'inactive'; ?>">
                    <?php echo $cuadrilla['estado_operativo']; ?>
                </span>
            </h2>
            <?php if (!empty($cuadrilla['encargado'] ?? null)): ?>
                <p style="margin: 5px 0 0; color: #666;">
                    <i class="fas fa-user-tie"></i> Encargado: <strong>
                        <?php echo htmlspecialchars($cuadrilla['encargado']); ?>
                    </strong>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon" style="background: #e3f2fd;"><i class="fas fa-boxes" style="color: #1976d2;"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value">
                    <?php echo $total_materiales; ?>
                </span>
                <span class="metric-label">Tipos de Material</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #e8f5e9;"><i class="fas fa-cubes" style="color: #388e3c;"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value">
                    <?php echo number_format($total_unidades, 1); ?>
                </span>
                <span class="metric-label">Unidades Totales</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #fff3e0;"><i class="fas fa-truck" style="color: #f57c00;"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value">
                    <?php echo $total_entregas; ?>
                </span>
                <span class="metric-label">Entregas Recibidas</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: #fce4ec;"><i class="fas fa-tools" style="color: #c2185b;"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value">
                    <?php echo $total_consumos; ?>
                </span>
                <span class="metric-label">Consumos en Obra</span>
            </div>
        </div>
    </div>

    <!-- Content Grid: Stock + Movements -->
    <div class="detail-grid">

        <!-- Stock Actual -->
        <div class="card" style="border-top: 4px solid var(--color-primary);">
            <div class="panel-header">
                <h3><i class="fas fa-warehouse"></i> Stock Actual</h3>
            </div>

            <?php if (empty($stock_items)): ?>
                <div class="empty-state-mini">
                    <i class="fas fa-check-circle"></i>
                    <p>Sin materiales en posesión</p>
                </div>
            <?php else: ?>
                <div class="table-container custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th class="text-right">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="mat-name">
                                            <?php echo $item['nombre']; ?>
                                        </div>
                                        <div class="mat-code">
                                            <?php echo $item['codigo']; ?>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <strong>
                                            <?php echo number_format($item['cantidad'], 2); ?>
                                        </strong>
                                        <small>
                                            <?php echo $item['unidad_medida']; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Movimientos Recientes -->
        <div class="card" style="border-top: 4px solid #6c757d;">
            <div class="panel-header">
                <h3><i class="fas fa-history"></i> Movimientos Recientes</h3>
            </div>

            <?php if (empty($movimientos)): ?>
                <div class="empty-state-mini">
                    <i class="fas fa-inbox"></i>
                    <p>Sin movimientos registrados</p>
                </div>
            <?php else: ?>
                <div class="table-container custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Material</th>
                                <th class="text-right">Cant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $mov):
                                $icon = '';
                                $color = '#666';
                                $typeName = '';
                                if ($mov['tipo_movimiento'] == 'Entrega_Oficina_Cuadrilla') {
                                    $icon = '📥';
                                    $color = '#28a745';
                                    $typeName = 'Recibido';
                                } elseif ($mov['tipo_movimiento'] == 'Consumo_Cuadrilla_Obra') {
                                    $icon = '🔧';
                                    $color = '#dc3545';
                                    $typeName = 'Consumo';
                                } else {
                                    $icon = '🔄';
                                    $typeName = str_replace('_', ' ', $mov['tipo_movimiento']);
                                }
                                ?>
                                <tr>
                                    <td style="font-size: 0.85em; color: #666;">
                                        <?php echo date('d/m/y H:i', strtotime($mov['fecha_hora'])); ?>
                                    </td>
                                    <td style="font-weight: 500; color: <?php echo $color; ?>;">
                                        <?php echo $icon . ' ' . $typeName; ?>
                                    </td>
                                    <td>
                                        <?php echo $mov['material']; ?>
                                    </td>
                                    <td class="text-right" style="font-weight: bold;">
                                        <?php echo $mov['cantidad']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
    .badge-status {
        font-size: 0.7em;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 500;
    }

    .badge-status.active {
        background: #d4edda;
        color: #155724;
    }

    .badge-status.inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .metric-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .metric-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3em;
    }

    .metric-data {
        display: flex;
        flex-direction: column;
    }

    .metric-value {
        font-size: 1.5em;
        font-weight: 700;
        color: #333;
    }

    .metric-label {
        font-size: 0.8em;
        color: #888;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 900px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }

    .panel-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    .panel-header h3 {
        margin: 0;
        font-size: 1.1em;
        color: #333;
    }

    .empty-state-mini {
        padding: 40px;
        text-align: center;
        color: #aaa;
    }

    .empty-state-mini i {
        font-size: 2em;
        margin-bottom: 10px;
    }

    .table-sm td,
    .table-sm th {
        padding: 8px 10px;
        font-size: 0.9em;
    }

    .mat-name {
        font-weight: 500;
    }

    .mat-code {
        font-size: 0.75em;
        color: #888;
    }

    .text-right {
        text-align: right;
    }
</style>

<?php require_once '../../includes/footer.php'; ?>