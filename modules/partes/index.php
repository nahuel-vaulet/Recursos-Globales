<?php
/**
 * Módulo: Partes Diarios y Consumo
 * 
 * Sistema de registro de ejecución de ODTs para Jefes de Cuadrilla.
 * Incluye: tiempos, trabajos, consumo de materiales, personal y evidencia fotográfica.
 * 
 * @author Sistema ERP - Recursos Globales
 * @version 1.0
 */

require_once '../../config/database.php';
require_once '../../includes/header.php';

// ============================================================================
// OBTENER DATOS DEL USUARIO
// ============================================================================
$usuarioActual = obtenerUsuarioActual();
$esJefeCuadrilla = ($usuarioActual['rol'] ?? '') === 'JefeCuadrilla';
$idCuadrillaUsuario = $usuarioActual['id_cuadrilla'] ?? null;

// ============================================================================
// CONSULTAS DE DATOS
// ============================================================================

// 1. Obtener ODTs programadas para la cuadrilla del usuario (o todas si es admin)
$sqlOdts = "
    SELECT o.id_odt, o.nro_odt_assa, o.direccion, o.estado_gestion, 
           t.nombre AS tipo_trabajo, ps.fecha_programada, ps.id_cuadrilla
    FROM ODT_Maestro o
    LEFT JOIN tipologias t ON o.id_tipologia = t.id_tipologia
    LEFT JOIN programacion_semanal ps ON o.id_odt = ps.id_odt
    WHERE o.estado_gestion IN ('Programado', 'Sin Programar')
";
if ($esJefeCuadrilla && $idCuadrillaUsuario) {
    $sqlOdts .= " AND ps.id_cuadrilla = " . intval($idCuadrillaUsuario);
}
$sqlOdts .= " ORDER BY ps.fecha_programada DESC, o.nro_odt_assa ASC";
$odts = $pdo->query($sqlOdts)->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener cuadrillas
$cuadrillas = $pdo->query("SELECT id_cuadrilla, nombre_cuadrilla FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener tipos de trabajos (ex tipologías)
$tipos_trabajos = $pdo->query("SELECT id_tipologia, nombre, codigo_trabajo, unidad_medida FROM tipologias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// 4. Obtener personal (filtrado por cuadrilla si es jefe)
$sqlPersonal = "SELECT id_personal, nombre_apellido, rol, id_cuadrilla FROM personal";
if ($esJefeCuadrilla && $idCuadrillaUsuario) {
    $sqlPersonal .= " WHERE id_cuadrilla = " . intval($idCuadrillaUsuario);
}
$sqlPersonal .= " ORDER BY nombre_apellido";
$personal = $pdo->query($sqlPersonal)->fetchAll(PDO::FETCH_ASSOC);

// 5. Obtener vehículos
$vehiculos = $pdo->query("SELECT id_vehiculo, patente, marca, modelo, tipo FROM vehiculos WHERE estado = 'Operativo' ORDER BY patente")->fetchAll(PDO::FETCH_ASSOC);

// 6. Obtener materiales con stock de cuadrilla
$sqlMateriales = "
    SELECT m.id_material, m.codigo, m.nombre, m.unidad_medida,
           COALESCE(sc.cantidad, 0) AS stock_disponible
    FROM maestro_materiales m
    LEFT JOIN stock_cuadrilla sc ON m.id_material = sc.id_material
";
if ($esJefeCuadrilla && $idCuadrillaUsuario) {
    $sqlMateriales .= " AND sc.id_cuadrilla = " . intval($idCuadrillaUsuario);
}
$sqlMateriales .= " ORDER BY m.nombre";
$materiales = $pdo->query($sqlMateriales)->fetchAll(PDO::FETCH_ASSOC);

// 7. Obtener partes recientes
$sqlPartes = "
    SELECT pd.*, o.nro_odt_assa, c.nombre_cuadrilla, t.nombre AS tipo_trabajo_nombre,
           (SELECT COUNT(*) FROM partes_fotos pf WHERE pf.id_parte = pd.id_parte) AS cant_fotos
    FROM partes_diarios pd
    LEFT JOIN ODT_Maestro o ON pd.id_odt = o.id_odt
    LEFT JOIN cuadrillas c ON pd.id_cuadrilla = c.id_cuadrilla
    LEFT JOIN tipos_trabajos t ON pd.id_tipologia = t.id_tipologia
";
if ($esJefeCuadrilla && $idCuadrillaUsuario) {
    $sqlPartes .= " WHERE pd.id_cuadrilla = " . intval($idCuadrillaUsuario);
}
$sqlPartes .= " ORDER BY pd.fecha_ejecucion DESC, pd.id_parte DESC LIMIT 50";
$partes = $pdo->query($sqlPartes)->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* ========================================
       MÓDULO PARTES DIARIOS - Estilos
       Usa variables globales del tema
       ======================================== */

    .partes-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: var(--spacing-md);
    }

    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-lg);
        padding: var(--spacing-lg);
        background: var(--bg-card);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
        box-shadow: var(--shadow-sm);
    }

    [data-theme="light"] .module-header {
        border-color: #e2e8f0;
    }

    .module-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .module-subtitle {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    /* Layout principal - Flujo vertical */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
    }

    .form-grid .card {
        margin-bottom: 0;
    }

    /* Sección de fotos y observaciones - ancho completo */
    .form-bottom {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
    }

    .form-bottom .card {
        margin-bottom: 0;
    }

    @media (max-width: 1400px) {
        .form-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 1024px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-bottom {
            grid-template-columns: 1fr;
        }
    }

    /* Cards genéricas - Tema adaptativo */
    .card {
        background: var(--bg-card);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
        margin-bottom: var(--spacing-lg);
        box-shadow: var(--shadow-sm);
    }

    [data-theme="light"] .card {
        background: #ffffff;
        border-color: #e2e8f0;
        box-shadow: var(--shadow-md);
    }

    .card-header {
        padding: var(--spacing-md) var(--spacing-lg);
        border-bottom: 1px solid rgba(100, 181, 246, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    [data-theme="light"] .card-header {
        border-bottom-color: #e2e8f0;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--accent-primary);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .card-body {
        padding: var(--spacing-lg);
    }

    /* Formulario */
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-md);
    }

    .form-group {
        margin-bottom: var(--spacing-md);
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin-bottom: var(--spacing-xs);
    }

    .form-group label .required {
        color: var(--color-danger);
    }

    .form-control {
        width: 100%;
        padding: 10px var(--spacing-md);
        background: var(--bg-tertiary);
        border: 1px solid rgba(100, 181, 246, 0.2);
        border-radius: var(--border-radius-sm);
        font-size: 0.95rem;
        font-family: inherit;
        color: var(--text-primary);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.15);
    }

    /* Modo Claro - Formularios */
    [data-theme="light"] .form-control {
        background: #ffffff;
        border: 1px solid #d1d5db;
        color: var(--text-primary);
    }

    [data-theme="light"] .form-control:focus {
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* Métricas */
    .metrics-section {
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-sm);
        padding: var(--spacing-md);
        margin-bottom: var(--spacing-md);
    }

    [data-theme="light"] .metrics-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .metrics-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--spacing-sm);
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-sm);
    }

    .metric-input {
        text-align: center;
    }

    .metric-input label {
        font-size: 0.75rem !important;
        color: var(--text-muted) !important;
    }

    .volume-result {
        padding: var(--spacing-md);
        background: linear-gradient(135deg, var(--accent-primary), #1d4ed8);
        border-radius: var(--border-radius-sm);
        text-align: center;
        color: white;
        margin-top: var(--spacing-sm);
    }

    .volume-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .volume-unit {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    /* Selector múltiple de personal */
    .multi-select {
        display: flex;
        flex-wrap: wrap;
        gap: var(--spacing-sm);
        max-height: 200px;
        overflow-y: auto;
        padding: var(--spacing-sm);
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-sm);
        border: 1px solid rgba(100, 181, 246, 0.2);
    }

    [data-theme="light"] .multi-select {
        background: #f8fafc;
        border-color: #d1d5db;
    }

    .multi-select-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: 6px 12px;
        background: var(--bg-secondary);
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.85rem;
        color: var(--text-primary);
        border: 1px solid transparent;
    }

    [data-theme="light"] .multi-select-item {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    .multi-select-item:hover {
        background: rgba(100, 181, 246, 0.15);
    }

    .multi-select-item.selected {
        background: var(--accent-primary);
        color: white;
        border-color: var(--accent-primary);
    }

    .multi-select-item input {
        display: none;
    }

    /* Grilla de materiales */
    .materials-grid {
        border: 1px solid rgba(100, 181, 246, 0.2);
        border-radius: var(--border-radius-sm);
        overflow: hidden;
    }

    [data-theme="light"] .materials-grid {
        border-color: #e2e8f0;
    }

    .materials-header {
        display: grid;
        grid-template-columns: 1fr 100px 80px 50px;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-md);
        background: var(--bg-tertiary);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
    }

    [data-theme="light"] .materials-header {
        background: #f1f5f9;
    }

    .material-row {
        display: grid;
        grid-template-columns: 1fr 100px 80px 50px;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-md);
        align-items: center;
        border-top: 1px solid rgba(100, 181, 246, 0.1);
        background: var(--bg-card);
    }

    [data-theme="light"] .material-row {
        border-top-color: #e2e8f0;
        background: #ffffff;
    }

    .material-row select,
    .material-row input {
        padding: 8px;
        font-size: 0.9rem;
    }

    .btn-remove {
        width: 32px;
        height: 32px;
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: none;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-add-row {
        width: 100%;
        padding: var(--spacing-sm);
        background: rgba(100, 181, 246, 0.1);
        border: 1px dashed var(--accent-primary);
        color: var(--accent-primary);
        border-radius: 0;
        cursor: pointer;
        font-size: 0.9rem;
        font-family: inherit;
    }

    .btn-add-row:hover {
        background: rgba(100, 181, 246, 0.2);
    }

    /* Upload de fotos */
    .photos-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-md);
    }

    .photo-slot {
        aspect-ratio: 4/3;
        border: 2px dashed rgba(100, 181, 246, 0.3);
        border-radius: var(--border-radius-md);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
        background: var(--bg-tertiary);
    }

    [data-theme="light"] .photo-slot {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .photo-slot:hover {
        border-color: var(--accent-primary);
        background: rgba(100, 181, 246, 0.05);
    }

    .photo-slot.has-image {
        border-style: solid;
        border-color: var(--color-success);
    }

    .photo-slot input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .photo-slot i {
        font-size: 2rem;
        color: var(--accent-primary);
        margin-bottom: var(--spacing-xs);
    }

    .photo-slot .label {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .photo-slot .sublabel {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .photo-slot img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .photo-slot .remove-photo {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
        display: none;
    }

    .photo-slot.has-image .remove-photo {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Tiempo calculado */
    .time-result {
        padding: var(--spacing-md);
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-sm);
        text-align: center;
        border-left: 4px solid var(--accent-primary);
    }

    [data-theme="light"] .time-result {
        background: #f8fafc;
    }

    .time-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--accent-primary);
    }

    .time-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    /* Tabla de partes */
    .table-container {
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(100, 181, 246, 0.3) transparent;
    }

    .table-container::-webkit-scrollbar {
        height: 6px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: rgba(100, 181, 246, 0.3);
        border-radius: 3px;
    }

    [data-theme="light"] .table-container {
        scrollbar-color: rgba(37, 99, 235, 0.3) transparent;
    }

    [data-theme="light"] .table-container::-webkit-scrollbar-thumb {
        background: rgba(37, 99, 235, 0.3);
    }

    /* Estilos de tabla */
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: var(--bg-tertiary);
    }

    [data-theme="light"] .data-table thead {
        background: #f1f5f9;
    }

    .data-table th {
        padding: 12px var(--spacing-md);
        text-align: left;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: var(--text-secondary);
        border-bottom: 1px solid rgba(100, 181, 246, 0.1);
    }

    [data-theme="light"] .data-table th {
        border-bottom-color: #e2e8f0;
        color: #64748b;
    }

    .data-table td {
        padding: 12px var(--spacing-md);
        border-bottom: 1px solid rgba(100, 181, 246, 0.05);
        color: var(--text-primary);
        font-size: 0.9rem;
    }

    [data-theme="light"] .data-table td {
        border-bottom-color: #f1f5f9;
    }

    .data-table tbody tr:hover {
        background: rgba(100, 181, 246, 0.05);
    }

    [data-theme="light"] .data-table tbody tr:hover {
        background: #f8fafc;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-Borrador {
        background: rgba(158, 158, 158, 0.15);
        color: #9e9e9e;
    }

    .status-Enviado {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }

    .status-Aprobado {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .status-Rechazado {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    /* Botones */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-sm);
        padding: 12px var(--spacing-lg);
        font-size: 1rem;
        font-weight: 500;
        font-family: inherit;
        border: none;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(145deg, var(--accent-primary) 0%, #1d4ed8 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(100, 181, 246, 0.3);
    }

    .btn-success {
        background: linear-gradient(145deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--accent-primary);
        color: var(--accent-primary);
    }

    .btn-outline:hover {
        background: rgba(100, 181, 246, 0.1);
    }

    .btn-block {
        width: 100%;
    }

    .btn-lg {
        padding: 16px var(--spacing-xl);
        font-size: 1.1rem;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        transition: all 0.2s;
    }

    .btn-icon:hover {
        transform: scale(1.1);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .photos-grid {
            grid-template-columns: 1fr;
        }

        .materials-header,
        .material-row {
            grid-template-columns: 1fr 80px 60px 40px;
            font-size: 0.8rem;
        }

        .module-header {
            flex-direction: column;
            gap: var(--spacing-md);
            text-align: center;
        }
    }
</style>

<div class="partes-container">
    <!-- Header -->
    <div class="module-header">
        <div>
            <h1 class="module-title">Partes Diarios</h1>
            <p class="module-subtitle">Registro de Ejecución y Consumo de Recursos</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="resetForm()">
                <i class="fas fa-plus"></i> Nuevo Parte
            </button>
        </div>
    </div>

    <form id="formParte" enctype="multipart/form-data">
        <input type="hidden" name="id_parte" id="id_parte" value="">

        <!-- Fila 1: 3 columnas principales -->
        <div class="form-grid">
            <!-- Columna 1: ODT y Jornada -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt"></i> ODT y Jornada
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>ODT Asignada <span class="required">*</span></label>
                        <select name="id_odt" id="id_odt" class="form-control" required>
                            <option value="">Seleccione ODT...</option>
                            <?php foreach ($odts as $odt): ?>
                                <option value="<?php echo $odt['id_odt']; ?>"
                                    data-tipo-trabajo="<?php echo $odt['tipo_trabajo']; ?>">
                                    <?php echo htmlspecialchars($odt['nro_odt_assa']); ?> -
                                    <?php echo htmlspecialchars($odt['direccion'] ?? 'Sin dirección'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!$esJefeCuadrilla): ?>
                        <div class="form-group">
                            <label>Cuadrilla <span class="required">*</span></label>
                            <select name="id_cuadrilla" id="id_cuadrilla" class="form-control" required>
                                <option value="">Seleccione Cuadrilla...</option>
                                <?php foreach ($cuadrillas as $c): ?>
                                    <option value="<?php echo $c['id_cuadrilla']; ?>">
                                        <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="id_cuadrilla" value="<?php echo $idCuadrillaUsuario; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Fecha Ejecución <span class="required">*</span></label>
                        <input type="date" name="fecha_ejecucion" id="fecha_ejecucion" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Inicio <span class="required">*</span></label>
                            <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" value="08:00"
                                required onchange="calcularTiempo()">
                        </div>
                        <div class="form-group">
                            <label>Fin <span class="required">*</span></label>
                            <input type="time" name="hora_fin" id="hora_fin" class="form-control" value="17:00" required
                                onchange="calcularTiempo()">
                        </div>
                    </div>

                    <div class="time-result">
                        <div class="time-value" id="tiempoCalculado">9h 00m</div>
                        <div class="time-label">Tiempo de Ejecución</div>
                    </div>
                </div>
            </div>

            <!-- Columna 2: Trabajo Realizado -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-hard-hat"></i> Trabajo Realizado
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Tipo de Trabajo <span class="required">*</span></label>
                        <select name="id_tipo_trabajo" id="id_tipo_trabajo" class="form-control" required
                            onchange="updateUnidadMedida()">
                            <option value="">Seleccione tipo...</option>
                            <?php foreach ($tipos_trabajos as $t): ?>
                                <option value="<?php echo $t['id_tipologia']; ?>"
                                    data-unidad="<?php echo $t['unidad_medida']; ?>">
                                    <?php echo htmlspecialchars($t['nombre']); ?>
                                    <?php if ($t['codigo_trabajo']): ?>(<?php echo $t['codigo_trabajo']; ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="metrics-section">
                        <div class="metrics-title">Métricas de Obra</div>
                        <div class="metrics-grid">
                            <div class="form-group metric-input">
                                <label>Largo (m)</label>
                                <input type="number" name="largo" id="largo" class="form-control" step="0.01" min="0"
                                    value="0" onchange="calcularVolumen()">
                            </div>
                            <div class="form-group metric-input">
                                <label>Ancho (m)</label>
                                <input type="number" name="ancho" id="ancho" class="form-control" step="0.01" min="0"
                                    value="0" onchange="calcularVolumen()">
                            </div>
                            <div class="form-group metric-input">
                                <label>Prof. (m)</label>
                                <input type="number" name="profundidad" id="profundidad" class="form-control"
                                    step="0.01" min="0" value="0" onchange="calcularVolumen()">
                            </div>
                        </div>

                        <div class="volume-result">
                            <div class="volume-value" id="volumenCalculado">0.00</div>
                            <div class="volume-unit" id="unidadVolumen">M²</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Vehículo Utilizado</label>
                        <select name="id_vehiculo" id="id_vehiculo" class="form-control">
                            <option value="">Ninguno / No aplica</option>
                            <?php foreach ($vehiculos as $v): ?>
                                <option value="<?php echo $v['id_vehiculo']; ?>">
                                    <?php echo htmlspecialchars($v['patente']); ?> -
                                    <?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Columna 3: Personal Interviniente -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Personal
                    </h3>
                </div>
                <div class="card-body">
                    <div class="multi-select" id="personalSelect">
                        <?php foreach ($personal as $p): ?>
                            <label class="multi-select-item" data-id="<?php echo $p['id_personal']; ?>">
                                <input type="checkbox" name="personal[]" value="<?php echo $p['id_personal']; ?>">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($p['nombre_apellido']); ?>
                                <small style="opacity: 0.7;">(<?php echo $p['rol']; ?>)</small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Fotos (2/3) + Materiales (1/3) -->
        <div class="form-bottom">
            <!-- Evidencia Fotográfica -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-camera"></i> Evidencia Fotográfica <span class="required">*</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="photos-grid">
                        <div class="photo-slot" id="slotInicio">
                            <input type="file" name="foto_inicio" accept="image/*" capture="environment"
                                onchange="previewPhoto(this, 'Inicio')">
                            <button type="button" class="remove-photo" onclick="removePhoto('Inicio')">&times;</button>
                            <i class="fas fa-camera"></i>
                            <span class="label">INICIO</span>
                            <span class="sublabel">Antes de trabajar</span>
                        </div>

                        <div class="photo-slot" id="slotProceso">
                            <input type="file" name="foto_proceso" accept="image/*" capture="environment"
                                onchange="previewPhoto(this, 'Proceso')">
                            <button type="button" class="remove-photo" onclick="removePhoto('Proceso')">&times;</button>
                            <i class="fas fa-camera"></i>
                            <span class="label">PROCESO</span>
                            <span class="sublabel">Durante el trabajo</span>
                        </div>

                        <div class="photo-slot" id="slotFin">
                            <input type="file" name="foto_fin" accept="image/*" capture="environment"
                                onchange="previewPhoto(this, 'Fin')">
                            <button type="button" class="remove-photo" onclick="removePhoto('Fin')">&times;</button>
                            <i class="fas fa-camera"></i>
                            <span class="label">FIN</span>
                            <span class="sublabel">Trabajo terminado</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Materiales Consumidos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-boxes"></i> Materiales
                    </h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="materials-grid">
                        <div class="materials-header">
                            <span>Material</span>
                            <span>Stock</span>
                            <span>Cant</span>
                            <span></span>
                        </div>
                        <div id="materialesContainer">
                            <!-- Rows dinámicas -->
                        </div>
                        <button type="button" class="btn-add-row" onclick="addMaterialRow()">
                            <i class="fas fa-plus"></i> Agregar Material
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 3: Observaciones y Acciones -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-comment-alt"></i> Observaciones y Envío
                </h3>
            </div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="2"
                        placeholder="Notas adicionales sobre el trabajo realizado..."></textarea>
                </div>

                <div style="display: flex; gap: var(--spacing-md);">
                    <button type="button" class="btn btn-outline" onclick="guardarBorrador()" style="flex: 1;">
                        <i class="fas fa-save"></i> Guardar Borrador
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" style="flex: 2;">
                        <i class="fas fa-paper-plane"></i> Enviar Parte
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Tabla de Partes Recientes -->
    <div class="card" style="margin-top: var(--spacing-lg);">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-clipboard-list"></i> Partes Recientes
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>ODT</th>
                            <th>Trabajo</th>
                            <th>Volumen</th>
                            <th>Tiempo</th>
                            <th>Fotos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($partes)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-clipboard"
                                        style="font-size: 2rem; opacity: 0.5; display: block; margin-bottom: 10px;"></i>
                                    No hay partes registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($partes as $p): ?>
                                <tr data-id="<?php echo $p['id_parte']; ?>">
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_ejecucion'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nro_odt_assa']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['tipo_trabajo_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo number_format($p['volumen_calculado'] ?? 0, 2); ?>
                                        <?php echo $p['unidad_volumen'] ?? ''; ?>
                                    </td>
                                    <td>
                                        <?php echo floor(($p['tiempo_ejecucion_real'] ?? 0) / 60); ?>h
                                        <?php echo ($p['tiempo_ejecucion_real'] ?? 0) % 60; ?>m
                                    </td>
                                    <td>
                                        <span
                                            style="color: <?php echo ($p['cant_fotos'] >= 3) ? 'var(--color-success)' : 'var(--color-warning)'; ?>">
                                            <?php echo $p['cant_fotos']; ?>/3
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $p['estado']; ?>">
                                            <?php echo $p['estado']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-icon view" onclick="loadParte(<?php echo $p['id_parte']; ?>)"
                                            title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // ============================================================================
    // VARIABLES GLOBALES
    // ============================================================================
    const materiales = <?php echo json_encode($materiales); ?>;
    let materialRowCount = 0;

    // ============================================================================
    // CÁLCULOS AUTOMÁTICOS
    // ============================================================================

    function calcularTiempo() {
        const inicio = document.getElementById('hora_inicio').value;
        const fin = document.getElementById('hora_fin').value;

        if (inicio && fin) {
            const [hi, mi] = inicio.split(':').map(Number);
            const [hf, mf] = fin.split(':').map(Number);

            let minutos = (hf * 60 + mf) - (hi * 60 + mi);
            if (minutos < 0) minutos += 24 * 60; // Si cruza medianoche

            const horas = Math.floor(minutos / 60);
            const mins = minutos % 60;

            document.getElementById('tiempoCalculado').textContent = `${horas}h ${mins.toString().padStart(2, '0')}m`;
        }
    }

    function calcularVolumen() {
        const largo = parseFloat(document.getElementById('largo').value) || 0;
        const ancho = parseFloat(document.getElementById('ancho').value) || 0;
        const profundidad = parseFloat(document.getElementById('profundidad').value) || 0;

        let volumen, unidad;

        if (profundidad > 0) {
            volumen = largo * ancho * profundidad;
            unidad = 'M³';
        } else {
            volumen = largo * ancho;
            unidad = 'M²';
        }

        document.getElementById('volumenCalculado').textContent = volumen.toFixed(2);
        document.getElementById('unidadVolumen').textContent = unidad;
    }

    function updateUnidadMedida() {
        const select = document.getElementById('id_tipo_trabajo');
        const option = select.options[select.selectedIndex];
        const unidad = option?.dataset.unidad || '';

        // Podría usarse para preconfigurar profundidad según tipo
    }

    // ============================================================================
    // MATERIALES DINÁMICOS
    // ============================================================================

    function addMaterialRow() {
        const container = document.getElementById('materialesContainer');
        const rowId = materialRowCount++;

        const row = document.createElement('div');
        row.className = 'material-row';
        row.id = `materialRow_${rowId}`;
        row.innerHTML = `
        <select name="materiales[${rowId}][id]" class="form-control" onchange="updateStockDisplay(this, ${rowId})">
            <option value="">Seleccione...</option>
            ${materiales.map(m => `
                <option value="${m.id_material}" data-stock="${m.stock_disponible}" data-unidad="${m.unidad_medida || ''}">
                    ${m.nombre}
                </option>
            `).join('')}
        </select>
        <span class="stock-display" id="stock_${rowId}" style="text-align: center; color: var(--text-muted);">-</span>
        <input type="number" name="materiales[${rowId}][cantidad]" class="form-control" 
               step="0.01" min="0.01" placeholder="0">
        <button type="button" class="btn-remove" onclick="removeMaterialRow(${rowId})">
            <i class="fas fa-times"></i>
        </button>
    `;

        container.appendChild(row);
    }

    function updateStockDisplay(select, rowId) {
        const option = select.options[select.selectedIndex];
        const stock = option?.dataset.stock || '-';
        const unidad = option?.dataset.unidad || '';
        document.getElementById(`stock_${rowId}`).textContent = stock + ' ' + unidad;
    }

    function removeMaterialRow(rowId) {
        const row = document.getElementById(`materialRow_${rowId}`);
        if (row) row.remove();
    }

    // ============================================================================
    // PERSONAL MULTI-SELECT
    // ============================================================================

    document.querySelectorAll('.multi-select-item').forEach(item => {
        item.addEventListener('click', function (e) {
            if (e.target.tagName !== 'INPUT') {
                const checkbox = this.querySelector('input');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
            }
        });

        const checkbox = item.querySelector('input');
        checkbox.addEventListener('change', function () {
            item.classList.toggle('selected', this.checked);
        });
    });

    // ============================================================================
    // FOTOS
    // ============================================================================

    function previewPhoto(input, tipo) {
        const slot = document.getElementById(`slot${tipo}`);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                // Limpiar contenido existente
                const existingImg = slot.querySelector('img');
                if (existingImg) existingImg.remove();

                // Crear imagen
                const img = document.createElement('img');
                img.src = e.target.result;
                slot.appendChild(img);
                slot.classList.add('has-image');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removePhoto(tipo) {
        const slot = document.getElementById(`slot${tipo}`);
        const img = slot.querySelector('img');
        const input = slot.querySelector('input');

        if (img) img.remove();
        if (input) input.value = '';
        slot.classList.remove('has-image');
    }

    // ============================================================================
    // FORMULARIO
    // ============================================================================

    function resetForm() {
        document.getElementById('formParte').reset();
        document.getElementById('id_parte').value = '';
        document.getElementById('materialesContainer').innerHTML = '';
        materialRowCount = 0;

        document.querySelectorAll('.multi-select-item').forEach(item => {
            item.classList.remove('selected');
            item.querySelector('input').checked = false;
        });

        ['Inicio', 'Proceso', 'Fin'].forEach(tipo => removePhoto(tipo));

        calcularTiempo();
        calcularVolumen();
    }

    async function guardarBorrador() {
        await submitForm('Borrador');
    }

    document.getElementById('formParte').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Validar fotos
        const fotoInicio = document.querySelector('input[name="foto_inicio"]').files.length;
        const fotoProceso = document.querySelector('input[name="foto_proceso"]').files.length;
        const fotoFin = document.querySelector('input[name="foto_fin"]').files.length;

        if (fotoInicio === 0 || fotoProceso === 0 || fotoFin === 0) {
            showToast('Debe subir las 3 fotos obligatorias (Inicio, Proceso, Fin)', 'warning');
            return;
        }

        await submitForm('Enviado');
    });

    async function submitForm(estado) {
        const form = document.getElementById('formParte');
        const formData = new FormData(form);
        formData.append('estado', estado);

        try {
            const response = await fetch('api/save_parte.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(estado === 'Borrador' ? 'Borrador guardado' : 'Parte enviado correctamente', 'success');
                location.reload();
            } else {
                showToast('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de conexión', 'error');
        }
    }

    async function loadParte(id) {
        // TODO: Implementar carga de parte existente
        showToast('Cargando parte #' + id, 'info');
    }

    // Inicialización
    document.addEventListener('DOMContentLoaded', function () {
        calcularTiempo();
        calcularVolumen();
    });
</script>

<?php require_once '../../includes/footer.php'; ?>