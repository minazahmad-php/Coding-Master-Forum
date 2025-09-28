<?php include '../header.php'; ?>

//views/admin/forum_create.php

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin">Admin Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/admin/forums">Manage Forums</a></li>
        <li class="breadcrumb-item active">Create Forum</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Create New Forum</h4>
    </div>
    <div class="card-body">
        <form action="/admin/forums/create" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="name" class="form-label">Forum Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <div class="form-text">This will be displayed as the forum title.</div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                <div class="form-text">A brief description of what this forum is about.</div>
            </div>
            
            <div class="mb-3">
                <label for="icon" class="form-label">Icon (Optional)</label>
                <input type="text" class="form-control" id="icon" name="icon" placeholder="fas fa-comments">
                <div class="form-text">Font Awesome icon class (e.g., fas fa-comments).</div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/admin/forums" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Forum</button>
            </div>
        </form>
    </div>
</div>

<?php include '../footer.php'; ?>