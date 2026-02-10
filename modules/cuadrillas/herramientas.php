<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

$id_cuadrilla = $_GET['id'] ?? null;

if (!$id_cuadrilla) {
    header("Location: index.php");
    exit;
}

// Fetch Cuadrilla
$stmt = $pdo->prepare("SELECT * FROM cuadrillas WHERE id_cuadrilla = ?");
$stmt->execute([$id_cuadrilla]);
$cuadrilla = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuadrilla) {
    echo "Cuadrilla no encontrada.";
    exit;
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'assign_tool') {
            $tool_id = $_POST['tool_id'];
            $pdo->prepare("UPDATE herramientas SET id_cuadrilla_asignada = ?, estado = 'Asignada' WHERE id_herramienta = ?")
                ->execute([$id_cuadrilla, $tool_id]);
        } elseif ($_POST['action'] === 'unassign_tool') {
            $tool_id = $_POST['tool_id'];
            $pdo->prepare("UPDATE herramientas SET id_cuadrilla_asignada = NULL, estado = 'Disponible' WHERE id_herramienta = ?")
                ->execute([$tool_id]);
        } elseif ($_POST['action'] === 'create_tool') {
            $nombre = $_POST['nombre'];
            $precio = $_POST['precio'];
            $marca = $_POST['marca'];
            $modelo = $_POST['modelo'];
            $serie = $_POST['serie'];

            $pdo->prepare("INSERT INTO herramientas (nombre, marca, modelo, numero_serie, precio_reposicion, estado, id_cuadrilla_asignada) VALUES (?, ?, ?, ?, ?, 'Asignada', ?)")
                ->execute([$nombre, $marca, $modelo, $serie, $precio, $id_cuadrilla]);
        }
    }
    // Redirect to avoid resubmit
    header("Location: herramientas.php?id=$id_cuadrilla");
    exit;
}

// Fetch Assigned Tools
$stmtTools = $pdo->prepare("SELECT * FROM herramientas WHERE id_cuadrilla_asignada = ? ORDER BY nombre ASC");
$stmtTools->execute([$id_cuadrilla]);
$assignedTools = $stmtTools->fetchAll(PDO::FETCH_ASSOC);

// Fetch Available Tools
$availableTools = $pdo->query("SELECT * FROM herramientas WHERE estado = 'Disponible' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$totalValue = 0;
foreach ($assignedTools as $t) {
    $totalValue += $t['precio_reposicion'];
}
?>

<div class="card" style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;">
                <i class="fas fa-tools"></i> Herramientas de Cuadrilla
            </h2>
            <p style="margin: 5px 0 0; color: #666;">
                <?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla']); ?>
            </p>
        </div>
        <div>
            <a href="generar_responsabilidad.php?id=<?php echo $id_cuadrilla; ?>" target="_blank"
                class="btn btn-warning">
                <i class="fas fa-file-pdf"></i> Generar Responsabilidad
            </a>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Left: Assigned Tools -->
        <div style="flex: 2; min-width: 300px;">
            <div class="form-section success">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;"><i class="fas fa-box-open"></i> Asignadas</h3>
                    <div
                        style="background: #fff; padding: 5px 10px; border-radius: 5px; font-weight: bold; color: #333;">
                        Total: $
                        <?php echo number_format($totalValue, 2); ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm custom-table">
                        <thead>
                            <tr>
                                <th>Herramienta</th>
                                <th>Marca/Modelo</th>
                                <th>Serie</th>
                                <th class="text-right">Precio</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignedTools)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay herramientas asignadas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignedTools as $t): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($t['nombre']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($t['marca'] . ' ' . $t['modelo']); ?>
                                        </td>
                                        <td><small>
                                                <?php echo htmlspecialchars($t['numero_serie']); ?>
                                            </small></td>
                                        <td class="text-right">$
                                            <?php echo number_format($t['precio_reposicion'], 2); ?>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="unassign_tool">
                                                <input type="hidden" name="tool_id" value="<?php echo $t['id_herramienta']; ?>">
                                                <button type="submit" class="btn-icon-del" title="Devolución"
                                                    onclick="return confirm('¿Devolver herramienta al depósito?')">
                                                    <i class="fas fa-reply"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div style="flex: 1; min-width: 300px;">
            <!-- Assign Existing -->
            <div class="form-section" style="margin-bottom: 20px;">
                <h3><i class="fas fa-plus-circle"></i> Asignar Existente</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_tool">
                    <div class="form-group">
                        <select name="tool_id" class="form-control" required style="width: 100%; padding: 10px;">
                            <option value="">-- Seleccionar Herramienta --</option>
                            <?php foreach ($availableTools as $at): ?>
                                <option value="<?php echo $at['id_herramienta']; ?>">
                                    <?php echo htmlspecialchars($at['nombre']); ?>
                                    (
                                    <?php echo htmlspecialchars($at['marca']); ?>) -
                                    $
                                    <?php echo $at['precio_reposicion']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px; width: 100%;">
                        Asignar a Cuadrilla
                    </button>
                </form>
            </div>

            <!-- Create New -->
            <div class="form-section">
                <h3><i class="fas fa-magic"></i> Nueva Herramienta</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_tool">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required
                            placeholder="Ej: Taladro Percutor">
                    </div>
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" class="form-control" placeholder="Ej: Makita">
                    </div>
                    <div class="form-group">
                        <label>Modelo</label>
                        <input type="text" name="modelo" class="form-control" placeholder="Ej: HP1630">
                    </div>
                    <div class="form-group">
                        <label>Nro Serie</label>
                        <input type="text" name="serie" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label>Precio Reposición ($)</label>
                        <input type="number" step="0.01" name="precio" class="form-control" required placeholder="0.00">
                    </div>
                    <button type="submit" class="btn btn-success btn-block" style="margin-top: 15px; width: 100%;">
                        Crear y Asignar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .form-section {
        background: var(--bg-card);
        padding: 20px;
        border-radius: 10px;
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(100, 181, 246, 0.1);
    }

    .form-section.success {
        border-top: 4px solid var(--color-success);
    }

    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 10px;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .btn-icon-del {
        background: none;
        border: none;
        color: var(--accent-primary);
        cursor: pointer;
        padding: 5px;
    }

    .btn-icon-del:hover {
        color: var(--color-primary);
        transform: scale(1.1);
    }

    th {
        font-weight: 600;
        color: var(--text-secondary);
        border-bottom: 2px solid rgba(0, 0, 0, 0.05);
    }
</style>

<?php require_once '../../includes/footer.php'; ?>