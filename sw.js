// OPTMS Tech ERP — Service Worker
const CACHE = 'optms-erp-v2';
const BASE = '/library';
const OFFLINE_ASSETS = [
  BASE + '/',
  BASE + '/index.php',
  BASE + '/login.php',
  BASE + '/student_app.php',
  BASE + '/scan.php',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
  'https://fonts.googleapis.com/icon?family=Material+Icons+Round',
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(OFFLINE_ASSETS).catch(() => { }))
  );
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const url = e.request.url;

  // ── Skip non-GET requests entirely ──
  if (e.request.method !== 'GET') return;

  // ── Skip cross-origin except Google Fonts ──
  if (!url.startsWith(self.location.origin) && !url.includes('fonts.googleapis') && !url.includes('fonts.gstatic')) return;

  // ── API calls: network first, fallback to offline JSON ──
  if (url.includes('/api/')) {
    return e.respondWith(
      fetch(e.request).catch(() =>
        new Response(JSON.stringify({ error: 'Offline' }), {
          headers: { 'Content-Type': 'application/json' }
        })
      )
    );
  }

  // ── Everything else: cache first, then network, then fallback ──
  e.respondWith(
    caches.match(e.request).then(cached => {
      if (cached) return cached;
      return fetch(e.request).then(response => {
        // Cache successful responses
        if (response && response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE).then(c => c.put(e.request, clone));
        }
        return response;
      }).catch(() => {
        // Fallback for HTML pages
        if (e.request.headers.get('accept')?.includes('text/html')) {
          return caches.match(BASE + '/login.php');
        }
        // Fallback for everything else — return empty response instead of throwing
        return new Response('', { status: 503, statusText: 'Offline' });
      });
    })
  );
});