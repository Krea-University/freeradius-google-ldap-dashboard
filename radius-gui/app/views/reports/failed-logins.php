<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-triangle"></i> Failed Login Report</span>
                    <a href="?page=reports&action=failed-logins&export=pdf&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>&threshold=<?= $threshold ?>" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="" class="row g-3 mb-3">
                        <input type="hidden" name="page" value="reports">
                        <input type="hidden" name="action" value="failed-logins">

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

                        <div class="col-md-2">
                            <label for="threshold" class="form-label">Minimum Failures</label>
                            <input type="number" class="form-control" id="threshold" name="threshold"
                                   value="<?= Utils::e($threshold) ?>" min="1">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>

                        <div class="col-md-2 d-flex align-items-end justify-content-end">
                            <a href="?page=reports&action=failed-logins" class="btn btn-secondary w-100">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>

                    <!-- Results -->
                    <div class="table-responsive">
                        <?php if (empty($failedLogins)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No failed logins found with the selected criteria (threshold: <?= $threshold ?> or more failures).
                            </div>
                        <?php else: ?>
                            <table class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Error Type</th>
                                        <th>Failure Count</th>
                                        <th>First Failure</th>
                                        <th>Last Failure</th>
                                        <th>Time Range</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($failedLogins as $failed): ?>
                                        <tr>
                                            <td>
                                                <a href="?page=auth-log&username=<?= urlencode($failed['username']) ?>">
                                                    <?= Utils::e($failed['username']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (!empty($failed['error_type'])): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <?= Utils::e(ucwords(str_replace('_', ' ', $failed['error_type']))) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $failed['failure_count'] ?>
                                                </span>
                                            </td>
                                            <td><?= Utils::formatDate($failed['first_failure']) ?></td>
                                            <td><?= Utils::formatDate($failed['last_failure']) ?></td>
                                            <td>
                                                <?php
                                                $start = new DateTime($failed['first_failure']);
                                                $end = new DateTime($failed['last_failure']);
                                                $interval = $start->diff($end);
                                                if ($interval->days > 0) {
                                                    echo $interval->days . ' days';
                                                } else {
                                                    echo ($interval->h > 0 ? $interval->h . 'h ' : '') . $interval->i . 'min';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
