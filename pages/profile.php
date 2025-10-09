<?php
/**
 * Forum Project - Profile Page
 * Free Hosting Optimized
 */

if (!is_logged_in()) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($pdo, $user_id);

if (!$user) {
    handle_error('User not found', 'index.php');
}

$action = $_GET['action'] ?? 'view';
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'update_profile':
            $username = sanitize_input($_POST['username'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $bio = sanitize_input($_POST['bio'] ?? '');
            
            if (empty($username) || empty($email)) {
                $error = 'Username and email are required.';
            } elseif (!validate_email($email)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if username/email already exists (excluding current user)
                $existing_user = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $existing_user->execute([$username, $email, $user_id]);
                
                if ($existing_user->fetch()) {
                    $error = 'Username or email already exists.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$username, $email, $bio, $user_id])) {
                        $_SESSION['username'] = $username;
                        $success = 'Profile updated successfully!';
                        $user = get_user_by_id($pdo, $user_id); // Refresh user data
                    } else {
                        $error = 'Failed to update profile.';
                    }
                }
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required.';
            } elseif (!verify_password($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (!validate_password($new_password)) {
                $error = 'New password must be at least 6 characters long.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } else {
                $hashed_password = hash_password($new_password);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password.';
                }
            }
            break;
    }
}

// Get user statistics
$topics_stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE user_id = ? AND status = 'active'");
$topics_stmt->execute([$user_id]);
$topics_count = $topics_stmt->fetchColumn();

$replies_stmt = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE user_id = ? AND status = 'active'");
$replies_stmt->execute([$user_id]);
$replies_count = $replies_stmt->fetchColumn();

$views_stmt = $pdo->prepare("SELECT SUM(views) FROM topics WHERE user_id = ? AND status = 'active'");
$views_stmt->execute([$user_id]);
$views_count = $views_stmt->fetchColumn() ?: 0;

$user_stats = [
    'topics' => $topics_count,
    'replies' => $replies_count,
    'views' => $views_count
];

// Get recent topics by user
$recent_topics_stmt = $pdo->prepare("SELECT t.*, c.name as category_name 
                                    FROM topics t 
                                    JOIN categories c ON t.category_id = c.id 
                                    WHERE t.user_id = ? AND t.status = 'active' 
                                    ORDER BY t.created_at DESC 
                                    LIMIT 5");
$recent_topics_stmt->execute([$user_id]);
$recent_topics = $recent_topics_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Forum Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 25px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-comments me-2"></i>Forum Project
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=profile">Profile</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=profile">Profile</a></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="index.php?page=admin">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="public/images/default-avatar.png" alt="Avatar" class="profile-avatar">
                </div>
                <div class="col-md-9">
                    <h1 class="display-4 mb-2"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="lead mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="mb-0">
                        <span class="badge bg-light text-dark me-2">
                            <i class="fas fa-user me-1"></i><?php echo ucfirst($user['role']); ?>
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-calendar me-1"></i>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Navigation -->
            <div class="col-md-3">
                <div class="profile-card">
                    <div class="card-body">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $action === 'view' ? 'active' : ''; ?>" href="?action=view">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $action === 'edit' ? 'active' : ''; ?>" href="?action=edit">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $action === 'password' ? 'active' : ''; ?>" href="?action=password">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $action === 'topics' ? 'active' : ''; ?>" href="?action=topics">
                                    <i class="fas fa-comments me-2"></i>My Topics
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="col-md-9">
                <?php if ($action === 'view'): ?>
                    <!-- Profile Stats -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-comments text-primary" style="font-size: 2rem;"></i>
                                <h3><?php echo number_format($user_stats['topics']); ?></h3>
                                <p class="text-muted">Topics</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-reply text-success" style="font-size: 2rem;"></i>
                                <h3><?php echo number_format($user_stats['replies']); ?></h3>
                                <p class="text-muted">Replies</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-eye text-info" style="font-size: 2rem;"></i>
                                <h3><?php echo number_format($user_stats['views']); ?></h3>
                                <p class="text-muted">Views</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($user['bio'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No bio available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Topics -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Topics</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_topics)): ?>
                                <p class="text-muted">No topics created yet.</p>
                            <?php else: ?>
                                <?php foreach ($recent_topics as $topic): ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="index.php?page=topic&id=<?php echo $topic['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($topic['title']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                in <?php echo htmlspecialchars($topic['category_name']); ?> • 
                                                <?php echo time_ago($topic['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i><?php echo $topic['views']; ?> views
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($action === 'edit'): ?>
                    <!-- Edit Profile -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" name="action" value="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'password'): ?>
                    <!-- Change Password -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="action" value="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'topics'): ?>
                    <!-- My Topics -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-comments me-2"></i>My Topics</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_topics)): ?>
                                <p class="text-muted">You haven't created any topics yet.</p>
                                <a href="index.php?page=create-topic" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Your First Topic
                                </a>
                            <?php else: ?>
                                <?php foreach ($recent_topics as $topic): ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="index.php?page=topic&id=<?php echo $topic['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($topic['title']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                in <?php echo htmlspecialchars($topic['category_name']); ?> • 
                                                <?php echo time_ago($topic['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted me-3">
                                                <i class="fas fa-eye me-1"></i><?php echo $topic['views']; ?> views
                                            </small>
                                            <span class="badge bg-<?php echo $topic['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($topic['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>