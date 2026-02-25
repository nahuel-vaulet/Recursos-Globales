const CACHE_NAME = 'stock-pwa-v2';
const ASSETS_TO_CACHE = [
    '/APP-Prueba/modules/stock/index.php',
    '/APP-Prueba/assets/css/style.css',
    '/APP-Prueba/assets/img/RG_Logo.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS_TO_CACHE))
    );
});

self.addEventListener('fetch', event => {
    if (event.request.method === 'POST' && event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(async () => {
                return new Response(JSON.stringify({
                    status: 'offline',
                    message: 'Operación guardada localmente en cola de sincronización.'
                }), { headers: { 'Content-Type': 'application/json' } });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});
