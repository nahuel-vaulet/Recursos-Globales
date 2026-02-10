const utils = {
    showToast(message, type = 'info', duration = 4000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const icons = { success: 'check-circle-fill', warning: 'exclamation-triangle-fill', danger: 'x-circle-fill', info: 'info-circle-fill' };
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.innerHTML = '<i class="bi bi-' + (icons[type] || icons.info) + '"></i><span>' + message + '</span>';
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    },

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },

    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    },

    getStockStatus(actual, minimo) {
        const ratio = actual / minimo;
        if (ratio <= 1) return 'danger';
        if (ratio <= 1.5) return 'warning';
        return 'ok';
    },

    getStockBadge(actual, minimo) {
        const status = this.getStockStatus(actual, minimo);
        const labels = { ok: 'En stock', warning: 'Stock bajo', danger: 'Critico' };
        return '<span class="stock-badge ' + status + '">' + labels[status] + '</span>';
    },

    debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    },

    toggleDarkMode() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        return next;
    },

    applyStoredTheme() {
        const stored = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', stored);
    }
};

document.addEventListener('DOMContentLoaded', () => utils.applyStoredTheme());
window.utils = utils;
