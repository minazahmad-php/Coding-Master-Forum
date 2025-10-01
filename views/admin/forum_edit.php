<?php include '../header.php'; ?>

//views/admin/forum_edit.php

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin">Admin Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/admin/forums">Manage Forums</a></li>
        <li class="breadcrumb-item active">Edit Forum</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Edit Forum: <?php echo $forum['name']; ?></h4>
    </div>
    <div class="card-body">
        <form action="/admin/forums/edit/<?php echo $forum['id']; ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="name" class="form-label">Forum Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($forum['name']); ?>" required>
                <div class="form-text">This will be displayed as the forum title.</div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($forum['description']); ?></textarea>
                <div class="form-text">A brief description of what this forum is about.</div>
            </div>
            
            <div class="mb-3">
                <label for="icon" class="form-label">Icon (Optional)</label>
                <input type="text" class="form-control" id="icon" name="icon" value="<?php echo htmlspecialchars($forum['icon']); ?>" placeholder="fas fa-comments">
                <div class="form-text">Font Awesome icon class (e.g., fas fa-comments).</div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/admin/forums" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Forum</button>
            </div>
        </form>
    </div>
</div>

<?php include '../footer.php'; ?>