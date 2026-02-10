<?php
/**
 * [!] ARCH: Formulario de ODT para Inspector ASSA
 * [→] EDITAR: Campos y validaciones según necesidad
 * [✓] AUDIT: Mobile-first con botones táctiles grandes
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// [✓] AUDIT: Verificar permisos
if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

$id = $_GET['id'] ?? null;
$odt = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM odt_maestro WHERE id_odt = ?");
    $stmt->execute([$id]);
    $odt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$odt) {
        header("Location: index.php?msg=error");
        exit();
    }

    // Cargar ítems existentes
    $stmtItems = $pdo->prepare("SELECT * FROM odt_items WHERE id_odt = ? ORDER BY id_item");
    $stmtItems->execute([$id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}

// [!] ARCH: Cargar cuadrilla asignada y su stock de materiales
$cuadrillaAsignada = null;
$stockCuadrilla = [];
$materialesOdt = [];

if ($id) {
    // Obtener cuadrilla asignada (la más reciente)
    $stmtCuad = $pdo->prepare("
        SELECT ps.id_cuadrilla, c.nombre_cuadrilla 
        FROM programacion_semanal ps
        JOIN cuadrillas c ON c.id_cuadrilla = ps.id_cuadrilla
        WHERE ps.id_odt = ?
        ORDER BY ps.id_programacion DESC LIMIT 1
    ");
    $stmtCuad->execute([$id]);
    $cuadrillaAsignada = $stmtCuad->fetch(PDO::FETCH_ASSOC);

    // Si hay cuadrilla, cargar su stock disponible
    if ($cuadrillaAsignada) {
        $stmtStock = $pdo->prepare("
            SELECT sc.id_material, m.nombre, m.unidad_medida, sc.cantidad as stock_disponible
            FROM stock_cuadrilla sc
            JOIN maestro_materiales m ON m.id_material = sc.id_material
            WHERE sc.id_cuadrilla = ? AND sc.cantidad > 0
            ORDER BY m.nombre
        ");
        $stmtStock->execute([$cuadrillaAsignada['id_cuadrilla']]);
        $stockCuadrilla = $stmtStock->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cargar materiales ya registrados para esta ODT
    $stmtMat = $pdo->prepare("
        SELECT om.id, om.id_material, om.cantidad, m.nombre, m.unidad_medida
        FROM odt_materiales om
        JOIN maestro_materiales m ON m.id_material = om.id_material
        WHERE om.id_odt = ?
        ORDER BY om.id
    ");
    $stmtMat->execute([$id]);
    $materialesOdt = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
}

// [→] EDITAR: Obtener tipos de trabajo activos
$tipos = $pdo->query("SELECT id_tipologia, nombre, codigo_trabajo, tiempo_limite_dias FROM tipos_trabajos WHERE estado = 1 ORDER BY codigo_trabajo, nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- [!] PWA-OFFLINE: Indicador de estado -->
<div id="offlineIndicator" class="offline-indicator">
    📶 Sin conexión - Los cambios se guardarán localmente
</div>

<div style="max-width: 600px; margin: 0 auto; padding: 15px;">
    <div class="card" style="padding: 20px;">

        <!-- Header -->
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
            <a href="index.php" class="btn"
                style="min-height: 48px; min-width: 48px; display: flex; align-items: center; justify-content: center; background: var(--bg-secondary); border: 1px solid rgba(255,255,255,0.1); color: var(--text-primary);">
                <i class="fas fa-arrow-left" style="font-size: 1.2em;"></i>
            </a>
            <div>
                <h1 style="margin: 0; font-size: 1.3em; color: var(--text-primary);">
                    <i class="fas fa-clipboard-list" style="color: var(--accent-primary);"></i>
                    <?php echo $id ? 'Editar' : 'Nueva'; ?> ODT
                </h1>
                <p style="margin: 5px 0 0; color: var(--text-secondary); font-size: 0.85em;">
                    <?php echo $id ? 'Modificar orden de trabajo' : 'Registrar nueva orden de trabajo'; ?>
                </p>
            </div>
        </div>

        <!-- [!] PWA-OFFLINE: Formulario con data-offline para sync -->
        <form id="odtForm" action="save.php" method="POST" enctype="multipart/form-data" data-offline="true">
            <?php if ($id): ?>
                <input type="hidden" name="id_odt" value="<?php echo $id; ?>">
            <?php endif; ?>

            <!-- Datos Principales -->
            <h3
                style="border-bottom: 2px solid var(--color-primary); padding-bottom: 8px; color: var(--color-primary); font-size: 1em; margin-bottom: 15px;">
                <i class="fas fa-clipboard"></i> Datos de la ODT
            </h3>

            <!-- Nro ODT ASSA -->
            <div style="margin-bottom: 20px;">
                <label class="input-label">Número ODT ASSA *</label>
                <input type="text" name="nro_odt_assa" required
                    value="<?php echo htmlspecialchars($odt['nro_odt_assa'] ?? ''); ?>"
                    placeholder="Ej: ODT-2026-001234" class="input-inspector">
            </div>

            <!-- Dirección -->
            <div style="margin-bottom: 20px;">
                <label class="input-label">Dirección del Trabajo *</label>
                <input type="text" name="direccion" required
                    value="<?php echo htmlspecialchars($odt['direccion'] ?? ''); ?>"
                    placeholder="Ej: Av. Corrientes 1234, CABA" class="input-inspector">
            </div>

            <!-- Tipo de Trabajo -->
            <div style="margin-bottom: 20px;">
                <label class="input-label">Tipo de Trabajo</label>
                <select name="id_tipologia" id="selectTipologia" class="input-inspector">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?php echo $t['id_tipologia']; ?>"
                            data-limit="<?php echo $t['tiempo_limite_dias'] ?? 0; ?>" <?php echo ($odt['id_tipologia'] ?? '') == $t['id_tipologia'] ? 'selected' : ''; ?>>
                            <?php echo $t['codigo_trabajo'] ? "[{$t['codigo_trabajo']}] " : ''; ?>
                            <?php echo $t['nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Prioridad y Estado -->
            <?php
            $userRole = $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '';
            $isInspector = ($userRole === 'Inspector ASSA');
            ?>
            <?php if ($isInspector): ?>
                <!-- Inspector: solo prioridad, estado forzado oculto -->
                <input type="hidden" name="estado_gestion" value="<?php echo $odt['estado_gestion'] ?? 'Sin Programar'; ?>">
                <div style="margin-bottom: 20px;">
                    <label class="input-label">Prioridad</label>
                    <select name="prioridad" class="input-inspector">
                        <option value="Normal" <?php echo ($odt['prioridad'] ?? '') === 'Normal' ? 'selected' : ''; ?>>
                            Normal</option>
                        <option value="Urgente" <?php echo ($odt['prioridad'] ?? '') === 'Urgente' ? 'selected' : ''; ?>>
                            🔴 Urgente</option>
                    </select>
                </div>
            <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label class="input-label">Prioridad</label>
                    <select name="prioridad" class="input-inspector">
                        <option value="Normal" <?php echo ($odt['prioridad'] ?? '') === 'Normal' ? 'selected' : ''; ?>>
                            Normal</option>
                        <option value="Urgente" <?php echo ($odt['prioridad'] ?? '') === 'Urgente' ? 'selected' : ''; ?>>
                            🔴
                            Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="input-label">Estado</label>
                    <select name="estado_gestion" id="selectEstado" class="input-inspector">
                        <option value="Sin Programar" <?php echo ($odt['estado_gestion'] ?? 'Sin Programar') === 'Sin Programar' ? 'selected' : ''; ?>>Sin Programar</option>
                        <option value="Programación Solicitada" <?php echo ($odt['estado_gestion'] ?? '') === 'Programación Solicitada' ? 'selected' : ''; ?>>Programación Solicitada</option>
                        <option value="Programado" <?php echo ($odt['estado_gestion'] ?? '') === 'Programado' ? 'selected' : ''; ?>>Programado</option>
                        <option value="Ejecución" <?php echo ($odt['estado_gestion'] ?? '') === 'Ejecución' ? 'selected' : ''; ?>>Ejecución</option>
                        <option value="Ejecutado" <?php echo ($odt['estado_gestion'] ?? '') === 'Ejecutado' ? 'selected' : ''; ?>>Ejecutado</option>
                        <option value="Precertificada" <?php echo ($odt['estado_gestion'] ?? '') === 'Precertificada' ? 'selected' : ''; ?>>Precertificada</option>
                        <option value="Finalizado" <?php echo ($odt['estado_gestion'] ?? '') === 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ítems de Trabajo con Medidas -->
            <h3 style="border-bottom: 2px solid var(--accent-primary); padding-bottom: 8px; color: var(--accent-primary); font-size: 1em; margin: 25px 0 15px;">
                <i class="fas fa-tasks"></i> Ítems a Ejecutar
            </h3>

            <div id="itemsContainer">
                <table class="items-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 35px;">Ej.</th>
                            <th>Ítem / Descripción</th>
                            <th style="width: 75px;">Med. 1</th>
                            <th style="width: 75px;">Med. 2</th>
                            <th style="width: 75px;">Med. 3</th>
                            <th style="width: 55px;">Ud.</th>
                            <th style="width: 35px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="items[<?php echo $idx; ?>][seleccionado]" value="1"
                                        <?php echo $item['seleccionado'] ? 'checked' : ''; ?>
                                        style="transform: scale(1.3);">
                                </td>
                                <td>
                                    <input type="text" name="items[<?php echo $idx; ?>][descripcion]" 
                                        value="<?php echo htmlspecialchars($item['descripcion_item']); ?>"
                                        placeholder="Descripción del ítem" class="item-input" required>
                                </td>
                                <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][medida_1]" 
                                    value="<?php echo $item['medida_1']; ?>" class="item-input" placeholder="0.00"></td>
                                <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][medida_2]" 
                                    value="<?php echo $item['medida_2']; ?>" class="item-input" placeholder="0.00"></td>
                                <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][medida_3]" 
                                    value="<?php echo $item['medida_3']; ?>" class="item-input" placeholder="0.00"></td>
                                <td>
                                    <select name="items[<?php echo $idx; ?>][unidad]" class="item-input">
                                        <option value="m" <?php echo ($item['unidad'] ?? 'm') === 'm' ? 'selected' : ''; ?>>m</option>
                                        <option value="m2" <?php echo ($item['unidad'] ?? '') === 'm2' ? 'selected' : ''; ?>>m²</option>
                                        <option value="m3" <?php echo ($item['unidad'] ?? '') === 'm3' ? 'selected' : ''; ?>>m³</option>
                                        <option value="u" <?php echo ($item['unidad'] ?? '') === 'u' ? 'selected' : ''; ?>>u</option>
                                    </select>
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" onclick="this.closest('tr').remove()" class="btn-remove-item" title="Quitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" onclick="addItemRow()" class="btn-add-item">
                    <i class="fas fa-plus"></i> Agregar Ítem
                </button>
            </div>

            <!-- Fechas -->
            <h3
                style="border-bottom: 2px solid #666; padding-bottom: 8px; color: #666; font-size: 1em; margin: 25px 0 15px;">
                <i class="fas fa-calendar-alt"></i> Plazos
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label class="input-label">Fecha Inicio Plazo</label>
                    <!-- [!] AUTO: Se establece hoy por defecto si nueva -->
                    <input type="date" name="fecha_inicio_plazo" id="fechaInicio"
                        value="<?php echo $odt['fecha_inicio_plazo'] ?? date('Y-m-d'); ?>" class="input-inspector">
                </div>
                <div>
                    <label class="input-label">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" id="fechaVencimiento"
                        value="<?php echo $odt['fecha_vencimiento'] ?? ''; ?>" class="input-inspector">
                    <small style="color: var(--text-secondary); font-size: 0.75em; display: block; margin-top: 5px;">
                        <i class="fas fa-magic"></i> Se calcula autom. al pasar a 'Programado'
                    </small>
                </div>
            </div>

            <!-- Avance -->
            <div style="margin-bottom: 20px;">
                <label class="input-label">Avance / Descripción</label>
                <textarea name="avance" rows="3"
                    placeholder="Descripción del trabajo realizado o porcentaje de avance..."
                    class="input-inspector"><?php echo htmlspecialchars($odt['avance'] ?? ''); ?></textarea>
            </div>

            <!-- Fotos -->
            <h3
                style="border-bottom: 2px solid var(--accent-primary); padding-bottom: 8px; color: var(--accent-primary); font-size: 1em; margin: 25px 0 15px;">
                <i class="fas fa-camera"></i> Registro Fotográfico
            </h3>

            <!-- Fotos Existentes (si hay) -->
            <?php if ($id):
                $stmtFoto = $pdo->prepare("SELECT * FROM odt_fotos WHERE id_odt = ? ORDER BY tipo_foto, id_foto");
                $stmtFoto->execute([$id]);
                $fotosExistentes = $stmtFoto->fetchAll(PDO::FETCH_ASSOC);

                // Agrupar por tipo
                $fotosPorTipo = [];
                foreach ($fotosExistentes as $f) {
                    $tipo = $f['tipo_foto'] ?? 'ODT';
                    $fotosPorTipo[$tipo][] = $f;
                }
                ?>
                <?php if (!empty($fotosPorTipo)): ?>
                    <div class="existing-photos-section"
                        style="margin-bottom: 20px; padding: 15px; background: var(--bg-tertiary); border-radius: var(--border-radius-md);">
                        <label class="input-label" style="margin-bottom: 15px;"><i class="fas fa-images"></i> Fotos
                            Guardadas</label>

                        <?php if (!empty($fotosPorTipo['ODT'])): ?>
                            <div style="margin-bottom: 15px;">
                                <small style="color: var(--text-muted); display: block; margin-bottom: 8px;"><i
                                        class="fas fa-file-image"></i> Foto de la ODT</small>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    <?php foreach ($fotosPorTipo['ODT'] as $f): ?>
                                        <div class="existing-photo-item" id="photo_<?php echo $f['id_foto']; ?>"
                                            style="position: relative; width: 100px;">
                                            <img src="../../<?php echo $f['ruta_archivo']; ?>"
                                                style="width: 100%; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--accent-primary);"
                                                onclick="window.open('../../<?php echo $f['ruta_archivo']; ?>', '_blank')">
                                            <button type="button" onclick="deletePhoto(<?php echo $f['id_foto']; ?>)"
                                                style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 12px;"
                                                title="Eliminar foto">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fotosPorTipo['TRABAJO'])): ?>
                            <div style="margin-bottom: 15px;">
                                <small style="color: var(--text-muted); display: block; margin-bottom: 8px;"><i
                                        class="fas fa-hard-hat"></i> Fotos del Trabajo
                                    (<?php echo count($fotosPorTipo['TRABAJO']); ?>)</small>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;">
                                    <?php foreach ($fotosPorTipo['TRABAJO'] as $f): ?>
                                        <div class="existing-photo-item" id="photo_<?php echo $f['id_foto']; ?>"
                                            style="position: relative;">
                                            <img src="../../<?php echo $f['ruta_archivo']; ?>"
                                                style="width: 100%; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--bg-tertiary);"
                                                onclick="window.open('../../<?php echo $f['ruta_archivo']; ?>', '_blank')">
                                            <button type="button" onclick="deletePhoto(<?php echo $f['id_foto']; ?>)"
                                                style="position: absolute; top: -6px; right: -6px; background: #e74c3c; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 10px;"
                                                title="Eliminar foto">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fotosPorTipo['EXTRA'])): ?>
                            <div>
                                <small style="color: var(--text-muted); display: block; margin-bottom: 8px;"><i
                                        class="fas fa-plus-circle"></i> Fotos Extra
                                    (<?php echo count($fotosPorTipo['EXTRA']); ?>)</small>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;">
                                    <?php foreach ($fotosPorTipo['EXTRA'] as $f): ?>
                                        <div class="existing-photo-item" id="photo_<?php echo $f['id_foto']; ?>"
                                            style="position: relative;">
                                            <img src="../../<?php echo $f['ruta_archivo']; ?>"
                                                style="width: 100%; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--bg-tertiary);"
                                                onclick="window.open('../../<?php echo $f['ruta_archivo']; ?>', '_blank')">
                                            <button type="button" onclick="deletePhoto(<?php echo $f['id_foto']; ?>)"
                                                style="position: absolute; top: -6px; right: -6px; background: #e74c3c; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 10px;"
                                                title="Eliminar foto">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- FOTO ODT: Solo 1, con opción de eliminar y retomar -->
            <div style="margin-bottom: 20px;">
                <label class="input-label"><i class="fas fa-file-image"></i> Foto de la ODT * <small
                        style="font-weight: normal;">(solo 1)</small></label>
                <input type="file" name="foto_odt" id="foto_odt" accept="image/*" class="input-inspector"
                    style="display: none;">

                <!-- Botones (se ocultan cuando hay foto) -->
                <div id="btns_foto_odt" style="display: flex; gap: 10px;">
                    <button type="button" onclick="openCamera('foto_odt')" class="btn-inspector-secondary"
                        style="flex: 1; min-height: 55px;">
                        <i class="fas fa-camera" style="font-size: 1.3em;"></i> Cámara
                    </button>
                    <button type="button" onclick="openGallery('foto_odt')" class="btn-inspector-secondary"
                        style="flex: 1; min-height: 55px;">
                        <i class="fas fa-images" style="font-size: 1.3em;"></i> Galería
                    </button>
                </div>

                <!-- Preview con botón eliminar -->
                <div id="preview_foto_odt" class="photo-preview-single" style="display: none;"></div>
            </div>

            <!-- FOTOS TRABAJO: Múltiples, se van acumulando -->
            <div style="margin-bottom: 20px;">
                <label class="input-label"><i class="fas fa-hard-hat"></i> Fotos del Trabajo * <small
                        style="font-weight: normal;">(puedes agregar varias)</small></label>
                <input type="file" id="foto_trabajo_input" accept="image/*" style="display: none;">

                <!-- Botones siempre visibles -->
                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <button type="button" onclick="openCameraWork()" class="btn-inspector-secondary"
                        style="flex: 1; min-height: 55px;">
                        <i class="fas fa-camera" style="font-size: 1.3em;"></i> Cámara
                    </button>
                    <button type="button" onclick="openGalleryWork()" class="btn-inspector-secondary"
                        style="flex: 1; min-height: 55px;">
                        <i class="fas fa-images" style="font-size: 1.3em;"></i> Galería
                    </button>
                </div>

                <!-- Grid de fotos acumuladas -->
                <div id="work_photos_grid" class="photo-grid"></div>
                <!-- Inputs ocultos para enviar al servidor -->
                <div id="work_photos_inputs"></div>
                <div id="work_photos_count" style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px;">
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- MATERIALES UTILIZADOS                  -->
            <!-- ═══════════════════════════════════════ -->
            <?php if ($id && $cuadrillaAsignada): ?>
            <h3 style="border-bottom: 2px solid var(--accent-primary); padding-bottom: 8px; color: var(--accent-primary); font-size: 1em; margin: 25px 0 15px;">
                <i class="fas fa-boxes"></i> Materiales Utilizados
                <small style="font-weight: normal; color: var(--text-muted);">
                    (Stock de <?php echo htmlspecialchars($cuadrillaAsignada['nombre_cuadrilla']); ?>)
                </small>
            </h3>
            <input type="hidden" name="id_cuadrilla_asignada" value="<?php echo $cuadrillaAsignada['id_cuadrilla']; ?>">

            <!-- Selector de material -->
            <?php if (!empty($stockCuadrilla)): ?>
            <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                <select id="materialSelect" class="item-input" style="flex: 2; min-width: 150px;">
                    <option value="">-- Seleccionar material --</option>
                    <?php foreach ($stockCuadrilla as $mat): ?>
                        <option value="<?php echo $mat['id_material']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($mat['nombre']); ?>"
                                data-stock="<?php echo $mat['stock_disponible']; ?>"
                                data-unidad="<?php echo htmlspecialchars($mat['unidad_medida']); ?>">
                            <?php echo htmlspecialchars($mat['nombre']); ?> (<?php echo $mat['stock_disponible']; ?> <?php echo htmlspecialchars($mat['unidad_medida']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="materialCantidad" step="0.01" min="0.01" placeholder="Cant." 
                       class="item-input" style="flex: 0.5; min-width: 80px;">
                <button type="button" onclick="addMaterial()" class="btn-inspector-secondary" 
                        style="min-height: 44px; padding: 0 15px;">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.85em; margin-bottom: 12px;">
                <i class="fas fa-info-circle"></i> La cuadrilla no tiene materiales en stock.
            </p>
            <?php endif; ?>

            <!-- Tabla de materiales agregados -->
            <table class="items-table" id="materialesTable">
                <thead>
                    <tr>
                        <th style="width: 50%;">Material</th>
                        <th style="width: 20%;">Cantidad</th>
                        <th style="width: 15%;">Ud.</th>
                        <th style="width: 15%; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody id="materialesBody">
                    <?php foreach ($materialesOdt as $idx => $mat): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="materiales[<?php echo $idx; ?>][id_material]" value="<?php echo $mat['id_material']; ?>">
                            <?php echo htmlspecialchars($mat['nombre']); ?>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="materiales[<?php echo $idx; ?>][cantidad]" 
                                   value="<?php echo $mat['cantidad']; ?>" class="item-input" style="padding: 6px;" required>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85em;">
                            <?php echo htmlspecialchars($mat['unidad_medida']); ?>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" class="btn-remove-item" title="Quitar">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Inspector -->
            <div style="margin-bottom: 25px;">
                <label class="input-label">Inspector ASSA</label>
                <input type="text" name="inspector"
                    value="<?php echo htmlspecialchars($odt['inspector'] ?? ($_SESSION['user_name'] ?? '')); ?>"
                    placeholder="Nombre del inspector" class="input-inspector">
            </div>

            <!-- Botones -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button type="submit" class="btn-inspector-primary">
                    <span class="online-text">💾 Guardar ODT</span>
                    <span class="offline-text">📱 Guardar Local</span>
                </button>
                <a href="index.php" class="btn-inspector-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    /* [→] EDITAR INTERFAZ: Estilos Mobile-First para Inspector */
    .offline-indicator {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        padding: 10px;
        background: #f39c12;
        color: white;
        text-align: center;
        font-weight: bold;
        z-index: 9999;
        display: none;
    }

    body.offline .offline-indicator {
        display: block;
    }

    body.offline {
        padding-top: 45px;
    }

    body.offline .online-text {
        display: none;
    }

    body.offline .offline-text {
        display: inline !important;
    }

    .offline-text {
        display: none;
    }

    .input-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-secondary);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* [✓] AUDIT: Inputs grandes para uso táctil */
    .input-inspector {
        width: 100%;
        padding: 16px;
        font-size: 1.1rem;
        /* Evita zoom en Android */
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border-radius: var(--border-radius-md);
        transition: all 0.2s ease;
    }

    .input-inspector:focus {
        border-color: var(--accent-primary);
        outline: none;
        background: var(--bg-secondary);
        box-shadow: 0 0 0 3px var(--accent-glow);
    }

    /* [✓] AUDIT: Botones extra grandes 60px para calle */
    .btn-inspector-primary {
        width: 100%;
        min-height: 60px;
        padding: 18px;
        font-size: 18px;
        font-weight: 700;
        text-transform: uppercase;
        background: var(--accent-primary);
        color: var(--color-primary-dark);
        border: none;
        border-radius: var(--border-radius-md);
        cursor: pointer;
        letter-spacing: 1px;
        box-shadow: var(--shadow-md);
        transition: all 0.2s ease;
    }

    .btn-inspector-primary:hover {
        transform: translateY(-2px);
        background: var(--accent-secondary);
        box-shadow: var(--glow-primary);
    }

    .btn-inspector-secondary {
        width: 100%;
        min-height: 50px;
        padding: 15px;
        font-size: 16px;
        background: transparent;
        color: var(--text-secondary);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--border-radius-md);
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .btn-inspector-secondary:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
    }

    /* Photo Preview */
    .photo-preview {
        margin-top: 10px;
    }

    .photo-preview img {
        max-width: 100%;
        max-height: 150px;
        border-radius: var(--border-radius-md);
        border: 2px solid var(--accent-primary);
        object-fit: cover;
    }

    .photo-preview .photo-name {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: 5px;
    }

    /* Single Photo Preview (ODT) */
    .photo-preview-single {
        position: relative;
        margin-top: 10px;
    }

    .photo-preview-single img {
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: var(--border-radius-md);
        border: 2px solid var(--accent-primary);
    }

    .photo-preview-single .delete-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #e74c3c;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.1em;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    /* Photo Grid (Work Photos) */
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
    }

    .photo-grid-item {
        position: relative;
        aspect-ratio: 1;
    }

    .photo-grid-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: var(--border-radius-sm);
        border: 1px solid var(--bg-tertiary);
    }

    .photo-grid-item .delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #e74c3c;
        color: white;
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.8em;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .items-table th {
        text-align: left;
        padding: 8px 6px;
        font-size: 0.7em;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
        border-bottom: 2px solid var(--bg-secondary);
        white-space: nowrap;
    }
    .items-table td {
        padding: 4px 3px;
    }
    .item-input {
        width: 100%;
        padding: 10px 8px;
        font-size: 0.95rem;
        border: 1px solid rgba(255,255,255,0.1);
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border-radius: 6px;
        box-sizing: border-box;
    }
    .item-input:focus {
        border-color: var(--accent-primary);
        outline: none;
        box-shadow: 0 0 0 2px var(--accent-glow);
    }
    .btn-add-item {
        width: 100%;
        padding: 12px;
        font-size: 0.95em;
        font-weight: 600;
        background: var(--bg-tertiary);
        color: var(--accent-primary);
        border: 2px dashed rgba(100,181,246,0.3);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-add-item:hover {
        background: rgba(100,181,246,0.08);
        border-color: var(--accent-primary);
    }
    .btn-remove-item {
        background: none;
        border: none;
        color: #e74c3c;
        cursor: pointer;
        font-size: 0.9em;
        padding: 4px;
        opacity: 0.6;
    }
    .btn-remove-item:hover {
        opacity: 1;
    }
    @media (max-width: 480px) {
        .items-table th:nth-child(5), .items-table td:nth-child(5) { display: none; }
        .items-table th:nth-child(6), .items-table td:nth-child(6) { display: none; }
    }
</style>

<script>
    // =============================================
    // ÍTEMS DINÁMICOS
    // =============================================
    let itemIndex = <?php echo !empty($items) ? count($items) : 0; ?>;

    function addItemRow() {
        const tbody = document.getElementById('itemsBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align:center;">
                <input type="checkbox" name="items[${itemIndex}][seleccionado]" value="1" checked style="transform: scale(1.3);">
            </td>
            <td>
                <input type="text" name="items[${itemIndex}][descripcion]" placeholder="Descripción del ítem" class="item-input" required>
            </td>
            <td><input type="number" step="0.01" name="items[${itemIndex}][medida_1]" class="item-input" placeholder="0.00"></td>
            <td><input type="number" step="0.01" name="items[${itemIndex}][medida_2]" class="item-input" placeholder="0.00"></td>
            <td><input type="number" step="0.01" name="items[${itemIndex}][medida_3]" class="item-input" placeholder="0.00"></td>
            <td>
                <select name="items[${itemIndex}][unidad]" class="item-input">
                    <option value="m">m</option>
                    <option value="m2">m²</option>
                    <option value="m3">m³</option>
                    <option value="u">u</option>
                </select>
            </td>
            <td style="text-align:center;">
                <button type="button" onclick="this.closest('tr').remove()" class="btn-remove-item" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        itemIndex++;
        // Focus en la descripción del nuevo item
        tr.querySelector('input[type="text"]').focus();
    }

    // =============================================
    // MATERIALES DINÁMICOS
    // =============================================
    let matIndex = <?php echo count($materialesOdt); ?>;

    function addMaterial() {
        const select = document.getElementById('materialSelect');
        const cantInput = document.getElementById('materialCantidad');
        
        if (!select || !select.value) { alert('Seleccione un material'); return; }
        
        const cantidad = parseFloat(cantInput.value);
        if (!cantidad || cantidad <= 0) { alert('Ingrese una cantidad válida'); return; }

        const opt = select.options[select.selectedIndex];
        const stockDisp = parseFloat(opt.dataset.stock);
        
        if (cantidad > stockDisp) {
            alert(`Stock insuficiente. Disponible: ${stockDisp} ${opt.dataset.unidad}`);
            return;
        }

        const tbody = document.getElementById('materialesBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="hidden" name="materiales[${matIndex}][id_material]" value="${select.value}">
                ${opt.dataset.nombre}
            </td>
            <td>
                <input type="number" step="0.01" name="materiales[${matIndex}][cantidad]" 
                       value="${cantidad}" class="item-input" style="padding: 6px;" required>
            </td>
            <td style="color: var(--text-muted); font-size: 0.85em;">${opt.dataset.unidad}</td>
            <td style="text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" class="btn-remove-item" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        matIndex++;

        // Limpiar inputs
        select.value = '';
        cantInput.value = '';
    }

    // =============================================
    // FOTO ODT: Solo 1 foto con eliminar/retomar
    // =============================================
    const fotoOdtInput = document.getElementById('foto_odt');
    const fotoOdtBtns = document.getElementById('btns_foto_odt');
    const fotoOdtPreview = document.getElementById('preview_foto_odt');

    function openCamera(inputId) {
        const input = document.getElementById(inputId);
        input.setAttribute('capture', 'environment');
        input.click();
    }

    function openGallery(inputId) {
        const input = document.getElementById(inputId);
        input.removeAttribute('capture');
        input.click();
    }

    fotoOdtInput.addEventListener('change', function (e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function (ev) {
                fotoOdtBtns.style.display = 'none';
                fotoOdtPreview.style.display = 'block';
                fotoOdtPreview.innerHTML = `
                    <img src="${ev.target.result}" alt="Foto ODT">
                    <button type="button" class="delete-btn" onclick="clearOdtPhoto()" title="Eliminar y tomar otra">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    function clearOdtPhoto() {
        fotoOdtInput.value = '';
        fotoOdtPreview.style.display = 'none';
        fotoOdtPreview.innerHTML = '';
        fotoOdtBtns.style.display = 'flex';
    }

    // =============================================
    // FOTOS TRABAJO: Múltiples, se van acumulando
    // =============================================
    const workPhotoInput = document.getElementById('foto_trabajo_input');
    const workPhotosGrid = document.getElementById('work_photos_grid');
    const workPhotosInputs = document.getElementById('work_photos_inputs');
    const workPhotosCount = document.getElementById('work_photos_count');
    let workPhotoFiles = []; // Array para guardar los archivos
    let workPhotoCounter = 0;

    function openCameraWork() {
        workPhotoInput.setAttribute('capture', 'environment');
        workPhotoInput.click();
    }

    function openGalleryWork() {
        workPhotoInput.removeAttribute('capture');
        workPhotoInput.click();
    }

    workPhotoInput.addEventListener('change', function (e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const fileId = 'work_photo_' + (workPhotoCounter++);

            // Guardar referencia
            workPhotoFiles.push({ id: fileId, file: file });

            // Crear input oculto para enviar
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'file';
            hiddenInput.name = 'fotos_trabajo[]';
            hiddenInput.id = fileId;
            hiddenInput.style.display = 'none';

            // Usar DataTransfer para asignar el archivo
            const dt = new DataTransfer();
            dt.items.add(file);
            hiddenInput.files = dt.files;
            workPhotosInputs.appendChild(hiddenInput);

            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function (ev) {
                const div = document.createElement('div');
                div.className = 'photo-grid-item';
                div.id = 'preview_' + fileId;
                div.innerHTML = `
                    <img src="${ev.target.result}" alt="Foto trabajo">
                    <button type="button" class="delete-btn" onclick="removeWorkPhoto('${fileId}')" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                workPhotosGrid.appendChild(div);
                updateWorkPhotoCount();
            };
            reader.readAsDataURL(file);

            // Limpiar input para permitir seleccionar la misma foto
            this.value = '';
        }
    });

    function removeWorkPhoto(fileId) {
        // Eliminar del array
        workPhotoFiles = workPhotoFiles.filter(f => f.id !== fileId);
        // Eliminar input oculto
        const input = document.getElementById(fileId);
        if (input) input.remove();
        // Eliminar preview
        const preview = document.getElementById('preview_' + fileId);
        if (preview) preview.remove();
        updateWorkPhotoCount();
    }

    function updateWorkPhotoCount() {
        const count = workPhotoFiles.length;
        workPhotosCount.innerHTML = count > 0
            ? `<i class="fas fa-check-circle" style="color: var(--color-success);"></i> ${count} foto${count > 1 ? 's' : ''} del trabajo`
            : '';
    }

    // =============================================
    // Conexión y otros
    // =============================================

    // [!] AUTO-FILL: Calcular Vencimiento
    const selectEstado = document.getElementById('selectEstado');
    const selectTipologia = document.getElementById('selectTipologia');
    const inputVencimiento = document.getElementById('fechaVencimiento');

    function checkAutoFillDate() {
        const estado = selectEstado.value;
        const tipoOption = selectTipologia.options[selectTipologia.selectedIndex];

        // Solo si cambiamos a Programado y tenemos un tipo seleccionado
        if (estado === 'Programado' && tipoOption && tipoOption.dataset.limit) {
            const diasLimite = parseInt(tipoOption.dataset.limit) || 0;
            if (diasLimite > 0) {
                const hoy = new Date();
                const venci = new Date(hoy);
                venci.setDate(hoy.getDate() + diasLimite);

                // Formato YYYY-MM-DD
                const yyyy = venci.getFullYear();
                const mm = String(venci.getMonth() + 1).padStart(2, '0');
                const dd = String(venci.getDate()).padStart(2, '0');

                inputVencimiento.value = `${yyyy}-${mm}-${dd}`;

                // Visual feedback
                inputVencimiento.style.borderColor = 'var(--accent-primary)';
                setTimeout(() => {
                    inputVencimiento.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                }, 1000);
            }
        }
    }

    if (selectEstado && selectTipologia) {
        selectEstado.addEventListener('change', checkAutoFillDate);
        selectTipologia.addEventListener('change', () => {
            // Si ya está en programado y cambio el tipo, recalcular
            if (selectEstado.value === 'Programado') checkAutoFillDate();
        });
    }

    // [!] PWA-OFFLINE: Interceptar submit para modo offline
    document.getElementById('odtForm').addEventListener('submit', async function (e) {
        if (!navigator.onLine) {
            e.preventDefault();

            const formData = new FormData(this);
            const datos = Object.fromEntries(formData);

            // Validación cliente
            if (!datos.nro_odt_assa || !datos.direccion) {
                alert('❌ Número ODT y Dirección son obligatorios');
                return;
            }

            // [!] FALLBACK: Guardar para sincronizar después
            const pending = JSON.parse(localStorage.getItem('odt_pending_sync') || '[]');
            pending.push({
                ...datos,
                _timestamp: Date.now(),
                _action: datos.id_odt ? 'UPDATE' : 'CREATE'
            });
            localStorage.setItem('odt_pending_sync', JSON.stringify(pending));

            alert('📱 ODT guardada localmente.\nSe sincronizará al recuperar conexión.');
            window.location.href = 'index.php';
        }
    });

    // =============================================
    // ELIMINAR FOTO EXISTENTE
    // =============================================
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
                    const photoEl = document.getElementById('photo_' + photoId);
                    if (photoEl) {
                        photoEl.style.opacity = '0';
                        photoEl.style.transform = 'scale(0.8)';
                        setTimeout(() => photoEl.remove(), 200);
                    }
                } else {
                    alert('Error: ' + (data.error || 'No se pudo eliminar'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión');
            });
    }
    // Hacer la función global
    window.deletePhoto = deletePhoto;
</script>

<?php require_once '../../includes/footer.php'; ?>