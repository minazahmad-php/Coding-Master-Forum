//public/js/app.js
// JavaScript for Coding Master Forum

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm before destructive actions
    const confirmLinks = document.querySelectorAll('a[data-confirm]');
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Auto-resize textareas
    const autoResizeTextareas = document.querySelectorAll('textarea.auto-resize');
    autoResizeTextareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Trigger initial resize
        textarea.dispatchEvent(new Event('input'));
    });

    // Live search functionality
    const searchInput = document.getElementById('live-search');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                document.getElementById('search-results').innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`/api/search?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        displaySearchResults(data);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }, 300);
        });
    }

    // Mark notifications as read
    const notificationLinks = document.querySelectorAll('.notification-link');
    notificationLinks.forEach(link => {
        link.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            if (notificationId) {
                fetch(`/api/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
            }
        });
    });

    // Infinite scroll for threads and posts
    let isLoading = false;
    const infiniteScrollContainers = document.querySelectorAll('.infinite-scroll');
    
    infiniteScrollContainers.forEach(container => {
        const nextPage = container.getAttribute('data-next-page');
        const hasMore = container.getAttribute('data-has-more') === 'true';
        
        if (hasMore) {
            window.addEventListener('scroll', function() {
                if (isLoading) return;
                
                const containerRect = container.getBoundingClientRect();
                if (containerRect.bottom <= window.innerHeight + 100) {
                    loadMoreContent(nextPage);
                }
            });
        }
    });

    // Emoji picker for message inputs
    const messageInputs = document.querySelectorAll('.message-input');
    messageInputs.forEach(input => {
        const emojiButton = input.parentNode.querySelector('.emoji-picker-btn');
        if (emojiButton) {
            emojiButton.addEventListener('click', function() {
                // Simple emoji picker implementation
                const emojis = ['😀', '😂', '😍', '🤔', '👍', '❤️', '🎉', '🔥'];
                const picker = document.createElement('div');
                picker.className = 'emoji-picker';
                picker.style.cssText = `
                    position: absolute;
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 8px;
                    z-index: 1000;
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 4px;
                `;
                
                emojis.forEach(emoji => {
                    const span = document.createElement('span');
                    span.textContent = emoji;
                    span.style.cssText = 'cursor: pointer; font-size: 1.2em; padding: 4px;';
                    span.addEventListener('click', function() {
                        input.value += emoji;
                        picker.remove();
                    });
                    picker.appendChild(span);
                });
                
                document.body.appendChild(picker);
                
                // Position picker near the button
                const rect = emojiButton.getBoundingClientRect();
                picker.style.top = (rect.bottom + window.scrollY) + 'px';
                picker.style.left = (rect.left + window.scrollX) + 'px';
                
                // Close picker when clicking outside
                const closePicker = function(e) {
                    if (!picker.contains(e.target) && e.target !== emojiButton) {
                        picker.remove();
                        document.removeEventListener('click', closePicker);
                    }
                };
                
                setTimeout(() => {
                    document.addEventListener('click', closePicker);
                }, 0);
            });
        }
    });

    // Real-time notifications (using polling for simplicity)
    if (document.querySelector('.notification-indicator')) {
        setInterval(() => {
            fetch('/api/notifications/unread-count')
                .then(response => response.json())
                .then(data => {
                    const indicator = document.querySelector('.notification-indicator');
                    if (indicator) {
                        if (data.count > 0) {
                            indicator.textContent = data.count;
                            indicator.style.display = 'inline';
                        } else {
                            indicator.style.display = 'none';
                        }
                    }
                });
        }, 30000); // Check every 30 seconds
    }
});

function displaySearchResults(results) {
    const container = document.getElementById('search-results');
    if (!container) return;
    
    if (results.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted">No results found</div>';
        return;
    }
    
    let html = '';
    results.forEach(result => {
        html += `
            <div class="list-group-item">
                <h6 class="mb-1"><a href="${result.url}">${result.title}</a></h6>
                <small class="text-muted">${result.description}</small>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function loadMoreContent(page) {
    isLoading = true;
    
    // Show loading indicator
    const loader = document.createElement('div');
    loader.className = 'text-center py-3';
    loader.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
    document.querySelector('.infinite-scroll').appendChild(loader);
    
    fetch(page)
        .then(response => response.text())
        .then(html => {
            loader.remove();
            
            // Parse the HTML and extract the content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('.infinite-scroll').innerHTML;
            const nextPage = doc.querySelector('.infinite-scroll').getAttribute('data-next-page');
            const hasMore = doc.querySelector('.infinite-scroll').getAttribute('data-has-more') === 'true';
            
            // Append new content
            document.querySelector('.infinite-scroll').innerHTML += newContent;
            
            // Update next page and has more attributes
            if (nextPage) {
                document.querySelector('.infinite-scroll').setAttribute('data-next-page', nextPage);
                document.querySelector('.infinite-scroll').setAttribute('data-has-more', hasMore);
            } else {
                document.querySelector('.infinite-scroll').removeAttribute('data-next-page');
                document.querySelector('.infinite-scroll').setAttribute('data-has-more', 'false');
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Error loading more content:', error);
            loader.remove();
            isLoading = false;
        });
}

// Utility function to format dates
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes} min ago`;
    if (hours < 24) return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    if (days < 7) return `${days} day${days !== 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString();
}

// Apply date formatting to all elements with data-date attribute
document.querySelectorAll('[data-date]').forEach(element => {
    element.textContent = formatDate(element.getAttribute('data-date'));
});