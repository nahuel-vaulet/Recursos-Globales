<?php
/**
 * Módulo: Usuarios del Sistema
 * Formulario de creación/edición
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Solo Gerente puede acceder
if (!tienePermiso('usuarios')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}

$id = $_GET['id'] ?? null;
$usuario = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "<script>window.location.href='index.php';</script>";
        exit;
    }
}

// Obtener cuadrillas
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo != 'Baja' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="padding: 0 20px;">
    <div class="card" style="max-width: 800px; margin: 20px auto; border-top: 4px solid var(--color-primary);">
        <div style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-user-edit" style="color: var(--color-primary);"></i>
                <?php echo $id ? 'Editar Usuario' : 'Nuevo Usuario'; ?>
            </h2>
        </div>

        <form action="save.php" method="POST" id="usuarioForm" onsubmit="return validateForm()">
            <input type="hidden" name="id_usuario" value="<?php echo $id; ?>">
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="required">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" 
                           value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required
                           placeholder="Ej: Juan Pérez">
                </div>
                
                <div class="col-md-6 form-group">
                    <label class="required">Email (Usuario)</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required
                           placeholder="juan@erp.com">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="<?php echo $id ? '' : 'required'; ?>">Contraseña</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" class="form-control" 
                               <?php echo $id ? '' : 'required'; ?>
                               placeholder="<?php echo $id ? 'Dejar en blanco para mantener actual' : 'Mínimo 6 caracteres'; ?>">
                        <button type="button" onclick="togglePass('password')" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #999;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6 form-group">
                    <label>Confirmar Contraseña</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                               placeholder="Repetir contraseña">
                        <button type="button" onclick="togglePass('confirm_password')" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #999;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="required">Tipo de Usuario</label>
                    <select name="tipo_usuario" id="tipo_usuario" class="form-control" required onchange="toggleCuadrilla()">
                        <option value="">Seleccione...</option>
                        <option value="Gerente" <?php echo ($usuario['tipo_usuario'] ?? '') === 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                        <option value="Coordinador ASSA" <?php echo ($usuario['tipo_usuario'] ?? '') === 'Coordinador ASSA' ? 'selected' : ''; ?>>Coordinador ASSA</option>
                        <option value="Administrativo" <?php echo ($usuario['tipo_usuario'] ?? '') === 'Administrativo' ? 'selected' : ''; ?>>Administrativo General</option>
                        <option value="Administrativo ASSA" <?php echo ($usuario['tipo_usuario'] ?? '') === 'Administrativo ASSA' ? 'selected' : ''; ?>>Administrativo ASSA</option>
                        <option value="Inspector ASSA" <?php echo ($usuario['tipo_usuario'] ?? '') === 'Inspector ASSA' ? 'selected' : ''; ?>>Inspector ASSA</option>
                        <option value="JefeCuadrilla" <?php echo ($usuario['tipo_usuario'] ?? '') === 'JefeCuadrilla' ? 'selected' : ''; ?>>Jefe de Cuadrilla</option>
                    </select>
                </div>

                <div class="col-md-6 form-group" id="cuadrilla_container" style="display: none;">
                    <label class="required">Asignar Cuadrilla</label>
                    <select name="id_cuadrilla" id="id_cuadrilla" class="form-control">
                        <option value="">Seleccione Cuadrilla...</option>
                        <?php foreach ($cuadrillas as $c): ?>
                            <option value="<?php echo $c['id_cuadrilla']; ?>" 
                                    <?php echo ($usuario['id_cuadrilla'] ?? '') == $c['id_cuadrilla'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre_cuadrilla']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Requerido para Jefes de Cuadrilla</small>
                </div>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <div style="display: flex; gap: 20px; padding: 10px; background: #f9f9f9; border-radius: 6px;">
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="estado" value="1" 
                               <?php echo ($usuario['estado'] ?? 1) == 1 ? 'checked' : ''; ?>>
                        <span style="color: var(--color-success); font-weight: 500;">
                            <i class="fas fa-check-circle"></i> Activo
                        </span>
                    </label>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="estado" value="0" 
                               <?php echo ($usuario['estado'] ?? 1) == 0 ? 'checked' : ''; ?>>
                        <span style="color: var(--color-danger); font-weight: 500;">
                            <i class="fas fa-times-circle"></i> Inactivo
                        </span>
                    </label>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePass(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function toggleCuadrilla() {
    const tipo = document.getElementById('tipo_usuario').value;
    const container = document.getElementById('cuadrilla_container');
    const select = document.getElementById('id_cuadrilla');
    
    if (tipo === 'JefeCuadrilla') {
        container.style.display = 'block';
        select.required = true;
    } else {
        container.style.display = 'none';
        select.required = false;
        select.value = '';
    }
}

function validateForm() {
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const tipo = document.getElementById('tipo_usuario').value;
    const cuadrilla = document.getElementById('id_cuadrilla').value;
    
    if (pass && pass.length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    if (pass !== confirm) {
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (tipo === 'JefeCuadrilla' && !cuadrilla) {
        alert('Debe asignar una cuadrilla para el rol de Jefe de Cuadrilla');
        return false;
    }
    
    return true;
}

// Init state
document.addEventListener('DOMContentLoaded', toggleCuadrilla);
</script>

<?php require_once '../../includes/footer.php'; ?>
