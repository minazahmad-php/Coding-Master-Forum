<?php
/**
 * Home Page View
 * @var array $featured_posts
 * @var array $latest_posts
 * @var array $trending_posts
 * @var array $categories
 * @var array $stats
 */

$this->layout('layouts/app', ['title' => $title ?? 'Welcome to Modern Forum']);
?>

<div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to <?= View::escape(APP_NAME) ?></h1>
                <p class="hero-subtitle">Connect, discuss, and share with our amazing community</p>
                
                <div class="hero-actions">
                    <?php if (!$is_logged_in): ?>
                        <a href="/register" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i>
                            Join Now
                        </a>
                        <a href="/forums" class="btn btn-outline btn-lg">
                            <i class="fas fa-th-list"></i>
                            Browse Forums
                        </a>
                    <?php else: ?>
                        <a href="/post/create" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus"></i>
                            Create Post
                        </a>
                        <a href="/forums" class="btn btn-outline btn-lg">
                            <i class="fas fa-th-list"></i>
                            Browse Forums
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats -->
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['total_users'] ?? 0) ?></div>
                        <div class="stat-label">Members</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['total_posts'] ?? 0) ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['total_categories'] ?? 0) ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['online_users'] ?? 0) ?></div>
                        <div class="stat-label">Online Now</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Posts -->
    <?php if (!empty($featured_posts)): ?>
        <section class="featured-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i>
                        Featured Posts
                    </h2>
                    <a href="/featured" class="section-link">View All</a>
                </div>
                
                <div class="posts-grid">
                    <?php foreach ($featured_posts as $post): ?>
                        <article class="post-card featured">
                            <div class="post-header">
                                <div class="post-meta">
                                    <a href="/user/<?= View::escape($post->author()->username ?? 'unknown') ?>" class="post-author">
                                        <img src="<?= View::escape($post->author()->getAvatarUrl()) ?>" 
                                             alt="<?= View::escape($post->author()->username ?? 'User') ?>"
                                             class="author-avatar">
                                        <span class="author-name"><?= View::escape($post->author()->username ?? 'User') ?></span>
                                    </a>
                                    <span class="post-date">
                                        <i class="fas fa-clock"></i>
                                        <?= View::timeAgo($post->created_at) ?>
                                    </span>
                                </div>
                                
                                <div class="post-badges">
                                    <?php if ($post->isFeatured()): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-star"></i>
                                            Featured
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($post->isPinned()): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-thumbtack"></i>
                                            Pinned
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="post-content">
                                <h3 class="post-title">
                                    <a href="<?= View::escape($post->getUrl()) ?>">
                                        <?= View::escape($post->title) ?>
                                    </a>
                                </h3>
                                
                                <p class="post-excerpt">
                                    <?= View::escape($post->getExcerpt(150)) ?>
                                </p>
                                
                                <?php if ($post->featured_image): ?>
                                    <div class="post-image">
                                        <img src="<?= View::escape($post->getFeaturedImageUrl()) ?>" 
                                             alt="<?= View::escape($post->title) ?>"
                                             loading="lazy">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-footer">
                                <div class="post-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-eye"></i>
                                        <?= number_format($post->views_count ?? 0) ?>
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-heart"></i>
                                        <?= number_format($post->likes_count ?? 0) ?>
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-comment"></i>
                                        <?= number_format($post->comments_count ?? 0) ?>
                                    </span>
                                </div>
                                
                                <div class="post-actions">
                                    <a href="<?= View::escape($post->getUrl()) ?>" class="btn btn-sm btn-outline">
                                        Read More
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Categories -->
    <?php if (!empty($categories)): ?>
        <section class="categories-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-th-list"></i>
                        Forum Categories
                    </h2>
                    <a href="/forums" class="section-link">View All</a>
                </div>
                
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-<?= View::escape($category->icon ?? 'folder') ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h3 class="category-name">
                                        <a href="<?= View::escape($category->getUrl()) ?>">
                                            <?= View::escape($category->name) ?>
                                        </a>
                                    </h3>
                                    <p class="category-description">
                                        <?= View::escape($category->description ?? '') ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= number_format($category->posts_count ?? 0) ?></span>
                                    <span class="stat-label">Posts</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= number_format($category->recent_posts_count ?? 0) ?></span>
                                    <span class="stat-label">Recent</span>
                                </div>
                            </div>
                            
                            <?php if ($category->getLatestPost()): ?>
                                <div class="category-latest">
                                    <div class="latest-post">
                                        <span class="latest-label">Latest:</span>
                                        <a href="<?= View::escape($category->getLatestPost()->getUrl()) ?>" class="latest-title">
                                            <?= View::escape($category->getLatestPost()->title) ?>
                                        </a>
                                        <span class="latest-date">
                                            <?= View::timeAgo($category->getLatestPost()->created_at) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Latest Posts -->
    <?php if (!empty($latest_posts)): ?>
        <section class="latest-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i>
                        Latest Posts
                    </h2>
                    <a href="/latest" class="section-link">View All</a>
                </div>
                
                <div class="posts-list">
                    <?php foreach ($latest_posts as $post): ?>
                        <article class="post-item">
                            <div class="post-avatar">
                                <img src="<?= View::escape($post->author()->getAvatarUrl()) ?>" 
                                     alt="<?= View::escape($post->author()->username ?? 'User') ?>"
                                     class="author-avatar">
                            </div>
                            
                            <div class="post-content">
                                <div class="post-header">
                                    <h3 class="post-title">
                                        <a href="<?= View::escape($post->getUrl()) ?>">
                                            <?= View::escape($post->title) ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="post-meta">
                                        <a href="/user/<?= View::escape($post->author()->username ?? 'unknown') ?>" class="post-author">
                                            <?= View::escape($post->author()->username ?? 'User') ?>
                                        </a>
                                        <span class="post-date">
                                            <?= View::timeAgo($post->created_at) ?>
                                        </span>
                                        <a href="<?= View::escape($post->category()->getUrl()) ?>" class="post-category">
                                            <?= View::escape($post->category()->name ?? 'Uncategorized') ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <p class="post-excerpt">
                                    <?= View::escape($post->getExcerpt(100)) ?>
                                </p>
                            </div>
                            
                            <div class="post-stats">
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($post->views_count ?? 0) ?>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-heart"></i>
                                    <?= number_format($post->likes_count ?? 0) ?>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-comment"></i>
                                    <?= number_format($post->comments_count ?? 0) ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Trending Posts -->
    <?php if (!empty($trending_posts)): ?>
        <section class="trending-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-fire"></i>
                        Trending Now
                    </h2>
                    <a href="/trending" class="section-link">View All</a>
                </div>
                
                <div class="trending-list">
                    <?php foreach ($trending_posts as $index => $post): ?>
                        <div class="trending-item">
                            <div class="trending-rank">
                                <?= $index + 1 ?>
                            </div>
                            
                            <div class="trending-content">
                                <h4 class="trending-title">
                                    <a href="<?= View::escape($post->getUrl()) ?>">
                                        <?= View::escape($post->title) ?>
                                    </a>
                                </h4>
                                
                                <div class="trending-meta">
                                    <span class="trending-author">
                                        by <?= View::escape($post->author()->username ?? 'User') ?>
                                    </span>
                                    <span class="trending-date">
                                        <?= View::timeAgo($post->created_at) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="trending-stats">
                                <span class="trending-views">
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($post->views_count ?? 0) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Join Our Community?</h2>
                <p class="cta-subtitle">Connect with like-minded people and share your thoughts</p>
                
                <div class="cta-actions">
                    <?php if (!$is_logged_in): ?>
                        <a href="/register" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i>
                            Sign Up Free
                        </a>
                        <a href="/login" class="btn btn-outline btn-lg">
                            <i class="fas fa-sign-in-alt"></i>
                            Login
                        </a>
                    <?php else: ?>
                        <a href="/post/create" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus"></i>
                            Create Your First Post
                        </a>
                        <a href="/forums" class="btn btn-outline btn-lg">
                            <i class="fas fa-th-list"></i>
                            Explore Forums
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* Home Page Specific Styles */
.home-page {
    padding-top: 0;
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.hero-actions {
    margin-bottom: 3rem;
}

.hero-actions .btn {
    margin: 0 0.5rem;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 2rem;
    max-width: 600px;
    margin: 0 auto;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

.section-title i {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

.section-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.section-link:hover {
    text-decoration: underline;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.post-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.post-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.post-card.featured {
    border-left: 4px solid var(--warning-color);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    color: var(--text-primary);
}

.author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.post-date {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.post-badges {
    display: flex;
    gap: 0.5rem;
}

.post-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.post-title a {
    color: var(--text-primary);
    text-decoration: none;
}

.post-title a:hover {
    color: var(--primary-color);
}

.post-excerpt {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.post-image {
    margin-bottom: 1rem;
}

.post-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: var(--radius-md);
}

.post-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.post-stats {
    display: flex;
    gap: 1rem;
}

.post-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: transform var(--transition-fast);
}

.category-card:hover {
    transform: translateY(-2px);
}

.category-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.category-icon {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    color: white;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.category-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.category-name a {
    color: var(--text-primary);
    text-decoration: none;
}

.category-name a:hover {
    color: var(--primary-color);
}

.category-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.category-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.category-stats .stat-item {
    text-align: center;
}

.category-stats .stat-number {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-color);
    display: block;
}

.category-stats .stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.category-latest {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
}

.latest-post {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.latest-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 500;
}

.latest-title {
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
}

.latest-title:hover {
    color: var(--primary-color);
}

.latest-date {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.posts-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.post-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    transition: box-shadow var(--transition-fast);
}

.post-item:hover {
    box-shadow: var(--shadow-sm);
}

.post-avatar {
    flex-shrink: 0;
}

.post-avatar .author-avatar {
    width: 40px;
    height: 40px;
}

.post-content {
    flex: 1;
}

.post-item .post-title {
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.post-item .post-meta {
    margin-bottom: 0.5rem;
}

.post-item .post-excerpt {
    font-size: 0.875rem;
    margin-bottom: 0;
}

.post-item .post-stats {
    flex-shrink: 0;
    flex-direction: column;
    gap: 0.5rem;
}

.trending-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.trending-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
}

.trending-rank {
    width: 32px;
    height: 32px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.trending-content {
    flex: 1;
}

.trending-title {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.trending-title a {
    color: var(--text-primary);
    text-decoration: none;
}

.trending-title a:hover {
    color: var(--primary-color);
}

.trending-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.trending-stats {
    flex-shrink: 0;
}

.trending-views {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.cta-section {
    background: var(--bg-secondary);
    padding: 4rem 0;
    text-align: center;
}

.cta-title {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.cta-subtitle {
    font-size: 1.125rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.cta-actions .btn {
    margin: 0 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .hero-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .post-item {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .post-item .post-stats {
        flex-direction: row;
        justify-content: flex-start;
    }
    
    .trending-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .trending-content {
        width: 100%;
    }
    
    .cta-title {
        font-size: 1.5rem;
    }
    
    .cta-actions .btn {
        display: block;
        width: 100%;
        margin: 0.5rem 0;
    }
}