// Search functionality
class SearchManager {
    constructor() {
        this.searchInput = document.getElementById('search-input');
        this.suggestionsContainer = document.getElementById('search-suggestions');
        this.suggestionTimeout = null;
        this.currentQuery = '';
        this.isSearching = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadSearchHistory();
    }
    
    bindEvents() {
        // Search input events
        this.searchInput.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });
        
        this.searchInput.addEventListener('focus', () => {
            this.showSuggestions();
        });
        
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        // Click outside to hide suggestions
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-form')) {
                this.hideSuggestions();
            }
        });
        
        // Form submission
        document.querySelector('.search-form').addEventListener('submit', (e) => {
            this.handleSubmit(e);
        });
        
        // Filter changes
        document.querySelectorAll('.filter-row input, .filter-row select').forEach(input => {
            input.addEventListener('change', () => {
                this.applyFilters();
            });
        });
    }
    
    handleInput(query) {
        this.currentQuery = query.trim();
        
        clearTimeout(this.suggestionTimeout);
        
        if (this.currentQuery.length >= 2) {
            this.suggestionTimeout = setTimeout(() => {
                this.fetchSuggestions(this.currentQuery);
            }, 300);
        } else {
            this.hideSuggestions();
        }
    }
    
    handleKeydown(e) {
        const suggestions = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        const activeSuggestion = this.suggestionsContainer.querySelector('.suggestion-item.active');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.navigateSuggestions(suggestions, activeSuggestion, 'down');
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigateSuggestions(suggestions, activeSuggestion, 'up');
                break;
            case 'Enter':
                e.preventDefault();
                if (activeSuggestion) {
                    this.selectSuggestion(activeSuggestion);
                } else {
                    this.performSearch();
                }
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    navigateSuggestions(suggestions, activeSuggestion, direction) {
        if (suggestions.length === 0) return;
        
        // Remove active class
        if (activeSuggestion) {
            activeSuggestion.classList.remove('active');
        }
        
        let newIndex;
        if (!activeSuggestion) {
            newIndex = direction === 'down' ? 0 : suggestions.length - 1;
        } else {
            const currentIndex = Array.from(suggestions).indexOf(activeSuggestion);
            if (direction === 'down') {
                newIndex = (currentIndex + 1) % suggestions.length;
            } else {
                newIndex = currentIndex === 0 ? suggestions.length - 1 : currentIndex - 1;
            }
        }
        
        suggestions[newIndex].classList.add('active');
    }
    
    selectSuggestion(suggestionElement) {
        const query = suggestionElement.querySelector('.suggestion-text').textContent;
        this.searchInput.value = query;
        this.hideSuggestions();
        this.performSearch();
    }
    
    async fetchSuggestions(query) {
        if (this.isSearching) return;
        
        try {
            this.isSearching = true;
            const response = await fetch(`/search/suggestions?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            this.displaySuggestions(data.suggestions);
        } catch (error) {
            console.error('Error fetching suggestions:', error);
        } finally {
            this.isSearching = false;
        }
    }
    
    displaySuggestions(suggestions) {
        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        let html = '<div class="suggestions-list">';
        
        suggestions.forEach(suggestion => {
            html += `
                <div class="suggestion-item" data-query="${suggestion.text}">
                    <span class="suggestion-text">${this.escapeHtml(suggestion.text)}</span>
                    <span class="suggestion-type">${suggestion.type}</span>
                    <span class="suggestion-count">${suggestion.count}</span>
                </div>
            `;
        });
        
        html += '</div>';
        
        this.suggestionsContainer.innerHTML = html;
        this.showSuggestions();
        
        // Bind click events
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectSuggestion(item);
            });
        });
    }
    
    showSuggestions() {
        if (this.suggestionsContainer.children.length > 0) {
            this.suggestionsContainer.style.display = 'block';
        }
    }
    
    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
    }
    
    handleSubmit(e) {
        const query = this.searchInput.value.trim();
        if (query) {
            this.saveSearchHistory(query);
        }
    }
    
    performSearch() {
        const form = document.querySelector('.search-form');
        form.submit();
    }
    
    applyFilters() {
        // Auto-submit form when filters change
        const form = document.querySelector('.search-form');
        const query = this.searchInput.value.trim();
        
        if (query) {
            form.submit();
        }
    }
    
    saveSearchHistory(query) {
        let history = this.getSearchHistory();
        
        // Remove if already exists
        history = history.filter(item => item !== query);
        
        // Add to beginning
        history.unshift(query);
        
        // Keep only last 10 searches
        history = history.slice(0, 10);
        
        localStorage.setItem('search_history', JSON.stringify(history));
    }
    
    getSearchHistory() {
        const history = localStorage.getItem('search_history');
        return history ? JSON.parse(history) : [];
    }
    
    loadSearchHistory() {
        const history = this.getSearchHistory();
        
        if (history.length > 0 && !this.searchInput.value) {
            this.displaySearchHistory(history);
        }
    }
    
    displaySearchHistory(history) {
        let html = '<div class="suggestions-list">';
        html += '<div class="suggestion-header">Recent searches</div>';
        
        history.forEach(query => {
            html += `
                <div class="suggestion-item" data-query="${query}">
                    <span class="suggestion-text">${this.escapeHtml(query)}</span>
                    <span class="suggestion-type">recent</span>
                </div>
            `;
        });
        
        html += '</div>';
        
        this.suggestionsContainer.innerHTML = html;
        
        // Bind click events
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectSuggestion(item);
            });
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Search analytics
class SearchAnalytics {
    constructor() {
        this.trackClicks();
        this.trackSearchTime();
    }
    
    trackClicks() {
        document.addEventListener('click', (e) => {
            const resultLink = e.target.closest('.result-link');
            if (resultLink) {
                const resultItem = resultLink.closest('.result-item');
                if (resultItem) {
                    const resultId = resultItem.dataset.id;
                    const resultType = resultItem.dataset.type;
                    
                    this.trackResultClick(resultId, resultType);
                }
            }
        });
    }
    
    trackSearchTime() {
        const startTime = performance.now();
        
        window.addEventListener('beforeunload', () => {
            const endTime = performance.now();
            const searchTime = endTime - startTime;
            
            this.trackSearchDuration(searchTime);
        });
    }
    
    async trackResultClick(resultId, resultType) {
        try {
            await fetch('/search/track-click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    result_id: resultId,
                    result_type: resultType
                })
            });
        } catch (error) {
            console.error('Error tracking click:', error);
        }
    }
    
    async trackSearchDuration(duration) {
        try {
            await fetch('/search/track-duration', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    duration: duration
                })
            });
        } catch (error) {
            console.error('Error tracking duration:', error);
        }
    }
}

// Search filters
class SearchFilters {
    constructor() {
        this.filters = {};
        this.init();
    }
    
    init() {
        this.loadFilters();
        this.bindEvents();
    }
    
    loadFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        this.filters = {
            type: urlParams.get('type') || '',
            forum_id: urlParams.get('forum_id') || '',
            date_from: urlParams.get('date_from') || '',
            date_to: urlParams.get('date_to') || '',
            user_id: urlParams.get('user_id') || '',
            tags: urlParams.get('tags') || ''
        };
    }
    
    bindEvents() {
        // Filter change events
        document.querySelectorAll('.filter-row input, .filter-row select').forEach(input => {
            input.addEventListener('change', () => {
                this.updateFilter(input.name, input.value);
            });
        });
        
        // Clear filters button
        const clearButton = document.querySelector('.clear-filters');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }
    
    updateFilter(name, value) {
        this.filters[name] = value;
        this.applyFilters();
    }
    
    clearFilters() {
        this.filters = {};
        
        // Clear form inputs
        document.querySelectorAll('.filter-row input, .filter-row select').forEach(input => {
            input.value = '';
        });
        
        this.applyFilters();
    }
    
    applyFilters() {
        const url = new URL(window.location);
        
        // Clear existing filter params
        Object.keys(this.filters).forEach(key => {
            url.searchParams.delete(key);
        });
        
        // Add non-empty filters
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value) {
                url.searchParams.set(key, value);
            }
        });
        
        // Redirect to new URL
        window.location.href = url.toString();
    }
}

// Search highlighting
class SearchHighlighter {
    constructor() {
        this.highlightResults();
    }
    
    highlightResults() {
        const query = this.getSearchQuery();
        if (!query) return;
        
        const resultItems = document.querySelectorAll('.result-item');
        
        resultItems.forEach(item => {
            this.highlightText(item.querySelector('.result-title'), query);
            this.highlightText(item.querySelector('.result-excerpt'), query);
        });
    }
    
    getSearchQuery() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('q') || '';
    }
    
    highlightText(element, query) {
        if (!element || !query) return;
        
        const text = element.textContent;
        const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi');
        
        element.innerHTML = text.replace(regex, '<mark class="highlight">$1</mark>');
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
}

// Search keyboard shortcuts
class SearchShortcuts {
    constructor() {
        this.bindEvents();
    }
    
    bindEvents() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search-input').focus();
            }
            
            // Escape to clear search
            if (e.key === 'Escape' && document.activeElement.id === 'search-input') {
                document.getElementById('search-input').value = '';
                document.getElementById('search-suggestions').style.display = 'none';
            }
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SearchManager();
    new SearchAnalytics();
    new SearchFilters();
    new SearchHighlighter();
    new SearchShortcuts();
});

// Utility functions
function setSearchQuery(query) {
    document.getElementById('search-input').value = query;
    document.getElementById('search-suggestions').style.display = 'none';
    document.querySelector('.search-form').submit();
}

function trackClick(resultId, resultType) {
    fetch('/search/track-click', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            result_id: resultId,
            result_type: resultType
        })
    }).catch(error => {
        console.error('Error tracking click:', error);
    });
}

// Export for global use
window.SearchManager = SearchManager;
window.SearchAnalytics = SearchAnalytics;
window.SearchFilters = SearchFilters;
window.SearchHighlighter = SearchHighlighter;
window.SearchShortcuts = SearchShortcuts;
window.setSearchQuery = setSearchQuery;
window.trackClick = trackClick;