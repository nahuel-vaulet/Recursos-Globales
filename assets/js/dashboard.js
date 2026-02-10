/**
 * Dashboard Page JavaScript
 * Handles data loading and chart rendering
 */

// Chart instance
let trendsChart = null;

/**
 * Initialize dashboard on page load
 */
document.addEventListener('DOMContentLoaded', async () => {
    // Set current date
    setCurrentDate();

    // Load all dashboard data
    await Promise.all([
        loadStats(),
        loadAlerts(),
        loadRecentActivity(),
        loadConsumptionBySquad(),
        loadTrendsChart()
    ]);
});

/**
 * Set current date display
 */
function setCurrentDate() {
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    const date = new Date().toLocaleDateString('es-AR', options);
    document.getElementById('current-date').textContent = date.charAt(0).toUpperCase() + date.slice(1);
}

/**
 * Load main statistics
 */
async function loadStats() {
    try {
        const response = await DashboardService.getStats();
        const data = response.data;

        // Update KPI values with animation
        animateValue('total-materiales', 0, data.total_materiales);
        animateValue('alertas-count', 0, data.alertas_count);
        animateValue('movimientos-hoy', 0, data.movimientos_hoy);

        // Format consumption
        document.getElementById('consumo-mensual').textContent = formatNumber(data.consumo_mensual);

        // Update variation badge
        const variacionEl = document.getElementById('variacion-mensual');
        const isPositive = data.variacion_mensual >= 0;
        variacionEl.className = `metric-change ${isPositive ? 'negative' : 'positive'}`;
        variacionEl.innerHTML = `
            <i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i>
            <span>${Math.abs(data.variacion_mensual)}%</span>
        `;

    } catch (error) {
        console.error('Error loading stats:', error);
        Toast.error('Error al cargar estadísticas');
    }
}

/**
 * Load low stock alerts
 */
async function loadAlerts() {
    try {
        const response = await DashboardService.getAlerts();
        const alerts = response.data;
        const container = document.getElementById('alerts-list');

        if (alerts.length === 0) {
            container.innerHTML = `
                <li class="empty-state">
                    <i class="fas fa-check-circle text-success"></i>
                    <div class="empty-state-title">¡Todo en orden!</div>
                    <p class="text-muted">No hay alertas de stock bajo</p>
                </li>
            `;
            return;
        }

        container.innerHTML = alerts.map(alert => `
            <li class="alert-item">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-item-content">
                    <div class="alert-item-title">${alert.nombre}</div>
                    <div class="alert-item-description">
                        Stock: ${formatNumber(alert.stock_actual)} / Mínimo: ${formatNumber(alert.stock_minimo)} ${alert.unidad_medida}
                    </div>
                </div>
                <span class="stock-indicator critical">${alert.porcentaje}%</span>
            </li>
        `).join('');

    } catch (error) {
        console.error('Error loading alerts:', error);
        document.getElementById('alerts-list').innerHTML = `
            <li class="empty-state">
                <i class="fas fa-times-circle text-danger"></i>
                <div class="empty-state-title">Error al cargar alertas</div>
            </li>
        `;
    }
}

/**
 * Load recent activity
 */
async function loadRecentActivity() {
    try {
        const response = await DashboardService.getRecentMovements(5);
        const movements = response.data;
        const container = document.getElementById('activity-list');

        if (movements.length === 0) {
            container.innerHTML = `
                <li class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <div class="empty-state-title">Sin actividad reciente</div>
                </li>
            `;
            return;
        }

        container.innerHTML = movements.map(mov => `
            <li class="activity-item">
                <div class="activity-icon ${mov.tipo}">
                    <i class="fas fa-${mov.tipo === 'entrada' ? 'arrow-down' : 'arrow-up'}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">
                        ${mov.tipo === 'entrada' ? 'Entrada' : 'Salida'} de ${mov.material_nombre}
                    </div>
                    <div class="activity-meta">
                        ${formatNumber(mov.cantidad)} ${mov.unidad_medida} 
                        ${mov.cuadrilla_nombre ? `• ${mov.cuadrilla_nombre}` : ''} 
                        • ${formatDate(mov.fecha)}
                    </div>
                </div>
            </li>
        `).join('');

    } catch (error) {
        console.error('Error loading activity:', error);
    }
}

/**
 * Load consumption by squad
 */
async function loadConsumptionBySquad() {
    try {
        const response = await DashboardService.getConsumptionBySquad();
        const consumption = response.data;
        const container = document.getElementById('consumption-list');

        if (consumption.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <div class="empty-state-title">Sin datos de consumo</div>
                </div>
            `;
            return;
        }

        container.innerHTML = consumption.map(c => `
            <div class="cuadrilla-item">
                <div class="cuadrilla-header">
                    <span class="cuadrilla-name">${c.nombre}</span>
                    <span class="cuadrilla-value">${formatNumber(c.total_consumo)} unidades</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: ${c.porcentaje}%"></div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading consumption:', error);
    }
}

/**
 * Load and render trends chart
 */
async function loadTrendsChart() {
    try {
        const response = await DashboardService.getMonthlyTrends();
        const trends = response.data;

        const ctx = document.getElementById('trends-chart').getContext('2d');

        // Get current theme colors
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        // Destroy existing chart
        if (trendsChart) {
            trendsChart.destroy();
        }

        trendsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trends.map(t => t.mes_label),
                datasets: [
                    {
                        label: 'Entradas',
                        data: trends.map(t => t.entradas),
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Salidas',
                        data: trends.map(t => t.salidas),
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1a1a2e' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#1a1a2e',
                        bodyColor: isDark ? '#94a3b8' : '#64748b',
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
                        },
                        beginAtZero: true
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error loading trends chart:', error);
    }
}

/**
 * Animate number value
 */
function animateValue(elementId, start, end, duration = 500) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const range = end - start;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + range * easeOut);

        element.textContent = formatNumber(current);

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

// Update chart on theme change
const originalToggle = ThemeToggle.toggle.bind(ThemeToggle);
ThemeToggle.toggle = function () {
    originalToggle();
    // Reload chart with new colors after small delay
    setTimeout(loadTrendsChart, 100);
};
