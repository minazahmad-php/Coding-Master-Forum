// PWA (Progressive Web App) functionality
class PWAManager {
    constructor() {
        this.isInstalled = false;
        this.deferredPrompt = null;
        this.isOnline = navigator.onLine;
        
        this.init();
    }
    
    init() {
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupOnlineOfflineHandlers();
        this.setupNotificationPermission();
        this.setupBackgroundSync();
        this.setupShareTarget();
        this.setupFileHandlers();
    }
    
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registered successfully:', registration);
                
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
                console.error('Service Worker registration failed:', error);
            }
        }
    }
    
    setupInstallPrompt() {
        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt triggered');
            
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            
            // Stash the event so it can be triggered later
            this.deferredPrompt = e;
            
            // Show install button
            this.showInstallButton();
        });
        
        // Listen for the appinstalled event
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            this.isInstalled = true;
            this.hideInstallButton();
            this.deferredPrompt = null;
            
            // Track installation
            this.trackInstallation();
        });
    }
    
    showInstallButton() {
        let installButton = document.getElementById('pwa-install-button');
        
        if (!installButton) {
            installButton = document.createElement('button');
            installButton.id = 'pwa-install-button';
            installButton.className = 'pwa-install-button';
            installButton.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
                Install App
            `;
            
            // Add to header or create floating button
            const header = document.querySelector('header') || document.body;
            header.appendChild(installButton);
        }
        
        installButton.style.display = 'block';
        installButton.addEventListener('click', () => this.installApp());
    }
    
    hideInstallButton() {
        const installButton = document.getElementById('pwa-install-button');
        if (installButton) {
            installButton.style.display = 'none';
        }
    }
    
    async installApp() {
        if (!this.deferredPrompt) {
            return;
        }
        
        // Show the install prompt
        this.deferredPrompt.prompt();
        
        // Wait for the user to respond to the prompt
        const { outcome } = await this.deferredPrompt.userChoice;
        
        console.log(`User response to the install prompt: ${outcome}`);
        
        // Clear the deferredPrompt
        this.deferredPrompt = null;
        
        // Hide the install button
        this.hideInstallButton();
    }
    
    setupOnlineOfflineHandlers() {
        window.addEventListener('online', () => {
            console.log('App is online');
            this.isOnline = true;
            this.showOnlineStatus();
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            console.log('App is offline');
            this.isOnline = false;
            this.showOfflineStatus();
        });
        
        // Initial status
        if (this.isOnline) {
            this.showOnlineStatus();
        } else {
            this.showOfflineStatus();
        }
    }
    
    showOnlineStatus() {
        this.showStatusMessage('You are back online!', 'success');
    }
    
    showOfflineStatus() {
        this.showStatusMessage('You are offline. Some features may be limited.', 'warning');
    }
    
    showStatusMessage(message, type) {
        // Remove existing status message
        const existingMessage = document.getElementById('pwa-status-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new status message
        const statusMessage = document.createElement('div');
        statusMessage.id = 'pwa-status-message';
        statusMessage.className = `pwa-status-message pwa-status-${type}`;
        statusMessage.textContent = message;
        
        // Add to page
        document.body.appendChild(statusMessage);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (statusMessage.parentNode) {
                statusMessage.remove();
            }
        }, 3000);
    }
    
    async setupNotificationPermission() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('Notification permission granted');
                this.subscribeToPushNotifications();
            } else {
                console.log('Notification permission denied');
            }
        }
    }
    
    async subscribeToPushNotifications() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                const registration = await navigator.serviceWorker.ready;
                
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                });
                
                // Send subscription to server
                await fetch('/api/push/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(subscription)
                });
                
                console.log('Push subscription successful');
                
            } catch (error) {
                console.error('Push subscription failed:', error);
            }
        }
    }
    
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
    
    setupBackgroundSync() {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            // Register background sync for offline actions
            navigator.serviceWorker.ready.then(registration => {
                // This would be called when user performs actions while offline
                window.addEventListener('beforeunload', () => {
                    registration.sync.register('background-sync');
                });
            });
        }
    }
    
    setupShareTarget() {
        // Handle shared content
        if (navigator.share) {
            // Add share buttons to relevant content
            this.addShareButtons();
        }
    }
    
    addShareButtons() {
        const shareButtons = document.querySelectorAll('[data-share]');
        
        shareButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                
                const shareData = {
                    title: button.dataset.shareTitle || document.title,
                    text: button.dataset.shareText || '',
                    url: button.dataset.shareUrl || window.location.href
                };
                
                try {
                    await navigator.share(shareData);
                    console.log('Content shared successfully');
                } catch (error) {
                    console.log('Error sharing:', error);
                }
            });
        });
    }
    
    setupFileHandlers() {
        // Handle file drops
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
        });
        
        document.addEventListener('drop', (e) => {
            e.preventDefault();
            
            const files = Array.from(e.dataTransfer.files);
            this.handleFiles(files);
        });
        
        // Handle file input
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                this.handleFiles(files);
            });
        });
    }
    
    handleFiles(files) {
        files.forEach(file => {
            if (this.isValidFileType(file)) {
                this.uploadFile(file);
            } else {
                this.showErrorMessage(`File type ${file.type} is not supported`);
            }
        });
    }
    
    isValidFileType(file) {
        const validTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'application/pdf'
        ];
        
        return validTypes.includes(file.type);
    }
    
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        try {
            const response = await fetch('/api/upload', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.json();
                console.log('File uploaded successfully:', result);
                this.showSuccessMessage('File uploaded successfully');
            } else {
                throw new Error('Upload failed');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showErrorMessage('Upload failed. Please try again.');
        }
    }
    
    showUpdateNotification() {
        const updateNotification = document.createElement('div');
        updateNotification.className = 'pwa-update-notification';
        updateNotification.innerHTML = `
            <div class="update-content">
                <h3>Update Available</h3>
                <p>A new version of the app is available.</p>
                <div class="update-actions">
                    <button onclick="this.updateApp()" class="update-btn">Update</button>
                    <button onclick="this.dismissUpdate()" class="dismiss-btn">Later</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(updateNotification);
        
        // Add event listeners
        updateNotification.querySelector('.update-btn').addEventListener('click', () => {
            this.updateApp();
        });
        
        updateNotification.querySelector('.dismiss-btn').addEventListener('click', () => {
            this.dismissUpdate();
        });
    }
    
    updateApp() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistration().then(registration => {
                if (registration && registration.waiting) {
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                    window.location.reload();
                }
            });
        }
    }
    
    dismissUpdate() {
        const updateNotification = document.querySelector('.pwa-update-notification');
        if (updateNotification) {
            updateNotification.remove();
        }
    }
    
    async syncOfflineData() {
        // Sync any offline data when connection is restored
        console.log('Syncing offline data...');
        
        // This would typically sync data from IndexedDB
        // For now, just log the action
    }
    
    trackInstallation() {
        // Track PWA installation
        if (typeof gtag !== 'undefined') {
            gtag('event', 'pwa_install', {
                event_category: 'PWA',
                event_label: 'App Installation'
            });
        }
    }
    
    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }
    
    showErrorMessage(message) {
        this.showToast(message, 'error');
    }
    
    showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `pwa-toast pwa-toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
    
    // Utility methods
    isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true;
    }
    
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    getDeviceInfo() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            isOnline: this.isOnline,
            isStandalone: this.isStandalone(),
            isMobile: this.isMobile()
        };
    }
}

// Initialize PWA when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
});

// Export for global use
window.PWAManager = PWAManager;