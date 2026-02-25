/**
 * Service Worker — ODT Import Module
 * Strategy: Cache-First for static assets, Network-First for API/uploads
 */
const CACHE_NAME = 'odt-import-v1';
const STATIC_ASSETS = [
    '/APP-Prueba/modules/odt/importar_odt.php',
    '/APP-Prueba/includes/header.php',
    '/APP-Prueba/assets/css/main.css'
];

// ─── INSTALL: Pre-cache static shell ────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[SW] Pre-caching static assets');
                return cache.addAll(STATIC_ASSETS).catch(err => {
                    console.warn('[SW] Some assets failed to cache:', err);
                });
            })
            .then(() => self.skipWaiting())
    );
});

// ─── ACTIVATE: Clean old caches ─────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ─── FETCH: Cache-First for GET, Network-Only for POST ──
self.addEventListener('fetch', event => {
    // Never cache POST requests (file uploads)
    if (event.request.method !== 'GET') return;

    // Network-First for API calls
    if (event.request.url.includes('/api/')) return;

    event.respondWith(
        caches.match(event.request).then(cached => {
            if (cached) return cached;

            return fetch(event.request).then(response => {
                // Only cache valid responses
                if (!response || response.status !== 200) return response;

                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, clone);
                });

                return response;
            }).catch(() => {
                // Offline fallback — return cached version if available
                return caches.match('/APP-Prueba/modules/odt/importar_odt.php');
            });
        })
    );
});
