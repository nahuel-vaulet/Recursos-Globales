<?php
/**
 * Movimientos (Stock Movements) Management View
 */

$pageTitle = 'Movimientos';
$pageScript = 'movimientos.js';
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Registro de Movimientos</h1>
        <p class="page-subtitle">Control de entradas y salidas de stock</p>
    </div>
    <div class="d-flex gap-sm">
        <button class="btn btn-success" onclick="openMovementModal('entrada')">
            <i class="fas fa-arrow-down"></i>
            Nueva Entrada
        </button>
        <button class="btn btn-warning" onclick="openMovementModal('salida')">
            <i class="fas fa-arrow-up"></i>
            Nueva Salida
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-lg">
    <div class="d-flex gap-md" style="flex-wrap: wrap;">
        <select class="form-control" id="filter-tipo" style="width: auto; min-width: 150px;">
            <option value="">Todos los tipos</option>
            <option value="entrada">Entradas</option>
            <option value="salida">Salidas</option>
        </select>

        <select class="form-control" id="filter-material" style="width: auto; min-width: 200px;">
            <option value="">Todos los materiales</option>
        </select>

        <select class="form-control" id="filter-cuadrilla" style="width: auto; min-width: 200px;">
            <option value="">Todas las cuadrillas</option>
        </select>

        <div class="d-flex gap-sm align-center">
            <label class="form-label" style="margin: 0;">Desde:</label>
            <input type="date" class="form-control" id="filter-desde" style="width: auto;">
        </div>

        <div class="d-flex gap-sm align-center">
            <label class="form-label" style="margin: 0;">Hasta:</label>
            <input type="date" class="form-control" id="filter-hasta" style="width: auto;">
        </div>

        <button class="btn btn-secondary" onclick="clearFilters()">
            <i class="fas fa-times"></i>
            Limpiar
        </button>
    </div>
</div>

<!-- Movements Table -->
<div id="movements-table"></div>

<!-- Create Movement Modal -->
<div class="modal" id="movement-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Nuevo Movimiento</h3>
        <button class="modal-close" onclick="Modal.close()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modal-body">
        <form id="movement-form">
            <input type="hidden" id="movement-tipo">

            <div class="form-group">
                <label class="form-label">Tipo de Movimiento</label>
                <div class="d-flex gap-md">
                    <span id="tipo-badge" class="stock-indicator ok">Entrada</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Material *</label>
                <select class="form-control" id="movement-material" required>
                    <option value="">Seleccionar material...</option>
                </select>
                <small class="text-muted" id="material-stock-info"></small>
            </div>

            <div class="form-group" id="cuadrilla-group">
                <label class="form-label">Cuadrilla *</label>
                <select class="form-control" id="movement-cuadrilla">
                    <option value="">Seleccionar cuadrilla...</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Cantidad *</label>
                <input type="number" class="form-control" id="movement-cantidad" min="0.01" step="0.01" required>
            </div>

            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="movement-observaciones" rows="3"
                    placeholder="Notas adicionales..."></textarea>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
        <button class="btn btn-primary" onclick="saveMovement()">
            <i class="fas fa-save"></i>
            Registrar Movimiento
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>