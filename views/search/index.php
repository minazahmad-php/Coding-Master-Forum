<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Search <?php echo SITE_NAME; ?> for posts, threads, and users">
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/search.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/highlight.css">
</head>
<body>
    <div class="search-container">
        <header class="search-header">
            <div class="container">
                <h1>Search <?php echo SITE_NAME; ?></h1>
                
                <form class="search-form" method="GET" action="/search">
                    <div class="search-input-group">
                        <input type="text" 
                               name="q" 
                               value="<?php echo htmlspecialchars($query); ?>" 
                               placeholder="Search posts, threads, users..."
                               class="search-input"
                               id="search-input"
                               autocomplete="off">
                        <button type="submit" class="search-button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="search-suggestions" id="search-suggestions"></div>
                </form>
                
                <div class="search-filters">
                    <details class="filter-group">
                        <summary>Filters</summary>
                        <div class="filter-content">
                            <div class="filter-row">
                                <label for="type">Type:</label>
                                <select name="type" id="type">
                                    <option value="">All</option>
                                    <option value="post" <?php echo $filters['type'] === 'post' ? 'selected' : ''; ?>>Posts</option>
                                    <option value="thread" <?php echo $filters['type'] === 'thread' ? 'selected' : ''; ?>>Threads</option>
                                    <option value="user" <?php echo $filters['type'] === 'user' ? 'selected' : ''; ?>>Users</option>
                                </select>
                            </div>
                            
                            <div class="filter-row">
                                <label for="date_from">From:</label>
                                <input type="date" name="date_from" id="date_from" value="<?php echo $filters['date_from']; ?>">
                            </div>
                            
                            <div class="filter-row">
                                <label for="date_to">To:</label>
                                <input type="date" name="date_to" id="date_to" value="<?php echo $filters['date_to']; ?>">
                            </div>
                            
                            <div class="filter-row">
                                <label for="tags">Tags:</label>
                                <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($filters['tags']); ?>" placeholder="tag1, tag2">
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </header>
        
        <main class="search-results">
            <div class="container">
                <?php if (!empty($query)): ?>
                    <div class="results-header">
                        <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
                        <p class="results-count"><?php echo number_format($totalResults); ?> results found</p>
                    </div>
                    
                    <?php if (empty($results)): ?>
                        <div class="no-results">
                            <div class="no-results-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                            </div>
                            <h3>No results found</h3>
                            <p>Try adjusting your search terms or filters</p>
                            <div class="search-tips">
                                <h4>Search Tips:</h4>
                                <ul>
                                    <li>Use specific keywords</li>
                                    <li>Try different spellings</li>
                                    <li>Use quotes for exact phrases</li>
                                    <li>Remove filters to broaden your search</li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="results-list">
                            <?php foreach ($results as $index => $result): ?>
                                <article class="result-item" data-type="<?php echo $result['type']; ?>" data-id="<?php echo $result['id']; ?>">
                                    <div class="result-header">
                                        <h3 class="result-title">
                                            <a href="<?php echo $this->getResultUrl($result); ?>" class="result-link">
                                                <?php echo $this->highlightText($result['title'], $query); ?>
                                            </a>
                                        </h3>
                                        <span class="result-type"><?php echo ucfirst($result['type']); ?></span>
                                    </div>
                                    
                                    <div class="result-content">
                                        <?php if (!empty($result['highlight']['content'])): ?>
                                            <?php foreach ($result['highlight']['content'] as $highlight): ?>
                                                <p class="result-excerpt"><?php echo $highlight; ?></p>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="result-excerpt"><?php echo $this->highlightText($this->truncateText($result['content'], 200), $query); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="result-meta">
                                        <div class="result-info">
                                            <?php if ($result['type'] === 'user'): ?>
                                                <span class="result-author">@<?php echo $result['username']; ?></span>
                                            <?php else: ?>
                                                <span class="result-author">by <?php echo $this->getUserLink($result['user_id']); ?></span>
                                            <?php endif; ?>
                                            
                                            <span class="result-date"><?php echo $this->formatDate($result['created_at']); ?></span>
                                            
                                            <?php if (isset($result['score'])): ?>
                                                <span class="result-score">Score: <?php echo number_format($result['score'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="result-actions">
                                            <button class="action-button" onclick="trackClick(<?php echo $result['id']; ?>, '<?php echo $result['type']; ?>')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M7 13l3 3 7-7"></path>
                                                </svg>
                                                Mark as helpful
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($totalResults > $perPage): ?>
                            <div class="pagination">
                                <?php echo $this->renderPagination($page, $totalResults, $perPage); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="search-intro">
                        <h2>Search <?php echo SITE_NAME; ?></h2>
                        <p>Find posts, threads, users, and more</p>
                        
                        <div class="search-examples">
                            <h3>Search Examples:</h3>
                            <div class="example-queries">
                                <button class="example-query" onclick="setSearchQuery('PHP programming')">PHP programming</button>
                                <button class="example-query" onclick="setSearchQuery('\"exact phrase\"')">"exact phrase"</button>
                                <button class="example-query" onclick="setSearchQuery('user:admin')">user:admin</button>
                                <button class="example-query" onclick="setSearchQuery('tag:help')">tag:help</button>
                            </div>
                        </div>
                        
                        <div class="popular-searches">
                            <h3>Popular Searches:</h3>
                            <div class="popular-tags">
                                <?php foreach ($this->getPopularTags() as $tag): ?>
                                    <a href="/search?q=tag:<?php echo urlencode($tag['tag']); ?>" class="popular-tag">
                                        <?php echo htmlspecialchars($tag['tag']); ?>
                                        <span class="tag-count"><?php echo $tag['count']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/search.js"></script>
    <script>
        // Initialize search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const suggestionsContainer = document.getElementById('search-suggestions');
            
            // Auto-suggestions
            let suggestionTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(suggestionTimeout);
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    suggestionTimeout = setTimeout(() => {
                        fetchSuggestions(query);
                    }, 300);
                } else {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.style.display = 'none';
                }
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-form')) {
                    suggestionsContainer.style.display = 'none';
                }
            });
            
            // Show suggestions when focusing input
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    suggestionsContainer.style.display = 'block';
                }
            });
        });
        
        function fetchSuggestions(query) {
            fetch(`/search/suggestions?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displaySuggestions(data.suggestions);
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                });
        }
        
        function displaySuggestions(suggestions) {
            const container = document.getElementById('search-suggestions');
            
            if (suggestions.length === 0) {
                container.style.display = 'none';
                return;
            }
            
            let html = '<div class="suggestions-list">';
            
            suggestions.forEach(suggestion => {
                html += `
                    <div class="suggestion-item" onclick="setSearchQuery('${suggestion.text}')">
                        <span class="suggestion-text">${suggestion.text}</span>
                        <span class="suggestion-type">${suggestion.type}</span>
                        <span class="suggestion-count">${suggestion.count}</span>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            container.style.display = 'block';
        }
        
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
            });
        }
    </script>
</body>
</html>