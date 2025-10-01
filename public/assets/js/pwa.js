/* Modern Forum - PWA JavaScript */

class PWA {
    constructor() {
        this.isOnline = navigator.onLine;
        this.deferredPrompt = null;
        this.init();
    }

    init() {
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupOnlineOfflineHandlers();
        this.setupPushNotifications();
        this.setupBackgroundSync();
    }

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('PWA: Service Worker registered successfully', registration);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateNotification();
                        }
                    });
                });
                
            } catch (error) {
                console.error('PWA: Service Worker registration failed', error);
            }
        }
    }

    setupInstallPrompt() {
        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: Install prompt triggered');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });

        // Listen for the appinstalled event
        window.addEventListener('appinstalled', () => {
            console.log('PWA: App installed successfully');
            this.hideInstallButton();
            this.deferredPrompt = null;
            this.showNotification('App installed successfully!', 'success');
        });
    }

    showInstallButton() {
        let installBtn = document.querySelector('.install-btn');
        if (!installBtn) {
            installBtn = document.createElement('button');
            installBtn.className = 'install-btn btn btn-primary';
            installBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
            installBtn.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                box-shadow: var(--shadow-lg);
            `;
            
            installBtn.addEventListener('click', () => {
                this.installApp();
            });
            
            document.body.appendChild(installBtn);
        }
        
        installBtn.style.display = 'block';
    }

    hideInstallButton() {
        const installBtn = document.querySelector('.install-btn');
        if (installBtn) {
            installBtn.style.display = 'none';
        }
    }

    async installApp() {
        if (!this.deferredPrompt) {
            return;
        }

        try {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log('PWA: Install prompt outcome', outcome);
            
            if (outcome === 'accepted') {
                this.showNotification('Installing app...', 'info');
            } else {
                this.showNotification('App installation cancelled', 'warning');
            }
            
            this.deferredPrompt = null;
            this.hideInstallButton();
        } catch (error) {
            console.error('PWA: Install prompt failed', error);
            this.showNotification('Failed to install app', 'error');
        }
    }

    showUpdateNotification() {
        const updateNotification = document.createElement('div');
        updateNotification.className = 'update-notification';
        updateNotification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: var(--text-white);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        `;
        
        updateNotification.innerHTML = `
            <i class="fas fa-sync-alt"></i>
            <span>New version available!</span>
            <button class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none;">
                Update
            </button>
            <button class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(updateNotification);
        
        // Update button
        updateNotification.querySelector('button:first-of-type').addEventListener('click', () => {
            this.updateApp();
        });
        
        // Close button
        updateNotification.querySelector('button:last-of-type').addEventListener('click', () => {
            updateNotification.remove();
        });
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (updateNotification.parentNode) {
                updateNotification.remove();
            }
        }, 10000);
    }

    async updateApp() {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration && registration.waiting) {
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                window.location.reload();
            }
        }
    }

    setupOnlineOfflineHandlers() {
        window.addEventListener('online', () => {
            console.log('PWA: Back online');
            this.isOnline = true;
            this.showNotification('You are back online!', 'success');
            this.syncOfflineData();
        });

        window.addEventListener('offline', () => {
            console.log('PWA: Gone offline');
            this.isOnline = false;
            this.showNotification('You are offline. Some features may be limited.', 'warning');
        });

        // Show initial online/offline status
        if (!this.isOnline) {
            this.showOfflineIndicator();
        }
    }

    showOfflineIndicator() {
        let indicator = document.querySelector('.offline-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'offline-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: var(--warning-color);
                color: var(--text-white);
                padding: var(--spacing-sm);
                text-align: center;
                font-size: var(--text-sm);
                z-index: 10000;
            `;
            indicator.innerHTML = '<i class="fas fa-wifi"></i> You are offline';
            document.body.appendChild(indicator);
        }
        
        indicator.style.display = this.isOnline ? 'none' : 'block';
    }

    async syncOfflineData() {
        try {
            // Trigger background sync
            if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('background-sync');
            }
            
            // Sync any pending actions
            await this.syncPendingActions();
        } catch (error) {
            console.error('PWA: Sync failed', error);
        }
    }

    async syncPendingActions() {
        // Implementation would depend on your offline storage strategy
        console.log('PWA: Syncing pending actions...');
    }

    async setupPushNotifications() {
        if (!('Notification' in window)) {
            console.log('PWA: Notifications not supported');
            return;
        }

        if (Notification.permission === 'granted') {
            console.log('PWA: Notifications already granted');
            return;
        }

        if (Notification.permission !== 'denied') {
            try {
                const permission = await Notification.requestPermission();
                console.log('PWA: Notification permission', permission);
                
                if (permission === 'granted') {
                    this.subscribeToPush();
                }
            } catch (error) {
                console.error('PWA: Failed to request notification permission', error);
            }
        }
    }

    async subscribeToPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.getVapidPublicKey())
            });
            
            console.log('PWA: Push subscription created', subscription);
            
            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
        } catch (error) {
            console.error('PWA: Push subscription failed', error);
        }
    }

    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(subscription)
            });
            
            if (response.ok) {
                console.log('PWA: Subscription sent to server');
            }
        } catch (error) {
            console.error('PWA: Failed to send subscription to server', error);
        }
    }

    setupBackgroundSync() {
        // Register for background sync
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            navigator.serviceWorker.ready.then((registration) => {
                // Background sync will be handled by the service worker
                console.log('PWA: Background sync ready');
            });
        }
    }

    // Utility methods
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    getVapidPublicKey() {
        // This should be your VAPID public key
        return 'YOUR_VAPID_PUBLIC_KEY';
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    showNotification(message, type = 'info') {
        if (window.modernForum && window.modernForum.showNotification) {
            window.modernForum.showNotification(message, type);
        } else {
            console.log('PWA Notification:', message);
        }
    }

    // Public methods for external use
    async install() {
        return this.installApp();
    }

    async update() {
        return this.updateApp();
    }

    isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true;
    }

    getInstallPrompt() {
        return this.deferredPrompt;
    }
}

// Initialize PWA when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pwa = new PWA();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PWA;
}