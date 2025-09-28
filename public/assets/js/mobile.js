// Mobile-specific functionality for Universal Forum Hub
class MobileManager {
    constructor() {
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        this.swipeThreshold = 50;
        this.pullToRefreshThreshold = 80;
        this.isPulling = false;
        this.pullStartY = 0;
        
        this.init();
    }
    
    init() {
        this.setupTouchGestures();
        this.setupSwipeNavigation();
        this.setupPullToRefresh();
        this.setupMobileMenu();
        this.setupFloatingActionButton();
        this.setupMobileModals();
        this.setupKeyboardHandling();
        this.setupOrientationChange();
        this.setupViewportHandling();
    }
    
    setupTouchGestures() {
        // Touch start
        document.addEventListener('touchstart', (e) => {
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
            this.pullStartY = e.touches[0].clientY;
        }, { passive: true });
        
        // Touch move
        document.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) {
                const touchY = e.touches[0].clientY;
                const touchX = e.touches[0].clientX;
                
                // Pull to refresh
                if (window.scrollY === 0 && touchY > this.pullStartY) {
                    this.handlePullToRefresh(e, touchY);
                }
                
                // Swipe gestures
                this.handleSwipeGesture(e, touchX, touchY);
            }
        }, { passive: false });
        
        // Touch end
        document.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].clientX;
            this.touchEndY = e.changedTouches[0].clientY;
            
            this.handleTouchEnd();
        }, { passive: true });
    }
    
    handlePullToRefresh(e, touchY) {
        const pullDistance = touchY - this.pullStartY;
        
        if (pullDistance > 0 && pullDistance < this.pullToRefreshThreshold) {
            e.preventDefault();
            this.isPulling = true;
            this.updatePullToRefreshIndicator(pullDistance);
        } else if (pullDistance >= this.pullToRefreshThreshold) {
            e.preventDefault();
            this.triggerRefresh();
        }
    }
    
    updatePullToRefreshIndicator(distance) {
        let indicator = document.getElementById('pull-to-refresh-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'pull-to-refresh-indicator';
            indicator.className = 'pull-to-refresh-indicator';
            indicator.innerHTML = '↓';
            document.body.appendChild(indicator);
        }
        
        const opacity = Math.min(distance / this.pullToRefreshThreshold, 1);
        const scale = 0.8 + (opacity * 0.2);
        
        indicator.style.opacity = opacity;
        indicator.style.transform = `translateX(-50%) scale(${scale})`;
        
        if (distance >= this.pullToRefreshThreshold) {
            indicator.innerHTML = '↻';
            indicator.classList.add('active');
        } else {
            indicator.innerHTML = '↓';
            indicator.classList.remove('active');
        }
    }
    
    triggerRefresh() {
        const indicator = document.getElementById('pull-to-refresh-indicator');
        if (indicator) {
            indicator.classList.add('refreshing');
            indicator.innerHTML = '⟳';
        }
        
        // Simulate refresh
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    handleSwipeGesture(e, touchX, touchY) {
        const deltaX = touchX - this.touchStartX;
        const deltaY = touchY - this.touchStartY;
        
        // Determine if it's a horizontal swipe
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            const swipeableElement = e.target.closest('.swipeable');
            if (swipeableElement) {
                this.handleSwipeableElement(swipeableElement, deltaX);
            }
        }
    }
    
    handleSwipeableElement(element, deltaX) {
        const content = element.querySelector('.swipeable-content');
        const actions = element.querySelector('.swipeable-actions');
        
        if (!content || !actions) return;
        
        if (deltaX < -this.swipeThreshold) {
            // Swipe left - show actions
            content.style.transform = `translateX(-80px)`;
            actions.classList.add('active');
        } else if (deltaX > this.swipeThreshold) {
            // Swipe right - hide actions
            content.style.transform = 'translateX(0)';
            actions.classList.remove('active');
        } else {
            // Reset position
            content.style.transform = 'translateX(0)';
            actions.classList.remove('active');
        }
    }
    
    handleTouchEnd() {
        const deltaX = this.touchEndX - this.touchStartX;
        const deltaY = this.touchEndY - this.touchStartY;
        
        // Reset pull to refresh
        if (this.isPulling) {
            this.isPulling = false;
            const indicator = document.getElementById('pull-to-refresh-indicator');
            if (indicator) {
                indicator.style.opacity = '0';
                indicator.classList.remove('active', 'refreshing');
            }
        }
        
        // Handle swipe gestures
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > this.swipeThreshold) {
            if (deltaX > 0) {
                this.handleSwipeRight();
            } else {
                this.handleSwipeLeft();
            }
        }
        
        // Handle vertical swipes
        if (Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > this.swipeThreshold) {
            if (deltaY > 0) {
                this.handleSwipeDown();
            } else {
                this.handleSwipeUp();
            }
        }
    }
    
    handleSwipeLeft() {
        // Navigate back
        if (window.history.length > 1) {
            window.history.back();
        }
    }
    
    handleSwipeRight() {
        // Navigate forward
        if (window.history.length > 1) {
            window.history.forward();
        }
    }
    
    handleSwipeUp() {
        // Scroll up
        window.scrollBy(0, -100);
    }
    
    handleSwipeDown() {
        // Scroll down
        window.scrollBy(0, 100);
    }
    
    setupSwipeNavigation() {
        // Add swipe indicators to swipeable elements
        const swipeableElements = document.querySelectorAll('.swipeable');
        swipeableElements.forEach(element => {
            this.addSwipeIndicators(element);
        });
    }
    
    addSwipeIndicators(element) {
        const leftIndicator = document.createElement('div');
        leftIndicator.className = 'swipe-indicator left';
        leftIndicator.innerHTML = '←';
        
        const rightIndicator = document.createElement('div');
        rightIndicator.className = 'swipe-indicator right';
        rightIndicator.innerHTML = '→';
        
        element.style.position = 'relative';
        element.appendChild(leftIndicator);
        element.appendChild(rightIndicator);
        
        // Hide indicators after 3 seconds
        setTimeout(() => {
            leftIndicator.style.display = 'none';
            rightIndicator.style.display = 'none';
        }, 3000);
    }
    
    setupMobileMenu() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileNav = document.querySelector('.mobile-nav');
        
        if (menuToggle && mobileNav) {
            menuToggle.addEventListener('click', () => {
                mobileNav.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!menuToggle.contains(e.target) && !mobileNav.contains(e.target)) {
                    mobileNav.classList.remove('active');
                }
            });
        }
    }
    
    setupFloatingActionButton() {
        const fab = document.querySelector('.mobile-floating-action');
        
        if (fab) {
            fab.addEventListener('click', () => {
                this.handleFloatingActionClick(fab);
            });
            
            // Show/hide FAB based on scroll position
            let lastScrollY = window.scrollY;
            window.addEventListener('scroll', () => {
                const currentScrollY = window.scrollY;
                
                if (currentScrollY > lastScrollY && currentScrollY > 100) {
                    // Scrolling down
                    fab.style.transform = 'translateY(100px)';
                } else {
                    // Scrolling up
                    fab.style.transform = 'translateY(0)';
                }
                
                lastScrollY = currentScrollY;
            });
        }
    }
    
    handleFloatingActionClick(fab) {
        const action = fab.dataset.action;
        
        switch (action) {
            case 'compose':
                this.openComposeModal();
                break;
            case 'search':
                this.openSearchModal();
                break;
            case 'menu':
                this.toggleMobileMenu();
                break;
            default:
                console.log('FAB action:', action);
        }
    }
    
    openComposeModal() {
        const modal = document.createElement('div');
        modal.className = 'mobile-modal';
        modal.innerHTML = `
            <div class="mobile-modal-content">
                <div class="mobile-modal-header">
                    <h3 class="mobile-modal-title">Compose</h3>
                    <button class="mobile-modal-close">×</button>
                </div>
                <div class="mobile-modal-body">
                    <form>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="Title" required>
                        </div>
                        <div class="form-group">
                            <textarea class="form-control" rows="5" placeholder="Content" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post</button>
                    </form>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close modal
        modal.querySelector('.mobile-modal-close').addEventListener('click', () => {
            modal.remove();
        });
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    openSearchModal() {
        const modal = document.createElement('div');
        modal.className = 'mobile-modal';
        modal.innerHTML = `
            <div class="mobile-modal-content">
                <div class="mobile-modal-header">
                    <h3 class="mobile-modal-title">Search</h3>
                    <button class="mobile-modal-close">×</button>
                </div>
                <div class="mobile-modal-body">
                    <form>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="Search..." autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Focus on input
        modal.querySelector('input').focus();
        
        // Close modal
        modal.querySelector('.mobile-modal-close').addEventListener('click', () => {
            modal.remove();
        });
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    toggleMobileMenu() {
        const mobileNav = document.querySelector('.mobile-nav');
        if (mobileNav) {
            mobileNav.classList.toggle('active');
        }
    }
    
    setupMobileModals() {
        // Handle modal triggers
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                const modalId = e.target.dataset.modal;
                this.openModal(modalId);
            }
        });
    }
    
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('mobile-modal');
            modal.style.display = 'flex';
            
            // Close modal
            const closeBtn = modal.querySelector('.mobile-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal(modal);
                });
            }
            
            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        }
    }
    
    closeModal(modal) {
        modal.classList.remove('mobile-modal');
        modal.style.display = 'none';
    }
    
    setupKeyboardHandling() {
        // Handle virtual keyboard
        window.addEventListener('resize', () => {
            this.handleKeyboardToggle();
        });
        
        // Handle input focus
        document.addEventListener('focusin', (e) => {
            if (e.target.matches('input, textarea')) {
                this.handleInputFocus(e.target);
            }
        });
        
        document.addEventListener('focusout', (e) => {
            if (e.target.matches('input, textarea')) {
                this.handleInputBlur(e.target);
            }
        });
    }
    
    handleKeyboardToggle() {
        const initialHeight = window.innerHeight;
        
        setTimeout(() => {
            const currentHeight = window.innerHeight;
            const heightDifference = initialHeight - currentHeight;
            
            if (heightDifference > 150) {
                // Keyboard is open
                document.body.classList.add('keyboard-open');
            } else {
                // Keyboard is closed
                document.body.classList.remove('keyboard-open');
            }
        }, 100);
    }
    
    handleInputFocus(input) {
        // Scroll input into view
        setTimeout(() => {
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
    
    handleInputBlur(input) {
        // Remove any focus-related classes
        input.classList.remove('focused');
    }
    
    setupOrientationChange() {
        window.addEventListener('orientationchange', () => {
            // Delay to allow orientation change to complete
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });
    }
    
    handleOrientationChange() {
        // Update viewport meta tag
        const viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.content = 'width=device-width, initial-scale=1.0';
        }
        
        // Trigger resize event
        window.dispatchEvent(new Event('resize'));
        
        // Update layout if needed
        this.updateLayoutForOrientation();
    }
    
    updateLayoutForOrientation() {
        const isLandscape = window.innerWidth > window.innerHeight;
        
        if (isLandscape) {
            document.body.classList.add('landscape');
            document.body.classList.remove('portrait');
        } else {
            document.body.classList.add('portrait');
            document.body.classList.remove('landscape');
        }
    }
    
    setupViewportHandling() {
        // Handle viewport changes
        window.addEventListener('resize', () => {
            this.updateViewportClasses();
        });
        
        // Initial viewport setup
        this.updateViewportClasses();
    }
    
    updateViewportClasses() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        
        // Remove existing viewport classes
        document.body.classList.remove('mobile', 'tablet', 'desktop');
        
        // Add appropriate class
        if (width <= 768) {
            document.body.classList.add('mobile');
        } else if (width <= 1024) {
            document.body.classList.add('tablet');
        } else {
            document.body.classList.add('desktop');
        }
        
        // Update device info
        this.isMobile = width <= 768;
        this.isTablet = width > 768 && width <= 1024;
    }
    
    // Utility methods
    isMobileDevice() {
        return this.isMobile;
    }
    
    isTabletDevice() {
        return this.isTablet;
    }
    
    isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }
    
    getDeviceInfo() {
        return {
            isMobile: this.isMobile,
            isTablet: this.isTablet,
            isTouch: this.isTouchDevice(),
            width: window.innerWidth,
            height: window.innerHeight,
            orientation: window.innerWidth > window.innerHeight ? 'landscape' : 'portrait'
        };
    }
    
    // Performance optimizations
    optimizeForMobile() {
        // Lazy load images
        this.setupLazyLoading();
        
        // Optimize scroll performance
        this.optimizeScrollPerformance();
        
        // Reduce animations on low-end devices
        this.optimizeAnimations();
    }
    
    setupLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
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
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => {
                img.src = img.dataset.src;
                img.classList.remove('lazy');
            });
        }
    }
    
    optimizeScrollPerformance() {
        let ticking = false;
        
        const updateScrollPosition = () => {
            // Update scroll position indicators
            const scrollY = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollY / maxScroll) * 100;
            
            document.documentElement.style.setProperty('--scroll-percent', `${scrollPercent}%`);
            
            ticking = false;
        };
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateScrollPosition);
                ticking = true;
            }
        });
    }
    
    optimizeAnimations() {
        // Reduce animations on low-end devices
        if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
            document.body.classList.add('reduced-motion');
        }
        
        // Respect user's motion preferences
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduced-motion');
        }
    }
}

// Initialize mobile manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.mobileManager = new MobileManager();
});

// Export for global use
window.MobileManager = MobileManager;