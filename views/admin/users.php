<?php include '../header.php'; ?>

//views/admin/users.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Users</h2>
    <a href="/admin" class="btn btn-outline-primary">Back to Dashboard</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Users (<?php echo $totalUsers; ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($users)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo get_gravatar($user['email'], 30); ?>" class="rounded-circle me-2" alt="Avatar">
                                <?php echo $user['username']; ?>
                            </div>
                        </td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'banned' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo format_date($user['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/admin/users/edit/<?php echo $user['id']; ?>" class="btn btn-outline-primary">Edit</a>
                                <a href="/admin/users/delete/<?php echo $user['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
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
        <p class="text-muted text-center py-4">No users found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>