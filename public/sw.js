// Aurosoft PWA Service Worker
const CACHE_NAME = 'aurosoft-pwa-v3';
const PRECACHE_URLS = [
    '/icons/icon.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Only cache static image/icon assets. Never touch HTML pages or dynamic endpoints.
    const isCacheableAsset = /\.(png|jpg|jpeg|svg|ico|webp)$/i.test(url.pathname);

    if (!isCacheableAsset || url.pathname.includes('manifest')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(event.request);
        })
    );
});
