/**
 * [!] ARCH: Stock / Materiales Module
 */
async function init_stock() {
    await stock_load();
    document.getElementById('stockSearch')?.addEventListener('input', debounce(stock_load, 300));
    document.getElementById('stockFilterAlerta')?.addEventListener('change', stock_load);
}

async function stock_load() {
    const search = document.getElementById('stockSearch')?.value || '';
    const alerta = document.getElementById('stockFilterAlerta')?.value || '';
    let url = `/api/materiales?search=${encodeURIComponent(search)}`;
    if (alerta) url += `&alerta=1`;

    try {
        const res = await api.get(url);
        const tbody = document.getElementById('stockTableBody');
        if (!tbody) return;

        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin materiales</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(m => {
            const low = m.stock_actual <= m.stock_minimo;
            return `<tr>
                <td>${m.codigo || '—'}</td>
                <td><strong>${m.nombre}</strong></td>
                <td>${m.unidad_medida}</td>
                <td class="${low ? 'text-danger' : ''}">${m.stock_actual}</td>
                <td>${m.stock_minimo}</td>
                <td>${low ? '<span class="badge badge-danger">⚠️ Bajo</span>' : '<span class="badge badge-success">OK</span>'}</td>
                <td>
                    <button class="btn btn-sm" onclick="stock_edit(${m.id})">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="stock_delete(${m.id})">🗑️</button>
                </td>
            </tr>`;
        }).join('');
    } catch (err) { console.error('[Stock]', err); }
}

function stock_showModal(mat) {
    document.getElementById('stockFormId').value = mat?.id || '';
    document.getElementById('stockFormNombre').value = mat?.nombre || '';
    document.getElementById('stockFormCodigo').value = mat?.codigo || '';
    document.getElementById('stockFormUnidad').value = mat?.unidad_medida || 'UND';
    document.getElementById('stockFormCategoria').value = mat?.categoria || '';
    document.getElementById('stockFormStockActual').value = mat?.stock_actual || 0;
    document.getElementById('stockFormStockMinimo').value = mat?.stock_minimo || 0;
    document.getElementById('stockModalTitle').textContent = mat ? 'Editar Material' : 'Nuevo Material';
    document.getElementById('stockModal').style.display = 'flex';
}

function stock_closeModal() { document.getElementById('stockModal').style.display = 'none'; }

async function stock_edit(id) {
    const res = await api.get(`/api/materiales/${id}`);
    if (res.data) stock_showModal(res.data);
}

async function stock_save(e) {
    e.preventDefault();
    const id = document.getElementById('stockFormId').value;
    const payload = {
        nombre: document.getElementById('stockFormNombre').value,
        codigo: document.getElementById('stockFormCodigo').value,
        unidad_medida: document.getElementById('stockFormUnidad').value,
        categoria: document.getElementById('stockFormCategoria').value,
        stock_actual: parseFloat(document.getElementById('stockFormStockActual').value),
        stock_minimo: parseFloat(document.getElementById('stockFormStockMinimo').value),
    };
    if (id) { await api.put(`/api/materiales/${id}`, payload); }
    else { await api.post('/api/materiales', payload); }
    stock_closeModal(); stock_load();
}

async function stock_delete(id) {
    if (!confirm('¿Eliminar este material?')) return;
    await api.delete(`/api/materiales/${id}`);
    stock_load();
}

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
