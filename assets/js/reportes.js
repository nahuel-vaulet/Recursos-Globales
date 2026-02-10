/**
 * Reportes (Reports) JavaScript
 * Handles report generation and export functionality
 */

let currentReportType = null;
let reportData = [];

const reportTypes = {
    stock: {
        title: 'Reporte de Stock Actual',
        filters: ['categoria'],
        columns: ['Código', 'Material', 'Categoría', 'Unidad', 'Stock Actual', 'Stock Mínimo', 'Estado']
    },
    movimientos: {
        title: 'Historial de Movimientos',
        filters: ['tipo', 'material', 'cuadrilla', 'fechas'],
        columns: ['Fecha', 'Tipo', 'Material', 'Cantidad', 'Cuadrilla', 'Usuario', 'Observaciones']
    },
    cuadrillas: {
        title: 'Consumo por Cuadrilla',
        filters: ['fechas'],
        columns: ['Cuadrilla', 'Zona de Trabajo', 'Total Consumo', 'Movimientos', '% del Total']
    },
    alertas: {
        title: 'Alertas de Stock Bajo',
        filters: [],
        columns: ['Código', 'Material', 'Stock Actual', 'Stock Mínimo', 'Déficit', 'Urgencia']
    }
};

// Options for filters
let materialesOptions = [];
let cuadrillasOptions = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadFilterOptions();
});

async function loadFilterOptions() {
    try {
        const [materialesRes, cuadrillasRes] = await Promise.all([
            MaterialesService.getAll(),
            CuadrillasService.getAll()
        ]);

        materialesOptions = materialesRes.data;
        cuadrillasOptions = cuadrillasRes.data;
    } catch (error) {
        console.error('Error loading filter options:', error);
    }
}

function selectReportType(type) {
    currentReportType = type;
    const config = reportTypes[type];

    // Highlight selected card
    document.querySelectorAll('.grid-cols-4 .card').forEach(card => {
        card.style.borderColor = 'var(--color-border)';
    });
    event.currentTarget.style.borderColor = 'var(--color-accent-primary)';

    // Show filters section
    document.getElementById('filters-section').style.display = 'block';
    document.getElementById('report-preview').style.display = 'none';

    // Build filters
    buildFilters(config.filters);
}

function buildFilters(filterTypes) {
    const container = document.getElementById('filters-container');
    let html = '';

    filterTypes.forEach(filter => {
        switch (filter) {
            case 'categoria':
                const categorias = [...new Set(materialesOptions.map(m => m.categoria).filter(Boolean))];
                html += `
                    <select class="form-control" id="filter-categoria" style="width: auto; min-width: 180px;">
                        <option value="">Todas las categorías</option>
                        ${categorias.map(c => `<option value="${c}">${c}</option>`).join('')}
                    </select>
                `;
                break;

            case 'tipo':
                html += `
                    <select class="form-control" id="filter-tipo" style="width: auto; min-width: 150px;">
                        <option value="">Todos los tipos</option>
                        <option value="entrada">Entradas</option>
                        <option value="salida">Salidas</option>
                    </select>
                `;
                break;

            case 'material':
                html += `
                    <select class="form-control" id="filter-material" style="width: auto; min-width: 200px;">
                        <option value="">Todos los materiales</option>
                        ${materialesOptions.map(m => `<option value="${m.id}">${m.nombre}</option>`).join('')}
                    </select>
                `;
                break;

            case 'cuadrilla':
                html += `
                    <select class="form-control" id="filter-cuadrilla" style="width: auto; min-width: 200px;">
                        <option value="">Todas las cuadrillas</option>
                        ${cuadrillasOptions.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('')}
                    </select>
                `;
                break;

            case 'fechas':
                const today = new Date().toISOString().split('T')[0];
                const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                html += `
                    <div class="d-flex gap-sm align-center">
                        <label>Desde:</label>
                        <input type="date" class="form-control" id="filter-desde" value="${monthAgo}" style="width: auto;">
                    </div>
                    <div class="d-flex gap-sm align-center">
                        <label>Hasta:</label>
                        <input type="date" class="form-control" id="filter-hasta" value="${today}" style="width: auto;">
                    </div>
                `;
                break;
        }
    });

    container.innerHTML = html || '<span class="text-muted">Este reporte no requiere filtros adicionales</span>';
}

async function generateReport() {
    if (!currentReportType) {
        Toast.warning('Seleccione un tipo de reporte');
        return;
    }

    try {
        Loading.show('#report-content');
        document.getElementById('report-preview').style.display = 'block';

        const config = reportTypes[currentReportType];
        document.getElementById('report-title').textContent = config.title;
        document.getElementById('report-date').textContent = `Generado: ${formatDate(new Date().toISOString())}`;

        // Fetch data based on report type
        switch (currentReportType) {
            case 'stock':
                await generateStockReport();
                break;
            case 'movimientos':
                await generateMovimientosReport();
                break;
            case 'cuadrillas':
                await generateCuadrillasReport();
                break;
            case 'alertas':
                await generateAlertasReport();
                break;
        }

    } catch (error) {
        console.error('Error generating report:', error);
        Toast.error('Error al generar el reporte');
    }
}

async function generateStockReport() {
    const categoria = document.getElementById('filter-categoria')?.value || '';

    const response = await MaterialesService.getAll({ categoria });
    reportData = response.data;

    renderReportTable(reportTypes.stock.columns, reportData.map(m => {
        const status = getStockStatus(m.stock_actual, m.stock_minimo);
        return [
            m.codigo || '-',
            m.nombre,
            m.categoria || '-',
            m.unidad_medida,
            formatNumber(m.stock_actual, 2),
            formatNumber(m.stock_minimo, 2),
            `<span class="stock-indicator ${status.class}">${status.text}</span>`
        ];
    }));
}

async function generateMovimientosReport() {
    const params = {};

    const tipo = document.getElementById('filter-tipo')?.value;
    const materialId = document.getElementById('filter-material')?.value;
    const cuadrillaId = document.getElementById('filter-cuadrilla')?.value;
    const desde = document.getElementById('filter-desde')?.value;
    const hasta = document.getElementById('filter-hasta')?.value;

    if (tipo) params.tipo = tipo;
    if (materialId) params.material_id = materialId;
    if (cuadrillaId) params.cuadrilla_id = cuadrillaId;
    if (desde) params.fecha_desde = desde;
    if (hasta) params.fecha_hasta = hasta;
    params.limit = 100;

    const response = await MovimientosService.getAll(params);
    reportData = response.data;

    renderReportTable(reportTypes.movimientos.columns, reportData.map(m => [
        formatDate(m.fecha),
        `<span class="stock-indicator ${m.tipo === 'entrada' ? 'ok' : 'warning'}">${m.tipo}</span>`,
        m.material_nombre,
        `${formatNumber(m.cantidad, 2)} ${m.unidad_medida}`,
        m.cuadrilla_nombre || '-',
        m.usuario_nombre,
        m.observaciones || '-'
    ]));
}

async function generateCuadrillasReport() {
    const response = await DashboardService.getConsumptionBySquad();
    reportData = response.data;

    const total = reportData.reduce((sum, c) => sum + parseFloat(c.total_consumo || 0), 0);

    renderReportTable(reportTypes.cuadrillas.columns, reportData.map(c => {
        const percent = total > 0 ? ((c.total_consumo / total) * 100).toFixed(1) : 0;
        return [
            c.nombre,
            c.zona_trabajo || '-',
            formatNumber(c.total_consumo || 0),
            '-', // Would need additional query
            `${percent}%`
        ];
    }));
}

async function generateAlertasReport() {
    const response = await DashboardService.getAlerts();
    reportData = response.data;

    renderReportTable(reportTypes.alertas.columns, reportData.map(a => {
        const deficit = a.stock_minimo - a.stock_actual;
        const urgency = a.porcentaje <= 25 ? 'Crítico' : (a.porcentaje <= 50 ? 'Alto' : 'Medio');
        return [
            a.codigo || '-',
            a.nombre,
            `${formatNumber(a.stock_actual, 2)} ${a.unidad_medida}`,
            `${formatNumber(a.stock_minimo, 2)} ${a.unidad_medida}`,
            formatNumber(deficit, 2),
            `<span class="stock-indicator ${a.porcentaje <= 25 ? 'critical' : 'warning'}">${urgency}</span>`
        ];
    }));
}

function renderReportTable(columns, rows) {
    const container = document.getElementById('report-content');

    if (rows.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <div class="empty-state-title">No hay datos para mostrar</div>
                <p class="text-muted">Prueba con otros filtros</p>
            </div>
        `;
        return;
    }

    let html = '<div class="data-table-container"><table class="data-table">';

    // Header
    html += '<thead><tr>';
    columns.forEach(col => html += `<th>${col}</th>`);
    html += '</tr></thead>';

    // Body
    html += '<tbody>';
    rows.forEach(row => {
        html += '<tr>';
        row.forEach(cell => html += `<td>${cell}</td>`);
        html += '</tr>';
    });
    html += '</tbody></table></div>';

    // Summary
    html += `<p class="text-muted" style="margin-top: var(--spacing-md);">Total: ${rows.length} registros</p>`;

    container.innerHTML = html;
}

function exportToExcel() {
    if (!reportData || reportData.length === 0) {
        Toast.warning('Primero genere un reporte');
        return;
    }

    // Create CSV content
    const config = reportTypes[currentReportType];
    let csv = config.columns.join(',') + '\n';

    reportData.forEach(row => {
        const values = Object.values(row).map(v => {
            // Escape quotes and wrap in quotes
            const str = String(v || '').replace(/"/g, '""');
            return `"${str}"`;
        });
        csv += values.join(',') + '\n';
    });

    // Download
    downloadFile(csv, `reporte_${currentReportType}_${Date.now()}.csv`, 'text/csv');
    Toast.success('Archivo Excel descargado');
}

function exportToPDF() {
    if (!reportData || reportData.length === 0) {
        Toast.warning('Primero genere un reporte');
        return;
    }

    // For PDF we'll use print functionality with styled content
    const config = reportTypes[currentReportType];
    const content = document.getElementById('report-content').innerHTML;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${config.title}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #1a1a2e; margin-bottom: 10px; }
                .meta { color: #666; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; }
                .stock-indicator { padding: 2px 8px; border-radius: 4px; font-size: 12px; }
                .stock-indicator.ok { background: #dcfce7; color: #16a34a; }
                .stock-indicator.warning { background: #fef3c7; color: #d97706; }
                .stock-indicator.critical { background: #fee2e2; color: #dc2626; }
            </style>
        </head>
        <body>
            <h1>${config.title}</h1>
            <p class="meta">Generado: ${new Date().toLocaleString('es-AR')}</p>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.print();

    Toast.success('Documento PDF generado');
}

function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
