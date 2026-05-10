const CACHE_NAME = 'distinto-roteiros-v2';
const ASSETS = [
  '/roteiros/index.php',
  'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap',
  'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js'
];

// Install: Cache base assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

// Activate: Clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
});

// Fetch: Network First, Fallback to Cache
self.addEventListener('fetch', (event) => {
  // Only cache GET requests
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.open(CACHE_NAME).then(async (cache) => {
      try {
        // Try network first
        const networkResponse = await fetch(event.request);
        // Save clone to cache for offline use
        cache.put(event.request, networkResponse.clone());
        return networkResponse;
      } catch (err) {
        // If offline, try fetching from cache
        const cachedResponse = await cache.match(event.request);
        if (cachedResponse) {
          return cachedResponse;
        }
        // If not in cache, let the browser show its offline page or fail
        throw err;
      }
    })
  );
});
