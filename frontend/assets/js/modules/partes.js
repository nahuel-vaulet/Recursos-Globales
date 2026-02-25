async function init_partes() {
    // Load cuadrillas for filter
    const cRes = await api.get('/api/cuadrillas/activas');
    if (cRes.data) {
        const opts = cRes.data.map(c => `<option value="${c.id_cuadrilla}">${c.nombre_cuadrilla}</option>`).join('');
        const f = document.getElementById('parteCuadrilla'); if (f) f.innerHTML = '<option value="">Todas</option>' + opts;
        const f2 = document.getElementById('parteFormCuadrilla'); if (f2) f2.innerHTML = opts;
    }
    document.getElementById('parteFormFecha').value = new Date().toISOString().split('T')[0];
    await parte_load();
    document.getElementById('parteFecha')?.addEventListener('change', parte_load);
    document.getElementById('parteCuadrilla')?.addEventListener('change', parte_load);
}
async function parte_load() {
    const fecha = document.getElementById('parteFecha')?.value || ''; const crew = document.getElementById('parteCuadrilla')?.value || '';
    let url = '/api/partes?limit=50'; if (fecha) url += `&fecha=${fecha}`; if (crew) url += `&cuadrilla_id=${crew}`;
    try {
        const r = await api.get(url); const tb = document.getElementById('parteTableBody'); if (!tb) return;
        if (!r.data?.length) { tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin partes</td></tr>'; return; }
        tb.innerHTML = r.data.map(p => `<tr><td>${p.fecha}</td><td>${p.nombre_cuadrilla || '—'}</td><td>${p.usuario_nombre || '—'}</td><td>${(p.observaciones || '—').substring(0, 60)}</td><td><button class="btn btn-sm" onclick="parte_view(${p.id_parte})">👁️</button></td></tr>`).join('');
    } catch (e) { console.error('[Partes]', e); }
}
function parte_showModal() { document.getElementById('parteModal').style.display = 'flex'; }
function parte_closeModal() { document.getElementById('parteModal').style.display = 'none'; }
async function parte_save(e) { e.preventDefault(); await api.post('/api/partes', { id_cuadrilla: parseInt(document.getElementById('parteFormCuadrilla').value), fecha: document.getElementById('parteFormFecha').value, observaciones: document.getElementById('parteFormObs').value }); parte_closeModal(); parte_load(); }
async function parte_view(id) { const r = await api.get(`/api/partes/${id}`); if (r.data) alert(JSON.stringify(r.data, null, 2)); }
