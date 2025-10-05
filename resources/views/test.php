<?php $this->layout('layouts.app', ['title' => 'System Test']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>System Test Page</h2>
            <p class="text-muted">This page shows system information and status.</p>
            
            <div class="alert alert-warning">
                <strong>Warning:</strong> This page should only be accessible in debug mode.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">PHP Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?= $php_version ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Software:</strong></td>
                                    <td><?= $server_software ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Memory Usage:</strong></td>
                                    <td><?= number_format($memory_usage / 1024 / 1024, 2) ?> MB</td>
                                </tr>
                                <tr>
                                    <td><strong>Memory Peak:</strong></td>
                                    <td><?= number_format($memory_peak / 1024 / 1024, 2) ?> MB</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Application Status</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Database Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?= $database_status === 'connected' ? 'success' : 'danger' ?>">
                                            <?= $database_status ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Debug Mode:</strong></td>
                                    <td>
                                        <span class="badge bg-<?= $config['debug'] ? 'warning' : 'success' ?>">
                                            <?= $config['debug'] ? 'Enabled' : 'Disabled' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Environment:</strong></td>
                                    <td><?= $config['environment'] ?? 'production' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Version:</strong></td>
                                    <td><?= $config['version'] ?? '1.0.0' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Loaded Extensions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_chunk($loaded_extensions, 4) as $chunk): ?>
                            <div class="col-md-3">
                                <ul class="list-unstyled">
                                    <?php foreach ($chunk as $extension): ?>
                                        <li><span class="badge bg-secondary"><?= $extension ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Configuration</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3"><code><?= htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT)) ?></code></pre>
                </div>
            </div>
        </div>
    </div>
</div>