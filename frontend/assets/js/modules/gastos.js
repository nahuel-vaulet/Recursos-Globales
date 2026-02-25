async function init_gastos() {
    await gastos_load();
}

async function gastos_load() {
    try {
        const r = await api.get('/api/gastos');
        const tb = document.getElementById('gastosTableBody');
        if (!tb) return;

        if (!r.data?.length) {
            tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay gastos registrados</td></tr>';
            return;
        }

        tb.innerHTML = r.data.map(g => `
            <tr>
                <td>${new Date(g.fecha).toLocaleDateString()}</td>
                <td><span class="badge">${g.categoria}</span></td>
                <td>${g.descripcion || '—'}</td>
                <td>$${parseFloat(g.monto).toLocaleString()}</td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('[Gastos]', e);
    }
}

function gastos_showModal() {
    document.getElementById('gastosModal').style.display = 'flex';
}

function gastos_closeModal() {
    document.getElementById('gastosModal').style.display = 'none';
}

async function gastos_save(e) {
    e.preventDefault();
    const p = {
        categoria: document.getElementById('gastosCategoria').value,
        monto: document.getElementById('gastosMonto').value,
        descripcion: document.getElementById('gastosDesc').value
    };

    await api.post('/api/gastos', p);
    gastos_closeModal();
    gastos_load();
}
