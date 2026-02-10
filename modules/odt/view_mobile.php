<?php
/**
 * Vista Mobile para Jefe de Cuadrilla — Gestión de ODT
 * [!] ARCH: Mobile-first con info readonly + gestión de estado/fotos/avance
 * [✓] AUDIT: Separación UI (readonly) / Lógica (POST handlers)
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id_odt = $_GET['id'] ?? null;
if (!$id_odt) {
    header("Location: ../cuadrillas/index.php");
    exit;
}

// ─── POST HANDLERS ─────────────────────────────────────────
$msg = '';
$msgType = '';

// 1. Update Status & Description
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    try {
        $newStatus = $_POST['estado_gestion'];
        $avance = trim($_POST['avance'] ?? '');

        $sql = "UPDATE odt_maestro SET estado_gestion = ?, avance = ? WHERE id_odt = ?";
        $pdo->prepare($sql)->execute([$newStatus, $avance, $id_odt]);

        $msg = "Estado y descripción actualizados correctamente.";
        $msgType = "success";

        registrarAccion('EDITAR', 'odt_maestro', "Jefe actualizó estado a: $newStatus", $id_odt);
    } catch (PDOException $e) {
        $msg = "Error: " . $e->getMessage();
        $msgType = "danger";
    }
}

// 2. Upload Photos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photos') {
    $uploadDir = '../../uploads/odt_fotos/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    $tipoFoto = $_POST['tipo_foto'] ?? 'TRABAJO';
    $count = 0;

    if (!empty($_FILES['fotos']['name'][0])) {
        foreach ($_FILES['fotos']['name'] as $key => $name) {
            if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['fotos']['tmp_name'][$key];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $valid = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $valid)) {
                    $newName = 'odt_' . $id_odt . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                        $stmtFoto = $pdo->prepare("INSERT INTO odt_fotos (id_odt, ruta_archivo, tipo_foto) VALUES (?, ?, ?)");
                        $stmtFoto->execute([$id_odt, $newName, $tipoFoto]);
                        $count++;
                    }
                }
            }
        }
    }
    $msg = $count > 0 ? "Se subieron $count foto(s) correctamente." : "Error al subir fotos.";
    $msgType = $count > 0 ? "success" : "warning";
}

// ─── FETCH DATA ────────────────────────────────────────────

// ODT Data with JOINs
$stmt = $pdo->prepare("
    SELECT o.*, 
           t.nombre AS tipo_trabajo, t.codigo_trabajo, t.tiempo_limite_dias,
           c.nombre_cuadrilla, c.zona_asignada,
           v.patente, v.marca AS v_marca, v.modelo AS v_modelo
    FROM odt_maestro o 
    LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
    LEFT JOIN programacion_semanal p ON o.id_odt = p.id_odt
    LEFT JOIN cuadrillas c ON p.id_cuadrilla = c.id_cuadrilla
    LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id_vehiculo
    WHERE o.id_odt = ?
    ORDER BY p.id_programacion DESC
    LIMIT 1
");
$stmt->execute([$id_odt]);
$odt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$odt)
    die("ODT no encontrada");

// Photos
$fotos = $pdo->prepare("SELECT * FROM odt_fotos WHERE id_odt = ? ORDER BY tipo_foto, fecha_subida DESC");
$fotos->execute([$id_odt]);
$galeria = $fotos->fetchAll(PDO::FETCH_ASSOC);

// Group photos by type
$fotosPorTipo = [];
foreach ($galeria as $f) {
    $tipo = $f['tipo_foto'] ?? 'TRABAJO';
    $fotosPorTipo[$tipo][] = $f;
}

// Calculate deadline info
$vencimiento = $odt['fecha_vencimiento'] ?? null;
$diasRestantes = null;
$classVenc = 'neutral';
$textVenc = 'Sin plazo';

if ($vencimiento) {
    $hoy = new DateTime();
    $fechaVenc = new DateTime($vencimiento);
    $diff = $hoy->diff($fechaVenc);
    $diasRestantes = $diff->invert ? -$diff->days : $diff->days;

    if ($diasRestantes < 0) {
        $classVenc = 'vencido';
        $textVenc = 'Vencido hace ' . abs($diasRestantes) . ' día(s)';
    } elseif ($diasRestantes <= 2) {
        $classVenc = 'urgente';
        $textVenc = $diasRestantes == 0 ? '¡Vence HOY!' : "Vence en $diasRestantes día(s)";
    } elseif ($diasRestantes <= 5) {
        $classVenc = 'proximo';
        $textVenc = "Vence en $diasRestantes días";
    } else {
        $classVenc = 'ok';
        $textVenc = "Vence en $diasRestantes días";
    }
}

// Status options (Jefe can set)
$estadosJefe = ['Programado', 'Ejecución', 'Ejecutado', 'Retrabajo', 'Postergado'];

// Status colors
$statusColors = [
    'Sin Programar' => '#6c757d',
    'Programación Solicitada' => '#fd7e14',
    'Programado' => '#7b1fa2',
    'Ejecución' => '#2e7d32',
    'Ejecutado' => '#1565c0',
    'Precertificada' => '#00838f',
    'Finalizado' => '#37474f',
    'Aprobado por inspector' => '#1b5e20',
    'Retrabajo' => '#e65100',
    'Postergado' => '#f9a825',
];
$currentStatusColor = $statusColors[$odt['estado_gestion']] ?? '#0073A8';
?>

<div class="mobile-container">

    <!-- ═══ HEADER ═══ -->
    <div class="card header-card"
        style="background: linear-gradient(135deg, <?php echo $currentStatusColor; ?>, <?php echo $currentStatusColor; ?>cc);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="odt-number">ODT #<?php echo htmlspecialchars($odt['nro_odt_assa']); ?></div>
                <div class="badge-type">
                    <?php if (!empty($odt['codigo_trabajo'])): ?>
                        [<?php echo $odt['codigo_trabajo']; ?>]
                    <?php endif; ?>
                    <?php echo htmlspecialchars($odt['tipo_trabajo'] ?? 'Sin tipo'); ?>
                </div>
                <div class="badge-estado"><?php echo $odt['estado_gestion']; ?></div>
            </div>
            <a href="../cuadrillas/index.php" class="btn-close-mobile" title="Volver">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <div
            style="margin-top:12px; color:rgba(255,255,255,0.9); font-size:0.95em; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-map-marker-alt"></i>
            <?php echo htmlspecialchars($odt['direccion']); ?>
        </div>
    </div>

    <!-- ═══ ALERTS ═══ -->
    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msgType; ?>">
            <i
                class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : ($msgType === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- ═══ 1. INFORMACIÓN DEL TRABAJO (READONLY) ═══ -->
    <div class="card p-15">
        <h5 class="section-title"><i class="fas fa-clipboard-list"></i> Información del Trabajo</h5>

        <div class="info-grid-mobile">
            <div class="info-item">
                <div class="info-label"><i class="fas fa-hashtag"></i> Nro ODT</div>
                <div class="info-value"><?php echo htmlspecialchars($odt['nro_odt_assa']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-flag"></i> Prioridad</div>
                <div class="info-value">
                    <?php if ($odt['prioridad'] === 'Urgente'): ?>
                        <span class="badge-prioridad urgente">🔴 Urgente</span>
                    <?php else: ?>
                        <span class="badge-prioridad normal">🟢 Normal</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                <div class="info-value"><?php echo htmlspecialchars($odt['direccion']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-briefcase"></i> Tipo de Trabajo</div>
                <div class="info-value">
                    <?php if (!empty($odt['codigo_trabajo'])): ?>
                        <strong>[<?php echo $odt['codigo_trabajo']; ?>]</strong>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($odt['tipo_trabajo'] ?? 'Sin tipo'); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-users"></i> Cuadrilla</div>
                <div class="info-value"><?php echo htmlspecialchars($odt['nombre_cuadrilla'] ?? 'Sin asignar'); ?></div>
            </div>
            <?php if (!empty($odt['patente'])): ?>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-truck"></i> Vehículo</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($odt['patente'] . ' - ' . $odt['v_marca'] . ' ' . $odt['v_modelo']); ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($odt['inspector'])): ?>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user-tie"></i> Inspector</div>
                    <div class="info-value"><?php echo htmlspecialchars($odt['inspector']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Deadline bar -->
        <div class="deadline-bar <?php echo $classVenc; ?>">
            <div class="deadline-icon">
                <?php
                $deadlineIcon = match ($classVenc) {
                    'vencido' => '⏰',
                    'urgente' => '⚠️',
                    'proximo' => '🕐',
                    'ok' => '✅',
                    default => '📅'
                };
                echo $deadlineIcon;
                ?>
            </div>
            <div class="deadline-text">
                <strong><?php echo $textVenc; ?></strong>
                <?php if ($vencimiento): ?>
                    <small>Vence: <?php echo (new DateTime($vencimiento))->format('d/m/Y'); ?></small>
                <?php endif; ?>
            </div>
            <?php if (!empty($odt['fecha_inicio_plazo'])): ?>
                <div class="deadline-start">
                    Inicio: <?php echo (new DateTime($odt['fecha_inicio_plazo']))->format('d/m/Y'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ 2. GESTIÓN RÁPIDA ═══ -->
    <div class="card p-15">
        <h5 class="section-title"><i class="fas fa-bolt"></i> Gestión</h5>

        <form method="POST" id="gestionForm">
            <input type="hidden" name="action" value="update_info">

            <!-- Quick Status Buttons -->
            <div class="quick-status-label">Cambiar Estado:</div>
            <div class="quick-status-grid">
                <?php
                $quickActions = [];
                $estadoActual = $odt['estado_gestion'];

                if (in_array($estadoActual, ['Programado', 'Postergado', 'Retrabajo'])) {
                    $quickActions[] = ['estado' => 'Ejecución', 'icon' => 'fa-play', 'color' => '#2e7d32', 'label' => 'Iniciar'];
                }
                if ($estadoActual === 'Ejecución') {
                    $quickActions[] = ['estado' => 'Ejecutado', 'icon' => 'fa-check', 'color' => '#1565c0', 'label' => 'Finalizar'];
                    $quickActions[] = ['estado' => 'Postergado', 'icon' => 'fa-clock', 'color' => '#f9a825', 'label' => 'Postergar'];
                }
                if ($estadoActual === 'Ejecutado') {
                    $quickActions[] = ['estado' => 'Retrabajo', 'icon' => 'fa-redo', 'color' => '#e65100', 'label' => 'Retrabajo'];
                }
                ?>

                <?php foreach ($quickActions as $qa): ?>
                    <button type="button" class="btn-quick-action" onclick="setQuickStatus('<?php echo $qa['estado']; ?>')"
                        style="--action-color: <?php echo $qa['color']; ?>;">
                        <i class="fas <?php echo $qa['icon']; ?>"></i>
                        <span><?php echo $qa['label']; ?></span>
                    </button>
                <?php endforeach; ?>

                <?php if (empty($quickActions)): ?>
                    <div class="no-actions-msg">
                        <i class="fas fa-info-circle"></i> Sin acciones rápidas para el estado actual
                    </div>
                <?php endif; ?>
            </div>

            <!-- Status Select (fallback) -->
            <div class="form-group" style="margin-top: 15px;">
                <label class="field-label">O seleccionar manualmente:</label>
                <select name="estado_gestion" id="statusSelect" class="form-control status-select">
                    <?php foreach ($estadosJefe as $e): ?>
                        <option value="<?php echo $e; ?>" <?php echo $odt['estado_gestion'] == $e ? 'selected' : ''; ?>>
                            <?php echo $e; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="field-label"><i class="fas fa-align-left"></i> Descripción del Avance</label>
                <textarea name="avance" class="form-control" rows="4"
                    placeholder="Describa el trabajo realizado, observaciones, materiales usados..."><?php echo htmlspecialchars($odt['avance'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-save-main">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </form>
    </div>

    <!-- ═══ 3. FOTOS ═══ -->
    <div class="card p-15">
        <h5 class="section-title"><i class="fas fa-camera"></i> Registro Fotográfico</h5>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" id="photoForm">
            <input type="hidden" name="action" value="upload_photos">

            <div class="photo-type-selector">
                <label class="photo-type-option">
                    <input type="radio" name="tipo_foto" value="TRABAJO" checked>
                    <span><i class="fas fa-hard-hat"></i> Trabajo</span>
                </label>
                <label class="photo-type-option">
                    <input type="radio" name="tipo_foto" value="ODT">
                    <span><i class="fas fa-file-alt"></i> ODT</span>
                </label>
                <label class="photo-type-option">
                    <input type="radio" name="tipo_foto" value="EXTRA">
                    <span><i class="fas fa-plus-circle"></i> Extra</span>
                </label>
            </div>

            <input type="file" name="fotos[]" id="fotoInput" multiple accept="image/*" style="display:none;">

            <div class="upload-actions">
                <button type="button" class="btn-upload" onclick="openCameraUpload()">
                    <i class="fas fa-camera"></i> Cámara
                </button>
                <button type="button" class="btn-upload" onclick="openGalleryUpload()">
                    <i class="fas fa-images"></i> Galería
                </button>
            </div>

            <!-- Preview before submit -->
            <div id="photoPreview" class="photo-preview-grid" style="display:none;"></div>
            <button type="submit" id="btnSubmitPhotos" class="btn-submit-photos" style="display:none;">
                <i class="fas fa-cloud-upload-alt"></i> Subir <span id="photoCount">0</span> foto(s)
            </button>
        </form>

        <!-- Existing Gallery -->
        <?php if (!empty($galeria)): ?>
            <div class="gallery-section">
                <?php
                $tipoLabels = [
                    'ODT' => ['icon' => 'fa-file-alt', 'label' => 'Foto de la ODT'],
                    'TRABAJO' => ['icon' => 'fa-hard-hat', 'label' => 'Fotos del Trabajo'],
                    'EXTRA' => ['icon' => 'fa-plus-circle', 'label' => 'Fotos Extra'],
                ];
                foreach ($tipoLabels as $tipo => $meta):
                    if (empty($fotosPorTipo[$tipo]))
                        continue;
                    ?>
                    <div class="gallery-type-group">
                        <div class="gallery-type-label">
                            <i class="fas <?php echo $meta['icon']; ?>"></i>
                            <?php echo $meta['label']; ?> (<?php echo count($fotosPorTipo[$tipo]); ?>)
                        </div>
                        <div class="gallery-grid">
                            <?php foreach ($fotosPorTipo[$tipo] as $f): ?>
                                <div class="gallery-item" id="galleryItem_<?php echo $f['id_foto']; ?>">
                                    <a href="../../uploads/odt_fotos/<?php echo $f['ruta_archivo']; ?>" target="_blank">
                                        <img src="../../uploads/odt_fotos/<?php echo $f['ruta_archivo']; ?>" loading="lazy"
                                            alt="Foto">
                                    </a>
                                    <button type="button" class="btn-delete-photo"
                                        onclick="deletePhoto(<?php echo $f['id_foto']; ?>)" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <div class="caption">
                                        <?php echo date('d/m H:i', strtotime($f['fecha_subida'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-gallery">
                <i class="fas fa-image" style="font-size: 2em; opacity: 0.3;"></i>
                <p>Sin fotos registradas</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ═══════════════════════════════════════════ -->
<!-- STYLES -->
<!-- ═══════════════════════════════════════════ -->
<style>
    .mobile-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 5px;
    }

    .card {
        background: var(--bg-card);
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        margin-bottom: 15px;
        overflow: hidden;
    }

    .p-15 {
        padding: 18px;
    }

    /* Header */
    .header-card {
        padding: 22px;
        color: white;
        position: relative;
    }

    .odt-number {
        font-size: 1.5em;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .badge-type {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(4px);
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.82em;
        display: inline-block;
        margin-top: 6px;
        font-weight: 500;
    }

    .badge-estado {
        background: rgba(255, 255, 255, 0.25);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.78em;
        display: inline-block;
        margin-top: 6px;
        margin-left: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-close-mobile {
        color: white;
        font-size: 1.3em;
        text-decoration: none;
        opacity: 0.85;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        transition: all 0.2s;
    }

    .btn-close-mobile:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.3);
    }

    /* Section titles */
    .section-title {
        margin: 0 0 15px 0;
        font-size: 1.05em;
        font-weight: 700;
        color: var(--text-primary);
        border-bottom: 2px solid var(--bg-secondary);
        padding-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Info Grid */
    .info-grid-mobile {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 15px;
    }

    .info-item {
        background: var(--bg-tertiary);
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid var(--bg-secondary);
    }

    .info-label {
        font-size: 0.72em;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .info-value {
        font-size: 0.92em;
        font-weight: 600;
        color: var(--text-primary);
        word-break: break-word;
    }

    .badge-prioridad {
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.85em;
        font-weight: 600;
    }

    .badge-prioridad.urgente {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .badge-prioridad.normal {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    /* Deadline bar */
    .deadline-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        margin-top: 5px;
    }

    .deadline-bar.vencido {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .deadline-bar.urgente {
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .deadline-bar.proximo {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .deadline-bar.ok {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .deadline-bar.neutral {
        background: var(--bg-tertiary);
        border: 1px solid var(--bg-secondary);
    }

    .deadline-icon {
        font-size: 1.3em;
    }

    .deadline-text {
        flex: 1;
        color: var(--text-primary);
        font-size: 0.9em;
    }

    .deadline-text small {
        display: block;
        color: var(--text-muted);
        font-size: 0.85em;
    }

    .deadline-start {
        font-size: 0.78em;
        color: var(--text-muted);
        white-space: nowrap;
    }

    /* Alerts */
    .alert {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 12px;
        font-size: 0.9em;
        display: flex;
        align-items: center;
        gap: 8px;
        animation: slideIn 0.3s ease;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .alert-warning {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    @keyframes slideIn {
        from {
            transform: translateY(-8px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Quick Status */
    .quick-status-label {
        font-size: 0.82em;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 10px;
    }

    .quick-status-grid {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .btn-quick-action {
        flex: 1;
        min-width: 100px;
        min-height: 54px;
        padding: 12px 16px;
        background: color-mix(in srgb, var(--action-color) 12%, transparent);
        color: var(--action-color);
        border: 2px solid var(--action-color);
        border-radius: 12px;
        cursor: pointer;
        font-size: 0.95em;
        font-weight: 700;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
    }

    .btn-quick-action:hover,
    .btn-quick-action:active {
        background: var(--action-color);
        color: white;
        transform: scale(1.02);
    }

    .btn-quick-action i {
        font-size: 1.2em;
    }

    .no-actions-msg {
        color: var(--text-muted);
        padding: 12px;
        text-align: center;
        font-size: 0.88em;
        width: 100%;
    }

    /* Form Controls */
    .field-label {
        display: block;
        font-size: 0.82em;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-control {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--bg-secondary);
        border-radius: 10px;
        background: var(--bg-tertiary);
        color: var(--text-primary);
        font-size: 1em;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: var(--accent-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 80px;
        line-height: 1.5;
    }

    .btn-save-main {
        width: 100%;
        min-height: 52px;
        padding: 14px;
        font-size: 1em;
        font-weight: 700;
        background: var(--accent-primary);
        color: white;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-save-main:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .btn-save-main:active {
        transform: scale(0.98);
    }

    /* Photo Type Selector */
    .photo-type-selector {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }

    .photo-type-option {
        flex: 1;
        cursor: pointer;
    }

    .photo-type-option input {
        display: none;
    }

    .photo-type-option span {
        display: block;
        text-align: center;
        padding: 8px 6px;
        border: 2px solid var(--bg-secondary);
        border-radius: 10px;
        font-size: 0.82em;
        font-weight: 600;
        color: var(--text-muted);
        transition: all 0.2s;
    }

    .photo-type-option input:checked+span {
        border-color: var(--accent-primary);
        color: var(--accent-primary);
        background: rgba(59, 130, 246, 0.1);
    }

    /* Upload buttons */
    .upload-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 12px;
    }

    .btn-upload {
        flex: 1;
        min-height: 50px;
        padding: 12px;
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 2px dashed var(--bg-secondary);
        border-radius: 12px;
        cursor: pointer;
        font-size: 0.95em;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-upload:hover,
    .btn-upload:active {
        border-color: var(--accent-primary);
        color: var(--accent-primary);
        background: rgba(59, 130, 246, 0.05);
    }

    .btn-upload i {
        font-size: 1.2em;
    }

    /* Photo Preview */
    .photo-preview-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 12px;
    }

    .preview-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
    }

    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-item .remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.7em;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-submit-photos {
        width: 100%;
        padding: 12px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 0.95em;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 15px;
        transition: all 0.2s;
    }

    .btn-submit-photos:hover {
        background: #059669;
    }

    /* Gallery */
    .gallery-section {
        margin-top: 15px;
        border-top: 1px solid var(--bg-secondary);
        padding-top: 15px;
    }

    .gallery-type-group {
        margin-bottom: 15px;
    }

    .gallery-type-label {
        font-size: 0.8em;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }

    .gallery-item {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        border-radius: 8px;
        background: var(--bg-tertiary);
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s;
    }

    .gallery-item:hover img {
        transform: scale(1.05);
    }

    .gallery-item .caption {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
        color: white;
        font-size: 0.68em;
        padding: 10px 4px 3px;
        text-align: center;
    }

    .btn-delete-photo {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 26px;
        height: 26px;
        background: rgba(239, 68, 68, 0.85);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.7em;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .gallery-item:hover .btn-delete-photo {
        opacity: 1;
    }

    .empty-gallery {
        text-align: center;
        padding: 25px;
        color: var(--text-muted);
        font-size: 0.9em;
    }

    /* Mobile touch */
    @media (max-width: 480px) {
        .info-grid-mobile {
            grid-template-columns: 1fr;
        }

        .gallery-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .btn-delete-photo {
            opacity: 1;
        }
    }
</style>

<!-- ═══════════════════════════════════════════ -->
<!-- SCRIPTS -->
<!-- ═══════════════════════════════════════════ -->
<script>
    // ── Quick Status Actions ──
    function setQuickStatus(estado) {
        const select = document.getElementById('statusSelect');
        select.value = estado;

        // Highlight the changed state
        select.style.borderColor = 'var(--accent-primary)';
        select.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';

        // Visual feedback
        setTimeout(() => {
            select.style.borderColor = '';
            select.style.boxShadow = '';
        }, 1500);

        // Optional: auto-submit
        if (confirm(`¿Cambiar estado a "${estado}"?`)) {
            document.getElementById('gestionForm').submit();
        }
    }

    // ── Photo Upload ──
    const fotoInput = document.getElementById('fotoInput');
    const photoPreview = document.getElementById('photoPreview');
    const btnSubmit = document.getElementById('btnSubmitPhotos');
    const photoCount = document.getElementById('photoCount');

    function openCameraUpload() {
        fotoInput.setAttribute('capture', 'environment');
        fotoInput.click();
    }

    function openGalleryUpload() {
        fotoInput.removeAttribute('capture');
        fotoInput.click();
    }

    fotoInput.addEventListener('change', function () {
        if (!this.files || this.files.length === 0) return;

        photoPreview.style.display = 'grid';
        btnSubmit.style.display = 'flex';
        photoPreview.innerHTML = '';

        let count = 0;
        Array.from(this.files).forEach((file, idx) => {
            count++;
            const reader = new FileReader();
            reader.onload = function (ev) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `<img src="${ev.target.result}" alt="Preview">`;
                photoPreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });

        photoCount.textContent = count;
    });

    // ── Delete Existing Photo ──
    function deletePhoto(photoId) {
        if (!confirm('¿Eliminar esta foto?')) return;

        fetch('delete_photo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_foto=' + photoId
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById('galleryItem_' + photoId);
                    if (el) {
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.8)';
                        el.style.transition = 'all 0.3s ease';
                        setTimeout(() => el.remove(), 300);
                    }
                } else {
                    alert('Error: ' + (data.error || 'No se pudo eliminar'));
                }
            })
            .catch(() => alert('Error de conexión'));
    }
</script>

<?php require_once '../../includes/footer.php'; ?>