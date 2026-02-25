<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['user_role'] ?? 'solicitante';

// Get user's requests
$stmt = $pdo->prepare("
    SELECT pr.*, 
           (SELECT COUNT(*) FROM request_items ri WHERE ri.request_id = pr.id) as item_count
    FROM purchase_requests pr 
    WHERE pr.user_id = ? 
    ORDER BY pr.created_at DESC
");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .requests-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .request-card {
            background: white;
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .request-card:hover {
            box-shadow: var(--shadow-md);
        }

        .request-info h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .request-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .urgency-badge {
            display: inline-block;
            padding: 0.125rem 0.375rem;
            border-radius: var(--radius-sm);
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .urgency-baja {
            background: #D1FAE5;
            color: #047857;
        }

        .urgency-media {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .urgency-alta {
            background: #FEF3C7;
            color: #D97706;
        }

        .urgency-critica {
            background: #FEE2E2;
            color: #DC2626;
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

        .status-convertida_odc {
            background: #E0E7FF;
            color: #4338CA;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <aside class="sidebar">
            <h2 style="margin-bottom: 2rem;">🛒 Compras</h2>
            <nav>
                <a href="my_requests.php" class="btn"
                    style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Mis Solicitudes</a>
                <a href="create.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Nueva Solicitud</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤
                    <?= htmlspecialchars($userName) ?>
                </p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Mis Solicitudes de Compra</h1>
                <a href="create.php" class="btn btn-primary">+ Nueva Solicitud</a>
            </div>

            <?php if (count($requests) > 0): ?>
                <div class="requests-list">
                    <?php foreach ($requests as $req): ?>
                        <div class="request-card">
                            <div class="request-info">
                                <h3>
                                    <?= htmlspecialchars($req['title']) ?>
                                    <span class="urgency-badge urgency-<?= $req['urgency'] ?>">
                                        <?= ucfirst($req['urgency']) ?>
                                    </span>
                                </h3>
                                <p class="request-meta">
                                    #
                                    <?= $req['id'] ?> ·
                                    <?= $req['item_count'] ?> ítem(s) ·
                                    <?= $req['location'] ? htmlspecialchars($req['location']) : 'Sin ubicación' ?> ·
                                    <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                </p>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $req['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $req['status'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state card">
                    <h3>No tienes solicitudes aún</h3>
                    <p>Crea tu primera solicitud de compra para empezar.</p>
                    <a href="create.php" class="btn btn-primary" style="margin-top: 1rem;">+ Nueva Solicitud</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>