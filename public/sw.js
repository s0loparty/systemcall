const CACHE_NAME = 'systemcall-pwa-v1';
const STATIC_ASSETS = [
    '/favicon.ico',
    '/favicon.svg',
    '/favicon-16x16.png',
    '/favicon-32x32.png',
    '/apple-touch-icon.png',
    '/android-chrome-192x192.png',
    '/android-chrome-512x512.png',
    '/site.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)),
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => caches.delete(cacheName)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request));
        return;
    }

    if (!STATIC_ASSETS.includes(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            return cachedResponse ?? fetch(request);
        }),
    );
});
