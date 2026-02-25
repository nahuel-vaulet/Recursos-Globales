<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

if (!tienePermiso('compras')) {
    header('Location: ../../index.php?msg=forbidden');
    exit;
}

// 1. Estadísticas Generales
// Solicitudes Pendientes (Enviada o En Revisión)
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM compras_solicitudes WHERE estado IN ('enviada', 'en_revision')")->fetchColumn();

// Aprobadas Hoy
$approvedToday = $pdo->query("SELECT COUNT(*) FROM compras_solicitudes WHERE estado = 'aprobada' AND DATE(fecha_creacion) = CURDATE()")->fetchColumn();

// Total Órdenes de Compra
$totalPOs = $pdo->query("SELECT COUNT(*) FROM compras_ordenes")->fetchColumn();

// Proveedores Activos
$totalProviders = $pdo->query("SELECT COUNT(*) FROM proveedores")->fetchColumn();

// 2. Solicitudes Recientes (Últimas 5)
$recentRequests = $pdo->query("
    SELECT cs.*, u.nombre as requester_name 
    FROM compras_solicitudes cs 
    JOIN usuarios u ON cs.id_usuario = u.id_usuario 
    ORDER BY cs.fecha_creacion DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Estadísticas para Gráfico de Órdenes
$odcStats = $pdo->query("
    SELECT 
        SUM(CASE WHEN estado = 'emitida' THEN 1 ELSE 0 END) as emitted,
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN estado = 'entregada' THEN 1 ELSE 0 END) as received
    FROM compras_ordenes
")->fetch(PDO::FETCH_ASSOC);

// 4. Órdenes Demoradas (No entregadas/canceladas y fecha pactada <= hoy)
$delayedOrders = $pdo->query("
    SELECT po.*, p.razon_social as provider_name 
    FROM compras_ordenes po
    JOIN proveedores p ON po.id_proveedor = p.id_proveedor
    WHERE po.estado NOT IN ('entregada', 'cancelada') 
    AND po.fecha_entrega_pactada <= CURDATE()
    ORDER BY po.fecha_entrega_pactada ASC
")->fetchAll(PDO::FETCH_ASSOC);

$countDelayed = count($delayedOrders);
?>

<div class="container-fluid" style="padding: 0 20px;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h1 style="margin: 0;"><i class="fas fa-shopping-cart"></i> Dashboard de Compras</h1>
            <p style="margin: 5px 0 0; color: #666;">Gestión de solicitudes y órdenes de compra</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="solicitudes/form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Solicitud
            </a>
            <a href="ordenes/form.php" class="btn btn-outline-primary">
                <i class="fas fa-file-contract"></i> Nueva Orden
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Pendientes -->
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="font-size: 2.5rem; color: var(--color-warning); margin: 0;"><?= $pendingRequests ?></h3>
            <p style="color: #666; margin: 5px 0 0;">Solicitudes Pendientes</p>
        </div>

        <!-- Aprobadas Hoy -->
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="font-size: 2.5rem; color: var(--color-success); margin: 0;"><?= $approvedToday ?></h3>
            <p style="color: #666; margin: 5px 0 0;">Aprobadas Hoy</p>
        </div>

        <!-- Total OCs -->
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="font-size: 2.5rem; color: var(--color-primary); margin: 0;"><?= $totalPOs ?></h3>
            <p style="color: #666; margin: 5px 0 0;">Órdenes Emitidas</p>
        </div>

        <!-- Proveedores -->
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="font-size: 2.5rem; color: #666; margin: 0;"><?= $totalProviders ?></h3>
            <p style="color: #666; margin: 5px 0 0;">Proveedores Activos</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Recent Requests -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Solicitudes Recientes</h3>
                <a href="solicitudes/index.php" style="font-size: 0.9em;">Ver todas</a>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-body); text-align: left; font-size: 0.85em; color: #666;">
                            <th style="padding: 10px;">ID</th>
                            <th style="padding: 10px;">Título</th>
                            <th style="padding: 10px;">Solicitante</th>
                            <th style="padding: 10px;">Urgencia</th>
                            <th style="padding: 10px;">Estado</th>
                            <th style="padding: 10px;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentRequests) > 0): ?>
                            <?php foreach ($recentRequests as $req): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;">#<?= $req['id'] ?></td>
                                    <td style="padding: 10px; font-weight: 500;"><?= htmlspecialchars($req['titulo']) ?></td>
                                    <td style="padding: 10px; font-size: 0.9em;"><?= htmlspecialchars($req['requester_name']) ?></td>
                                    <td style="padding: 10px;">
                                        <?php
                                        $urgenciaClass = 'badge-secondary';
                                        if ($req['urgencia'] == 'critica') $urgenciaClass = 'badge-danger';
                                        if ($req['urgencia'] == 'alta') $urgenciaClass = 'badge-warning';
                                        if ($req['urgencia'] == 'media') $urgenciaClass = 'badge-info';
                                        if ($req['urgencia'] == 'baja') $urgenciaClass = 'badge-success';
                                        ?>
                                        <span class="badge <?= $urgenciaClass ?>"><?= ucfirst($req['urgencia']) ?></span>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php
                                        $estadoClass = 'badge-secondary';
                                        if ($req['estado'] == 'enviada') $estadoClass = 'badge-info';
                                        if ($req['estado'] == 'aprobada') $estadoClass = 'badge-success';
                                        if ($req['estado'] == 'rechazada') $estadoClass = 'badge-danger';
                                        if ($req['estado'] == 'en_revision') $estadoClass = 'badge-warning';
                                        ?>
                                        <span class="badge <?= $estadoClass ?>"><?= ucfirst(str_replace('_', ' ', $req['estado'])) ?></span>
                                    </td>
                                    <td style="padding: 10px; font-size: 0.85em; color: #666;">
                                        <?= date('d/m/Y', strtotime($req['fecha_creacion'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="padding: 20px; text-align: center; color: #999;">No hay solicitudes recientes</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alerts & Chart -->
        <div>
            <!-- Alerts -->
            <?php if ($countDelayed > 0): ?>
                <div class="card" style="background: #FEF2F2; border: 1px solid #FECACA; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #DC2626;"><i class="fas fa-exclamation-triangle"></i> Órdenes Demoradas (<?= $countDelayed ?>)</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; max-height: 150px; overflow-y: auto;">
                        <?php foreach ($delayedOrders as $order): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #FCA5A5; font-size: 0.9em;">
                                <div style="display: flex; justify-content: space-between;">
                                    <strong>#<?= htmlspecialchars($order['nro_orden']) ?></strong>
                                    <span style="color: #DC2626;"><?= date('d/m/Y', strtotime($order['fecha_entrega_pactada'])) ?></span>
                                </div>
                                <div style="color: #7F1D1D; font-size: 0.85em;"><?= htmlspecialchars($order['provider_name']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="card" style="background: #ECFDF5; border: 1px solid #6EE7B7; margin-bottom: 20px; text-align: center; padding: 20px;">
                    <h4 style="color: #047857; margin: 0;"><i class="fas fa-check-circle"></i> Todo al día</h4>
                    <p style="margin: 5px 0 0; font-size: 0.9em; color: #065F46;">No hay órdenes demoradas</p>
                </div>
            <?php endif; ?>

            <!-- Chart -->
            <div class="card">
                <h4 style="margin: 0 0 15px 0;">Estado de Órdenes</h4>
                <canvas id="odcChart" style="height: 200px; width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('odcChart').getContext('2d');
    const odcChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Emitidas', 'Confirmadas', 'Recibidas', 'Demoradas'],
            datasets: [{
                data: [
                    <?= $odcStats['emitted'] ?? 0 ?>,
                    <?= $odcStats['confirmed'] ?? 0 ?>,
                    <?= $odcStats['received'] ?? 0 ?>,
                    <?= $countDelayed ?>
                ],
                backgroundColor: [
                    '#3B82F6', // Blue
                    '#10B981', // Green
                    '#6B7280', // Gray
                    '#EF4444'  // Red
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                }
            }
        }
    });

    // Custom CSS for Badges if not present
    const style = document.createElement('style');
    style.innerHTML = `
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75em; font-weight: 600; }
        .badge-secondary { background: #E5E7EB; color: #374151; }
        .badge-info { background: #DBEAFE; color: #1E40AF; }
        .badge-success { background: #D1FAE5; color: #065F46; }
        .badge-warning { background: #FEF3C7; color: #92400E; }
        .badge-danger { background: #FEE2E2; color: #B91C1C; }
    `;
    document.head.appendChild(style);
</script>

<?php require_once '../../includes/footer.php'; ?>
