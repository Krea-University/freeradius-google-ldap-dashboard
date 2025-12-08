<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-heartbeat"></i> System Health Report</span>
                    <a href="?page=reports&action=system-health&export=pdf" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="card-body">
                    <!-- Database Statistics -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-database"></i> Database Statistics</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card info">
                                <p class="text-muted mb-1">Total Auth Records</p>
                                <h4><?= number_format($dbStats['total_auth_records']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card primary">
                                <p class="text-muted mb-1">Total Accounting</p>
                                <h4><?= number_format($dbStats['total_acct_records']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card success">
                                <p class="text-muted mb-1">Total Users</p>
                                <h4><?= number_format($dbStats['total_users']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card warning">
                                <p class="text-muted mb-1">Database Size</p>
                                <h4><?= Utils::formatBytes($dbStats['database_size']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card secondary">
                                <p class="text-muted mb-1">Operators</p>
                                <h4><?= number_format($dbStats['total_operators']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card info">
                                <p class="text-muted mb-1">NAS Devices</p>
                                <h4><?= number_format($dbStats['total_nas']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card success">
                                <p class="text-muted mb-1">Online Sessions</p>
                                <h4><?= number_format($dbStats['online_sessions']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card primary">
                                <p class="text-muted mb-1">Data Retention</p>
                                <h4>
                                    <?php
                                    if ($dbStats['oldest_auth_date'] && $dbStats['newest_auth_date']) {
                                        $diff = strtotime($dbStats['newest_auth_date']) - strtotime($dbStats['oldest_auth_date']);
                                        echo round($diff / (60 * 60 * 24)) . ' days';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Authentication Statistics (Last 24 Hours) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-key"></i> Authentication Statistics (Last 24 Hours)</h5>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card info">
                                <p class="text-muted mb-1">Total Attempts</p>
                                <h4><?= number_format($authStats['total_attempts']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card success">
                                <p class="text-muted mb-1">Successful</p>
                                <h4><?= number_format($authStats['successful']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card danger">
                                <p class="text-muted mb-1">Failed</p>
                                <h4><?= number_format($authStats['failed']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card <?= $authStats['success_rate'] >= 90 ? 'success' : ($authStats['success_rate'] >= 70 ? 'warning' : 'danger') ?>">
                                <p class="text-muted mb-1">Success Rate</p>
                                <h4><?= number_format($authStats['success_rate'], 1) ?>%</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Error Breakdown (Last 24 Hours) -->
                    <?php if (!empty($errorBreakdown)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-exclamation-triangle"></i> Error Breakdown (Last 24 Hours)</h5>
                        </div>
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-hover data-table">
                                    <thead>
                                        <tr>
                                            <th>Error Type</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                            <th>Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalErrors = array_sum(array_column($errorBreakdown, 'count'));
                                        foreach ($errorBreakdown as $error):
                                            $percentage = ($error['count'] / $totalErrors) * 100;
                                        ?>
                                            <tr>
                                                <td><span class="badge bg-danger"><?= Utils::e($error['error_type']) ?></span></td>
                                                <td><?= number_format($error['count']) ?></td>
                                                <td><?= number_format($percentage, 1) ?>%</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $percentage ?>%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Performance Metrics (Last 7 Days) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-tachometer-alt"></i> Performance Metrics (Last 7 Days)</h5>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card info">
                                <p class="text-muted mb-1">Avg Session Duration</p>
                                <h4><?= Utils::formatDuration($performanceMetrics['avg_session_duration']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card primary">
                                <p class="text-muted mb-1">Avg Data Per Session</p>
                                <h4><?= Utils::formatBytes($performanceMetrics['avg_data_per_session']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stats-card success">
                                <p class="text-muted mb-1">Peak Concurrent Users</p>
                                <h4><?= number_format($performanceMetrics['peak_concurrent_users']) ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Top NAS Devices (Last 7 Days) -->
                    <?php if (!empty($topNasDevices)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-server"></i> Top NAS Devices (Last 7 Days)</h5>
                        </div>
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-hover data-table">
                                    <thead>
                                        <tr>
                                            <th>NAS IP</th>
                                            <th>Short Name</th>
                                            <th>Unique Users</th>
                                            <th>Total Sessions</th>
                                            <th>Total Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topNasDevices as $nas): ?>
                                            <tr>
                                                <td><?= Utils::e($nas['nasname']) ?></td>
                                                <td><span class="badge bg-primary"><?= Utils::e($nas['shortname']) ?></span></td>
                                                <td><?= number_format($nas['unique_users']) ?></td>
                                                <td><?= number_format($nas['total_sessions']) ?></td>
                                                <td><?= Utils::formatBytes($nas['total_data']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent System Alerts (Last Hour) -->
                    <?php if (!empty($recentAlerts)): ?>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-bell"></i> Recent System Alerts (Last Hour - 10+ Failures)</h5>
                        </div>
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-hover data-table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Error Type</th>
                                            <th>Failure Count</th>
                                            <th>Last Failure</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAlerts as $alert): ?>
                                            <tr class="table-danger">
                                                <td><strong><?= Utils::e($alert['username']) ?></strong></td>
                                                <td><span class="badge bg-danger"><?= Utils::e($alert['error_type']) ?></span></td>
                                                <td><strong><?= number_format($alert['failure_count']) ?></strong></td>
                                                <td><?= Utils::formatDate($alert['last_failure']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-bell"></i> Recent System Alerts</h5>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> No critical alerts in the last hour. System is running smoothly.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
