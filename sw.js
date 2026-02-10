/**
 * [!] ARCH: Service Worker para PWA ERP Recursos Globales
 * [→] EDITAR: Cambiar CACHE_VERSION al hacer cambios en assets
 * [✓] AUDIT: Cache-first para assets, network-first para API
 */

const CACHE_VERSION = 'erp-v1.4'; // [!] UPDATE: Removed index.php from static cache
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;

// [→] EDITAR: Assets a cachear para funcionamiento offline
const STATIC_ASSETS = [
    '/APP-Prueba/assets/css/main.css',
    '/APP-Prueba/assets/css/style.css',
    '/APP-Prueba/RG_Logo.png',
    '/APP-Prueba/icons/icon-192.png',
    '/APP-Prueba/icons/icon-512.png',
    '/APP-Prueba/offline.html'
];

// [!] PWA-OFFLINE: Instalar y cachear assets estáticos
self.addEventListener('install', event => {
    console.log('[SW] Instalando Service Worker...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[SW] Cacheando assets estáticos');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// [!] ARCH: Limpiar caches antiguas al activar
self.addEventListener('activate', event => {
    console.log('[SW] Activando Service Worker...');
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => {
                        console.log('[SW] Eliminando cache antigua:', key);
                        return caches.delete(key);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// [!] PWA-OFFLINE: Estrategia de fetch
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // [✓] AUDIT: API requests -> Network first, fallback to offline response
    if (url.pathname.includes('/api/')) {
        event.respondWith(networkFirst(event.request));
        return;
    }

    // [✓] AUDIT: PHP pages -> Network first with cache fallback
    if (url.pathname.endsWith('.php')) {
        event.respondWith(networkFirst(event.request));
        return;
    }

    // [✓] AUDIT: Static assets -> Cache first
    event.respondWith(cacheFirst(event.request));
});

// [!] ARCH: Estrategia Cache-First
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Fallback para imágenes
        if (request.destination === 'image') {
            return caches.match('/APP-Prueba/icons/icon-192.png');
        }
        return new Response('Offline', { status: 503 });
    }
}

// [!] ARCH: Estrategia Network-First
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;

        // [!] PWA-OFFLINE: Página de fallback
        if (request.mode === 'navigate') {
            return caches.match('/APP-Prueba/offline.html');
        }

        return new Response(JSON.stringify({
            offline: true,
            message: 'Sin conexión'
        }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// [!] ARCH: Escuchar mensajes del cliente
self.addEventListener('message', event => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});
