<?php include '../header.php'; ?>

//views/admin/threads.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Threads</h2>
    <a href="/admin" class="btn btn-outline-primary">Back to Dashboard</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Threads (<?php echo $totalThreads; ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($threads)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Forum</th>
                        <th>Replies</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($threads as $thread): ?>
                    <tr>
                        <td><?php echo $thread['id']; ?></td>
                        <td>
                            <a href="/thread/<?php echo $thread['id']; ?>"><?php echo truncate($thread['title'], 50); ?></a>
                            <?php if ($thread['is_pinned']): ?>
                            <span class="badge bg-warning ms-1"><i class="fas fa-thumbtack"></i></span>
                            <?php endif; ?>
                            <?php if ($thread['is_locked']): ?>
                            <span class="badge bg-danger ms-1"><i class="fas fa-lock"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $thread['username']; ?></td>
                        <td><?php echo $thread['forum_name']; ?></td>
                        <td><?php echo $thread['replies_count']; ?></td>
                        <td><?php echo $thread['views']; ?></td>
                        <td>
                            <?php if ($thread['is_pinned']): ?>
                            <span class="badge bg-warning">Pinned</span>
                            <?php endif; ?>
                            <?php if ($thread['is_locked']): ?>
                            <span class="badge bg-danger">Locked</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo format_date($thread['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/thread/<?php echo $thread['id']; ?>" class="btn btn-outline-primary">View</a>
                                <a href="/admin/threads/delete/<?php echo $thread['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this thread?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['pages'] > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($pagination['previous']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo str_replace('{page}', $pagination['previous'], $pagination['urlPattern']); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php foreach ($pagination['items'] as $item): ?>
                <li class="page-item <?php echo $item['active'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $item['url']; ?>"><?php echo $item['page']; ?></a>
                </li>
                <?php endforeach; ?>
                
                <?php if ($pagination['next']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo str_replace('{page}', $pagination['next'], $pagination['urlPattern']); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <p class="text-muted text-center py-4">No threads found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>