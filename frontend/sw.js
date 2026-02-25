/**
 * [!] ARCH: Service Worker — Offline-First PWA Strategy
 * [✓] AUDIT: Cache-first for static assets, network-first for API
 */

const CACHE_NAME = 'erp-rg-v2.2.0';
const STATIC_ASSETS = [
    './',
    './index.html',
    './offline.html',
    './manifest.json',
    './assets/css/app.css',
    './assets/js/api.js',
    './assets/js/auth.js',
    './assets/js/app.js',
];

// ─── Install: Cache static assets ──────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// ─── Activate: Clean old caches ────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

// ─── Fetch Strategy ────────────────────────
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // API calls: Network-first, no cache fallback
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return new Response(JSON.stringify({
                    error: 'ERR-CX-OFFLINE',
                    message: 'Sin conexión. Los datos se sincronizarán cuando vuelva la red.'
                }), {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // Static assets: Cache-first
    event.respondWith(
        caches.match(event.request).then(cached => {
            return cached || fetch(event.request).then(response => {
                // Cache successful static responses
                if (response.status === 200) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                }
                return response;
            }).catch(() => {
                // Fallback to offline page for navigation
                if (event.request.mode === 'navigate') {
                    return caches.match('./offline.html');
                }
            });
        })
    );
});
