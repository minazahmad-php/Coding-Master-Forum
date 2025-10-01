<?php

//views/header.php

$currentUser = Auth::getUser();
$unreadMessages = $currentUser ? (new Message())->getUnreadCount($currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/my_forum/public/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active('/'); ?>" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active('/forums'); ?>" href="/forums">Forums</a>
                </li>
                <?php if ($currentUser): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active('/messages'); ?>" href="/messages">
                        Messages
                        <?php if ($unreadMessages > 0): ?>
                        <span class="badge bg-danger"><?php echo $unreadMessages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($currentUser): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <img src="<?php echo get_gravatar($currentUser['email'], 30); ?>" class="rounded-circle me-1" alt="Avatar">
                        <?php echo $currentUser['username']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/user/profile">Profile</a></li>
                        <li><a class="dropdown-item" href="/user/dashboard">Dashboard</a></li>
                        <li><a class="dropdown-item" href="/messages">Messages</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'moderator'): ?>
                        <li><a class="dropdown-item" href="/admin">Admin Panel</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="/logout">Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active('/login'); ?>" href="/login">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active('/register'); ?>" href="/register">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>