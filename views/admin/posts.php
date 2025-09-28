<?php include '../header.php'; ?>

//views/admin/posts.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Posts</h2>
    <a href="/admin" class="btn btn-outline-primary">Back to Dashboard</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Posts (<?php echo $totalPosts; ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($posts)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Content</th>
                        <th>Author</th>
                        <th>Thread</th>
                        <th>Forum</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td><?php echo truncate($post['content'], 70); ?></td>
                        <td><?php echo $post['username']; ?></td>
                        <td>
                            <a href="/thread/<?php echo $post['thread_id']; ?>#post-<?php echo $post['id']; ?>">
                                <?php echo truncate($post['thread_title'], 30); ?>
                            </a>
                        </td>
                        <td><?php echo $post['forum_name']; ?></td>
                        <td><?php echo format_date($post['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/thread/<?php echo $post['thread_id']; ?>#post-<?php echo $post['id']; ?>" class="btn btn-outline-primary">View</a>
                                <a href="/admin/posts/delete/<?php echo $post['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
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
        <p class="text-muted text-center py-4">No posts found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>