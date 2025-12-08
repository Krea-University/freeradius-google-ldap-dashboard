<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-day"></i> Daily Authentication Summary - <?= $date ?></span>
                    <a href="?page=reports&action=daily-auth&export=pdf&date=<?= $date ?>" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card info">
                                <p class="text-muted mb-1">Total Attempts</p>
                                <h4><?= number_format($stats['total_attempts'] ?? 0) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card success">
                                <p class="text-muted mb-1">Successful Logins</p>
                                <h4><?= number_format($stats['successful_logins'] ?? 0) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card danger">
                                <p class="text-muted mb-1">Failed Logins</p>
                                <h4><?= number_format($stats['failed_logins'] ?? 0) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card warning">
                                <p class="text-muted mb-1">Success Rate</p>
                                <h4><?= number_format($stats['success_rate'] ?? 0, 2) ?>%</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Hourly Breakdown Chart -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Hourly Breakdown
                        </div>
                        <div class="card-body">
                            <canvas id="hourlyChart" height="60"></canvas>
                        </div>
                    </div>

                    <!-- Hourly Table -->
                    <div class="table-responsive mt-3">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Total Attempts</th>
                                    <th>Successful</th>
                                    <th>Failed</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hourlyData as $hour): ?>
                                    <tr>
                                        <td><?= str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) ?>:00</td>
                                        <td><?= $hour['attempts'] ?></td>
                                        <td><span class="badge bg-success"><?= $hour['successful'] ?></span></td>
                                        <td><span class="badge bg-danger"><?= $hour['failed'] ?></span></td>
                                        <td>
                                            <?php $rate = $hour['attempts'] > 0 ? round(($hour['successful'] / $hour['attempts']) * 100, 2) : 0; ?>
                                            <?= $rate ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- VLAN Statistics -->
                    <?php if (!empty($vlanStats)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-network-wired"></i> VLAN Assignments
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>VLAN ID</th>
                                            <th>Authentications</th>
                                            <th>Unique Users</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalVlanAuths = array_sum(array_column($vlanStats, 'auth_count'));
                                        foreach ($vlanStats as $vlan):
                                            $percentage = $totalVlanAuths > 0 ? round(($vlan['auth_count'] / $totalVlanAuths) * 100, 2) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-network-wired"></i> <?= Utils::e($vlan['vlan']) ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($vlan['auth_count']) ?></td>
                                                <td><?= number_format($vlan['unique_users']) ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                             style="width: <?= $percentage ?>%;"
                                                             aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?= $percentage ?>%
                                                        </div>
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

                    <!-- Error Type Statistics -->
                    <?php if (!empty($errorStats)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle"></i> Failed Authentication Breakdown
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Error Type</th>
                                            <th>Count</th>
                                            <th>Affected Users</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalErrors = array_sum(array_column($errorStats, 'error_count'));
                                        foreach ($errorStats as $error):
                                            $percentage = $totalErrors > 0 ? round(($error['error_count'] / $totalErrors) * 100, 2) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <?= Utils::e(ucwords(str_replace('_', ' ', $error['error_type']))) ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($error['error_count']) ?></td>
                                                <td><?= number_format($error['affected_users']) ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-warning" role="progressbar"
                                                             style="width: <?= $percentage ?>%;"
                                                             aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?= $percentage ?>%
                                                        </div>
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
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('hourlyChart').getContext('2d');
const hourlyData = <?= json_encode(array_column($hourlyData, 'hour')) ?>;
const attempts = <?= json_encode(array_column($hourlyData, 'attempts')) ?>;
const successful = <?= json_encode(array_column($hourlyData, 'successful')) ?>;
const failed = <?= json_encode(array_column($hourlyData, 'failed')) ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: hourlyData.map(h => h.toString().padStart(2, '0') + ':00'),
        datasets: [
            {
                label: 'Successful',
                data: successful,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            },
            {
                label: 'Failed',
                data: failed,
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});
</script>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
