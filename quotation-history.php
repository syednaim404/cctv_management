<?php
require_once 'dbcon.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection variable $conn was not found in dbcon.php.');
}
$conn->set_charset('utf8mb4');

$allowedStatuses = ['Active', 'Pending', 'Processing', 'Cancelled'];

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusTextClass($status) {
    switch ($status) {
        case 'Active': return 'status-active';
        case 'Pending': return 'status-pending';
        case 'Processing': return 'status-processing';
        case 'Cancelled': return 'status-cancelled';
        default: return 'status-default';
    }
}

function validDateOrNull($date) {
    if ($date === '' || $date === null) return null;
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) return false;
    return $date;
}

/* Bulk update: only changed rows are sent by JavaScript. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_update_followups') {
    header('Content-Type: application/json; charset=utf-8');

    $updates = json_decode($_POST['updates'] ?? '', true);
    if (!is_array($updates) || count($updates) < 1 || count($updates) > 100) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'No valid changes were received.']);
        exit;
    }

    $cleanUpdates = [];
    $seenIds = [];
    foreach ($updates as $item) {
        if (!is_array($item)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid update data.']);
            exit;
        }

        $id = isset($item['id']) ? (int)$item['id'] : 0;
        $status = trim((string)($item['status'] ?? ''));
        $comment = trim((string)($item['comment'] ?? ''));
        $dateInput = trim((string)($item['next_followup_date'] ?? ''));
        $date = validDateOrNull($dateInput);

        if ($id < 1 || isset($seenIds[$id]) || !in_array($status, $allowedStatuses, true) || $date === false) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'One or more invoice updates contain invalid information.']);
            exit;
        }
        $seenIds[$id] = true;
        $cleanUpdates[] = [
            'id' => $id,
            'status' => $status,
            'comment' => ($comment === '') ? null : $comment,
            'date' => $date
        ];
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE hd_camera_quotations SET status = ?, comment = ?, next_followup_date = ? WHERE id = ?');
        if (!$stmt) throw new Exception('Could not prepare the database update.');

        foreach ($cleanUpdates as $item) {
            $status = $item['status'];
            $comment = $item['comment'];
            $date = $item['date'];
            $id = $item['id'];
            $stmt->bind_param('sssi', $status, $comment, $date, $id);
            if (!$stmt->execute()) throw new Exception('Could not update invoice #' . $id . '.');
        }
        $stmt->close();
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => count($cleanUpdates) . ' invoice(s) updated successfully.',
            'updated_count' => count($cleanUpdates)
        ]);
    } catch (Exception $exception) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
    }
    exit;
}

/* Search and pagination */
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$whereSql = '';
$searchParam = '%' . $search . '%';
if ($search !== '') {
    $whereSql = 'WHERE invoice_no LIKE ? OR customer_name LIKE ? OR phone LIKE ?';
}

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM hd_camera_quotations $whereSql");
if ($search !== '') $countStmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$summaryResult = $conn->query("SELECT COUNT(*) AS total_quotations,
    COALESCE(SUM(grand_total), 0) AS total_value,
    COALESCE(SUM(status = 'Active'), 0) AS active_count,
    COALESCE(SUM(status = 'Pending'), 0) AS pending_count,
    COALESCE(SUM(status = 'Processing'), 0) AS processing_count,
    COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled_count
    FROM hd_camera_quotations");
$summary = $summaryResult ? $summaryResult->fetch_assoc() : [];

$listSql = "SELECT id, invoice_no, customer_name, phone, quotation_date, grand_total,
            COALESCE(status, 'Active') AS status, comment, next_followup_date
            FROM hd_camera_quotations $whereSql ORDER BY id DESC LIMIT ? OFFSET ?";
$listStmt = $conn->prepare($listSql);
if ($search !== '') {
    $listStmt->bind_param('sssii', $searchParam, $searchParam, $searchParam, $limit, $offset);
} else {
    $listStmt->bind_param('ii', $limit, $offset);
}
$listStmt->execute();
$quotations = $listStmt->get_result();

/* Dashboard reminders: overdue, today and next 7 days. Cancelled invoices are excluded. */
$reminderSql = "SELECT id, invoice_no, customer_name, phone, next_followup_date, comment,
                CASE
                    WHEN next_followup_date < CURDATE() THEN 'Overdue'
                    WHEN next_followup_date = CURDATE() THEN 'Today'
                    ELSE 'Upcoming'
                END AS reminder_type
                FROM hd_camera_quotations
                WHERE next_followup_date IS NOT NULL
                  AND next_followup_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND COALESCE(status, 'Active') <> 'Cancelled'
                ORDER BY next_followup_date ASC, id DESC";
$reminderResult = $conn->query($reminderSql);
$reminders = [];
$reminderCounts = ['Overdue' => 0, 'Today' => 0, 'Upcoming' => 0];
if ($reminderResult) {
    while ($reminder = $reminderResult->fetch_assoc()) {
        $reminders[] = $reminder;
        $reminderCounts[$reminder['reminder_type']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation History | CCTV Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f5f6fa; font-family:Arial,sans-serif; color:#212529; }
        .sidebar { min-height:100vh; background:#212529; color:#fff; }
        .sidebar .brand { font-size:20px; font-weight:700; padding:24px 20px; border-bottom:1px solid rgba(255,255,255,.12); }
        .sidebar a { display:block; color:#d8dce0; text-decoration:none; padding:12px 20px; }
        .sidebar a:hover,.sidebar a.active { color:#fff; background:#343a40; }
        .main-content { padding:28px; }
        .stat-card,.content-card,.reminder-card { background:#fff; border:0; border-radius:12px; box-shadow:0 3px 15px rgba(0,0,0,.06); }
        .stat-card { padding:18px; height:100%; }
        .stat-number { font-size:26px; font-weight:700; }
        .table thead th { background:#212529; color:#fff; white-space:nowrap; vertical-align:middle; font-size:13px; }
        .table td { vertical-align:middle; white-space:nowrap; }
        .status-select { min-width:132px; font-size:13px; font-weight:700; border-width:1px; }
        .status-active { color:#198754 !important; font-weight:700 !important; }
        .status-pending { color:#b58100 !important; font-weight:700 !important; }
        .status-processing { color:#0d6efd !important; font-weight:700 !important; }
        .status-cancelled { color:#dc3545 !important; font-weight:700 !important; }
        .status-default { color:#6c757d !important; }
        .comment-btn { width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center; position:relative; }
        .comment-dot { width:8px; height:8px; border-radius:50%; background:#198754; position:absolute; top:5px; right:5px; border:1px solid #fff; }
        .followup-small { font-size:12px; color:#6c757d; margin-top:4px; }
        .changed-row { background:#fffbea !important; }
        .reminder-item { border-left:4px solid #0d6efd; background:#fff; border-radius:8px; padding:12px 14px; }
        .reminder-item.overdue { border-left-color:#dc3545; }
        .reminder-item.today { border-left-color:#fd7e14; }
        .reminder-item.upcoming { border-left-color:#0d6efd; }
        .reminder-count { font-size:24px; font-weight:700; }
        @media(max-width:767px) { .main-content{padding:16px;} .sidebar{min-height:auto;} }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-2 p-0 sidebar">
            <div class="brand"><i class="bi bi-camera-video me-2"></i>CC Camera</div>
            <nav class="py-3">
                <a href="index.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
                <a href="hd-camera.php"><i class="bi bi-receipt me-2"></i>New Quotation</a>
                <a href="quotation-history.php" class="active"><i class="bi bi-clock-history me-2"></i>Quotation History</a>
            </nav>
        </aside>

        <main class="col-md-10 main-content">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Quotation History</h3>
                    <div class="text-secondary">Manage client status and follow-up reminders</div>
                </div>
                <a href="hd-camera.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Quotation</a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Total Quotations</div><div class="stat-number"><?= number_format((int)($summary['total_quotations'] ?? 0)) ?></div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Active</div><div class="stat-number status-active"><?= number_format((int)($summary['active_count'] ?? 0)) ?></div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Pending</div><div class="stat-number status-pending"><?= number_format((int)($summary['pending_count'] ?? 0)) ?></div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Processing</div><div class="stat-number status-processing"><?= number_format((int)($summary['processing_count'] ?? 0)) ?></div></div></div>
            </div>

            <!-- Dashboard follow-up reminders -->
            <div class="reminder-card p-3 p-lg-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-bell me-2 text-warning"></i>Follow-up Reminders</h5>
                    <span class="small text-secondary">Today and next 7 days</span>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-4"><div class="border rounded-3 p-3 h-100"><div class="small text-secondary">Overdue</div><div class="reminder-count text-danger"><?= (int)$reminderCounts['Overdue'] ?></div></div></div>
                    <div class="col-4"><div class="border rounded-3 p-3 h-100"><div class="small text-secondary">Today</div><div class="reminder-count" style="color:#fd7e14"><?= (int)$reminderCounts['Today'] ?></div></div></div>
                    <div class="col-4"><div class="border rounded-3 p-3 h-100"><div class="small text-secondary">Upcoming</div><div class="reminder-count text-primary"><?= (int)$reminderCounts['Upcoming'] ?></div></div></div>
                </div>
                <?php if (count($reminders) > 0): ?>
                    <div class="d-grid gap-2">
                        <?php foreach ($reminders as $reminder): ?>
                            <?php $typeClass = strtolower($reminder['reminder_type']); ?>
                            <div class="reminder-item <?= e($typeClass) ?>">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= e($reminder['customer_name'] ?: 'Customer') ?> <span class="text-secondary fw-normal">(<?= e($reminder['invoice_no']) ?>)</span></div>
                                        <div class="small text-secondary"><i class="bi bi-telephone me-1"></i><?= e($reminder['phone'] ?: 'No phone number') ?></div>
                                        <?php if (!empty($reminder['comment'])): ?><div class="small mt-1"><?= e($reminder['comment']) ?></div><?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge <?= $typeClass === 'overdue' ? 'text-bg-danger' : ($typeClass === 'today' ? 'text-bg-warning' : 'text-bg-primary') ?>"><?= e($reminder['reminder_type']) ?></span>
                                        <div class="small text-secondary mt-1"><?= date('d M Y', strtotime($reminder['next_followup_date'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-secondary py-3"><i class="bi bi-calendar-check fs-3 d-block mb-2"></i>No follow-up reminders for today or the next 7 days.</div>
                <?php endif; ?>
            </div>

            <div class="content-card p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h5 class="fw-bold mb-0">All Quotations</h5>
                    <form method="GET" class="d-flex gap-2" style="max-width:390px;width:100%;">
                        <input type="search" name="search" class="form-control" placeholder="Search invoice, customer or phone..." value="<?= e($search) ?>">
                        <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
                        <?php if ($search !== ''): ?><a href="quotation-history.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                    </form>
                </div>

                <!-- Small bulk update button aligned to the right, outside the modal -->
                <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
                    <span id="pendingChangesText" class="small text-secondary">No unsaved changes</span>
                    <button type="button" id="updateAllBtn" class="btn btn-success btn-sm" disabled>
                        <i class="bi bi-save me-1"></i> Update All
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" id="quotationTable">
                        <thead><tr>
                            <th>Invoice Number</th><th>Customer Name</th><th>Phone</th><th>Date</th>
                            <th class="text-end">Grand Total</th><th>Status</th><th>Comment / Follow-up</th>
                        </tr></thead>
                        <tbody>
                        <?php if ($quotations->num_rows > 0): ?>
                            <?php while ($row = $quotations->fetch_assoc()): ?>
                                <?php
                                    $currentStatus = $row['status'] ?: 'Active';
                                    $hasComment = trim((string)$row['comment']) !== '';
                                    $dateValue = $row['next_followup_date'] ?? '';
                                ?>
                                <tr data-id="<?= (int)$row['id'] ?>"
                                    data-invoice="<?= e($row['invoice_no']) ?>"
                                    data-original-status="<?= e($currentStatus) ?>"
                                    data-original-comment="<?= e($row['comment']) ?>"
                                    data-original-followup="<?= e($dateValue) ?>"
                                    data-comment="<?= e($row['comment']) ?>"
                                    data-followup="<?= e($dateValue) ?>">
                                    <td class="fw-semibold"><?= e($row['invoice_no']) ?></td>
                                    <td><?= e($row['customer_name']) ?></td>
                                    <td><?= e($row['phone']) ?></td>
                                    <td><?= $row['quotation_date'] ? date('d M Y', strtotime($row['quotation_date'])) : '-' ?></td>
                                    <td class="text-end fw-semibold">৳<?= number_format((float)$row['grand_total'], 2) ?></td>
                                    <td>
                                        <select class="form-select form-select-sm status-select row-status <?= e(statusTextClass($currentStatus)) ?>">
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option value="<?= e($status) ?>" <?= $currentStatus === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary comment-btn open-comment" title="Add or edit comment">
                                            <i class="bi bi-chat-left-text"></i><?php if ($hasComment): ?><span class="comment-dot"></span><?php endif; ?>
                                        </button>
                                        <div class="followup-small"><?= $dateValue ? 'Follow-up: ' . date('d M Y', strtotime($dateValue)) : 'No follow-up date' ?></div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-secondary py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No quotation found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4"><ul class="pagination justify-content-end mb-0">
                        <?php $queryBase = 'quotation-history.php?search=' . urlencode($search) . '&page='; ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($queryBase . max(1, $page - 1)) ?>">Previous</a></li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?><li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($queryBase . $p) ?>"><?= $p ?></a></li><?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($queryBase . min($totalPages, $page + 1)) ?>">Next</a></li>
                    </ul></nav>
                <?php endif; ?>
            </div>

            <div class="text-secondary small mt-3">Total quotation value: <strong>৳<?= number_format((float)($summary['total_value'] ?? 0), 2) ?></strong><span class="mx-2">|</span>Cancelled: <strong><?= number_format((int)($summary['cancelled_count'] ?? 0)) ?></strong></div>
        </main>
    </div>
</div>

<!-- Comment editor: changes are staged on the row only; database is not updated here. -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <form id="commentForm">
            <div class="modal-header bg-dark text-white">
                <div><h5 class="modal-title mb-0">Client Follow-up</h5><small id="modalInvoice" class="text-white-50"></small></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="modalComment" class="form-label fw-semibold">Comment / Client Note</label>
                <textarea class="form-control mb-3" id="modalComment" rows="4" placeholder="Write what the client said..."></textarea>
                <label for="modalFollowup" class="form-label fw-semibold">Next Follow-up Date</label>
                <input type="date" class="form-control" id="modalFollowup">
                <div class="form-text">These changes will be saved to the page first. Click Update All to save them to the database.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Save to Row</button>
            </div>
        </form>
    </div></div>
</div>

<div class="position-fixed top-0 end-0 p-3" style="z-index:1100">
    <div id="toastMessage" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex"><div class="toast-body" id="toastText">Updated successfully.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalElement = document.getElementById('commentModal');
const commentModal = new bootstrap.Modal(modalElement);
const commentForm = document.getElementById('commentForm');
const updateAllBtn = document.getElementById('updateAllBtn');
const pendingChangesText = document.getElementById('pendingChangesText');
let activeRow = null;

function showToast(message, isError = false) {
    const toastElement = document.getElementById('toastMessage');
    document.getElementById('toastText').textContent = message;
    toastElement.classList.toggle('text-bg-danger', isError);
    toastElement.classList.toggle('text-bg-success', !isError);
    bootstrap.Toast.getOrCreateInstance(toastElement).show();
}

function statusClass(status) {
    const classes = {
        Active: 'status-active',
        Pending: 'status-pending',
        Processing: 'status-processing',
        Cancelled: 'status-cancelled'
    };
    return classes[status] || 'status-default';
}

function updateStatusColor(select) {
    select.classList.remove('status-active', 'status-pending', 'status-processing', 'status-cancelled', 'status-default');
    select.classList.add(statusClass(select.value));
}

function rowHasChanges(row) {
    const status = row.querySelector('.row-status').value;
    return status !== row.dataset.originalStatus ||
        (row.dataset.comment || '') !== (row.dataset.originalComment || '') ||
        (row.dataset.followup || '') !== (row.dataset.originalFollowup || '');
}

function refreshDirtyState() {
    const rows = Array.from(document.querySelectorAll('#quotationTable tbody tr[data-id]'));
    let changedCount = 0;
    rows.forEach(row => {
        const changed = rowHasChanges(row);
        row.classList.toggle('changed-row', changed);
        if (changed) changedCount++;
    });
    updateAllBtn.disabled = changedCount === 0;
    pendingChangesText.textContent = changedCount ? changedCount + ' unsaved invoice change(s)' : 'No unsaved changes';
}

function updateCommentDisplay(row) {
    const button = row.querySelector('.open-comment');
    const existingDot = button.querySelector('.comment-dot');
    if ((row.dataset.comment || '').trim() && !existingDot) {
        const dot = document.createElement('span');
        dot.className = 'comment-dot';
        button.appendChild(dot);
    } else if (!(row.dataset.comment || '').trim() && existingDot) {
        existingDot.remove();
    }
    const followupText = row.querySelector('.followup-small');
    if (row.dataset.followup) {
        const date = new Date(row.dataset.followup + 'T00:00:00');
        followupText.textContent = 'Follow-up: ' + date.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
    } else {
        followupText.textContent = 'No follow-up date';
    }
}

document.querySelectorAll('.row-status').forEach(select => {
    updateStatusColor(select);
    select.addEventListener('change', function () {
        updateStatusColor(this);
        refreshDirtyState();
    });
});

document.querySelectorAll('.open-comment').forEach(button => {
    button.addEventListener('click', function () {
        activeRow = this.closest('tr');
        document.getElementById('modalInvoice').textContent = 'Invoice: ' + activeRow.dataset.invoice;
        document.getElementById('modalComment').value = activeRow.dataset.comment || '';
        document.getElementById('modalFollowup').value = activeRow.dataset.followup || '';
        commentModal.show();
    });
});

commentForm.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!activeRow) return;
    activeRow.dataset.comment = document.getElementById('modalComment').value.trim();
    activeRow.dataset.followup = document.getElementById('modalFollowup').value;
    updateCommentDisplay(activeRow);
    refreshDirtyState();
    commentModal.hide();
    showToast('Changes added to this invoice. Click Update All to save to database.');
});

updateAllBtn.addEventListener('click', async function () {
    const changedRows = Array.from(document.querySelectorAll('#quotationTable tbody tr[data-id]')).filter(rowHasChanges);
    if (!changedRows.length) return;

    const updates = changedRows.map(row => ({
        id: parseInt(row.dataset.id, 10),
        status: row.querySelector('.row-status').value,
        comment: row.dataset.comment || '',
        next_followup_date: row.dataset.followup || ''
    }));

    const originalButtonHtml = updateAllBtn.innerHTML;
    updateAllBtn.disabled = true;
    updateAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving';

    try {
        const formData = new FormData();
        formData.append('action', 'bulk_update_followups');
        formData.append('updates', JSON.stringify(updates));
        const response = await fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Could not save changes.');

        changedRows.forEach(row => {
            row.dataset.originalStatus = row.querySelector('.row-status').value;
            row.dataset.originalComment = row.dataset.comment || '';
            row.dataset.originalFollowup = row.dataset.followup || '';
        });
        refreshDirtyState();
        showToast(result.message || 'All changes saved successfully.');
    } catch (error) {
        showToast(error.message || 'An error occurred while saving.', true);
    } finally {
        updateAllBtn.innerHTML = originalButtonHtml;
        refreshDirtyState();
    }
});

refreshDirtyState();
</script>
</body>
</html>
<?php
$listStmt->close();
?>
