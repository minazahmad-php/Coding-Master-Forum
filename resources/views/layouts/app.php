<!DOCTYPE html>
<html lang="<?= config('app.locale', 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? e($title) . ' - ' : '' ?><?= config('app.name', 'My Forum') ?></title>
    <meta name="description" content="<?= isset($description) ? e($description) : 'A modern forum application' ?>">
    <meta name="keywords" content="<?= isset($keywords) ? e($keywords) : 'forum, discussion, community' ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= asset('build/css/app.css') ?>" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .navbar-brand { font-weight: bold; }
        .forum-card { transition: transform 0.2s; }
        .forum-card:hover { transform: translateY(-2px); }
        .post-content { line-height: 1.6; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; }
        .thread-meta { font-size: 0.9em; color: #6c757d; }
        .reaction-btn { border: none; background: none; color: #6c757d; }
        .reaction-btn:hover { color: #007bff; }
        .reaction-btn.active { color: #007bff; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/') ?>">
                <i class="fas fa-comments me-2"></i>
                <?= config('app.name', 'My Forum') ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/forums') ?>">Forums</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/members') ?>">Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/statistics') ?>">Statistics</a>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3" action="<?= url('/search') ?>" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search..." value="<?= e($_GET['q'] ?? '') ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <?php if ($user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?= asset('images/default-avatar.png') ?>" alt="Avatar" class="user-avatar me-1">
                                <?= e($user['display_name'] ?: $user['username']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= url('/profile/' . $user['id']) ?>">Profile</a></li>
                                <li><a class="dropdown-item" href="<?= url('/settings') ?>">Settings</a></li>
                                <li><a class="dropdown-item" href="<?= url('/messages') ?>">Messages</a></li>
                                <li><a class="dropdown-item" href="<?= url('/notifications') ?>">Notifications</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/admin') ?>">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= url('/logout') ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/login') ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/register') ?>">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (session()->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?= e(session()->flash('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?= e(session()->flash('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container my-4">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?= config('app.name', 'My Forum') ?></h5>
                    <p class="text-muted">A modern forum application built with PHP.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline">
                        <li class="list-inline-item"><a href="<?= url('/rules') ?>" class="text-muted">Rules</a></li>
                        <li class="list-inline-item"><a href="<?= url('/contact') ?>" class="text-muted">Contact</a></li>
                        <li class="list-inline-item"><a href="<?= url('/statistics') ?>" class="text-muted">Statistics</a></li>
                    </ul>
                    <p class="text-muted small">© <?= date('Y') ?> <?= config('app.name', 'My Forum') ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('build/js/app.js') ?>"></script>
</body>
</html>