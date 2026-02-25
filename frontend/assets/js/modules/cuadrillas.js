async function init_cuadrillas() { await cuad_load(); document.getElementById('cuadSearch')?.addEventListener('input', debounce(cuad_load, 300)); }
async function cuad_load() {
    const s = document.getElementById('cuadSearch')?.value || '';
    try {
        const [listRes, resumenRes] = await Promise.all([api.get(`/api/cuadrillas?search=${encodeURIComponent(s)}`), api.get('/api/cuadrillas/resumen')]);
        const tbody = document.getElementById('cuadTableBody'); if (!tbody) return;
        const resumen = {};
        if (resumenRes.cuadrillas) resumenRes.cuadrillas.forEach(c => { resumen[c.id_cuadrilla] = c; });
        if (!listRes.data?.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin cuadrillas</td></tr>'; return; }
        tbody.innerHTML = listRes.data.map(c => {
            const r = resumen[c.id_cuadrilla] || {};
            return `<tr>
                <td><strong>${c.nombre_cuadrilla}</strong></td>
                <td>${c.tipo_especialidad || '—'}</td>
                <td><span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:${c.color_hex || '#607D8B'}"></span></td>
                <td>${r.odts_hoy || 0}</td><td>${r.odts_manana || 0}</td>
                <td><button class="btn btn-sm" onclick="cuad_edit(${c.id_cuadrilla})">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="cuad_delete(${c.id_cuadrilla})">🗑️</button></td>
            </tr>`;
        }).join('');
    } catch (e) { console.error('[Cuadrillas]', e); }
}
function cuad_showModal(c) {
    document.getElementById('cuadFormId').value = c?.id_cuadrilla || '';
    document.getElementById('cuadFormNombre').value = c?.nombre_cuadrilla || '';
    document.getElementById('cuadFormEspec').value = c?.tipo_especialidad || '';
    document.getElementById('cuadFormColor').value = c?.color_hex || '#607D8B';
    document.getElementById('cuadModalTitle').textContent = c ? 'Editar Cuadrilla' : 'Nueva Cuadrilla';
    document.getElementById('cuadModal').style.display = 'flex';
}
function cuad_closeModal() { document.getElementById('cuadModal').style.display = 'none'; }
async function cuad_edit(id) { const r = await api.get(`/api/cuadrillas/${id}`); if (r.data) cuad_showModal(r.data); }
async function cuad_save(e) {
    e.preventDefault(); const id = document.getElementById('cuadFormId').value;
    const p = { nombre_cuadrilla: document.getElementById('cuadFormNombre').value, tipo_especialidad: document.getElementById('cuadFormEspec').value, color_hex: document.getElementById('cuadFormColor').value };
    if (id) await api.put(`/api/cuadrillas/${id}`, p); else await api.post('/api/cuadrillas', p);
    cuad_closeModal(); cuad_load();
}
async function cuad_delete(id) { if (!confirm('¿Desactivar cuadrilla?')) return; await api.delete(`/api/cuadrillas/${id}`); cuad_load(); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
