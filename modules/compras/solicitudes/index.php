<?php
require_once '../../../config/database.php';
require_once '../../../includes/header.php';

if (!tienePermiso('compras')) {
    header('Location: ../../../index.php?msg=forbidden');
    exit;
}

// Filtros
$status = $_GET['status'] ?? '';
$urgency = $_GET['urgency'] ?? '';

$where = " WHERE 1=1 ";
$params = [];

if ($status) {
    $where .= " AND cs.estado = ?";
    $params[] = $status;
}
if ($urgency) {
    $where .= " AND cs.urgencia = ?";
    $params[] = $urgency;
}

// Consultar Solicitudes
$stmt = $pdo->prepare("
    SELECT cs.*, u.nombre as requester_name,
           (SELECT COUNT(*) FROM compras_items_solicitud cis WHERE cis.id_solicitud = cs.id) as item_count
    FROM compras_solicitudes cs 
    JOIN usuarios u ON cs.id_usuario = u.id_usuario 
    $where
    ORDER BY 
        CASE cs.urgencia 
            WHEN 'critica' THEN 1 
            WHEN 'alta' THEN 2 
            WHEN 'media' THEN 3 
            ELSE 4 
        END,
        cs.fecha_creacion DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mensajes flash
$msg = $_GET['msg'] ?? '';
?>

<div class="container-fluid" style="padding: 0 20px;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin: 0;"><i class="fas fa-list"></i> Todas las Solicitudes</h1>
        <a href="form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Solicitud</a>
    </div>

    <?php if ($msg == 'created'): ?>
        <div class="alert alert-success"
            style="background-color: #D1FAE5; color: #065F46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> Solicitud creada exitosamente.
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 20px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label style="font-size: 0.9em; color: #666;">Estado</label>
                <select name="status" class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Todos</option>
                    <option value="enviada" <?= $status === 'enviada' ? 'selected' : '' ?>>Enviada</option>
                    <option value="en_revision" <?= $status === 'en_revision' ? 'selected' : '' ?>>En Revisión</option>
                    <option value="aprobada" <?= $status === 'aprobada' ? 'selected' : '' ?>>Aprobada</option>
                    <option value="rechazada" <?= $status === 'rechazada' ? 'selected' : '' ?>>Rechazada</option>
                </select>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 0.9em; color: #666;">Urgencia</label>
                <select name="urgency" class="form-control"
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Todas</option>
                    <option value="critica" <?= $urgency === 'critica' ? 'selected' : '' ?>>Crítica</option>
                    <option value="alta" <?= $urgency === 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="media" <?= $urgency === 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="baja" <?= $urgency === 'baja' ? 'selected' : '' ?>>Baja</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"
                style="padding: 8px 15px; border-radius: 6px; border: none; background: var(--color-primary); color: white; cursor: pointer;">
                Filtrar
            </button>
            <?php if ($status || $urgency): ?>
                <a href="index.php" class="btn btn-light"
                    style="padding: 8px 15px; border-radius: 6px; text-decoration: none; color: #666; border: 1px solid #ddd;">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--color-primary-dark); color: white; text-align: left;">
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;">Título</th>
                        <th style="padding: 12px;">Solicitante</th>
                        <th style="padding: 12px;">Urgencia</th>
                        <th style="padding: 12px;">Estado</th>
                        <th style="padding: 12px;">Ítems</th>
                        <th style="padding: 12px;">Fecha</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $req): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;">#
                                    <?= $req['id'] ?>
                                </td>
                                <td style="padding: 12px; font-weight: 500;">
                                    <?= htmlspecialchars($req['titulo']) ?>
                                </td>
                                <td style="padding: 12px; font-size: 0.9em;">
                                    <?= htmlspecialchars($req['requester_name']) ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php
                                    $urgenciaClass = 'badge-secondary';
                                    if ($req['urgencia'] == 'critica')
                                        $urgenciaClass = 'badge-danger';
                                    if ($req['urgencia'] == 'alta')
                                        $urgenciaClass = 'badge-warning';
                                    if ($req['urgencia'] == 'media')
                                        $urgenciaClass = 'badge-info';
                                    if ($req['urgencia'] == 'baja')
                                        $urgenciaClass = 'badge-success';
                                    ?>
                                    <span class="badge <?= $urgenciaClass ?>"
                                        style="padding: 4px 8px; border-radius: 12px; font-size: 0.75em; font-weight: 600; color: #fff; opacity: 0.9;">
                                        <?= ucfirst($req['urgencia']) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <?php
                                    $estadoClass = 'badge-secondary';
                                    if ($req['estado'] == 'enviada')
                                        $estadoClass = 'badge-info';
                                    if ($req['estado'] == 'aprobada')
                                        $estadoClass = 'badge-success';
                                    if ($req['estado'] == 'rechazada')
                                        $estadoClass = 'badge-danger';
                                    if ($req['estado'] == 'en_revision')
                                        $estadoClass = 'badge-warning';
                                    ?>
                                    <span class="badge <?= $estadoClass ?>"
                                        style="padding: 4px 8px; border-radius: 12px; font-size: 0.75em; font-weight: 600; color: #fff; opacity: 0.9;">
                                        <?= ucfirst(str_replace('_', ' ', $req['estado'])) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #666;">
                                    <?= $req['item_count'] ?>
                                </td>
                                <td style="padding: 12px; font-size: 0.9em; color: #666;">
                                    <?= date('d/m/Y', strtotime($req['fecha_creacion'])) ?>
                                </td>
                                <td style="padding: 12px;">
                                    <a href="form.php?id=<?= $req['id'] ?>&action=view" class="btn btn-sm btn-outline-primary"
                                        style="padding: 5px 10px; border: 1px solid var(--color-primary); border-radius: 4px; text-decoration: none; color: var(--color-primary);">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="padding: 30px; text-align: center; color: #999;">No hay solicitudes
                                registradas con estos filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Inline badges helper if global css is missing them */
    .badge-secondary {
        background-color: #6B7280 !important;
    }

    .badge-info {
        background-color: #3B82F6 !important;
    }

    .badge-success {
        background-color: #10B981 !important;
    }

    .badge-warning {
        background-color: #F59E0B !important;
    }

    .badge-danger {
        background-color: #EF4444 !important;
    }
</style>

<?php require_once '../../../includes/footer.php'; ?>