async function init_combustibles() {
    const [stockRes, histRes, vehRes] = await Promise.all([api.get('/api/combustibles/stock'), api.get('/api/combustibles/historial?limit=20'), api.get('/api/vehiculos')]);
    // Tanks
    const tanksEl = document.getElementById('fuelTanks');
    if (tanksEl && stockRes.data) {
        tanksEl.innerHTML = stockRes.data.map(t => `<div class="kpi-card"><div class="kpi-icon">⛽</div><div class="kpi-value">${t.stock_actual}L</div><div class="kpi-label">${t.nombre}</div><div class="kpi-change">${t.porcentaje_lleno || 0}% lleno</div></div>`).join('');
        // Populate selects
        const opts = stockRes.data.map(t => `<option value="${t.id_tanque}">${t.nombre} (${t.stock_actual}L)</option>`).join('');
        const el1 = document.getElementById('fuelCargaTanque'); if (el1) el1.innerHTML = opts;
        const el2 = document.getElementById('fuelDespachoTanque'); if (el2) el2.innerHTML = '<option value="">Compra directa</option>' + opts;
    }
    // Vehicles for despacho
    if (vehRes.data) { const sel = document.getElementById('fuelDespachoVeh'); if (sel) sel.innerHTML = vehRes.data.map(v => `<option value="${v.id_vehiculo}">${v.patente} - ${v.marca || ''} ${v.modelo || ''}</option>`).join(''); }
    // History
    const tb = document.getElementById('fuelTableBody');
    if (tb && histRes.data) { tb.innerHTML = histRes.data.length ? histRes.data.map(h => `<tr><td>${h.fecha}</td><td><span class="badge ${h.tipo_movimiento === 'carga' ? 'badge-success' : 'badge-warning'}">${h.tipo_movimiento}</span></td><td>${h.litros}L</td><td>${h.patente || '—'}</td><td>${h.tanque_nombre || '—'}</td><td>${h.usuario_nombre || '—'}</td></tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted">Sin registros</td></tr>'; }
}
function fuel_showCarga() { document.getElementById('fuelModalCarga').style.display = 'flex'; }
function fuel_closeCarga() { document.getElementById('fuelModalCarga').style.display = 'none'; }
function fuel_showDespacho() { document.getElementById('fuelModalDespacho').style.display = 'flex'; }
function fuel_closeDespacho() { document.getElementById('fuelModalDespacho').style.display = 'none'; }
async function fuel_saveCarga(e) { e.preventDefault(); await api.post('/api/combustibles/carga', { id_tanque: parseInt(document.getElementById('fuelCargaTanque').value), litros: parseFloat(document.getElementById('fuelCargaLitros').value), observaciones: document.getElementById('fuelCargaObs').value }); fuel_closeCarga(); init_combustibles(); }
async function fuel_saveDespacho(e) { e.preventDefault(); const t = document.getElementById('fuelDespachoTanque').value; await api.post('/api/combustibles/despacho', { id_vehiculo: parseInt(document.getElementById('fuelDespachoVeh').value), id_tanque: t ? parseInt(t) : null, litros: parseFloat(document.getElementById('fuelDespachoLitros').value), km_odometro: document.getElementById('fuelDespachoKm').value || null }); fuel_closeDespacho(); init_combustibles(); }
