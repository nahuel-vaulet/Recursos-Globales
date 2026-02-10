/**
 * API Wrapper Module
 * Dependency agnostic wrapper for HTTP requests (buenas-practicas.md)
 * Abstracts fetch API so it can be swapped later if needed
 */

const API = {
    baseUrl: typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : 'api',

    /**
     * Make HTTP request
     * @param {string} endpoint - API endpoint
     * @param {object} options - Request options
     * @returns {Promise<object>} Response data
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error en la solicitud');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    },

    /**
     * DELETE request
     */
    async delete(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'DELETE' });
    }
};

/**
 * Materiales API Service
 */
const MaterialesService = {
    getAll: (params = {}) => API.get('materiales.php', params),
    getById: (id) => API.get('materiales.php', { id }),
    create: (data) => API.post('materiales.php', data),
    update: (data) => API.put('materiales.php', data),
    delete: (id) => API.delete('materiales.php', { id }),
};

/**
 * Cuadrillas API Service
 */
const CuadrillasService = {
    getAll: (params = {}) => API.get('cuadrillas.php', params),
    getById: (id) => API.get('cuadrillas.php', { id }),
    create: (data) => API.post('cuadrillas.php', data),
    update: (data) => API.put('cuadrillas.php', data),
    delete: (id) => API.delete('cuadrillas.php', { id }),
};

/**
 * Movimientos API Service
 */
const MovimientosService = {
    getAll: (params = {}) => API.get('movimientos.php', params),
    getById: (id) => API.get('movimientos.php', { id }),
    create: (data) => API.post('movimientos.php', data),
    getByMaterial: (materialId) => API.get('movimientos.php', { material_id: materialId }),
    getByCuadrilla: (cuadrillaId) => API.get('movimientos.php', { cuadrilla_id: cuadrillaId }),
};

/**
 * Dashboard API Service
 */
const DashboardService = {
    getStats: () => API.get('dashboard.php'),
    getAlerts: () => API.get('dashboard.php', { action: 'alerts' }),
    getRecentMovements: (limit = 5) => API.get('dashboard.php', { action: 'recent', limit }),
    getConsumptionBySquad: () => API.get('dashboard.php', { action: 'consumption' }),
    getMonthlyTrends: () => API.get('dashboard.php', { action: 'trends' }),
};

/**
 * Usuarios API Service
 */
const UsuariosService = {
    getAll: (params = {}) => API.get('usuarios.php', params),
    getById: (id) => API.get('usuarios.php', { id }),
    create: (data) => API.post('usuarios.php', data),
    update: (data) => API.put('usuarios.php', data),
    delete: (id) => API.delete('usuarios.php', { id }),
};

// Export for module systems (if used)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        API,
        MaterialesService,
        CuadrillasService,
        MovimientosService,
        DashboardService,
        UsuariosService,
    };
}
