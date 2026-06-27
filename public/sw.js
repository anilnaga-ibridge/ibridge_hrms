const CACHE_NAME = 'hrm-enterprise-tasks-v1';
const BASE_PATH = self.location.pathname.replace('/sw.js', '');
const ASSETS_TO_CACHE = [
    BASE_PATH + '/',
    BASE_PATH + '/css/app.css',
    BASE_PATH + '/js/app.js',
    BASE_PATH + '/offline'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    if (event.request.method === 'GET' && url.pathname.includes('/api/v1/enterprise-tasks/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request);
                })
        );
    } else {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match(event.request);
            })
        );
    }
});
