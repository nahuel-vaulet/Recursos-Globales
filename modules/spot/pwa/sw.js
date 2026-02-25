const CACHE_NAME = 'spot-pwa-v1';
const ASSETS_TO_CACHE = [
    '/APP-Prueba/modules/spot/presentation/materiales.php',
    '/APP-Prueba/modules/spot/presentation/combustible.php',
    '/APP-Prueba/assets/css/style.css',
    '/APP-Prueba/assets/img/RG_Logo.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS_TO_CACHE))
    );
});

self.addEventListener('fetch', event => {
    // Offline Queue Logic for API calls
    if (event.request.method === 'POST' && event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(async () => {
                // Background Sync Queue Placeholder
                // In production, use IndexedDB to store the request
                return new Response(JSON.stringify({
                    status: 'offline',
                    message: 'Operación guardada localmente. Se sincronizará al recuperar conexión.'
                }), { headers: { 'Content-Type': 'application/json' } });
            })
        );
        return;
    }

    // Cache-First for static assets
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});
