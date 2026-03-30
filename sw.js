// OPTMS Tech ERP — Service Worker
const CACHE = 'optms-erp-v1';
const OFFLINE_ASSETS = [
  '/',
  '/index.php',
  '/login.php',
  'https://fonts.googleapis.com/icon?family=Material+Icons+Round',
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(OFFLINE_ASSETS).catch(() => {}))
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
  // Network first for API calls
  if (e.request.url.includes('/api/')) {
    return e.respondWith(
      fetch(e.request).catch(() =>
        new Response(JSON.stringify({ error: 'Offline' }), { headers: { 'Content-Type': 'application/json' } })
      )
    );
  }
  // Cache first for assets
  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request))
  );
});
