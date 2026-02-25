<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';

$providers = $pdo->query("SELECT * FROM providers ORDER BY name")->fetchAll();

// Payment terms labels
$paymentLabels = [
    'contado' => 'Contado',
    '30_dias' => '30 días',
    '60_dias' => '60 días',
    '90_dias' => '90 días',
    'anticipado' => 'Pago anticipado',
    'contra_entrega' => 'Contra entrega'
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .providers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .provider-card {
            background: white;
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            transition: box-shadow 0.2s;
        }

        .provider-card:hover {
            box-shadow: var(--shadow-md);
        }

        .provider-card h3 {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .provider-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .provider-meta p {
            margin-bottom: 0.25rem;
        }

        .provider-section {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed var(--border-color);
        }

        .contract-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .contract-marco {
            background: #D1FAE5;
            color: #047857;
        }

        .contract-spot {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .contract-exclusivo {
            background: #E0E7FF;
            color: #4338CA;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .alert-success {
            background: #D1FAE5;
            color: #047857;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .info-item {
            background: var(--bg-body);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
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
                <a href="list.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Proveedores</a>
                <a href="../orders/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Órdenes de
                    Compra</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤 <?= htmlspecialchars($userName) ?></p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1>Proveedores</h1>
                <a href="create.php" class="btn btn-primary">+ Nuevo Proveedor</a>
            </div>

            <?php if (isset($_GET['created'])): ?>
                <div class="alert-success">✅ Proveedor creado exitosamente</div>
            <?php endif; ?>

            <?php if (count($providers) > 0): ?>
                <div class="providers-grid">
                    <?php foreach ($providers as $prov): ?>
                        <div class="provider-card">
                            <h3>
                                <?= htmlspecialchars($prov['name']) ?>
                                <span class="contract-badge contract-<?= $prov['contract_type'] ?? 'spot' ?>">
                                    <?= ucfirst($prov['contract_type'] ?? 'spot') ?>
                                </span>
                            </h3>
                            <div class="provider-meta">
                                <?php if (!empty($prov['tax_id'])): ?>
                                    <p>🏢 <?= htmlspecialchars($prov['tax_id']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($prov['contact_name'])): ?>
                                    <p>👤 <?= htmlspecialchars($prov['contact_name']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($prov['contact_email'])): ?>
                                    <p>✉️ <?= htmlspecialchars($prov['contact_email']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($prov['phone'])): ?>
                                    <p>📞 <?= htmlspecialchars($prov['phone']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($prov['address'])): ?>
                                    <p>📍 <?= htmlspecialchars($prov['address']) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($prov['payment_terms']) || !empty($prov['payment_limit'])): ?>
                                <div class="provider-section">
                                    <div class="info-row">
                                        <?php if (!empty($prov['payment_terms'])): ?>
                                            <span class="info-item">💳
                                                <?= $paymentLabels[$prov['payment_terms']] ?? $prov['payment_terms'] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($prov['payment_limit']) && $prov['payment_limit'] > 0): ?>
                                            <span class="info-item">💰 Límite: $<?= number_format($prov['payment_limit'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($prov['notes'])): ?>
                                <div class="provider-section">
                                    <p style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                                        📝
                                        <?= htmlspecialchars(substr($prov['notes'], 0, 100)) ?>            <?= strlen($prov['notes']) > 100 ? '...' : '' ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state card">
                    <h3>No hay proveedores registrados</h3>
                    <p>Agrega tu primer proveedor para empezar.</p>
                    <a href="create.php" class="btn btn-primary" style="margin-top: 1rem;">+ Nuevo Proveedor</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>