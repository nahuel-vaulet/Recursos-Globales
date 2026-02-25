async function init_spot() {
    await spot_load();
}

async function spot_load() {
    try {
        const r = await api.get('/api/spot');
        const tb = document.getElementById('spotTableBody');
        if (!tb) return;

        if (!r.data?.length) {
            tb.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay puntos registrados</td></tr>';
            return;
        }

        tb.innerHTML = r.data.map(p => `
            <tr>
                <td><strong>${p.nombre}</strong></td>
                <td>${p.coordenadas || p.direccion || '—'}</td>
                <td><span class="badge badge-warning">${p.tipo || 'General'}</span></td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('[Spot]', e);
    }
}
