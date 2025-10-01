/* Modern Forum - Main JavaScript */

class ModernForum {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupThemeToggle();
        this.setupNotifications();
        this.setupSearch();
        this.setupLazyLoading();
        this.setupInfiniteScroll();
    }

    setupEventListeners() {
        // Global click handlers
        document.addEventListener('click', (e) => {
            this.handleGlobalClick(e);
        });

        // Form submissions
        document.addEventListener('submit', (e) => {
            this.handleFormSubmit(e);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });

        // Window events
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));

        window.addEventListener('scroll', this.throttle(() => {
            this.handleScroll();
        }, 100));
    }

    initializeComponents() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize modals
        this.initModals();
        
        // Initialize dropdowns
        this.initDropdowns();
        
        // Initialize tabs
        this.initTabs();
        
        // Initialize accordions
        this.initAccordions();
        
        // Initialize carousels
        this.initCarousels();
    }

    setupThemeToggle() {
        const themeToggle = document.querySelector('[data-theme-toggle]');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                this.toggleTheme();
            });
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        this.setTheme(savedTheme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update theme toggle button
        const themeToggle = document.querySelector('[data-theme-toggle]');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
    }

    setupNotifications() {
        // Create notification container
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notification-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-sm);
            box-shadow: var(--shadow-lg);
            transform: translateX(100%);
            transition: transform var(--transition-normal);
        `;

        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="${icon}" style="color: var(--${type}-color);"></i>
                <span style="flex: 1;">${message}</span>
                <button class="notification-close" style="background: none; border: none; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.removeNotification(notification);
        });
    }

    removeNotification(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    setupSearch() {
        const searchInput = document.querySelector('[data-search-input]');
        if (searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        this.performSearch(query);
                    }, 300);
                } else {
                    this.hideSearchResults();
                }
            });

            // Handle search form submission
            const searchForm = document.querySelector('[data-search-form]');
            if (searchForm) {
                searchForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const query = searchInput.value.trim();
                    if (query) {
                        window.location.href = `/search?q=${encodeURIComponent(query)}`;
                    }
                });
            }
        }
    }

    async performSearch(query) {
        try {
            const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.success) {
                this.showSearchResults(data.results);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    showSearchResults(results) {
        let dropdown = document.querySelector('.search-dropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'search-dropdown';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--bg-primary);
                border: 1px solid var(--border-color);
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
            `;
            
            const searchContainer = document.querySelector('[data-search-container]');
            if (searchContainer) {
                searchContainer.style.position = 'relative';
                searchContainer.appendChild(dropdown);
            }
        }

        if (results.length === 0) {
            dropdown.innerHTML = '<div style="padding: var(--spacing-md); color: var(--text-muted);">No results found</div>';
        } else {
            dropdown.innerHTML = results.map(result => `
                <a href="${result.url}" style="display: block; padding: var(--spacing-sm) var(--spacing-md); text-decoration: none; color: var(--text-primary); border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 500;">${result.title}</div>
                    <div style="font-size: var(--text-sm); color: var(--text-muted);">${result.excerpt}</div>
                </a>
            `).join('');
        }

        dropdown.style.display = 'block';
    }

    hideSearchResults() {
        const dropdown = document.querySelector('.search-dropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }

    setupLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        if (images.length === 0) return;

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            imageObserver.observe(img);
        });
    }

    setupInfiniteScroll() {
        const loadMoreBtn = document.querySelector('[data-load-more]');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadMoreContent();
            });
        }

        // Auto-load on scroll
        const infiniteScrollContainer = document.querySelector('[data-infinite-scroll]');
        if (infiniteScrollContainer) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadMoreContent();
                    }
                });
            });

            observer.observe(infiniteScrollContainer);
        }
    }

    async loadMoreContent() {
        const container = document.querySelector('[data-content-container]');
        const loadMoreBtn = document.querySelector('[data-load-more]');
        
        if (!container || !loadMoreBtn) return;

        const currentPage = parseInt(loadMoreBtn.dataset.page) || 1;
        const nextPage = currentPage + 1;
        const url = loadMoreBtn.dataset.url;

        try {
            loadMoreBtn.disabled = true;
            loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            const response = await fetch(`${url}?page=${nextPage}`);
            const data = await response.json();

            if (data.success && data.html) {
                container.insertAdjacentHTML('beforeend', data.html);
                loadMoreBtn.dataset.page = nextPage;

                if (!data.hasMore) {
                    loadMoreBtn.style.display = 'none';
                }
            } else {
                this.showNotification('Failed to load more content', 'error');
            }
        } catch (error) {
            console.error('Load more error:', error);
            this.showNotification('Failed to load more content', 'error');
        } finally {
            loadMoreBtn.disabled = false;
            loadMoreBtn.innerHTML = 'Load More';
        }
    }

    initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });

            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--bg-darker);
            color: var(--text-white);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: var(--text-xs);
            z-index: 10000;
            pointer-events: none;
            opacity: 0;
            transition: opacity var(--transition-fast);
        `;

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';

        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);

        element._tooltip = tooltip;
    }

    hideTooltip() {
        const tooltips = document.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => {
            tooltip.style.opacity = '0';
            setTimeout(() => {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, 150);
        });
    }

    initModals() {
        const modalTriggers = document.querySelectorAll('[data-modal-trigger]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modalTrigger;
                this.openModal(modalId);
            });
        });

        const modalCloses = document.querySelectorAll('[data-modal-close]');
        modalCloses.forEach(close => {
            close.addEventListener('click', () => {
                this.closeModal();
            });
        });

        // Close modal on overlay click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const overlay = modal.querySelector('.modal-overlay') || modal;
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    initDropdowns() {
        const dropdownTriggers = document.querySelectorAll('[data-dropdown-trigger]');
        dropdownTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown(trigger);
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[data-dropdown-trigger]') && !e.target.closest('[data-dropdown-menu]')) {
                this.closeAllDropdowns();
            }
        });
    }

    toggleDropdown(trigger) {
        const dropdown = trigger.nextElementSibling;
        const isOpen = dropdown && dropdown.classList.contains('active');

        this.closeAllDropdowns();

        if (!isOpen && dropdown) {
            dropdown.classList.add('active');
            trigger.classList.add('active');
        }
    }

    closeAllDropdowns() {
        const activeDropdowns = document.querySelectorAll('[data-dropdown-menu].active');
        const activeTriggers = document.querySelectorAll('[data-dropdown-trigger].active');

        activeDropdowns.forEach(dropdown => dropdown.classList.remove('active'));
        activeTriggers.forEach(trigger => trigger.classList.remove('active'));
    }

    initTabs() {
        const tabContainers = document.querySelectorAll('[data-tabs]');
        tabContainers.forEach(container => {
            const tabs = container.querySelectorAll('[data-tab-trigger]');
            const contents = container.querySelectorAll('[data-tab-content]');

            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = tab.dataset.tabTrigger;

                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    const targetContent = container.querySelector(`[data-tab-content="${targetId}"]`);
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }
                });
            });
        });
    }

    initAccordions() {
        const accordionTriggers = document.querySelectorAll('[data-accordion-trigger]');
        accordionTriggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const accordion = trigger.closest('[data-accordion]');
                const content = accordion.querySelector('[data-accordion-content]');
                const isOpen = accordion.classList.contains('active');

                if (isOpen) {
                    accordion.classList.remove('active');
                    content.style.maxHeight = '0';
                } else {
                    accordion.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
    }

    initCarousels() {
        const carousels = document.querySelectorAll('[data-carousel]');
        carousels.forEach(carousel => {
            const slides = carousel.querySelectorAll('[data-carousel-slide]');
            const prevBtn = carousel.querySelector('[data-carousel-prev]');
            const nextBtn = carousel.querySelector('[data-carousel-next]');
            const indicators = carousel.querySelectorAll('[data-carousel-indicator]');

            let currentSlide = 0;

            const showSlide = (index) => {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
                indicators.forEach((indicator, i) => {
                    indicator.classList.toggle('active', i === index);
                });
            };

            const nextSlide = () => {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            };

            const prevSlide = () => {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
            };

            if (prevBtn) prevBtn.addEventListener('click', prevSlide);
            if (nextBtn) nextBtn.addEventListener('click', nextSlide);

            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    currentSlide = index;
                    showSlide(currentSlide);
                });
            });

            // Auto-play
            const autoplay = carousel.dataset.autoplay === 'true';
            if (autoplay) {
                setInterval(nextSlide, 5000);
            }

            // Initialize first slide
            showSlide(0);
        });
    }

    handleGlobalClick(e) {
        // Handle like buttons
        if (e.target.closest('[data-like-btn]')) {
            e.preventDefault();
            this.handleLike(e.target.closest('[data-like-btn]'));
        }

        // Handle bookmark buttons
        if (e.target.closest('[data-bookmark-btn]')) {
            e.preventDefault();
            this.handleBookmark(e.target.closest('[data-bookmark-btn]'));
        }

        // Handle follow buttons
        if (e.target.closest('[data-follow-btn]')) {
            e.preventDefault();
            this.handleFollow(e.target.closest('[data-follow-btn]'));
        }
    }

    async handleLike(button) {
        const postId = button.dataset.postId;
        const isLiked = button.classList.contains('liked');

        try {
            const response = await fetch(`/api/post/${postId}/${isLiked ? 'unlike' : 'like'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            const data = await response.json();

            if (data.success) {
                button.classList.toggle('liked', !isLiked);
                const countElement = button.querySelector('[data-like-count]');
                if (countElement) {
                    countElement.textContent = data.likesCount;
                }
            } else {
                this.showNotification(data.message || 'Failed to update like', 'error');
            }
        } catch (error) {
            console.error('Like error:', error);
            this.showNotification('Failed to update like', 'error');
        }
    }

    async handleBookmark(button) {
        const postId = button.dataset.postId;
        const isBookmarked = button.classList.contains('bookmarked');

        try {
            const response = await fetch(`/api/post/${postId}/${isBookmarked ? 'unbookmark' : 'bookmark'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            const data = await response.json();

            if (data.success) {
                button.classList.toggle('bookmarked', !isBookmarked);
                const countElement = button.querySelector('[data-bookmark-count]');
                if (countElement) {
                    countElement.textContent = data.bookmarksCount;
                }
            } else {
                this.showNotification(data.message || 'Failed to update bookmark', 'error');
            }
        } catch (error) {
            console.error('Bookmark error:', error);
            this.showNotification('Failed to update bookmark', 'error');
        }
    }

    async handleFollow(button) {
        const userId = button.dataset.userId;
        const isFollowing = button.classList.contains('following');

        try {
            const response = await fetch(`/api/user/${userId}/${isFollowing ? 'unfollow' : 'follow'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            const data = await response.json();

            if (data.success) {
                button.classList.toggle('following', !isFollowing);
                button.textContent = isFollowing ? 'Follow' : 'Unfollow';
            } else {
                this.showNotification(data.message || 'Failed to update follow', 'error');
            }
        } catch (error) {
            console.error('Follow error:', error);
            this.showNotification('Failed to update follow', 'error');
        }
    }

    handleFormSubmit(e) {
        const form = e.target;
        const submitBtn = form.querySelector('[type="submit"]');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Re-enable button after 5 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
            }, 5000);
        }
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.focus();
            }
        }

        // Escape to close modals
        if (e.key === 'Escape') {
            this.closeModal();
            this.closeAllDropdowns();
        }
    }

    handleResize() {
        // Handle responsive adjustments
        const isMobile = window.innerWidth < 768;
        document.body.classList.toggle('mobile', isMobile);
    }

    handleScroll() {
        // Show/hide scroll to top button
        const scrollTopBtn = document.querySelector('[data-scroll-top]');
        if (scrollTopBtn) {
            const shouldShow = window.scrollY > 300;
            scrollTopBtn.style.display = shouldShow ? 'block' : 'none';
        }
    }

    scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.modernForum = new ModernForum();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernForum;
}