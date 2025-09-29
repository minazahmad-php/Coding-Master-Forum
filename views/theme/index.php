<?php
/**
 * Theme Management View
 */

$this->layout('layouts/app', ['title' => $title ?? 'Theme Management']);
?>

<div class="theme-page">
    <div class="container">
        <div class="page-header">
            <h1>Theme Management</h1>
            <p>Customize the appearance of your forum</p>
        </div>

        <div class="theme-content">
            <!-- Active Theme -->
            <div class="active-theme-section">
                <h2>Active Theme</h2>
                <div class="active-theme-card">
                    <?php 
                    $activeThemeInfo = null;
                    foreach ($installed_themes as $theme) {
                        if ($theme['directory'] === $active_theme) {
                            $activeThemeInfo = $theme;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($activeThemeInfo): ?>
                        <div class="theme-preview">
                            <?php if ($activeThemeInfo['screenshot']): ?>
                                <img src="<?= View::escape($activeThemeInfo['screenshot']) ?>" 
                                     alt="<?= View::escape($activeThemeInfo['name']) ?>"
                                     class="theme-screenshot">
                            <?php else: ?>
                                <div class="theme-placeholder">
                                    <i class="fas fa-palette"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="theme-info">
                            <h3><?= View::escape($activeThemeInfo['name']) ?></h3>
                            <p><?= View::escape($activeThemeInfo['description']) ?></p>
                            <div class="theme-meta">
                                <span class="theme-author">
                                    <i class="fas fa-user"></i>
                                    <?= View::escape($activeThemeInfo['author']) ?>
                                </span>
                                <span class="theme-version">
                                    <i class="fas fa-tag"></i>
                                    <?= View::escape($activeThemeInfo['version']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="theme-actions">
                            <a href="/admin/themes/customize/<?= View::escape($active_theme) ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i>
                                Customize
                            </a>
                            <a href="/admin/themes/preview/<?= View::escape($active_theme) ?>" class="btn btn-outline" target="_blank">
                                <i class="fas fa-eye"></i>
                                Preview
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="no-active-theme">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>No Active Theme</h3>
                            <p>Please select a theme to activate</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Installed Themes -->
            <div class="installed-themes-section">
                <h2>Installed Themes</h2>
                <div class="themes-grid">
                    <?php foreach ($installed_themes as $theme): ?>
                        <div class="theme-card <?= $theme['directory'] === $active_theme ? 'active' : '' ?>">
                            <div class="theme-preview">
                                <?php if ($theme['screenshot']): ?>
                                    <img src="<?= View::escape($theme['screenshot']) ?>" 
                                         alt="<?= View::escape($theme['name']) ?>"
                                         class="theme-screenshot">
                                <?php else: ?>
                                    <div class="theme-placeholder">
                                        <i class="fas fa-palette"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($theme['directory'] === $active_theme): ?>
                                    <div class="active-badge">
                                        <i class="fas fa-check"></i>
                                        Active
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="theme-info">
                                <h3><?= View::escape($theme['name']) ?></h3>
                                <p><?= View::escape($theme['description']) ?></p>
                                <div class="theme-meta">
                                    <span class="theme-author">
                                        <i class="fas fa-user"></i>
                                        <?= View::escape($theme['author']) ?>
                                    </span>
                                    <span class="theme-version">
                                        <i class="fas fa-tag"></i>
                                        <?= View::escape($theme['version']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="theme-actions">
                                <?php if ($theme['directory'] !== $active_theme): ?>
                                    <a href="/admin/themes/activate/<?= View::escape($theme['directory']) ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i>
                                        Activate
                                    </a>
                                <?php endif; ?>
                                
                                <a href="/admin/themes/customize/<?= View::escape($theme['directory']) ?>" 
                                   class="btn btn-outline btn-sm">
                                    <i class="fas fa-edit"></i>
                                    Customize
                                </a>
                                
                                <div class="theme-dropdown">
                                    <button class="btn btn-outline btn-sm dropdown-toggle">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="/admin/themes/preview/<?= View::escape($theme['directory']) ?>" 
                                           class="dropdown-item" target="_blank">
                                            <i class="fas fa-eye"></i>
                                            Preview
                                        </a>
                                        <a href="/admin/themes/export/<?= View::escape($theme['directory']) ?>" 
                                           class="dropdown-item">
                                            <i class="fas fa-download"></i>
                                            Export
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a href="/admin/themes/uninstall/<?= View::escape($theme['directory']) ?>" 
                                           class="dropdown-item text-danger"
                                           onclick="return confirm('Are you sure you want to uninstall this theme?')">
                                            <i class="fas fa-trash"></i>
                                            Uninstall
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Available Themes -->
            <?php if (!empty($available_themes)): ?>
                <div class="available-themes-section">
                    <h2>Available Themes</h2>
                    <div class="themes-grid">
                        <?php foreach ($available_themes as $theme): ?>
                            <div class="theme-card available">
                                <div class="theme-preview">
                                    <?php if ($theme['screenshot']): ?>
                                        <img src="<?= View::escape($theme['screenshot']) ?>" 
                                             alt="<?= View::escape($theme['name']) ?>"
                                             class="theme-screenshot">
                                    <?php else: ?>
                                        <div class="theme-placeholder">
                                            <i class="fas fa-palette"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="available-badge">
                                        <i class="fas fa-plus"></i>
                                        Available
                                    </div>
                                </div>
                                
                                <div class="theme-info">
                                    <h3><?= View::escape($theme['name']) ?></h3>
                                    <p><?= View::escape($theme['description']) ?></p>
                                    <div class="theme-meta">
                                        <span class="theme-author">
                                            <i class="fas fa-user"></i>
                                            <?= View::escape($theme['author']) ?>
                                        </span>
                                        <span class="theme-version">
                                            <i class="fas fa-tag"></i>
                                            <?= View::escape($theme['version']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="theme-actions">
                                    <a href="/admin/themes/install/<?= View::escape($theme['directory']) ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i>
                                        Install
                                    </a>
                                    
                                    <a href="/admin/themes/preview/<?= View::escape($theme['directory']) ?>" 
                                       class="btn btn-outline btn-sm" target="_blank">
                                        <i class="fas fa-eye"></i>
                                        Preview
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Theme Actions -->
            <div class="theme-actions-section">
                <h2>Theme Actions</h2>
                <div class="actions-grid">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-content">
                            <h3>Create Custom Theme</h3>
                            <p>Create a new theme from scratch</p>
                        </div>
                        <div class="action-button">
                            <button class="btn btn-primary" onclick="showCreateThemeModal()">
                                <i class="fas fa-plus"></i>
                                Create
                            </button>
                        </div>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div class="action-content">
                            <h3>Import Theme</h3>
                            <p>Upload a theme from a zip file</p>
                        </div>
                        <div class="action-button">
                            <button class="btn btn-outline" onclick="showImportThemeModal()">
                                <i class="fas fa-upload"></i>
                                Import
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Theme Modal -->
<div id="createThemeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Custom Theme</h3>
            <button class="modal-close" onclick="hideCreateThemeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="createThemeForm">
                <div class="form-group">
                    <label for="themeName" class="form-label">Theme Name</label>
                    <input type="text" id="themeName" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="themeDescription" class="form-label">Description</label>
                    <textarea id="themeDescription" name="description" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="themeAuthor" class="form-label">Author</label>
                    <input type="text" id="themeAuthor" name="author" class="form-input" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideCreateThemeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="createTheme()">Create Theme</button>
        </div>
    </div>
</div>

<!-- Import Theme Modal -->
<div id="importThemeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Theme</h3>
            <button class="modal-close" onclick="hideImportThemeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="importThemeForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="themeFile" class="form-label">Theme File (ZIP)</label>
                    <input type="file" id="themeFile" name="theme_file" class="form-input" accept=".zip" required>
                    <div class="form-help">Select a theme zip file to import</div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideImportThemeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="importTheme()">Import Theme</button>
        </div>
    </div>
</div>

<style>
.theme-page {
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

.theme-content {
    max-width: 1200px;
    margin: 0 auto;
}

.active-theme-section,
.installed-themes-section,
.available-themes-section,
.theme-actions-section {
    margin-bottom: 3rem;
}

.active-theme-section h2,
.installed-themes-section h2,
.available-themes-section h2,
.theme-actions-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.active-theme-card {
    background: var(--bg-primary);
    border: 2px solid var(--primary-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    display: flex;
    gap: 2rem;
    align-items: center;
}

.theme-preview {
    position: relative;
    width: 200px;
    height: 150px;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--bg-secondary);
}

.theme-screenshot {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.theme-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-secondary);
    color: var(--text-muted);
    font-size: 2rem;
}

.active-badge,
.available-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.active-badge {
    background: var(--success-color);
    color: white;
}

.available-badge {
    background: var(--info-color);
    color: white;
}

.theme-info {
    flex: 1;
}

.theme-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.theme-info p {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.theme-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.theme-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.theme-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.theme-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: all var(--transition-fast);
}

.theme-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.theme-card.active {
    border-color: var(--primary-color);
    background: var(--primary-color-light);
}

.theme-card .theme-preview {
    width: 100%;
    height: 120px;
    margin-bottom: 1rem;
}

.theme-card .theme-info {
    margin-bottom: 1rem;
}

.theme-card .theme-info h3 {
    font-size: 1.125rem;
    margin-bottom: 0.5rem;
}

.theme-card .theme-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.theme-dropdown {
    position: relative;
}

.dropdown-toggle {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    padding: 0.5rem 0;
    min-width: 150px;
    z-index: 1000;
    display: none;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: background-color var(--transition-fast);
}

.dropdown-item:hover {
    background: var(--bg-secondary);
}

.dropdown-item.text-danger {
    color: var(--error-color);
}

.dropdown-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0.5rem 0;
}

.no-active-theme {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.no-active-theme i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--warning-color);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.action-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.action-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.action-content {
    flex: 1;
}

.action-content h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.action-content p {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.action-button {
    flex-shrink: 0;
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

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: var(--radius-lg);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .theme-page {
        padding: 1rem 0;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .active-theme-card {
        flex-direction: column;
        text-align: center;
    }
    
    .themes-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .action-card {
        flex-direction: column;
        text-align: center;
    }
    
    .theme-card .theme-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown functionality
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            const isOpen = menu.classList.contains('show');
            
            // Close all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
            
            // Toggle current dropdown
            if (!isOpen) {
                menu.classList.add('show');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    });
});

function showCreateThemeModal() {
    document.getElementById('createThemeModal').classList.add('show');
}

function hideCreateThemeModal() {
    document.getElementById('createThemeModal').classList.remove('show');
}

function showImportThemeModal() {
    document.getElementById('importThemeModal').classList.add('show');
}

function hideImportThemeModal() {
    document.getElementById('importThemeModal').classList.remove('show');
}

function createTheme() {
    const form = document.getElementById('createThemeForm');
    const formData = new FormData(form);
    
    fetch('/admin/themes/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideCreateThemeModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the theme');
    });
}

function importTheme() {
    const form = document.getElementById('importThemeForm');
    const formData = new FormData(form);
    
    fetch('/admin/themes/import', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideImportThemeModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while importing the theme');
    });
}
</script>