<?php
/**
 * Módulo: Administración de Gastos - Fondos Fijos y Gastos Menores
 * 
 * CRUD completo para gestión de caja chica y gastos menores.
 * Usa las variables de tema globales de la aplicación.
 * 
 * @author Sistema ERP - Recursos Globales
 * @version 2.0
 */

require_once '../../config/database.php';
require_once '../../includes/header.php';

// ============================================================================
// CONSULTAS DE DATOS
// ============================================================================

// 1. Obtener configuración del fondo fijo
$stmtFondo = $pdo->query("SELECT monto_fondo FROM Administracion_Fondo_Fijo LIMIT 1");
$fondo = $stmtFondo->fetch(PDO::FETCH_ASSOC);
$montoFondo = $fondo ? floatval($fondo['monto_fondo']) : 100000.00;

// 2. Calcular gastos no rendidos (pendientes)
$stmtGastosPendientes = $pdo->query("
    SELECT COALESCE(SUM(monto), 0) as total 
    FROM Administracion_Gastos 
    WHERE estado = 'Pendiente'
");
$gastosPendientes = floatval($stmtGastosPendientes->fetch(PDO::FETCH_ASSOC)['total']);

// 3. Calcular saldo disponible
$saldoDisponible = $montoFondo - $gastosPendientes;

// 4. Obtener lista de personal para dropdown
$stmtPersonal = $pdo->query("SELECT id_personal, nombre_apellido FROM personal ORDER BY nombre_apellido");
$personal = $stmtPersonal->fetchAll(PDO::FETCH_ASSOC);

// 5. Obtener últimos gastos (con datos del responsable)
$stmtGastos = $pdo->query("
    SELECT g.*, p.nombre_apellido as responsable_nombre
    FROM Administracion_Gastos g
    LEFT JOIN personal p ON g.id_responsable = p.id_personal
    ORDER BY g.fecha_gasto DESC, g.id_gasto DESC
    LIMIT 100
");
$gastos = $stmtGastos->fetchAll(PDO::FETCH_ASSOC);

// 6. Tipos de gasto para filtro
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
    /* ========================================
       MÓDULO GASTOS - Estilos específicos
       Usa variables globales del tema
       ======================================== */

    .gastos-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: var(--spacing-md);
    }

    /* Header del módulo */
    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-lg);
        padding: var(--spacing-lg);
        background: var(--bg-card);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
    }

    .module-header-left {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
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

    /* Panel de Saldo */
    .balance-panel {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }

    .balance-card {
        padding: var(--spacing-lg);
        border-radius: var(--border-radius-md);
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .balance-card.fondo {
        background: linear-gradient(135deg, #004A7F 0%, #0073A8 100%);
        color: white;
    }

    .balance-card.gastado {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .balance-card.disponible {
        background: linear-gradient(135deg, #28A745 0%, #20c997 100%);
        color: white;
    }

    .balance-card.warning {
        background: linear-gradient(135deg, #FFC107 0%, #e0a800 100%);
        color: #333;
    }

    .balance-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.9;
        margin-bottom: var(--spacing-xs);
    }

    .balance-amount {
        font-size: 2rem;
        font-weight: 700;
    }

    .balance-icon {
        font-size: 1.5rem;
        margin-bottom: var(--spacing-sm);
    }

    /* Layout principal */
    .main-layout {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: var(--spacing-lg);
    }

    @media (max-width: 1024px) {
        .main-layout {
            grid-template-columns: 1fr;
        }
    }

    /* Formulario de gasto - Usa variables de tema */
    .form-card {
        background: var(--bg-card);
        padding: var(--spacing-lg);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
        position: sticky;
        top: 80px;
    }

    .form-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--accent-primary);
        margin-bottom: var(--spacing-lg);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .form-group {
        margin-bottom: var(--spacing-md);
    }

    .form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin-bottom: var(--spacing-xs);
    }

    .form-control {
        width: 100%;
        padding: 12px var(--spacing-md);
        background: var(--bg-tertiary);
        border: 1px solid rgba(100, 181, 246, 0.2);
        border-radius: var(--border-radius-sm);
        font-size: 1rem;
        font-family: inherit;
        color: var(--text-primary);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.15);
    }

    .form-control[type="number"] {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
    }

    /* Modo Claro - Formulario */
    [data-theme="light"] .form-control {
        background: #ffffff;
        border: 1px solid #d1d5db;
        color: var(--text-primary);
    }

    [data-theme="light"] .form-control:focus {
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* File upload area */
    .file-upload-area {
        border: 2px dashed rgba(100, 181, 246, 0.3);
        border-radius: var(--border-radius-md);
        padding: var(--spacing-lg);
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        background: var(--bg-tertiary);
    }

    .file-upload-area:hover {
        border-color: var(--accent-primary);
        background: rgba(100, 181, 246, 0.05);
    }

    .file-upload-area.dragover {
        border-color: var(--accent-primary);
        background: rgba(100, 181, 246, 0.1);
    }

    .file-upload-area input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .file-upload-icon {
        font-size: 2.5rem;
        color: var(--accent-primary);
        margin-bottom: var(--spacing-sm);
    }

    .file-upload-text {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .file-upload-text strong {
        color: var(--accent-primary);
    }

    .file-preview {
        max-width: 100%;
        max-height: 150px;
        border-radius: var(--border-radius-sm);
        margin-top: var(--spacing-sm);
        display: none;
    }

    /* Modo Claro - File Upload */
    [data-theme="light"] .file-upload-area {
        border-color: #d1d5db;
        background: #f8fafc;
    }

    [data-theme="light"] .file-upload-area:hover {
        border-color: var(--accent-primary);
        background: rgba(37, 99, 235, 0.05);
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
        border: 1px solid rgba(100, 181, 246, 0.3);
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

    /* Tabla de gastos - Usa estilos globales de tabla */
    .table-card {
        background: var(--bg-card);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(100, 181, 246, 0.15);
        overflow: hidden;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-lg);
        border-bottom: 1px solid rgba(100, 181, 246, 0.1);
        flex-wrap: wrap;
        gap: var(--spacing-md);
    }

    .table-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--accent-primary);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .filters {
        display: flex;
        gap: var(--spacing-sm);
        flex-wrap: wrap;
    }

    .filter-select {
        padding: 8px 12px;
        background: var(--bg-tertiary);
        border: 1px solid rgba(100, 181, 246, 0.2);
        border-radius: var(--border-radius-sm);
        font-size: 0.9rem;
        color: var(--text-primary);
    }

    [data-theme="light"] .filter-select {
        background: #ffffff;
        border-color: #d1d5db;
    }

    .table-container {
        overflow-x: auto;
        /* Scrollbar personalizado estilo app */
        scrollbar-width: thin;
        scrollbar-color: rgba(100, 181, 246, 0.3) transparent;
    }

    .table-container::-webkit-scrollbar {
        height: 6px;
    }

    .table-container::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 3px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: rgba(100, 181, 246, 0.3);
        border-radius: 3px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 181, 246, 0.5);
    }

    [data-theme="light"] .table-container {
        scrollbar-color: rgba(37, 99, 235, 0.3) transparent;
    }

    [data-theme="light"] .table-container::-webkit-scrollbar-thumb {
        background: rgba(37, 99, 235, 0.3);
    }

    [data-theme="light"] .table-container::-webkit-scrollbar-thumb:hover {
        background: rgba(37, 99, 235, 0.5);
    }

    /* Usa estilos globales de tabla del CSS principal */
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .amount-cell {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--color-danger) !important;
    }

    .type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
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

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
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

    .thumb-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        transition: transform 0.2s;
        border: 1px solid rgba(100, 181, 246, 0.2);
    }

    .thumb-img:hover {
        transform: scale(1.1);
    }

    .checkbox-cell {
        width: 40px;
        text-align: center;
    }

    .checkbox-cell input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--accent-primary);
    }

    .actions-cell {
        display: flex;
        gap: var(--spacing-xs);
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        background: rgba(100, 181, 246, 0.1);
        color: var(--accent-primary);
    }

    .btn-icon.view {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }

    .btn-icon.delete {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .btn-icon:hover {
        transform: scale(1.1);
    }

    /* Panel de rendición */
    .rendicion-panel {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-md) var(--spacing-lg);
        background: var(--bg-tertiary);
        border-top: 1px solid rgba(100, 181, 246, 0.1);
    }

    [data-theme="light"] .rendicion-panel {
        background: #f8fafc;
    }

    .selected-count {
        font-weight: 500;
        color: var(--text-secondary);
    }

    .selected-count strong {
        color: var(--accent-primary);
    }

    /* Modal de imagen */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        max-width: 90%;
        max-height: 90%;
    }

    .modal-content img {
        max-width: 100%;
        max-height: 85vh;
        border-radius: var(--border-radius-md);
    }

    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        z-index: 2001;
    }

    /* Alertas */
    .alert {
        padding: var(--spacing-md);
        border-radius: var(--border-radius-sm);
        margin-bottom: var(--spacing-md);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .alert-warning {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: var(--spacing-xl);
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: var(--spacing-md);
        opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .module-header {
            flex-direction: column;
            text-align: center;
            gap: var(--spacing-md);
        }

        .balance-panel {
            grid-template-columns: 1fr 1fr;
        }

        .form-card {
            position: static;
        }

        .table-header {
            flex-direction: column;
            align-items: stretch;
        }

        .filters {
            width: 100%;
        }

        .filter-select {
            flex: 1;
        }
    }
</style>

<div class="gastos-container">
    <!-- Header del módulo -->
    <div class="module-header">
        <div class="module-header-left">
            <div>
                <h1 class="module-title">Administración de Gastos</h1>
                <p class="module-subtitle">Fondos Fijos y Gastos Menores</p>
            </div>
        </div>
        <div>
            <button class="btn btn-success" onclick="openRendicionModal()">
                <i class="fas fa-file-invoice-dollar"></i> Realizar Rendición
            </button>
        </div>
    </div>

    <!-- Panel de Saldo -->
    <div class="balance-panel">
        <div class="balance-card fondo">
            <div class="balance-icon"><i class="fas fa-piggy-bank"></i></div>
            <div class="balance-label">Fondo Fijo Total</div>
            <div class="balance-amount">$<?php echo number_format($montoFondo, 2, ',', '.'); ?></div>
        </div>

        <div class="balance-card gastado">
            <div class="balance-icon"><i class="fas fa-receipt"></i></div>
            <div class="balance-label">Gastos Pendientes</div>
            <div class="balance-amount">$<?php echo number_format($gastosPendientes, 2, ',', '.'); ?></div>
        </div>

        <div class="balance-card <?php echo $saldoDisponible < ($montoFondo * 0.2) ? 'warning' : 'disponible'; ?>">
            <div class="balance-icon"><i class="fas fa-wallet"></i></div>
            <div class="balance-label">Saldo Disponible</div>
            <div class="balance-amount">$<?php echo number_format($saldoDisponible, 2, ',', '.'); ?></div>
        </div>
    </div>

    <?php if ($saldoDisponible < ($montoFondo * 0.2)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Atención:</strong> El saldo disponible es menor al 20% del fondo. Se recomienda realizar una
                rendición para solicitar reposición.</span>
        </div>
    <?php endif; ?>

    <!-- Layout Principal -->
    <div class="main-layout">
        <!-- Formulario de Gasto -->
        <div class="form-card">
            <h2 class="form-title">
                <i class="fas fa-plus-circle"></i> Registrar Nuevo Gasto
            </h2>

            <form id="formGasto" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="monto">Monto ($) *</label>
                    <input type="number" id="monto" name="monto" class="form-control" step="0.01" min="0.01"
                        max="<?php echo $saldoDisponible; ?>" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="tipo_gasto">Tipo de Gasto *</label>
                    <select id="tipo_gasto" name="tipo_gasto" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($tiposGasto as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_responsable">Responsable *</label>
                    <select id="id_responsable" name="id_responsable" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?php echo $p['id_personal']; ?>">
                                <?php echo htmlspecialchars($p['nombre_apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fecha_gasto">Fecha *</label>
                    <input type="date" id="fecha_gasto" name="fecha_gasto" class="form-control"
                        value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Comprobante (Ticket/Factura) *</label>
                    <div class="file-upload-area" id="uploadArea">
                        <input type="file" id="comprobante" name="comprobante" accept="image/*" capture="environment"
                            required>
                        <div class="file-upload-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="file-upload-text">
                            <strong>Toca para tomar foto</strong><br>
                            o arrastra una imagen aquí
                        </div>
                        <img id="filePreview" class="file-preview" alt="Preview">
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción (Opcional)</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="2"
                        placeholder="Detalle adicional del gasto..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnSubmit">
                    <i class="fas fa-save"></i> Guardar Gasto
                </button>
            </form>
        </div>

        <!-- Tabla de Gastos -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-list-alt"></i> Últimos Gastos
                </h2>
                <div class="filters">
                    <select id="filterTipo" class="filter-select" onchange="applyFilters()">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tiposGasto as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterResponsable" class="filter-select" onchange="applyFilters()">
                        <option value="">Todos los responsables</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?php echo $p['id_personal']; ?>">
                                <?php echo htmlspecialchars($p['nombre_apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterEstado" class="filter-select" onchange="applyFilters()">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Rendido">Rendido</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Tipo</th>
                            <th>Responsable</th>
                            <th>Ticket</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($gastos)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-receipt"></i>
                                        <p>No hay gastos registrados</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gastos as $g): ?>
                                <tr data-id="<?php echo $g['id_gasto']; ?>" data-tipo="<?php echo $g['tipo_gasto']; ?>"
                                    data-responsable="<?php echo $g['id_responsable']; ?>"
                                    data-estado="<?php echo $g['estado']; ?>">
                                    <td class="checkbox-cell">
                                        <?php if ($g['estado'] == 'Pendiente'): ?>
                                            <input type="checkbox" class="gasto-check" value="<?php echo $g['id_gasto']; ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($g['fecha_gasto'])); ?></td>
                                    <td class="amount-cell">$<?php echo number_format($g['monto'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="type-badge type-<?php echo $g['tipo_gasto']; ?>">
                                            <?php echo $tiposGasto[$g['tipo_gasto']] ?? $g['tipo_gasto']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($g['responsable_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($g['comprobante_path']): ?>
                                            <img src="/APP-Prueba/uploads/comprobantes/<?php echo $g['comprobante_path']; ?>"
                                                class="thumb-img"
                                                onclick="openImageModal('/APP-Prueba/uploads/comprobantes/<?php echo $g['comprobante_path']; ?>')"
                                                alt="Ticket">
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">Sin imagen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $g['estado']; ?>">
                                            <?php echo $g['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn-icon view" onclick="viewGasto(<?php echo $g['id_gasto']; ?>)"
                                            title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($g['estado'] == 'Pendiente'): ?>
                                            <button class="btn-icon delete" onclick="deleteGasto(<?php echo $g['id_gasto']; ?>)"
                                                title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="rendicion-panel">
                <div class="selected-count">
                    Seleccionados: <strong id="selectedCount">0</strong> |
                    Total: <strong id="selectedTotal">$0,00</strong>
                </div>
                <button class="btn btn-success" onclick="realizarRendicion()" id="btnRendicion" disabled>
                    <i class="fas fa-check-circle"></i> Rendir Seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Imagen -->
<div class="modal-overlay" id="imageModal" onclick="closeImageModal()">
    <button class="modal-close" onclick="closeImageModal()">&times;</button>
    <div class="modal-content">
        <img id="modalImage" src="" alt="Comprobante">
    </div>
</div>

<script>
    // ============================================================================
    // JAVASCRIPT - Módulo de Gastos
    // ============================================================================

    // Saldo disponible (desde PHP)
    const saldoDisponible = <?php echo $saldoDisponible; ?>;

    // ============================================================================
    // FORMULARIO DE GASTO
    // ============================================================================

    // Preview de imagen
    document.getElementById('comprobante').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById('filePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
                document.querySelector('.file-upload-icon').style.display = 'none';
                document.querySelector('.file-upload-text').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // Drag and drop
    const uploadArea = document.getElementById('uploadArea');
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'));
    });
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'));
    });

    // Validación de monto
    document.getElementById('monto').addEventListener('input', function (e) {
        const monto = parseFloat(e.target.value) || 0;
        if (monto > saldoDisponible) {
            e.target.setCustomValidity('El monto supera el saldo disponible ($' + saldoDisponible.toFixed(2) + ')');
        } else {
            e.target.setCustomValidity('');
        }
    });

    // Submit del formulario
    document.getElementById('formGasto').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = document.getElementById('btnSubmit');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        try {
            const response = await fetch('api/save_gasto.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Gasto registrado correctamente', 'success');
                location.reload();
            } else {
                showToast('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de conexión', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // ============================================================================
    // FILTROS
    // ============================================================================

    function applyFilters() {
        const tipo = document.getElementById('filterTipo').value;
        const responsable = document.getElementById('filterResponsable').value;
        const estado = document.getElementById('filterEstado').value;

        const rows = document.querySelectorAll('#tableBody tr[data-id]');

        rows.forEach(row => {
            const matchTipo = !tipo || row.dataset.tipo === tipo;
            const matchResp = !responsable || row.dataset.responsable === responsable;
            const matchEstado = !estado || row.dataset.estado === estado;

            row.style.display = (matchTipo && matchResp && matchEstado) ? '' : 'none';
        });
    }

    // ============================================================================
    // SELECCIÓN Y RENDICIÓN
    // ============================================================================

    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.gasto-check').forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = checked;
            }
        });
        updateSelectedCount();
    }

    document.querySelectorAll('.gasto-check').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.gasto-check:checked');
        let total = 0;

        checked.forEach(cb => {
            const row = cb.closest('tr');
            const amountText = row.querySelector('.amount-cell').textContent;
            const amount = parseFloat(amountText.replace('$', '').replace('.', '').replace(',', '.'));
            total += amount;
        });

        document.getElementById('selectedCount').textContent = checked.length;
        document.getElementById('selectedTotal').textContent = '$' + total.toLocaleString('es-AR', { minimumFractionDigits: 2 });
        document.getElementById('btnRendicion').disabled = checked.length === 0;
    }

    async function realizarRendicion() {
        const checked = document.querySelectorAll('.gasto-check:checked');
        if (checked.length === 0) {
            showToast('Seleccione al menos un gasto para rendir', 'warning');
            return;
        }

        const ids = Array.from(checked).map(cb => cb.value);
        const total = document.getElementById('selectedTotal').textContent;

        if (!confirm(`¿Confirma rendir ${checked.length} gastos por un total de ${total}?\n\nSe generará un resumen de la rendición.`)) {
            return;
        }

        try {
            const response = await fetch('api/rendicion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Rendición realizada correctamente. ID: ' + data.id_rendicion, 'success');
                location.reload();
            } else {
                showToast('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de conexión', 'error');
        }
    }

    // ============================================================================
    // MODAL DE IMAGEN
    // ============================================================================

    function openImageModal(src) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModal').classList.add('active');
    }

    function closeImageModal() {
        document.getElementById('imageModal').classList.remove('active');
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeImageModal();
    });

    // ============================================================================
    // ACCIONES
    // ============================================================================

    function viewGasto(id) {
        window.location.href = 'detalle.php?id=' + id;
    }

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
                location.reload();
            } else {
                showToast('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de conexión', 'error');
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>