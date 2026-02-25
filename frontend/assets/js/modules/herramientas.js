async function init_herramientas() { await tool_load(); document.getElementById('toolSearch')?.addEventListener('input', debounce(tool_load, 300)); }
async function tool_load() {
    const s = document.getElementById('toolSearch')?.value || ''; try {
        const r = await api.get(`/api/herramientas?search=${encodeURIComponent(s)}`); const tb = document.getElementById('toolTableBody'); if (!tb) return; if (!r.data?.length) { tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin herramientas</td></tr>'; return; }
        tb.innerHTML = r.data.map(h => `<tr><td>${h.codigo || '—'}</td><td><strong>${h.nombre}</strong></td><td>${h.marca || '—'}</td><td>${h.nombre_cuadrilla || '—'}</td><td>${h.proveedor || '—'}</td><td><button class="btn btn-sm" onclick="tool_edit(${h.id_herramienta})">✏️</button><button class="btn btn-sm btn-danger" onclick="tool_delete(${h.id_herramienta})">🗑️</button></td></tr>`).join('');
    } catch (e) { console.error('[Herramientas]', e); }
}
function tool_showModal(h) { document.getElementById('toolFormId').value = h?.id_herramienta || ''; document.getElementById('toolNombre').value = h?.nombre || ''; document.getElementById('toolCodigo').value = h?.codigo || ''; document.getElementById('toolMarca').value = h?.marca || ''; document.getElementById('toolProveedor').value = h?.proveedor || ''; document.getElementById('toolModalTitle').textContent = h ? 'Editar' : 'Nueva Herramienta'; document.getElementById('toolModal').style.display = 'flex'; }
function tool_closeModal() { document.getElementById('toolModal').style.display = 'none'; }
async function tool_edit(id) { const r = await api.get(`/api/herramientas/${id}`); if (r.data) tool_showModal(r.data); }
async function tool_save(e) { e.preventDefault(); const id = document.getElementById('toolFormId').value; const p = { nombre: document.getElementById('toolNombre').value, codigo: document.getElementById('toolCodigo').value, marca: document.getElementById('toolMarca').value, proveedor: document.getElementById('toolProveedor').value }; if (id) await api.put(`/api/herramientas/${id}`, p); else await api.post('/api/herramientas', p); tool_closeModal(); tool_load(); }
async function tool_delete(id) { if (!confirm('¿Eliminar?')) return; await api.delete(`/api/herramientas/${id}`); tool_load(); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
