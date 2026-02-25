<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';

$orders = $pdo->query("
    SELECT po.*, p.name as provider_name,
           (SELECT SUM(quantity * price_unit) FROM po_items WHERE po_id = po.id) as calculated_total
    FROM purchase_orders po 
    JOIN providers p ON po.provider_id = p.id 
    ORDER BY po.created_at DESC
")->fetchAll();

// Status flow
$nextStatus = [
    'emitida' => 'enviada',
    'enviada' => 'confirmada',
    'confirmada' => 'entregada',
    'entregada' => null,
    'cancelada' => null
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Compra - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .data-table tr:hover {
            background: var(--bg-body);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 500;
        }

        .status-emitida {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-enviada {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-confirmada {
            background: #D1FAE5;
            color: #047857;
        }

        .status-entregada {
            background: #E0E7FF;
            color: #4338CA;
        }

        .status-cancelada {
            background: #FEE2E2;
            color: #DC2626;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .action-btns {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .btn-xs {
            padding: 0.2rem 0.4rem;
            font-size: 0.65rem;
            border-radius: var(--radius-sm);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        .alert-success {
            background: #D1FAE5;
            color: #047857;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <aside class="sidebar">
            <h2 style="margin-bottom: 2rem;">🛒 Compras</h2>
            <nav>
                <a href="../dashboard/index.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Dashboard</a>
                <a href="../requests/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Solicitudes</a>
                <a href="../providers/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Proveedores</a>
                <a href="list.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Órdenes de Compra</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤 <?= htmlspecialchars($userName) ?></p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1>Órdenes de Compra</h1>
                <a href="create.php" class="btn btn-primary">+ Nueva OC</a>
            </div>

            <?php if (isset($_GET['created'])): ?>
                <div class="alert-success">✅ Orden <strong><?= htmlspecialchars($_GET['created']) ?></strong> creada
                    exitosamente</div>
            <?php endif; ?>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert-success">✅ Estado actualizado correctamente</div>
            <?php endif; ?>

            <?php if (isset($_GET['sent'])): ?>
                <div class="alert-success">✅ Orden <strong><?= htmlspecialchars($_GET['sent']) ?></strong> marcada como
                    enviada</div>
            <?php endif; ?>

            <?php if (count($orders) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° OC</th>
                            <th>Proveedor</th>
                            <th>Total</th>
                            <th>Entrega</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['po_number']) ?></strong></td>
                                <td><?= htmlspecialchars($order['provider_name']) ?></td>
                                <td>$<?= number_format($order['calculated_total'] ?? $order['total_amount'], 2) ?></td>
                                <td><?= $order['delivery_date_committed'] ? date('d/m/Y', strtotime($order['delivery_date_committed'])) : '-' ?>
                                </td>
                                <td><span
                                        class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                <td class="action-btns">
                                    <a href="pdf.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-xs btn-info"
                                        title="Ver PDF">📄 PDF</a>

                                    <?php if ($order['status'] === 'emitida'): ?>
                                        <a href="send.php?id=<?= $order['id'] ?>" class="btn btn-xs btn-success">
                                            📧 Enviar
                                        </a>
                                    <?php elseif ($order['status'] === 'enviada'): ?>
                                        <a href="update_status.php?id=<?= $order['id'] ?>&status=confirmada"
                                            class="btn btn-xs btn-success"
                                            onclick="return confirm('¿Confirmar que el proveedor aceptó la orden?')">
                                            ✓ Confirmar
                                        </a>
                                    <?php elseif ($order['status'] === 'confirmada'): ?>
                                        <a href="update_status.php?id=<?= $order['id'] ?>&status=entregada"
                                            class="btn btn-xs btn-success"
                                            onclick="return confirm('¿Confirmar que los materiales fueron entregados?')">
                                            📦 Recibido
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($order['status'] !== 'cancelada' && $order['status'] !== 'entregada'): ?>
                                        <a href="update_status.php?id=<?= $order['id'] ?>&status=cancelada"
                                            class="btn btn-xs btn-danger" onclick="return confirm('¿Cancelar esta orden?')">
                                            ✕ Cancelar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state card">
                    <h3>No hay órdenes de compra</h3>
                    <p>Crea tu primera orden de compra.</p>
                    <a href="create.php" class="btn btn-primary" style="margin-top: 1rem;">+ Nueva OC</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>