<?php
/**
 * Reportes (Reports) View
 * Dynamic filters and export functionality
 */

$pageTitle = 'Reportes';
$pageScript = 'reportes.js';
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reportes y Exportación</h1>
        <p class="page-subtitle">Genera reportes personalizados con filtros dinámicos</p>
    </div>
</div>

<!-- Report Type Selection -->
<div class="grid grid-cols-4 mb-lg">
    <div class="card" style="cursor: pointer;" onclick="selectReportType('stock')">
        <div class="d-flex align-center gap-md">
            <div class="card-icon primary">
                <i class="fas fa-boxes"></i>
            </div>
            <div>
                <h4>Reporte de Stock</h4>
                <p class="text-muted">Estado actual del inventario</p>
            </div>
        </div>
    </div>

    <div class="card" style="cursor: pointer;" onclick="selectReportType('movimientos')">
        <div class="d-flex align-center gap-md">
            <div class="card-icon success">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
                <h4>Historial de Movimientos</h4>
                <p class="text-muted">Entradas y salidas detalladas</p>
            </div>
        </div>
    </div>

    <div class="card" style="cursor: pointer;" onclick="selectReportType('cuadrillas')">
        <div class="d-flex align-center gap-md">
            <div class="card-icon warning">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <h4>Consumo por Cuadrilla</h4>
                <p class="text-muted">Análisis de consumo</p>
            </div>
        </div>
    </div>

    <div class="card" style="cursor: pointer;" onclick="selectReportType('alertas')">
        <div class="d-flex align-center gap-md">
            <div class="card-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h4>Alertas de Stock</h4>
                <p class="text-muted">Materiales bajo mínimo</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="card mb-lg" id="filters-section" style="display: none;">
    <h3 class="card-title mb-md">
        <i class="fas fa-filter"></i>
        Filtros del Reporte
    </h3>

    <div class="d-flex gap-md" style="flex-wrap: wrap;" id="filters-container">
        <!-- Dynamic filters will be inserted here -->
    </div>

    <div class="d-flex gap-sm" style="margin-top: var(--spacing-lg);">
        <button class="btn btn-primary" onclick="generateReport()">
            <i class="fas fa-sync"></i>
            Generar Reporte
        </button>
        <button class="btn btn-success" onclick="exportToExcel()">
            <i class="fas fa-file-excel"></i>
            Exportar Excel
        </button>
        <button class="btn btn-danger" onclick="exportToPDF()">
            <i class="fas fa-file-pdf"></i>
            Exportar PDF
        </button>
    </div>
</div>

<!-- Report Preview -->
<div class="card" id="report-preview" style="display: none;">
    <div class="card-header">
        <span class="card-title" id="report-title">Vista Previa del Reporte</span>
        <span class="text-muted" id="report-date"></span>
    </div>
    <div class="card-body" id="report-content">
        <!-- Report data will be displayed here -->
    </div>
</div>

<?php include '../includes/footer.php'; ?>