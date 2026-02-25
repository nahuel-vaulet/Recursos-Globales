let calYear, calMonth;
async function init_calendario() {
    const now = new Date();
    calYear = now.getFullYear(); calMonth = now.getMonth() + 1;
    await cal_render();
}
function cal_prev() { calMonth--; if (calMonth < 1) { calMonth = 12; calYear--; } cal_render(); }
function cal_next() { calMonth++; if (calMonth > 12) { calMonth = 1; calYear++; } cal_render(); }

async function cal_render() {
    const titleEl = document.getElementById('calTitle');
    const months = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    if (titleEl) titleEl.textContent = `${months[calMonth]} ${calYear}`;

    try {
        const res = await api.get(`/api/calendar/month?anio=${calYear}&mes=${calMonth}`);
        const grid = document.getElementById('calGrid');
        if (!grid) return;

        const daysInMonth = new Date(calYear, calMonth, 0).getDate();
        const firstDay = new Date(calYear, calMonth - 1, 1).getDay(); // 0=Sun

        let html = '<div class="cal-header">Dom</div><div class="cal-header">Lun</div><div class="cal-header">Mar</div><div class="cal-header">Mié</div><div class="cal-header">Jue</div><div class="cal-header">Vie</div><div class="cal-header">Sáb</div>';

        // Empty cells
        for (let i = 0; i < firstDay; i++) html += '<div class="cal-cell empty"></div>';

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${calYear}-${String(calMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayData = res.dias?.[dateStr] || [];
            const total = dayData.reduce((s, c) => s + c.count, 0);

            html += `<div class="cal-cell ${total ? 'has-events' : ''}" onclick="cal_showDay('${dateStr}')">
                <span class="cal-day">${d}</span>
                ${dayData.map(c => `<div class="cal-dot" style="background:${c.color || 'var(--accent-primary)'}" title="${c.cuadrilla}: ${c.count} ODTs"></div>`).join('')}
                ${total ? `<span class="cal-count">${total}</span>` : ''}
            </div>`;
        }

        grid.innerHTML = html;
    } catch (e) { console.error('[Calendar]', e); }
}

async function cal_showDay(fecha) {
    try {
        const res = await api.get(`/api/calendar/day?fecha=${fecha}`);
        const title = document.getElementById('calModalTitle');
        const body = document.getElementById('calModalBody');
        if (title) title.textContent = `ODTs del ${fecha}`;
        if (body) {
            if (!res.cuadrillas?.length) {
                body.innerHTML = '<p class="text-muted">Sin ODTs asignadas</p>';
            } else {
                body.innerHTML = res.cuadrillas.map(c => `
                    <div style="margin-bottom:16px;">
                        <h4 style="color:${c.color_hex || 'inherit'}">${c.nombre_cuadrilla} (${c.odts.length})</h4>
                        <div class="table-responsive"><table class="data-table"><thead><tr><th>ODT</th><th>Dirección</th><th>Estado</th><th>Tipo</th></tr></thead>
                        <tbody>${c.odts.map(o => `<tr><td>${o.nro_odt_assa || o.id_odt}</td><td>${o.direccion || '—'}</td><td><span class="badge">${o.estado_gestion}</span></td><td>${o.tipo_trabajo || '—'}</td></tr>`).join('')}</tbody></table></div>
                    </div>
                `).join('');
            }
        }
        document.getElementById('calModal').style.display = 'flex';
    } catch (e) { console.error('[Calendar]', e); }
}
function cal_closeModal() { document.getElementById('calModal').style.display = 'none'; }
