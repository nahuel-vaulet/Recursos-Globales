<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Get cuadrilla info
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_cuadrilla = intval($_GET['id']);
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Get cuadrilla data
$stmtC = $pdo->prepare("SELECT * FROM cuadrillas WHERE id_cuadrilla = ?");
$stmtC->execute([$id_cuadrilla]);
$cuadrilla = $stmtC->fetch(PDO::FETCH_ASSOC);

if (!$cuadrilla) {
    header("Location: index.php?msg=not_found");
    exit;
}

// Get personal of this cuadrilla
$stmtP = $pdo->prepare("SELECT * FROM personal WHERE id_cuadrilla = ? ORDER BY nombre_apellido");
$stmtP->execute([$id_cuadrilla]);
$personal = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// Get existing attendance for this date
$stmtA = $pdo->prepare("SELECT * FROM asistencia WHERE fecha = ? AND id_personal IN (SELECT id_personal FROM personal WHERE id_cuadrilla = ?)");
$stmtA->execute([$fecha, $id_cuadrilla]);
$asistenciaData = [];
foreach ($stmtA->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $asistenciaData[$a['id_personal']] = $a;
}

// Options
$estados_dia = ['Presente', 'Falta Justificada', 'Injustificada', 'Dia Lluvia'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        foreach ($_POST['asistencia'] as $id_personal => $data) {
            // Check if record exists
            $stmtCheck = $pdo->prepare("SELECT id_asistencia FROM asistencia WHERE id_personal = ? AND fecha = ?");
            $stmtCheck->execute([$id_personal, $fecha]);
            $existing = $stmtCheck->fetchColumn();

            $hora_entrada = !empty($data['hora_entrada']) ? $data['hora_entrada'] : null;
            $hora_salida = !empty($data['hora_salida']) ? $data['hora_salida'] : null;
            $estado_dia = $data['estado_dia'] ?? 'Presente';
            $horas_emergencia = !empty($data['horas_emergencia']) ? floatval($data['horas_emergencia']) : 0;

            if ($existing) {
                // UPDATE
                $stmtU = $pdo->prepare("UPDATE asistencia SET hora_entrada = ?, hora_salida = ?, estado_dia = ?, horas_emergencia = ? WHERE id_asistencia = ?");
                $stmtU->execute([$hora_entrada, $hora_salida, $estado_dia, $horas_emergencia, $existing]);
            } else {
                // INSERT
                $stmtI = $pdo->prepare("INSERT INTO asistencia (id_personal, fecha, hora_entrada, hora_salida, estado_dia, horas_emergencia) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtI->execute([$id_personal, $fecha, $hora_entrada, $hora_salida, $estado_dia, $horas_emergencia]);
            }
        }

        $pdo->commit();
        header("Location: asistencia.php?id=$id_cuadrilla&fecha=$fecha&msg=saved");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;">
                <i class="fas fa-clipboard-check"></i> Asistencia -
                <?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla']); ?>
            </h2>
            <p style="margin: 5px 0 0; color: #666;">Control de Horas y Asistencia del Personal</p>
        </div>
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver a Cuadrillas</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="alert alert-success"
            style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> Asistencia guardada correctamente
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"
            style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Error:</strong>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Date Selector -->
    <div class="card" style="margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <input type="hidden" name="id" value="<?php echo $id_cuadrilla; ?>">
            <label style="font-weight: 600;"><i class="fas fa-calendar-alt"></i> Fecha:</label>
            <input type="date" name="fecha" value="<?php echo $fecha; ?>" class="form-control" style="width: auto;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Ver</button>

            <!-- Quick date buttons -->
            <div style="margin-left: auto; display: flex; gap: 5px;">
                <a href="?id=<?php echo $id_cuadrilla; ?>&fecha=<?php echo date('Y-m-d', strtotime('-1 day', strtotime($fecha))); ?>"
                    class="btn btn-outline btn-sm"><i class="fas fa-chevron-left"></i></a>
                <a href="?id=<?php echo $id_cuadrilla; ?>&fecha=<?php echo date('Y-m-d'); ?>"
                    class="btn btn-outline btn-sm">Hoy</a>
                <a href="?id=<?php echo $id_cuadrilla; ?>&fecha=<?php echo date('Y-m-d', strtotime('+1 day', strtotime($fecha))); ?>"
                    class="btn btn-outline btn-sm"><i class="fas fa-chevron-right"></i></a>
            </div>
        </form>
    </div>

    <!-- Attendance Form -->
    <div class="card" style="border-top: 4px solid var(--color-primary);">
        <?php if (empty($personal)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-users-slash" style="font-size: 2em; margin-bottom: 10px;"></i><br>
                Esta cuadrilla no tiene personal asignado.<br>
                <a href="../personal/index.php" style="margin-top: 10px; display: inline-block;">Ir a Gestión de
                    Personal</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span class="badge"
                        style="background: #e3f2fd; color: #1565c0; padding: 8px 15px; border-radius: 20px;">
                        <i class="fas fa-users"></i>
                        <?php echo count($personal); ?> integrantes
                    </span>
                    <span style="color: #666; font-weight: 500;">
                        <?php echo strftime('%A %d de %B, %Y', strtotime($fecha)); ?>
                    </span>
                </div>

                <table class="table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Personal</th>
                            <th>Rol</th>
                            <th style="width: 130px;">Entrada</th>
                            <th style="width: 130px;">Salida</th>
                            <th style="width: 180px;">Estado</th>
                            <th style="width: 100px;">Hs Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personal as $p):
                            $a = $asistenciaData[$p['id_personal']] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($p['nombre_apellido']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge-rol <?php echo strtolower($p['rol']); ?>">
                                        <?php echo $p['rol']; ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="time" name="asistencia[<?php echo $p['id_personal']; ?>][hora_entrada]"
                                        value="<?php echo $a['hora_entrada'] ?? '08:00'; ?>" class="form-control-sm">
                                </td>
                                <td>
                                    <input type="time" name="asistencia[<?php echo $p['id_personal']; ?>][hora_salida]"
                                        value="<?php echo $a['hora_salida'] ?? '17:00'; ?>" class="form-control-sm">
                                </td>
                                <td>
                                    <select name="asistencia[<?php echo $p['id_personal']; ?>][estado_dia]"
                                        class="form-control-sm estado-select" onchange="updateRowStyle(this)">
                                        <?php foreach ($estados_dia as $e): ?>
                                            <option value="<?php echo $e; ?>" <?php echo ($a['estado_dia'] ?? 'Presente') == $e ? 'selected' : ''; ?>>
                                                <?php echo $e; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.5" min="0" max="24"
                                        name="asistencia[<?php echo $p['id_personal']; ?>][horas_emergencia]"
                                        value="<?php echo $a['horas_emergencia'] ?? '0'; ?>" class="form-control-sm"
                                        style="width: 80px;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="marcarTodosPresentes()">
                        <i class="fas fa-check-double"></i> Todos Presentes
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asistencia
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    .form-control-sm {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9em;
        width: 100%;
    }

    .badge-rol {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .badge-rol.oficial {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-rol.ayudante {
        background: #fff3e0;
        color: #ef6c00;
    }

    .badge-rol.chofer {
        background: #e8eaf6;
        color: #3949ab;
    }

    .table tbody tr {
        transition: background 0.2s;
    }

    .table tbody tr:hover {
        background: #f5f5f5;
    }

    .estado-select {
        font-weight: 500;
    }
</style>

<script>
    function marcarTodosPresentes() {
        document.querySelectorAll('.estado-select').forEach(select => {
            select.value = 'Presente';
        });
    }

    function updateRowStyle(select) {
        const row = select.closest('tr');
        row.style.background = '';

        if (select.value === 'Falta Justificada' || select.value === 'Injustificada') {
            row.style.background = '#fff8e1';
        } else if (select.value === 'Dia Lluvia') {
            row.style.background = '#e3f2fd';
        }
    }

    // Apply initial styles
    document.querySelectorAll('.estado-select').forEach(select => updateRowStyle(select));
</script>

<?php require_once '../../includes/footer.php'; ?>