async function init_reportes() {
    await Promise.all([reportes_loadOdt(), reportes_loadConsum()]);
}

async function reportes_loadOdt() {
    try {
        const r = await api.get('/api/reportes/odt-efficiency');
        const el = document.getElementById('odtStatsList');
        if (!el) return;

        el.innerHTML = r.data.map(i => `
            <li style="margin-bottom:10px; display:flex; justify-content:space-between;">
                <span>${i.estado}</span>
                <strong>${i.cantidad}</strong>
            </li>
        `).join('');
    } catch (e) {
        console.error('[Rep-ODT]', e);
    }
}

async function reportes_loadConsum() {
    try {
        const r = await api.get('/api/reportes/consumption');
        const el = document.getElementById('consumptionList');
        if (!el) return;

        el.innerHTML = r.data.map(i => `
            <li style="margin-bottom:10px; display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:5px;">
                <span>${i.nombre}</span>
                <strong>${parseFloat(i.total_consumido).toLocaleString()}</strong>
            </li>
        `).join('');
    } catch (e) {
        console.error('[Rep-Consum]', e);
    }
}
