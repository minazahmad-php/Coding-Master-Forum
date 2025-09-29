<?php
/**
 * Language Settings View
 */

$this->layout('layouts/app', ['title' => $title ?? 'Language Settings']);
?>

<div class="language-page">
    <div class="container">
        <div class="page-header">
            <h1>Language Settings</h1>
            <p>Manage your language preferences and translations</p>
        </div>

        <div class="language-content">
            <!-- Current Language -->
            <div class="current-language-section">
                <h2>Current Language</h2>
                <div class="current-language-card">
                    <div class="language-info">
                        <div class="language-flag">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="language-details">
                            <h3><?= View::escape($current_language_info['name'] ?? 'English') ?></h3>
                            <p><?= View::escape($current_language_info['native_name'] ?? 'English') ?></p>
                            <span class="language-direction">
                                <?= View::escape($current_language_info['direction'] ?? 'ltr') ?>
                            </span>
                        </div>
                    </div>
                    <div class="language-actions">
                        <a href="/language/translations/<?= View::escape($current_language) ?>" class="btn btn-outline">
                            <i class="fas fa-edit"></i>
                            Manage Translations
                        </a>
                    </div>
                </div>
            </div>

            <!-- Available Languages -->
            <div class="available-languages-section">
                <h2>Available Languages</h2>
                <div class="languages-grid">
                    <?php foreach ($languages as $language): ?>
                        <div class="language-card <?= $language['code'] === $current_language ? 'active' : '' ?>">
                            <div class="language-header">
                                <div class="language-flag">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="language-info">
                                    <h3><?= View::escape($language['name']) ?></h3>
                                    <p><?= View::escape($language['native_name']) ?></p>
                                </div>
                                <?php if ($language['code'] === $current_language): ?>
                                    <div class="current-badge">
                                        <i class="fas fa-check"></i>
                                        Current
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="language-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Translations:</span>
                                    <span class="stat-value"><?= $language['translation_count'] ?? 0 ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Active:</span>
                                    <span class="stat-value"><?= $language['active_count'] ?? 0 ?></span>
                                </div>
                            </div>
                            
                            <div class="language-actions">
                                <?php if ($language['code'] !== $current_language): ?>
                                    <a href="/language/switch/<?= View::escape($language['code']) ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-language"></i>
                                        Switch
                                    </a>
                                <?php endif; ?>
                                <a href="/language/translations/<?= View::escape($language['code']) ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-edit"></i>
                                    Manage
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Translation Statistics -->
            <div class="translation-stats-section">
                <h2>Translation Statistics</h2>
                <div class="stats-grid">
                    <?php foreach ($stats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-header">
                                <h3><?= View::escape($stat['name']) ?></h3>
                                <span class="stat-code"><?= View::escape($stat['code']) ?></span>
                            </div>
                            <div class="stat-content">
                                <div class="stat-bar">
                                    <div class="stat-fill" style="width: <?= $stat['active_count'] > 0 ? min(100, ($stat['active_count'] / max($stat['translation_count'], 1)) * 100) : 0 ?>%"></div>
                                </div>
                                <div class="stat-numbers">
                                    <span><?= $stat['active_count'] ?> / <?= $stat['translation_count'] ?></span>
                                    <span><?= $stat['active_count'] > 0 ? round(($stat['active_count'] / max($stat['translation_count'], 1)) * 100) : 0 ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.language-page {
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.page-header p {
    font-size: 1.125rem;
    color: var(--text-secondary);
}

.language-content {
    max-width: 1200px;
    margin: 0 auto;
}

.current-language-section,
.available-languages-section,
.translation-stats-section {
    margin-bottom: 3rem;
}

.current-language-section h2,
.available-languages-section h2,
.translation-stats-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.current-language-card {
    background: var(--bg-primary);
    border: 2px solid var(--primary-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.language-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.language-flag {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.language-details h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.language-details p {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.language-direction {
    background: var(--primary-color-light);
    color: var(--primary-color);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.languages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.language-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: all var(--transition-fast);
}

.language-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.language-card.active {
    border-color: var(--primary-color);
    background: var(--primary-color-light);
}

.language-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.language-header .language-flag {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
}

.language-header .language-info {
    flex: 1;
}

.language-header .language-info h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.language-header .language-info p {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.current-badge {
    background: var(--success-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.language-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 500;
}

.stat-value {
    font-size: 0.875rem;
    color: var(--text-primary);
    font-weight: 600;
}

.language-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-color-dark);
}

.btn-outline {
    background: transparent;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
    border-color: var(--primary-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.stat-code {
    background: var(--bg-secondary);
    color: var(--text-muted);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
}

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-bar {
    width: 100%;
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 4px;
    overflow: hidden;
}

.stat-fill {
    height: 100%;
    background: var(--primary-color);
    transition: width 0.3s ease;
}

.stat-numbers {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

/* Responsive Design */
@media (max-width: 768px) {
    .language-page {
        padding: 1rem 0;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .current-language-card {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .languages-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .language-actions {
        flex-direction: column;
    }
}
</style>