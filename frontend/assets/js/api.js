/**
 * [!] ARCH: API Client Wrapper — Centralized fetch with error handling
 * [✓] AUDIT: Every API call goes through this module
 * [→] EDITAR: Update API_BASE_URL when backend is deployed
 *
 * Error Codes:
 *   ERR-CX-502     — Backend unreachable
 *   ERR-CX-TIMEOUT — Request timed out
 *   ERR-CX-CORS    — CORS policy violation
 *   ERR-CX-401     — Token expired or invalid
 *   ERR-CX-403     — Insufficient permissions
 *   ERR-CX-PARSE   — Response not valid JSON
 */

// ─── Configuration ──────────────────────────────────────
// Same-origin in Docker/Render (empty string = relative URLs)
// Override via APP_CONFIG or localStorage for local XAMPP dev
const API_BASE_URL = window.APP_CONFIG?.API_URL
    || localStorage.getItem('api_url')
    || '';

const REQUEST_TIMEOUT_MS = 15000;

// ─── Core Fetch Wrapper ─────────────────────────────────

/**
 * Make an authenticated API request.
 *
 * @param {string} endpoint  — Relative path, e.g. '/api/odt'
 * @param {object} options   — { method, body, headers, timeout }
 * @returns {Promise<object>} — Parsed JSON response
 * @throws {ApiError}        — Structured error with code + message
 */
async function apiFetch(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const method = (options.method || 'GET').toUpperCase();
    const timeout = options.timeout || REQUEST_TIMEOUT_MS;

    // Build headers
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
    };

    // Attach JWT if available
    const token = AuthManager.getToken();
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    // Build fetch config
    const fetchConfig = {
        method,
        headers,
        credentials: 'include',
    };

    if (options.body && method !== 'GET') {
        fetchConfig.body = typeof options.body === 'string'
            ? options.body
            : JSON.stringify(options.body);
    }

    // Timeout via AbortController
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);
    fetchConfig.signal = controller.signal;

    try {
        const response = await fetch(url, fetchConfig);
        clearTimeout(timeoutId);

        // Handle 401 → auto-redirect to login
        if (response.status === 401) {
            AuthManager.clearToken();
            const data = await response.json().catch(() => ({}));
            throw new ApiError(
                data.error || 'ERR-CX-401',
                data.message || 'Sesión expirada. Por favor, inicie sesión nuevamente.',
                401
            );
        }

        // Handle non-2xx
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new ApiError(
                data.error || `ERR-HTTP-${response.status}`,
                data.message || `Error del servidor (${response.status})`,
                response.status
            );
        }

        // Empty response (204 No Content)
        if (response.status === 204) {
            return null;
        }

        // Parse JSON
        try {
            return await response.json();
        } catch {
            throw new ApiError('ERR-CX-PARSE', 'Respuesta del servidor no es JSON válido', 500);
        }

    } catch (err) {
        clearTimeout(timeoutId);

        // Already an ApiError — re-throw
        if (err instanceof ApiError) throw err;

        // Network/CORS error
        if (err.name === 'AbortError') {
            throw new ApiError('ERR-CX-TIMEOUT', `La solicitud tardó más de ${timeout / 1000}s`, 0);
        }

        if (err.name === 'TypeError' && err.message.includes('Failed to fetch')) {
            throw new ApiError('ERR-CX-502', 'No se pudo conectar con el servidor', 0);
        }

        throw new ApiError('ERR-CX-UNKNOWN', err.message || 'Error desconocido', 0);
    }
}

// ─── Convenience Methods ────────────────────────────────

const api = {
    get: (endpoint, opts = {}) => apiFetch(endpoint, { ...opts, method: 'GET' }),
    post: (endpoint, body, opts = {}) => apiFetch(endpoint, { ...opts, method: 'POST', body }),
    put: (endpoint, body, opts = {}) => apiFetch(endpoint, { ...opts, method: 'PUT', body }),
    patch: (endpoint, body, opts = {}) => apiFetch(endpoint, { ...opts, method: 'PATCH', body }),
    delete: (endpoint, opts = {}) => apiFetch(endpoint, { ...opts, method: 'DELETE' }),
};

// ─── Error Class ────────────────────────────────────────

class ApiError extends Error {
    constructor(code, message, status) {
        super(message);
        this.name = 'ApiError';
        this.code = code;
        this.status = status;
    }
}

// ─── Global Error Toast ─────────────────────────────────

/**
 * Show a toast notification for API errors.
 * Auto-injects a toast container if not present.
 */
function showApiError(error) {
    const code = error.code || 'ERR-UNKNOWN';
    const msg = error.message || 'Error desconocido';

    console.error(`[${code}]`, msg);

    // Create or find toast container
    let container = document.getElementById('api-error-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'api-error-container';
        container.style.cssText = `
            position: fixed; top: 16px; right: 16px; z-index: 99999;
            display: flex; flex-direction: column; gap: 8px;
            max-width: 420px; pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: var(--bg-card, #1e2938); border: 1px solid var(--color-danger, #ef4444);
        border-left: 4px solid var(--color-danger, #ef4444); border-radius: 8px;
        padding: 12px 16px; color: var(--text-primary, #fff); font-size: 0.85em;
        box-shadow: 0 4px 20px rgba(0,0,0,0.4); pointer-events: auto;
        animation: slideInRight 0.3s ease-out;
        display: flex; align-items: flex-start; gap: 10px;
    `;

    toast.innerHTML = `
        <div style="flex:1;">
            <strong style="color: var(--color-danger, #ef4444);">${code}</strong>
            <p style="margin:4px 0 0; opacity:0.9;">${msg}</p>
        </div>
        <button onclick="this.parentElement.remove()" style="
            background:none; border:none; color: var(--text-muted, #888);
            font-size:1.2em; cursor:pointer; padding:0; line-height:1;
        ">&times;</button>
    `;

    container.appendChild(toast);

    // Auto-dismiss after 8 seconds
    setTimeout(() => toast.remove(), 8000);
}

// ─── Auto-attach to unhandled rejections ────────────────
window.addEventListener('unhandledrejection', (event) => {
    if (event.reason instanceof ApiError) {
        showApiError(event.reason);
        event.preventDefault();
    }
});
