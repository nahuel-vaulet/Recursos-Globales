<?php
/**
 * Módulo: Tipos de Trabajos
 * Listado principal con filtros y métricas
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Filtros
$filtroUnidad = $_GET['unidad'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

// Query con filtros
$where = "WHERE 1=1";
$params = [];

if ($filtroUnidad) {
    $where .= " AND unidad_medida = ?";
    $params[] = $filtroUnidad;
}

if ($busqueda) {
    $where .= " AND (nombre LIKE ? OR codigo_trabajo LIKE ? OR descripcion_breve LIKE ?)";
    $busquedaParam = "%{$busqueda}%";
    $params[] = $busquedaParam;
    $params[] = $busquedaParam;
    $params[] = $busquedaParam;
}

$query = "SELECT * FROM tipos_trabajos $where ORDER BY codigo_trabajo ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trabajos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
        <h1><i class="fas fa-hard-hat"></i> Tipos de Trabajos</h1>
        <a href="form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Tipo</a>
    </div>

    <!-- Filtros -->
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Buscar</label>
            <input type="text" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>"
                placeholder="Código, nombre o descripción..." class="form-control-sm">
        </div>
        <div class="filter-group">
            <label>Unidad</label>
            <select name="unidad" class="form-control-sm">
                <option value="">Todas</option>
                <option value="M2" <?php echo $filtroUnidad === 'M2' ? 'selected' : ''; ?>>M² (Superficie)</option>
                <option value="M3" <?php echo $filtroUnidad === 'M3' ? 'selected' : ''; ?>>M³ (Volumen)</option>
                <option value="ML" <?php echo $filtroUnidad === 'ML' ? 'selected' : ''; ?>>ML (Lineal)</option>
                <option value="U" <?php echo $filtroUnidad === 'U' ? 'selected' : ''; ?>>U (Unidad)</option>
            </select>
        </div>
        <div class="filter-group" style="flex: 0 0 auto; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" title="Buscar">
                <i class="fas fa-search"></i>
            </button>
            <?php if ($filtroUnidad || $busqueda): ?>
                <a href="index.php" class="btn btn-outline" title="Limpiar Filtros">
                    <i class="fas fa-sync-alt"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Feedback Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div
            style="padding: var(--spacing-md); margin-bottom: var(--spacing-md); border-radius: var(--border-radius-md); 
            background: <?php echo $_GET['msg'] == 'saved' ? '#d4edda' : ($_GET['msg'] == 'deleted' ? '#fff3cd' : '#f8d7da'); ?>; 
            color: <?php echo $_GET['msg'] == 'saved' ? '#155724' : ($_GET['msg'] == 'deleted' ? '#856404' : '#721c24'); ?>;">
            <?php
            if ($_GET['msg'] == 'saved')
                echo "<i class='fas fa-check-circle'></i> Tipo de trabajo guardado correctamente.";
            if ($_GET['msg'] == 'deleted')
                echo "<i class='fas fa-trash'></i> Tipo de trabajo eliminado.";
            if ($_GET['msg'] == 'error')
                echo "<i class='fas fa-exclamation-circle'></i> Ha ocurrido un error.";
            ?>
        </div>
    <?php endif; ?>

    <!-- Tabla -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--color-primary-dark); color: white;">
                    <th style="padding: var(--spacing-md); text-align: left; width: 80px;">Código</th>
                    <th style="padding: var(--spacing-md); text-align: left;">Nombre</th>
                    <th style="padding: var(--spacing-md); text-align: left;">Descripción</th>
                    <th style="padding: var(--spacing-md); text-align: center; width: 80px;">Unidad</th>
                    <th style="padding: var(--spacing-md); text-align: center; width: 100px;">Tiempo Límite</th>
                    <th style="padding: var(--spacing-md); text-align: right; width: 150px; white-space: nowrap;">Precio
                        OM</th>
                    <th style="padding: var(--spacing-md); text-align: center; width: 80px;">Estado</th>
                    <th style="padding: var(--spacing-md); text-align: center; width: 100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trabajos as $trabajo): ?>
                    <tr style="border-bottom: 1px solid var(--color-neutral-light);">
                        <td style="padding: var(--spacing-md);">
                            <span
                                style="background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">
                                <?php echo htmlspecialchars($trabajo['codigo_trabajo']); ?>
                            </span>
                        </td>
                        <td style="padding: var(--spacing-md); font-weight: 500;">
                            <?php echo htmlspecialchars($trabajo['nombre']); ?>
                        </td>
                        <td style="padding: var(--spacing-md); color: #666; font-size: 0.9em;">
                            <?php echo htmlspecialchars($trabajo['descripcion_breve'] ?? '-'); ?>
                        </td>
                        <td style="padding: var(--spacing-md); text-align: center;">
                            <?php
                            $unidadColors = ['M2' => '#10b981', 'M3' => '#3b82f6', 'ML' => '#f59e0b', 'U' => '#6366f1'];
                            $color = $unidadColors[$trabajo['unidad_medida']] ?? '#6b7280';
                            ?>
                            <span
                                style="background: <?php echo $color; ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">
                                <?php echo $trabajo['unidad_medida']; ?>
                            </span>
                        </td>
                        <td style="padding: var(--spacing-md); text-align: center;">
                            <?php echo $trabajo['tiempo_limite_dias'] ? $trabajo['tiempo_limite_dias'] . ' días' : '-'; ?>
                        </td>
                        <td style="padding: var(--spacing-md); text-align: right; font-weight: 500;">
                            <?php echo $trabajo['precio_unitario'] ? '$ ' . number_format($trabajo['precio_unitario'], 2, ',', '.') : '-'; ?>
                        </td>
                        <td style="padding: var(--spacing-md); text-align: center;">
                            <?php if ($trabajo['estado']): ?>
                                <span
                                    style="background: #d1fae5; color: #059669; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">Activo</span>
                            <?php else: ?>
                                <span
                                    style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: var(--spacing-md); text-align: center;">
                            <a href="form.php?id=<?php echo $trabajo['id_tipologia']; ?>" class="btn btn-outline"
                                style="padding: 4px 8px; font-size: 0.9em;"><i class="fas fa-edit"></i></a>
                            <a href="delete.php?id=<?php echo $trabajo['id_tipologia']; ?>"
                                onclick="return confirm('¿Está seguro de eliminar este tipo de trabajo?');"
                                class="btn btn-outline"
                                style="padding: 4px 8px; font-size: 0.9em; border-color: var(--color-danger); color: var(--color-danger);">
                                <i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($trabajos)): ?>
                    <tr>
                        <td colspan="7"
                            style="padding: var(--spacing-lg); text-align: center; color: var(--color-neutral);">
                            <i class="fas fa-search"
                                style="font-size: 2em; opacity: 0.5; display: block; margin-bottom: 10px;"></i>
                            No hay tipos de trabajos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>