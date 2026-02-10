/**
 * Reusable UI Components
 * Following buenas-practicas.md componentization principles
 */

/**
 * Toast Notification System
 * Handles all edge states: loading, success, error, warning
 */
const Toast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
        };

        const titles = {
            success: 'Éxito',
            error: 'Error',
            warning: 'Advertencia',
            info: 'Información',
        };

        toast.innerHTML = `
            <i class="toast-icon ${icons[type]}"></i>
            <div class="toast-content">
                <div class="toast-title">${titles[type]}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Close button handler
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.dismiss(toast);
        });

        this.container.appendChild(toast);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => this.dismiss(toast), duration);
        }

        return toast;
    },

    dismiss(toast) {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    },

    success(message, duration) {
        return this.show(message, 'success', duration);
    },

    error(message, duration) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration) {
        return this.show(message, 'info', duration);
    }
};

/**
 * Modal Component
 * Handles modal lifecycle
 */
const Modal = {
    activeModal: null,
    backdrop: null,

    init() {
        if (!this.backdrop) {
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'modal-backdrop';
            this.backdrop.id = 'modal-backdrop';
            document.body.appendChild(this.backdrop);

            this.backdrop.addEventListener('click', () => this.close());

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.activeModal) {
                    this.close();
                }
            });
        }
    },

    open(modalId) {
        this.init();
        const modal = document.getElementById(modalId);
        if (!modal) return;

        this.activeModal = modal;
        this.backdrop.classList.add('active');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    close() {
        if (this.activeModal) {
            this.activeModal.classList.remove('active');
            this.backdrop.classList.remove('active');
            document.body.style.overflow = '';
            this.activeModal = null;
        }
    },

    /**
     * Create a dynamic modal
     */
    create(options = {}) {
        const {
            id = 'dynamic-modal',
            title = 'Modal',
            content = '',
            footer = '',
            size = 'md'
        } = options;

        // Remove existing modal with same id
        const existing = document.getElementById(id);
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = id;
        modal.className = `modal modal-${size}`;

        modal.innerHTML = `
            <div class="modal-header">
                <h3 class="modal-title">${title}</h3>
                <button class="modal-close" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">${content}</div>
            ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
        `;

        modal.querySelector('.modal-close').addEventListener('click', () => this.close());

        document.body.appendChild(modal);
        return modal;
    },

    /**
     * Confirmation dialog
     */
    confirm(message, onConfirm, onCancel = null) {
        const modal = this.create({
            id: 'confirm-modal',
            title: 'Confirmar acción',
            content: `<p>${message}</p>`,
            footer: `
                <button class="btn btn-secondary" id="confirm-cancel">Cancelar</button>
                <button class="btn btn-danger" id="confirm-accept">Confirmar</button>
            `
        });

        this.open('confirm-modal');

        document.getElementById('confirm-cancel').addEventListener('click', () => {
            this.close();
            if (onCancel) onCancel();
        });

        document.getElementById('confirm-accept').addEventListener('click', () => {
            this.close();
            if (onConfirm) onConfirm();
        });
    }
};

/**
 * Theme Toggle Component
 */
const ThemeToggle = {
    storageKey: 'theme',

    init() {
        const savedTheme = localStorage.getItem(this.storageKey);
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else {
            // Check system preference
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        }

        this.updateIcon();
    },

    toggle() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem(this.storageKey, newTheme);
        this.updateIcon();
    },

    updateIcon() {
        const btn = document.getElementById('theme-toggle');
        if (!btn) return;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        btn.innerHTML = `<i class="fas fa-${isDark ? 'sun' : 'moon'}"></i>`;
    }
};

/**
 * Sidebar Component
 */
const Sidebar = {
    isCollapsed: false,
    storageKey: 'sidebar-collapsed',

    init() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved === 'true') {
            this.collapse();
        }

        // Mobile: close on click outside
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.btn-toggle-sidebar');

            if (window.innerWidth <= 992 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                this.closeMobile();
            }
        });
    },

    toggle() {
        if (window.innerWidth <= 992) {
            this.toggleMobile();
        } else {
            this.isCollapsed ? this.expand() : this.collapse();
        }
    },

    collapse() {
        document.body.classList.add('sidebar-collapsed');
        this.isCollapsed = true;
        localStorage.setItem(this.storageKey, 'true');
    },

    expand() {
        document.body.classList.remove('sidebar-collapsed');
        this.isCollapsed = false;
        localStorage.setItem(this.storageKey, 'false');
    },

    toggleMobile() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('open');
    },

    closeMobile() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.remove('open');
    }
};

/**
 * DataTable Component
 * Handles table rendering with pagination and search
 */
const DataTable = {
    instances: {},

    init(tableId, options = {}) {
        const {
            data = [],
            columns = [],
            pageSize = 10,
            searchable = true,
            onEdit = null,
            onDelete = null,
        } = options;

        this.instances[tableId] = {
            data,
            columns,
            pageSize,
            currentPage: 1,
            searchTerm: '',
            searchable,
            onEdit,
            onDelete,
        };

        this.render(tableId);
        return this;
    },

    render(tableId) {
        const instance = this.instances[tableId];
        if (!instance) return;

        const container = document.getElementById(tableId);
        if (!container) return;

        const filteredData = this.filterData(tableId);
        const paginatedData = this.paginateData(tableId, filteredData);
        const totalPages = Math.ceil(filteredData.length / instance.pageSize);

        let html = '';

        // Search box
        if (instance.searchable) {
            html += `
                <div class="d-flex justify-between align-center mb-md">
                    <div class="search-box" style="width: 300px;">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar..." 
                               value="${instance.searchTerm}"
                               onkeyup="DataTable.search('${tableId}', this.value)">
                    </div>
                </div>
            `;
        }

        html += '<div class="data-table-container"><table class="data-table">';

        // Header
        html += '<thead><tr>';
        instance.columns.forEach(col => {
            html += `<th>${col.label}</th>`;
        });
        if (instance.onEdit || instance.onDelete) {
            html += '<th class="text-center">Acciones</th>';
        }
        html += '</tr></thead>';

        // Body
        html += '<tbody>';
        if (paginatedData.length === 0) {
            html += `
                <tr>
                    <td colspan="${instance.columns.length + 1}">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <div class="empty-state-title">No hay datos disponibles</div>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            paginatedData.forEach(row => {
                html += '<tr>';
                instance.columns.forEach(col => {
                    const value = row[col.key];
                    const rendered = col.render ? col.render(value, row) : value;
                    html += `<td>${rendered}</td>`;
                });
                if (instance.onEdit || instance.onDelete) {
                    html += '<td class="table-actions text-center">';
                    if (instance.onEdit) {
                        html += `<button class="btn btn-icon btn-outline" onclick="${instance.onEdit}(${row.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>`;
                    }
                    if (instance.onDelete) {
                        html += `<button class="btn btn-icon btn-outline text-danger" onclick="${instance.onDelete}(${row.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>`;
                    }
                    html += '</td>';
                }
                html += '</tr>';
            });
        }
        html += '</tbody></table></div>';

        // Pagination
        if (totalPages > 1) {
            html += this.renderPagination(tableId, totalPages);
        }

        container.innerHTML = html;
    },

    renderPagination(tableId, totalPages) {
        const instance = this.instances[tableId];
        let html = '<div class="d-flex justify-between align-center" style="margin-top: var(--spacing-md);">';

        html += `<span class="text-muted">Página ${instance.currentPage} de ${totalPages}</span>`;
        html += '<div class="d-flex gap-sm">';

        html += `<button class="btn btn-sm btn-outline" 
                         ${instance.currentPage === 1 ? 'disabled' : ''}
                         onclick="DataTable.goToPage('${tableId}', ${instance.currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        html += `<button class="btn btn-sm btn-outline" 
                         ${instance.currentPage === totalPages ? 'disabled' : ''}
                         onclick="DataTable.goToPage('${tableId}', ${instance.currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        html += '</div></div>';
        return html;
    },

    filterData(tableId) {
        const instance = this.instances[tableId];
        if (!instance.searchTerm) return instance.data;

        const term = instance.searchTerm.toLowerCase();
        return instance.data.filter(row => {
            return instance.columns.some(col => {
                const value = row[col.key];
                return value && value.toString().toLowerCase().includes(term);
            });
        });
    },

    paginateData(tableId, data) {
        const instance = this.instances[tableId];
        const start = (instance.currentPage - 1) * instance.pageSize;
        return data.slice(start, start + instance.pageSize);
    },

    search(tableId, term) {
        const instance = this.instances[tableId];
        if (!instance) return;

        instance.searchTerm = term;
        instance.currentPage = 1;
        this.render(tableId);
    },

    goToPage(tableId, page) {
        const instance = this.instances[tableId];
        if (!instance) return;

        instance.currentPage = page;
        this.render(tableId);
    },

    setData(tableId, data) {
        const instance = this.instances[tableId];
        if (!instance) return;

        instance.data = data;
        instance.currentPage = 1;
        this.render(tableId);
    },

    refresh(tableId) {
        this.render(tableId);
    }
};

/**
 * Loading Spinner Component
 */
const Loading = {
    show(container) {
        const el = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!el) return;

        el.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <div class="empty-state-title">Cargando...</div>
            </div>
        `;
    },

    hide(container, content = '') {
        const el = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!el) return;
        el.innerHTML = content;
    }
};

/**
 * Stock Indicator Helper
 * Returns appropriate class and text for stock status
 */
function getStockStatus(stockActual, stockMinimo) {
    const ratio = stockActual / stockMinimo;

    if (ratio <= 0.5) {
        return { class: 'critical', text: 'Crítico' };
    } else if (ratio <= 1) {
        return { class: 'warning', text: 'Bajo' };
    } else {
        return { class: 'ok', text: 'Normal' };
    }
}

/**
 * Format number with locale
 */
function formatNumber(num, decimals = 0) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(num);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    ThemeToggle.init();
    Sidebar.init();
});
