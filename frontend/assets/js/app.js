/**
 * [!] ARCH: SPA Router & Application Bootstrap
 * [✓] AUDIT: Hash-based routing (#/odt, #/stock, etc.)
 */

// ─── Route Definitions ─────────────────────────────────
const routes = {
    '/login': { view: 'views/login.html', module: null, auth: false, title: 'Iniciar Sesión' },
    '/': { view: 'views/dashboard.html', module: 'dashboard', auth: true, title: 'Dashboard' },
    '/odt': { view: 'views/odt/list.html', module: 'odt', auth: true, title: 'Gestión de ODTs' },
    '/stock': { view: 'views/stock/list.html', module: 'stock', auth: true, title: 'Stock y Materiales' },
    '/cuadrillas': { view: 'views/cuadrillas/list.html', module: 'cuadrillas', auth: true, title: 'Cuadrillas' },
    '/vehiculos': { view: 'views/vehiculos/list.html', module: 'vehiculos', auth: true, title: 'Vehículos' },
    '/combustibles': { view: 'views/combustibles/list.html', module: 'combustibles', auth: true, title: 'Combustibles' },
    '/herramientas': { view: 'views/herramientas/list.html', module: 'herramientas', auth: true, title: 'Herramientas' },
    '/proveedores': { view: 'views/proveedores/list.html', module: 'proveedores', auth: true, title: 'Proveedores' },
    '/partes': { view: 'views/partes/list.html', module: 'partes', auth: true, title: 'Partes Diarios' },
    '/tareas': { view: 'views/tareas/list.html', module: 'tareas', auth: true, title: 'Tareas' },
    '/usuarios': { view: 'views/usuarios/list.html', module: 'usuarios', auth: true, title: 'Usuarios' },
    '/calendario': { view: 'views/calendario/list.html', module: 'calendario', auth: true, title: 'Calendario' },
    '/personal': { view: 'views/personal/list.html', module: 'personal', auth: true, title: 'Personal' },
    '/compras': { view: 'views/compras/list.html', module: 'compras', auth: true, title: 'Compras' },
    '/gastos': { view: 'views/gastos/list.html', module: 'gastos', auth: true, title: 'Gastos' },
    '/reportes': { view: 'views/reportes/index.html', module: 'reportes', auth: true, title: 'Reportes' },
    '/spot': { view: 'views/spot/list.html', module: 'spot', auth: true, title: 'Puntos de Interés' },
};

// ─── Router ─────────────────────────────────────────────

class AppRouter {
    constructor() {
        this.contentEl = null;
        this.currentModule = null;
    }

    init() {
        this.contentEl = document.getElementById('app-content');

        window.addEventListener('hashchange', () => this.handleRoute());

        // Initial route
        if (!window.location.hash) {
            window.location.hash = AuthManager.isAuthenticated() ? '#/' : '#/login';
        } else {
            this.handleRoute();
        }
    }

    async handleRoute() {
        const hash = window.location.hash.slice(1) || '/';
        const route = routes[hash];

        if (!route) {
            this.contentEl.innerHTML = `
                <div class="card" style="padding:40px; text-align:center;">
                    <h2>404 — Página no encontrada</h2>
                    <p><a href="#/" style="color:var(--accent-primary);">Volver al inicio</a></p>
                </div>
            `;
            return;
        }

        // Auth guard
        if (route.auth && !AuthManager.isAuthenticated()) {
            window.location.hash = '#/login';
            return;
        }
        if (!route.auth && hash === '/login' && AuthManager.isAuthenticated()) {
            window.location.hash = '#/';
            return;
        }

        // Update title
        document.title = `${route.title} — ERP Recursos Globales`;

        // Load view
        try {
            const response = await fetch(route.view);
            if (!response.ok) throw new Error(`View not found: ${route.view}`);
            const html = await response.text();
            this.contentEl.innerHTML = html;

            // Load module JS if needed
            if (route.module && route.module !== this.currentModule) {
                this.currentModule = route.module;
                await this.loadModule(route.module);
            }

            // Update active sidebar link
            this.updateSidebarActive(hash);

        } catch (err) {
            console.error('[Router]', err);
            this.contentEl.innerHTML = `
                <div class="card" style="padding:40px; text-align:center;">
                    <h2>Error cargando la vista</h2>
                    <p style="color:var(--text-muted);">${err.message}</p>
                </div>
            `;
        }
    }

    async loadModule(name) {
        try {
            const script = document.createElement('script');
            script.src = `assets/js/modules/${name}.js`;
            script.onload = () => {
                // Call module init if it exists
                if (typeof window[`init_${name}`] === 'function') {
                    window[`init_${name}`]();
                }
            };
            document.body.appendChild(script);
        } catch (err) {
            console.warn(`[Router] Module ${name} not found`, err);
        }
    }

    updateSidebarActive(hash) {
        document.querySelectorAll('.sidebar-link').forEach(link => {
            const href = link.getAttribute('href');
            link.classList.toggle('active', href === `#${hash}`);
        });
    }
}

// ─── Application Bootstrap ──────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const appRouter = new AppRouter();

    // Show/hide shell based on auth
    const updateShell = () => {
        const isAuth = AuthManager.isAuthenticated();
        const shell = document.getElementById('app-shell');
        const loginView = window.location.hash === '#/login';

        if (shell) {
            shell.classList.toggle('authenticated', isAuth && !loginView);
        }
    };

    window.addEventListener('hashchange', updateShell);

    // Theme toggle
    window.toggleTheme = () => {
        const current = document.body.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    };

    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.body.setAttribute('data-theme', savedTheme);

    // Start auth auto-refresh
    if (AuthManager.isAuthenticated()) {
        AuthManager.startAutoRefresh();
    }

    // Init router
    appRouter.init();
    updateShell();
});
