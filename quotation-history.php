<?php
require_once 'dbcon.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection variable \$conn was not found in dbcon.php.");
}

$conn->set_charset("utf8mb4");

$allowedStatuses = ['Active', 'Pending', 'Processing', 'Cancelled'];

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusBadgeClass(string $status): string {
    return match ($status) {
        'Active' => 'text-bg-info',
        'Pending' => 'text-bg-warning',
        'Processing' => 'text-bg-primary',
        'Cancelled' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
}

/*
|--------------------------------------------------------------------------
| AJAX: update quotation status, comment and next follow-up date
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_followup') {
    header('Content-Type: application/json; charset=utf-8');

    $quotationId = filter_input(INPUT_POST, 'quotation_id', FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $nextFollowupDate = trim($_POST['next_followup_date'] ?? '');

    if (!$quotationId || !in_array($status, $allowedStatuses, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid invoice or status.']);
        exit;
    }

    if ($nextFollowupDate !== '') {
        $dateObject = DateTime::createFromFormat('Y-m-d', $nextFollowupDate);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $nextFollowupDate) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid follow-up date.']);
            exit;
        }
    } else {
        $nextFollowupDate = null;
    }

    $commentValue = ($comment === '') ? null : $comment;

    $update = $conn->prepare(
        "UPDATE hd_camera_quotations
         SET status = ?, comment = ?, next_followup_date = ?
         WHERE id = ?"
    );

    if (!$update) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not prepare database query.']);
        exit;
    }

    $update->bind_param('sssi', $status, $commentValue, $nextFollowupDate, $quotationId);

    if ($update->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Quotation follow-up updated successfully.',
            'status' => $status,
            'comment' => $commentValue,
            'next_followup_date' => $nextFollowupDate
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }

    $update->close();
    exit;
}

/*
|--------------------------------------------------------------------------
| Search and pagination
|--------------------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereSql = '';
$searchParam = '%' . $search . '%';

if ($search !== '') {
    $whereSql = "WHERE invoice_no LIKE ? OR customer_name LIKE ? OR phone LIKE ?";
}

$countSql = "SELECT COUNT(*) AS total FROM hd_camera_quotations $whereSql";
$countStmt = $conn->prepare($countSql);

if ($search !== '') {
    $countStmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
}
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $limit));

$summaryResult = $conn->query(
    "SELECT
        COUNT(*) AS total_quotations,
        COALESCE(SUM(grand_total), 0) AS total_value,
        SUM(status = 'Active') AS active_count,
        SUM(status = 'Pending') AS pending_count,
        SUM(status = 'Processing') AS processing_count,
        SUM(status = 'Cancelled') AS cancelled_count
     FROM hd_camera_quotations"
);
$summary = $summaryResult ? $summaryResult->fetch_assoc() : [];

$listSql = "SELECT id, invoice_no, customer_name, phone, quotation_date,
                   grand_total, status, comment, next_followup_date
            FROM hd_camera_quotations
            $whereSql
            ORDER BY id DESC
            LIMIT ? OFFSET ?";

$listStmt = $conn->prepare($listSql);

if ($search !== '') {
    $listStmt->bind_param('sssii', $searchParam, $searchParam, $searchParam, $limit, $offset);
} else {
    $listStmt->bind_param('ii', $limit, $offset);
}

$listStmt->execute();
$quotations = $listStmt->get_result();
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
        body {
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            color: #212529;
        }
        .sidebar {
            min-height: 100vh;
            background: #212529;
            color: #fff;
        }
        .sidebar .brand {
            font-size: 20px;
            font-weight: 700;
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .sidebar a {
            display: block;
            color: #d8dce0;
            text-decoration: none;
            padding: 12px 20px;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background: #343a40;
        }
        .main-content {
            padding: 28px;
        }
        .stat-card, .content-card {
            background: #fff;
            border: 0;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,.06);
        }
        .stat-card {
            padding: 18px;
            height: 100%;
        }
        .stat-number {
            font-size: 26px;
            font-weight: 700;
        }
        .table thead th {
            background: #212529;
            color: #fff;
            white-space: nowrap;
            vertical-align: middle;
            font-size: 13px;
        }
        .table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .status-select {
            min-width: 132px;
            font-size: 13px;
            font-weight: 600;
        }
        .comment-btn {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .comment-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #198754;
            position: absolute;
            top: 5px;
            right: 5px;
            border: 1px solid #fff;
        }
        .followup-small {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        @media (max-width: 767px) {
            .main-content { padding: 16px; }
            .sidebar { min-height: auto; }
        }
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
                    <div class="text-secondary">Manage client status and follow-up comments</div>
                </div>
                <a href="hd-camera.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New Quotation
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="text-secondary small">Total Quotations</div>
                        <div class="stat-number"><?= number_format((int)($summary['total_quotations'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="text-secondary small">Active</div>
                        <div class="stat-number text-info"><?= number_format((int)($summary['active_count'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="text-secondary small">Pending</div>
                        <div class="stat-number text-warning"><?= number_format((int)($summary['pending_count'] ?? 0)) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="text-secondary small">Processing</div>
                        <div class="stat-number text-primary"><?= number_format((int)($summary['processing_count'] ?? 0)) ?></div>
                    </div>
                </div>
            </div>

            <div class="content-card p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h5 class="fw-bold mb-0">All Quotations</h5>
                    <form method="GET" class="d-flex gap-2" style="max-width: 390px; width: 100%;">
                        <input type="search" name="search" class="form-control"
                               placeholder="Search invoice, customer or phone..."
                               value="<?= e($search) ?>">
                        <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
                        <?php if ($search !== ''): ?>
                            <a href="quotation-history.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Date</th>
                            <th class="text-end">Grand Total</th>
                            <th>Status</th>
                            <th>Comment</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($quotations->num_rows > 0): ?>
                            <?php while ($row = $quotations->fetch_assoc()): ?>
                                <?php
                                    $currentStatus = $row['status'] ?: 'Active';
                                    $hasComment = trim((string)$row['comment']) !== '';
                                ?>
                                <tr
                                    data-id="<?= (int)$row['id'] ?>"
                                    data-invoice="<?= e($row['invoice_no']) ?>"
                                    data-comment="<?= e($row['comment']) ?>"
                                    data-followup="<?= e($row['next_followup_date']) ?>"
                                >
                                    <td class="fw-semibold"><?= e($row['invoice_no']) ?></td>
                                    <td><?= e($row['customer_name']) ?></td>
                                    <td><?= e($row['phone']) ?></td>
                                    <td><?= $row['quotation_date'] ? date('d M Y', strtotime($row['quotation_date'])) : '-' ?></td>
                                    <td class="text-end fw-semibold">৳<?= number_format((float)$row['grand_total'], 2) ?></td>
                                    <td>
                                        <select class="form-select form-select-sm status-select row-status">
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option value="<?= e($status) ?>" <?= $currentStatus === $status ? 'selected' : '' ?>>
                                                    <?= e($status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary comment-btn open-comment"
                                                title="Add or edit comment">
                                            <i class="bi bi-chat-left-text"></i>
                                            <?php if ($hasComment): ?><span class="comment-dot"></span><?php endif; ?>
                                        </button>
                                        <div class="followup-small">
                                            <?= !empty($row['next_followup_date']) ? 'Follow-up: ' . date('d M Y', strtotime($row['next_followup_date'])) : 'No follow-up date' ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No quotation found.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-end mb-0">
                            <?php
                            $queryBase = 'quotation-history.php?search=' . urlencode($search) . '&page=';
                            ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= e($queryBase . max(1, $page - 1)) ?>">Previous</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= e($queryBase . $p) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= e($queryBase . min($totalPages, $page + 1)) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="text-secondary small mt-3">
                Total quotation value: <strong>৳<?= number_format((float)($summary['total_value'] ?? 0), 2) ?></strong>
                <span class="mx-2">|</span>
                Cancelled: <strong><?= number_format((int)($summary['cancelled_count'] ?? 0)) ?></strong>
            </div>
        </main>
    </div>
</div>

<!-- Comment / Follow-up Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="commentForm">
                <div class="modal-header bg-dark text-white">
                    <div>
                        <h5 class="modal-title mb-0">Client Follow-up</h5>
                        <small id="modalInvoice" class="text-white-50"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="quotationId" name="quotation_id">
                    <input type="hidden" name="action" value="update_followup">

                    <label for="modalStatus" class="form-label fw-semibold">Status</label>
                    <select class="form-select mb-3" id="modalStatus" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <label for="modalComment" class="form-label fw-semibold">Comment / Client Note</label>
                    <textarea class="form-control mb-3" id="modalComment" name="comment" rows="4"
                              placeholder="Write what the client said..."></textarea>

                    <label for="modalFollowup" class="form-label fw-semibold">Next Follow-up Date</label>
                    <input type="date" class="form-control" id="modalFollowup" name="next_followup_date">
                    <div class="form-text">Leave the comment empty to remove the saved comment. Leave the date empty if no follow-up is needed.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveFollowupBtn">
                        <i class="bi bi-check2-circle me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
    <div id="toastMessage" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastText">Updated successfully.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const commentModalElement = document.getElementById('commentModal');
const commentModal = new bootstrap.Modal(commentModalElement);
const commentForm = document.getElementById('commentForm');
let activeRow = null;

document.querySelectorAll('.open-comment').forEach(button => {
    button.addEventListener('click', () => {
        activeRow = button.closest('tr');

        document.getElementById('quotationId').value = activeRow.dataset.id;
        document.getElementById('modalInvoice').textContent = 'Invoice: ' + activeRow.dataset.invoice;
        document.getElementById('modalStatus').value = activeRow.querySelector('.row-status').value;
        document.getElementById('modalComment').value = activeRow.dataset.comment || '';
        document.getElementById('modalFollowup').value = activeRow.dataset.followup || '';

        commentModal.show();
    });
});

document.getElementById('modalStatus').addEventListener('change', function () {
    if (activeRow) {
        activeRow.querySelector('.row-status').value = this.value;
    }
});

commentForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    if (!activeRow) return;

    const saveButton = document.getElementById('saveFollowupBtn');
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: new FormData(commentForm),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Update failed.');
        }

        activeRow.querySelector('.row-status').value = result.status;
        activeRow.dataset.comment = result.comment || '';
        activeRow.dataset.followup = result.next_followup_date || '';

        const commentCell = activeRow.querySelector('.open-comment');
        const existingDot = commentCell.querySelector('.comment-dot');

        if (result.comment && !existingDot) {
            const dot = document.createElement('span');
            dot.className = 'comment-dot';
            commentCell.appendChild(dot);
        } else if (!result.comment && existingDot) {
            existingDot.remove();
        }

        const followupText = activeRow.querySelector('.followup-small');
        followupText.textContent = result.next_followup_date
            ? 'Follow-up: ' + new Date(result.next_followup_date + 'T00:00:00').toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric'
              })
            : 'No follow-up date';

        commentModal.hide();

        document.getElementById('toastText').textContent = result.message;
        const toastElement = document.getElementById('toastMessage');
        toastElement.classList.remove('text-bg-danger');
        toastElement.classList.add('text-bg-success');
        bootstrap.Toast.getOrCreateInstance(toastElement).show();

    } catch (error) {
        document.getElementById('toastText').textContent = error.message;
        const toastElement = document.getElementById('toastMessage');
        toastElement.classList.remove('text-bg-success');
        toastElement.classList.add('text-bg-danger');
        bootstrap.Toast.getOrCreateInstance(toastElement).show();
    } finally {
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Update';
    }
});
</script>
</body>
</html>
<?php
$listStmt->close();
?>
