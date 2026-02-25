<?php
/**
 * [!] ARCH: Importador Masivo de ODTs desde Excel
 * [✓] AUDIT: Validación por fila, detección de duplicados, Error IDs, Error Stack
 * [→] EDITAR: Mapeo de columnas adicionales si es necesario
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

// ─── CONFIG ──────────────────────────────────────────────
$MAX_FILE_SIZE_MB = 10;
$ESTADO_DEFAULT = 'Sin Programar';
$PRIORIDAD_DEFAULT = 'Normal';

// ─── RESULT CONTAINERS ──────────────────────────────────
$importResult = null;
$successRows = [];
$errorRows = [];
$totalProcessed = 0;
$errorStack = null;

// ─── PROCESS UPLOAD ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivoexcel'])) {
    $errorId = 'IMPORT-' . strtoupper(bin2hex(random_bytes(4)));

    try {
        // 1. Validate file upload
        $file = $_FILES['archivoexcel'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Error en la subida del archivo (código: {$file['error']})", 1001);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            throw new \RuntimeException("Formato no soportado: .$ext. Solo se aceptan .xlsx y .xls", 1002);
        }

        $sizeMB = $file['size'] / (1024 * 1024);
        if ($sizeMB > $MAX_FILE_SIZE_MB) {
            throw new \RuntimeException("Archivo demasiado grande (" . number_format($sizeMB, 1) . " MB). Máximo: {$MAX_FILE_SIZE_MB} MB", 1003);
        }

        // 2. Load PhpSpreadsheet
        require_once '../../vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $errorStack = [
                'id' => 'IMPORT-ERR-001',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            throw new \RuntimeException("Error al procesar el archivo Excel. Verifique el formato.", 1010);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        if ($highestRow < 2) {
            throw new \RuntimeException("El archivo está vacío o solo contiene encabezados.", 1004);
        }

        // 3. Auto-detect column mapping from headers (Row 1)
        $headerRow = [];
        $highestCol = $sheet->getHighestColumn();
        $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($col = 1; $col <= $highestColIdx; $col++) {
            $val = trim((string) $sheet->getCell([$col, 1])->getValue());
            $headerRow[$col] = $val;
        }

        // Map columns by header name (case-insensitive, partial match)
        $colOrden = null;
        $colDireccion = null;

        foreach ($headerRow as $colIdx => $headerName) {
            $lower = mb_strtolower($headerName, 'UTF-8');
            if ($colOrden === null && (str_contains($lower, 'nro') && str_contains($lower, 'req') || str_contains($lower, 'requerimiento') || str_contains($lower, 'n°') || str_contains($lower, 'nº'))) {
                $colOrden = $colIdx;
            }
            if ($colDireccion === null && (str_contains($lower, 'direcci') || str_contains($lower, 'direccion') || str_contains($lower, 'domicilio'))) {
                $colDireccion = $colIdx;
            }
        }

        if ($colOrden === null) {
            throw new \RuntimeException("No se encontró la columna 'Nro.Req' en los encabezados del Excel. Columnas encontradas: " . implode(', ', $headerRow), 1005);
        }

        // 4. Begin transactional import
        $pdo->beginTransaction();

        // Pre-fetch existing ODTs for duplicate detection
        $existingOdts = [];
        $stmtExist = $pdo->query("SELECT nro_odt_assa FROM odt_maestro");
        while ($row = $stmtExist->fetch(PDO::FETCH_COLUMN)) {
            $existingOdts[trim((string) $row)] = true;
        }

        $stmtInsert = $pdo->prepare(
            "INSERT INTO odt_maestro (nro_odt_assa, direccion, prioridad, estado_gestion) VALUES (?, ?, ?, ?)"
        );

        // 5. Iterate rows (skip header)
        for ($row = 2; $row <= $highestRow; $row++) {
            $totalProcessed++;

            $nroOdt = trim((string) $sheet->getCell([$colOrden, $row])->getValue());
            $direccion = $colDireccion !== null
                ? trim((string) $sheet->getCell([$colDireccion, $row])->getValue())
                : '';

            // Validation: ODT number is mandatory
            if (empty($nroOdt)) {
                $errorRows[] = [
                    'fila' => $row,
                    'error' => "El campo 'Número de ODT' está vacío.",
                    'datos' => "Dirección: " . ($direccion ?: '(vacía)')
                ];
                continue;
            }

            // Validation: Duplicate check
            if (isset($existingOdts[$nroOdt])) {
                $errorRows[] = [
                    'fila' => $row,
                    'error' => "ODT '$nroOdt' ya existe en el sistema.",
                    'datos' => "Dirección: $direccion"
                ];
                continue;
            }

            // Insert
            try {
                $stmtInsert->execute([
                    $nroOdt,
                    $direccion,
                    $PRIORIDAD_DEFAULT,
                    $ESTADO_DEFAULT
                ]);

                $existingOdts[$nroOdt] = true; // Prevent intra-file duplicates
                $successRows[] = [
                    'fila' => $row,
                    'nro_odt' => $nroOdt,
                    'direccion' => $direccion
                ];
            } catch (\PDOException $e) {
                $errorRows[] = [
                    'fila' => $row,
                    'error' => "DB-CONN-ERR-002: " . $e->getMessage(),
                    'datos' => "ODT: $nroOdt"
                ];
            }
        }

        $pdo->commit();
        $importResult = 'success';

    } catch (\RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $importResult = 'error';
        if (!$errorStack) {
            $errorStack = [
                'id' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $importResult = 'error';
        $errorStack = [
            'id' => $errorId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
}

// ─── Count for the header ────────────────────────────────
$totalOdts = (int) $pdo->query("SELECT COUNT(*) FROM odt_maestro")->fetchColumn();
?>
<?php require_once '../../includes/header.php'; ?>

<!-- PWA -->
<link rel="manifest" href="pwa/manifest.json">
<meta name="theme-color" content="#0d1b2a">

<style>
    /* ─── IMPORT MODULE STYLES ────────────────────────────── */
    .import-container {
        max-width: 800px;
        margin: 0 auto;
        padding: var(--spacing-md);
    }

    .import-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-lg);
        flex-wrap: wrap;
        gap: var(--spacing-sm);
    }

    .import-header h2 {
        margin: 0;
        font-size: 1.4em;
        color: var(--text-primary);
    }

    .import-header p {
        margin: 4px 0 0;
        color: var(--text-secondary);
        font-size: 0.9em;
    }

    /* ─── DROP ZONE ──────────────────────────────────────── */
    .drop-zone {
        border: 2px dashed var(--accent-primary);
        border-radius: var(--border-radius-md);
        background: var(--bg-card);
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .drop-zone:hover,
    .drop-zone.dragover {
        border-color: var(--color-success);
        background: rgba(16, 185, 129, 0.05);
        box-shadow: var(--glow-primary);
        transform: translateY(-2px);
    }

    .drop-zone .icon-upload {
        font-size: 3em;
        color: var(--accent-primary);
        margin-bottom: var(--spacing-md);
        display: block;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 0.7;
            transform: scale(1);
        }

        50% {
            opacity: 1;
            transform: scale(1.05);
        }
    }

    .drop-zone .label-main {
        font-size: 1.1em;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .drop-zone .label-hint {
        font-size: 0.85em;
        color: var(--text-muted);
    }

    .drop-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .file-selected {
        display: none;
        align-items: center;
        gap: var(--spacing-sm);
        margin-top: var(--spacing-md);
        padding: 10px 15px;
        border-radius: var(--border-radius-sm);
        background: rgba(100, 181, 246, 0.1);
        color: var(--text-primary);
        font-weight: 500;
    }

    .file-selected.visible {
        display: flex;
    }

    .file-selected .file-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ─── RESULTS SECTION ────────────────────────────────── */
    .result-card {
        border-radius: var(--border-radius-md);
        background: var(--bg-card);
        padding: var(--spacing-lg);
        margin-top: var(--spacing-lg);
        box-shadow: var(--shadow-md);
    }

    .result-summary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }

    .stat-box {
        padding: var(--spacing-md);
        border-radius: var(--border-radius-sm);
        text-align: center;
    }

    .stat-box.success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .stat-box.error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .stat-box .stat-num {
        font-size: 2em;
        font-weight: 700;
        line-height: 1;
    }

    .stat-box.success .stat-num {
        color: var(--color-success);
    }

    .stat-box.error .stat-num {
        color: var(--color-danger);
    }

    .stat-box .stat-label {
        font-size: 0.85em;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    /* ─── ERROR TABLE ────────────────────────────────────── */
    .error-list-title {
        font-size: 1em;
        font-weight: 600;
        color: var(--color-danger);
        margin-bottom: var(--spacing-sm);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .error-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85em;
    }

    .error-table th {
        text-align: left;
        padding: 8px 12px;
        background: rgba(239, 68, 68, 0.08);
        color: var(--text-secondary);
        font-weight: 600;
        border-bottom: 1px solid rgba(239, 68, 68, 0.2);
    }

    .error-table td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.06));
        color: var(--text-primary);
    }

    .error-table tr:hover td {
        background: rgba(239, 68, 68, 0.04);
    }

    /* ─── ERROR STACK ────────────────────────────────────── */
    .error-stack {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: var(--border-radius-sm);
        padding: var(--spacing-md);
        font-family: 'Courier New', monospace;
        font-size: 0.82em;
        color: var(--color-danger);
        white-space: pre-wrap;
        word-break: break-all;
        position: relative;
    }

    .error-stack .copy-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: var(--color-danger);
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85em;
    }

    /* ─── SUCCESS TABLE ──────────────────────────────────── */
    .success-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85em;
        margin-top: var(--spacing-sm);
    }

    .success-table th {
        text-align: left;
        padding: 8px 12px;
        background: rgba(16, 185, 129, 0.08);
        color: var(--text-secondary);
        font-weight: 600;
        border-bottom: 1px solid rgba(16, 185, 129, 0.2);
    }

    .success-table td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.06));
        color: var(--text-primary);
    }

    /* ─── ACTION BAR ─────────────────────────────────────── */
    .action-bar {
        display: flex;
        gap: var(--spacing-sm);
        margin-top: var(--spacing-lg);
        flex-wrap: wrap;
    }

    /* ─── LOADING OVERLAY ────────────────────────────────── */
    .loading-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 4px solid rgba(100, 181, 246, 0.2);
        border-top-color: var(--accent-primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .loading-text {
        color: #fff;
        font-size: 1.1em;
        font-weight: 600;
    }

    /* ─── RESPONSIVE ─────────────────────────────────────── */
    @media (max-width: 600px) {
        .import-container {
            padding: var(--spacing-sm);
        }

        .drop-zone {
            padding: 25px 15px;
        }

        .drop-zone .icon-upload {
            font-size: 2.2em;
        }

        .result-summary {
            grid-template-columns: 1fr;
        }

        .error-table,
        .success-table {
            font-size: 0.78em;
        }

        .error-table th,
        .error-table td,
        .success-table th,
        .success-table td {
            padding: 6px 8px;
        }
    }
</style>

<!-- LOADING OVERLAY -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Procesando Excel…</div>
</div>

<div class="import-container">

    <!-- HEADER -->
    <div class="import-header">
        <div>
            <h2><i class="fas fa-file-excel" style="color: var(--color-success);"></i> Importar ODTs desde Excel</h2>
            <p>Suba un archivo .xlsx o .xls para registrar ODTs masivamente. Actualmente hay
                <strong><?= number_format($totalOdts) ?></strong> ODTs registradas.
            </p>
        </div>
        <a href="index.php" class="btn btn-outline"
            style="min-height: 40px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($importResult === null): ?>
        <!-- ═══ UPLOAD FORM ═══ -->
        <form method="POST" enctype="multipart/form-data" id="importForm">
            <div class="drop-zone" id="dropZone">
                <input type="file" name="archivoexcel" id="fileInput" accept=".xlsx,.xls" required>
                <i class="fas fa-cloud-upload-alt icon-upload"></i>
                <div class="label-main">Arrastre su archivo Excel aquí</div>
                <div class="label-hint">o haga clic para seleccionar — .xlsx / .xls (máx. <?= $MAX_FILE_SIZE_MB ?> MB)</div>
            </div>

            <div class="file-selected" id="fileInfo">
                <i class="fas fa-file-excel" style="color: var(--color-success); font-size: 1.3em;"></i>
                <span class="file-name" id="fileName"></span>
                <span id="fileSize" style="color: var(--text-muted); font-size: 0.85em;"></span>
            </div>

            <div class="action-bar">
                <button type="submit" class="btn btn-primary" id="btnImport" disabled
                    style="min-height: 48px; flex: 1; font-weight: 600; font-size: 1em; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-upload"></i> Importar ODTs
                </button>
            </div>

            <!-- MAPPING HINT -->
            <div
                style="margin-top: var(--spacing-md); padding: 12px 16px; border-radius: var(--border-radius-sm); background: rgba(100, 181, 246, 0.08); border: 1px solid rgba(100, 181, 246, 0.2);">
                <div style="font-weight: 600; color: var(--accent-primary); margin-bottom: 6px;">
                    <i class="fas fa-info-circle"></i> Mapeo de Columnas
                </div>
                <div style="font-size: 0.85em; color: var(--text-secondary); line-height: 1.6;">
                    <strong>"Nro.Req"</strong> → Nº ODT (obligatorio)<br>
                    <strong>"Dirección"</strong> → Dirección de la ODT<br>
                    <em>La primera fila del Excel se interpreta como encabezados.</em>
                </div>
            </div>
        </form>

    <?php elseif ($importResult === 'success'): ?>
        <!-- ═══ RESULTS ═══ -->
        <div class="result-card">
            <div class="result-summary">
                <div class="stat-box success">
                    <div class="stat-num"><?= count($successRows) ?></div>
                    <div class="stat-label">ODTs Importadas</div>
                </div>
                <div class="stat-box error">
                    <div class="stat-num"><?= count($errorRows) ?></div>
                    <div class="stat-label">Filas con Errores</div>
                </div>
            </div>

            <?php if (!empty($errorRows)): ?>
                <div class="error-list-title">
                    <i class="fas fa-exclamation-triangle"></i> Detalle de Errores
                </div>
                <div style="overflow-x: auto;">
                    <table class="error-table">
                        <thead>
                            <tr>
                                <th>Fila</th>
                                <th>Error</th>
                                <th>Datos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($errorRows as $err): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--color-danger);"><?= $err['fila'] ?></td>
                                    <td><?= htmlspecialchars($err['error']) ?></td>
                                    <td style="color: var(--text-muted); font-size: 0.9em;"><?= htmlspecialchars($err['datos']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($successRows)): ?>
                <details style="margin-top: var(--spacing-lg);">
                    <summary style="cursor: pointer; font-weight: 600; color: var(--color-success); padding: 8px 0;">
                        <i class="fas fa-check-circle"></i> Ver <?= count($successRows) ?> ODTs Importadas
                    </summary>
                    <div style="overflow-x: auto; margin-top: var(--spacing-sm);">
                        <table class="success-table">
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th>Nro. ODT</th>
                                    <th>Dirección</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($successRows as $s): ?>
                                    <tr>
                                        <td><?= $s['fila'] ?></td>
                                        <td style="font-weight: 600;"><?= htmlspecialchars($s['nro_odt']) ?></td>
                                        <td><?= htmlspecialchars($s['direccion'] ?: '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>

            <div class="action-bar">
                <a href="importar_odt.php" class="btn btn-primary"
                    style="min-height: 45px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-redo"></i> Importar Otro Archivo
                </a>
                <a href="index.php" class="btn btn-outline"
                    style="min-height: 45px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-list"></i> Ver Listado ODTs
                </a>
            </div>
        </div>

    <?php elseif ($importResult === 'error'): ?>
        <!-- ═══ CRITICAL ERROR ═══ -->
        <div class="result-card">
            <div style="text-align: center; padding: var(--spacing-lg) 0;">
                <i class="fas fa-exclamation-circle"
                    style="font-size: 3em; color: var(--color-danger); margin-bottom: var(--spacing-md);"></i>
                <h3 style="color: var(--color-danger); margin: 0;">Error en la Importación</h3>
            </div>

            <?php if ($errorStack): ?>
                <div class="error-stack" id="errorStackBlock">
                    <button class="copy-btn" onclick="copyErrorStack()"><i class="fas fa-copy"></i> Copiar</button>
                    -- ERROR STACK (Copy & Report) --
                    Error ID: <?= htmlspecialchars($errorStack['id']) ?>

                    Message: <?= htmlspecialchars($errorStack['message']) ?>

                    File: <?= htmlspecialchars($errorStack['file']) ?>

                    Line: <?= htmlspecialchars($errorStack['line']) ?>

                    ------------------------------------
                </div>
            <?php endif; ?>

            <div class="action-bar">
                <a href="importar_odt.php" class="btn btn-primary"
                    style="min-height: 45px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-redo"></i> Intentar de Nuevo
                </a>
                <a href="index.php" class="btn btn-outline"
                    style="min-height: 45px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // ─── File Input UX ──────────────────────────────────────
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const btnImport = document.getElementById('btnImport');
    const importForm = document.getElementById('importForm');

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                const f = this.files[0];
                fileName.textContent = f.name;
                fileSize.textContent = (f.size / 1024).toFixed(1) + ' KB';
                fileInfo.classList.add('visible');
                btnImport.disabled = false;
                dropZone.style.borderColor = 'var(--color-success)';
            }
        });
    }

    // Drag & Drop visual feedback
    if (dropZone) {
        ['dragenter', 'dragover'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            });
        });
    }

    // Loading overlay on submit
    if (importForm) {
        importForm.addEventListener('submit', function () {
            document.getElementById('loadingOverlay').classList.add('active');
            btnImport.disabled = true;
            btnImport.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        });
    }

    // Copy Error Stack
    function copyErrorStack() {
        const block = document.getElementById('errorStackBlock');
        if (block) {
            const text = block.innerText.replace('Copiar', '').trim();
            navigator.clipboard.writeText(text).then(() => {
                const btn = block.querySelector('.copy-btn');
                btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
                setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i> Copiar', 2000);
            });
        }
    }

    // ─── PWA: Register Service Worker ───────────────────────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('pwa/sw-import.js')
            .then(reg => console.log('[PWA] SW registrado:', reg.scope))
            .catch(err => console.warn('[PWA] SW error:', err));
    }
</script>

<?php require_once '../../includes/footer.php'; ?>