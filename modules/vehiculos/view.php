<?php
/**
 * Vista detalle de Vehículo — Ficha completa readonly
 * [!] ARCH: Muestra toda la info, mantenimiento y reparaciones
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch vehicle
$stmt = $pdo->prepare("
    SELECT v.*, c.nombre_cuadrilla 
    FROM vehiculos v 
    LEFT JOIN cuadrillas c ON v.id_cuadrilla = c.id_cuadrilla 
    WHERE v.id_vehiculo = ?
");
$stmt->execute([$id]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$v) {
    header("Location: index.php?msg=not_found");
    exit;
}

// Fetch maintenance
$stmtMant = $pdo->prepare("SELECT * FROM vehiculos_mantenimiento WHERE id_vehiculo = ? ORDER BY tipo");
$stmtMant->execute([$id]);
$mantenimiento = $stmtMant->fetchAll(PDO::FETCH_ASSOC);

// Fetch repairs
$stmtRep = $pdo->prepare("SELECT * FROM vehiculos_reparaciones WHERE id_vehiculo = ? ORDER BY fecha DESC");
$stmtRep->execute([$id]);
$reparaciones = $stmtRep->fetchAll(PDO::FETCH_ASSOC);

// Insurance status
$seguroVence = $v['vencimiento_seguro'] ? new DateTime($v['vencimiento_seguro']) : null;
$seguroDias = $seguroVence ? (new DateTime())->diff($seguroVence) : null;

// VTV status
$vtvVence = $v['vencimiento_vtv'] ? new DateTime($v['vencimiento_vtv']) : null;
$vtvDias = $vtvVence ? (new DateTime())->diff($vtvVence) : null;

// Service status
$kmFaltante = ($v['proximo_service_km'] && $v['km_actual']) ? ($v['proximo_service_km'] - $v['km_actual']) : null;

// Total maintenance cost
$totalUsd = array_sum(array_column($mantenimiento, 'precio_usd'));
$totalArs = array_sum(array_column($mantenimiento, 'precio_ars'));
$totalRepArs = 0;
$totalRepUsd = 0;
foreach ($reparaciones as $r) {
    if ($r['moneda'] === 'USD')
        $totalRepUsd += $r['costo'];
    else
        $totalRepArs += $r['costo'];
}
?>

<div class="view-container">

    <!-- ═══ HEADER ═══ -->
    <div class="view-header">
        <div class="header-left">
            <?php if (!empty($v['foto_estado'])): ?>
                <img src="../../uploads/vehiculos/<?php echo $v['foto_estado']; ?>" class="vehicle-photo"
                    alt="Foto vehículo" onclick="window.open(this.src, '_blank')">
            <?php else: ?>
                <div class="vehicle-photo-placeholder">
                    <i class="fas fa-truck" style="font-size: 2.5em;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="header-info">
            <h1 class="patente-title">
                <?php echo htmlspecialchars($v['patente']); ?>
            </h1>
            <div class="vehicle-meta">
                <?php echo htmlspecialchars(trim(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?: 'Sin marca/modelo'); ?>
                <?php if ($v['anio']): ?> •
                    <?php echo $v['anio']; ?>
                <?php endif; ?>
            </div>
            <div class="vehicle-badges">
                <span class="badge-tipo">
                    <?php echo $v['tipo']; ?>
                </span>
                <span class="badge-estado <?php echo strtolower($v['estado']); ?>">
                    <?php echo $v['estado']; ?>
                </span>
                <?php if ($v['gestya_instalado']): ?>
                    <span class="badge-gestya">📡 Gestya</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-actions">
            <a href="form.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <!-- ═══ KPI CARDS ═══ -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-tachometer-alt"></i></div>
            <div class="kpi-data">
                <div class="kpi-value">
                    <?php echo number_format($v['km_actual'] ?? 0, 0, ',', '.'); ?>
                </div>
                <div class="kpi-label">Km Actual</div>
            </div>
        </div>
        <div class="kpi-card <?php echo ($kmFaltante !== null && $kmFaltante <= 500) ? 'warning' : ''; ?>">
            <div class="kpi-icon"><i class="fas fa-wrench"></i></div>
            <div class="kpi-data">
                <div class="kpi-value">
                    <?php echo $kmFaltante !== null ? number_format($kmFaltante, 0, ',', '.') . ' km' : '-'; ?>
                </div>
                <div class="kpi-label">Para Service</div>
            </div>
        </div>
        <div class="kpi-card <?php
        if ($seguroDias && $seguroDias->invert)
            echo 'danger';
        elseif ($seguroDias && $seguroDias->days <= 30)
            echo 'warning';
        ?>">
            <div class="kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="kpi-data">
                <div class="kpi-value">
                    <?php
                    if (!$seguroVence)
                        echo '-';
                    elseif ($seguroDias->invert)
                        echo 'VENCIDO';
                    else
                        echo $seguroDias->days . 'd';
                    ?>
                </div>
                <div class="kpi-label">Seguro</div>
            </div>
        </div>
        <div class="kpi-card <?php
        if ($vtvDias && $vtvDias->invert)
            echo 'danger';
        elseif ($vtvDias && $vtvDias->days <= 30)
            echo 'warning';
        ?>">
            <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
            <div class="kpi-data">
                <div class="kpi-value">
                    <?php
                    if (!$vtvVence)
                        echo '-';
                    elseif ($vtvDias->invert)
                        echo 'VENCIDO';
                    else
                        echo $vtvDias->days . 'd';
                    ?>
                </div>
                <div class="kpi-label">VTV</div>
            </div>
        </div>
    </div>

    <!-- ═══ INFO TABS ═══ -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('info')"><i class="fas fa-info-circle"></i>
                Info</button>
            <button class="tab-btn" onclick="switchTab('seguro')"><i class="fas fa-shield-alt"></i> Seguro</button>
            <button class="tab-btn" onclick="switchTab('mantenimiento')"><i class="fas fa-oil-can"></i>
                Mantenimiento</button>
            <button class="tab-btn" onclick="switchTab('reparaciones')"><i class="fas fa-tools"></i>
                Reparaciones <span class="tab-count">
                    <?php echo count($reparaciones); ?>
                </span></button>
        </div>

        <!-- TAB: Info General -->
        <div class="tab-content active" id="tab-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Tipo</span>
                    <span class="info-value">
                        <?php echo $v['tipo']; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Combustible</span>
                    <span class="info-value">
                        <?php echo $v['tipo_combustible']; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cuadrilla</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($v['nombre_cuadrilla'] ?? 'Sin asignar'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nivel Aceite</span>
                    <span class="info-value">
                        <?php
                        $icons = ['OK' => '✅', 'Bajo' => '⚠️', 'Crítico' => '🔴'];
                        echo ($icons[$v['nivel_aceite']] ?? '') . ' ' . $v['nivel_aceite'];
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nivel Combustible</span>
                    <span class="info-value">
                        <?php
                        $icons = ['Lleno' => '🟢', 'Medio' => '🟡', 'Bajo' => '🟠', 'Reserva' => '🔴'];
                        echo ($icons[$v['nivel_combustible']] ?? '') . ' ' . $v['nivel_combustible'];
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Frenos</span>
                    <span class="info-value">
                        <?php
                        $icons = ['OK' => '✅', 'Desgastados' => '⚠️', 'Cambiar' => '🔴'];
                        echo ($icons[$v['estado_frenos']] ?? '') . ' ' . $v['estado_frenos'];
                        ?>
                    </span>
                </div>
                <?php if ($v['costo_reposicion']): ?>
                    <div class="info-item">
                        <span class="info-label">Costo Reposición</span>
                        <span class="info-value">$
                            <?php echo number_format($v['costo_reposicion'], 2, ',', '.'); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if ($v['gestya_instalado']): ?>
                    <div class="info-item highlight">
                        <span class="info-label">📡 Gestya</span>
                        <span class="info-value">
                            Instalado
                            <?php if ($v['gestya_fecha_instalacion']): ?>
                                -
                                <?php echo date('d/m/Y', strtotime($v['gestya_fecha_instalacion'])); ?>
                            <?php endif; ?>
                            <?php if ($v['gestya_lugar']): ?>
                                <br><small>
                                    <?php echo htmlspecialchars($v['gestya_lugar']); ?>
                                </small>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($v['observaciones']): ?>
                <div class="obs-box">
                    <strong><i class="fas fa-sticky-note"></i> Observaciones:</strong>
                    <p>
                        <?php echo nl2br(htmlspecialchars($v['observaciones'])); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: Seguro -->
        <div class="tab-content" id="tab-seguro">
            <?php if ($v['seguro_nombre']): ?>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Aseguradora</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($v['seguro_nombre']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value">
                            <?php if ($v['seguro_telefono']): ?>
                                <a href="tel:<?php echo $v['seguro_telefono']; ?>" style="color: var(--accent-primary);">
                                    <i class="fas fa-phone"></i>
                                    <?php echo $v['seguro_telefono']; ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono Grúa</span>
                        <span class="info-value">
                            <?php if ($v['seguro_grua_telefono']): ?>
                                <a href="tel:<?php echo $v['seguro_grua_telefono']; ?>"
                                    style="color: #e74c3c; font-weight: 700;">
                                    <i class="fas fa-truck-loading"></i>
                                    <?php echo $v['seguro_grua_telefono']; ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cobertura</span>
                        <span class="info-value">
                            <?php echo $v['seguro_cobertura'] ?? '-'; ?>
                            <?php if ($v['seguro_cobertura'] === 'Todo Riesgo' && $v['seguro_franquicia']): ?>
                                <br><small>Franquicia:
                                    $
                                    <?php echo number_format($v['seguro_franquicia'], 2, ',', '.'); ?>
                                </small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Valor Mensual</span>
                        <span class="info-value">
                            <?php echo $v['seguro_valor'] ? '$' . number_format($v['seguro_valor'], 2, ',', '.') : '-'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Vencimiento</span>
                        <span class="info-value">
                            <?php echo $v['vencimiento_seguro'] ? date('d/m/Y', strtotime($v['vencimiento_seguro'])) : '-'; ?>
                        </span>
                    </div>
                </div>
                <?php if ($v['seguro_poliza_pdf']): ?>
                    <div style="margin-top: 15px;">
                        <a href="../../uploads/vehiculos/polizas/<?php echo $v['seguro_poliza_pdf']; ?>" target="_blank"
                            class="btn btn-outline">
                            <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> Ver Póliza (PDF)
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shield-alt"></i>
                    <p>Sin datos de seguro cargados</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: Mantenimiento -->
        <div class="tab-content" id="tab-mantenimiento">
            <?php if (!empty($mantenimiento)): ?>
                <div class="table-responsive">
                    <table class="mant-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Código</th>
                                <th>Marca</th>
                                <th>Equivalencia / Tipo Aceite</th>
                                <th>Cant.</th>
                                <th style="text-align:right;">USD</th>
                                <th style="text-align:right;">ARS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mantenimiento as $m): ?>
                                <tr>
                                    <td><strong>
                                            <?php echo $m['tipo']; ?>
                                        </strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($m['codigo'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($m['marca'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($m['equivalencia'] ?? $m['tipo_aceite'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo $m['cantidad'] ? $m['cantidad'] . 'L' : '-'; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php echo $m['precio_usd'] ? 'U$D ' . number_format($m['precio_usd'], 2, ',', '.') : '-'; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php echo $m['precio_ars'] ? '$ ' . number_format($m['precio_ars'], 2, ',', '.') : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align:right;"><strong>TOTALES:</strong></td>
                                <td style="text-align:right;">
                                    <strong>
                                        <?php echo $totalUsd > 0 ? 'U$D ' . number_format($totalUsd, 2, ',', '.') : '-'; ?>
                                    </strong>
                                </td>
                                <td style="text-align:right;">
                                    <strong>
                                        <?php echo $totalArs > 0 ? '$ ' . number_format($totalArs, 2, ',', '.') : '-'; ?>
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-oil-can"></i>
                    <p>Sin datos de mantenimiento</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: Reparaciones -->
        <div class="tab-content" id="tab-reparaciones">
            <?php if (!empty($reparaciones)): ?>
                <?php if ($totalRepArs > 0 || $totalRepUsd > 0): ?>
                    <div class="repair-totals">
                        <?php if ($totalRepArs > 0): ?>
                            <span>Total ARS: <strong>$
                                    <?php echo number_format($totalRepArs, 2, ',', '.'); ?>
                                </strong></span>
                        <?php endif; ?>
                        <?php if ($totalRepUsd > 0): ?>
                            <span>Total USD: <strong>U$D
                                    <?php echo number_format($totalRepUsd, 2, ',', '.'); ?>
                                </strong></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($reparaciones as $r): ?>
                    <div class="repair-card">
                        <div class="repair-header">
                            <span class="repair-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($r['fecha'])); ?>
                            </span>
                            <span class="repair-cost">
                                <?php echo $r['moneda']; ?>
                                $
                                <?php echo number_format($r['costo'] ?? 0, 2, ',', '.'); ?>
                            </span>
                        </div>
                        <p class="repair-desc">
                            <?php echo nl2br(htmlspecialchars($r['descripcion'])); ?>
                        </p>
                        <div class="repair-meta">
                            <?php if ($r['realizado_por']): ?>
                                <span><i class="fas fa-user-cog"></i>
                                    <?php echo htmlspecialchars($r['realizado_por']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($r['tiempo_horas']): ?>
                                <span><i class="fas fa-clock"></i>
                                    <?php echo $r['tiempo_horas']; ?>hs
                                </span>
                            <?php endif; ?>
                            <?php if ($r['codigos_repuestos']): ?>
                                <span><i class="fas fa-barcode"></i>
                                    <?php echo htmlspecialchars($r['codigos_repuestos']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($r['proveedor_repuestos']): ?>
                                <span><i class="fas fa-store"></i>
                                    <?php echo htmlspecialchars($r['proveedor_repuestos']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tools"></i>
                    <p>Sin reparaciones registradas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
    .view-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    /* Header */
    .view-header {
        display: flex;
        gap: 20px;
        align-items: center;
        padding: 25px;
        background: var(--bg-card);
        border-radius: 14px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-md);
        flex-wrap: wrap;
    }

    .vehicle-photo {
        width: 140px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        border: 2px solid var(--bg-secondary);
        transition: transform 0.2s;
    }

    .vehicle-photo:hover {
        transform: scale(1.05);
    }

    .vehicle-photo-placeholder {
        width: 140px;
        height: 100px;
        border-radius: 10px;
        background: var(--bg-tertiary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }

    .header-info {
        flex: 1;
    }

    .patente-title {
        margin: 0;
        font-size: 1.8em;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: 1px;
    }

    .vehicle-meta {
        color: var(--text-secondary);
        font-size: 0.95em;
        margin-top: 4px;
    }

    .vehicle-badges {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .badge-tipo {
        background: rgba(59, 130, 246, 0.15);
        color: var(--accent-primary);
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.8em;
        font-weight: 600;
    }

    .badge-estado {
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.8em;
        font-weight: 600;
    }

    .badge-estado.operativo {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .badge-estado.en\ taller {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .badge-estado.baja {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .badge-gestya {
        background: rgba(139, 92, 246, 0.15);
        color: #8b5cf6;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.8em;
        font-weight: 600;
    }

    .header-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* KPI Row */
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .kpi-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--bg-secondary);
    }

    .kpi-card.warning {
        border-color: rgba(245, 158, 11, 0.4);
        background: rgba(245, 158, 11, 0.05);
    }

    .kpi-card.danger {
        border-color: rgba(239, 68, 68, 0.4);
        background: rgba(239, 68, 68, 0.05);
    }

    .kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--bg-tertiary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1em;
        color: var(--accent-primary);
    }

    .kpi-value {
        font-size: 1.2em;
        font-weight: 800;
        color: var(--text-primary);
    }

    .kpi-label {
        font-size: 0.75em;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* Tabs */
    .tabs-container {
        background: var(--bg-card);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .tabs-nav {
        display: flex;
        border-bottom: 2px solid var(--bg-secondary);
        overflow-x: auto;
    }

    .tab-btn {
        flex: 1;
        padding: 14px 16px;
        background: none;
        border: none;
        font-size: 0.9em;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
        position: relative;
        transition: all 0.2s;
    }

    .tab-btn:hover {
        color: var(--text-primary);
        background: var(--bg-tertiary);
    }

    .tab-btn.active {
        color: var(--accent-primary);
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--accent-primary);
        border-radius: 3px 3px 0 0;
    }

    .tab-count {
        background: var(--accent-primary);
        color: white;
        padding: 1px 7px;
        border-radius: 10px;
        font-size: 0.78em;
    }

    .tab-content {
        display: none;
        padding: 20px;
    }

    .tab-content.active {
        display: block;
    }

    /* Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .info-item {
        padding: 12px;
        background: var(--bg-tertiary);
        border-radius: 10px;
        border: 1px solid var(--bg-secondary);
    }

    .info-item.highlight {
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.05);
    }

    .info-label {
        font-size: 0.72em;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.3px;
        display: block;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 0.95em;
        font-weight: 600;
        color: var(--text-primary);
        display: block;
    }

    .obs-box {
        margin-top: 15px;
        padding: 14px;
        background: var(--bg-tertiary);
        border-radius: 10px;
        border-left: 3px solid var(--accent-primary);
    }

    .obs-box p {
        margin: 8px 0 0;
        color: var(--text-secondary);
        font-size: 0.92em;
    }

    /* Maintenance Table */
    .table-responsive {
        overflow-x: auto;
    }

    .mant-table {
        width: 100%;
        border-collapse: collapse;
    }

    .mant-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 0.78em;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: var(--text-muted);
        border-bottom: 2px solid var(--bg-secondary);
    }

    .mant-table td {
        padding: 10px 12px;
        font-size: 0.9em;
        color: var(--text-primary);
        border-bottom: 1px solid var(--bg-secondary);
    }

    .mant-table tfoot td {
        border-top: 2px solid var(--accent-primary);
        padding-top: 12px;
    }

    /* Repairs */
    .repair-totals {
        display: flex;
        gap: 20px;
        padding: 12px 16px;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 10px;
        margin-bottom: 15px;
        font-size: 0.9em;
        color: var(--text-primary);
    }

    .repair-card {
        background: var(--bg-tertiary);
        border: 1px solid var(--bg-secondary);
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
    }

    .repair-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .repair-date {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 0.9em;
    }

    .repair-cost {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.85em;
        font-weight: 600;
    }

    .repair-desc {
        margin: 0 0 8px;
        color: var(--text-primary);
        font-size: 0.92em;
        line-height: 1.4;
    }

    .repair-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .repair-meta span {
        font-size: 0.78em;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .empty-state {
        text-align: center;
        padding: 30px;
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 2.5em;
        opacity: 0.2;
        display: block;
        margin-bottom: 10px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .view-header {
            flex-direction: column;
            text-align: center;
        }

        .header-actions {
            flex-direction: row;
            width: 100%;
        }

        .header-actions a {
            flex: 1;
            text-align: center;
        }

        .kpi-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

        document.getElementById('tab-' + tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>

<?php require_once '../../includes/footer.php'; ?>