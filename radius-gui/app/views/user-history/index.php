<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="card card-custom">
    <div class="card-header">
        <i class="fas fa-history"></i> User Session History
    </div>
    <div class="card-body">
        <!-- Search Form -->
        <form method="GET" action="" class="row g-3 mb-4">
            <input type="hidden" name="page" value="user-history">

            <div class="col-md-4">
                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= Utils::e($username) ?>" placeholder="Enter username" required>
            </div>

            <div class="col-md-3">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date"
                       value="<?= Utils::e($fromDate) ?>">
            </div>

            <div class="col-md-3">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date"
                       value="<?= Utils::e($toDate) ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>

        <?php if (!empty($username) && $summary): ?>
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card info">
                        <p class="text-muted mb-1">Total Sessions</p>
                        <h4><?= number_format($summary['total_sessions']) ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card primary">
                        <p class="text-muted mb-1">Total Online Time</p>
                        <h4><?= Utils::formatDuration($summary['total_online_time']) ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card success">
                        <p class="text-muted mb-1">Download</p>
                        <h4><?= Utils::formatBytes($summary['total_download']) ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card warning">
                        <p class="text-muted mb-1">Upload</p>
                        <h4><?= Utils::formatBytes($summary['total_upload']) ?></h4>
                    </div>
                </div>
            </div>

            <!-- Export Button -->
            <div class="mb-3">
                <a href="?page=user-history&export=csv&username=<?= urlencode($username) ?>&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>"
                   class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>

            <!-- Sessions Table -->
            <div class="table-responsive">
                <?php if (empty($sessions)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No sessions found for user <strong><?= Utils::e($username) ?></strong>
                        between <?= $fromDate ?> and <?= $toDate ?>.
                    </div>
                <?php else: ?>
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Start Time</th>
                                <th>Stop Time</th>
                                <th>Duration</th>
                                <th>Device MAC</th>
                                <th>IP Address</th>
                                <th>NAS IP</th>
                                <th>Download</th>
                                <th>Upload</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><?= Utils::formatDate($session['acctstarttime']) ?></td>
                                    <td>
                                        <?php if ($session['acctstoptime']): ?>
                                            <?= Utils::formatDate($session['acctstoptime']) ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= Utils::formatDuration($session['acctsessiontime']) ?></td>
                                    <td><code><?= Utils::e($session['callingstationid'] ?? '-') ?></code></td>
                                    <td><?= Utils::e($session['framedipaddress'] ?? '-') ?></td>
                                    <td><?= Utils::e($session['nasipaddress']) ?></td>
                                    <td><?= Utils::formatBytes($session['acctinputoctets']) ?></td>
                                    <td><?= Utils::formatBytes($session['acctoutputoctets']) ?></td>
                                    <td><strong><?= Utils::formatBytes($session['total_bytes']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($username)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Enter a username to view session history.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
