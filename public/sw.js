const VERSION = '1.0.0';
const CACHE_NAME = `dukaflow-cache-v${VERSION}`;
const PRE_CACHE_ASSETS = [
  '/offline.html',
  '/manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(PRE_CACHE_ASSETS);
    })
  );
  // Do NOT skipWaiting here, allow Update Manager to coordinate skipped waits
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.map(key => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      })
    )).then(() => {
      // Notify all clients to reload and display updated notification
      return self.clients.matchAll({ type: 'window' }).then(clients => {
        clients.forEach(client => {
          client.postMessage({ type: 'VERSION_ACTIVATED', version: VERSION });
        });
      });
    })
  );
  self.clients.claim();
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', event => {
  const req = event.request;
  
  // Only handle GET requests
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);

  // 1. Navigation requests (HTML pages) - Network-first with fallback to offline.html
  if (req.mode === 'navigate' || (req.headers.get('accept') && req.headers.get('accept').includes('text/html'))) {
    event.respondWith(
      fetch(req)
        .then(response => {
          // Clone and cache the successfully fetched page for offline loading
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(req, clone));
          return response;
        })
        .catch(async () => {
          // If network failed, try to load from cache
          const cachedResponse = await caches.match(req);
          if (cachedResponse) {
             return cachedResponse;
          }
          // If no cached version, serve the standard static offline fallback page
          return caches.match('/offline.html');
        })
    );
    return;
  }

  // 2. Static assets & CDN assets - Cache-first with network fallback
  const isStaticAsset = url.pathname.includes('/build/assets/') || 
                         url.pathname.includes('/icons/') ||
                         url.host.includes('cdnjs.cloudflare.com') ||
                         url.host.includes('cdn.jsdelivr.net') ||
                         url.host.includes('unpkg.com');

  if (isStaticAsset) {
    event.respondWith(
      caches.match(req).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(req).then(response => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(req, clone));
          }
          return response;
        });
      })
    );
    return;
  }

  // 3. General requests - Network first
  event.respondWith(
    fetch(req).catch(() => caches.match(req))
  );
});
