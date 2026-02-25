<?php
/**
 * [!] ARCH: Panel de Cuadrillas — ODTs por cuadrilla (hoy/mañana)
 * Vista Gerente: resumen de todas las cuadrillas
 * Vista JefeCuadrilla: solo su cuadrilla, acciones rápidas
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../services/CrewService.php';
require_once '../../services/DateUtil.php';
require_once '../../services/PriorityUtil.php';
require_once '../../services/StateMachine.php';

if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

$rolActual = $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '';
$idCuadrillaUsuario = $_SESSION['usuario_id_cuadrilla'] ?? null;

$crewService = new CrewService($pdo);

// JefeCuadrilla: solo su cuadrilla
if ($rolActual === 'JefeCuadrilla' && $idCuadrillaUsuario) {
    $datosCuadrilla = $crewService->obtenerODTsCuadrilla((int) $idCuadrillaUsuario);
    $modoCuadrilla = true;
} else {
    $resumen = $crewService->resumenCuadrillas();
    $modoCuadrilla = false;
}

$estadoColors = StateMachine::getStateColors();
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
?>

<div class="container-fluid" style="padding: 0 20px;">
    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="margin: 0; font-size: 1.4em; color: var(--text-primary);">
                <i class="fas fa-users" style="color: #00bcd4;"></i>
                <?php echo $modoCuadrilla ? 'Mis ODTs de Hoy y Mañana' : 'Panel de Cuadrillas'; ?>
            </h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary); font-size: 0.9em;">
                <?php
                $rango = $modoCuadrilla ? $datosCuadrilla['rango'] : $resumen['rango'];
                echo DateUtil::formatear($rango['hoy']) . ' — ' . DateUtil::formatear($rango['manana']);
                ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="index.php" class="btn"
                style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); color: var(--accent-primary); display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <i class="fas fa-list"></i> Lista ODTs
            </a>
            <?php if (!$modoCuadrilla): ?>
                <a href="calendar.php" class="btn"
                    style="min-height: 45px; padding: 0 15px; background: var(--bg-secondary); border: 1px solid var(--accent-primary); color: var(--accent-primary); display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-calendar-alt"></i> Calendario
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($modoCuadrilla): ?>
        <!-- ═══ VISTA JEFE DE CUADRILLA: ODTs hoy y mañana ═══ -->
        <?php foreach (['hoy' => 'Hoy', 'manana' => 'Mañana'] as $key => $label):
            $odts = $datosCuadrilla[$key];
            $fechaLabel = DateUtil::formatear($rango[$key]);
            ?>
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 12px; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <span style="background: <?php echo $key === 'hoy' ? '#e8f5e9' : '#e3f2fd'; ?>; color: <?php echo $key === 'hoy' ? '#2e7d32' : '#1565c0'; ?>;
                padding: 5px 12px; border-radius: 8px; font-size: 0.85em;">
                        <?php echo $label; ?> —
                        <?php echo $fechaLabel; ?>
                    </span>
                    <span style="color: var(--text-muted); font-size: 0.8em;">(
                        <?php echo count($odts); ?> ODTs)
                    </span>
                </h3>

                <?php if (empty($odts)): ?>
                    <div class="card" style="padding: 30px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-check-circle" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                        Sin ODTs asignadas para
                        <?php echo strtolower($label); ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($odts as $odt):
                        $ec = $estadoColors[$odt['estado_gestion']] ?? ['bg' => '#eee', 'color' => '#666'];
                        $esUrgente = PriorityUtil::esUrgente($odt);
                        ?>
                        <div class="card crew-odt-card <?php echo $esUrgente ? 'urgente' : ''; ?>"
                            style="margin-bottom: 10px; padding: 15px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div style="font-weight: 700; font-size: 1.05em; color: var(--text-primary); margin-bottom: 4px;">
                                        <?php echo $esUrgente ? '🔴 ' : ''; ?>
                                        <?php echo htmlspecialchars($odt['nro_odt_assa']); ?>
                                        <span style="font-size: 0.8em; color: var(--text-muted); margin-left: 8px;">
                                            #
                                            <?php echo $odt['orden'] ?: '-'; ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.9em; color: var(--text-secondary); margin-bottom: 4px;">
                                        📍
                                        <?php echo htmlspecialchars($odt['direccion'] ?: 'Sin dirección'); ?>
                                    </div>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap; font-size: 0.8em; color: var(--text-muted);">
                                        <?php if ($odt['tipo_trabajo']): ?>
                                            <span><i class="fas fa-tools"></i>
                                                <?php echo htmlspecialchars($odt['tipo_trabajo']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <span style="background: <?php echo $ec['bg']; ?>; color: <?php echo $ec['color']; ?>;
                            padding: 5px 12px; border-radius: 10px; font-size: 0.8em; font-weight: 600;">
                                        <?php echo $odt['estado_gestion']; ?>
                                    </span>

                                    <!-- Quick Actions -->
                                    <div style="display: flex; gap: 6px;">
                                        <?php if ($odt['estado_gestion'] === 'Asignado'): ?>
                                            <button onclick="quickAction(<?php echo $odt['id_odt']; ?>, 'En ejecución')" class="btn"
                                                style="min-height: 40px; padding: 0 12px; background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; font-size: 0.85em;">
                                                ▶ Iniciar
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($odt['estado_gestion'] === 'En ejecución'): ?>
                                            <button onclick="quickAction(<?php echo $odt['id_odt']; ?>, 'Ejecutado')" class="btn"
                                                style="min-height: 40px; padding: 0 12px; background: #f1f8e9; color: #33691e; border: 1px solid #dcedc8; font-size: 0.85em;">
                                                ✅ Finalizar
                                            </button>
                                            <button onclick="quickAction(<?php echo $odt['id_odt']; ?>, 'Reprogramar por visita fallida')"
                                                class="btn"
                                                style="min-height: 40px; padding: 0 12px; background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; font-size: 0.85em;">
                                                🔄 Postergar
                                            </button>
                                        <?php endif; ?>

                                        <a href="form.php?id=<?php echo $odt['id_odt']; ?>" class="btn btn-outline"
                                            style="min-height: 40px; padding: 0 12px; font-size: 0.85em;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <!-- ═══ VISTA GERENCIAL: Resumen de todas las cuadrillas ═══ -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">
            <?php foreach ($resumen['cuadrillas'] as $crew):
                $color = $crew['color_hex'] ?? '#2196F3';
                ?>
                <div class="card" style="padding: 0; overflow: hidden; border-top: 4px solid <?php echo $color; ?>;">
                    <div style="padding: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span
                                style="font-weight: 700; font-size: 1.05em; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                <span
                                    style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $color; ?>; display: inline-block;"></span>
                                <?php echo htmlspecialchars($crew['nombre_cuadrilla']); ?>
                            </span>
                            <span
                                style="font-size: 0.75em; color: var(--text-muted); background: var(--bg-secondary); padding: 3px 8px; border-radius: 6px;">
                                <?php echo htmlspecialchars($crew['tipo_especialidad'] ?? ''); ?>
                            </span>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 12px;">
                            <div
                                style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; background: rgba(76,175,80,0.1);">
                                <div style="font-weight: 700; font-size: 1.5em; color: #4CAF50;">
                                    <?php echo $crew['odts_hoy']; ?>
                                </div>
                                <div style="font-size: 0.75em; color: var(--text-muted);">Hoy</div>
                            </div>
                            <div
                                style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; background: rgba(33,150,243,0.1);">
                                <div style="font-weight: 700; font-size: 1.5em; color: #2196F3;">
                                    <?php echo $crew['odts_manana']; ?>
                                </div>
                                <div style="font-size: 0.75em; color: var(--text-muted);">Mañana</div>
                            </div>
                        </div>

                        <a href="index.php?cuadrilla=<?php echo $crew['id_cuadrilla']; ?>"
                            style="display: block; text-align: center; padding: 8px; border-radius: 6px; background: var(--bg-secondary);
                    color: var(--accent-primary); font-weight: 600; font-size: 0.85em; text-decoration: none; border: 1px solid var(--border-color);">
                            Ver ODTs de esta cuadrilla →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .crew-odt-card {
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .crew-odt-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .crew-odt-card.urgente {
        border-left: 4px solid #d32f2f !important;
    }
</style>

<script>
    const CSRF = '<?php echo $csrfToken; ?>';

    function quickAction(odtId, nuevoEstado) {
        if (!confirm(`¿Cambiar estado a "${nuevoEstado}"?`)) return;

        fetch('bulk_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'unified_update',
                ids: [odtId],
                estado: nuevoEstado,
                csrf: CSRF
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + (data.message || 'No se pudo cambiar el estado'));
            })
            .catch(err => {
                console.error(err);
                alert('Error de red');
            });
    }
</script>

<?php require_once '../../includes/footer.php'; ?>