<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3"><i class="fas fa-chart-line"></i> Dashboard</h1>
            <p class="text-muted">System Overview & Statistics</p>
        </div>
        <small class="text-muted">Last updated: <span id="last-updated"><?= date('H:i:s') ?></span></small>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <!-- Total Users -->
        <div class="col-md-3 mb-3">
            <div class="card card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Users</p>
                            <h2 class="mb-0"><?= $totalUsers['count'] ?? 0 ?></h2>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Online Users -->
        <div class="col-md-3 mb-3">
            <div class="card card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Online Now</p>
                            <h2 class="mb-0"><?= $onlineUsers['count'] ?? 0 ?></h2>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="fas fa-wifi"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auth Attempts Today -->
        <div class="col-md-3 mb-3">
            <div class="card card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Auth Attempts (Today)</p>
                            <h2 class="mb-0"><?= $authToday['count'] ?? 0 ?></h2>
                        </div>
                        <div class="text-info" style="font-size: 2rem;">
                            <i class="fas fa-key"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Auths Today -->
        <div class="col-md-3 mb-3">
            <div class="card card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Failed Auths (Today)</p>
                            <h2 class="mb-0"><?= $authFailedToday['count'] ?? 0 ?></h2>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Users -->
        <div class="col-lg-8 mb-4">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-star"></i> Top Users (Today)</span>
                    <a href="index.php?page=user-history" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($topUsers)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> No user activity today.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Sessions</th>
                                        <th>Total Time</th>
                                        <th>Download</th>
                                        <th>Upload</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?= Utils::e($user['username']) ?></strong>
                                            </td>
                                            <td><?= $user['session_count'] ?></td>
                                            <td><?= Utils::formatDuration($user['total_time']) ?></td>
                                            <td><?= Utils::formatBytes($user['input_bytes']) ?></td>
                                            <td><?= Utils::formatBytes($user['output_bytes']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom">
                <div class="card-header">
                    <span><i class="fas fa-heartbeat"></i> System Health</span>
                </div>
                <div class="card-body">
                    <div class="health-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>RADIUS Server</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="health-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Database</span>
                            <span class="badge bg-success">Connected</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Last Sync</span>
                            <small class="text-muted"><?= date('H:i:s') ?></small>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Failed Authentications -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-triangle"></i> Recent Failed Authentications</span>
                    <a href="index.php?page=auth-log" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentFailures)): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> No authentication failures today.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentFailures as $failure): ?>
                                        <tr>
                                            <td><?= Utils::e($failure['username']) ?></td>
                                            <td>
                                                <span class="badge bg-danger">Rejected</span>
                                            </td>
                                            <td><?= Utils::formatDate($failure['authdate']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
