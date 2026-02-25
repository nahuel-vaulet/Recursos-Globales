<?php require_once 'includes/header.php'; ?>

<!-- DASHBOARD GERENCIAL -->
<?php if (($_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '') === 'Gerente'): ?>
    <style>
        /* Estilos Dashboard Gerencial */
        .dash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .dash-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            position: relative;
            border-left: 5px solid transparent;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(100, 181, 246, 0.15);
            /* Borde sutil por defecto */
            color: var(--text-primary);
        }

        /* Ajuste de Borde en Modo Claro para consistencia */
        [data-theme="light"] .dash-card {
            border: 1px solid #e2e8f0;
            border-left-width: 5px;
        }

        .dash-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .card-finance {
            border-left: 5px solid var(--color-primary);
        }

        .card-stock {
            border-left: 5px solid #e74c3c;
            cursor: pointer;
        }

        .card-buy {
            border-left: 5px solid #f39c12;
        }

        .card-expiry {
            border-left: 5px solid #9b59b6;
        }

        .dash-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .dash-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .dash-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Progress Circle */
        .circle-wrap {
            width: 80px;
            height: 80px;
            background: var(--bg-tertiary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
            background: conic-gradient(var(--color-primary) var(--angle), var(--bg-secondary) 0deg);
        }

        .circle-inner {
            width: 64px;
            height: 64px;
            background: var(--bg-card);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--color-primary);
        }

        .badge-alert {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #e74c3c;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Workflow Panels */
        .workflow-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .flow-panel {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(100, 181, 246, 0.15);
        }

        [data-theme="light"] .flow-panel {
            border: 1px solid #e2e8f0;
        }

        .flow-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(100, 181, 246, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-secondary);
        }

        [data-theme="light"] .flow-header {
            background: #f8fafc;
            border-bottom-color: #e2e8f0;
        }

        .flow-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .flow-body {
            padding: 0;
            max-height: 350px;
            overflow-y: auto;
        }

        .task-item {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        [data-theme="light"] .task-item {
            border-bottom: 1px solid #f0f0f0;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-item:hover {
            background: rgba(100, 181, 246, 0.1);
        }

        .task-urgent {
            border-left: 3px solid #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }

        [data-theme="light"] .task-urgent {
            background: #fff5f5;
        }

        .task-main {
            font-weight: 600;
            display: block;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .task-info {
            display: block;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }


        /* Scrollbar personalizado para Paneles */
        .flow-body::-webkit-scrollbar {
            width: 6px;
        }

        .flow-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .flow-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .flow-body::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        [data-theme="light"] .flow-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .flow-body::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 900px) {
            .workflow-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card">
    <div class="card">
    <div class="container-fluid" style="padding-top: 20px;">
        <!-- 1. KPIs Financieros y Logísticos -->
        <div class="dash-grid">
            <!-- Certificación -->
            <div class="dash-card card-finance">
                <h3 class="dash-title">Certificación (Mes) <i class="fas fa-chart-pie"
                        style="float:right; opacity:0.3;"></i></h3>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div class="dash-value" id="kpi-cert-val" style="font-size: 1.5rem;">$0</div>
                        <div class="dash-sub">Proyectado vs Meta</div>
                    </div>
                    <div class="circle-wrap" id="kpi-cert-circle" style="--angle: 0deg;">
                        <div class="circle-inner" id="kpi-cert-pct">0%</div>
                    </div>
                </div>
            </div>

            <!-- Stock Alerta -->
            <div class="dash-card card-stock" onclick="window.location='modules/stock/index.php'">
                <h3 class="dash-title">Inventario Crítico</h3>
                <div class="dash-value" id="kpi-stock-val">0</div>
                <div class="dash-sub">Ítems bajo punto de pedido</div>
                <div class="badge-alert" id="kpi-stock-badge" style="display:none;">0</div>
            </div>

            <!-- Compras -->
            <div class="dash-card card-buy">
                <h3 class="dash-title">Supply Chain</h3>
                <div class="dash-value" id="kpi-buy-val">0</div>
                <div class="dash-sub" style="color:#e67e22;">Pendientes de Ingreso</div>
            </div>

            <!-- Vencimientos -->
            <div class="dash-card card-expiry">
                <h3 class="dash-title">Riesgo (15 días)</h3>
                <div class="dash-value" id="kpi-exp-val">0</div>
                <div class="dash-sub">VTV / Seguros / Carnets</div>
            </div>
        </div>

        <!-- 2. Flujo de Trabajo -->
        <div class="workflow-grid">
            <!-- Panel A: Urgentes -->
            <div class="flow-panel">
                <div class="flow-header">
                    <h4 class="flow-title"><i class="fas fa-fire-alt" style="color: #e74c3c;"></i> Urgentes del Día</h4>
                    <span class="badge badge-danger" id="kpi-urgent-count" style="display:none">0</span>
                    <a href="modules/tareas/index.php" class="btn btn-sm btn-outline" style="margin-left: auto;">Gestionar
                        Tareas</a>
                </div>
                <div class="flow-body" id="list-urgent">
                    <div style="padding: 20px; text-align: center; color: #aaa;"><i class="fas fa-spinner fa-spin"></i>
                        Cargando...</div>
                </div>
            </div>

            <!-- Panel B: Programación -->
            <div class="flow-panel">
                <div class="flow-header">
                    <h4 class="flow-title"><i class="fas fa-calendar-check" style="color: var(--color-primary);"></i>
                        Bandeja de Programación</h4>
                    <a href="modules/odt/index.php" class="btn btn-sm btn-outline">Asignar Cuadrilla</a>
                </div>
                <div class="flow-body" id="list-pending">
                    <div style="padding: 20px; text-align: center; color: #aaa;"><i class="fas fa-spinner fa-spin"></i>
                        Cargando...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('modules/reportes/api/get_dashboard_kpis.php')
                .then(r => r.json())
                .then(data => {
                    // Cert
                    const certVal = data.certification || '0,00';
                    const certPct = data.certification_percent || 0;

                    document.getElementById('kpi-cert-val').textContent = '$ ' + certVal;
                    document.getElementById('kpi-cert-pct').textContent = certPct + '%';
                    document.getElementById('kpi-cert-circle').style.setProperty('--angle', (certPct * 3.6) + 'deg');

                    // Stock
                    document.getElementById('kpi-stock-val').textContent = data.stock_alerts;
                    if (data.stock_alerts > 0) {
                        const b = document.getElementById('kpi-stock-badge');
                        b.textContent = data.stock_alerts;
                        b.style.display = 'flex';
                    }

                    // Compras
                    document.getElementById('kpi-buy-val').textContent = data.purchase_alerts;

                    // Expiry
                    document.getElementById('kpi-exp-val').textContent = data.expiration_alerts;

                    // Urgentes
                    const ulUrgent = document.getElementById('list-urgent');
                    ulUrgent.innerHTML = '';
                    if (data.urgent_tasks.length > 0) {
                        document.getElementById('kpi-urgent-count').textContent = data.urgent_tasks.length;
                        document.getElementById('kpi-urgent-count').style.display = 'inline-block';

                        data.urgent_tasks.forEach(t => {
                            let link = '#';
                            let icon = '<i class="fas fa-exclamation-circle"></i>';
                            let actionBtn = '';

                            if (t.source === 'ODT') {
                                link = `modules/odt/form.php?id=${t.id}`;
                                icon = '<i class="fas fa-hard-hat"></i>';
                                actionBtn = `<a href="${link}" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>`;
                            } else if (t.source === 'PERSONAL') {
                                link = `modules/personal/form.php?id=${t.id}&tab=legajo`;
                                icon = '<i class="fas fa-user-clock"></i>';
                                actionBtn = `<a href="${link}" class="btn btn-sm btn-warning" title="Completar Legajo"><i class="fas fa-file-signature"></i></a>`;
                            } else {
                                link = `modules/tareas/index.php`;
                                icon = '<i class="fas fa-tasks"></i>';

                                // Botones de Acción Rápida para Tareas
                                if (t.estado === 'Pendiente') {
                                    actionBtn = `<a href="modules/tareas/complete_task.php?id=${t.id}&status=En Curso&redirect=dashboard" class="btn btn-sm btn-outline-info" title="Iniciar"><i class="fas fa-play"></i></a>`;
                                } else if (t.estado === 'En Curso') {
                                    actionBtn = `<a href="modules/tareas/complete_task.php?id=${t.id}&status=Completada&redirect=dashboard" class="btn btn-sm btn-success" title="Completar"><i class="fas fa-check"></i></a>`;
                                } else {
                                    actionBtn = `<a href="${link}" class="btn btn-sm btn-outline"><i class="fas fa-arrow-right"></i></a>`;
                                }
                            }

                            const html = `
                    <div class="task-item task-urgent">
                        <div onclick="window.location='${link}'" style="cursor:pointer; flex-grow:1;">
                            <span class="task-main">${icon} ${t.titulo}</span>
                            <span class="task-info">${t.descripcion || t.tipo} ${t.estado ? '(' + t.estado + ')' : ''}</span>
                        </div>
                        <div style="margin-left:10px;">
                            ${actionBtn}
                        </div>
                    </div>`;
                            ulUrgent.innerHTML += html;
                        });
                    } else {
                        ulUrgent.innerHTML = '<div style="padding: 20px; text-align: center; color: #aaa;">Sin urgencias para hoy.</div>';
                    }

                    // Pendientes
                    const ulPending = document.getElementById('list-pending');
                    ulPending.innerHTML = '';
                    if (data.pending_tasks.length > 0) {
                        data.pending_tasks.forEach(t => {
                            let link = '#';
                            if (t.source === 'ODT') {
                                link = `modules/odt/form.php?id=${t.id}`;
                            } else {
                                link = `modules/tareas/index.php`;
                            }

                            const html = `
                    <div class="task-item">
                        <div>
                            <span class="task-main">${t.titulo}</span>
                            <span class="task-info">Vence: ${t.fecha || 'N/A'} - ${t.tipo || 'General'}</span>
                        </div>
                        <a href="${link}" class="btn btn-sm btn-outline"><i class="fas fa-arrow-right"></i></a>
                    </div>`;
                            ulPending.innerHTML += html;
                        });
                    } else {
                        ulPending.innerHTML = '<div style="padding: 20px; text-align: center; color: #aaa;">Bandeja al día.</div>';
                    }
                })
                .catch(e => console.error(e));
        });
    </script>
<?php else: ?>

    <div
        style="margin-top: var(--spacing-lg); display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md);">

        <?php if (tienePermiso('materiales')): ?>
            <div class="card" style="border-left: 5px solid var(--color-primary);">
                <h3><i class="fas fa-box"></i> Gestión de Materiales</h3>
                <p>Administre el maestro de materiales y precios.</p>
                <a href="modules/materiales/index.php" class="btn btn-outline" style="margin-top: var(--spacing-sm)">Ir a
                    Materiales</a>
            </div>
        <?php endif; ?>

        <?php if (tienePermiso('odt')): ?>
            <div class="card" style="border-left: 5px solid var(--color-warning);">
                <h3><i class="fas fa-hard-hat"></i> ODTs Activas</h3>
                <p>Monitoreo de órdenes de trabajo en curso.</p>
                <a href="modules/odt/index.php" class="btn btn-outline" style="margin-top: var(--spacing-sm)">Ver ODTs</a>
            </div>
        <?php endif; ?>

        <?php if (tienePermiso('stock')): ?>
            <div class="card" style="border-left: 5px solid var(--color-success);">
                <h3><i class="fas fa-dolly"></i> Stock Rápido</h3>
                <p>Movimientos y consulta de saldos.</p>
                <a href="modules/stock/index.php" class="btn btn-outline" style="margin-top: var(--spacing-sm)">Consultar
                    Stock</a>
            </div>
        <?php endif; ?>

        <?php if (tienePermiso('compras')): ?>
            <div class="card" style="border-left: 5px solid #2980b9;">
                <h3><i class="fas fa-shopping-cart"></i> Compras</h3>
                <p>Solicitudes y Órdenes de Compra</p>
                <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: var(--spacing-sm);">
                    <a href="modules/compras/index.php" class="btn btn-sm btn-outline">Dashboard</a>
                    <a href="modules/compras/solicitudes/index.php" class="btn btn-sm btn-outline">Solicitudes</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '', ['Gerente', 'Administrativo', 'Coordinador ASSA'])): ?>
            <div class="card" style="border-left: 5px solid #9b59b6;">
                <h3><i class="fas fa-calendar-alt"></i> Gestión de Tareas</h3>
                <p>Agenda, programación y recordatorios.</p>
                <a href="modules/tareas/index.php" class="btn btn-outline" style="margin-top: var(--spacing-sm)">Ver
                    Agenda</a>
            </div>
        <?php endif; ?>


    </div>
    </div> <!-- Close container -->
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>