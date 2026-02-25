async function init_compras() {
    await compras_load();
}

async function compras_load() {
    try {
        const r = await api.get('/api/compras');
        const tb = document.getElementById('comprasTableBody');
        if (!tb) return;

        if (!r.data?.length) {
            tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay órdenes registradas</td></tr>';
            return;
        }

        tb.innerHTML = r.data.map(c => `
            <tr>
                <td>${new Date(c.fecha).toLocaleDateString()}</td>
                <td><strong>${c.proveedor_nombre || 'Desconocido'}</strong></td>
                <td>$${parseFloat(c.monto_total).toLocaleString()}</td>
                <td><span class="badge badge-info">${c.estado}</span></td>
                <td><button class="btn btn-sm" onclick="alert('Ver detalle en desarrollo...')">👁️</button></td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('[Compras]', e);
    }
}

async function compras_showModal() {
    const p = await api.get('/api/proveedores');
    const sel = document.getElementById('comprasProveedor');
    if (sel && p.data) {
        sel.innerHTML = p.data.map(x => `<option value="${x.id}">${x.nombre}</option>`).join('');
    }
    document.getElementById('comprasModal').style.display = 'flex';
}

function compras_closeModal() {
    document.getElementById('comprasModal').style.display = 'none';
}

async function compras_save(e) {
    e.preventDefault();
    const p = {
        id_proveedor: document.getElementById('comprasProveedor').value,
        monto_total: document.getElementById('comprasMonto').value,
        observaciones: document.getElementById('comprasObs').value
    };

    await api.post('/api/compras', p);
    compras_closeModal();
    compras_load();
}
