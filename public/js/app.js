/**
 * Universal Forum Hub - Modern JavaScript Framework
 */

class UniversalForum {
    constructor() {
        this.init();
        this.setupEventListeners();
    }

    init() {
        this.config = {
            apiUrl: '/api',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            userId: document.querySelector('meta[name="user-id"]')?.getAttribute('content'),
            theme: localStorage.getItem('theme') || 'light'
        };

        this.state = {
            isOnline: navigator.onLine,
            notifications: [],
            unreadCount: 0,
            theme: this.config.theme
        };

        this.setupTheme();
    }

    setupEventListeners() {
        // Theme toggle
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-theme-toggle]')) {
                this.toggleTheme();
            }
        });

        // Like/Unlike buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-like-btn]')) {
                this.handleLike(e.target);
            }
        });

        // Follow/Unfollow buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-follow-btn]')) {
                this.handleFollow(e.target);
            }
        });

        // Online status
        window.addEventListener('online', () => {
            this.state.isOnline = true;
            this.showNotification('You are back online', 'success');
        });

        window.addEventListener('offline', () => {
            this.state.isOnline = false;
            this.showNotification('You are offline', 'warning');
        });
    }

    setupTheme() {
        document.documentElement.setAttribute('data-theme', this.state.theme);
        this.updateThemeIcon();
    }

    toggleTheme() {
        this.state.theme = this.state.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.state.theme);
        this.setupTheme();
    }

    updateThemeIcon() {
        const icon = document.querySelector('[data-theme-icon]');
        if (icon) {
            icon.textContent = this.state.theme === 'light' ? '🌙' : '☀️';
        }
    }

    async handleLike(button) {
        const postId = button.dataset.postId;
        const isLiked = button.classList.contains('liked');

        try {
            const response = await this.apiCall(`/post/${postId}/${isLiked ? 'unlike' : 'like'}`, {
                method: 'POST'
            });

            if (response.success) {
                button.classList.toggle('liked');
                button.querySelector('.like-count').textContent = response.data.likes;
            }
        } catch (error) {
            this.showNotification('Failed to update like', 'error');
        }
    }

    async handleFollow(button) {
        const userId = button.dataset.userId;
        const isFollowing = button.classList.contains('following');

        try {
            const response = await this.apiCall(`/user/${userId}/${isFollowing ? 'unfollow' : 'follow'}`, {
                method: 'POST'
            });

            if (response.success) {
                button.classList.toggle('following');
                button.textContent = isFollowing ? 'Follow' : 'Following';
            }
        } catch (error) {
            this.showNotification('Failed to update follow status', 'error');
        }
    }

    async apiCall(endpoint, options = {}) {
        const url = this.config.apiUrl + endpoint;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.config.csrfToken
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`API call failed: ${response.statusText}`);
        }

        return await response.json();
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize the forum when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.forum = new UniversalForum();
});