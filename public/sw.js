/* Modern Forum - Service Worker */

const CACHE_NAME = 'modern-forum-v1';
const STATIC_CACHE_NAME = 'modern-forum-static-v1';
const DYNAMIC_CACHE_NAME = 'modern-forum-dynamic-v1';

// Files to cache for offline functionality
const STATIC_FILES = [
    '/',
    '/assets/css/main.css',
    '/assets/css/admin.css',
    '/assets/js/main.js',
    '/assets/js/admin.js',
    '/assets/images/logo.png',
    '/assets/images/default-avatar.png',
    '/assets/images/default-cover.jpg',
    '/manifest.json'
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
    '/api/forums',
    '/api/forum/',
    '/api/thread/',
    '/api/user/',
    '/api/search'
];

// Install event - cache static files
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Caching static files');
                return cache.addAll(STATIC_FILES);
            })
            .then(() => {
                console.log('Service Worker: Static files cached successfully');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('Service Worker: Failed to cache static files', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== STATIC_CACHE_NAME && cacheName !== DYNAMIC_CACHE_NAME) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Activated successfully');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Handle different types of requests
    if (isStaticFile(request.url)) {
        event.respondWith(handleStaticFile(request));
    } else if (isAPIRequest(request.url)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isPageRequest(request)) {
        event.respondWith(handlePageRequest(request));
    } else {
        event.respondWith(handleOtherRequest(request));
    }
});

// Handle static files (CSS, JS, images)
async function handleStaticFile(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('Service Worker: Error handling static file', error);
        return new Response('Offline - Static file not available', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// Handle API requests
async function handleAPIRequest(request) {
    try {
        // Try network first for API requests
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful API responses
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Service Worker: Network failed, trying cache for API request');
        
        // Fallback to cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline response for API
        return new Response(JSON.stringify({
            success: false,
            message: 'You are offline. Please check your internet connection.',
            offline: true
        }), {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
}

// Handle page requests
async function handlePageRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Service Worker: Network failed, trying cache for page request');
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page
        return caches.match('/offline.html') || new Response(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Offline - Modern Forum</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        margin: 0;
                        background: #f8fafc;
                        color: #1e293b;
                    }
                    .offline-container {
                        text-align: center;
                        padding: 2rem;
                        max-width: 400px;
                    }
                    .offline-icon {
                        font-size: 4rem;
                        color: #64748b;
                        margin-bottom: 1rem;
                    }
                    .offline-title {
                        font-size: 1.5rem;
                        font-weight: 600;
                        margin-bottom: 1rem;
                    }
                    .offline-message {
                        color: #64748b;
                        margin-bottom: 2rem;
                    }
                    .retry-btn {
                        background: #3b82f6;
                        color: white;
                        border: none;
                        padding: 0.75rem 1.5rem;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        font-size: 1rem;
                        transition: background-color 0.2s;
                    }
                    .retry-btn:hover {
                        background: #2563eb;
                    }
                </style>
            </head>
            <body>
                <div class="offline-container">
                    <div class="offline-icon">📡</div>
                    <h1 class="offline-title">You're Offline</h1>
                    <p class="offline-message">
                        It looks like you're not connected to the internet. 
                        Please check your connection and try again.
                    </p>
                    <button class="retry-btn" onclick="window.location.reload()">
                        Try Again
                    </button>
                </div>
            </body>
            </html>
        `, {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
                'Content-Type': 'text/html'
            }
        });
    }
}

// Handle other requests
async function handleOtherRequest(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return new Response('Offline', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// Helper functions
function isStaticFile(url) {
    return url.includes('/assets/') || 
           url.includes('.css') || 
           url.includes('.js') || 
           url.includes('.png') || 
           url.includes('.jpg') || 
           url.includes('.jpeg') || 
           url.includes('.gif') || 
           url.includes('.svg') || 
           url.includes('.woff') || 
           url.includes('.woff2');
}

function isAPIRequest(url) {
    return url.includes('/api/');
}

function isPageRequest(request) {
    return request.headers.get('accept').includes('text/html');
}

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    console.log('Service Worker: Background sync triggered', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    try {
        // Get pending actions from IndexedDB
        const pendingActions = await getPendingActions();
        
        for (const action of pendingActions) {
            try {
                await syncAction(action);
                await removePendingAction(action.id);
            } catch (error) {
                console.error('Service Worker: Failed to sync action', error);
            }
        }
    } catch (error) {
        console.error('Service Worker: Background sync failed', error);
    }
}

// Push notifications
self.addEventListener('push', (event) => {
    console.log('Service Worker: Push notification received');
    
    const options = {
        body: 'You have a new notification',
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/badge-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: '/assets/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/assets/images/xmark.png'
            }
        ]
    };
    
    if (event.data) {
        const data = event.data.json();
        options.body = data.body || options.body;
        options.title = data.title || 'Modern Forum';
        options.data = { ...options.data, ...data };
    }
    
    event.waitUntil(
        self.registration.showNotification('Modern Forum', options)
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    console.log('Service Worker: Notification clicked');
    
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    } else if (event.action === 'close') {
        // Just close the notification
        return;
    } else {
        // Default action - open the app
        event.waitUntil(
            clients.matchAll().then((clientList) => {
                if (clientList.length > 0) {
                    return clientList[0].focus();
                }
                return clients.openWindow('/');
            })
        );
    }
});

// Message handling from main thread
self.addEventListener('message', (event) => {
    console.log('Service Worker: Message received', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE_NAME)
                .then((cache) => {
                    return cache.addAll(event.data.urls);
                })
        );
    }
});

// IndexedDB helpers for offline storage
async function getPendingActions() {
    // Implementation would depend on your IndexedDB setup
    return [];
}

async function syncAction(action) {
    // Implementation would depend on your sync logic
    return fetch(action.url, action.options);
}

async function removePendingAction(id) {
    // Implementation would depend on your IndexedDB setup
    return Promise.resolve();
}

// Cache management
async function clearOldCaches() {
    const cacheNames = await caches.keys();
    const oldCaches = cacheNames.filter(name => 
        name.startsWith('modern-forum-') && 
        name !== STATIC_CACHE_NAME && 
        name !== DYNAMIC_CACHE_NAME
    );
    
    return Promise.all(
        oldCaches.map(name => caches.delete(name))
    );
}

// Periodic cache cleanup
setInterval(() => {
    clearOldCaches().then(() => {
        console.log('Service Worker: Old caches cleaned up');
    });
}, 24 * 60 * 60 * 1000); // Every 24 hours