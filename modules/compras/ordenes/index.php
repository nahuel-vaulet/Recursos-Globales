<?php
require_once '../../../config/database.php';
require_once '../../../includes/header.php';

if (!tienePermiso('compras')) {
    header('Location: ../../../index.php?msg=forbidden');
    exit;
}

// Consultar Órdenes
$orders = $pdo->query("
    SELECT po.*, p.razon_social as provider_name
    FROM compras_ordenes po 
    JOIN proveedores p ON po.id_proveedor = p.id_proveedor 
    ORDER BY po.fecha_creacion DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Mensajes
$msg = $_GET['msg'] ?? '';
$errorMsg = $_GET['error'] ?? '';
?>

<div class="container-fluid" style="padding: 0 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin: 0;"><i class="fas fa-file-contract"></i> Órdenes de Compra</h1>
        <a href="form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Orden</a>
    </div>

    <?php if ($msg == 'created'): ?>
        <div class="alert alert-success" style="background-color: #D1FAE5; color: #065F46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> Orden creada exitosamente.
        </div>
    <?php elseif ($msg == 'updated'): ?>
        <div class="alert alert-success" style="background-color: #D1FAE5; color: #065F46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> Estado actualizado.
        </div>
    <?php elseif ($msg == 'cancelled'): ?>
        <div class="alert alert-warning" style="background-color: #FEF3C7; color: #92400E; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> Orden cancelada.
        </div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger" style="background-color: #FEE2E2; color: #B91C1C; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-times-circle"></i> Error: <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--color-primary-dark); color: white; text-align: left;">
                        <th style="padding: 12px;">N° OC</th>
                        <th style="padding: 12px;">Proveedor</th>
                        <th style="padding: 12px;">Total</th>
                        <th style="padding: 12px;">Entrega Pactada</th>
                        <th style="padding: 12px;">Estado</th>
                        <th style="padding: 12px;">Fecha Emisión</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;"><strong><?= htmlspecialchars($order['nro_orden']) ?></strong></td>
                                <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($order['provider_name']) ?></td>
                                <td style="padding: 12px;">$<?= number_format($order['monto_total'], 2) ?></td>
                                <td style="padding: 12px;">
                                    <?= $order['fecha_entrega_pactada'] ? date('d/m/Y', strtotime($order['fecha_entrega_pactada'])) : '-' ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php
                                    $estadoClass = 'badge-secondary';
                                    if ($order['estado'] == 'emitida') $estadoClass = 'badge-info';
                                    if ($order['estado'] == 'enviada') $estadoClass = 'badge-warning';
                                    if ($order['estado'] == 'confirmada') $estadoClass = 'badge-success';
                                    if ($order['estado'] == 'entregada') $estadoClass = 'badge-light-blue';
                                    if ($order['estado'] == 'cancelada') $estadoClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?= $estadoClass ?>" style="padding: 4px 8px; border-radius: 12px; font-size: 0.75em; font-weight: 600; color: #fff; opacity: 0.9;"><?= ucfirst($order['estado']) ?></span>
                                </td>
                                <td style="padding: 12px; color: #666;">
                                    <?= date('d/m/Y', strtotime($order['fecha_creacion'])) ?>
                                </td>
                                <td style="padding: 12px;">
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="print.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Imprimir PDF"
                                            style="padding: 5px 8px; border: 1px solid var(--color-info); border-radius: 4px; text-decoration: none; color: var(--color-info); font-size: 0.8em;">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        <?php if ($order['estado'] === 'emitida'): ?>
                                            <a href="action.php?action=update_status&id=<?= $order['id'] ?>&status=enviada" class="btn btn-sm btn-outline-primary" title="Marcar como Enviada"
                                                style="padding: 5px 8px; border: 1px solid var(--color-primary); border-radius: 4px; text-decoration: none; color: var(--color-primary); font-size: 0.8em;">
                                                <i class="fas fa-paper-plane"></i>
                                            </a>
                                        <?php elseif ($order['estado'] === 'enviada'): ?>
                                            <a href="action.php?action=update_status&id=<?= $order['id'] ?>&status=confirmada" class="btn btn-sm btn-outline-success" title="Confirmar"
                                                onclick="return confirm('¿El proveedor confirmó la orden?')"
                                                style="padding: 5px 8px; border: 1px solid var(--color-success); border-radius: 4px; text-decoration: none; color: var(--color-success); font-size: 0.8em;">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php elseif ($order['estado'] === 'confirmada'): ?>
                                            <a href="action.php?action=update_status&id=<?= $order['id'] ?>&status=entregada" class="btn btn-sm btn-outline-dark" title="Marcar como Recibida"
                                                onclick="return confirm('¿Marcar como entregada?')"
                                                style="padding: 5px 8px; border: 1px solid #333; border-radius: 4px; text-decoration: none; color: #333; font-size: 0.8em;">
                                                <i class="fas fa-box-open"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!in_array($order['estado'], ['entregada', 'cancelada'])): ?>
                                            <a href="action.php?action=cancel&id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger" title="Cancelar"
                                                onclick="return confirm('¿Estás seguro de cancelar esta orden?')"
                                                style="padding: 5px 8px; border: 1px solid var(--color-danger); border-radius: 4px; text-decoration: none; color: var(--color-danger); font-size: 0.8em;">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="padding: 30px; text-align: center; color: #999;">No hay órdenes de compra registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.badge-light-blue { background-color: #60A5FA !important; }
</style>

<?php require_once '../../../includes/footer.php'; ?>
