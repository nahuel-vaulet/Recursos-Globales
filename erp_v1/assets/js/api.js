const API_BASE_URL = './api';

const api = {
    token: localStorage.getItem('auth_token') || null,

    getHeaders() {
        const headers = { 'Content-Type': 'application/json' };
        if (this.token) headers['Authorization'] = 'Bearer ' + this.token;
        return headers;
    },

    async handleResponse(response) {
        const data = await response.json();
        if (!response.ok) {
            if (response.status === 401) { this.logout(); window.location.href = '/login.html'; }
            throw new Error(data.message || 'Error en la peticion');
        }
        return data;
    },

    async get(endpoint, params = {}) {
        const url = new URL(API_BASE_URL + endpoint, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        const response = await fetch(url, { method: 'GET', headers: this.getHeaders() });
        return this.handleResponse(response);
    },

    async post(endpoint, body = {}) {
        const response = await fetch(API_BASE_URL + endpoint, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(body)
        });
        return this.handleResponse(response);
    },

    async put(endpoint, body = {}) {
        const response = await fetch(API_BASE_URL + endpoint, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify(body)
        });
        return this.handleResponse(response);
    },

    async delete(endpoint) {
        const response = await fetch(API_BASE_URL + endpoint, { method: 'DELETE', headers: this.getHeaders() });
        return this.handleResponse(response);
    },

    setToken(token) { this.token = token; localStorage.setItem('auth_token', token); },
    logout() { this.token = null; localStorage.removeItem('auth_token'); localStorage.removeItem('user'); },
    getUser() { const user = localStorage.getItem('user'); return user ? JSON.parse(user) : null; },
    setUser(user) { localStorage.setItem('user', JSON.stringify(user)); }
};

window.api = api;
