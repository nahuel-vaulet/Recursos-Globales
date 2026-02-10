<?php
/**
 * Módulo Herramientas - Listado de Sanciones
 * [!] ARQUITECTURA: Gestión de sanciones por pérdida, rotura o mal uso
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Obtener sanciones con JOINs
$sql = "SELECT s.*, h.nombre as herramienta_nombre, p.nombre_apellido, c.nombre_cuadrilla
        FROM herramientas_sanciones s
        LEFT JOIN herramientas h ON h.id_herramienta = s.id_herramienta
        LEFT JOIN personal p ON p.id_personal = s.id_personal
        LEFT JOIN cuadrillas c ON c.id_cuadrilla = s.id_cuadrilla
        ORDER BY s.created_at DESC";
$sanciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$total = count($sanciones);
$pendientes = count(array_filter($sanciones, fn($s) => $s['estado'] === 'Pendiente'));
$aplicadas = count(array_filter($sanciones, fn($s) => $s['estado'] === 'Aplicada'));
$montoTotal = array_sum(array_column($sanciones, 'monto_descuento'));
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: var(--text-primary);"><i class="fas fa-exclamation-triangle"></i> Sanciones de
                Herramientas</h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary);">Pérdidas, roturas y mal uso</p>
        </div>
        <a href="index.php" class="btn btn-outline"
            style="color: var(--text-secondary); border-color: var(--text-muted);">
            <i class="fas fa-arrow-left"></i> Volver a Herramientas
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="metrics-row" style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
        <div class="metric-mini"
            style="background: var(--bg-card); border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm);">
            <div
                style="width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(244, 67, 54, 0.15); color: #f44336;">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div>
                <span style="font-size: 1.4em; font-weight: 700; color: var(--text-primary); display: block;">
                    <?php echo $total; ?>
                </span>
                <span style="font-size: 0.8em; color: var(--text-secondary);">Total Sanciones</span>
            </div>
        </div>
        <div class="metric-mini"
            style="background: var(--bg-card); border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm);">
            <div
                style="width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(255, 152, 0, 0.15); color: #ff9800;">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <span style="font-size: 1.4em; font-weight: 700; color: var(--text-primary); display: block;">
                    <?php echo $pendientes; ?>
                </span>
                <span style="font-size: 0.8em; color: var(--text-secondary);">Pendientes</span>
            </div>
        </div>
        <div class="metric-mini"
            style="background: var(--bg-card); border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm);">
            <div
                style="width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(76, 175, 80, 0.15); color: #4caf50;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <span style="font-size: 1.4em; font-weight: 700; color: var(--text-primary); display: block;">
                    <?php echo $aplicadas; ?>
                </span>
                <span style="font-size: 0.8em; color: var(--text-secondary);">Aplicadas</span>
            </div>
        </div>
        <div class="metric-mini"
            style="background: var(--bg-card); border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm);">
            <div
                style="width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(33, 150, 243, 0.15); color: var(--accent-primary);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div>
                <span style="font-size: 1.4em; font-weight: 700; color: var(--text-primary); display: block;">$
                    <?php echo number_format($montoTotal, 0, ',', '.'); ?>
                </span>
                <span style="font-size: 0.8em; color: var(--text-secondary);">Monto Total</span>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card" style="border-top: 4px solid #f44336;">
        <?php if (empty($sanciones)): ?>
            <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                <i class="fas fa-check-circle" style="font-size: 3em; margin-bottom: 15px; color: #4caf50;"></i><br>
                <h3>No hay sanciones registradas</h3>
                <p>Todas las herramientas están en buen estado</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" id="sancionesTable">
                    <thead>
                        <tr style="background: var(--bg-secondary);">
                            <th style="color: var(--text-secondary);">Fecha</th>
                            <th style="color: var(--text-secondary);">Herramienta</th>
                            <th style="color: var(--text-secondary);">Tipo</th>
                            <th style="color: var(--text-secondary);">Responsable</th>
                            <th style="color: var(--text-secondary);">Cuadrilla</th>
                            <th style="color: var(--text-secondary); text-align: right;">Monto</th>
                            <th style="color: var(--text-secondary);">Estado</th>
                            <th class="text-center" style="color: var(--text-secondary);">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sanciones as $s): ?>
                            <tr>
                                <td style="color: var(--text-primary);">
                                    <?php echo date('d/m/Y', strtotime($s['fecha_incidente'])); ?>
                                </td>
                                <td>
                                    <a href="historial.php?id=<?php echo $s['id_herramienta']; ?>"
                                        style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">
                                        <?php echo htmlspecialchars($s['herramienta_nombre']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $tipoColores = ['Pérdida' => '#f44336', 'Perdida' => '#f44336', 'Rotura' => '#ff9800', 'Mal Uso' => '#9c27b0'];
                                    $color = $tipoColores[$s['tipo_sancion']] ?? '#999';
                                    ?>
                                    <span
                                        style="background: <?php echo $color; ?>; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.85em;">
                                        <?php echo $s['tipo_sancion']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-primary);">
                                    <i class="fas fa-user" style="color: var(--text-muted); margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($s['nombre_apellido']); ?>
                                </td>
                                <td style="color: var(--text-secondary);">
                                    <?php echo $s['nombre_cuadrilla'] ? htmlspecialchars($s['nombre_cuadrilla']) : '—'; ?>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: var(--text-primary);">
                                    $
                                    <?php echo number_format($s['monto_descuento'], 2); ?>
                                </td>
                                <td>
                                    <?php
                                    $estadoColores = ['Pendiente' => '#ff9800', 'Aplicada' => '#4caf50', 'Anulada' => '#999'];
                                    $colorEstado = $estadoColores[$s['estado']] ?? '#999';
                                    ?>
                                    <span
                                        style="background: <?php echo $colorEstado; ?>; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.85em;">
                                        <?php echo $s['estado']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['estado'] === 'Pendiente'): ?>
                                        <button onclick="aplicarSancion(<?php echo $s['id_sancion']; ?>)"
                                            class="btn-icon btn-success" title="Aplicar Sanción"
                                            style="background: none; border: 1px solid #4caf50; color: #4caf50; border-radius: 6px; padding: 6px 10px; cursor: pointer;">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="anularSancion(<?php echo $s['id_sancion']; ?>)" class="btn-icon btn-danger"
                                            title="Anular"
                                            style="background: none; border: 1px solid #f44336; color: #f44336; border-radius: 6px; padding: 6px 10px; cursor: pointer;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .text-center {
        text-align: center;
    }
</style>

<script>
    function aplicarSancion(id) {
        if (!confirm('¿Marcar esta sanción como APLICADA?')) return;

        fetch('api_sanciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'aplicar', id_sancion: id })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.message);
            });
    }

    function anularSancion(id) {
        if (!confirm('¿ANULAR esta sanción?')) return;

        fetch('api_sanciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'anular', id_sancion: id })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.message);
            });
    }
</script>

<?php require_once '../../includes/footer.php'; ?>