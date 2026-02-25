async function init_proveedores() { await prov_load(); document.getElementById('provSearch')?.addEventListener('input', debounce(prov_load, 300)); }
async function prov_load() {
    const s = document.getElementById('provSearch')?.value || ''; try {
        const r = await api.get(`/api/proveedores?search=${encodeURIComponent(s)}`); const tb = document.getElementById('provTableBody'); if (!tb) return; if (!r.data?.length) { tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin proveedores</td></tr>'; return; }
        tb.innerHTML = r.data.map(p => `<tr><td><strong>${p.nombre}</strong></td><td>${p.cuit || '—'}</td><td>${p.telefono || '—'}</td><td>${p.email || '—'}</td><td>${p.direccion || '—'}</td><td><button class="btn btn-sm" onclick="prov_edit(${p.id_proveedor})">✏️</button><button class="btn btn-sm btn-danger" onclick="prov_delete(${p.id_proveedor})">🗑️</button></td></tr>`).join('');
    } catch (e) { console.error('[Proveedores]', e); }
}
function prov_showModal(p) { document.getElementById('provFormId').value = p?.id_proveedor || ''; document.getElementById('provNombre').value = p?.nombre || ''; document.getElementById('provCuit').value = p?.cuit || ''; document.getElementById('provTel').value = p?.telefono || ''; document.getElementById('provEmail').value = p?.email || ''; document.getElementById('provDir').value = p?.direccion || ''; document.getElementById('provModalTitle').textContent = p ? 'Editar' : 'Nuevo Proveedor'; document.getElementById('provModal').style.display = 'flex'; }
function prov_closeModal() { document.getElementById('provModal').style.display = 'none'; }
async function prov_edit(id) { const r = await api.get(`/api/proveedores/${id}`); if (r.data) prov_showModal(r.data); }
async function prov_save(e) { e.preventDefault(); const id = document.getElementById('provFormId').value; const p = { nombre: document.getElementById('provNombre').value, cuit: document.getElementById('provCuit').value, telefono: document.getElementById('provTel').value, email: document.getElementById('provEmail').value, direccion: document.getElementById('provDir').value }; if (id) await api.put(`/api/proveedores/${id}`, p); else await api.post('/api/proveedores', p); prov_closeModal(); prov_load(); }
async function prov_delete(id) { if (!confirm('¿Eliminar?')) return; await api.delete(`/api/proveedores/${id}`); prov_load(); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
