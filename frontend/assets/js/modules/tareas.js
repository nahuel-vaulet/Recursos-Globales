async function init_tareas() { await task_load(); document.getElementById('taskSearch')?.addEventListener('input', debounce(task_load, 300)); document.getElementById('taskEstado')?.addEventListener('change', task_load); }
async function task_load() {
    const s = document.getElementById('taskSearch')?.value || ''; const e = document.getElementById('taskEstado')?.value || '';
    let url = `/api/tareas?search=${encodeURIComponent(s)}`; if (e) url += `&estado=${e}`;
    try {
        const r = await api.get(url); const tb = document.getElementById('taskTableBody'); if (!tb) return;
        if (!r.data?.length) { tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin tareas</td></tr>'; return; }
        const prioLabels = { 1: '🔴 Alta', 2: '🟠 Media-Alta', 3: '🟡 Normal', 4: '🟢 Baja' };
        const estadoBadge = { pendiente: 'badge-warning', en_progreso: 'badge-info', completada: 'badge-success' };
        tb.innerHTML = r.data.map(t => `<tr><td><strong>${t.titulo}</strong></td><td>${t.responsable_nombre || '—'}</td><td>${prioLabels[t.prioridad] || t.prioridad}</td><td>${t.fecha_vencimiento || '—'}</td><td><span class="badge ${estadoBadge[t.estado] || ''}">${t.estado}</span></td><td><button class="btn btn-sm" onclick="task_edit(${t.id_tarea})">✏️</button><button class="btn btn-sm btn-danger" onclick="task_delete(${t.id_tarea})">🗑️</button></td></tr>`).join('');
    } catch (e) { console.error('[Tareas]', e); }
}
function task_showModal(t) { document.getElementById('taskFormId').value = t?.id_tarea || ''; document.getElementById('taskTitulo').value = t?.titulo || ''; document.getElementById('taskDesc').value = t?.descripcion || ''; document.getElementById('taskPrioridad').value = t?.prioridad || 3; document.getElementById('taskVenc').value = t?.fecha_vencimiento || ''; document.getElementById('taskModalTitle').textContent = t ? 'Editar Tarea' : 'Nueva Tarea'; document.getElementById('taskModal').style.display = 'flex'; }
function task_closeModal() { document.getElementById('taskModal').style.display = 'none'; }
async function task_edit(id) { const r = await api.get(`/api/tareas/${id}`); if (r.data) task_showModal(r.data); }
async function task_save(e) { e.preventDefault(); const id = document.getElementById('taskFormId').value; const p = { titulo: document.getElementById('taskTitulo').value, descripcion: document.getElementById('taskDesc').value, prioridad: parseInt(document.getElementById('taskPrioridad').value), fecha_vencimiento: document.getElementById('taskVenc').value || null }; if (id) await api.put(`/api/tareas/${id}`, p); else await api.post('/api/tareas', p); task_closeModal(); task_load(); }
async function task_delete(id) { if (!confirm('¿Eliminar tarea?')) return; await api.delete(`/api/tareas/${id}`); task_load(); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
