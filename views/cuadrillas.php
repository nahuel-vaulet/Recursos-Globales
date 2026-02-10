<?php
/**
 * Cuadrillas (Work Squads) Management View
 */

$pageTitle = 'Cuadrillas';
$pageScript = 'cuadrillas.js';
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gestión de Cuadrillas</h1>
        <p class="page-subtitle">Administra las cuadrillas de trabajo</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i>
        Nueva Cuadrilla
    </button>
</div>

<!-- Cuadrillas Grid -->
<div class="grid grid-cols-3" id="cuadrillas-grid">
    <div class="empty-state">
        <i class="fas fa-spinner fa-spin"></i>
        <div class="empty-state-title">Cargando cuadrillas...</div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal" id="cuadrilla-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Nueva Cuadrilla</h3>
        <button class="modal-close" onclick="Modal.close()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modal-body">
        <form id="cuadrilla-form">
            <input type="hidden" id="cuadrilla-id">

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="cuadrilla-nombre" required>
            </div>

            <div class="form-group">
                <label class="form-label">Zona de Trabajo</label>
                <input type="text" class="form-control" id="cuadrilla-zona" placeholder="Ej: Zona Norte - Sector A">
            </div>

            <div class="form-group">
                <label class="form-label">Responsable</label>
                <input type="text" class="form-control" id="cuadrilla-responsable" placeholder="Nombre del responsable">
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
        <button class="btn btn-primary" onclick="saveCuadrilla()">
            <i class="fas fa-save"></i>
            Guardar
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>