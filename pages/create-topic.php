<?php
/**
 * Forum Project - Create Topic Page
 * Free Hosting Optimized
 */

if (!is_logged_in()) {
    header('Location: index.php?page=login');
    exit;
}

$category_id = $_GET['category'] ?? null;
$error = '';
$success = '';

// Get categories
$categories = get_categories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $content = sanitize_input($_POST['content'] ?? '');
    $selected_category_id = (int)($_POST['category_id'] ?? 0);
    
    if (empty($title) || empty($content) || empty($selected_category_id)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($title) < 5) {
        $error = 'Title must be at least 5 characters long.';
    } elseif (strlen($content) < 10) {
        $error = 'Content must be at least 10 characters long.';
    } else {
        if (create_topic($pdo, $title, $content, $_SESSION['user_id'], $selected_category_id)) {
            $topic_id = $pdo->lastInsertId();
            handle_success('Topic created successfully!', "index.php?page=topic&id={$topic_id}");
        } else {
            $error = 'Failed to create topic.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Topic - Forum Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .create-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
                        <a class="nav-link active" href="index.php?page=create-topic">Create Topic</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
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
                </ul>
            </div>
        </div>
    </nav>

    <!-- Create Header -->
    <div class="create-header">
        <div class="container text-center">
            <h1 class="display-4 mb-3">Create New Topic</h1>
            <p class="lead">Start a new discussion in our community</p>
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

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="form-card">
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Topic Title</label>
                                <input type="text" class="form-control form-control-lg" name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                       placeholder="Enter a descriptive title for your topic" required>
                                <div class="form-text">Make your title clear and descriptive to help others understand your topic.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Category</label>
                                <select class="form-select form-select-lg" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($category_id == $category['id'] || ($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the most appropriate category for your topic.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Content</label>
                                <textarea class="form-control" name="content" rows="10" 
                                          placeholder="Write your topic content here..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <strong>Tips for great content:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Be clear and specific about your question or topic</li>
                                        <li>Provide relevant details and context</li>
                                        <li>Use proper formatting and paragraphs</li>
                                        <li>Be respectful and constructive</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Create Topic
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Guidelines -->
                <div class="form-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Community Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success"><i class="fas fa-check me-2"></i>Do:</h6>
                                <ul class="text-muted">
                                    <li>Be respectful and constructive</li>
                                    <li>Use clear and descriptive titles</li>
                                    <li>Search before creating duplicate topics</li>
                                    <li>Stay on topic and relevant</li>
                                    <li>Help others when you can</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger"><i class="fas fa-times me-2"></i>Don't:</h6>
                                <ul class="text-muted">
                                    <li>Post spam or irrelevant content</li>
                                    <li>Use offensive language</li>
                                    <li>Create duplicate topics</li>
                                    <li>Post personal information</li>
                                    <li>Engage in trolling or harassment</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>