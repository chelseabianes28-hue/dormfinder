// StayC service worker: makes the site installable (PWA) and shows an offline page.
// Rules:
//  - The offline page is built into this file, so it never has to be downloaded.
//  - CSS, JS, images, fonts, and CDN libraries: cache first, but ONLY if the response really is
//    that kind of file. Free hosts such as InfinityFree sometimes answer with an HTML
//    "checking your browser" page instead; that page is never saved.
//  - Pages: always from the network (they show private, changing data). No internet = offline page.
//  - API calls (api/), uploads, and form submissions (POST) are never touched.

const VERSION = 'stayc-v2';
const STATIC_CACHE = `${VERSION}-static`;

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#2D6AF0"><title>You are offline | StayC</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#F8F6F3;color:#111214;text-align:center}
.card{max-width:420px;background:#fff;border:1px solid #E6E1DB;border-radius:16px;padding:32px}
h1{font-size:1.4rem;margin:16px 0 8px}p{color:#62666D;line-height:1.5}
button{margin-top:12px;background:#2D6AF0;color:#fff;border:0;border-radius:8px;padding:10px 20px;font-size:1rem;cursor:pointer}
@media (prefers-color-scheme:dark){body{background:#101113;color:#ECEDEF}.card{background:#1B1D21;border-color:#2C2F35}p{color:#A3A8B0}}
</style></head><body><main class="card">
<svg width="72" height="72" viewBox="0 0 72 72" aria-hidden="true"><rect width="72" height="72" rx="16" fill="#2D6AF0"/><path d="M36 14 60 36H54V58H18V36H12Z" fill="#fff"/><rect x="31" y="44" width="10" height="14" rx="2" fill="#2D6AF0"/></svg>
<h1>You are offline</h1>
<p>StayC needs an internet connection to show listings and messages. Please check your connection and try again.</p>
<button type="button" onclick="location.reload()">Try again</button>
</main></body></html>`;

const offlineResponse = () =>
  new Response(OFFLINE_HTML, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });

self.addEventListener('install', () => self.skipWaiting());

// Remove caches from older versions (stayc-v1 could hold a saved "checking your browser" page).
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(VERSION)).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

const isCdn = (url) =>
  url.hostname === 'cdn.jsdelivr.net' || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';

const isOwnAsset = (url) => url.origin === self.location.origin && /\/assets\/(css|js|img)\//.test(url.pathname);

// Only save a response if it really is the file type we asked for.
const looksRight = (url, response) => {
  if (response.type === 'opaque') return isCdn(url);       // cross-site files we cannot inspect
  if (!response.ok) return false;
  const type = (response.headers.get('Content-Type') || '').toLowerCase();
  if (type.includes('text/html')) return false;             // e.g. a host security check page
  if (url.pathname.endsWith('.js')) return type.includes('javascript');
  if (url.pathname.endsWith('.css') || url.hostname === 'fonts.googleapis.com') return type.includes('css');
  return true;                                               // images and fonts
};

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.pathname.includes('/api/') || url.pathname.includes('/uploads/')) return;

  // Pages: network first, built-in offline page as a fallback.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(offlineResponse));
    return;
  }

  // Static files: cache first, then network (saving only real files).
  if (isOwnAsset(url) || isCdn(url)) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        if (looksRight(url, response)) {
          const copy = response.clone();
          caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
      }))
    );
  }
});
