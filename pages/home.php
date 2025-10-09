<?php
/**
 * Forum Project - Home Page
 * Free Hosting Optimized
 */

// Get categories
$categories = get_categories($pdo);

// Get recent topics
$recent_topics = get_topics($pdo, null, 10);

// Get total stats
$total_topics = $pdo->query("SELECT COUNT(*) FROM topics WHERE status = 'active'")->fetchColumn();
$total_replies = $pdo->query("SELECT COUNT(*) FROM replies WHERE status = 'active'")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Project - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 1rem;
        }
        .category-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .topic-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .topic-item:hover {
            transform: translateX(5px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=register">Register</a>
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
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-3">Welcome to Forum Project</h1>
            <p class="lead mb-4">Connect, discuss, and share ideas with our community</p>
            <a href="index.php?page=register" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus me-2"></i>Join Now
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Messages -->
        <?php display_messages(); ?>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-comments text-primary" style="font-size: 2rem;"></i>
                    <h3><?php echo number_format($total_topics); ?></h3>
                    <p class="text-muted">Topics</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-reply text-success" style="font-size: 2rem;"></i>
                    <h3><?php echo number_format($total_replies); ?></h3>
                    <p class="text-muted">Replies</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-users text-info" style="font-size: 2rem;"></i>
                    <h3><?php echo number_format($total_users); ?></h3>
                    <p class="text-muted">Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-clock text-warning" style="font-size: 2rem;"></i>
                    <h3><?php echo date('H:i'); ?></h3>
                    <p class="text-muted">Current Time</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Categories -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Categories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No categories available yet.</p>
                                <?php if (is_admin()): ?>
                                    <a href="index.php?page=admin" class="btn btn-primary">Create Categories</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="category-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">
                                                <a href="index.php?page=category&id=<?php echo $category['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <small class="text-muted">
                                                <?php
                                                $topic_count = $pdo->query("SELECT COUNT(*) FROM topics WHERE category_id = {$category['id']} AND status = 'active'")->fetchColumn();
                                                echo $topic_count . ' topics';
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Topics -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Topics</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_topics)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comment-slash text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2">No topics yet.</p>
                                <?php if (is_logged_in()): ?>
                                    <a href="index.php?page=create-topic" class="btn btn-primary btn-sm">Create First Topic</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_topics as $topic): ?>
                                <div class="topic-item">
                                    <h6 class="mb-1">
                                        <a href="index.php?page=topic&id=<?php echo $topic['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($topic['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        by <?php echo htmlspecialchars($topic['username']); ?> • 
                                        <?php echo time_ago($topic['created_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>