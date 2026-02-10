<?php
/**
 * Módulo Herramientas - Historial de Movimientos
 * [!] ARQUITECTURA: Timeline visual de todos los movimientos de una herramienta
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id_herramienta = $_GET['id'] ?? null;

if (!$id_herramienta) {
    header("Location: index.php");
    exit;
}

// Obtener herramienta
$stmt = $pdo->prepare("SELECT * FROM herramientas WHERE id_herramienta = ?");
$stmt->execute([$id_herramienta]);
$herramienta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$herramienta) {
    echo "<div class='card' style='text-align:center; padding:40px;'>Herramienta no encontrada</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Obtener movimientos
$stmtMov = $pdo->prepare("
    SELECT m.*, c.nombre_cuadrilla, p.nombre_apellido
    FROM herramientas_movimientos m
    LEFT JOIN cuadrillas c ON c.id_cuadrilla = m.id_cuadrilla
    LEFT JOIN personal p ON p.id_personal = m.id_personal
    ORDER BY m.created_at DESC
");
$stmtMov->execute();
$movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

// Filtrar solo los de esta herramienta
$movimientos = array_filter($movimientos, fn($m) => $m['id_herramienta'] == $id_herramienta);

// Obtener sanciones
$stmtSan = $pdo->prepare("
    SELECT s.*, p.nombre_apellido, c.nombre_cuadrilla
    FROM herramientas_sanciones s
    LEFT JOIN personal p ON p.id_personal = s.id_personal
    LEFT JOIN cuadrillas c ON c.id_cuadrilla = s.id_cuadrilla
    WHERE s.id_herramienta = ?
    ORDER BY s.created_at DESC
");
$stmtSan->execute([$id_herramienta]);
$sanciones = $stmtSan->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: var(--text-primary);">
                <i class="fas fa-history"></i> Historial de Herramienta
            </h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary);">
                <strong>
                    <?php echo htmlspecialchars($herramienta['nombre']); ?>
                </strong>
                <?php if ($herramienta['marca']): ?> -
                    <?php echo htmlspecialchars($herramienta['marca']); ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="index.php" class="btn btn-outline"
            style="color: var(--text-secondary); border-color: var(--text-muted);">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Info Card -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px; padding: 20px; background: var(--bg-tertiary); border-radius: 10px;">
        <div>
            <small style="color: var(--text-muted);">Estado Actual</small>
            <div style="font-weight: 600; color: var(--text-primary);">
                <?php echo $herramienta['estado']; ?>
            </div>
        </div>
        <div>
            <small style="color: var(--text-muted);">Precio Reposición</small>
            <div style="font-weight: 600; color: var(--text-primary);">$
                <?php echo number_format($herramienta['precio_reposicion'], 2); ?>
            </div>
        </div>
        <div>
            <small style="color: var(--text-muted);">Nro Serie</small>
            <div style="font-weight: 600; color: var(--text-primary);">
                <?php echo $herramienta['numero_serie'] ?: '—'; ?>
            </div>
        </div>
        <div>
            <small style="color: var(--text-muted);">Fecha Compra</small>
            <div style="font-weight: 600; color: var(--text-primary);">
                <?php echo $herramienta['fecha_compra'] ? date('d/m/Y', strtotime($herramienta['fecha_compra'])) : '—'; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($sanciones)): ?>
        <!-- Sanciones -->
        <div style="margin-bottom: 30px;">
            <h3 style="color: #f44336; margin-bottom: 15px;"><i class="fas fa-exclamation-triangle"></i> Sanciones
                Registradas</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($sanciones as $s): ?>
                    <div
                        style="background: rgba(244, 67, 54, 0.1); border-left: 4px solid #f44336; padding: 15px; border-radius: 8px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <span
                                    style="background: #f44336; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600;">
                                    <?php echo $s['tipo_sancion']; ?>
                                </span>
                                <span style="margin-left: 10px; color: var(--text-secondary);">
                                    <?php echo date('d/m/Y', strtotime($s['fecha_incidente'])); ?>
                                </span>
                            </div>
                            <span
                                style="background: <?php echo $s['estado'] === 'Pendiente' ? '#ff9800' : ($s['estado'] === 'Aplicada' ? '#4caf50' : '#999'); ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">
                                <?php echo $s['estado']; ?>
                            </span>
                        </div>
                        <p style="margin: 10px 0 5px; color: var(--text-primary);">
                            <?php echo htmlspecialchars($s['descripcion']); ?>
                        </p>
                        <small style="color: var(--text-muted);">
                            Responsable: <strong>
                                <?php echo htmlspecialchars($s['nombre_apellido']); ?>
                            </strong>
                            <?php if ($s['monto_descuento'] > 0): ?>
                                | Monto: <strong>$
                                    <?php echo number_format($s['monto_descuento'], 2); ?>
                                </strong>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Timeline -->
    <h3 style="color: var(--text-primary); margin-bottom: 15px;"><i class="fas fa-stream"></i> Movimientos</h3>

    <?php if (empty($movimientos)): ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px;"></i><br>
            No hay movimientos registrados
        </div>
    <?php else: ?>
        <div class="timeline" style="position: relative; padding-left: 30px;">
            <?php foreach ($movimientos as $m):
                $iconos = [
                    'Compra' => ['icon' => 'fa-shopping-cart', 'color' => '#4caf50'],
                    'Asignacion' => ['icon' => 'fa-arrow-right', 'color' => '#2196f3'],
                    'Devolucion' => ['icon' => 'fa-undo', 'color' => '#ff9800'],
                    'Reparacion' => ['icon' => 'fa-wrench', 'color' => '#00bcd4'],
                    'Baja' => ['icon' => 'fa-times-circle', 'color' => '#f44336'],
                    'Sancion' => ['icon' => 'fa-exclamation-triangle', 'color' => '#f44336'],
                    'Reposicion' => ['icon' => 'fa-sync', 'color' => '#9c27b0']
                ];
                $cfg = $iconos[$m['tipo_movimiento']] ?? ['icon' => 'fa-circle', 'color' => '#999'];
                ?>
                <div
                    style="position: relative; padding-bottom: 20px; border-left: 2px solid var(--bg-tertiary); padding-left: 25px; margin-left: -30px;">
                    <div
                        style="position: absolute; left: -11px; top: 0; width: 22px; height: 22px; background: <?php echo $cfg['color']; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas <?php echo $cfg['icon']; ?>" style="color: white; font-size: 0.7em;"></i>
                    </div>
                    <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <strong style="color: var(--text-primary);">
                                <?php echo $m['tipo_movimiento']; ?>
                            </strong>
                            <small style="color: var(--text-muted);">
                                <?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?>
                            </small>
                        </div>
                        <?php if ($m['nombre_cuadrilla']): ?>
                            <p style="margin: 5px 0; color: var(--text-secondary);">
                                <i class="fas fa-hard-hat"></i>
                                <?php echo htmlspecialchars($m['nombre_cuadrilla']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($m['observaciones']): ?>
                            <p style="margin: 5px 0; color: var(--text-secondary); font-size: 0.9em;">
                                <?php echo htmlspecialchars($m['observaciones']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($m['monto']): ?>
                            <p style="margin: 5px 0; color: var(--accent-primary); font-weight: 600;">
                                $
                                <?php echo number_format($m['monto'], 2); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>