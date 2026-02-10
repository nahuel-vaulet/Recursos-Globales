<?php
/**
 * Módulo de Reportes - Dashboard
 * Solo accesible para Gerente
 * ERP Recursos Globales
 */

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Verificar permiso para este módulo (Gerente, Coordinador ASSA, Administrativo ASSA)
verificarPermiso('reportes');

$rolActual = $_SESSION['usuario_tipo'] ?? '';
$modificador = obtenerModificadorModulo('reportes', $rolActual);

// Obtener estadísticas generales (común para roles con acceso)
$stats = [];

// Total materiales
$stmt = $pdo->query("SELECT COUNT(*) as total FROM maestro_materiales");
$stats['materiales'] = $stmt->fetch()['total'];

// Total cuadrillas activas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM cuadrillas WHERE estado_operativo = 'Activa'");
$stats['cuadrillas_activas'] = $stmt->fetch()['total'];

// Total personal
$stmt = $pdo->query("SELECT COUNT(*) as total FROM personal");
$stats['personal'] = $stmt->fetch()['total'];

// Movimientos del mes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM movimientos WHERE MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(fecha_hora) = YEAR(CURRENT_DATE())");
$stats['movimientos_mes'] = $stmt->fetch()['total'];

// Últimas acciones de auditoría
$auditoria = [];
try {
    $stmt = $pdo->query("
        SELECT a.*, u.nombre as usuario_nombre 
        FROM auditoria_acciones a
        JOIN usuarios u ON a.id_usuario = u.id_usuario
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $auditoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabla puede no existir aún
    $auditoria = [];
}

// Usuarios del sistema
$usuarios = [];
try {
    $stmt = $pdo->query("
        SELECT u.id_usuario, u.nombre, u.email, u.rol, u.estado, c.nombre_cuadrilla,
               (SELECT COUNT(*) FROM auditoria_acciones a WHERE a.id_usuario = u.id_usuario) as total_acciones
        FROM usuarios u
        LEFT JOIN cuadrillas c ON u.id_cuadrilla = c.id_cuadrilla
        ORDER BY u.nombre ASC
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
}
?>

<div class="container-fluid" style="padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0;"><i class="fas fa-chart-bar"></i> Dashboard de Reportes</h2>
            <p style="margin: 5px 0 0; color: #666;">
                <?php
                if ($rolActual === 'Gerente')
                    echo 'Panel exclusivo de gerencia - Visión general del sistema';
                elseif ($rolActual === 'Coordinador ASSA')
                    echo 'Panel Estratégico ASSA - Supervisión de Operativa';
                else
                    echo 'Panel Operativo - Gestión y Métricas ASSA';
                ?>
            </p>
        </div>
        <span class="<?php echo obtenerColorTipoUsuario($rolActual); ?>"
            style="padding: 8px 15px; border-radius: 20px; font-weight: 600;">
            <i class="fas fa-user-shield"></i> Acceso: <?php echo $rolActual; ?>
        </span>
    </div>

    <?php if ($rolActual === 'Inspector ASSA'): ?>
        <!-- Vista mínima para Inspector -->
        <div class="card" style="text-align: center; padding: 50px;">
            <i class="fas fa-clipboard-list" style="font-size: 4em; color: var(--color-warning); margin-bottom: 20px;"></i>
            <h2>Módulo de Gestión de ODTs únicamente</h2>
            <p>Usted tiene acceso limitado a la carga y consulta de ODTs.</p>
            <a href="../odt/index.php" class="btn btn-primary btn-lg">Ir a Gestión de ODTs</a>
        </div>
    <?php else: ?>
        <!-- Dashboards para Administrativo, Coordinador y Gerente -->

        <?php if ($rolActual !== 'Administrativo ASSA'): ?>
            <!-- Metrics Cards Generales (Solo Gerente y Coordinador) -->
            <div class="metrics-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="metric-card"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px;">
                    <div style="font-size: 2.5em; font-weight: 700;">
                        <?php echo $stats['materiales']; ?>
                    </div>
                    <div style="opacity: 0.9;"><i class="fas fa-boxes"></i> Materiales Registrados</div>
                </div>

                <div class="metric-card"
                    style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 25px; border-radius: 12px;">
                    <div style="font-size: 2.5em; font-weight: 700;">
                        <?php echo $stats['cuadrillas_activas']; ?>
                    </div>
                    <div style="opacity: 0.9;"><i class="fas fa-hard-hat"></i> Cuadrillas Activas</div>
                </div>

                <div class="metric-card"
                    style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%); color: white; padding: 25px; border-radius: 12px;">
                    <div style="font-size: 2.5em; font-weight: 700;">
                        <?php echo $stats['personal']; ?>
                    </div>
                    <div style="opacity: 0.9;"><i class="fas fa-users"></i> Personal Total</div>
                </div>

                <div class="metric-card"
                    style="background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%); color: white; padding: 25px; border-radius: 12px;">
                    <div style="font-size: 2.5em; font-weight: 700;">
                        <?php echo $stats['movimientos_mes']; ?>
                    </div>
                    <div style="opacity: 0.9;"><i class="fas fa-exchange-alt"></i> Movimientos del Mes</div>
                </div>
            </div>
        <?php else: ?>
            <!-- Dashboard Administrativo ASSA (Métricas Operativas) -->
            <div class="metrics-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <!-- Aquí irían métricas de stock de agua y asistencia si existieran tablas específicas -->
                <div class="metric-card"
                    style="background: var(--bg-card); border: 1px solid var(--color-success); padding: 20px; border-radius: 12px;">
                    <div style="font-size: 2em; color: var(--color-success); font-weight: 700;">ACTIVO</div>
                    <div style="color: var(--text-secondary);"><i class="fas fa-check-circle"></i> Estado del Sistema</div>
                </div>
                <div class="metric-card"
                    style="background: var(--bg-card); border: 1px solid var(--color-info); padding: 20px; border-radius: 12px;">
                    <div style="font-size: 2em; color: var(--color-info); font-weight: 700;">
                        <?php echo $stats['movimientos_mes']; ?>
                    </div>
                    <div style="color: var(--text-secondary);"><i class="fas fa-exchange-alt"></i> Movimientos Stock</div>
                </div>
            </div>
        <?php endif; ?>

        <div
            style="display: grid; grid-template-columns: <?php echo ($rolActual === 'Administrativo ASSA') ? '1fr' : '2fr 1fr'; ?>; gap: 25px;">

            <?php if ($rolActual !== 'Administrativo ASSA'): ?>
                <!-- Auditoría de Acciones (Solo Gerente y Coordinador) -->
                <div class="card" style="border-top: 4px solid var(--color-primary);">
                    <h3 style="margin-top: 0;"><i class="fas fa-history"></i> Historial de Acciones</h3>

                    <?php if (empty($auditoria)): ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-clock" style="font-size: 3em; margin-bottom: 15px;"></i><br>
                            No hay acciones registradas aún.<br>
                            <small>Las acciones aparecerán aquí una vez que ejecutes el script SQL.</small>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                        <th>Módulo</th>
                                        <th>Descripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($auditoria as $a): ?>
                                        <tr>
                                            <td style="white-space: nowrap; font-size: 0.85em;">
                                                <?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($a['usuario_nombre']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = 'badge-default';
                                                switch ($a['accion']) {
                                                    case 'LOGIN':
                                                        $badgeClass = 'badge-success';
                                                        break;
                                                    case 'LOGOUT':
                                                        $badgeClass = 'badge-info';
                                                        break;
                                                    case 'CREAR':
                                                        $badgeClass = 'badge-primary';
                                                        break;
                                                    case 'EDITAR':
                                                        $badgeClass = 'badge-warning';
                                                        break;
                                                    case 'ELIMINAR':
                                                        $badgeClass = 'badge-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $a['accion']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($a['modulo']); ?>
                                            </td>
                                            <td style="font-size: 0.85em; color: #666;">
                                                <?php echo htmlspecialchars(substr($a['descripcion'] ?? '', 0, 50)); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Usuarios del Sistema (Solo Gerente y Coordinador) -->
                <?php if ($rolActual !== 'Administrativo ASSA'): ?>
                    <div class="card" style="border-top: 4px solid #28a745;">
                        <h3 style="margin-top: 0;"><i class="fas fa-users-cog"></i> Usuarios del Sistema</h3>

                        <?php if (empty($usuarios)): ?>
                            <div style="text-align: center; padding: 30px; color: #999;">
                                <i class="fas fa-user-plus" style="font-size: 2em;"></i><br>
                                Sin usuarios configurados
                            </div>
                        <?php else: ?>
                            <div class="user-list">
                                <?php foreach ($usuarios as $u): ?>
                                    <div class="user-item"
                                        style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                                        <div class="user-avatar"
                                            style="width: 40px; height: 40px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                            <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="font-weight: 500;">
                                                <?php echo htmlspecialchars($u['nombre']); ?>
                                            </div>
                                            <div style="font-size: 0.8em; color: #888;">
                                                <?php
                                                $rolDisp = $u['rol'];
                                                if ($rolDisp === 'JefeCuadrilla') {
                                                    $rolDisp = 'Jefe: ' . ($u['nombre_cuadrilla'] ?? 'Sin asignar');
                                                }
                                                echo $rolDisp;
                                                ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right; font-size: 0.8em;">
                                            <span style="color: <?php echo $u['estado'] ? '#28a745' : '#dc3545'; ?>;">
                                                <i class="fas fa-circle" style="font-size: 0.6em;"></i>
                                                <?php echo $u['estado'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                            <div style="color: #888; font-size: 0.9em;">
                                                <?php echo $u['total_acciones']; ?> acciones
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<style>
    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75em;
        font-weight: 500;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    .badge-primary {
        background: #cce5ff;
        color: #004085;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-default {
        background: #e9ecef;
        color: #495057;
    }

    .badge-gerente {
        background: linear-gradient(135deg, #ffd700 0%, #ffb700 100%);
        color: #333;
    }

    .table {
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 0.85em;
        color: #666;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }
</style>

<?php require_once '../../includes/footer.php'; ?>