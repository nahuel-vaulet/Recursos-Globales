<?php
/**
 * Usuarios (Users) Management View
 */

$pageTitle = 'Usuarios';
$pageScript = 'usuarios.js';
include '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gestión de Usuarios</h1>
        <p class="page-subtitle">Administra usuarios y roles del sistema</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i>
        Nuevo Usuario
    </button>
</div>

<!-- Users Table -->
<div id="users-table"></div>

<!-- Create/Edit Modal -->
<div class="modal" id="user-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Nuevo Usuario</h3>
        <button class="modal-close" onclick="Modal.close()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modal-body">
        <form id="user-form">
            <input type="hidden" id="user-id">

            <div class="form-group">
                <label class="form-label">Nombre Completo *</label>
                <input type="text" class="form-control" id="user-nombre" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" class="form-control" id="user-email" required>
            </div>

            <div class="form-group">
                <label class="form-label" id="password-label">Contraseña *</label>
                <input type="password" class="form-control" id="user-password">
                <small class="text-muted" id="password-hint">Mínimo 6 caracteres</small>
            </div>

            <div class="form-group">
                <label class="form-label">Rol *</label>
                <select class="form-control" id="user-rol" required>
                    <option value="">Seleccionar rol...</option>
                    <option value="admin">Administrador</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="operador">Operador</option>
                </select>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
        <button class="btn btn-primary" onclick="saveUser()">
            <i class="fas fa-save"></i>
            Guardar
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>