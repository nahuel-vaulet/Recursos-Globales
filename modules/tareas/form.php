<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check Permissions
if (!in_array($_SESSION['usuario_rol'], ['Gerente', 'Administrativo', 'Coordinador ASSA'])) {
    echo "<div class='container'><div class='alert alert-danger'>Acceso denegado.</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Handle Form Submission
$isEdit = false;
$id = $_GET['id'] ?? null;
$tarea = [
    'titulo' => '',
    'descripcion' => '',
    'importancia' => 'Baja',
    'tipo_recurrencia' => 'Unica',
    'parametro_recurrencia' => '',
    'fecha_inicio' => date('Y-m-d'),
    'fecha_fin' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM tareas_definicion WHERE id_definicion = ?");
    $stmt->execute([$id]);
    $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
    $isEdit = true;
}

?>

<div class="container" style="max-width: 800px; padding-top: 20px;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-check-double"></i>
                <?php echo $isEdit ? 'Editar Tarea Programada' : 'Nueva Tarea'; ?>
            </h2>
        </div>
        <div class="card-body">
            <form action="save.php" method="POST">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id_definicion" value="<?php echo $id; ?>">
                <?php endif; ?>

                <div class="form-group mb-3">
                    <label>Título de la Tarea <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control" required
                        value="<?php echo htmlspecialchars($tarea['titulo']); ?>">
                </div>

                <div class="form-group mb-3">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="form-control"
                        rows="3"><?php echo htmlspecialchars($tarea['descripcion']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Importancia</label>
                        <select name="importancia" class="form-control">
                            <option value="Baja" <?php echo $tarea['importancia'] == 'Baja' ? 'selected' : ''; ?>>Baja
                            </option>
                            <option value="Alta" <?php echo $tarea['importancia'] == 'Alta' ? 'selected' : ''; ?>>Alta
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tipo de Recurrencia</label>
                        <select name="tipo_recurrencia" id="tipoRecurrencia" class="form-control"
                            onchange="toggleRecurrenceOptions()">
                            <option value="Unica" <?php echo $tarea['tipo_recurrencia'] == 'Unica' ? 'selected' : ''; ?>>
                                Única (Una sola vez)</option>
                            <option value="Diaria" <?php echo $tarea['tipo_recurrencia'] == 'Diaria' ? 'selected' : ''; ?>>Diaria (Todos los días)</option>
                            <option value="Semanal" <?php echo $tarea['tipo_recurrencia'] == 'Semanal' ? 'selected' : ''; ?>>Semanal</option>
                            <option value="Mensual" <?php echo $tarea['tipo_recurrencia'] == 'Mensual' ? 'selected' : ''; ?>>Mensual</option>
                        </select>
                    </div>
                </div>

                <!-- Opciones Dinámicas -->
                <div id="recurrenceOptions"
                    style="display: none; background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-md-12" id="weeklyOptions" style="display: none;">
                            <label>Día de la Semana</label>
                            <select name="parametro_semanal" class="form-control">
                                <option value="1">Lunes</option>
                                <option value="2">Martes</option>
                                <option value="3">Miércoles</option>
                                <option value="4">Jueves</option>
                                <option value="5">Viernes</option>
                                <option value="6">Sábado</option>
                                <option value="7">Domingo</option>
                            </select>
                        </div>
                        <div class="col-md-12" id="monthlyOptions" style="display: none;">
                            <label>Día del Mes (1-31)</label>
                            <input type="number" name="parametro_mensual" class="form-control" min="1" max="31"
                                value="1">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label>Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" required
                                value="<?php echo $tarea['fecha_inicio']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label>Fecha Fin (Opcional)</label>
                            <input type="date" name="fecha_fin" class="form-control"
                                value="<?php echo $tarea['fecha_fin']; ?>">
                            <small class="text-muted">Dejar vacío para indefinido</small>
                        </div>
                    </div>
                </div>

                <!-- Fecha única -->
                <div id="singleDateOption" class="mb-3">
                    <label>Fecha de Vencimiento</label>
                    <input type="date" name="fecha_unica" class="form-control"
                        value="<?php echo $tarea['fecha_inicio']; ?>">
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Tarea</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleRecurrenceOptions() {
        const type = document.getElementById('tipoRecurrencia').value;
        const recOpts = document.getElementById('recurrenceOptions');
        const singleOpt = document.getElementById('singleDateOption');
        const weekOpt = document.getElementById('weeklyOptions');
        const monthOpt = document.getElementById('monthlyOptions');

        if (type === 'Unica') {
            recOpts.style.display = 'none';
            singleOpt.style.display = 'block';
        } else {
            recOpts.style.display = 'block';
            singleOpt.style.display = 'none'; // La fecha de inicio va en el bloque recurrente if needed, or use logic

            // Hide specific params first
            weekOpt.style.display = 'none';
            monthOpt.style.display = 'none';

            if (type === 'Semanal') weekOpt.style.display = 'block';
            if (type === 'Mensual') monthOpt.style.display = 'block';
        }
    }

    // Init
    toggleRecurrenceOptions();
</script>

<?php require_once '../../includes/footer.php'; ?>