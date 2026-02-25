/**
 * [!] ARCH: Service Worker para PWA ERP Recursos Globales v2
 * [→] EDITAR: Cambiar CACHE_VERSION al hacer cambios en assets
 * [✓] AUDIT: Cache-first para assets, SWR para calendario/ODT, network-first para API
 * [✓] AUDIT: Background Sync para operaciones offline (POST/PUT)
 */

const CACHE_VERSION = 'erp-v2.1';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;
const SYNC_QUEUE_NAME = 'sync-odt-queue';

// [→] EDITAR: Assets a cachear para funcionamiento offline
const STATIC_ASSETS = [
    '/APP-Prueba/assets/css/main.css',
    '/APP-Prueba/assets/css/style.css',
    '/APP-Prueba/RG_Logo.png',
    '/APP-Prueba/icons/icon-192.png',
    '/APP-Prueba/icons/icon-512.png',
    '/APP-Prueba/offline.html',
    '/APP-Prueba/modules/stock/index.php'
];

// URLs con estrategia Stale-While-Revalidate (datos ODT/calendario)
const SWR_PATTERNS = [
    '/api/calendar.php',
    '/api/odt.php',
];

// [!] PWA-OFFLINE: Instalar y cachear assets estáticos
self.addEventListener('install', event => {
    console.log('[SW v2] Instalando...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[SW v2] Cacheando assets estáticos');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// [!] ARCH: Limpiar caches antiguas al activar
self.addEventListener('activate', event => {
    console.log('[SW v2] Activando...');
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => {
                        console.log('[SW v2] Eliminando cache antigua:', key);
                        return caches.delete(key);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// [!] PWA-OFFLINE: Estrategia de fetch
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // No interceptar POST/PUT — dejar que Background Sync los maneje si fallan
    if (event.request.method !== 'GET') {
        event.respondWith(
            fetch(event.request).catch(async () => {
                // Guardar en cola de sync si es operación ODT
                if (url.pathname.includes('/api/') || url.pathname.includes('bulk_action.php')) {
                    await queueForSync(event.request.clone());
                    return new Response(JSON.stringify({
                        success: true,
                        offline: true,
                        message: 'Operación guardada para sincronización'
                    }), { headers: { 'Content-Type': 'application/json' } });
                }
                return new Response(JSON.stringify({ success: false, offline: true }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // [✓] SWR para datos de calendario y ODT (devuelve cache, actualiza en background)
    if (SWR_PATTERNS.some(p => url.pathname.includes(p))) {
        event.respondWith(staleWhileRevalidate(event.request));
        return;
    }

    // [✓] AUDIT: PHP pages → Network first with cache fallback
    if (url.pathname.endsWith('.php')) {
        event.respondWith(networkFirst(event.request));
        return;
    }

    // [✓] AUDIT: Static assets → Cache first
    event.respondWith(cacheFirst(event.request));
});

// [!] ARCH: Estrategia Cache-First (para assets estáticos)
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
        if (request.destination === 'image') {
            return caches.match('/APP-Prueba/icons/icon-192.png');
        }
        return new Response('Offline', { status: 503 });
    }
}

// [!] ARCH: Estrategia Network-First (para páginas PHP)
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

// [!] ARCH: Stale-While-Revalidate (para API ODT/calendario)
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);

    // Iniciar actualización en background (no bloquea respuesta)
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => null);

    // Devolver cache inmediatamente si existe, sino esperar fetch
    if (cached) {
        // Notificar al cliente si los datos se actualizaron
        fetchPromise.then(freshResponse => {
            if (freshResponse) {
                notifyClients({ type: 'DATA_UPDATED', url: request.url });
            }
        });
        return cached;
    }

    // No hay cache, esperar fetch
    const freshResponse = await fetchPromise;
    if (freshResponse) return freshResponse;

    return new Response(JSON.stringify({ offline: true, message: 'Sin datos disponibles' }), {
        headers: { 'Content-Type': 'application/json' }
    });
}

// ── Background Sync ──
self.addEventListener('sync', event => {
    if (event.tag === 'sync-odt') {
        event.waitUntil(processOfflineQueue());
    }
});

async function queueForSync(request) {
    try {
        const body = await request.text();
        const queueItem = {
            url: request.url,
            method: request.method,
            headers: Object.fromEntries(request.headers.entries()),
            body: body,
            timestamp: Date.now()
        };

        // Usar IndexedDB para persistencia
        const db = await openSyncDB();
        const tx = db.transaction('queue', 'readwrite');
        await tx.objectStore('queue').add(queueItem);

        // Registrar sync tag
        if ('sync' in self.registration) {
            await self.registration.sync.register('sync-odt');
        }

        console.log('[SW v2] Operación encolada para sync');
    } catch (err) {
        console.error('[SW v2] Error al encolar:', err);
    }
}

async function processOfflineQueue() {
    try {
        const db = await openSyncDB();
        const tx = db.transaction('queue', 'readonly');
        const items = await getAllFromStore(tx.objectStore('queue'));

        for (const item of items) {
            try {
                const response = await fetch(item.url, {
                    method: item.method,
                    headers: item.headers,
                    body: item.body
                });

                if (response.ok) {
                    // Eliminar de la cola
                    const deleteTx = db.transaction('queue', 'readwrite');
                    deleteTx.objectStore('queue').delete(item.id);
                    console.log('[SW v2] Sync OK:', item.url);
                }
            } catch (err) {
                console.log('[SW v2] Sync fallido, reintentando:', item.url);
            }
        }

        // Notificar al cliente
        notifyClients({ type: 'SYNC_COMPLETE', count: items.length });

    } catch (err) {
        console.error('[SW v2] Error procesando cola:', err);
    }
}

// ── IndexedDB helpers ──
function openSyncDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('erp-sync-db', 1);
        request.onupgradeneeded = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('queue')) {
                db.createObjectStore('queue', { keyPath: 'id', autoIncrement: true });
            }
        };
        request.onsuccess = e => resolve(e.target.result);
        request.onerror = e => reject(e.target.error);
    });
}

function getAllFromStore(store) {
    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// ── Client communication ──
async function notifyClients(message) {
    const clients = await self.clients.matchAll({ type: 'window' });
    clients.forEach(client => client.postMessage(message));
}

// [!] ARCH: Escuchar mensajes del cliente
self.addEventListener('message', event => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
    if (event.data === 'getQueueCount') {
        openSyncDB().then(db => {
            const tx = db.transaction('queue', 'readonly');
            const store = tx.objectStore('queue');
            const countReq = store.count();
            countReq.onsuccess = () => {
                event.source.postMessage({ type: 'QUEUE_COUNT', count: countReq.result });
            };
        });
    }
});
