<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="h3"><i class="fas fa-cog"></i> Settings</h1>
    </div>

    <div class="row">
        <!-- Database Information -->
        <div class="col-lg-6 mb-4">
            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-database"></i> Database Information
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Total Authentication Records</strong></td>
                            <td><?= number_format($dbStats['total_auth_records'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Accounting Records</strong></td>
                            <td><?= number_format($dbStats['total_acct_records'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Operators</strong></td>
                            <td><?= number_format($dbStats['total_operators'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total NAS Devices</strong></td>
                            <td><?= number_format($dbStats['total_nas'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Database Size</strong></td>
                            <td><?= Utils::formatBytes($dbStats['database_size'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Oldest Record</strong></td>
                            <td><?= isset($dbStats['oldest_auth_date']) ? Utils::formatDate($dbStats['oldest_auth_date']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Newest Record</strong></td>
                            <td><?= isset($dbStats['newest_auth_date']) ? Utils::formatDate($dbStats['newest_auth_date']) : 'N/A' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Application Information -->
        <div class="col-lg-6 mb-4">
            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Application Information
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Application Name</strong></td>
                            <td><?= htmlspecialchars($appConfig['name'] ?? 'RADIUS Dashboard') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Version</strong></td>
                            <td><?= htmlspecialchars($appConfig['version'] ?? '1.0.0') ?></td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version</strong></td>
                            <td><?= phpversion() ?></td>
                        </tr>
                        <tr>
                            <td><strong>Server Time</strong></td>
                            <td><?= date('Y-m-d H:i:s') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Timezone</strong></td>
                            <td><?= date_default_timezone_get() ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
