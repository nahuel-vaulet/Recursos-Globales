async function init_usuarios() { await usr_load(); document.getElementById('usrSearch')?.addEventListener('input', debounce(usr_load, 300)); document.getElementById('usrTipo')?.addEventListener('change', usr_load); }
async function usr_load() {
    const s = document.getElementById('usrSearch')?.value || ''; const t = document.getElementById('usrTipo')?.value || '';
    let url = `/api/usuarios?search=${encodeURIComponent(s)}`; if (t) url += `&tipo_usuario=${t}`;
    try {
        const r = await api.get(url); const tb = document.getElementById('usrTableBody'); if (!tb) return;
        if (!r.data?.length) { tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin usuarios</td></tr>'; return; }
        tb.innerHTML = r.data.map(u => `<tr><td><strong>${u.nombre}</strong></td><td>${u.email}</td><td><span class="badge">${u.tipo_usuario}</span></td><td>${u.created_at || '—'}</td><td><button class="btn btn-sm" onclick="usr_edit(${u.id_usuario})">✏️</button><button class="btn btn-sm btn-danger" onclick="usr_delete(${u.id_usuario})">🗑️</button></td></tr>`).join('');
    } catch (e) { console.error('[Usuarios]', e); }
}
function usr_showModal(u) { document.getElementById('usrFormId').value = u?.id_usuario || ''; document.getElementById('usrNombre').value = u?.nombre || ''; document.getElementById('usrEmail').value = u?.email || ''; document.getElementById('usrPass').value = ''; document.getElementById('usrRol').value = u?.tipo_usuario || 'JefeCuadrilla'; document.getElementById('usrModalTitle').textContent = u ? 'Editar Usuario' : 'Nuevo Usuario'; document.getElementById('usrModal').style.display = 'flex'; }
function usr_closeModal() { document.getElementById('usrModal').style.display = 'none'; }
async function usr_edit(id) { const r = await api.get(`/api/usuarios/${id}`); if (r.data) usr_showModal(r.data); }
async function usr_save(e) {
    e.preventDefault(); const id = document.getElementById('usrFormId').value; const p = { nombre: document.getElementById('usrNombre').value, email: document.getElementById('usrEmail').value, tipo_usuario: document.getElementById('usrRol').value }; const pw = document.getElementById('usrPass').value; if (pw) p.password = pw; if (!id && !pw) { alert('Contraseña requerida para nuevo usuario'); return; }
    if (id) await api.put(`/api/usuarios/${id}`, p); else await api.post('/api/usuarios', p); usr_closeModal(); usr_load();
}
async function usr_delete(id) { if (!confirm('¿Eliminar usuario?')) return; await api.delete(`/api/usuarios/${id}`); usr_load(); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
