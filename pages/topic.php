<?php
/**
 * Forum Project - Topic View Page
 * Free Hosting Optimized
 */

$topic_id = $_GET['id'] ?? null;

if (!$topic_id) {
    header('Location: index.php');
    exit;
}

$topic = get_topic_by_id($pdo, $topic_id);

if (!$topic) {
    header('Location: index.php');
    exit;
}

// Update views
update_topic_views($pdo, $topic_id);

// Get replies
$replies = get_replies($pdo, $topic_id);

$error = '';
$success = '';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    if (!is_logged_in()) {
        $error = 'Please login to reply.';
    } else {
        $content = sanitize_input($_POST['content'] ?? '');
        
        if (empty($content)) {
            $error = 'Reply content is required.';
        } else {
            if (create_reply($pdo, $content, $_SESSION['user_id'], $topic_id)) {
                $success = 'Reply posted successfully!';
                // Refresh replies
                $replies = get_replies($pdo, $topic_id);
            } else {
                $error = 'Failed to post reply.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Forum Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .topic-header {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .reply-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .breadcrumb {
            background: transparent;
            padding: 0;
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
                        <a class="nav-link" href="index.php?page=category&id=<?php echo $topic['category_id']; ?>">
                            <?php echo htmlspecialchars($topic['category_name']); ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=register">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=category&id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_name']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($topic['title']); ?></li>
            </ol>
        </nav>

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

        <!-- Topic Header -->
        <div class="topic-header">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h1 class="display-6 mb-3"><?php echo htmlspecialchars($topic['title']); ?></h1>
                        <div class="d-flex align-items-center text-muted mb-3">
                            <i class="fas fa-user me-2"></i>
                            <span class="me-3">by <?php echo htmlspecialchars($topic['username']); ?></span>
                            <i class="fas fa-calendar me-2"></i>
                            <span class="me-3"><?php echo date('M j, Y g:i A', strtotime($topic['created_at'])); ?></span>
                            <i class="fas fa-eye me-2"></i>
                            <span><?php echo number_format($topic['views']); ?> views</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-<?php echo $topic['status'] === 'active' ? 'success' : 'secondary'; ?> fs-6">
                            <?php echo ucfirst($topic['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="topic-content mt-4">
                    <?php echo format_content($topic['content']); ?>
                </div>
            </div>
        </div>

        <!-- Replies Section -->
        <div class="row">
            <div class="col-md-8">
                <h4 class="mb-3">
                    <i class="fas fa-reply me-2"></i>Replies (<?php echo count($replies); ?>)
                </h4>

                <?php if (empty($replies)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No replies yet. Be the first to reply!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($replies as $reply): ?>
                        <div class="reply-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="public/images/default-avatar.png" alt="Avatar" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($reply['username']); ?></h6>
                                            <small class="text-muted"><?php echo time_ago($reply['created_at']); ?></small>
                                        </div>
                                    </div>
                                    <?php if (is_moderator()): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="reply-content">
                                    <?php echo format_content($reply['content']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Reply Form -->
                <?php if (is_logged_in()): ?>
                    <div class="reply-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-reply me-2"></i>Post a Reply</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" placeholder="Write your reply..." required></textarea>
                                </div>
                                <button type="submit" name="reply" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Post Reply
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="reply-card">
                        <div class="card-body text-center">
                            <p class="text-muted">Please <a href="index.php?page=login">login</a> to post a reply.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="reply-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Topic Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Category:</strong><br>
                            <a href="index.php?page=category&id=<?php echo $topic['category_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($topic['category_name']); ?>
                            </a>
                        </div>
                        <div class="mb-3">
                            <strong>Author:</strong><br>
                            <a href="index.php?page=profile&user=<?php echo $topic['user_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($topic['username']); ?>
                            </a>
                        </div>
                        <div class="mb-3">
                            <strong>Created:</strong><br>
                            <?php echo date('M j, Y g:i A', strtotime($topic['created_at'])); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Views:</strong><br>
                            <?php echo number_format($topic['views']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Replies:</strong><br>
                            <?php echo count($replies); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>