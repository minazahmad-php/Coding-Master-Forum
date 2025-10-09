<?php
/**
 * Forum Project - Category View Page
 * Free Hosting Optimized
 */

$category_id = $_GET['id'] ?? null;

if (!$category_id) {
    header('Location: index.php');
    exit;
}

$category = get_category_by_id($pdo, $category_id);

if (!$category) {
    header('Location: index.php');
    exit;
}

// Get current page
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get topics in this category
$topics = get_topics($pdo, $category_id, $limit, $offset);

// Get total topics for pagination
$total_topics = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE category_id = ? AND status = 'active'");
$total_topics->execute([$category_id]);
$total_topics = $total_topics->fetchColumn();

$total_pages = ceil($total_topics / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Forum Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .topic-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }
        .topic-item:hover {
            transform: translateY(-2px);
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
                        <a class="nav-link active" href="index.php?page=category&id=<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
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

    <!-- Category Header -->
    <div class="category-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-2"><?php echo htmlspecialchars($category['name']); ?></h1>
                    <p class="lead mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (is_logged_in()): ?>
                        <a href="index.php?page=create-topic&category=<?php echo $category['id']; ?>" class="btn btn-light btn-lg">
                            <i class="fas fa-plus me-2"></i>New Topic
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($category['name']); ?></li>
            </ol>
        </nav>

        <!-- Topics List -->
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Topics (<?php echo number_format($total_topics); ?>)</h4>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm active">Latest</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm">Most Popular</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm">Most Replies</button>
                    </div>
                </div>

                <?php if (empty($topics)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash text-muted" style="font-size: 4rem;"></i>
                        <h4 class="text-muted mt-3">No topics yet</h4>
                        <p class="text-muted">Be the first to start a discussion in this category!</p>
                        <?php if (is_logged_in()): ?>
                            <a href="index.php?page=create-topic&category=<?php echo $category['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Topic
                            </a>
                        <?php else: ?>
                            <a href="index.php?page=login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Create Topic
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <div class="topic-item">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">
                                            <a href="index.php?page=topic&id=<?php echo $topic['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($topic['title']); ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex align-items-center text-muted">
                                            <i class="fas fa-user me-2"></i>
                                            <span class="me-3">by <?php echo htmlspecialchars($topic['username']); ?></span>
                                            <i class="fas fa-calendar me-2"></i>
                                            <span class="me-3"><?php echo time_ago($topic['created_at']); ?></span>
                                            <i class="fas fa-eye me-2"></i>
                                            <span><?php echo number_format($topic['views']); ?> views</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="d-flex justify-content-end">
                                            <div class="text-center me-3">
                                                <div class="h5 mb-0 text-primary">
                                                    <?php
                                                    $reply_count = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE topic_id = ? AND status = 'active'");
                                                    $reply_count->execute([$topic['id']]);
                                                    echo $reply_count->fetchColumn();
                                                    ?>
                                                </div>
                                                <small class="text-muted">replies</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="h5 mb-0 text-success"><?php echo number_format($topic['views']); ?></div>
                                                <small class="text-muted">views</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Topics pagination" class="mt-4">
                            <?php echo paginate($page, $total_pages, "index.php?page=category&id={$category_id}"); ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="topic-item">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Category Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Description:</strong><br>
                            <?php echo htmlspecialchars($category['description']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Total Topics:</strong><br>
                            <?php echo number_format($total_topics); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($category['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (is_logged_in()): ?>
                    <div class="topic-item">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <a href="index.php?page=create-topic&category=<?php echo $category['id']; ?>" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-plus me-2"></i>New Topic
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>