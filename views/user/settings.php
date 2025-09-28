<?php include '../header.php'; ?>

//views/user/settings.php

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Settings</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/user/profile" class="list-group-item list-group-item-action">Profile</a>
                <a href="/user/settings" class="list-group-item list-group-item-action active">Account Settings</a>
                <a href="/user/privacy" class="list-group-item list-group-item-action">Privacy</a>
                <a href="/user/notifications" class="list-group-item list-group-item-action">Notifications</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Account Settings</h5>
            </div>
            <div class="card-body">
                <form action="/user/settings/update" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <h6 class="mb-3">Change Password</h6>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
                
                <hr class="my-4">
                
                <h6 class="mb-3">Account Actions</h6>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#deactivateAccountModal">
                        <i class="fas fa-user-slash"></i> Deactivate Account
                    </button>
                    
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-user-times"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Account Modal -->
<div class="modal fade" id="deactivateAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deactivate Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate your account? Your profile will be hidden but not permanently deleted.</p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="deactivateConfirm">
                    <label class="form-check-label" for="deactivateConfirm">I understand that my account will be deactivated</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="deactivateButton" disabled>Deactivate Account</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This action is permanent and cannot be undone. All your data will be permanently deleted.
                </div>
                <p>Type <strong>DELETE</strong> to confirm:</p>
                <input type="text" class="form-control" id="deleteConfirm" placeholder="Type DELETE here">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteButton" disabled>Delete Account</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('deactivateConfirm').addEventListener('change', function() {
    document.getElementById('deactivateButton').disabled = !this.checked;
});

document.getElementById('deleteConfirm').addEventListener('input', function() {
    document.getElementById('deleteButton').disabled = this.value !== 'DELETE';
});
</script>

<?php include '../footer.php'; ?>