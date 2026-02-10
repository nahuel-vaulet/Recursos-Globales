<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if editing
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$vehiculo = null;
$mantenimiento = [];
$reparaciones = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id_vehiculo = ?");
    $stmt->execute([$_GET['id']]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        header("Location: index.php?msg=not_found");
        exit;
    }

    // Fetch maintenance items
    $stmtMant = $pdo->prepare("SELECT * FROM vehiculos_mantenimiento WHERE id_vehiculo = ? ORDER BY tipo");
    $stmtMant->execute([$_GET['id']]);
    $mantenimiento = $stmtMant->fetchAll(PDO::FETCH_ASSOC);

    // Fetch repairs
    $stmtRep = $pdo->prepare("SELECT * FROM vehiculos_reparaciones WHERE id_vehiculo = ? ORDER BY fecha DESC");
    $stmtRep->execute([$_GET['id']]);
    $reparaciones = $stmtRep->fetchAll(PDO::FETCH_ASSOC);
}

// Index existing maintenance by type
$mantByType = [];
foreach ($mantenimiento as $m) {
    $mantByType[$m['tipo']] = $m;
}

// Options
$tipos = ['Camioneta', 'Utilitario', 'Camión', 'Moto', 'Retropala', 'Generador', 'Otro'];
$estados = ['Operativo', 'En Taller', 'Baja'];
$niveles_aceite = ['OK', 'Bajo', 'Crítico'];
$niveles_combustible = ['Lleno', 'Medio', 'Bajo', 'Reserva'];
$estados_frenos = ['OK', 'Desgastados', 'Cambiar'];
$coberturas = ['Básica', 'Tercero Completo', 'Todo Riesgo'];

// Maintenance item types
$tiposMantenimiento = [
    ['key' => 'Filtro Aceite', 'icon' => 'fa-oil-can', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Aire', 'icon' => 'fa-wind', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Aire Secundario', 'icon' => 'fa-wind', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Combustible 1rio', 'icon' => 'fa-gas-pump', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Combustible 2rio', 'icon' => 'fa-gas-pump', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Habitáculo', 'icon' => 'fa-fan', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Hidráulico 1', 'icon' => 'fa-cogs', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Filtro Hidráulico 2', 'icon' => 'fa-cogs', 'group' => 'filtros', 'hasOil' => false],
    ['key' => 'Aceite Motor', 'icon' => 'fa-oil-can', 'group' => 'aceites', 'hasOil' => true],
    ['key' => 'Aceite Diferencial', 'icon' => 'fa-oil-can', 'group' => 'aceites', 'hasOil' => true],
    ['key' => 'Aceite Caja', 'icon' => 'fa-oil-can', 'group' => 'aceites', 'hasOil' => true],
];

// Fetch Active Squads
$cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card" style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0;">
            <i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus-circle'; ?>"></i>
            <?php echo $isEdit ? 'Editar Vehículo' : 'Nuevo Vehículo'; ?>
        </h2>
        <div style="display: flex; gap: 10px;">
            <?php if ($isEdit): ?>
                <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-eye"></i> Ver Ficha
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
        <div class="alert alert-danger"
            style="background: rgba(239,68,68,0.15); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239,68,68,0.3);">
            <strong>Error:</strong>
            <?php echo htmlspecialchars($_GET['details'] ?? 'No se pudo guardar'); ?>
        </div>
    <?php endif; ?>

    <form action="save.php" method="POST" id="vehiculoForm" enctype="multipart/form-data">
        <input type="hidden" name="id_vehiculo" value="<?php echo $vehiculo['id_vehiculo'] ?? ''; ?>">

        <!-- ═══ SECCIÓN 1: IDENTIFICACIÓN ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-id-card"></i> 1. Identificación</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Patente / Nº Serie *</label>
                    <input type="text" name="patente" required
                        value="<?php echo htmlspecialchars($vehiculo['patente'] ?? ''); ?>"
                        placeholder="ABC123 o S/N-0001" style="text-transform: uppercase;">
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="tipo" required>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo ($vehiculo['tipo'] ?? '') == $t ? 'selected' : ''; ?>>
                                <?php echo $t; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Combustible *</label>
                    <select name="tipo_combustible" required>
                        <?php
                        $combustibles = ['Diesel', 'Gasoil', 'Nafta', 'GNC', 'Eléctrico'];
                        $currentFuel = $vehiculo['tipo_combustible'] ?? 'Diesel';
                        foreach ($combustibles as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo $currentFuel == $c ? 'selected' : ''; ?>>
                                <?php echo $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cuadrilla Asignada</label>
                    <select name="id_cuadrilla">
                        <option value="">-- Sin Asignar --</option>
                        <?php foreach ($cuadrillas as $c): ?>
                            <option value="<?php echo $c['id_cuadrilla']; ?>" <?php echo ($vehiculo['id_cuadrilla'] ?? '') == $c['id_cuadrilla'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" name="marca" value="<?php echo htmlspecialchars($vehiculo['marca'] ?? ''); ?>"
                        placeholder="Ej: Ford, Fiat, Toyota">
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" name="modelo"
                        value="<?php echo htmlspecialchars($vehiculo['modelo'] ?? ''); ?>"
                        placeholder="Ej: Ranger, Ducato">
                </div>
                <div class="form-group">
                    <label>Año</label>
                    <input type="number" name="anio" min="1990" max="2030"
                        value="<?php echo $vehiculo['anio'] ?? ''; ?>" placeholder="2024">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo ($vehiculo['estado'] ?? 'Operativo') == $e ? 'selected' : ''; ?>>
                                <?php echo $e; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ═══ SECCIÓN 2: FOTO DEL VEHÍCULO ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-camera"></i> 2. Foto del Estado</h3>
            <?php if (!empty($vehiculo['foto_estado'])): ?>
                <div style="margin-bottom: 15px;">
                    <img src="../../uploads/vehiculos/<?php echo $vehiculo['foto_estado']; ?>"
                        style="max-width: 300px; max-height: 200px; border-radius: 10px; object-fit: cover; border: 2px solid var(--bg-secondary);"
                        alt="Estado actual">
                    <div style="font-size: 0.8em; color: var(--text-muted); margin-top: 5px;">Foto actual del vehículo
                    </div>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Subir/Actualizar Foto</label>
                <input type="file" name="foto_estado" accept="image/*">
            </div>
        </div>

        <!-- ═══ SECCIÓN 3: DOCUMENTACIÓN Y SEGURO ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-shield-alt"></i> 3. Documentación y Seguro</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Vencimiento VTV</label>
                    <input type="date" name="vencimiento_vtv"
                        value="<?php echo $vehiculo['vencimiento_vtv'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Vencimiento Seguro</label>
                    <input type="date" name="vencimiento_seguro"
                        value="<?php echo $vehiculo['vencimiento_seguro'] ?? ''; ?>">
                </div>
            </div>

            <div class="subsection-title"><i class="fas fa-file-contract"></i> Datos del Seguro</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre Aseguradora / Productor</label>
                    <input type="text" name="seguro_nombre"
                        value="<?php echo htmlspecialchars($vehiculo['seguro_nombre'] ?? ''); ?>"
                        placeholder="Ej: La Caja, Zurich, Sancor">
                </div>
                <div class="form-group">
                    <label>Teléfono Contacto</label>
                    <input type="text" name="seguro_telefono"
                        value="<?php echo htmlspecialchars($vehiculo['seguro_telefono'] ?? ''); ?>"
                        placeholder="Ej: 011-4555-1234">
                </div>
                <div class="form-group">
                    <label>Teléfono Grúa</label>
                    <input type="text" name="seguro_grua_telefono"
                        value="<?php echo htmlspecialchars($vehiculo['seguro_grua_telefono'] ?? ''); ?>"
                        placeholder="Ej: 0800-222-GRUA">
                </div>
                <div class="form-group">
                    <label>Tipo de Cobertura</label>
                    <select name="seguro_cobertura" id="selectCobertura">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($coberturas as $cob): ?>
                            <option value="<?php echo $cob; ?>" <?php echo ($vehiculo['seguro_cobertura'] ?? '') == $cob ? 'selected' : ''; ?>>
                                <?php echo $cob; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="franquiciaGroup"
                    style="<?php echo ($vehiculo['seguro_cobertura'] ?? '') !== 'Todo Riesgo' ? 'display:none;' : ''; ?>">
                    <label>Monto Franquicia ($)</label>
                    <input type="number" name="seguro_franquicia" step="0.01" min="0"
                        value="<?php echo $vehiculo['seguro_franquicia'] ?? ''; ?>"
                        placeholder="Monto de franquicia">
                </div>
                <div class="form-group">
                    <label>Valor Seguro ($/mes)</label>
                    <input type="number" name="seguro_valor" step="0.01" min="0"
                        value="<?php echo $vehiculo['seguro_valor'] ?? ''; ?>" placeholder="Valor mensual">
                </div>
            </div>
            <div class="form-grid" style="margin-top: 15px;">
                <div class="form-group">
                    <label>Póliza (PDF)</label>
                    <?php if (!empty($vehiculo['seguro_poliza_pdf'])): ?>
                        <div style="margin-bottom: 8px;">
                            <a href="../../uploads/vehiculos/polizas/<?php echo $vehiculo['seguro_poliza_pdf']; ?>"
                                target="_blank"
                                style="color: var(--accent-primary); text-decoration: none; font-size: 0.9em;">
                                <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> Ver póliza actual
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="seguro_poliza_pdf" accept=".pdf">
                </div>
            </div>
        </div>

        <!-- ═══ SECCIÓN 4: GESTYA ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-satellite-dish"></i> 4. Gestya (Rastreo GPS)</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>¿Tiene Gestya Instalado?</label>
                    <div class="toggle-switch-wrapper">
                        <label class="toggle-switch">
                            <input type="checkbox" name="gestya_instalado" value="1"
                                <?php echo ($vehiculo['gestya_instalado'] ?? 0) ? 'checked' : ''; ?>
                                onchange="toggleGestya(this)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label"
                            id="gestyaLabel"><?php echo ($vehiculo['gestya_instalado'] ?? 0) ? 'SÍ' : 'NO'; ?></span>
                    </div>
                </div>
                <div class="form-group gestya-field"
                    style="<?php echo !($vehiculo['gestya_instalado'] ?? 0) ? 'display:none;' : ''; ?>">
                    <label>Fecha Instalación</label>
                    <input type="date" name="gestya_fecha_instalacion"
                        value="<?php echo $vehiculo['gestya_fecha_instalacion'] ?? ''; ?>">
                </div>
                <div class="form-group gestya-field"
                    style="<?php echo !($vehiculo['gestya_instalado'] ?? 0) ? 'display:none;' : ''; ?>">
                    <label>Lugar de Instalación</label>
                    <input type="text" name="gestya_lugar"
                        value="<?php echo htmlspecialchars($vehiculo['gestya_lugar'] ?? ''); ?>"
                        placeholder="Ej: Taller Central, Sede Norte">
                </div>
            </div>
        </div>

        <!-- ═══ SECCIÓN 5: CONTROL DIARIO ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-clipboard-check"></i> 5. Control Diario</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nivel de Aceite</label>
                    <select name="nivel_aceite">
                        <?php foreach ($niveles_aceite as $n): ?>
                            <option value="<?php echo $n; ?>" <?php echo ($vehiculo['nivel_aceite'] ?? 'OK') == $n ? 'selected' : ''; ?>>
                                <?php
                                $icons = ['OK' => '✅', 'Bajo' => '⚠️', 'Crítico' => '🔴'];
                                echo ($icons[$n] ?? '') . ' ' . $n;
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel de Combustible</label>
                    <select name="nivel_combustible">
                        <?php foreach ($niveles_combustible as $n): ?>
                            <option value="<?php echo $n; ?>" <?php echo ($vehiculo['nivel_combustible'] ?? 'Medio') == $n ? 'selected' : ''; ?>>
                                <?php
                                $icons = ['Lleno' => '🟢', 'Medio' => '🟡', 'Bajo' => '🟠', 'Reserva' => '🔴'];
                                echo ($icons[$n] ?? '') . ' ' . $n;
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado de Frenos</label>
                    <select name="estado_frenos">
                        <?php foreach ($estados_frenos as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo ($vehiculo['estado_frenos'] ?? 'OK') == $e ? 'selected' : ''; ?>>
                                <?php
                                $icons = ['OK' => '✅', 'Desgastados' => '⚠️', 'Cambiar' => '🔴'];
                                echo ($icons[$e] ?? '') . ' ' . $e;
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ═══ SECCIÓN 6: MANTENIMIENTO / KM ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-wrench"></i> 6. Kilómetros y Service</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Km Actual</label>
                    <input type="number" name="km_actual" min="0"
                        value="<?php echo $vehiculo['km_actual'] ?? ''; ?>" placeholder="Ej: 125000">
                </div>
                <div class="form-group">
                    <label>Próximo Service (Km)</label>
                    <input type="number" name="proximo_service_km" min="0"
                        value="<?php echo $vehiculo['proximo_service_km'] ?? ''; ?>" placeholder="Ej: 130000">
                </div>
                <div class="form-group">
                    <label>Fecha Último Inventario</label>
                    <input type="date" name="fecha_ultimo_inventario"
                        value="<?php echo $vehiculo['fecha_ultimo_inventario'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Costo de Reposición ($)</label>
                    <input type="number" name="costo_reposicion" step="0.01" min="0"
                        value="<?php echo $vehiculo['costo_reposicion'] ?? ''; ?>" placeholder="Ej: 15000000">
                </div>
            </div>
        </div>

        <!-- ═══ SECCIÓN 7: FILTROS Y ACEITES ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-oil-can"></i> 7. Filtros y Aceites</h3>
            <p style="color: var(--text-muted); font-size: 0.85em; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Complete código, marca, equivalencia y precio para cada elemento. Los
                precios se cargan en USD y/o ARS.
            </p>

            <!-- Filtros -->
            <div class="subsection-title"><i class="fas fa-filter"></i> Filtros</div>
            <?php foreach ($tiposMantenimiento as $tm):
                if ($tm['group'] !== 'filtros')
                    continue;
                $existing = $mantByType[$tm['key']] ?? [];
                $prefix = 'mant_' . str_replace(' ', '_', $tm['key']);
                ?>
                <div class="mant-row">
                    <div class="mant-label">
                        <i class="fas <?php echo $tm['icon']; ?>"></i>
                        <?php echo $tm['key']; ?>
                    </div>
                    <div class="mant-fields">
                        <input type="hidden" name="<?php echo $prefix; ?>_tipo"
                            value="<?php echo $tm['key']; ?>">
                        <input type="text" name="<?php echo $prefix; ?>_codigo"
                            value="<?php echo htmlspecialchars($existing['codigo'] ?? ''); ?>" placeholder="Código"
                            class="mant-input">
                        <input type="text" name="<?php echo $prefix; ?>_marca"
                            value="<?php echo htmlspecialchars($existing['marca'] ?? ''); ?>" placeholder="Marca"
                            class="mant-input">
                        <input type="text" name="<?php echo $prefix; ?>_equivalencia"
                            value="<?php echo htmlspecialchars($existing['equivalencia'] ?? ''); ?>"
                            placeholder="Equivalencia" class="mant-input mant-input-wide">
                        <input type="number" name="<?php echo $prefix; ?>_precio_usd" step="0.01" min="0"
                            value="<?php echo $existing['precio_usd'] ?? ''; ?>" placeholder="USD" class="mant-input mant-input-price">
                        <input type="number" name="<?php echo $prefix; ?>_precio_ars" step="0.01" min="0"
                            value="<?php echo $existing['precio_ars'] ?? ''; ?>" placeholder="ARS" class="mant-input mant-input-price">
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Aceites -->
            <div class="subsection-title" style="margin-top: 20px;"><i class="fas fa-oil-can"></i> Aceites y
                Lubricantes</div>
            <?php foreach ($tiposMantenimiento as $tm):
                if ($tm['group'] !== 'aceites')
                    continue;
                $existing = $mantByType[$tm['key']] ?? [];
                $prefix = 'mant_' . str_replace(' ', '_', $tm['key']);
                ?>
                <div class="mant-row">
                    <div class="mant-label">
                        <i class="fas <?php echo $tm['icon']; ?>"></i>
                        <?php echo $tm['key']; ?>
                    </div>
                    <div class="mant-fields">
                        <input type="hidden" name="<?php echo $prefix; ?>_tipo"
                            value="<?php echo $tm['key']; ?>">
                        <input type="text" name="<?php echo $prefix; ?>_tipo_aceite"
                            value="<?php echo htmlspecialchars($existing['tipo_aceite'] ?? ''); ?>" placeholder="Tipo"
                            class="mant-input">
                        <input type="number" name="<?php echo $prefix; ?>_cantidad" step="0.1" min="0"
                            value="<?php echo $existing['cantidad'] ?? ''; ?>" placeholder="Litros"
                            class="mant-input mant-input-sm">
                        <input type="text" name="<?php echo $prefix; ?>_codigo"
                            value="<?php echo htmlspecialchars($existing['codigo'] ?? ''); ?>" placeholder="Código"
                            class="mant-input">
                        <input type="text" name="<?php echo $prefix; ?>_marca"
                            value="<?php echo htmlspecialchars($existing['marca'] ?? ''); ?>" placeholder="Marca"
                            class="mant-input">
                        <input type="number" name="<?php echo $prefix; ?>_precio_usd" step="0.01" min="0"
                            value="<?php echo $existing['precio_usd'] ?? ''; ?>" placeholder="USD" class="mant-input mant-input-price">
                        <input type="number" name="<?php echo $prefix; ?>_precio_ars" step="0.01" min="0"
                            value="<?php echo $existing['precio_ars'] ?? ''; ?>" placeholder="ARS" class="mant-input mant-input-price">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ═══ SECCIÓN 8: REPARACIONES ═══ -->
        <?php if ($isEdit): ?>
            <div class="form-section">
                <h3><i class="fas fa-tools"></i> 8. Historial de Reparaciones</h3>

                <div id="reparacionesList">
                    <?php if (empty($reparaciones)): ?>
                        <div class="empty-state" id="emptyReparaciones">
                            <i class="fas fa-clipboard-check" style="font-size: 2em; opacity: 0.3;"></i>
                            <p>Sin reparaciones registradas</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reparaciones as $r): ?>
                            <div class="reparacion-card" id="rep_<?php echo $r['id_reparacion']; ?>">
                                <div class="rep-header">
                                    <div>
                                        <strong><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></strong>
                                        <span class="rep-cost">
                                            <?php echo $r['moneda']; ?>
                                            $<?php echo number_format($r['costo'] ?? 0, 2, ',', '.'); ?>
                                        </span>
                                    </div>
                                    <button type="button" class="btn-delete-rep"
                                        onclick="deleteReparacion(<?php echo $r['id_reparacion']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="rep-body">
                                    <p><?php echo htmlspecialchars($r['descripcion']); ?></p>
                                    <div class="rep-details">
                                        <?php if ($r['realizado_por']): ?>
                                            <span><i class="fas fa-user-cog"></i>
                                                <?php echo htmlspecialchars($r['realizado_por']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($r['tiempo_horas']): ?>
                                            <span><i class="fas fa-clock"></i> <?php echo $r['tiempo_horas']; ?>hs</span>
                                        <?php endif; ?>
                                        <?php if ($r['codigos_repuestos']): ?>
                                            <span><i class="fas fa-barcode"></i>
                                                <?php echo htmlspecialchars($r['codigos_repuestos']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($r['proveedor_repuestos']): ?>
                                            <span><i class="fas fa-store"></i>
                                                <?php echo htmlspecialchars($r['proveedor_repuestos']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Add Repair Form -->
                <div class="add-repair-form" id="addRepairForm" style="display: none;">
                    <div class="subsection-title"><i class="fas fa-plus-circle"></i> Nueva Reparación</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha *</label>
                            <input type="date" id="rep_fecha" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Realizado por</label>
                            <input type="text" id="rep_realizado_por" placeholder="Nombre del mecánico/taller">
                        </div>
                        <div class="form-group">
                            <label>Costo ($)</label>
                            <input type="number" id="rep_costo" step="0.01" min="0" placeholder="Monto">
                        </div>
                        <div class="form-group">
                            <label>Moneda</label>
                            <select id="rep_moneda">
                                <option value="ARS">ARS (Pesos)</option>
                                <option value="USD">USD (Dólares)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tiempo (horas)</label>
                            <input type="number" id="rep_tiempo" step="0.5" min="0" placeholder="Ej: 4.5">
                        </div>
                        <div class="form-group">
                            <label>Proveedor Repuestos</label>
                            <input type="text" id="rep_proveedor" placeholder="Dónde se compraron">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Descripción del trabajo *</label>
                        <textarea id="rep_descripcion" rows="2"
                            placeholder="Qué se reparó, cambió o ajustó..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Códigos de Repuestos</label>
                        <input type="text" id="rep_codigos"
                            placeholder="Ej: FLT-001, ACE-15W40, DSC-024 (separados por coma)">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
                        <button type="button" class="btn btn-outline" onclick="toggleRepairForm()">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="saveReparacion()">
                            <i class="fas fa-save"></i> Guardar Reparación
                        </button>
                    </div>
                </div>

                <button type="button" class="btn btn-outline" id="btnAddRepair" onclick="toggleRepairForm()"
                    style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-plus"></i> Agregar Reparación
                </button>
            </div>
        <?php endif; ?>

        <!-- ═══ SECCIÓN 9: OBSERVACIONES ═══ -->
        <div class="form-section">
            <h3><i class="fas fa-sticky-note"></i> <?php echo $isEdit ? '9' : '8'; ?>. Observaciones</h3>
            <div class="form-group" style="margin-bottom: 0;">
                <textarea name="observaciones" rows="3"
                    placeholder="Notas adicionales sobre el vehículo..."><?php echo htmlspecialchars($vehiculo['observaciones'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- BOTONES -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <a href="index.php" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                <?php echo $isEdit ? 'Guardar Cambios' : 'Crear Vehículo'; ?>
            </button>
        </div>
    </form>
</div>

<style>
    /* Existing form styles */
    .form-section {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(100, 181, 246, 0.15);
        border-left: 4px solid var(--color-primary);
        box-shadow: var(--shadow-sm);
    }

    [data-theme="light"] .form-section {
        background: #f8f9fa;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--color-primary);
    }

    .form-section h3 {
        margin: 0 0 15px 0;
        font-size: 1.1em;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    [data-theme="light"] .form-section h3 {
        color: #2c3e50;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 0.85em;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    [data-theme="light"] .form-group label {
        color: #555;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 12px 14px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        font-size: 0.95em;
        background: var(--bg-tertiary);
        color: var(--text-primary);
        transition: all 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.2);
        background: var(--bg-secondary);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    [data-theme="light"] .form-group input,
    [data-theme="light"] .form-group select,
    [data-theme="light"] .form-group textarea {
        background: #ffffff;
        border: 1px solid #d1d5db;
        color: #333;
    }

    [data-theme="light"] .form-group input:focus,
    [data-theme="light"] .form-group select:focus,
    [data-theme="light"] .form-group textarea:focus {
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* Subsection titles */
    .subsection-title {
        font-size: 0.9em;
        font-weight: 700;
        color: var(--accent-primary);
        margin: 15px 0 12px 0;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--bg-secondary);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Toggle Switch */
    .toggle-switch-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--bg-secondary);
        border-radius: 26px;
        transition: 0.3s;
    }

    .toggle-slider:before {
        content: "";
        position: absolute;
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
    }

    .toggle-switch input:checked+.toggle-slider {
        background: #10b981;
    }

    .toggle-switch input:checked+.toggle-slider:before {
        transform: translateX(24px);
    }

    .toggle-label {
        font-weight: 700;
        font-size: 0.9em;
        color: var(--text-primary);
    }

    /* Maintenance rows */
    .mant-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        margin-bottom: 6px;
        background: var(--bg-tertiary);
        border-radius: 8px;
        border: 1px solid var(--bg-secondary);
    }

    .mant-label {
        min-width: 180px;
        font-size: 0.85em;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .mant-fields {
        display: flex;
        gap: 8px;
        flex: 1;
        flex-wrap: wrap;
    }

    .mant-input {
        padding: 8px 10px;
        border: 1px solid var(--bg-secondary);
        border-radius: 6px;
        font-size: 0.85em;
        background: var(--bg-card);
        color: var(--text-primary);
        width: 100px;
        transition: all 0.2s;
    }

    .mant-input:focus {
        border-color: var(--accent-primary);
        outline: none;
    }

    .mant-input-wide {
        width: 140px;
    }

    .mant-input-price {
        width: 80px;
        text-align: right;
    }

    .mant-input-sm {
        width: 70px;
    }

    [data-theme="light"] .mant-row {
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    [data-theme="light"] .mant-input {
        background: white;
        border-color: #d1d5db;
    }

    /* Reparaciones */
    .reparacion-card {
        background: var(--bg-tertiary);
        border: 1px solid var(--bg-secondary);
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }

    .reparacion-card:hover {
        border-color: var(--accent-primary);
    }

    .rep-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .rep-cost {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.85em;
        font-weight: 600;
        margin-left: 10px;
    }

    .rep-body p {
        margin: 0 0 8px 0;
        color: var(--text-primary);
        font-size: 0.92em;
    }

    .rep-details {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .rep-details span {
        font-size: 0.8em;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-delete-rep {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .btn-delete-rep:hover {
        background: #ef4444;
        color: white;
    }

    .add-repair-form {
        background: var(--bg-tertiary);
        border: 2px dashed var(--accent-primary);
        border-radius: 10px;
        padding: 18px;
        margin-top: 15px;
    }

    .empty-state {
        text-align: center;
        padding: 20px;
        color: var(--text-muted);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .mant-row {
            flex-direction: column;
            align-items: stretch;
        }

        .mant-label {
            min-width: unset;
        }

        .mant-fields {
            flex-wrap: wrap;
        }

        .mant-input {
            flex: 1;
            min-width: 70px;
        }
    }
</style>

<script>
    // ── Toggle Gestya Fields ──
    function toggleGestya(checkbox) {
        const fields = document.querySelectorAll('.gestya-field');
        const label = document.getElementById('gestyaLabel');
        fields.forEach(f => f.style.display = checkbox.checked ? '' : 'none');
        label.textContent = checkbox.checked ? 'SÍ' : 'NO';
    }

    // ── Toggle Franquicia Field ──
    document.getElementById('selectCobertura')?.addEventListener('change', function () {
        const franquiciaGroup = document.getElementById('franquiciaGroup');
        franquiciaGroup.style.display = this.value === 'Todo Riesgo' ? '' : 'none';
    });

    // ── Repair Form Toggle ──
    function toggleRepairForm() {
        const form = document.getElementById('addRepairForm');
        const btn = document.getElementById('btnAddRepair');
        const isVisible = form.style.display !== 'none';
        form.style.display = isVisible ? 'none' : 'block';
        btn.style.display = isVisible ? '' : 'none';
    }

    // ── Save Repair via AJAX ──
    function saveReparacion() {
        const fecha = document.getElementById('rep_fecha').value;
        const descripcion = document.getElementById('rep_descripcion').value;

        if (!fecha || !descripcion) {
            alert('Fecha y descripción son obligatorios');
            return;
        }

        const data = {
            action: 'add',
            id_vehiculo: '<?php echo $_GET['id'] ?? ''; ?>',
            fecha: fecha,
            descripcion: descripcion,
            realizado_por: document.getElementById('rep_realizado_por').value,
            costo: document.getElementById('rep_costo').value,
            moneda: document.getElementById('rep_moneda').value,
            tiempo_horas: document.getElementById('rep_tiempo').value,
            codigos_repuestos: document.getElementById('rep_codigos').value,
            proveedor_repuestos: document.getElementById('rep_proveedor').value
        };

        fetch('api_reparaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'No se pudo guardar'));
                }
            })
            .catch(() => alert('Error de conexión'));
    }

    // ── Delete Repair ──
    function deleteReparacion(id) {
        if (!confirm('¿Eliminar esta reparación?')) return;

        fetch('api_reparaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id_reparacion: id })
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    const el = document.getElementById('rep_' + id);
                    if (el) {
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.95)';
                        el.style.transition = 'all 0.3s';
                        setTimeout(() => el.remove(), 300);
                    }
                } else {
                    alert('Error: ' + (result.message || 'No se pudo eliminar'));
                }
            })
            .catch(() => alert('Error de conexión'));
    }
</script>

<?php require_once '../../includes/footer.php'; ?>