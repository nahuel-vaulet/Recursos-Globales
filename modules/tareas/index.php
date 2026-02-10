<?php
// modules/tareas/index.php
require_once '../../includes/header.php';
require_once '../../config/database.php';
?>

<style>
    /* Layout Principal */
    .task-container {
        padding: 0 20px;
    }

    /* Tabs */
    .tabs {
        display: flex;
        gap: var(--spacing-sm);
        margin-bottom: var(--spacing-md);
        border-bottom: 1px solid var(--bg-tertiary);
        padding-bottom: var(--spacing-sm);
    }

    .tab-btn {
        background: transparent;
        color: var(--text-secondary);
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 0.95rem;
        transition: all 0.2s;
        border-radius: var(--border-radius-sm) var(--border-radius-sm) 0 0;
    }

    .tab-btn:hover {
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .tab-btn.active {
        color: var(--accent-primary);
        border-bottom: 3px solid var(--accent-primary);
        font-weight: 600;
    }

    /* Task Cards */
    .task-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .task-card {
        background: var(--bg-card);
        border-radius: var(--border-radius-md);
        padding: var(--spacing-md);
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-left: 5px solid transparent;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .task-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .task-card.priority-Alta {
        border-left-color: var(--color-danger);
    }

    .task-card.priority-Media {
        border-left-color: var(--color-warning);
    }

    .task-card.priority-Baja {
        border-left-color: var(--color-success);
    }

    .task-card.completed {
        opacity: 0.6;
    }

    .task-card.completed .task-title {
        text-decoration: line-through;
    }

    .task-content {
        flex-grow: 1;
        margin-left: var(--spacing-md);
    }

    .task-title {
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-primary);
    }

    .task-meta {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        gap: 12px;
        margin-top: 4px;
        flex-wrap: wrap;
    }

    /* Calendar Grid */
    .calendar-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-md);
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }

    .calendar-header {
        font-weight: 600;
        text-align: center;
        padding: 10px;
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .calendar-day {
        min-height: 90px;
        background: var(--bg-secondary);
        padding: 8px;
        border-radius: var(--border-radius-sm);
        font-size: 0.8rem;
    }

    .calendar-day.today {
        border: 2px solid var(--accent-primary);
    }

    .calendar-day-num {
        font-weight: bold;
        margin-bottom: 5px;
        text-align: center;
    }

    .cal-task-dot {
        font-size: 0.7rem;
        padding: 2px 5px;
        border-radius: 4px;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(2px);
    }

    .modal-content {
        background: var(--bg-card);
        padding: var(--spacing-lg);
        border-radius: var(--border-radius-lg);
        width: 100%;
        max-width: 500px;
        border: 1px solid var(--bg-tertiary);
    }

    .hidden {
        display: none !important;
    }
</style>

<div class="task-container">
    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="margin: 0; font-size: 1.4em; color: var(--text-primary);"><i class="fas fa-tasks"
                    style="color: var(--accent-primary);"></i> Gestión de Tareas</h2>
            <p style="margin: 5px 0 0; color: var(--text-secondary); font-size: 0.9em;">Administra tu lista de tareas
                pendientes.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-danger" onclick="purgeTasks()"><i class="fas fa-trash"></i> Purgar</button>
            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus-circle"></i> Nueva
                Tarea</button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchView('list')" id="btn-view-list"><i class="fas fa-list"></i>
            Lista</button>
        <button class="tab-btn" onclick="switchView('calendar')" id="btn-view-calendar"><i
                class="fas fa-calendar-alt"></i> Calendario</button>
    </div>

    <!-- View: List -->
    <div id="view-list">
        <!-- Filters Card (Style from ODT) -->
        <div class="card" style="margin-bottom: 20px; padding: 15px;">
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <!-- Search -->
                <div style="flex: 2; min-width: 200px;">
                    <input type="text" id="search-input" class="filter-select" placeholder="🔍 Buscar tarea..."
                        onkeyup="renderTasks()">
                </div>
                <!-- Date From -->
                <div style="flex: 1; min-width: 140px;">
                    <input type="date" id="date-from" class="filter-select" title="Desde" onchange="renderTasks()">
                </div>
                <!-- Date To -->
                <div style="flex: 1; min-width: 140px;">
                    <input type="date" id="date-to" class="filter-select" title="Hasta" onchange="renderTasks()">
                </div>
                <!-- Estado Dropdown -->
                <div style="flex: 1; min-width: 150px;">
                    <select id="filter-estado" class="filter-select" onchange="renderTasks()">
                        <option value="">Estado: Todos</option>
                        <option value="Pendiente" selected>Pendiente</option>
                        <option value="En progreso">En Progreso</option>
                        <option value="Completada">Completada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
                <!-- Prioridad Dropdown -->
                <div style="flex: 1; min-width: 150px;">
                    <select id="filter-prioridad" class="filter-select" onchange="renderTasks()">
                        <option value="">Prioridad: Todas</option>
                        <option value="Alta">🔥 Alta</option>
                        <option value="Media">⚠️ Media</option>
                        <option value="Baja">✅ Baja</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="task-list-container" class="task-list">
            <div style="padding: 40px; text-align: center; color: var(--text-muted);"><i
                    class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>

    <!-- View: Calendar -->
    <div id="view-calendar" class="hidden">
        <div class="calendar-nav card" style="padding: 15px; margin-bottom: 15px;">
            <button class="btn btn-outline" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
            <h3 id="calendar-month-year" style="margin: 0;"></h3>
            <button class="btn btn-outline" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
        </div>

        <div class="card" style="padding: 15px;">
            <div class="calendar-grid" id="calendar-grid-headers">
                <div class="calendar-header">Lun</div>
                <div class="calendar-header">Mar</div>
                <div class="calendar-header">Mié</div>
                <div class="calendar-header">Jue</div>
                <div class="calendar-header">Vie</div>
                <div class="calendar-header">Sáb</div>
                <div class="calendar-header">Dom</div>
            </div>
            <div class="calendar-grid" id="calendar-grid"></div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="task-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <h3 id="modal-title"
            style="margin: 0 0 20px; border-bottom: 1px solid var(--bg-tertiary); padding-bottom: 15px;">Nueva Tarea
        </h3>
        <form id="task-form" onsubmit="handleSave(event)">
            <input type="hidden" id="task-id">

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Título *</label>
                <input type="text" id="task-title" class="form-control" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Descripción</label>
                <textarea id="task-desc" class="form-control" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Fecha Límite</label>
                    <input type="date" id="task-date" class="form-control">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Prioridad</label>
                    <select id="task-priority" class="form-control">
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Estado</label>
                    <select id="task-estado" class="form-control">
                        <option value="Pendiente">Pendiente</option>
                        <option value="En progreso">En Progreso</option>
                        <option value="Completada">Completada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Responsable</label>
                    <input type="text" id="task-resp" class="form-control" value="Cache">
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--bg-tertiary); padding-top: 15px;">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const API_URL = 'api.php';
    let tasks = [];
    let currentView = 'list';
    let currentDate = new Date();

    document.addEventListener('DOMContentLoaded', fetchTasks);

    function switchView(view) {
        currentView = view;
        document.getElementById('view-list').classList.toggle('hidden', view !== 'list');
        document.getElementById('view-calendar').classList.toggle('hidden', view !== 'calendar');
        document.getElementById('btn-view-list').classList.toggle('active', view === 'list');
        document.getElementById('btn-view-calendar').classList.toggle('active', view === 'calendar');
        if (view === 'calendar') renderCalendar();
    }

    async function fetchTasks() {
        try {
            const res = await fetch(API_URL);
            tasks = await res.json();
            renderTasks();
            if (currentView === 'calendar') renderCalendar();
        } catch (e) {
            console.error(e);
            document.getElementById('task-list-container').innerHTML = '<div class="text-center p-4 text-danger">Error al cargar tareas.</div>';
        }
    }

    function renderTasks() {
        const container = document.getElementById('task-list-container');
        const search = document.getElementById('search-input').value.toLowerCase();
        const dateFrom = document.getElementById('date-from').value;
        const dateTo = document.getElementById('date-to').value;
        const estado = document.getElementById('filter-estado').value;
        const prioridad = document.getElementById('filter-prioridad').value;

        const filtered = tasks.filter(t => {
            // Estado
            if (estado && t.estado !== estado) return false;
            // Prioridad
            if (prioridad && t.prioridad !== prioridad) return false;
            // Search
            if (search && !t.titulo.toLowerCase().includes(search) && !(t.descripcion || '').toLowerCase().includes(search)) return false;
            // Date Range
            if (dateFrom && t.fecha_limite < dateFrom) return false;
            if (dateTo && t.fecha_limite > dateTo) return false;
            return true;
        });

        if (filtered.length === 0) {
            container.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted);"><i class="fas fa-calendar-times fa-2x"></i><br>No se encontraron tareas.</div>';
            return;
        }

        container.innerHTML = filtered.map(t => {
            const isOverdue = !['Completada', 'Cancelada'].includes(t.estado) && t.fecha_limite && new Date(t.fecha_limite) < new Date().setHours(0, 0, 0, 0);
            return `
            <div class="task-card priority-${t.prioridad} ${t.estado === 'Completada' ? 'completed' : ''}">
                <div style="display: flex; align-items: center;">
                    <button class="btn btn-icon ${t.estado === 'Completada' ? 'text-success' : 'text-muted'}"
                            onclick="toggleComplete(${t.id}, '${t.estado}')" title="Marcar como completada">
                        <i class="fas fa-${t.estado === 'Completada' ? 'check-circle' : 'circle'} fa-lg"></i>
                    </button>
                    <div class="task-content">
                        <div class="task-title ${isOverdue ? 'text-danger' : ''}">
                            ${t.titulo} ${isOverdue ? '<i class="fas fa-exclamation-circle"></i>' : ''}
                        </div>
                        <div class="task-meta">
                            <span class="${isOverdue ? 'text-danger' : ''}"><i class="fas fa-calendar-alt"></i> ${formatDate(t.fecha_limite)}</span>
                            <span><i class="fas fa-user"></i> ${t.responsable || '-'}</span>
                            <span class="badge ${getPriorityBadge(t.prioridad)}">${t.prioridad}</span>
                            <span class="badge ${getStatusBadge(t.estado)}">${t.estado}</span>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-outline" onclick="editTask(${t.id})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn" style="background: #fee; color: #d32f2f; border: 1px solid #fcc;" onclick="deleteTask(${t.id}, '${t.estado}')" title="Eliminar"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            `;
        }).join('');
    }

    function renderCalendar() {
        const grid = document.getElementById('calendar-grid');
        const monthYear = document.getElementById('calendar-month-year');

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthYear.textContent = new Date(year, month).toLocaleString('es-ES', { month: 'long', year: 'numeric' });

        let firstDay = new Date(year, month, 1).getDay();
        firstDay = firstDay === 0 ? 6 : firstDay - 1; // Monday = 0
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date().toISOString().split('T')[0];

        let html = '';
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day" style="opacity: 0.3;"></div>';
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayTasks = tasks.filter(t => t.fecha_limite === dateStr && !['Completada', 'Cancelada'].includes(t.estado));
            const isToday = dateStr === today;

            html += `
                <div class="calendar-day ${isToday ? 'today' : ''}">
                    <div class="calendar-day-num">${d}</div>
                    ${dayTasks.slice(0, 3).map(t => `<div class="cal-task-dot" style="border-left: 3px solid ${t.prioridad === 'Alta' ? 'var(--color-danger)' : 'var(--color-success)'};" onclick="editTask(${t.id})">${t.titulo}</div>`).join('')}
                    ${dayTasks.length > 3 ? `<div class="cal-task-dot" style="text-align:center;">+${dayTasks.length - 3} más</div>` : ''}
                </div>
            `;
        }
        grid.innerHTML = html;
    }

    function changeMonth(delta) {
        currentDate.setMonth(currentDate.getMonth() + delta);
        renderCalendar();
    }

    // CRUD Actions
    function openModal() {
        document.getElementById('task-form').reset();
        document.getElementById('task-id').value = '';
        document.getElementById('modal-title').textContent = 'Nueva Tarea';
        document.getElementById('task-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('task-modal').classList.add('hidden');
    }

    function editTask(id) {
        const task = tasks.find(t => t.id == id);
        if (!task) return;

        document.getElementById('task-id').value = task.id;
        document.getElementById('task-title').value = task.titulo;
        document.getElementById('task-desc').value = task.descripcion || '';
        document.getElementById('task-date').value = task.fecha_limite || '';
        document.getElementById('task-priority').value = task.prioridad;
        document.getElementById('task-estado').value = task.estado;
        document.getElementById('task-resp').value = task.responsable || '';
        document.getElementById('modal-title').textContent = 'Editar Tarea';
        document.getElementById('task-modal').classList.remove('hidden');
    }

    async function handleSave(e) {
        e.preventDefault();
        const id = document.getElementById('task-id').value;
        const data = {
            titulo: document.getElementById('task-title').value,
            descripcion: document.getElementById('task-desc').value,
            fecha_limite: document.getElementById('task-date').value,
            prioridad: document.getElementById('task-priority').value,
            estado: document.getElementById('task-estado').value,
            responsable: document.getElementById('task-resp').value
        };

        const method = id ? 'PUT' : 'POST';
        const url = id ? `${API_URL}?id=${id}` : API_URL;

        await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        closeModal();
        fetchTasks();
    }

    async function toggleComplete(id, currentStatus) {
        const newStatus = currentStatus === 'Completada' ? 'Pendiente' : 'Completada';
        await fetch(`${API_URL}?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estado: newStatus })
        });
        fetchTasks();
    }

    async function deleteTask(id, status) {
        if (status !== 'Cancelada') {
            if (!confirm('¿Cancelar esta tarea?')) return;
            await fetch(`${API_URL}?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ estado: 'Cancelada' })
            });
        } else {
            if (!confirm('¿Eliminar definitivamente esta tarea?')) return;
            await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
        }
        fetchTasks();
    }

    async function purgeTasks() {
        const pwd = prompt("🔐 Contraseña de Administrador:");
        if (pwd !== '1234') { alert("Contraseña incorrecta"); return; }

        const date = prompt("📅 Eliminar tareas Completadas/Canceladas hasta (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
        if (!date) return;

        const res = await fetch(`${API_URL}?action=purge`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ date: date })
        });

        const data = await res.json();
        if (data.deleted_count > 0) {
            alert(`✅ Eliminadas ${data.deleted_count} tareas.`);
            if (data.csv_data) {
                const link = document.createElement('a');
                link.href = 'data:text/csv;base64,' + data.csv_data;
                link.download = `backup_tareas_${date}.csv`;
                link.click();
            }
        } else {
            alert("No hay tareas para purgar en ese rango.");
        }
        fetchTasks();
    }

    // Helpers
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }

    function getPriorityBadge(p) {
        if (p === 'Alta') return 'badge-danger';
        if (p === 'Media') return 'badge-warning';
        return 'badge-success';
    }

    function getStatusBadge(s) {
        if (s === 'Completada') return 'badge-success';
        if (s === 'En progreso') return 'badge-info';
        if (s === 'Cancelada') return 'badge-secondary';
        return 'badge-warning';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>