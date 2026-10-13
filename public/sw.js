/*
 * PhytoQuest service worker (FR-14): plant profiles the user explicitly saves are cached
 * for offline reading. Maps and identification still need a connection.
 */
const PROFILES = 'phytoquest-saved-profiles-v1';
const ASSETS = 'phytoquest-assets-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('message', (event) => {
    if (event.data?.type !== 'save-profile') {
        return;
    }

    event.waitUntil(
        caches.open(PROFILES)
            .then((cache) => cache.add(new Request(event.data.url, { credentials: 'same-origin' })))
            .then(() => event.source?.postMessage({ type: 'profile-saved', url: event.data.url })),
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(caches.open(ASSETS).then(async (cache) => {
            const cached = await cache.match(event.request);

            if (cached) {
                return cached;
            }

            const response = await fetch(event.request);
            cache.put(event.request, response.clone());

            return response;
        }));

        return;
    }

    if (event.request.mode === 'navigate' && url.pathname.startsWith('/plants/')) {
        event.respondWith(
            fetch(event.request).catch(async () => (await caches.match(event.request, { ignoreSearch: true })) ?? Response.error()),
        );
    }
});
