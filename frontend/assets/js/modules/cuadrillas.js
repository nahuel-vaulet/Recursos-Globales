/**
 * [!] ARCH: Cuadrillas Module — Responsive + Column Selector
 * [✓] AUDIT: Dual render (table + mobile cards), column visibility persisted in localStorage
 */

// ─── Column Visibility State ────────────────────────────
const CUAD_COL_KEY = 'cuad_visible_cols';
const CUAD_DEFAULT_COLS = { especialidad: true, color: true, odtsHoy: true, odtsManana: true };

function cuad_getVisibleCols() {
    try {
        const saved = localStorage.getItem(CUAD_COL_KEY);
        return saved ? JSON.parse(saved) : { ...CUAD_DEFAULT_COLS };
    } catch {
        return { ...CUAD_DEFAULT_COLS };
    }
}

function cuad_saveVisibleCols(cols) {
    localStorage.setItem(CUAD_COL_KEY, JSON.stringify(cols));
}

// ─── Init ───────────────────────────────────────────────
async function init_cuadrillas() {
    cuad_restoreColState();
    await cuad_load();

    const searchEl = document.getElementById('cuadSearch');
    if (searchEl) {
        searchEl.addEventListener('input', cuad_debounce(cuad_load, 300));
    }

    // Close column menu when clicking outside
    document.addEventListener('click', (e) => {
        const selector = document.getElementById('cuadColSelector');
        const menu = document.getElementById('cuadColMenu');
        if (selector && menu && !selector.contains(e.target)) {
            menu.classList.remove('open');
        }
    });
}

// ─── Restore Column Checkbox State ──────────────────────
function cuad_restoreColState() {
    const cols = cuad_getVisibleCols();

    document.querySelectorAll('#cuadColMenu input[data-col]').forEach(cb => {
        const colName = cb.dataset.col;
        cb.checked = cols[colName] !== false;
    });

    cuad_applyColVisibility(cols);
}

// ─── Toggle Column Menu ─────────────────────────────────
window.cuad_toggleColMenu = function () {
    const menu = document.getElementById('cuadColMenu');
    if (menu) menu.classList.toggle('open');
};

// ─── Toggle Individual Column ───────────────────────────
window.cuad_toggleCol = function (checkbox) {
    const cols = cuad_getVisibleCols();
    cols[checkbox.dataset.col] = checkbox.checked;
    cuad_saveVisibleCols(cols);
    cuad_applyColVisibility(cols);
};

// ─── Apply Column Visibility (Table Headers + Cells) ────
function cuad_applyColVisibility(cols) {
    // Toggle <th> headers
    document.querySelectorAll('#cuadTable th[data-col]').forEach(th => {
        th.classList.toggle('col-hidden', cols[th.dataset.col] === false);
    });

    // Toggle <td> cells
    document.querySelectorAll('#cuadTable td[data-col]').forEach(td => {
        td.classList.toggle('col-hidden', cols[td.dataset.col] === false);
    });

    // Toggle mobile card fields
    document.querySelectorAll('.cuad-card-field[data-col]').forEach(field => {
        field.classList.toggle('col-hidden', cols[field.dataset.col] === false);
    });
}

// ─── Load Data ──────────────────────────────────────────
async function cuad_load() {
    const search = document.getElementById('cuadSearch')?.value || '';

    try {
        const [listRes, resumenRes] = await Promise.all([
            api.get(`/api/cuadrillas?search=${encodeURIComponent(search)}`),
            api.get('/api/cuadrillas/resumen'),
        ]);

        const resumen = {};
        if (resumenRes.cuadrillas) {
            resumenRes.cuadrillas.forEach(c => { resumen[c.id_cuadrilla] = c; });
        }

        const data = listRes.data || [];
        const cols = cuad_getVisibleCols();

        cuad_renderTable(data, resumen, cols);
        cuad_renderCards(data, resumen, cols);

    } catch (e) {
        console.error('[Cuadrillas]', e);
        const tbody = document.getElementById('cuadTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Error cargando cuadrillas</td></tr>';
        }
        const mobile = document.getElementById('cuadMobileCards');
        if (mobile) {
            mobile.innerHTML = '<div class="card" style="padding:24px;text-align:center;color:var(--text-muted);">Error cargando cuadrillas</div>';
        }
    }
}

// ─── Render Desktop Table ───────────────────────────────
function cuad_renderTable(data, resumen, cols) {
    const tbody = document.getElementById('cuadTableBody');
    if (!tbody) return;

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin cuadrillas registradas</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(c => {
        const r = resumen[c.id_cuadrilla] || {};
        const colorHex = c.color_hex || '#607D8B';

        return `<tr>
            <td><strong>${esc(c.nombre_cuadrilla)}</strong></td>
            <td data-col="especialidad" class="${cols.especialidad === false ? 'col-hidden' : ''}">${esc(c.tipo_especialidad || '—')}</td>
            <td data-col="color" class="${cols.color === false ? 'col-hidden' : ''}">
                <span style="display:inline-block;width:24px;height:24px;border-radius:6px;background:${colorHex};border:2px solid rgba(255,255,255,0.15);"></span>
            </td>
            <td data-col="odtsHoy" class="${cols.odtsHoy === false ? 'col-hidden' : ''}">
                <span class="odt-badge odt-badge-hoy">${r.odts_hoy || 0}</span>
            </td>
            <td data-col="odtsManana" class="${cols.odtsManana === false ? 'col-hidden' : ''}">
                <span class="odt-badge odt-badge-manana">${r.odts_manana || 0}</span>
            </td>
            <td>
                <button class="btn btn-sm" onclick="cuad_edit(${c.id_cuadrilla})" title="Editar">✏️</button>
                <button class="btn btn-sm btn-danger" onclick="cuad_delete(${c.id_cuadrilla})" title="Eliminar">🗑️</button>
            </td>
        </tr>`;
    }).join('');
}

// ─── Render Mobile Cards ────────────────────────────────
function cuad_renderCards(data, resumen, cols) {
    const container = document.getElementById('cuadMobileCards');
    if (!container) return;

    if (!data.length) {
        container.innerHTML = '<div class="card" style="padding:24px;text-align:center;color:var(--text-muted);">Sin cuadrillas registradas</div>';
        return;
    }

    container.innerHTML = data.map(c => {
        const r = resumen[c.id_cuadrilla] || {};
        const colorHex = c.color_hex || '#607D8B';

        return `<div class="cuad-card">
            <div class="cuad-card-header">
                <div class="cuad-card-name">
                    <span class="cuad-card-color" style="background:${colorHex}"></span>
                    ${esc(c.nombre_cuadrilla)}
                </div>
            </div>
            <div class="cuad-card-body">
                <div class="cuad-card-field" data-col="especialidad" ${cols.especialidad === false ? 'style="display:none"' : ''}>
                    <span class="cuad-card-label">Especialidad</span>
                    <span class="cuad-card-value">${esc(c.tipo_especialidad || '—')}</span>
                </div>
                <div class="cuad-card-field" data-col="color" ${cols.color === false ? 'style="display:none"' : ''}>
                    <span class="cuad-card-label">Color</span>
                    <span class="cuad-card-value">
                        <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:${colorHex};vertical-align:middle;"></span>
                        ${colorHex}
                    </span>
                </div>
                <div class="cuad-card-field" data-col="odtsHoy" ${cols.odtsHoy === false ? 'style="display:none"' : ''}>
                    <span class="cuad-card-label">ODTs Hoy</span>
                    <span class="cuad-card-value"><span class="odt-badge odt-badge-hoy">${r.odts_hoy || 0}</span></span>
                </div>
                <div class="cuad-card-field" data-col="odtsManana" ${cols.odtsManana === false ? 'style="display:none"' : ''}>
                    <span class="cuad-card-label">ODTs Mañana</span>
                    <span class="cuad-card-value"><span class="odt-badge odt-badge-manana">${r.odts_manana || 0}</span></span>
                </div>
            </div>
            <div class="cuad-card-actions">
                <button class="btn btn-ghost" onclick="cuad_edit(${c.id_cuadrilla})">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-danger" onclick="cuad_delete(${c.id_cuadrilla})">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            </div>
        </div>`;
    }).join('');
}

// ─── Modal CRUD ─────────────────────────────────────────
window.cuad_showModal = function (c) {
    document.getElementById('cuadFormId').value = c?.id_cuadrilla || '';
    document.getElementById('cuadFormNombre').value = c?.nombre_cuadrilla || '';
    document.getElementById('cuadFormEspec').value = c?.tipo_especialidad || '';
    document.getElementById('cuadFormColor').value = c?.color_hex || '#607D8B';
    document.getElementById('cuadModalTitle').textContent = c ? 'Editar Cuadrilla' : 'Nueva Cuadrilla';
    document.getElementById('cuadModal').style.display = 'flex';
};

window.cuad_closeModal = function () {
    document.getElementById('cuadModal').style.display = 'none';
};

window.cuad_edit = async function (id) {
    const r = await api.get(`/api/cuadrillas/${id}`);
    if (r.data) cuad_showModal(r.data);
};

window.cuad_save = async function (e) {
    e.preventDefault();
    const id = document.getElementById('cuadFormId').value;
    const payload = {
        nombre_cuadrilla: document.getElementById('cuadFormNombre').value,
        tipo_especialidad: document.getElementById('cuadFormEspec').value,
        color_hex: document.getElementById('cuadFormColor').value,
    };

    if (id) {
        await api.put(`/api/cuadrillas/${id}`, payload);
    } else {
        await api.post('/api/cuadrillas', payload);
    }

    cuad_closeModal();
    cuad_load();
};

window.cuad_delete = async function (id) {
    if (!confirm('¿Desactivar esta cuadrilla?')) return;
    await api.delete(`/api/cuadrillas/${id}`);
    cuad_load();
};

// ─── Utilities ──────────────────────────────────────────
function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function cuad_debounce(fn, ms) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
}
