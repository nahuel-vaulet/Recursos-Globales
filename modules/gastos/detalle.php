<?php
/**
 * Vista: Detalle de Gasto
 * 
 * Muestra información completa de un gasto individual.
 * Usa las variables de tema globales de la aplicación.
 */

require_once '../../config/database.php';
require_once '../../includes/header.php';

$id_gasto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id_gasto) {
    header('Location: index.php');
    exit;
}

// Obtener datos del gasto
$stmt = $pdo->prepare("
    SELECT g.*, 
           p.nombre_apellido as responsable_nombre,
           r.fecha_rendicion
    FROM Administracion_Gastos g
    LEFT JOIN personal p ON g.id_responsable = p.id_personal
    LEFT JOIN Administracion_Rendiciones r ON g.id_rendicion = r.id_rendicion
    WHERE g.id_gasto = ?
");
$stmt->execute([$id_gasto]);
$gasto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gasto) {
    header('Location: index.php');
    exit;
}

$tiposGasto = [
    'Ferreteria' => '🔧 Ferretería',
    'Comida' => '🍔 Comida',
    'Peajes' => '🛣️ Peajes',
    'Combustible_Emergencia' => '⛽ Combustible Emergencia',
    'Insumos_Oficina' => '📎 Insumos Oficina',
    'Otros' => '📦 Otros'
];
?>

<style>
    /* Detalle Gasto - Usa variables de tema global */
    .detalle-container {
        max-width: 800px;
        margin: 0 auto;
        padding: var(--spacing-lg);
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        color: var(--accent-primary);
        text-decoration: none;
        margin-bottom: var(--spacing-lg);
        font-weight: 500;
        transition: all 0.2s;
    }

    .back-link:hover {
        color: var(--text-primary);
    }

    .detail-card {
        background: var(--bg-card);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
        overflow: hidden;
    }

    .detail-header {
        background: linear-gradient(135deg, #004A7F 0%, #0073A8 100%);
        color: white;
        padding: var(--spacing-lg);
        text-align: center;
    }

    .detail-header h1 {
        font-size: 1.5rem;
        margin-bottom: var(--spacing-sm);
        color: white;
    }

    .amount {
        font-size: 3rem;
        font-weight: 700;
    }

    .detail-body {
        padding: var(--spacing-lg);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }

    .info-item {
        padding: var(--spacing-md);
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.1);
    }

    [data-theme="light"] .info-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .info-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 500;
        color: var(--text-primary);
    }

    .status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-Pendiente {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .status-Rendido {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .status-Rechazado {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .type-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .type-Ferreteria {
        background: rgba(0, 188, 212, 0.15);
        color: #00bcd4;
    }

    .type-Comida {
        background: rgba(255, 152, 0, 0.15);
        color: #ff9800;
    }

    .type-Peajes {
        background: rgba(156, 39, 176, 0.15);
        color: #9c27b0;
    }

    .type-Combustible_Emergencia {
        background: rgba(244, 67, 54, 0.15);
        color: #f44336;
    }

    .type-Insumos_Oficina {
        background: rgba(76, 175, 80, 0.15);
        color: #4caf50;
    }

    .type-Otros {
        background: rgba(158, 158, 158, 0.15);
        color: #9e9e9e;
    }

    .comprobante-section {
        text-align: center;
        margin-top: var(--spacing-lg);
        padding-top: var(--spacing-lg);
        border-top: 1px solid rgba(100, 181, 246, 0.1);
    }

    [data-theme="light"] .comprobante-section {
        border-top-color: #e2e8f0;
    }

    .comprobante-section h3 {
        margin-bottom: var(--spacing-md);
        color: var(--text-primary);
    }

    .comprobante-img {
        max-width: 100%;
        max-height: 500px;
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.2);
    }

    .description-section {
        margin-top: var(--spacing-lg);
        padding: var(--spacing-md);
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-md);
        border-left: 4px solid var(--accent-primary);
    }

    [data-theme="light"] .description-section {
        background: #f8fafc;
    }

    .description-section h4 {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: var(--spacing-sm);
    }

    .description-section p {
        color: var(--text-primary);
    }

    .actions {
        display: flex;
        gap: var(--spacing-md);
        margin-top: var(--spacing-lg);
        padding-top: var(--spacing-lg);
        border-top: 1px solid rgba(100, 181, 246, 0.1);
    }

    [data-theme="light"] .actions {
        border-top-color: #e2e8f0;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: 12px var(--spacing-lg);
        font-size: 1rem;
        font-weight: 500;
        border: none;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-primary {
        background: linear-gradient(145deg, var(--accent-primary) 0%, #1d4ed8 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
    }

    .btn-danger {
        background: linear-gradient(145deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--accent-primary);
        color: var(--accent-primary);
    }

    .btn-outline:hover {
        background: rgba(100, 181, 246, 0.1);
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .detail-card {
            box-shadow: none;
            border: none;
        }
    }
</style>

<div class="detalle-container">
    <a href="index.php" class="back-link no-print">
        <i class="fas fa-arrow-left"></i> Volver a la lista
    </a>

    <div class="detail-card">
        <div class="detail-header">
            <h1>Gasto #<?php echo str_pad($id_gasto, 5, '0', STR_PAD_LEFT); ?></h1>
            <div class="amount">$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></div>
        </div>

        <div class="detail-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Fecha del Gasto</div>
                    <div class="info-value">
                        <?php echo date('d/m/Y', strtotime($gasto['fecha_gasto'])); ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Tipo de Gasto</div>
                    <div class="info-value">
                        <span class="type-badge type-<?php echo $gasto['tipo_gasto']; ?>">
                            <?php echo $tiposGasto[$gasto['tipo_gasto']] ?? $gasto['tipo_gasto']; ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Responsable</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($gasto['responsable_nombre'] ?? 'N/A'); ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo $gasto['estado']; ?>">
                            <?php echo $gasto['estado']; ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Fecha de Registro</div>
                    <div class="info-value">
                        <?php echo date('d/m/Y H:i', strtotime($gasto['fecha_creacion'])); ?>
                    </div>
                </div>

                <?php if ($gasto['fecha_rendicion']): ?>
                    <div class="info-item">
                        <div class="info-label">Fecha de Rendición</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($gasto['fecha_rendicion'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($gasto['descripcion'])): ?>
                <div class="description-section">
                    <h4>Descripción</h4>
                    <p><?php echo nl2br(htmlspecialchars($gasto['descripcion'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($gasto['comprobante_path'])): ?>
                <div class="comprobante-section">
                    <h3><i class="fas fa-receipt"></i> Comprobante</h3>
                    <img src="/APP-Prueba/uploads/comprobantes/<?php echo $gasto['comprobante_path']; ?>" alt="Comprobante"
                        class="comprobante-img">
                </div>
            <?php endif; ?>

            <div class="actions no-print">
                <button class="btn btn-outline" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>

                <?php if ($gasto['estado'] === 'Pendiente'): ?>
                    <button class="btn btn-danger" onclick="deleteGasto(<?php echo $id_gasto; ?>)">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    async function deleteGasto(id) {
        if (!confirm('¿Está seguro de eliminar este gasto?')) return;

        try {
            const response = await fetch('api/delete_gasto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Gasto eliminado', 'success');
                window.location.href = 'index.php';
            } else {
                showToast('Error: ' + data.error, 'error');
            }
        } catch (error) {
            showToast('Error de conexión', 'error');
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>