// ============================================================
//  Service Worker — UTC Chatbot PWA
//  Estrategia: Cache-First para estáticos, Network-First para API
// ============================================================

const CACHE_NAME = 'utc-chatbot-v1';
const STATIC_ASSETS = [
  '/index.php',
  '/styles.css',
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/utc.jpg',
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// ─── INSTALL: precache static assets ───────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Precaching static assets');
      return cache.addAll(STATIC_ASSETS);
    }).catch((err) => {
      console.warn('[SW] Precache failed for some assets:', err);
    })
  );
  self.skipWaiting();
});

// ─── ACTIVATE: limpiar versiones antiguas ──────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      )
    )
  );
  self.clients.claim();
});

// ─── FETCH: estrategia de caché ────────────────────────────
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // API calls → Network-First (sin caché)
  if (url.pathname.includes('api.php')) {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(
          JSON.stringify({ error: 'Sin conexión. Verifica tu red e intenta de nuevo.' }),
          {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
          }
        )
      )
    );
    return;
  }

  // Recursos estáticos → Cache-First
  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(request)
        .then((networkResponse) => {
          // Guardar en caché si la respuesta es válida
          if (
            networkResponse &&
            networkResponse.status === 200 &&
            networkResponse.type !== 'opaque'
          ) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Fallback: página offline
          if (request.destination === 'document') {
            return caches.match('/index.php');
          }
        });
    })
  );
});

// ─── PUSH NOTIFICATIONS (base para futuro) ─────────────────
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'UTC Chatbot';
  const options = {
    body: data.body || 'Tienes un nuevo mensaje.',
    icon: '/dialogflow/icons/icon-192.png',
    badge: '/dialogflow/icons/icon-96.png',
    vibrate: [200, 100, 200],
    data: { url: '/index.php' }
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url || '/index.php')
  );
});
