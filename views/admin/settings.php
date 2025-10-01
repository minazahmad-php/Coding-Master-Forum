<?php include '../header.php'; ?>

//views/admin/settings.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Forum Settings</h2>
    <a href="/admin" class="btn btn-outline-primary">Back to Dashboard</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">General Settings</h5>
    </div>
    <div class="card-body">
        <form action="/admin/settings" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($settingsMap['site_name'] ?? SITE_NAME); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="site_url" class="form-label">Site URL</label>
                        <input type="url" class="form-control" id="site_url" name="site_url" 
                               value="<?php echo htmlspecialchars($settingsMap['site_url'] ?? SITE_URL); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="default_lang" class="form-label">Default Language</label>
                <select class="form-select" id="default_lang" name="default_lang" required>
                    <option value="en" <?php echo ($settingsMap['default_lang'] ?? DEFAULT_LANG) === 'en' ? 'selected' : ''; ?>>English</option>
                    <option value="bn" <?php echo ($settingsMap['default_lang'] ?? DEFAULT_LANG) === 'bn' ? 'selected' : ''; ?>>Bengali</option>
                </select>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/admin" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Maintenance Mode</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Warning:</strong> Enabling maintenance mode will restrict access to the forum for regular users.
        </div>
        
        <form>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="maintenance_mode" disabled>
                <label class="form-check-label" for="maintenance_mode">Enable Maintenance Mode</label>
            </div>
            
            <div class="mb-3">
                <label for="maintenance_message" class="form-label">Maintenance Message</label>
                <textarea class="form-control" id="maintenance_message" rows="3" disabled>The forum is currently under maintenance. Please check back later.</textarea>
            </div>
            
            <button type="button" class="btn btn-outline-warning" disabled>Save Maintenance Settings</button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Danger Zone</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> 
            <strong>Danger:</strong> These actions are irreversible. Proceed with caution.
        </div>
        
        <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearCacheModal">
                <i class="fas fa-broom"></i> Clear Cache
            </button>
            
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rebuildSearchIndexModal">
                <i class="fas fa-search"></i> Rebuild Search Index
            </button>
            
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetForumModal">
                <i class="fas fa-trash"></i> Reset Forum Data
            </button>
        </div>
    </div>
</div>

<!-- Clear Cache Modal -->
<div class="modal fade" id="clearCacheModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Cache</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear all cached data? This will remove temporary files but won't affect your content.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" disabled>Clear Cache</button>
            </div>
        </div>
    </div>
</div>

<!-- Rebuild Search Index Modal -->
<div class="modal fade" id="rebuildSearchIndexModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rebuild Search Index</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will rebuild the search index from scratch. It may take a while depending on the amount of content.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" disabled>Rebuild Index</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Forum Modal -->
<div class="modal fade" id="resetForumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Forum Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This will delete all forum data including users, threads, and posts. This action cannot be undone!
                </div>
                <p>Type <strong>RESET</strong> to confirm:</p>
                <input type="text" class="form-control" id="resetConfirm" placeholder="Type RESET here">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="resetButton" disabled>Reset Forum</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('resetConfirm').addEventListener('input', function() {
    document.getElementById('resetButton').disabled = this.value !== 'RESET';
});
</script>

<?php include '../footer.php'; ?>