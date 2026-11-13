// StayC service worker: makes the site installable (PWA) and shows an offline page.
// Rules:
//  - CSS, JS, icons, and CDN libraries: cache first (fast repeat visits).
//  - Pages: always from the network, because they show private, changing data.
//    If there is no internet, the offline page is shown instead.
//  - API calls (api/), uploads, and form submissions (POST) are never cached.

const VERSION = 'stayc-v1';
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = 'offline.html';

const PRECACHE = [
  OFFLINE_URL,
  'assets/css/style.css',
  'assets/css/theme.css',
  'assets/js/main.js',
  'assets/img/icons/icon-192.png',
  'assets/img/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)));
  self.skipWaiting();
});

// Remove caches from older versions.
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(VERSION)).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

const isStaticAsset = (url) =>
  /\/assets\/(css|js|img)\//.test(url.pathname)
  || url.hostname === 'cdn.jsdelivr.net'
  || url.hostname === 'fonts.googleapis.com'
  || url.hostname === 'fonts.gstatic.com';

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.pathname.includes('/api/') || url.pathname.includes('/uploads/')) return;

  // Pages: network first, offline page as a fallback.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  // Static files: cache first, then network (and save the copy).
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        if (response.ok || response.type === 'opaque') {
          const copy = response.clone();
          caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
      }))
    );
  }
});
