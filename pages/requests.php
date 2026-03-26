<?php
// pages/requests.php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pdo = getDB();
$filter = $_GET['filter'] ?? 'Pending';

$validFilters = ['Pending','Resolved','Dismissed','All'];
if (!in_array($filter, $validFilters)) $filter = 'Pending';

$where = $filter !== 'All' ? "WHERE status = " . $pdo->quote($filter) : '';
$requests = $pdo->query("SELECT * FROM update_requests $where ORDER BY created_at DESC")->fetchAll();

$counts = [];
foreach ($validFilters as $f) {
    $w = $f !== 'All' ? "WHERE status = " . $pdo->quote($f) : '';
    $counts[$f] = $pdo->query("SELECT COUNT(*) FROM update_requests $w")->fetchColumn();
}
?>

<div class="page-header">
    <div>
        <h2>Update Requests Inbox</h2>
        <p class="page-subtitle">Review and respond to staff update requests</p>
    </div>
</div>

<div class="filter-bar">
    <span class="filter-label">Filter:</span>
    <div class="filter-tabs">
        <?php foreach ($validFilters as $f): ?>
        <a href="?page=requests&filter=<?= $f ?>" class="filter-tab <?= $filter === $f ? 'active' : '' ?>">
            <?= $f ?>
            <span class="filter-count"><?= $counts[$f] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path></svg>
        <p>No <?= strtolower($filter) ?> requests.</p>
    </div>
</div>
<?php else: ?>
<div class="requests-list">
    <?php foreach ($requests as $req): ?>
    <div class="request-card <?= strtolower($req['status']) ?>">
        <div class="request-header">
            <div class="request-meta">
                <div class="req-avatar"><?= strtoupper(substr($req['requester_name'], 0, 1)) ?></div>
                <div>
                    <strong><?= htmlspecialchars($req['requester_name']) ?></strong>
                    <span class="req-type-badge"><?= htmlspecialchars($req['record_type']) ?></span>
                </div>
            </div>
            <div class="request-status-wrap">
                <span class="status-pill status-<?= strtolower($req['status']) ?>"><?= $req['status'] ?></span>
                <span class="req-time"><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></span>
            </div>
        </div>
        <div class="request-body">
            <div class="req-reference">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                Record: <strong><?= htmlspecialchars($req['record_reference']) ?></strong>
            </div>
            <div class="req-message"><?= nl2br(htmlspecialchars($req['message'])) ?></div>
        </div>
        <?php if ($req['status'] === 'Pending'): ?>
        <div class="request-footer">
            <form method="POST" action="actions/request.php" style="display:inline">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="id" value="<?= $req['id'] ?>">
                <button type="submit" class="btn btn-sm btn-primary">✓ Mark Resolved</button>
            </form>
            <form method="POST" action="actions/request.php" style="display:inline">
                <input type="hidden" name="action" value="dismiss">
                <input type="hidden" name="id" value="<?= $req['id'] ?>">
                <button type="submit" class="btn btn-sm btn-ghost">Dismiss</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
