<?php
/**
 * Materials Management View
 * CRUD interface for materials
 */

$pageTitle = 'Materiales';
$pageScript = 'materiales.js';
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gestión de Materiales</h1>
        <p class="page-subtitle">Administra el inventario de materiales</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i>
        Nuevo Material
    </button>
</div>

<!-- Filters -->
<div class="card mb-lg">
    <div class="d-flex gap-md" style="flex-wrap: wrap;">
        <div class="search-box" style="flex: 1; min-width: 200px;">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Buscar por nombre o código...">
        </div>

        <select class="form-control" id="filter-categoria" style="width: auto; min-width: 150px;">
            <option value="">Todas las categorías</option>
        </select>

        <label class="d-flex align-center gap-sm" style="cursor: pointer;">
            <input type="checkbox" id="filter-bajo-stock">
            <span class="stock-indicator warning">Solo bajo stock</span>
        </label>
    </div>
</div>

<!-- Materials Table -->
<div id="materials-table"></div>

<!-- Create/Edit Modal -->
<div class="modal" id="material-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Nuevo Material</h3>
        <button class="modal-close" onclick="Modal.close()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modal-body">
        <form id="material-form">
            <input type="hidden" id="material-id">

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="material-nombre" required>
            </div>

            <div class="grid grid-cols-2" style="gap: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" id="material-codigo" placeholder="Ej: CEM-001">
                </div>

                <div class="form-group">
                    <label class="form-label">Unidad de Medida *</label>
                    <select class="form-control" id="material-unidad" required>
                        <option value="">Seleccionar...</option>
                        <option value="Unidad">Unidad</option>
                        <option value="Kg">Kilogramo (Kg)</option>
                        <option value="m">Metro (m)</option>
                        <option value="m²">Metro cuadrado (m²)</option>
                        <option value="m³">Metro cúbico (m³)</option>
                        <option value="Litro">Litro</option>
                        <option value="Bolsa">Bolsa</option>
                        <option value="Varilla">Varilla</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2" style="gap: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Stock Actual</label>
                    <input type="number" class="form-control" id="material-stock" min="0" step="0.01" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Stock Mínimo</label>
                    <input type="number" class="form-control" id="material-minimo" min="0" step="0.01" value="0">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Categoría</label>
                <input type="text" class="form-control" id="material-categoria"
                    placeholder="Ej: Construcción, Electricidad...">
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
        <button class="btn btn-primary" onclick="saveMaterial()">
            <i class="fas fa-save"></i>
            Guardar
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>