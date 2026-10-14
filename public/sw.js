// public/sw.js - Service Worker PWA Universitas Harkat Negeri
const CACHE_NAME = 'uhn-taskforce-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Strategi Network First dengan fallback cache
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});
