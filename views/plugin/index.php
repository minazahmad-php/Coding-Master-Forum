<?php
/**
 * Plugin Management View
 */

$this->layout('layouts/app', ['title' => $title ?? 'Plugin Management']);
?>

<div class="plugin-page">
    <div class="container">
        <div class="page-header">
            <h1>Plugin Management</h1>
            <p>Extend your forum functionality with plugins</p>
        </div>

        <div class="plugin-content">
            <!-- Active Plugins -->
            <div class="active-plugins-section">
                <h2>Active Plugins</h2>
                <div class="plugins-grid">
                    <?php foreach ($installed_plugins as $plugin): ?>
                        <?php if ($plugin['is_active']): ?>
                            <div class="plugin-card active">
                                <div class="plugin-header">
                                    <div class="plugin-icon">
                                        <i class="fas fa-puzzle-piece"></i>
                                    </div>
                                    <div class="plugin-info">
                                        <h3><?= View::escape($plugin['name']) ?></h3>
                                        <p><?= View::escape($plugin['description']) ?></p>
                                        <div class="plugin-meta">
                                            <span class="plugin-author">
                                                <i class="fas fa-user"></i>
                                                <?= View::escape($plugin['author']) ?>
                                            </span>
                                            <span class="plugin-version">
                                                <i class="fas fa-tag"></i>
                                                <?= View::escape($plugin['version']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="plugin-status">
                                        <span class="status-badge active">
                                            <i class="fas fa-check"></i>
                                            Active
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="plugin-actions">
                                    <a href="/admin/plugins/settings/<?= View::escape($plugin['directory']) ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-cog"></i>
                                        Settings
                                    </a>
                                    <a href="/admin/plugins/deactivate/<?= View::escape($plugin['directory']) ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-pause"></i>
                                        Deactivate
                                    </a>
                                    <div class="plugin-dropdown">
                                        <button class="btn btn-outline btn-sm dropdown-toggle">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="/admin/plugins/export/<?= View::escape($plugin['directory']) ?>" 
                                               class="dropdown-item">
                                                <i class="fas fa-download"></i>
                                                Export
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="/admin/plugins/uninstall/<?= View::escape($plugin['directory']) ?>" 
                                               class="dropdown-item text-danger"
                                               onclick="return confirm('Are you sure you want to uninstall this plugin?')">
                                                <i class="fas fa-trash"></i>
                                                Uninstall
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Inactive Plugins -->
            <div class="inactive-plugins-section">
                <h2>Inactive Plugins</h2>
                <div class="plugins-grid">
                    <?php foreach ($installed_plugins as $plugin): ?>
                        <?php if (!$plugin['is_active']): ?>
                            <div class="plugin-card inactive">
                                <div class="plugin-header">
                                    <div class="plugin-icon">
                                        <i class="fas fa-puzzle-piece"></i>
                                    </div>
                                    <div class="plugin-info">
                                        <h3><?= View::escape($plugin['name']) ?></h3>
                                        <p><?= View::escape($plugin['description']) ?></p>
                                        <div class="plugin-meta">
                                            <span class="plugin-author">
                                                <i class="fas fa-user"></i>
                                                <?= View::escape($plugin['author']) ?>
                                            </span>
                                            <span class="plugin-version">
                                                <i class="fas fa-tag"></i>
                                                <?= View::escape($plugin['version']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="plugin-status">
                                        <span class="status-badge inactive">
                                            <i class="fas fa-pause"></i>
                                            Inactive
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="plugin-actions">
                                    <a href="/admin/plugins/activate/<?= View::escape($plugin['directory']) ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i>
                                        Activate
                                    </a>
                                    <a href="/admin/plugins/settings/<?= View::escape($plugin['directory']) ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-cog"></i>
                                        Settings
                                    </a>
                                    <div class="plugin-dropdown">
                                        <button class="btn btn-outline btn-sm dropdown-toggle">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="/admin/plugins/export/<?= View::escape($plugin['directory']) ?>" 
                                               class="dropdown-item">
                                                <i class="fas fa-download"></i>
                                                Export
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="/admin/plugins/uninstall/<?= View::escape($plugin['directory']) ?>" 
                                               class="dropdown-item text-danger"
                                               onclick="return confirm('Are you sure you want to uninstall this plugin?')">
                                                <i class="fas fa-trash"></i>
                                                Uninstall
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Available Plugins -->
            <?php if (!empty($available_plugins)): ?>
                <div class="available-plugins-section">
                    <h2>Available Plugins</h2>
                    <div class="plugins-grid">
                        <?php foreach ($available_plugins as $plugin): ?>
                            <div class="plugin-card available">
                                <div class="plugin-header">
                                    <div class="plugin-icon">
                                        <i class="fas fa-puzzle-piece"></i>
                                    </div>
                                    <div class="plugin-info">
                                        <h3><?= View::escape($plugin['name']) ?></h3>
                                        <p><?= View::escape($plugin['description']) ?></p>
                                        <div class="plugin-meta">
                                            <span class="plugin-author">
                                                <i class="fas fa-user"></i>
                                                <?= View::escape($plugin['author']) ?>
                                            </span>
                                            <span class="plugin-version">
                                                <i class="fas fa-tag"></i>
                                                <?= View::escape($plugin['version']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="plugin-status">
                                        <span class="status-badge available">
                                            <i class="fas fa-plus"></i>
                                            Available
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="plugin-actions">
                                    <a href="/admin/plugins/install/<?= View::escape($plugin['directory']) ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i>
                                        Install
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Plugin Actions -->
            <div class="plugin-actions-section">
                <h2>Plugin Actions</h2>
                <div class="actions-grid">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-content">
                            <h3>Create Custom Plugin</h3>
                            <p>Create a new plugin from scratch</p>
                        </div>
                        <div class="action-button">
                            <button class="btn btn-primary" onclick="showCreatePluginModal()">
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
                            <h3>Import Plugin</h3>
                            <p>Upload a plugin from a zip file</p>
                        </div>
                        <div class="action-button">
                            <button class="btn btn-outline" onclick="showImportPluginModal()">
                                <i class="fas fa-upload"></i>
                                Import
                            </button>
                        </div>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="action-content">
                            <h3>Plugin Hooks</h3>
                            <p>View and manage plugin hooks</p>
                        </div>
                        <div class="action-button">
                            <a href="/admin/plugins/hooks" class="btn btn-outline">
                                <i class="fas fa-code"></i>
                                View Hooks
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Plugin Modal -->
<div id="createPluginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Custom Plugin</h3>
            <button class="modal-close" onclick="hideCreatePluginModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="createPluginForm">
                <div class="form-group">
                    <label for="pluginName" class="form-label">Plugin Name</label>
                    <input type="text" id="pluginName" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="pluginDescription" class="form-label">Description</label>
                    <textarea id="pluginDescription" name="description" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="pluginAuthor" class="form-label">Author</label>
                    <input type="text" id="pluginAuthor" name="author" class="form-input" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideCreatePluginModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="createPlugin()">Create Plugin</button>
        </div>
    </div>
</div>

<!-- Import Plugin Modal -->
<div id="importPluginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Plugin</h3>
            <button class="modal-close" onclick="hideImportPluginModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="importPluginForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="pluginFile" class="form-label">Plugin File (ZIP)</label>
                    <input type="file" id="pluginFile" name="plugin_file" class="form-input" accept=".zip" required>
                    <div class="form-help">Select a plugin zip file to import</div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="hideImportPluginModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="importPlugin()">Import Plugin</button>
        </div>
    </div>
</div>

<style>
.plugin-page {
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

.plugin-content {
    max-width: 1200px;
    margin: 0 auto;
}

.active-plugins-section,
.inactive-plugins-section,
.available-plugins-section,
.plugin-actions-section {
    margin-bottom: 3rem;
}

.active-plugins-section h2,
.inactive-plugins-section h2,
.available-plugins-section h2,
.plugin-actions-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.plugin-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: all var(--transition-fast);
}

.plugin-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.plugin-card.active {
    border-color: var(--success-color);
    background: var(--success-color-light);
}

.plugin-card.inactive {
    border-color: var(--warning-color);
    background: var(--warning-color-light);
}

.plugin-card.available {
    border-color: var(--info-color);
    background: var(--info-color-light);
}

.plugin-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.plugin-icon {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    color: white;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.plugin-info {
    flex: 1;
}

.plugin-info h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.plugin-info p {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.plugin-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.plugin-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.plugin-status {
    flex-shrink: 0;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-badge.active {
    background: var(--success-color);
    color: white;
}

.status-badge.inactive {
    background: var(--warning-color);
    color: white;
}

.status-badge.available {
    background: var(--info-color);
    color: white;
}

.plugin-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.plugin-dropdown {
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

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-warning:hover {
    background: var(--warning-color-dark);
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
    .plugin-page {
        padding: 1rem 0;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .plugins-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .action-card {
        flex-direction: column;
        text-align: center;
    }
    
    .plugin-card .plugin-actions {
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

function showCreatePluginModal() {
    document.getElementById('createPluginModal').classList.add('show');
}

function hideCreatePluginModal() {
    document.getElementById('createPluginModal').classList.remove('show');
}

function showImportPluginModal() {
    document.getElementById('importPluginModal').classList.add('show');
}

function hideImportPluginModal() {
    document.getElementById('importPluginModal').classList.remove('show');
}

function createPlugin() {
    const form = document.getElementById('createPluginForm');
    const formData = new FormData(form);
    
    fetch('/admin/plugins/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideCreatePluginModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the plugin');
    });
}

function importPlugin() {
    const form = document.getElementById('importPluginForm');
    const formData = new FormData(form);
    
    fetch('/admin/plugins/import', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideImportPluginModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while importing the plugin');
    });
}
</script>