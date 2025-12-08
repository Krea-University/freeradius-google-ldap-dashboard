<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-alt"></i> Monthly Usage Summary - <?= $month ?></span>
                    <a href="?page=reports&action=monthly-usage&export=pdf&month=<?= $month ?>" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card info">
                                <p class="text-muted mb-1">Total Sessions</p>
                                <h4><?= number_format($totalSessions) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card primary">
                                <p class="text-muted mb-1">Unique Users</p>
                                <h4><?= number_format($uniqueUsers) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card success">
                                <p class="text-muted mb-1">Total Online Time</p>
                                <h4><?= Utils::formatDuration($totalOnlineTime) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card warning">
                                <p class="text-muted mb-1">Total Data Used</p>
                                <h4><?= Utils::formatBytes($totalData) ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Breakdown Chart -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Daily Breakdown
                        </div>
                        <div class="card-body">
                            <canvas id="dailyChart" height="60"></canvas>
                        </div>
                    </div>

                    <!-- Daily Table -->
                    <div class="table-responsive mt-3">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Sessions</th>
                                    <th>Unique Users</th>
                                    <th>Online Time</th>
                                    <th>Data Used</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dailyData as $day): ?>
                                    <tr>
                                        <td><?= Utils::formatDate($day['date']) ?></td>
                                        <td><?= $day['total_sessions'] ?></td>
                                        <td><?= $day['unique_users'] ?></td>
                                        <td><?= Utils::formatDuration($day['total_online_time']) ?></td>
                                        <td><?= Utils::formatBytes($day['total_data']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('dailyChart').getContext('2d');
const dates = <?= json_encode(array_column($dailyData, 'date')) ?>;
const sessions = <?= json_encode(array_column($dailyData, 'total_sessions')) ?>;
const users = <?= json_encode(array_column($dailyData, 'unique_users')) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: dates.map(d => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        datasets: [
            {
                label: 'Sessions',
                data: sessions,
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                yAxisID: 'y'
            },
            {
                label: 'Unique Users',
                data: users,
                borderColor: 'rgba(28, 183, 87, 1)',
                backgroundColor: 'rgba(28, 183, 87, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Sessions'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Unique Users'
                },
                grid: {
                    drawOnChartArea: false,
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
