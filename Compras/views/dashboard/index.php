<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';

// Get stats
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status IN ('enviada', 'en_revision')")->fetchColumn();
$approvedToday = $pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'aprobada' AND DATE(created_at) = CURDATE()")->fetchColumn();
$totalPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
$totalProviders = $pdo->query("SELECT COUNT(*) FROM providers")->fetchColumn();

// Recent requests
$recentRequests = $pdo->query("
    SELECT pr.*, u.full_name as requester_name 
    FROM purchase_requests pr 
    JOIN users u ON pr.user_id = u.id 
    ORDER BY pr.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }

        .stat-card h3 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .stat-card p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-table th,
        .recent-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .recent-table th {
            background: var(--bg-body);
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-enviada {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .status-aprobada {
            background: #D1FAE5;
            color: #047857;
        }

        .status-rechazada {
            background: #FEE2E2;
            color: #DC2626;
        }

        .status-en_revision {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-borrador {
            background: #F3F4F6;
            color: #6B7280;
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <aside class="sidebar">
            <h2 style="margin-bottom: 2rem;">🛒 Compras</h2>
            <nav>
                <a href="index.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Dashboard</a>
                <a href="../requests/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Solicitudes</a>
                <a href="../providers/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Proveedores</a>
                <a href="../orders/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Órdenes de
                    Compra</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤 <?= htmlspecialchars($userName) ?></p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <h1 style="margin-bottom: 1.5rem;">Dashboard de Compras</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $pendingRequests ?></h3>
                    <p>Solicitudes Pendientes</p>
                </div>
                <div class="stat-card">
                    <h3><?= $approvedToday ?></h3>
                    <p>Aprobadas Hoy</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totalPOs ?></h3>
                    <p>Órdenes de Compra</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totalProviders ?></h3>
                    <p>Proveedores Activos</p>
                </div>
            </div>

            <div class="card" style="margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1rem; font-size: 1.125rem;">Solicitudes Recientes</h2>
                <?php if (count($recentRequests) > 0): ?>
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Solicitante</th>
                                <th>Urgencia</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $req): ?>
                                <tr>
                                    <td>#<?= $req['id'] ?></td>
                                    <td><?= htmlspecialchars($req['title']) ?></td>
                                    <td><?= htmlspecialchars($req['requester_name']) ?></td>
                                    <td><?= ucfirst($req['urgency']) ?></td>
                                    <td><span
                                            class="status-badge status-<?= $req['status'] ?>"><?= ucfirst(str_replace('_', ' ', $req['status'])) ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($req['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No hay solicitudes aún.</p>
                <?php endif; ?>
            </div>

            <!-- ODC Charts & Alerts Section -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Chart -->
                <div class="card">
                    <h2 style="margin-bottom: 1rem;">Estado de Órdenes de Compra</h2>
                    <canvas id="odcChart" style="max-height: 300px;"></canvas>
                </div>

                <!-- Alerts -->
                <div class="card">
                    <h2 style="margin-bottom: 1rem;">⚠️ Alertas de Demora</h2>
                    <?php
                    // Logic for stats and alerts
                    // 1. Counts by status
                    $odcStats = $pdo->query("
                        SELECT 
                            SUM(CASE WHEN status = 'emitida' THEN 1 ELSE 0 END) as emitted,
                            SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmed,
                            SUM(CASE WHEN status = 'entregada' THEN 1 ELSE 0 END) as received
                        FROM purchase_orders
                    ")->fetch(PDO::FETCH_ASSOC);

                    // 2. Delayed Orders (Not delivered/cancelled AND date <= today)
                    $delayedOrders = $pdo->query("
                        SELECT po.*, p.name as provider_name 
                        FROM purchase_orders po
                        JOIN providers p ON po.provider_id = p.id
                        WHERE po.status NOT IN ('entregada', 'cancelada') 
                        AND po.delivery_date_committed <= CURDATE()
                        ORDER BY po.delivery_date_committed ASC
                    ")->fetchAll();

                    $countDelayed = count($delayedOrders);
                    ?>

                    <?php if ($countDelayed > 0): ?>
                        <div
                            style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.375rem; padding: 1rem; margin-bottom: 1rem;">
                            <strong style="color: #DC2626; display: block; margin-bottom: 0.5rem;">
                                <?= $countDelayed ?> Órdenes Demoradas o Para Hoy
                            </strong>
                            <ul style="list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto;">
                                <?php foreach ($delayedOrders as $order): ?>
                                    <li style="padding: 0.5rem 0; border-bottom: 1px solid #FECACA; font-size: 0.875rem;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <strong>#<?= htmlspecialchars($order['po_number']) ?></strong>
                                            <span
                                                style="color: #DC2626;"><?= date('d/m/Y', strtotime($order['delivery_date_committed'])) ?></span>
                                        </div>
                                        <div style="color: #7F1D1D;"><?= htmlspecialchars($order['provider_name']) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div
                            style="text-align: center; padding: 2rem; color: #047857; background-color: #ECFDF5; border-radius: 0.375rem;">
                            <p>✅ Todo en orden</p>
                            <span style="font-size: 0.875rem;">No hay órdenes demoradas.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chart.js Script -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('odcChart').getContext('2d');
                const odcChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Emitidas', 'Confirmadas', 'Recibidas', 'Demoradas'],
                        datasets: [{
                            label: 'Cantidad de Órdenes',
                            data: [
                                <?= $odcStats['emitted'] ?? 0 ?>,
                                <?= $odcStats['confirmed'] ?? 0 ?>,
                                <?= $odcStats['received'] ?? 0 ?>,
                                <?= $countDelayed ?>
                            ],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.6)', // Blue - Emitida
                                'rgba(16, 185, 129, 0.6)', // Green - Confirmada
                                'rgba(107, 114, 128, 0.6)', // Gray - Recibida (Entregada)
                                'rgba(239, 68, 68, 0.6)'   // Red - Demorada
                            ],
                            borderColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(107, 114, 128)',
                                'rgb(239, 68, 68)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            </script>
        </main>
    </div>
</body>

</html>