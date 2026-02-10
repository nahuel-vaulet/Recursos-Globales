<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if editing
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$cuadrilla = null;
$squadTypes = [];
$squadMembers = [];

if ($isEdit) {
    // 1. Get Squad Data
    $stmt = $pdo->prepare("SELECT * FROM cuadrillas WHERE id_cuadrilla = ?");
    $stmt->execute([$_GET['id']]);
    $cuadrilla = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuadrilla) {
        header("Location: index.php?msg=not_found");
        exit;
    }

    // 2. Get Assigned Work Types
    // Table is now cuadrilla_tipos_trabajo (id_cuadrilla, id_tipologia)
    // IMPORTANT: db_migration_refactor_types renamed col id_tipo -> id_tipologia on NEW table
    // But verify if I actually renamed it or just dropped/created. 
    // I created with: id_tipologia INT NOT NULL.
    $stmtTypes = $pdo->prepare("SELECT id_tipologia FROM cuadrilla_tipos_trabajo WHERE id_cuadrilla = ?");
    $stmtTypes->execute([$cuadrilla['id_cuadrilla']]);
    $squadTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

    // 3. Get Assigned Members
    $stmtMembers = $pdo->prepare("SELECT id_personal, nombre_apellido, rol, dni, estado_documentacion FROM personal WHERE id_cuadrilla = ? ORDER BY nombre_apellido");
    $stmtMembers->execute([$cuadrilla['id_cuadrilla']]);
    $squadMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);
}

// Global Data Fetch
// A. Available Types (Specialties)
// Using 'tipologias' table as requested (Especialidades)
$allTypes = $pdo->query("SELECT * FROM tipologias ORDER BY codigo_trabajo ASC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// ...



// B. Available Members (Not assigned to any squad OR assigned to this one)
// We already have members assigned to this squad in $squadMembers.
// We need 'available' ones for the dropdown.
$sqlAvail = "SELECT id_personal, nombre_apellido, rol FROM personal WHERE id_cuadrilla IS NULL AND estado_documentacion != 'Incompleto' ORDER BY nombre_apellido";
$availableMembers = $pdo->query($sqlAvail)->fetchAll(PDO::FETCH_ASSOC);

// C. Vehicles
$sqlVehiculos = "SELECT v.* FROM vehiculos v 
                 WHERE v.estado = 'Operativo' 
                 AND (v.id_vehiculo NOT IN (SELECT id_vehiculo_asignado FROM cuadrillas WHERE id_vehiculo_asignado IS NOT NULL)
                      OR v.id_vehiculo = ?)";
$stmtV = $pdo->prepare($sqlVehiculos);
$stmtV->execute([$cuadrilla['id_vehiculo_asignado'] ?? 0]);
$vehiculos = $stmtV->fetchAll(PDO::FETCH_ASSOC);

// D. Tools - Available and Assigned
$availableTools = $pdo->query("SELECT id_herramienta, nombre, marca, precio_reposicion FROM herramientas WHERE estado = 'Disponible' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get tools assigned to this squad (for edit mode)
$squadTools = [];
if ($isEdit) {
    $stmtTools = $pdo->prepare("SELECT id_herramienta, nombre, marca, modelo, numero_serie, precio_reposicion, fecha_asignacion FROM herramientas WHERE id_cuadrilla_asignada = ? ORDER BY nombre ASC");
    $stmtTools->execute([$cuadrilla['id_cuadrilla']]);
    $squadTools = $stmtTools->fetchAll(PDO::FETCH_ASSOC);
}

// Options
$estados = ['Programada', 'Activa', 'Mantenimiento', 'Baja', 'Suspendida'];
?>

<div class="card"
    style="max-width: 900px; margin: 0 auto; background-color: var(--bg-card); color: var(--text-primary);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: var(--text-primary);">
            <i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus-circle'; ?>"></i>
            <?php echo $isEdit ? 'Editar Cuadrilla' : 'Nueva Cuadrilla'; ?>
        </h2>
        <a href="index.php" class="btn btn-outline"
            style="color: var(--text-secondary); border-color: var(--text-muted);"><i class="fas fa-arrow-left"></i>
            Volver</a>
    </div>

    <!-- Feedback Area -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong>
            <?php echo htmlspecialchars($_GET['details'] ?? 'No se pudo guardar'); ?>
        </div>
    <?php endif; ?>

    <form action="save.php" method="POST" id="cuadrillaForm">
        <input type="hidden" name="id_cuadrilla" value="<?php echo $cuadrilla['id_cuadrilla'] ?? ''; ?>">

        <!-- 1. INFORMACIÓN BÁSICA -->
        <div class="form-section"
            style="border: 1px solid var(--text-muted); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-info-circle"></i> 1. Información
                Básica</h3>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label style="color: var(--text-secondary); font-weight: 500;">Nombre de la Cuadrilla *</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="color" name="color_hex"
                            value="<?php echo htmlspecialchars($cuadrilla['color_hex'] ?? '#0073A8'); ?>"
                            style="width: 50px; padding: 2px; height: 46px; cursor: pointer; border: 1px solid var(--text-muted); background: var(--bg-input);">
                        <input type="text" name="nombre_cuadrilla" required class="form-control"
                            value="<?php echo htmlspecialchars($cuadrilla['nombre_cuadrilla'] ?? ''); ?>"
                            placeholder="Ej: Cuadrilla Hidráulica 1"
                            style="flex: 1; background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted);">
                    </div>
                </div>

                <div class="form-group">
                    <label style="color: var(--text-secondary); font-weight: 500;">Estado Operativo</label>
                    <select name="estado_operativo" class="form-control"
                        style="background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted);">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo ($cuadrilla['estado_operativo'] ?? 'Programada') == $e ? 'selected' : ''; ?>>
                                <?php echo $e; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- DYNAMIC WORK TYPES -->
                <div class="form-group" style="grid-column: span 3;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label style="color: var(--text-primary); font-weight: 600; font-size: 1em;">Tipos de Trabajo de
                            la
                            Cuadrilla</label>
                        <button type="button" class="btn btn-sm btn-outline" onclick="openNewTypeModal()"
                            style="font-size: 0.85em; padding: 6px 12px; border: 1px solid var(--accent-primary); color: var(--accent-primary); background: transparent; border-radius: 6px; transition: all 0.2s;">
                            <i class="fas fa-plus"></i> Nuevo Tipo de Trabajo
                        </button>
                    </div>
                    <div class="input-group" style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <select id="selTipoTrabajo" class="form-control"
                            style="flex: 1; background-color: var(--bg-card); color: var(--text-primary); border: 2px solid var(--accent-primary); border-radius: 8px; padding: 10px 12px; font-size: 1em;">
                            <option value="">-- Seleccionar Tipo de Trabajo --</option>
                            <?php foreach ($allTypes as $t): ?>
                                <option value="<?php echo $t['id_tipologia']; ?>">
                                    <?php echo ($t['codigo_trabajo'] ? '[' . $t['codigo_trabajo'] . '] ' : '') . htmlspecialchars($t['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-primary" onclick="addWorkType()"
                            style="padding: 10px 20px; min-width: 120px;">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>

                    <div id="workTypesList" class="tags-container">
                        <!-- Types will be rendered here -->
                    </div>
                    <!-- Hidden inputs storage -->
                    <div id="workTypesInputs"></div>
                </div>
            </div>
        </div>

        <!-- 2. RECURSOS Y COMUNICACIÓN -->
        <div class="form-section"
            style="border: 1px solid var(--text-muted); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-truck"></i> 2. Vehículo y
                Comunicación</h3>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label style="color: var(--text-secondary); font-weight: 500;">Vehículo Asignado</label>
                    <select name="id_vehiculo_asignado" class="form-control"
                        style="background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted);">
                        <option value="">Sin vehículo</option>
                        <?php foreach ($vehiculos as $v): ?>
                            <option value="<?php echo $v['id_vehiculo']; ?>" <?php echo ($cuadrilla['id_vehiculo_asignado'] ?? '') == $v['id_vehiculo'] ? 'selected' : ''; ?>>
                                <?php echo $v['patente'] . ' - ' . $v['marca'] . ' ' . $v['modelo']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="color: var(--text-secondary); font-weight: 500;">Celular Asignado</label>
                    <input type="text" name="id_celular_asignado" class="form-control"
                        value="<?php echo htmlspecialchars($cuadrilla['id_celular_asignado'] ?? ''); ?>"
                        placeholder="Ej: 3492-123456"
                        style="background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted);">
                </div>
                <div class="form-group">
                    <label style="color: var(--text-secondary); font-weight: 500;">Zona Asignada</label>
                    <input type="text" name="zona_asignada" class="form-control"
                        value="<?php echo htmlspecialchars($cuadrilla['zona_asignada'] ?? ''); ?>"
                        placeholder="Ej: Zona Norte, Centro"
                        style="background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted);">
                </div>
                <div class="form-group">
                    <label style="color: var(--text-secondary); font-weight: 500;">URL WhatsApp</label>
                    <input type="url" name="url_grupo_whatsapp" class="form-control"
                        value="<?php echo htmlspecialchars($cuadrilla['url_grupo_whatsapp'] ?? ''); ?>"
                        placeholder="https://chat.whatsapp.com/..."
                        style="background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--text-muted);">
                </div>
            </div>
        </div>

        <!-- 3. INTEGRANTES -->
        <div class="form-section success"
            style="border: 1px solid var(--color-success); border-left: 5px solid var(--color-success); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-users"></i> 3. Integrantes</h3>
                <div id="membersCount"
                    style="background: var(--color-success); color: white; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9em;">
                    0 activos</div>
            </div>

            <div
                style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed var(--color-success);">
                <label style="display:block; margin-bottom:5px; font-weight:600; color: var(--color-success);">Agregar
                    Integrante:</label>
                <div style="display: flex; gap: 10px;">
                    <select id="selMember" class="form-control"
                        style="flex: 1; background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--text-muted);">
                        <option value="">-- Seleccionar Personal Disponible --</option>
                        <?php foreach ($availableMembers as $m): ?>
                            <option value="<?php echo $m['id_personal']; ?>" data-rol="<?php echo $m['rol']; ?>">
                                <?php echo htmlspecialchars($m['nombre_apellido']); ?> (
                                <?php echo $m['rol']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-success" onclick="addMember()">
                        <i class="fas fa-user-plus"></i> Agregar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm" id="membersTable" style="color: var(--text-primary);">
                    <thead>
                        <tr style="background: var(--bg-secondary);">
                            <th style="color: var(--text-secondary);">Nombre</th>
                            <th style="color: var(--text-secondary);">Rol</th>
                            <th style="color: var(--text-secondary);">DNI</th>
                            <th style="color: var(--text-secondary); text-align:center;">Estado</th>
                            <th width="100" class="text-center" style="color: var(--text-secondary);">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="membersBody">
                        <!-- Members rendered here by JS -->
                    </tbody>
                </table>
            </div>
            <div id="membersInputs"></div>
        </div>

        <!-- 4. HERRAMIENTAS -->
        <div class="form-section"
            style="border: 1px solid var(--accent-primary); border-left: 5px solid var(--accent-primary); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="color: var(--text-primary); margin-top: 0;"><i class="fas fa-tools"></i> 4. Herramientas
                    Asignadas</h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if ($isEdit): ?>
                        <a href="generar_responsabilidad.php?id=<?php echo $cuadrilla['id_cuadrilla']; ?>" target="_blank"
                            class="btn btn-outline"
                            style="font-size: 0.85em; padding: 6px 14px; color: var(--color-danger); border-color: var(--color-danger);">
                            <i class="fas fa-file-pdf"></i> Generar PDF Responsabilidad
                        </a>
                    <?php endif; ?>
                    <div id="toolsTotal"
                        style="background: var(--accent-primary); color: white; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9em;">
                        Total: $0.00
                    </div>
                </div>
            </div>

            <div
                style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed var(--accent-primary);">
                <label style="display:block; margin-bottom:5px; font-weight:600; color: var(--accent-primary);">Asignar
                    Herramienta:</label>
                <div style="display: flex; gap: 10px;">
                    <select id="selTool" class="form-control"
                        style="flex: 1; background-color: var(--bg-card); color: var(--text-primary); border: 2px solid var(--accent-primary); border-radius: 8px; padding: 10px;">
                        <option value="">-- Seleccionar Herramienta Disponible --</option>
                        <?php foreach ($availableTools as $tool): ?>
                            <option value="<?php echo $tool['id_herramienta']; ?>"
                                data-nombre="<?php echo htmlspecialchars($tool['nombre']); ?>"
                                data-marca="<?php echo htmlspecialchars($tool['marca'] ?? ''); ?>"
                                data-precio="<?php echo $tool['precio_reposicion']; ?>">
                                <?php echo htmlspecialchars($tool['nombre']); ?>
                                <?php if ($tool['marca']): ?>(<?php echo htmlspecialchars($tool['marca']); ?>)<?php endif; ?>
                                - $<?php echo number_format($tool['precio_reposicion'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary" onclick="addTool()"
                        style="padding: 10px 20px; min-width: 120px;">
                        <i class="fas fa-plus"></i> Asignar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm" id="toolsTable" style="color: var(--text-primary);">
                    <thead>
                        <tr style="background: var(--bg-secondary);">
                            <th style="color: var(--text-secondary);">#</th>
                            <th style="color: var(--text-secondary);">Herramienta</th>
                            <th style="color: var(--text-secondary);">Marca</th>
                            <th style="color: var(--text-secondary);">Fecha Asignación</th>
                            <th style="color: var(--text-secondary); text-align: right;">Precio Reposición</th>
                            <th width="50" class="text-center" style="color: var(--text-secondary);">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="toolsBody">
                        <!-- Tools rendered here by JS -->
                    </tbody>
                </table>
            </div>
            <div id="toolsInputs"></div>
        </div>

        <!-- BOTONES -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <a href="index.php" class="btn btn-outline"
                style="color: var(--text-secondary); border-color: var(--text-muted);">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                <?php echo $isEdit ? 'Guardar Cambios' : 'Crear Cuadrilla'; ?>
            </button>
        </div>
    </form>
</div>

<!-- MODAL NUEVA ESPECIALIDAD -->
<div id="modalNewType"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div
        style="background: var(--bg-card); padding: 25px; border-radius: 12px; width: 400px; box-shadow: var(--shadow-lg); border: 1px solid var(--text-muted);">
        <h4 style="margin-top: 0; margin-bottom: 20px; color: var(--text-primary);"><i class="fas fa-tag"></i> Nuevo
            Tipo de Trabajo</h4>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-secondary);">Nombre
                *</label>
            <input type="text" id="newTypeNombre" class="form-control" placeholder="Ej: Electricidad"
                style="width: 100%; padding: 8px; border: 1px solid var(--text-muted); border-radius: 6px; background: var(--bg-tertiary); color: var(--text-primary);">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-secondary);">Código
                (Opcional)</label>
            <input type="text" id="newTypeCodigo" class="form-control" placeholder="Ej: 3.5"
                style="width: 100%; padding: 8px; border: 1px solid var(--text-muted); border-radius: 6px; background: var(--bg-tertiary); color: var(--text-primary);">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeNewTypeModal()"
                style="color: var(--text-secondary); border-color: var(--text-muted);">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="saveNewType()">Guardar</button>
        </div>
    </div>
</div>

<!-- DATA FOR JS -->
<script>
    // Data from PHP
    const allWorkTypes = <?php echo json_encode($allTypes); ?>;
    const initialSquadTypes = <?php echo json_encode($squadTypes); ?>;
    const initialMembers = <?php echo json_encode($squadMembers); ?>.map(m => ({ ...m, activo: (m.estado_documentacion !== 'Baja') }));
    const initialTools = <?php echo json_encode($squadTools); ?>;

    // State
    let currentTypes = [...initialSquadTypes]; // Store IDs
    let currentMembers = [...initialMembers];  // Store Objects {id_personal, nombre_apellido, rol}
    let currentTools = [...initialTools];      // Store Objects {id_herramienta, nombre, marca, precio_reposicion}

    function init() {
        renderTypes();
        renderMembers();
        renderTools();
    }

    // --- WORK TYPES LOGIC ---
    function addWorkType() {
        const sel = document.getElementById('selTipoTrabajo');
        const id = sel.value;
        if (!id) return;

        if (currentTypes.includes(parseInt(id)) || currentTypes.includes(id)) {
            alert('Este tipo de trabajo ya está agregado.');
            return;
        }

        currentTypes.push(id);
        renderTypes();
        sel.value = ''; // Reset
    }

    function removeType(id) {
        currentTypes = currentTypes.filter(t => t != id);
        renderTypes();
    }

    function renderTypes() {
        const list = document.getElementById('workTypesList');
        const inputs = document.getElementById('workTypesInputs');

        list.innerHTML = '';
        inputs.innerHTML = '';

        if (currentTypes.length === 0) {
            list.innerHTML = '<span style="color: var(--text-muted); font-style: italic; padding: 10px;">No hay tipos de trabajo asignados. Seleccione del desplegable y haga clic en "Agregar".</span>';
            return;
        }

        currentTypes.forEach(id => {
            // Find by id_tipologia (compare as string since JSON might vary)
            const typeObj = allWorkTypes.find(t => String(t.id_tipologia) === String(id));
            if (!typeObj) {
                console.warn('Tipo no encontrado con ID:', id);
                return;
            }

            // Visual Tag
            const tag = document.createElement('span');
            tag.className = 'type-tag';
            // Display [Code] Nombre with delete button
            const codeDisplay = typeObj.codigo_trabajo ? `[${typeObj.codigo_trabajo}] ` : '';
            tag.innerHTML = `
                <span class="type-text">${codeDisplay}${typeObj.nombre}</span>
                <button type="button" class="type-delete-btn" onclick="removeType('${id}')" title="Eliminar este tipo">
                    <i class="fas fa-times"></i>
                </button>
            `;
            list.appendChild(tag);

            // Hidden Input
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tipos_trabajo[]';
            input.value = id;
            inputs.appendChild(input);
        });
    }

    // --- MEMBERS LOGIC ---
    function addMember() {
        const sel = document.getElementById('selMember');
        const id = sel.value;
        if (!id) return;

        // Check duplicates
        if (currentMembers.find(m => m.id_personal == id)) {
            alert('El integrante ya está en la lista.');
            return;
        }

        const name = sel.options[sel.selectedIndex].text;
        const rol = sel.options[sel.selectedIndex].getAttribute('data-rol');

        const newMember = {
            id_personal: id,
            nombre_apellido: name.split(' (')[0],
            rol: rol,
            dni: '',
            activo: true
        };

        currentMembers.push(newMember);
        renderMembers();
        sel.value = '';
    }

    function removeMember(id) {
        if (!confirm('¿Quitar definitivamente a este integrante de la cuadrilla?')) return;
        currentMembers = currentMembers.filter(m => m.id_personal != id);
        renderMembers();
    }

    function toggleMemberBaja(id) {
        const member = currentMembers.find(m => m.id_personal == id);
        if (!member) return;
        member.activo = !member.activo;
        renderMembers();
    }

    function renderMembers() {
        const tbody = document.getElementById('membersBody');
        const inputs = document.getElementById('membersInputs');
        const countEl = document.getElementById('membersCount');

        tbody.innerHTML = '';
        inputs.innerHTML = '';

        if (currentMembers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin integrantes asignados</td></tr>';
            if (countEl) countEl.textContent = '0 activos';
            return;
        }

        const activos = currentMembers.filter(m => m.activo !== false).length;
        const bajas = currentMembers.length - activos;
        if (countEl) countEl.textContent = `${activos} activo${activos !== 1 ? 's' : ''}${bajas > 0 ? ` / ${bajas} baja` : ''}`;

        currentMembers.forEach(m => {
            const isActive = m.activo !== false;
            const tr = document.createElement('tr');
            tr.style.opacity = isActive ? '1' : '0.5';
            if (!isActive) tr.style.textDecoration = 'line-through';

            const estadoBadge = isActive
                ? '<span style="background:rgba(16,185,129,0.15); color:var(--color-success); padding:2px 8px; border-radius:4px; font-size:0.8em; font-weight:600;">ACTIVO</span>'
                : '<span style="background:rgba(239,68,68,0.15); color:var(--color-danger); padding:2px 8px; border-radius:4px; font-size:0.8em; font-weight:600;">BAJA</span>';

            const actionBtns = isActive
                ? `<button type="button" class="btn-member-action baja" onclick="toggleMemberBaja('${m.id_personal}')" title="Dar de Baja">
                       <i class="fas fa-user-slash"></i>
                   </button>
                   <button type="button" class="btn-icon-del" onclick="removeMember('${m.id_personal}')" title="Quitar de cuadrilla">
                       <i class="fas fa-trash"></i>
                   </button>`
                : `<button type="button" class="btn-member-action reactivar" onclick="toggleMemberBaja('${m.id_personal}')" title="Reactivar">
                       <i class="fas fa-user-check"></i>
                   </button>
                   <button type="button" class="btn-icon-del" onclick="removeMember('${m.id_personal}')" title="Quitar de cuadrilla">
                       <i class="fas fa-trash"></i>
                   </button>`;

            tr.innerHTML = `
                <td><i class="fas fa-user-circle" style="color:${isActive ? 'var(--color-success)' : 'var(--color-danger)'}; margin-right:5px;"></i> ${m.nombre_apellido}</td>
                <td><span class="badge-rol">${m.rol || '-'}</span></td>
                <td>${m.dni || '-'}</td>
                <td class="text-center">${estadoBadge}</td>
                <td class="text-center" style="text-decoration:none;">${actionBtns}</td>
            `;
            tbody.appendChild(tr);

            // Only send active members as hidden inputs to the backend
            if (isActive) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'miembros[]';
                input.value = m.id_personal;
                inputs.appendChild(input);
            }
        });
    }

    // --- TOOLS LOGIC ---
    function addTool() {
        const sel = document.getElementById('selTool');
        const id = sel.value;
        if (!id) return;

        // Check duplicates
        if (currentTools.find(t => t.id_herramienta == id)) {
            alert('Esta herramienta ya está asignada.');
            return;
        }

        const opt = sel.options[sel.selectedIndex];
        const newTool = {
            id_herramienta: id,
            nombre: opt.getAttribute('data-nombre'),
            marca: opt.getAttribute('data-marca') || '',
            precio_reposicion: parseFloat(opt.getAttribute('data-precio')) || 0
        };

        currentTools.push(newTool);
        renderTools();
        sel.value = '';
    }

    function removeTool(id) {
        currentTools = currentTools.filter(t => t.id_herramienta != id);
        renderTools();
    }

    function renderTools() {
        const tbody = document.getElementById('toolsBody');
        const inputs = document.getElementById('toolsInputs');
        const totalEl = document.getElementById('toolsTotal');

        tbody.innerHTML = '';
        inputs.innerHTML = '';

        if (currentTools.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin herramientas asignadas</td></tr>';
            totalEl.textContent = 'Total: $0.00';
            return;
        }

        let total = 0;
        let idx = 0;
        currentTools.forEach(t => {
            idx++;
            const precio = parseFloat(t.precio_reposicion) || 0;
            total += precio;

            // Format date
            let fechaDisplay = '-';
            if (t.fecha_asignacion) {
                const parts = t.fecha_asignacion.split('-');
                if (parts.length === 3) fechaDisplay = parts[2] + '/' + parts[1] + '/' + parts[0];
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="color: var(--text-muted); font-weight: 600;">${idx}</td>
                <td><i class="fas fa-wrench" style="color: var(--accent-primary); margin-right:5px;"></i> ${t.nombre}</td>
                <td>${t.marca || '-'}</td>
                <td><i class="fas fa-calendar-alt" style="color: var(--text-muted); margin-right:4px;"></i> ${fechaDisplay}</td>
                <td style="text-align: right; font-weight: 600;">$${precio.toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn-icon-del" onclick="removeTool('${t.id_herramienta}')" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'herramientas[]';
            input.value = t.id_herramienta;
            inputs.appendChild(input);
        });

        totalEl.textContent = 'Total: $' + total.toFixed(2);
    }

    // --- MODAL & AJAX LOGIC ---
    function openNewTypeModal() {
        document.getElementById('modalNewType').style.display = 'flex';
        document.getElementById('newTypeNombre').focus();
    }

    function closeNewTypeModal() {
        document.getElementById('modalNewType').style.display = 'none';
        document.getElementById('newTypeNombre').value = '';
        document.getElementById('newTypeCodigo').value = '';
    }

    function saveNewType() {
        const nombre = document.getElementById('newTypeNombre').value.trim();
        const codigo = document.getElementById('newTypeCodigo').value.trim();

        if (!nombre) {
            alert('El nombre es obligatorio');
            return;
        }

        // AJAX Request
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('codigo', codigo);

        fetch('save_tipo_trabajo_ajax.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add to global list if using it, or just to dropdown
                    const newOption = {
                        id_tipologia: data.data.id_tipologia,
                        nombre: data.data.nombre,
                        codigo_trabajo: data.data.codigo_trabajo
                    };

                    // Add to Dropdown
                    const sel = document.getElementById('selTipoTrabajo');
                    const opt = document.createElement('option');
                    opt.value = newOption.id_tipologia;
                    const codeDisplay = newOption.codigo_trabajo ? `[${newOption.codigo_trabajo}] ` : '';
                    opt.text = codeDisplay + newOption.nombre;
                    sel.add(opt); // append at end

                    // Select it
                    sel.value = newOption.id_tipologia;

                    // Update global Data array (needed for renderTypes if we want to support it immediately)
                    allWorkTypes.push(newOption);

                    // Add to list immediately? User might want to click "Agregar" manually to confirm.
                    // The prompt said: "posteriormente figure en el desplegable". 
                    // So adding to dropdown and selecting it is good. The user clicks "Agregar" next.

                    closeNewTypeModal();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar');
            });
    }

    // Init on load
    document.addEventListener('DOMContentLoaded', init);

</script>

<style>
    /* CSS Styles for new components */
    .form-section.success {
        border-color: #81c784;
        border-left-color: #4caf50;
    }

    .tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
        min-height: 50px;
        padding: 12px;
        border: 2px dashed var(--accent-primary);
        border-radius: 10px;
        background: var(--bg-secondary);
        align-items: center;
    }

    .tags-container:empty::before {
        content: 'No hay tipos de trabajo asignados';
        color: var(--text-muted);
        font-style: italic;
    }

    .type-tag {
        background: var(--accent-primary);
        color: white;
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 0.9em;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
        box-shadow: 0 2px 6px rgba(0, 115, 168, 0.3);
        transition: all 0.2s ease;
    }

    .type-tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 115, 168, 0.4);
    }

    .type-tag .type-text {
        flex: 1;
    }

    .type-delete-btn {
        background: rgba(255, 255, 255, 0.25);
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: white;
        padding: 0;
        margin-left: 5px;
    }

    .type-delete-btn:hover {
        background: var(--color-danger);
        transform: scale(1.15);
    }

    .type-delete-btn i {
        font-size: 0.75em;
    }

    .badge-rol {
        background: #f5f5f5;
        color: #616161;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        font-weight: 500;
        border: 1px solid #e0e0e0;
    }

    .btn-icon-del {
        background: none;
        border: none;
        color: #ef5350;
        cursor: pointer;
        transition: transform 0.2s;
        padding: 5px;
    }

    .btn-icon-del:hover {
        transform: scale(1.2);
        color: #d32f2f;
    }

    /* Member action buttons */
    .btn-member-action {
        background: none;
        border: 1px solid transparent;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.85em;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-member-action.baja {
        color: var(--color-warning);
        border-color: var(--color-warning);
    }

    .btn-member-action.baja:hover {
        background: rgba(245, 158, 11, 0.15);
        transform: scale(1.05);
    }

    .btn-member-action.reactivar {
        color: var(--color-success);
        border-color: var(--color-success);
    }

    .btn-member-action.reactivar:hover {
        background: rgba(16, 185, 129, 0.15);
        transform: scale(1.05);
    }

    /* Input Group styling */
    .input-group select,
    .input-group button,
    #selMember,
    button[onclick="addMember()"] {
        height: 38px;
    }
</style>

<?php require_once '../../includes/footer.php'; ?>