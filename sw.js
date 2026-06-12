/**
 * LEODRI.pe V2 — Service Worker
 * Catálogo cacheado + actualizaciones silenciosas en segundo plano.
 * Al desplegar cambios, incrementa CACHE_VERSION.
 */
const CACHE_VERSION = 'leodri-v21';

const PRECACHE_URLS = [
    './',
    './index.php',
    './catalogo.php',
    './index.html',
    './data/catalogo.json',
    './offline.html',
    './manifest.webmanifest',
    './css/ficha.css',
    './js/leodri-config.js',
    './js/ficha.js',
    './js/pwa.js',
    './js/hero.js',
    './js/home-categoria.js',
    './assets/icons/icon-192.png',
    './assets/icons/icon-512.png',
    './assets/icons/apple-touch-icon.png',
    './assets/logo-leodri.svg',
    './assets/logo-leodri-oficial.png',
    './assets/icono-entrega-domicilio.png',
    './assets/demo/hero-rjn.png',
    './assets/demo/color-gry.png',
    './assets/demo/color-tan.png'
];

const CACHE_STATIC = CACHE_VERSION + '-static';
const CACHE_RUNTIME = CACHE_VERSION + '-runtime';

const STALE_WHILE_REVALIDATE = [
    /\.(?:js|css)$/i,
    /fonts\.googleapis\.com/i,
    /fonts\.gstatic\.com/i
];

const CACHE_FIRST = [
    /\.(?:png|jpg|jpeg|webp|gif|svg|ico)$/i,
    /fonts\.gstatic\.com/i
];

function esMismaOrigen(url) {
    return url.origin === self.location.origin;
}

function esNavegacion(request) {
    return request.mode === 'navigate'
        || (request.method === 'GET' && request.headers.get('accept') && request.headers.get('accept').indexOf('text/html') !== -1);
}

function coincidePatron(url, patrones) {
    return patrones.some(function (patron) {
        return patron.test(url.href);
    });
}

function esApiDinamica(url) {
    return /\/api\//i.test(url.pathname);
}

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_RUNTIME);
    const cached = await cache.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (e) {
        return cached || Response.error();
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_RUNTIME);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request).then(function (response) {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(function () {
        return null;
    });

    if (cached) {
        fetchPromise.catch(function () {});
        return cached;
    }

    const fetched = await fetchPromise;
    if (fetched) return fetched;

    return Response.error();
}

async function networkFirstConCatalogo(request) {
    const cache = await caches.open(CACHE_STATIC);

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (e) {
        const cached = await cache.match('./index.html')
            || await cache.match('./')
            || await cache.match(request);
        if (cached) return cached;

        const offline = await cache.match('./offline.html');
        if (offline) return offline;

        return Response.error();
    }
}

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then(function (cache) {
                return cache.addAll(PRECACHE_URLS);
            })
            .then(function () {
                return self.skipWaiting();
            })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(
                    keys
                        .filter(function (key) {
                            return key.indexOf('leodri-') === 0 && key.indexOf(CACHE_VERSION) !== 0;
                        })
                        .map(function (key) {
                            return caches.delete(key);
                        })
                );
            })
            .then(function () {
                return self.clients.claim();
            })
    );
});

self.addEventListener('fetch', function (event) {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (esNavegacion(request)) {
        event.respondWith(networkFirstConCatalogo(request));
        return;
    }

    if (esApiDinamica(url)) {
        event.respondWith(fetch(request));
        return;
    }

    if (!esMismaOrigen(url) && !coincidePatron(url, STALE_WHILE_REVALIDATE) && !coincidePatron(url, CACHE_FIRST)) {
        return;
    }

    if (coincidePatron(url, STALE_WHILE_REVALIDATE)) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    if (coincidePatron(url, CACHE_FIRST) || esMismaOrigen(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }
});
