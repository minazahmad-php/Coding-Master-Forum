// Service Worker for Universal Forum Hub
const CACHE_NAME = 'forum-hub-v1';
const STATIC_CACHE_NAME = 'forum-hub-static-v1';
const DYNAMIC_CACHE_NAME = 'forum-hub-dynamic-v1';

// Files to cache for offline functionality
const STATIC_FILES = [
    '/',
    '/assets/css/main.css',
    '/assets/css/search.css',
    '/assets/css/analytics.css',
    '/assets/js/main.js',
    '/assets/js/search.js',
    '/assets/js/pwa.js',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png',
    '/manifest.json'
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
    /^\/api\/forums/,
    /^\/api\/thread/,
    /^\/api\/user/,
    /^\/search\/suggestions/
];

// Install event - cache static files
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then(cache => {
                console.log('Caching static files...');
                return cache.addAll(STATIC_FILES);
            })
            .then(() => {
                console.log('Static files cached successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Error caching static files:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE_NAME && 
                            cacheName !== DYNAMIC_CACHE_NAME) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip external requests
    if (url.origin !== location.origin) {
        return;
    }
    
    // Handle different types of requests
    if (isStaticFile(request)) {
        event.respondWith(handleStaticFile(request));
    } else if (isApiRequest(request)) {
        event.respondWith(handleApiRequest(request));
    } else if (isPageRequest(request)) {
        event.respondWith(handlePageRequest(request));
    } else {
        event.respondWith(handleOtherRequest(request));
    }
});

// Check if request is for a static file
function isStaticFile(request) {
    const url = new URL(request.url);
    return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/);
}

// Check if request is for API
function isApiRequest(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/api/') || 
           url.pathname.startsWith('/search/suggestions');
}

// Check if request is for a page
function isPageRequest(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/') && 
           !url.pathname.includes('.') &&
           request.headers.get('accept').includes('text/html');
}

// Handle static file requests
function handleStaticFile(request) {
    return caches.match(request)
        .then(response => {
            if (response) {
                return response;
            }
            
            return fetch(request)
                .then(response => {
                    if (response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(STATIC_CACHE_NAME)
                            .then(cache => {
                                cache.put(request, responseClone);
                            });
                    }
                    return response;
                });
        });
}

// Handle API requests
function handleApiRequest(request) {
    return caches.open(DYNAMIC_CACHE_NAME)
        .then(cache => {
            return cache.match(request)
                .then(response => {
                    if (response) {
                        // Return cached response and update in background
                        fetch(request)
                            .then(fetchResponse => {
                                if (fetchResponse.status === 200) {
                                    cache.put(request, fetchResponse.clone());
                                }
                            })
                            .catch(() => {
                                // Ignore fetch errors for background updates
                            });
                        
                        return response;
                    }
                    
                    // No cached response, fetch from network
                    return fetch(request)
                        .then(fetchResponse => {
                            if (fetchResponse.status === 200) {
                                cache.put(request, fetchResponse.clone());
                            }
                            return fetchResponse;
                        })
                        .catch(() => {
                            // Return offline response for API requests
                            return new Response(
                                JSON.stringify({
                                    error: 'Offline',
                                    message: 'You are currently offline. Please check your connection.'
                                }),
                                {
                                    status: 503,
                                    headers: { 'Content-Type': 'application/json' }
                                }
                            );
                        });
                });
        });
}

// Handle page requests
function handlePageRequest(request) {
    return caches.match(request)
        .then(response => {
            if (response) {
                return response;
            }
            
            return fetch(request)
                .then(response => {
                    if (response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(DYNAMIC_CACHE_NAME)
                            .then(cache => {
                                cache.put(request, responseClone);
                            });
                    }
                    return response;
                })
                .catch(() => {
                    // Return offline page
                    return caches.match('/offline.html')
                        .then(offlineResponse => {
                            if (offlineResponse) {
                                return offlineResponse;
                            }
                            
                            // Create a simple offline page
                            return new Response(
                                `
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Offline - ${SITE_NAME}</title>
                                    <meta name="viewport" content="width=device-width, initial-scale=1">
                                    <style>
                                        body { 
                                            font-family: -apple-system, BlinkMacSystemFont, sans-serif; 
                                            text-align: center; 
                                            padding: 2rem; 
                                            background: #f8f9fa;
                                        }
                                        .offline-container {
                                            max-width: 400px;
                                            margin: 0 auto;
                                            background: white;
                                            padding: 2rem;
                                            border-radius: 10px;
                                            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                                        }
                                        .offline-icon {
                                            font-size: 4rem;
                                            margin-bottom: 1rem;
                                            color: #667eea;
                                        }
                                        h1 { color: #333; margin-bottom: 1rem; }
                                        p { color: #666; margin-bottom: 2rem; }
                                        .retry-btn {
                                            background: #667eea;
                                            color: white;
                                            border: none;
                                            padding: 0.75rem 1.5rem;
                                            border-radius: 5px;
                                            cursor: pointer;
                                            font-size: 1rem;
                                        }
                                        .retry-btn:hover {
                                            background: #5a6fd8;
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="offline-container">
                                        <div class="offline-icon">📡</div>
                                        <h1>You're Offline</h1>
                                        <p>It looks like you're not connected to the internet. Please check your connection and try again.</p>
                                        <button class="retry-btn" onclick="window.location.reload()">Try Again</button>
                                    </div>
                                </body>
                                </html>
                                `,
                                {
                                    status: 200,
                                    headers: { 'Content-Type': 'text/html' }
                                }
                            );
                        });
                });
        });
}

// Handle other requests
function handleOtherRequest(request) {
    return fetch(request)
        .catch(() => {
            // Return a generic offline response
            return new Response('Offline', { status: 503 });
        });
}

// Background sync for offline actions
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // Sync offline actions when connection is restored
    return new Promise((resolve) => {
        // Get offline actions from IndexedDB
        getOfflineActions()
            .then(actions => {
                return Promise.all(
                    actions.map(action => {
                        return fetch(action.url, {
                            method: action.method,
                            headers: action.headers,
                            body: action.body
                        })
                        .then(response => {
                            if (response.ok) {
                                removeOfflineAction(action.id);
                            }
                            return response;
                        })
                        .catch(error => {
                            console.error('Background sync failed:', error);
                        });
                    })
                );
            })
            .then(() => {
                resolve();
            });
    });
}

// Push notifications
self.addEventListener('push', event => {
    console.log('Push notification received:', event);
    
    const options = {
        body: event.data ? event.data.text() : 'New notification from Forum Hub',
        icon: '/assets/icons/icon-192x192.png',
        badge: '/assets/icons/badge-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            url: '/notifications'
        },
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/assets/icons/view-24x24.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/assets/icons/dismiss-24x24.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('Forum Hub', options)
    );
});

// Notification click
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url || '/')
        );
    }
});

// Message handling
self.addEventListener('message', event => {
    console.log('Service Worker received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE_NAME)
                .then(cache => {
                    return cache.addAll(event.data.urls);
                })
        );
    }
});

// Utility functions for offline storage
function getOfflineActions() {
    return new Promise((resolve) => {
        // This would typically use IndexedDB
        // For now, return empty array
        resolve([]);
    });
}

function removeOfflineAction(id) {
    // This would typically remove from IndexedDB
    console.log('Removing offline action:', id);
}

// Cache management
function clearOldCaches() {
    return caches.keys()
        .then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== STATIC_CACHE_NAME && 
                        cacheName !== DYNAMIC_CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        });
}

// Periodic cache cleanup
setInterval(() => {
    clearOldCaches();
}, 24 * 60 * 60 * 1000); // Every 24 hours