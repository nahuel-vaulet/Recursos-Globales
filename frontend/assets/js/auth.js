/**
 * [!] ARCH: JWT Auth Manager — Frontend Token Storage
 * [✓] AUDIT: Handles login, logout, token persistence, auto-refresh
 */

const AuthManager = {
    TOKEN_KEY: 'erp_jwt_token',
    USER_KEY: 'erp_user',

    /**
     * Perform login: call backend, store token.
     * @param {string} email
     * @param {string} password
     * @returns {Promise<object>} User data
     */
    async login(email, password) {
        const result = await api.post('/api/auth/login', { email, password });

        if (result.token) {
            localStorage.setItem(this.TOKEN_KEY, result.token);
            localStorage.setItem(this.USER_KEY, JSON.stringify(result.user));
        }

        return result.user;
    },

    /**
     * Logout: clear token and redirect.
     */
    logout() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
        window.location.hash = '#/login';
    },

    /**
     * Get stored JWT token.
     * @returns {string|null}
     */
    getToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    },

    /**
     * Clear token (called on 401).
     */
    clearToken() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
    },

    /**
     * Check if user is authenticated.
     * @returns {boolean}
     */
    isAuthenticated() {
        const token = this.getToken();
        if (!token) return false;

        // Check expiration from JWT payload
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            return payload.exp * 1000 > Date.now();
        } catch {
            return false;
        }
    },

    /**
     * Get stored user data.
     * @returns {object|null}
     */
    getUser() {
        try {
            return JSON.parse(localStorage.getItem(this.USER_KEY));
        } catch {
            return null;
        }
    },

    /**
     * Refresh token if close to expiration (< 5 min).
     */
    async autoRefresh() {
        const token = this.getToken();
        if (!token) return;

        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const expiresIn = payload.exp * 1000 - Date.now();

            // Refresh if < 5 minutes remaining
            if (expiresIn < 300000 && expiresIn > 0) {
                const result = await api.post('/api/auth/refresh');
                if (result.token) {
                    localStorage.setItem(this.TOKEN_KEY, result.token);
                }
            }
        } catch (err) {
            console.warn('[Auth] Auto-refresh failed:', err.message);
        }
    },

    /**
     * Start periodic auto-refresh (every 4 min).
     */
    startAutoRefresh() {
        setInterval(() => this.autoRefresh(), 240000);
    },

    /**
     * Check if user has a specific role.
     * @param {string} role
     * @returns {boolean}
     */
    hasRole(role) {
        const user = this.getUser();
        if (!user) return false;

        const adminRoles = ['Administrador', 'Gerente', 'Supervisor'];
        if (adminRoles.includes(user.tipo)) return true;

        return user.tipo === role;
    }
};
