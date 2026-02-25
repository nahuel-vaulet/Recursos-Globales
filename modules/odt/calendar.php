<?php
/**
 * [!] ARCH: Vista de Calendario ODT — Mes/Semana/Día
 * Vista Dual: Asignaciones (fecha_asignacion) / Vencimientos (fecha_vencimiento)
 * Datos vía fetch() a /api/calendar.php
 */
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../services/DateUtil.php';

if (!tienePermiso('odt')) {
    header("Location: /APP-Prueba/index.php?msg=forbidden");
    exit();
}
?>

<div class="container-fluid" style="padding: 0 20px;">
    <!-- Header -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="margin: 0; font-size: 1.4em; color: var(--text-primary);">
                <i class="fas fa-calendar-alt" style="color: var(--accent-primary);"></i> Calendario de ODTs
            </h2>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="index.php" class="btn" style="min-height: 45px; padding: 0 15px;
                background: var(--bg-secondary); border: 1px solid var(--accent-primary); color: var(--accent-primary);
                display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <i class="fas fa-list"></i> Lista ODTs
            </a>
        </div>
    </div>

    <!-- Controls -->
    <div class="card" style="padding: 15px; margin-bottom: 20px;">
        <!-- Search Bar -->
        <div style="margin-bottom: 12px;">
            <input type="text" id="calSearchInput" onkeyup="filterCalendar()"
                placeholder="🔍 Buscar por Nro ODT, Dirección, Cuadrilla..." class="filter-select"
                style="margin-bottom: 0; width: 100%; max-width: 500px; min-height: 44px; font-size: 0.9em;">
        </div>
        <div style="display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap;">

            <!-- Mode Toggle: Asignaciones / Vencimientos -->
            <div class="cal-mode-toggle" id="modeToggle">
                <button onclick="switchMode('assigned')" id="btnModeAssigned" class="cal-mode-btn active">
                    <i class="fas fa-user-hard-hat"></i> Asignaciones
                </button>
                <button onclick="switchMode('duedate')" id="btnModeDuedate" class="cal-mode-btn">
                    <i class="fas fa-clock"></i> Vencimientos
                </button>
            </div>

            <!-- View Switcher: Mes / Semana / Día -->
            <div
                style="display: flex; gap: 0; border-radius: var(--border-radius-md); overflow: hidden; border: 1px solid var(--accent-primary);">
                <button onclick="switchView('month')" id="btnMonth" class="cal-view-btn active">
                    <i class="fas fa-th"></i> Mes
                </button>
                <button onclick="switchView('week')" id="btnWeek" class="cal-view-btn">
                    <i class="fas fa-th-list"></i> Semana
                </button>
                <button onclick="switchView('day')" id="btnDay" class="cal-view-btn">
                    <i class="fas fa-calendar-day"></i> Día
                </button>
            </div>

            <!-- Navigation -->
            <div style="display: flex; gap: 8px; align-items: center;">
                <button onclick="navigatePrev()" class="btn cal-nav-btn" title="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="navigateToday()" class="btn cal-nav-btn" style="padding: 0 18px; font-weight: 600;">
                    Hoy
                </button>
                <span id="calTitle"
                    style="font-size: 1.1em; font-weight: 600; color: var(--text-primary); min-width: 200px; text-align: center;">
                    ...
                </span>
                <button onclick="navigateNext()" class="btn cal-nav-btn" title="Siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Column Selector (day view only) + Counter -->
            <div style="display: flex; gap: 12px; align-items: center;">
                <div id="colSelectorWrap" style="position: relative; display: none;">
                    <button id="calColSelectorBtn" class="btn cal-nav-btn" onclick="toggleCalColSelector()"
                        title="Columnas visibles">
                        <i class="fas fa-columns"></i> Columnas
                    </button>
                    <div id="calColSelectorDropdown" class="cal-col-dropdown" style="display: none;"></div>
                </div>
                <div style="font-size: 0.85em; color: var(--text-muted);">
                    <span id="calTotal">-</span> <span id="calTotalLabel">ODTs asignadas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Container -->
    <div id="calendarContainer" class="card" style="padding: 15px; min-height: 400px;">
        <div id="calLoading" style="text-align: center; padding: 60px; color: var(--text-muted);">
            <i class="fas fa-spinner fa-spin" style="font-size: 2em;"></i>
            <p>Cargando calendario...</p>
        </div>
        <div id="calContent" style="display: none;">
            <!-- Dynamic content rendered by JS -->
        </div>
    </div>
</div>

<!-- Error Stack Modal -->
<div id="errorStackModal" class="error-modal-overlay" style="display: none;">
    <div class="error-modal-content" style="max-width: 560px;">
        <div class="error-modal-header"
            style="background: linear-gradient(135deg, #d32f2f, #b71c1c); color: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-exclamation-triangle"></i> <span id="errModalTitle">Error</span></span>
            <button onclick="closeErrorModal()"
                style="background: none; border: none; color: white; font-size: 1.4em; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 20px;">
            <div id="errModalBody" style="font-size: 0.9em; color: var(--text-secondary);"></div>
            <pre id="errModalStack"
                style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 6px; padding: 12px; font-size: 0.8em; margin-top: 12px; overflow-x: auto; max-height: 200px; color: var(--text-primary);"></pre>
        </div>
        <div
            style="padding: 12px 20px; border-top: 1px solid var(--border-color); display: flex; gap: 8px; justify-content: flex-end;">
            <button onclick="copyErrorStack()" class="btn"
                style="background: var(--accent-primary); color: white; min-height: 40px; padding: 0 16px; font-size: 0.85em;">
                <i class="fas fa-copy"></i> Copiar Error Stack
            </button>
            <button onclick="closeErrorModal()" class="btn"
                style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); min-height: 40px; padding: 0 16px; font-size: 0.85em;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<style>
    /* ── Mode Toggle ── */
    .cal-mode-toggle {
        display: flex;
        gap: 0;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        border: 2px solid var(--accent-primary);
        box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15);
    }

    .cal-mode-btn {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        background: var(--bg-secondary);
        color: var(--text-secondary);
        font-weight: 700;
        font-size: 0.85em;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        min-height: 44px;
    }

    .cal-mode-btn:hover {
        opacity: 0.85;
    }

    .cal-mode-btn.active {
        background: linear-gradient(135deg, var(--accent-primary), #1565C0);
        color: white;
        box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.1);
    }

    .cal-mode-btn.active-duedate {
        background: linear-gradient(135deg, #FF6F00, #E65100);
        color: white;
    }

    /* ── View buttons ── */
    .cal-view-btn {
        padding: 10px 18px;
        cursor: pointer;
        border: none;
        background: var(--bg-secondary);
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.85em;
        transition: all 0.2s;
        min-height: 44px;
    }

    .cal-view-btn:hover {
        background: rgba(33, 150, 243, 0.1);
        color: var(--accent-primary);
    }

    .cal-view-btn.active {
        background: var(--accent-primary);
        color: white;
    }

    .cal-nav-btn {
        min-height: 44px;
        min-width: 44px;
        padding: 0 12px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9em;
    }

    /* ── Month Grid ── */
    .cal-month-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
    }

    .cal-month-header {
        text-align: center;
        padding: 10px 4px;
        font-weight: 700;
        font-size: 0.8em;
        color: var(--text-muted);
        text-transform: uppercase;
        background: var(--bg-secondary);
    }

    .cal-month-cell {
        min-height: 90px;
        border: 1px solid var(--border-color);
        padding: 6px;
        background: var(--bg-primary);
        border-radius: 4px;
        position: relative;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }

    .cal-month-cell:hover {
        background: rgba(33, 150, 243, 0.05);
    }

    .cal-month-cell.today {
        border-color: var(--accent-primary);
        border-width: 2px;
    }

    .cal-month-cell.other-month {
        opacity: 0.3;
    }

    .cal-day-number {
        font-weight: 700;
        font-size: 0.85em;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    /* ── Crew Pills ── */
    .cal-crew-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.7em;
        font-weight: 600;
        margin: 1px;
        white-space: nowrap;
    }

    /* ── Alert Level Badges (Vencimientos mode) ── */
    .cal-alert-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.7em;
        font-weight: 700;
        margin: 1px;
        white-space: nowrap;
    }

    .alert-vencida {
        background: rgba(211, 47, 47, 0.2);
        color: #d32f2f;
        border: 1px solid rgba(211, 47, 47, 0.3);
    }

    .alert-critica {
        background: rgba(245, 124, 0, 0.2);
        color: #e65100;
        border: 1px solid rgba(245, 124, 0, 0.3);
    }

    .alert-proxima {
        background: rgba(255, 193, 7, 0.2);
        color: #f57f17;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .alert-normal {
        background: rgba(76, 175, 80, 0.15);
        color: #2e7d32;
        border: 1px solid rgba(76, 175, 80, 0.3);
    }

    /* ── Week Table ── */
    .cal-week-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cal-week-table th {
        padding: 12px 8px;
        text-align: center;
        border: 1px solid var(--border-color);
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 0.85em;
    }

    .cal-week-table td {
        padding: 10px;
        text-align: center;
        border: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .cal-week-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.9em;
        cursor: pointer;
        transition: transform 0.15s;
    }

    .cal-week-count:hover {
        transform: scale(1.15);
    }

    /* ── Day Detail ── */
    .cal-day-section {
        margin-bottom: 20px;
        border-radius: var(--border-radius-md);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .cal-day-crew-header {
        padding: 12px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: white;
        font-size: 0.95em;
    }

    .cal-day-odt-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9em;
        transition: background 0.15s;
        flex-wrap: wrap;
    }

    .cal-day-odt-row:hover {
        background: rgba(0, 0, 0, 0.03);
    }

    .cal-day-odt-row:last-child {
        border-bottom: none;
    }

    /* ── Column Selector ── */
    .cal-col-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        min-width: 200px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-md);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        padding: 8px 0;
        max-height: 350px;
        overflow-y: auto;
    }

    .cal-col-dropdown label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        cursor: pointer;
        font-size: 0.85em;
        color: var(--text-primary);
        transition: background 0.15s;
    }

    .cal-col-dropdown label:hover {
        background: rgba(33, 150, 243, 0.08);
    }

    /* ── Error modal ── */
    .error-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .error-modal-content {
        background: var(--bg-primary);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        width: 100%;
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .cal-month-grid {
            grid-template-columns: repeat(7, 1fr);
        }

        .cal-month-cell {
            min-height: 55px;
            padding: 3px;
        }

        .cal-crew-pill,
        .cal-alert-pill {
            font-size: 0.6em;
            padding: 1px 4px;
        }

        .cal-day-number {
            font-size: 0.72em;
        }

        .cal-mode-btn {
            padding: 8px 12px;
            font-size: 0.78em;
        }

        .cal-view-btn {
            padding: 8px 12px;
            font-size: 0.78em;
        }

        .cal-day-odt-row {
            font-size: 0.82em;
            gap: 6px;
        }

        .cal-week-table {
            font-size: 0.82em;
        }
    }
</style>

<script>
    // ── Constants ──
    const MONTHS_ES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const DAYS_ES = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const DAYS_FULL = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    const ALERT_COLORS = {
        vencida: { bg: 'rgba(211,47,47,0.2)', color: '#d32f2f', label: 'Vencida' },
        critica: { bg: 'rgba(245,124,0,0.2)', color: '#e65100', label: 'Crítica' },
        proxima: { bg: 'rgba(255,193,7,0.2)', color: '#f57f17', label: 'Próxima' },
        normal: { bg: 'rgba(76,175,80,0.15)', color: '#2e7d32', label: 'OK' },
    };

    // Columnas para Day View
    const DAY_COL_CONFIG = [
        { key: 'numero', label: 'Nº ODT', fixed: true },
        { key: 'direccion', label: 'Dirección', fixed: true },
        { key: 'tipo', label: 'Tipo Trabajo', default: true },
        { key: 'estado', label: 'Estado', default: true },
        { key: 'orden', label: 'Orden', default: true },
        { key: 'prioridad', label: 'Prioridad', default: false },
        { key: 'vencimiento', label: 'Vencimiento', default: true },
    ];

    // ── State ──
    let currentView = 'month';
    let currentMode = 'assigned'; // 'assigned' | 'duedate'
    let currentDate = new Date();
    let dayVisibleCols = loadDayColPrefs();
    let lastCalData = null; // Cache last API response for client-side filtering

    // ── Mode Toggle ──
    function switchMode(mode) {
        currentMode = mode;
        document.querySelectorAll('.cal-mode-btn').forEach(b => {
            b.classList.remove('active', 'active-duedate');
        });
        const activeBtn = document.getElementById('btnMode' + mode.charAt(0).toUpperCase() + mode.slice(1));
        activeBtn.classList.add(mode === 'duedate' ? 'active-duedate' : 'active');

        // Update label
        document.getElementById('calTotalLabel').textContent =
            mode === 'duedate' ? 'ODTs con vencimiento' : 'ODTs asignadas';

        loadCalendar();
    }

    // ── View Switcher ──
    function switchView(view) {
        currentView = view;
        document.querySelectorAll('.cal-view-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn' + view.charAt(0).toUpperCase() + view.slice(1)).classList.add('active');

        // Show/hide column selector (only in day view)
        document.getElementById('colSelectorWrap').style.display = view === 'day' ? '' : 'none';

        loadCalendar();
    }

    // ── Navigation ──
    function navigatePrev() {
        if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() - 1);
        else if (currentView === 'week') currentDate.setDate(currentDate.getDate() - 7);
        else currentDate.setDate(currentDate.getDate() - 1);
        loadCalendar();
    }
    function navigateNext() {
        if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() + 1);
        else if (currentView === 'week') currentDate.setDate(currentDate.getDate() + 7);
        else currentDate.setDate(currentDate.getDate() + 1);
        loadCalendar();
    }
    function navigateToday() { currentDate = new Date(); loadCalendar(); }

    function formatDate(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    // ── Main Loader ──
    async function loadCalendar() {
        const loading = document.getElementById('calLoading');
        const content = document.getElementById('calContent');
        loading.style.display = 'block';
        content.style.display = 'none';
        loading.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2em;"></i><p>Cargando calendario...</p>';

        let url = `/APP-Prueba/api/calendar.php?mode=${currentMode}&`;
        let titleText = '';

        if (currentView === 'month') {
            const y = currentDate.getFullYear();
            const m = currentDate.getMonth() + 1;
            url += `view=month&year=${y}&month=${m}`;
            titleText = MONTHS_ES[m - 1] + ' ' + y;
        } else if (currentView === 'week') {
            url += `view=week&date=${formatDate(currentDate)}`;
            const d = new Date(currentDate);
            const day = d.getDay() || 7;
            d.setDate(d.getDate() - day + 1);
            const end = new Date(d); end.setDate(end.getDate() + 6);
            titleText = `${d.getDate()} ${MONTHS_ES[d.getMonth()]} — ${end.getDate()} ${MONTHS_ES[end.getMonth()]}`;
        } else {
            url += `view=day&date=${formatDate(currentDate)}`;
            titleText = `${DAYS_FULL[currentDate.getDay() === 0 ? 6 : currentDate.getDay() - 1]} ${currentDate.getDate()} ${MONTHS_ES[currentDate.getMonth()]}`;
        }

        document.getElementById('calTitle').textContent = titleText;

        try {
            const res = await fetch(url);
            if (!res.ok) {
                const errBody = await res.json().catch(() => null);
                throw {
                    httpCode: res.status,
                    errorId: errBody?.error?.id || `CAL-HTTP-${res.status}`,
                    message: errBody?.error?.message || errBody?.error || `HTTP ${res.status}`,
                    endpoint: url
                };
            }
            const json = await res.json();
            if (!json.success) {
                throw {
                    errorId: json.error?.id || 'CAL-API-ERR',
                    message: json.error?.message || json.error || 'Error desconocido',
                    endpoint: url
                };
            }

            lastCalData = json.data; // Cache for search filtering
            const searchTerm = document.getElementById('calSearchInput').value.toLowerCase().trim();
            if (currentView === 'month') renderMonth(json.data, searchTerm);
            else if (currentView === 'week') renderWeek(json.data, searchTerm);
            else renderDay(json.data, searchTerm);

            loading.style.display = 'none';
            content.style.display = 'block';
        } catch (err) {
            showErrorStack(err, url);
            loading.innerHTML = `<p style="color:#d32f2f;"><i class="fas fa-exclamation-triangle"></i> Error al cargar. <a href="javascript:loadCalendar()" style="color: var(--accent-primary);">Reintentar</a></p>`;
        }
    }

    // ── Client-side Search Filter ──
    function filterCalendar() {
        if (!lastCalData) return;
        const searchTerm = document.getElementById('calSearchInput').value.toLowerCase().trim();
        const content = document.getElementById('calContent');
        content.style.display = 'block';
        document.getElementById('calLoading').style.display = 'none';
        if (currentView === 'month') renderMonth(lastCalData, searchTerm);
        else if (currentView === 'week') renderWeek(lastCalData, searchTerm);
        else renderDay(lastCalData, searchTerm);
    }

    // ── Render: MONTH ──
    function renderMonth(data, searchTerm) {
        searchTerm = searchTerm || '';
        const content = document.getElementById('calContent');
        const today = formatDate(new Date());
        const isDueDate = currentMode === 'duedate';

        const firstDay = new Date(data.anio, data.mes - 1, 1);
        const startDow = firstDay.getDay() || 7;
        const daysInMonth = data.rango.dias;

        let html = '<div class="cal-month-grid">';
        DAYS_ES.forEach(d => html += `<div class="cal-month-header">${d}</div>`);

        for (let i = 1; i < startDow; i++) {
            html += '<div class="cal-month-cell other-month"></div>';
        }

        let totalOdts = 0;

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${data.anio}-${String(data.mes).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isToday = dateStr === today;
            let crews = data.dias[dateStr] || [];

            // Filter crews by search term
            if (searchTerm) {
                crews = crews.filter(c => c.nombre.toLowerCase().includes(searchTerm));
            }

            html += `<div class="cal-month-cell ${isToday ? 'today' : ''}" onclick="drillDown('${dateStr}')">`;
            html += `<div class="cal-day-number">${d}</div>`;

            crews.forEach(c => {
                totalOdts += c.count;
                if (isDueDate && c.nivel_alerta) {
                    const alertInfo = ALERT_COLORS[c.nivel_alerta] || ALERT_COLORS.normal;
                    html += `<span class="cal-alert-pill alert-${c.nivel_alerta}" title="${c.nombre}: ${c.count} ODTs - ${alertInfo.label}">
                        ${c.count}
                    </span>`;
                } else {
                    const bg = hexToRgba(c.color || '#2196F3', 0.15);
                    html += `<span class="cal-crew-pill" style="background:${bg};color:${c.color || '#2196F3'};" title="${c.nombre}: ${c.count} ODTs">
                        ${c.count}
                    </span>`;
                }
            });

            html += '</div>';
        }

        html += '</div>';
        content.innerHTML = html;
        document.getElementById('calTotal').textContent = totalOdts;
    }

    // ── Render: WEEK ──
    function renderWeek(data, searchTerm) {
        searchTerm = searchTerm || '';
        const content = document.getElementById('calContent');
        const today = formatDate(new Date());

        const start = new Date(data.rango.inicio);
        const weekDays = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(start);
            d.setDate(d.getDate() + i);
            weekDays.push(formatDate(d));
        }

        let totalOdts = 0;
        let html = '<table class="cal-week-table"><thead><tr><th>Cuadrilla</th>';
        weekDays.forEach((d, i) => {
            const isToday = d === today;
            html += `<th style="${isToday ? 'background: var(--accent-primary); color: white;' : ''}">${DAYS_ES[i]}<br><small>${d.split('-')[2]}</small></th>`;
        });
        html += '</tr></thead><tbody>';

        let filteredCrews = data.cuadrillas;
        if (searchTerm) {
            filteredCrews = filteredCrews.filter(c => c.nombre.toLowerCase().includes(searchTerm));
        }

        if (filteredCrews.length === 0) {
            html += `<tr><td colspan="8" style="padding:30px;text-align:center;color:var(--text-muted);">Sin ODTs esta semana</td></tr>`;
        }

        filteredCrews.forEach(crew => {
            html += `<tr><td style="font-weight: 600; text-align: left; white-space: nowrap;">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${crew.color || '#2196F3'};margin-right:6px;"></span>
                ${crew.nombre}
            </td>`;

            weekDays.forEach(d => {
                const count = crew.dias[d] || 0;
                totalOdts += count;
                const bg = count > 0 ? hexToRgba(crew.color || '#2196F3', 0.2) : 'transparent';
                const color = count > 0 ? (crew.color || '#2196F3') : 'var(--text-muted)';
                html += `<td>
                    <span class="cal-week-count" style="background:${bg};color:${color};" onclick="drillDown('${d}')" title="Ver detalle">
                        ${count || '-'}
                    </span>
                </td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        content.innerHTML = html;
        document.getElementById('calTotal').textContent = totalOdts;
    }

    // ── Render: DAY ──
    function renderDay(data, searchTerm) {
        searchTerm = searchTerm || '';
        const content = document.getElementById('calContent');
        const isDueDate = currentMode === 'duedate';

        if (data.cuadrillas.length === 0) {
            const emptyMsg = isDueDate
                ? `No hay ODTs con vencimiento para ${data.nombre_dia} ${data.fecha_formato}`
                : `No hay ODTs asignadas para ${data.nombre_dia} ${data.fecha_formato}`;
            content.innerHTML = `<div style="padding:40px;text-align:center;color:var(--text-muted);">
                <i class="fas fa-calendar-xmark" style="font-size:3em;margin-bottom:15px;display:block;"></i>
                ${emptyMsg}
            </div>`;
            document.getElementById('calTotal').textContent = 0;
            return;
        }

        let html = '';
        let totalFiltered = 0;
        data.cuadrillas.forEach(crew => {
            // Filter ODTs within crew
            let filteredOdts = crew.odts;
            if (searchTerm) {
                filteredOdts = crew.odts.filter(odt => {
                    const haystack = [
                        odt.numero, odt.direccion, crew.nombre,
                        odt.tipo_trabajo, odt.estado, odt.codigo_trabajo
                    ].filter(Boolean).join(' ').toLowerCase();
                    return haystack.includes(searchTerm);
                });
            }
            if (filteredOdts.length === 0) return; // Hide empty crews after filter
            totalFiltered += filteredOdts.length;

            html += `<div class="cal-day-section">`;
            html += `<div class="cal-day-crew-header" style="background:${crew.color || '#2196F3'};">
                <i class="fas fa-users"></i> ${crew.nombre} — ${filteredOdts.length} ODTs
            </div>`;

            filteredOdts.forEach(odt => {
                const urgStyle = odt.urgente ? 'border-left: 3px solid #d32f2f;' : '';
                html += `<div class="cal-day-odt-row" style="${urgStyle}">`;

                // Nº ODT (fixed)
                if (isColVisible('numero')) {
                    html += `<span style="font-weight:700;min-width:90px;color:var(--text-primary);">${odt.numero}</span>`;
                }
                // Dirección (fixed)
                if (isColVisible('direccion')) {
                    html += `<span style="flex:2;color:var(--text-secondary);">${odt.direccion}</span>`;
                }
                // Tipo Trabajo
                if (isColVisible('tipo')) {
                    html += `<span style="min-width:120px;"><i class="fas fa-tools" style="color:var(--text-muted);"></i> ${odt.tipo_trabajo || '-'}</span>`;
                }
                // Estado
                if (isColVisible('estado')) {
                    html += `<span style="min-width:100px;font-size:0.85em;color:var(--text-muted);">${odt.estado || '-'}</span>`;
                }
                // Orden
                if (isColVisible('orden')) {
                    html += `<span style="min-width:30px;text-align:center;font-weight:600;color:var(--text-muted);">#${odt.orden}</span>`;
                }
                // Prioridad
                if (isColVisible('prioridad')) {
                    html += `<span style="min-width:30px;text-align:center;font-weight:600;color:var(--text-muted);">P${odt.prioridad}</span>`;
                }
                // Vencimiento (especialmente útil en duedate mode)
                if (isColVisible('vencimiento')) {
                    if (isDueDate && odt.dias_restantes !== undefined) {
                        const nivel = odt.nivel_alerta || 'normal';
                        const alertInfo = ALERT_COLORS[nivel] || ALERT_COLORS.normal;
                        const diasTxt = odt.dias_restantes < 0
                            ? `${Math.abs(odt.dias_restantes)}d vencida`
                            : odt.dias_restantes === 0 ? 'Hoy' : `${odt.dias_restantes}d`;
                        html += `<span class="cal-alert-pill alert-${nivel}" style="min-width:70px;text-align:center;">${diasTxt}</span>`;
                    } else {
                        html += `<span style="min-width:70px;font-size:0.82em;color:var(--text-muted);">${odt.fecha_vencimiento || '-'}</span>`;
                    }
                }

                // Edit link
                html += `<a href="form.php?id=${odt.id}" class="btn btn-outline" style="min-height:34px;min-width:34px;padding:0;font-size:0.8em;" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>`;
                html += '</div>';
            });

            html += '</div>';
        });

        if (!html) {
            html = `<div style="padding:30px;text-align:center;color:var(--text-muted);"><i class="fas fa-search" style="font-size:2em;margin-bottom:10px;display:block;"></i>Sin resultados para "${searchTerm}"</div>`;
        }
        content.innerHTML = html;
        document.getElementById('calTotal').textContent = searchTerm ? totalFiltered : data.total_odts;
    }

    // ── Drill Down ──
    function drillDown(dateStr) {
        currentDate = new Date(dateStr + 'T12:00:00');
        switchView('day');
    }

    // ── Column Selector ──
    function loadDayColPrefs() {
        try {
            const saved = localStorage.getItem('cal_visible_cols');
            if (saved) return JSON.parse(saved);
        } catch (e) { }
        // Defaults
        const prefs = {};
        DAY_COL_CONFIG.forEach(c => { prefs[c.key] = c.fixed || (c.default !== false); });
        return prefs;
    }

    function saveDayColPrefs() {
        localStorage.setItem('cal_visible_cols', JSON.stringify(dayVisibleCols));
    }

    function isColVisible(key) {
        const cfg = DAY_COL_CONFIG.find(c => c.key === key);
        if (cfg && cfg.fixed) return true;
        return dayVisibleCols[key] !== false;
    }

    function toggleCalColSelector() {
        const dd = document.getElementById('calColSelectorDropdown');
        if (dd.style.display === 'none') {
            buildCalColSelector();
            dd.style.display = 'block';
        } else {
            dd.style.display = 'none';
        }
    }

    function buildCalColSelector() {
        const dd = document.getElementById('calColSelectorDropdown');
        dd.innerHTML = '';
        DAY_COL_CONFIG.forEach(col => {
            const label = document.createElement('label');
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = col.fixed || isColVisible(col.key);
            cb.disabled = col.fixed;
            if (!col.fixed) {
                cb.addEventListener('change', () => {
                    dayVisibleCols[col.key] = cb.checked;
                    saveDayColPrefs();
                    if (currentView === 'day') loadCalendar();
                });
            }
            label.appendChild(cb);
            label.appendChild(document.createTextNode(' ' + col.label + (col.fixed ? ' (fija)' : '')));
            dd.appendChild(label);
        });
    }

    // Close dropdown on outside click
    document.addEventListener('click', e => {
        const wrap = document.getElementById('colSelectorWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('calColSelectorDropdown').style.display = 'none';
        }
    });

    // ── Error Stack Modal ──
    function showErrorStack(err, endpoint) {
        const errorId = err.errorId || `CAL-FE-${Date.now()}`;
        const timestamp = new Date().toISOString();
        const message = err.message || err.toString();

        const stack = {
            errorId: errorId,
            endpoint: endpoint,
            timestamp: timestamp,
            message: message,
            httpCode: err.httpCode || null,
            userAgent: navigator.userAgent
        };

        document.getElementById('errModalTitle').textContent = `Error ${errorId}`;
        document.getElementById('errModalBody').innerHTML = `
            <p><strong>Endpoint:</strong> ${endpoint}</p>
            <p><strong>Mensaje:</strong> ${message}</p>
            <p><strong>Timestamp:</strong> ${timestamp}</p>
        `;
        document.getElementById('errModalStack').textContent = JSON.stringify(stack, null, 2);
        document.getElementById('errorStackModal').style.display = 'flex';
    }

    function closeErrorModal() {
        document.getElementById('errorStackModal').style.display = 'none';
    }

    function copyErrorStack() {
        const text = document.getElementById('errModalStack').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target.closest('button');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            setTimeout(() => btn.innerHTML = orig, 2000);
        });
    }

    // ── Helpers ──
    function hexToRgba(hex, alpha) {
        if (!hex || hex.length < 7) return `rgba(33,150,243,${alpha})`;
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r},${g},${b},${alpha})`;
    }

    // ── Initial Load ──
    loadCalendar();
</script>

<?php require_once '../../includes/footer.php';