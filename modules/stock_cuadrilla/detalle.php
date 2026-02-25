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
// 5. Datos del Vehículo Asignado + Combustible Hoy
$vehiculo = null;
$litros_hoy = 0;
if (!empty($cuadrilla['id_vehiculo_asignado'])) {
    $stmt_veh = $pdo->prepare("SELECT * FROM vehiculos WHERE id_vehiculo = ?");
    $stmt_veh->execute([$cuadrilla['id_vehiculo_asignado']]);
    $vehiculo = $stmt_veh->fetch(PDO::FETCH_ASSOC);

    // Calcular consumo de hoy
    $today = date('Y-m-d');
    $sql_fuel = "
        SELECT SUM(litros) FROM (
            SELECT litros FROM combustibles_despachos WHERE DATE(fecha_hora) = ? AND id_vehiculo = ?
            UNION ALL
            SELECT litros FROM combustibles_cargas WHERE DATE(fecha_hora) = ? AND destino_tipo = 'vehiculo' AND id_vehiculo = ?
        ) as combined_fuel";
    $stmt_fuel = $pdo->prepare($sql_fuel);
    $stmt_fuel->execute([$today, $cuadrilla['id_vehiculo_asignado'], $today, $cuadrilla['id_vehiculo_asignado']]);
    $litros_hoy = $stmt_fuel->fetchColumn() ?: 0;
}

// 6. Personal de la Cuadrilla
$stmt_pers = $pdo->prepare("SELECT * FROM personal WHERE id_cuadrilla = ? AND estado_documentacion != 'Incompleto' ORDER BY rol");
$stmt_pers->execute([$id_cuadrilla]);
$personal = $stmt_pers->fetchAll(PDO::FETCH_ASSOC);

// 7. Herramientas Asignadas
$stmt_tools = $pdo->prepare("SELECT * FROM herramientas WHERE id_cuadrilla_asignada = ? AND estado = 'Asignada'");
$stmt_tools->execute([$id_cuadrilla]);
$herramientas = $stmt_tools->fetchAll(PDO::FETCH_ASSOC);

// 8. Conteo de ODTs Pendientes (Para el botón)
// Filtramos por estados activos
$sql_odts_pending = "
    SELECT COUNT(DISTINCT o.id_odt) 
    FROM odt_maestro o
    JOIN programacion_semanal ps ON o.id_odt = ps.id_odt
    WHERE ps.id_cuadrilla = ? 
    AND o.estado_gestion IN ('Programado', 'Ejecución', 'Retrabajo', 'Postergado')";
$stmt_odt = $pdo->prepare($sql_odts_pending);
$stmt_odt->execute([$id_cuadrilla]);
$odts_pendientes = $stmt_odt->fetchColumn();
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

    <!-- NEW: Resource Cards (Vehicle, Personnel, Tools) -->
    <div class="resource-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 25px;">

        <!-- 1. Vehículo -->
        <div class="card" style="padding: 0;">
            <div class="panel-header"
                style="background: rgba(33, 150, 243, 0.1); border-bottom: 1px solid rgba(33, 150, 243, 0.2);">
                <h3 style="color: #1976d2;"><i class="fas fa-truck"></i> Vehículo Asignado</h3>
            </div>
            <div style="padding: 15px;">
                <?php if ($vehiculo): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <div style="font-size: 1.2em; font-weight: bold; color: var(--text-primary);">
                                <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                            </div>
                            <div style="color: var(--text-secondary); font-family: monospace; font-size: 1.1em;">
                                <?php echo htmlspecialchars($vehiculo['patente']); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge-fuel <?php echo $litros_hoy > 0 ? 'success' : 'danger'; ?>"
                                style="font-size: 0.9em;">
                                <i class="fas fa-gas-pump"></i> <?php echo number_format($litros_hoy, 1); ?> L
                            </span>
                            <div style="font-size: 0.75em; color: #888; margin-top: 4px;">Cargado Hoy</div>
                        </div>
                    </div>

                    <div class="list-group-simple">
                        <div class="list-item">
                            <span><i class="fas fa-tachometer-alt"></i> KM Actual</span>
                            <strong><?php echo number_format($vehiculo['km_actual'] ?? 0, 0, ',', '.'); ?> km</strong>
                        </div>
                        <div class="list-item">
                            <span><i class="fas fa-wrench"></i> Próximo Service</span>
                            <strong><?php echo number_format($vehiculo['proximo_service_km'] ?? 0, 0, ',', '.'); ?>
                                km</strong>
                        </div>
                        <div class="list-item">
                            <span><i class="fas fa-info-circle"></i> Estado</span>
                            <strong><?php echo $vehiculo['estado']; ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state-mini">
                        <i class="fas fa-ban"></i>
                        <p>Sin vehículo asignado</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Personal -->
        <div class="card" style="padding: 0;">
            <div class="panel-header"
                style="background: rgba(76, 175, 80, 0.1); border-bottom: 1px solid rgba(76, 175, 80, 0.2);">
                <h3 style="color: #388e3c;"><i class="fas fa-users"></i> Personal (<?php echo count($personal); ?>)</h3>
            </div>
            <div class="custom-scrollbar" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($personal)): ?>
                    <div class="empty-state-mini">
                        <i class="fas fa-user-slash"></i>
                        <p>Sin personal asignado</p>
                    </div>
                <?php else: ?>
                    <ul class="simple-list">
                        <?php foreach ($personal as $p): ?>
                            <li>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="avatar-circle"><?php echo strtoupper(substr($p['nombre_apellido'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($p['nombre_apellido']); ?>
                                        </div>
                                        <small style="color: #666;"><?php echo $p['rol']; ?></small>
                                    </div>
                                </div>
                                <?php if ($p['rol'] == 'Chofer'): ?>
                                    <i class="fas fa-steering-wheel" title="Chofer" style="color: var(--text-muted);"></i>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Herramientas -->
        <div class="card" style="padding: 0;">
            <div class="panel-header"
                style="background: rgba(255, 152, 0, 0.1); border-bottom: 1px solid rgba(255, 152, 0, 0.2);">
                <h3 style="color: #f57c00;"><i class="fas fa-tools"></i> Herramientas
                    (<?php echo count($herramientas); ?>)</h3>
            </div>
            <div class="custom-scrollbar" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($herramientas)): ?>
                    <div class="empty-state-mini">
                        <i class="fas fa-toolbox"></i>
                        <p>Sin herramientas asignadas</p>
                    </div>
                <?php else: ?>
                    <ul class="simple-list">
                        <?php foreach ($herramientas as $h): ?>
                            <li>
                                <div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($h['nombre']); ?></div>
                                    <small style="color: #888;">S/N: <?php echo htmlspecialchars($h['numero_serie']); ?></small>
                                </div>
                                <span class="badge-status <?php echo $h['estado'] == 'Asignada' ? 'active' : 'inactive'; ?>"
                                    style="font-size: 0.7em;">
                                    <?php echo $h['estado']; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ODTs Pendientes CTA -->
    <div style="margin-bottom: 25px;">
        <a href="../odt/index.php?cuadrilla=<?php echo urlencode($cuadrilla['nombre_cuadrilla']); ?>&estado=&back_to_squad=<?php echo $id_cuadrilla; ?>"
            class="btn btn-primary"
            style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; font-size: 1.1em; background: linear-gradient(145deg, #1565c0, #0d47a1);">
            <span>
                <i class="fas fa-clipboard-list"></i> Ver Gestión de ODTs / Trabajos Pendientes
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 15px; font-size: 0.9em;">
                <?php echo $odts_pendientes; ?> Pendientes
            </span>
        </a>
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

    /* New Styles for Resource Cards */
    .simple-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .simple-list li {
        padding: 12px 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .simple-list li:last-child {
        border-bottom: none;
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9em;
    }

    .list-group-simple .list-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed rgba(0, 0, 0, 0.1);
        font-size: 0.9em;
    }

    .list-group-simple .list-item:last-child {
        border-bottom: none;
    }

    .list-group-simple .list-item span {
        color: var(--text-secondary);
    }

    .badge-fuel {
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
        color: white;
    }

    .badge-fuel.success {
        background: #4caf50;
    }

    .badge-fuel.danger {
        background: #f44336;
    }

    [data-theme="dark"] .simple-list li {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }

    [data-theme="dark"] .avatar-circle {
        background: #333;
        color: #ddd;
    }
</style>

<?php require_once '../../includes/footer.php'; ?>