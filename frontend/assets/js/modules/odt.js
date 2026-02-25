/**
 * [!] ARCH: ODT Module — Frontend JavaScript
 * [✓] AUDIT: Loads data via api.js, renders table and metrics
 */

const odtModule = {
    data: [],
    metrics: {},
    stateConfig: null,
    selectedIds: new Set(),
    searchTimeout: null,

    // ─── Init ───────────────────────────────────────
    async init() {
        await this.loadStateConfig();
        this.populateStateFilters();
        await this.reload();
    },

    async loadStateConfig() {
        try {
            this.stateConfig = await api.get('/api/odt/states');
        } catch (err) {
            console.warn('[ODT] Could not load state config:', err.code);
            // Fallback
            this.stateConfig = { states: [], colors: {}, transitions: {} };
        }
    },

    populateStateFilters() {
        const select = document.getElementById('odtEstado');
        const bulkSelect = document.getElementById('bulkEstado');
        if (!this.stateConfig?.states) return;

        this.stateConfig.states.forEach(state => {
            select.innerHTML += `<option value="${state}">${state}</option>`;
            bulkSelect.innerHTML += `<option value="${state}">${state}</option>`;
        });
    },

    // ─── Data Loading ───────────────────────────────

    async reload() {
        const params = new URLSearchParams();
        const estado = document.getElementById('odtEstado')?.value;
        const prioridad = document.getElementById('odtPrioridad')?.value;
        const cuadrilla = document.getElementById('odtCuadrilla')?.value;
        const vencimiento = document.getElementById('odtVencimiento')?.value;
        const search = document.getElementById('odtSearch')?.value;

        if (estado) params.set('estado', estado);
        if (prioridad) params.set('prioridad', prioridad);
        if (cuadrilla) params.set('cuadrilla', cuadrilla);
        if (vencimiento) params.set('vencimiento', vencimiento);
        if (search) params.set('search', search);

        try {
            const result = await api.get(`/api/odt?${params.toString()}`);
            this.data = result.data || [];
            this.metrics = result.metrics || {};
            this.renderMetrics();
            this.renderTable();
        } catch (err) {
            showApiError(err);
        }
    },

    debounceSearch() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => this.reload(), 350);
    },

    clearFilters() {
        ['odtEstado', 'odtPrioridad', 'odtCuadrilla', 'odtVencimiento'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const search = document.getElementById('odtSearch');
        if (search) search.value = '';
        document.querySelectorAll('.kpi-mini').forEach(k => k.classList.remove('active'));
        this.reload();
    },

    // ─── Render Metrics ─────────────────────────────

    renderMetrics() {
        const container = document.getElementById('odtMetrics');
        if (!container) return;

        const m = this.metrics;
        const cards = [
            { label: 'Total', value: m.total || 0, color: 'var(--accent-primary)', filter: '' },
            { label: 'Urgentes', value: m.urgentes || 0, color: 'var(--color-danger)', filter: 'urgente' },
            { label: 'Pendientes', value: (m.nuevo || 0) + (m.priorizado || 0) + (m.programado || 0), color: 'var(--color-warning)', filter: 'pendiente' },
            { label: 'En Ejecución', value: m.en_ejecución || 0, color: 'var(--color-success)', filter: 'En ejecución' },
            { label: 'Ejecutadas', value: m.ejecutado || 0, color: 'var(--color-info)', filter: 'Ejecutado' },
            { label: 'Prox. Vencer', value: m.proximas_vencer || 0, color: 'var(--color-warning)', filter: 'proximas' },
        ];

        container.innerHTML = cards.map(c => `
            <div class="kpi-mini" onclick="odtModule.filterByMetric('${c.filter}')">
                <div class="kpi-val" style="color:${c.color};">${c.value}</div>
                <div class="kpi-lbl">${c.label}</div>
            </div>
        `).join('');
    },

    filterByMetric(filter) {
        if (filter === 'urgente') {
            // toggle urgente filter via search param
            const search = document.getElementById('odtSearch');
            search.value = search.value === '!urgente' ? '' : '!urgente';
        } else if (filter === 'pendiente') {
            // No single estado, show combined
        } else if (filter === 'proximas') {
            document.getElementById('odtVencimiento').value = 'proximas';
        } else if (filter) {
            document.getElementById('odtEstado').value = filter;
        } else {
            this.clearFilters();
            return;
        }
        this.reload();
    },

    // ─── Render Table ───────────────────────────────

    renderTable() {
        const tbody = document.getElementById('odtTableBody');
        if (!tbody) return;

        if (this.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:40px; color:var(--text-muted);">
                <i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                No se encontraron ODTs con los filtros aplicados
            </td></tr>`;
            return;
        }

        tbody.innerHTML = this.data.map(odt => {
            const checked = this.selectedIds.has(odt.id_odt) ? 'checked' : '';
            const stateBadge = this.renderStateBadge(odt.estado_gestion);
            const priorBadge = this.renderPriorityBadge(odt.prioridad, odt.urgente_flag);
            const crewBadge = odt.nombre_cuadrilla
                ? `<span class="crew-badge" style="background:${odt.color_hex || '#333'}22; color:${odt.color_hex || 'var(--text-primary)'}; border:1px solid ${odt.color_hex || '#555'}44;">${odt.nombre_cuadrilla}</span>`
                : '<span style="color:var(--text-muted); font-size:0.8em;">Sin asignar</span>';

            const vencClass = this.getVencimientoClass(odt.fecha_vencimiento);

            return `<tr>
                <td><input type="checkbox" ${checked} onchange="odtModule.toggleSelect(${odt.id_odt}, this.checked)"></td>
                <td><strong style="color:var(--accent-primary);">${odt.nro_odt_assa || '—'}</strong></td>
                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${odt.direccion || ''}">${odt.direccion || '—'}</td>
                <td>${odt.tipo_trabajo || '—'}</td>
                <td>${stateBadge}</td>
                <td>${priorBadge}</td>
                <td>${crewBadge}</td>
                <td>${odt.fecha_asignacion || '—'}</td>
                <td class="${vencClass}">${odt.fecha_vencimiento || '—'}</td>
                <td>
                    <button class="action-btn" title="Ver detalle" onclick="odtModule.showDetail(${odt.id_odt})"><i class="fas fa-eye"></i></button>
                    <button class="action-btn" title="Toggle urgente" onclick="odtModule.toggleUrgent(${odt.id_odt})"><i class="fas fa-bolt"></i></button>
                </td>
            </tr>`;
        }).join('');
    },

    renderStateBadge(estado) {
        const colors = this.stateConfig?.colors?.[estado] || { bg: '#333', color: '#fff' };
        return `<span class="status-badge" style="background:${colors.bg}; color:${colors.color};">
            <i class="${colors.icon || 'fas fa-circle'}" style="font-size:0.7em;"></i> ${estado}
        </span>`;
    },

    renderPriorityBadge(prioridad, urgenteFlag) {
        const labels = { 1: '🔴 Urgente', 2: '🟠 Alta', 3: '🔵 Normal', 4: '🟢 Baja', 5: '⚪ Mínima' };
        const colors = { 1: '#d32f2f', 2: '#e65100', 3: '#616161', 4: '#2e7d32', 5: '#9e9e9e' };
        const nivel = urgenteFlag ? 1 : (prioridad || 3);
        return `<span class="priority-badge" style="color:${colors[nivel]};">${labels[nivel] || 'Normal'}</span>`;
    },

    getVencimientoClass(fecha) {
        if (!fecha) return '';
        const dias = Math.round((new Date(fecha) - new Date()) / 86400000);
        if (dias < 0) return 'style="color:var(--color-danger); font-weight:600;"';
        if (dias <= 3) return 'style="color:var(--color-warning); font-weight:600;"';
        return '';
    },

    // ─── Selection / Bulk ───────────────────────────

    toggleSelect(id, checked) {
        if (checked) { this.selectedIds.add(id); } else { this.selectedIds.delete(id); }
        this.updateBulkBar();
    },

    toggleSelectAll(checked) {
        this.data.forEach(odt => {
            if (checked) { this.selectedIds.add(odt.id_odt); } else { this.selectedIds.delete(odt.id_odt); }
        });
        this.renderTable();
        this.updateBulkBar();
    },

    clearSelection() {
        this.selectedIds.clear();
        document.getElementById('odtSelectAll').checked = false;
        this.renderTable();
        this.updateBulkBar();
    },

    updateBulkBar() {
        const bar = document.getElementById('odtBulkBar');
        const count = document.getElementById('odtSelectedCount');
        if (this.selectedIds.size > 0) {
            bar.style.display = 'block';
            count.textContent = `${this.selectedIds.size} seleccionada${this.selectedIds.size > 1 ? 's' : ''}`;
        } else {
            bar.style.display = 'none';
        }
    },

    async applyBulk() {
        const estado = document.getElementById('bulkEstado')?.value;
        if (!estado) {
            showApiError({ code: 'ERR-UI', message: 'Seleccioná un estado para aplicar' });
            return;
        }

        try {
            const result = await api.post('/api/odt/bulk', {
                ids: Array.from(this.selectedIds),
                estado: estado,
            });

            this.clearSelection();
            await this.reload();

            // Show result
            if (result.errores?.length > 0) {
                showApiError({ code: 'BULK-PARTIAL', message: result.message });
            }
        } catch (err) {
            showApiError(err);
        }
    },

    // ─── Detail Modal ───────────────────────────────

    async showDetail(id) {
        const modal = document.getElementById('odtDetailModal');
        const body = document.getElementById('odtDetailBody');
        const title = document.getElementById('odtDetailTitle');

        body.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        modal.style.display = 'flex';

        try {
            const result = await api.get(`/api/odt/${id}`);
            const odt = result.data;
            const hist = result.historial || [];
            const trans = result.transitions || [];

            title.textContent = `ODT #${odt.nro_odt_assa || id}`;

            body.innerHTML = `
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                    <div><strong>Dirección:</strong> ${odt.direccion || '—'}</div>
                    <div><strong>Tipo Trabajo:</strong> ${odt.tipo_trabajo || '—'}</div>
                    <div><strong>Estado:</strong> ${this.renderStateBadge(odt.estado_gestion)}</div>
                    <div><strong>Cuadrilla:</strong> ${odt.nombre_cuadrilla || 'Sin asignar'}</div>
                    <div><strong>Inspector:</strong> ${odt.inspector || '—'}</div>
                    <div><strong>Vencimiento:</strong> ${odt.fecha_vencimiento || '—'}</div>
                </div>

                ${trans.length > 0 ? `
                <div style="margin-bottom:20px;">
                    <strong>Transiciones posibles:</strong>
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                        ${trans.map(t => `<button class="btn btn-ghost" style="padding:6px 12px; font-size:0.8em;" 
                            onclick="odtModule.changeStatus(${id}, '${t}')">${t}</button>`).join('')}
                    </div>
                </div>` : ''}

                <div style="border-top:1px solid rgba(255,255,255,0.06); padding-top:16px;">
                    <strong>Historial</strong>
                    ${hist.length === 0 ? '<p style="color:var(--text-muted); margin-top:8px;">Sin historial</p>' :
                    `<div style="margin-top:10px; max-height:250px; overflow-y:auto;">
                        ${hist.map(h => `
                            <div style="padding:8px 12px; border-left:3px solid var(--accent-primary); margin-bottom:8px; background:rgba(255,255,255,0.02); border-radius:0 8px 8px 0;">
                                <div style="font-size:0.75em; color:var(--text-muted);">${h.created_at} — ${h.usuario_nombre || 'Sistema'}</div>
                                <div style="font-size:0.85em;">${h.estado_anterior || '—'} → <strong>${h.estado_nuevo}</strong></div>
                                ${h.observacion ? `<div style="font-size:0.8em; color:var(--text-muted); margin-top:2px;">${h.observacion}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>`}
                </div>
            `;
        } catch (err) {
            body.innerHTML = `<p style="color:var(--color-danger);">[${err.code}] ${err.message}</p>`;
        }
    },

    // ─── Actions ────────────────────────────────────

    async changeStatus(id, nuevoEstado) {
        try {
            await api.post(`/api/odt/${id}/status`, { estado: nuevoEstado });
            document.getElementById('odtDetailModal').style.display = 'none';
            await this.reload();
        } catch (err) {
            showApiError(err);
        }
    },

    async toggleUrgent(id) {
        try {
            await api.post(`/api/odt/${id}/urgent`);
            await this.reload();
        } catch (err) {
            showApiError(err);
        }
    },

    exportCSV() {
        if (this.data.length === 0) return;

        const headers = ['Nro ODT', 'Dirección', 'Tipo Trabajo', 'Estado', 'Prioridad', 'Cuadrilla', 'Fecha Asig.', 'Vencimiento'];
        const rows = this.data.map(o => [
            o.nro_odt_assa || '', o.direccion || '', o.tipo_trabajo || '',
            o.estado_gestion || '', o.prioridad || '', o.nombre_cuadrilla || '',
            o.fecha_asignacion || '', o.fecha_vencimiento || ''
        ]);

        const csv = [headers, ...rows].map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `odts_${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
    }
};

// ─── Module init (called by SPA router) ──────────
function init_odt() {
    odtModule.init();
}
