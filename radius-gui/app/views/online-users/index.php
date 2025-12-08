<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="card card-custom">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users"></i> Online Users</span>
        <span class="badge bg-success"><?= count($sessions) ?> Active</span>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="" class="row g-3 mb-3">
            <input type="hidden" name="page" value="online-users">

            <div class="col-md-4">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= Utils::e($username) ?>" placeholder="Search username...">
            </div>

            <div class="col-md-3">
                <label for="nas_ip" class="form-label">NAS IP</label>
                <select class="form-select" id="nas_ip" name="nas_ip">
                    <option value="">All NAS</option>
                    <?php foreach ($nasList as $nas): ?>
                        <option value="<?= Utils::e($nas['nasipaddress']) ?>"
                                <?= $nasIp === $nas['nasipaddress'] ? 'selected' : '' ?>>
                            <?= Utils::e($nas['nasipaddress']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="index.php?page=online-users" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                </a>
            </div>

            <div class="col-md-3 d-flex align-items-end justify-content-end">
                <a href="?page=online-users&export=csv<?= $username ? '&username=' . urlencode($username) : '' ?><?= $nasIp ? '&nas_ip=' . urlencode($nasIp) : '' ?>"
                   class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </form>

        <!-- Sessions Table -->
        <div class="table-responsive">
            <?php if (empty($sessions)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No active sessions found.
                </div>
            <?php else: ?>
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Device MAC</th>
                            <th>IP Address</th>
                            <th>NAS IP</th>
                            <th>WiFi Network</th>
                            <th>Session Start</th>
                            <th>Duration</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= Utils::e($session['username']) ?></td>
                                <td><code><?= Utils::e($session['device_mac'] ?? '-') ?></code></td>
                                <td><?= Utils::e($session['framedipaddress'] ?? '-') ?></td>
                                <td><?= Utils::e($session['nasipaddress']) ?></td>
                                <td><?= Utils::e($session['wifi_network'] ?? '-') ?></td>
                                <td><?= Utils::formatDate($session['acctstarttime']) ?></td>
                                <td><?= Utils::formatDuration($session['session_duration']) ?></td>
                                <td><?= Utils::formatBytes($session['acctinputoctets']) ?></td>
                                <td><?= Utils::formatBytes($session['acctoutputoctets']) ?></td>
                                <td><strong><?= Utils::formatBytes($session['total_bytes']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
