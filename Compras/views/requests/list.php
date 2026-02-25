<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'comprador') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';

// Filters
$status = $_GET['status'] ?? '';
$urgency = $_GET['urgency'] ?? '';

$where = " WHERE 1=1 ";
$params = [];

if ($status) {
    $where .= " AND pr.status = ?";
    $params[] = $status;
}
if ($urgency) {
    $where .= " AND pr.urgency = ?";
    $params[] = $urgency;
}

$stmt = $pdo->prepare("
    SELECT pr.*, u.full_name as requester_name, u.department,
           (SELECT COUNT(*) FROM request_items ri WHERE ri.request_id = pr.id) as item_count
    FROM purchase_requests pr 
    JOIN users u ON pr.user_id = u.id 
    $where
    ORDER BY 
        CASE pr.urgency 
            WHEN 'critica' THEN 1 
            WHEN 'alta' THEN 2 
            WHEN 'media' THEN 3 
            ELSE 4 
        END,
        pr.created_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes - Módulo de Compras</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filters select {
            padding: 0.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .data-table th, .data-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .data-table tr:hover {
            background: var(--bg-body);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-enviada { background: #DBEAFE; color: #1D4ED8; }
        .status-aprobada { background: #D1FAE5; color: #047857; }
        .status-rechazada { background: #FEE2E2; color: #DC2626; }
        .status-en_revision { background: #FEF3C7; color: #D97706; }
        .status-borrador { background: #F3F4F6; color: #6B7280; }
        .urgency-badge {
            display: inline-block;
            padding: 0.125rem 0.375rem;
            border-radius: var(--radius-sm);
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .urgency-baja { background: #D1FAE5; color: #047857; }
        .urgency-media { background: #DBEAFE; color: #1D4ED8; }
        .urgency-alta { background: #FEF3C7; color: #D97706; }
        .urgency-critica { background: #FEE2E2; color: #DC2626; }
        .action-btns {
            display: flex;
            gap: 0.25rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <h2 style="margin-bottom: 2rem;">🛒 Compras</h2>
            <nav>
                <a href="../dashboard/index.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Dashboard</a>
                <a href="list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">Solicitudes</a>
                <a href="../providers/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Proveedores</a>
                <a href="../orders/list.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Órdenes de Compra</a>
            </nav>
            <div style="margin-top: auto; padding-top: 2rem;">
                <p style="font-size: 0.75rem; opacity: 0.7;">👤 <?= htmlspecialchars($userName) ?></p>
                <a href="../../logout.php" style="font-size: 0.75rem; opacity: 0.7;">Cerrar sesión</a>
            </div>
        </aside>
        
        <main class="main-content">
            <h1 style="margin-bottom: 1.5rem;">Todas las Solicitudes</h1>
            
            <form class="filters" method="GET">
                <select name="status">
                    <option value="">Todos los estados</option>
                    <option value="enviada" <?= $status === 'enviada' ? 'selected' : '' ?>>Enviada</option>
                    <option value="en_revision" <?= $status === 'en_revision' ? 'selected' : '' ?>>En Revisión</option>
                    <option value="aprobada" <?= $status === 'aprobada' ? 'selected' : '' ?>>Aprobada</option>
                    <option value="rechazada" <?= $status === 'rechazada' ? 'selected' : '' ?>>Rechazada</option>
                </select>
                <select name="urgency">
                    <option value="">Todas las urgencias</option>
                    <option value="critica" <?= $urgency === 'critica' ? 'selected' : '' ?>>Crítica</option>
                    <option value="alta" <?= $urgency === 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="media" <?= $urgency === 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="baja" <?= $urgency === 'baja' ? 'selected' : '' ?>>Baja</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="list.php" class="btn">Limpiar</a>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Solicitante</th>
                        <th>Depto</th>
                        <th>Urgencia</th>
                        <th>Estado</th>
                        <th>Ítems</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>#<?= $req['id'] ?></td>
                            <td><?= htmlspecialchars($req['title']) ?></td>
                            <td><?= htmlspecialchars($req['requester_name']) ?></td>
                            <td><?= htmlspecialchars($req['department'] ?? '-') ?></td>
                            <td><span class="urgency-badge urgency-<?= $req['urgency'] ?>"><?= ucfirst($req['urgency']) ?></span></td>
                            <td><span class="status-badge status-<?= $req['status'] ?>"><?= ucfirst(str_replace('_', ' ', $req['status'])) ?></span></td>
                            <td><?= $req['item_count'] ?></td>
                            <td><?= date('d/m/Y', strtotime($req['created_at'])) ?></td>
                            <td class="action-btns">
                                <a href="view.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted);">No hay solicitudes</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
