async function init_vehiculos() { await veh_load(); document.getElementById('vehSearch')?.addEventListener('input', debounce(veh_load, 300)); }
async function veh_load() {
    const s = document.getElementById('vehSearch')?.value || ''; try {
        const r = await api.get(`/api/vehiculos?search=${encodeURIComponent(s)}`); const tb = document.getElementById('vehTableBody'); if (!tb) return; if (!r.data?.length) { tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin vehículos</td></tr>'; return; }
        tb.innerHTML = r.data.map(v => `<tr><td><strong>${v.patente}</strong></td><td>${v.marca || '—'}</td><td>${v.modelo || '—'}</td><td>${v.anio || '—'}</td><td>${v.nombre_cuadrilla || '—'}</td><td>${v.km_actual || 0}</td><td><button class="btn btn-sm" onclick="veh_edit(${v.id_vehiculo})">✏️</button><button class="btn btn-sm btn-danger" onclick="veh_delete(${v.id_vehiculo})">🗑️</button></td></tr>`).join('');
    } catch (e) { console.error('[Vehiculos]', e); }
}
function veh_showModal(v) { document.getElementById('vehFormId').value = v?.id_vehiculo || ''; document.getElementById('vehPatente').value = v?.patente || ''; document.getElementById('vehMarca').value = v?.marca || ''; document.getElementById('vehModelo').value = v?.modelo || ''; document.getElementById('vehAnio').value = v?.anio || ''; document.getElementById('vehKm').value = v?.km_actual || 0; document.getElementById('vehModalTitle').textContent = v ? 'Editar Vehículo' : 'Nuevo Vehículo'; document.getElementById('vehModal').style.display = 'flex'; }
function veh_closeModal() { document.getElementById('vehModal').style.display = 'none'; }
async function veh_edit(id) { const r = await api.get(`/api/vehiculos/${id}`); if (r.data) veh_showModal(r.data); }
async function veh_save(e) { e.preventDefault(); const id = document.getElementById('vehFormId').value; const p = { patente: document.getElementById('vehPatente').value, marca: document.getElementById('vehMarca').value, modelo: document.getElementById('vehModelo').value, anio: document.getElementById('vehAnio').value, km_actual: parseInt(document.getElementById('vehKm').value) }; if (id) await api.put(`/api/vehiculos/${id}`, p); else await api.post('/api/vehiculos', p); veh_closeModal(); veh_load(); }
async function veh_delete(id) { if (!confirm('¿Eliminar vehículo?')) return; await api.delete(`/api/vehiculos/${id}`); veh_load(); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
