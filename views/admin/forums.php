<?php include '../header.php'; ?>

//views/admin/forums.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Forums</h2>
    <div>
        <a href="/admin" class="btn btn-outline-primary me-2">Back to Dashboard</a>
        <a href="/admin/forums/create" class="btn btn-primary">Create Forum</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Forums (<?php echo count($forums); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($forums)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Threads</th>
                        <th>Posts</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forums as $forum): ?>
                    <tr>
                        <td><?php echo $forum['id']; ?></td>
                        <td>
                            <strong><?php echo $forum['name']; ?></strong>
                            <br>
                            <small class="text-muted">/forum/<?php echo $forum['slug']; ?></small>
                        </td>
                        <td><?php echo truncate($forum['description'], 50); ?></td>
                        <td><?php echo $forum['threads_count']; ?></td>
                        <td><?php echo $forum['posts_count']; ?></td>
                        <td><?php echo format_date($forum['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/admin/forums/edit/<?php echo $forum['id']; ?>" class="btn btn-outline-primary">Edit</a>
                                <a href="/admin/forums/delete/<?php echo $forum['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this forum?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-4">No forums found. <a href="/admin/forums/create">Create the first forum</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>