<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .setup-card {
            transition: transform 0.2s;
        }
        .setup-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h1 class="display-4">
                        <i class="fas fa-database text-primary me-3"></i>
                        Database Setup
                    </h1>
                    <p class="lead text-muted">Choose the appropriate setup option for your forum</p>
                </div>
                
                <div class="row g-4">
                    <!-- Test Database -->
                    <div class="col-md-6">
                        <div class="card setup-card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-stethoscope fa-3x text-info"></i>
                                </div>
                                <h5 class="card-title">Test Database</h5>
                                <p class="card-text">Test your database connection and check existing tables.</p>
                                <a href="test-db.php" class="btn btn-info">
                                    <i class="fas fa-play me-2"></i>
                                    Test Database
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Migration -->
                    <div class="col-md-6">
                        <div class="card setup-card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-tools fa-3x text-warning"></i>
                                </div>
                                <h5 class="card-title">Basic Migration</h5>
                                <p class="card-text">Run basic database migration with core tables only.</p>
                                <a href="migrate.php" class="btn btn-warning">
                                    <i class="fas fa-database me-2"></i>
                                    Run Migration
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Complete Setup -->
                    <div class="col-md-12">
                        <div class="card setup-card border-success">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-rocket fa-3x text-success"></i>
                                </div>
                                <h5 class="card-title text-success">Complete Database Setup</h5>
                                <p class="card-text">Recommended: Complete setup with all tables, indexes, and sample data.</p>
                                <div class="row">
                                    <div class="col-md-8 mx-auto">
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-check-circle me-2"></i>Includes:</h6>
                                            <ul class="list-unstyled mb-0">
                                                <li>✓ All core tables (users, posts, comments, categories)</li>
                                                <li>✓ Analytics tables (user activities, page views, engagement)</li>
                                                <li>✓ Security tables (security logs, blocked IPs, API keys)</li>
                                                <li>✓ Integration tables (email logs, SMS logs, cloud storage)</li>
                                                <li>✓ Advanced feature tables (themes, plugins, payments, languages)</li>
                                                <li>✓ Database indexes for optimal performance</li>
                                                <li>✓ Sample data (categories, languages, users, posts)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <a href="setup-db.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-rocket me-2"></i>
                                    Run Complete Setup
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Setup Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Default Users Created:</h6>
                                        <ul>
                                            <li><strong>Admin:</strong> admin / admin123</li>
                                            <li><strong>User:</strong> john_doe / user123</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Database Location:</h6>
                                        <p class="mb-0"><code><?= STORAGE_PATH ?>/forum.sqlite</code></p>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Next Steps:</h6>
                                        <ol>
                                            <li>Run the complete database setup</li>
                                            <li>Configure your environment variables (.env file)</li>
                                            <li>Set up your web server</li>
                                            <li>Access your forum at the configured URL</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="/" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>
                        Go to Forum
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>