async function init_personal() {
    await personal_load();
    document.getElementById('personalSearch')?.addEventListener('input', debounce(personal_load, 300));
}

async function personal_load() {
    const search = document.getElementById('personalSearch')?.value || '';
    let url = `/api/personal?search=${encodeURIComponent(search)}`;

    try {
        const r = await api.get(url);
        const tb = document.getElementById('personalTableBody');
        if (!tb) return;

        if (!r.data?.length) {
            tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron legajos</td></tr>';
            return;
        }

        tb.innerHTML = r.data.map(p => `
            <tr>
                <td><strong>${p.nombre}</strong></td>
                <td>${p.dni}</td>
                <td>${p.legajo || '—'}</td>
                <td>${p.puesto || '—'}</td>
                <td>${p.fecha_ingreso || '—'}</td>
                <td>
                    <button class="btn btn-sm" onclick="personal_edit(${p.id})">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="personal_delete(${p.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('[Personal]', e);
    }
}

function personal_showModal(p) {
    document.getElementById('personalFormId').value = p?.id || '';
    document.getElementById('personalNombre').value = p?.nombre || '';
    document.getElementById('personalDni').value = p?.dni || '';
    document.getElementById('personalLegajo').value = p?.legajo || '';
    document.getElementById('personalPuesto').value = p?.puesto || '';
    document.getElementById('personalFechaIngreso').value = p?.fecha_ingreso || '';

    document.getElementById('personalModalTitle').textContent = p ? 'Editar Legajo' : 'Nuevo Legajo';
    document.getElementById('personalModal').style.display = 'flex';
}

function personal_closeModal() {
    document.getElementById('personalModal').style.display = 'none';
}

async function personal_edit(id) {
    const r = await api.get(`/api/personal/${id}`);
    if (r.data) personal_showModal(r.data);
}

async function personal_save(e) {
    e.preventDefault();
    const id = document.getElementById('personalFormId').value;
    const p = {
        nombre: document.getElementById('personalNombre').value,
        dni: document.getElementById('personalDni').value,
        legajo: document.getElementById('personalLegajo').value,
        puesto: document.getElementById('personalPuesto').value,
        fecha_ingreso: document.getElementById('personalFechaIngreso').value || null
    };

    if (id) await api.put(`/api/personal/${id}`, p);
    else await api.post('/api/personal', p);

    personal_closeModal();
    personal_load();
}

async function personal_delete(id) {
    if (!confirm('¿Eliminar legajo?')) return;
    await api.delete(`/api/personal/${id}`);
    personal_load();
}

// Utility debounce
function debounce(fn, ms) {
    let t;
    return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), ms);
    };
}
