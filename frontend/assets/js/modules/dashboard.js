/**
 * [!] ARCH: Dashboard Module
 * [✓] AUDIT: Loads KPIs, alerts, recent movements from API
 */

async function init_dashboard() {
    try {
        const [statsRes, alertsRes, recentRes] = await Promise.all([
            api.get('/api/dashboard/stats'),
            api.get('/api/dashboard/alerts'),
            api.get('/api/dashboard/recent?limit=5'),
        ]);

        // Render KPIs
        if (statsRes.data) {
            const d = statsRes.data;
            setText('kpi-materiales', d.total_materiales);
            setText('kpi-alertas', d.alertas_count);
            setText('kpi-movimientos', d.movimientos_hoy);
            setText('kpi-cuadrillas', d.cuadrillas_activas);
            setText('kpi-consumo', d.consumo_mensual.toLocaleString());

            const varEl = document.getElementById('kpi-variacion');
            if (varEl && d.variacion_mensual !== 0) {
                const sign = d.variacion_mensual > 0 ? '↑' : '↓';
                varEl.textContent = `${sign} ${Math.abs(d.variacion_mensual)}% vs mes anterior`;
                varEl.className = `kpi-change ${d.variacion_mensual > 0 ? 'up' : 'down'}`;
            }
        }

        // Render Alerts
        const alertsEl = document.getElementById('dashboardAlerts');
        if (alertsEl && alertsRes.data) {
            if (alertsRes.data.length === 0) {
                alertsEl.innerHTML = '<p class="text-muted" style="padding:16px;">✓ Sin alertas de stock</p>';
            } else {
                alertsEl.innerHTML = alertsRes.data.map(a => `
                    <div class="alert-row">
                        <div>
                            <strong>${a.nombre}</strong>
                            <span class="badge badge-sm">${a.codigo || ''}</span>
                        </div>
                        <div class="alert-stock">
                            <span class="text-danger">${a.stock_actual}</span> / ${a.stock_minimo} ${a.unidad_medida}
                        </div>
                    </div>
                `).join('');
            }
        }

        // Render Recent
        const recentEl = document.getElementById('dashboardRecent');
        if (recentEl && recentRes.data) {
            if (recentRes.data.length === 0) {
                recentEl.innerHTML = '<p class="text-muted" style="padding:16px;">Sin movimientos recientes</p>';
            } else {
                recentEl.innerHTML = recentRes.data.map(m => `
                    <div class="alert-row">
                        <div>
                            <span class="badge ${m.tipo === 'entrada' ? 'badge-success' : 'badge-warning'}">${m.tipo === 'entrada' ? '📥' : '📤'} ${m.tipo}</span>
                            <strong>${m.material_nombre}</strong>
                        </div>
                        <div>
                            <span>${m.cantidad} ${m.unidad_medida}</span>
                            ${m.nombre_cuadrilla ? `<span class="text-muted">→ ${m.nombre_cuadrilla}</span>` : ''}
                        </div>
                    </div>
                `).join('');
            }
        }

    } catch (err) {
        console.error('[Dashboard]', err);
    }
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}
