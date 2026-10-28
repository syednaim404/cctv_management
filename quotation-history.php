
<?php
require_once 'dbcon.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return '৳ ' . number_format((float)$amount, 2);
}

/*
|--------------------------------------------------------------------------
| AJAX: Get quotation details for View modal
|--------------------------------------------------------------------------
*/
if (isset($_GET['ajax_view'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_var($_GET['ajax_view'], FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid quotation ID']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT id, invoice_no, customer_name, phone, address,
                quotation_date, grand_total
         FROM hd_camera_quotations
         WHERE id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $quotation = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$quotation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quotation not found']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT product_name, quantity, price, total_price
         FROM hd_camera_quotation_items
         WHERE quotation_id = ?
         ORDER BY id ASC"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($item = $result->fetch_assoc()) {
        $items[] = $item;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'quotation' => $quotation,
        'items' => $items
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| PRINT: Print a previously saved quotation
|--------------------------------------------------------------------------
*/
if (isset($_GET['print'])) {
    $id = filter_var($_GET['print'], FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Invalid quotation ID.');
    }

    $stmt = $conn->prepare(
        "SELECT id, invoice_no, customer_name, phone, address,
                quotation_date, grand_total
         FROM hd_camera_quotations
         WHERE id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $quotation = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$quotation) {
        http_response_code(404);
        exit('Quotation not found.');
    }

    $stmt = $conn->prepare(
        "SELECT product_name, quantity, price, total_price
         FROM hd_camera_quotation_items
         WHERE quotation_id = ?
         ORDER BY id ASC"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $itemsResult = $stmt->get_result();

    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $stmt->close();

    $printDate = !empty($quotation['quotation_date'])
        ? date('d M Y', strtotime($quotation['quotation_date']))
        : '';

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice <?= e($quotation['invoice_no']) ?></title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
              rel="stylesheet">

        <style>
            body {
                background: #f1f5f9;
                color: #1e293b;
                font-family: Arial, sans-serif;
            }

            .invoice-paper {
                max-width: 900px;
                margin: 30px auto;
                padding: 45px;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 5px 25px rgba(0,0,0,.08);
            }

            .brand-name {
                color: #263d64;
                font-size: 28px;
                font-weight: 800;
            }

            .invoice-title {
                color: #263d64;
                font-size: 27px;
                font-weight: 800;
                letter-spacing: 2px;
            }

            .table thead th {
                background: #263d64;
                color: #fff;
                padding: 12px;
            }

            .total-box {
                background: #f1f5f9;
                padding: 18px;
                border-radius: 8px;
            }

            @media print {
                @page {
                    size: A4;
                    margin: 12mm;
                }

                body {
                    background: #fff;
                }

                .invoice-paper {
                    max-width: 100%;
                    margin: 0;
                    padding: 10px;
                    box-shadow: none;
                    border-radius: 0;
                }

                .no-print {
                    display: none !important;
                }

                .table thead th {
                    background: #263d64 !important;
                    color: #fff !important;
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }

                .total-box {
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>

    <div class="invoice-paper">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <div class="brand-name">NEXT BARISHAL</div>
                <div class="text-secondary">CCTV &amp; Security Solutions</div>
                <div class="small text-secondary">Website: nextbarisal.com</div>
                <div class="small text-secondary">Email: nextbarishal@gmail.com</div>
            </div>

            <div class="text-md-end">
                <div class="invoice-title">INVOICE</div>
                <div><strong>Invoice No:</strong> <?= e($quotation['invoice_no']) ?></div>
                <div><strong>Date:</strong> <?= e($printDate) ?></div>
            </div>
        </div>

        <hr>

        <h6 class="fw-bold mb-3">CUSTOMER INFORMATION</h6>

        <div class="row mb-4">
            <div class="col-md-6 mb-2">
                <strong>Customer:</strong>
                <?= e($quotation['customer_name'] ?: 'N/A') ?>
            </div>
            <div class="col-md-6 mb-2">
                <strong>Phone:</strong>
                <?= e($quotation['phone'] ?: 'N/A') ?>
            </div>
            <div class="col-12">
                <strong>Address:</strong>
                <?= nl2br(e($quotation['address'] ?: 'N/A')) ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th style="width: 60px;">SL</th>
                    <th>Product Description</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($item['product_name']) ?></td>
                            <td class="text-end">
                                <?= e(rtrim(rtrim(number_format((float)$item['quantity'], 2, '.', ''), '0'), '.')) ?>
                            </td>
                            <td class="text-end">
                                <?= money($item['price']) ?>
                            </td>
                            <td class="text-end">
                                <?= money($item['total_price']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary">
                            No product items found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end mt-3">
            <div class="col-md-5">
                <div class="total-box d-flex justify-content-between align-items-center">
                    <strong>GRAND TOTAL</strong>
                    <strong class="fs-4 text-primary">
                        <?= money($quotation['grand_total']) ?>
                    </strong>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between gap-3 mt-5 pt-4">
            <div class="text-center" style="min-width: 150px;">
                <div class="border-top pt-2">Customer Signature</div>
            </div>
            <div class="text-center" style="min-width: 150px;">
                <div class="border-top pt-2">Authorized Signature</div>
            </div>
        </div>

        <div class="text-center small text-secondary mt-5">
            Thank you for your business!
        </div>

        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary px-4" onclick="window.print()">
                Print Invoice
            </button>
            <button class="btn btn-secondary px-4"
                    onclick="window.location.href='quotation-history.php'">
                Back to History
            </button>
        </div>
    </div>

    <script>
        // Automatically open the print dialog.
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 400);
        });
    </script>
    </body>
    </html>
    <?php
    exit;
}

/*
|--------------------------------------------------------------------------
| HISTORY: Search, pagination and summary
|--------------------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = (!$page || $page < 1) ? 1 : $page;

$perPage = 10;
$offset = ($page - 1) * $perPage;

$searchLike = '%' . $search . '%';

/* Total quotation count */
if ($search !== '') {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM hd_camera_quotations
         WHERE invoice_no LIKE ?
            OR customer_name LIKE ?
            OR phone LIKE ?"
    );
    $stmt->bind_param('sss', $searchLike, $searchLike, $searchLike);
} else {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM hd_camera_quotations"
    );
}

$stmt->execute();
$totalQuotations = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

/* Summary amount for all quotations */
$summaryResult = $conn->query(
    "SELECT COUNT(*) AS total_count,
            COALESCE(SUM(grand_total), 0) AS total_amount
     FROM hd_camera_quotations"
);
$summary = $summaryResult->fetch_assoc();

$totalPages = max(1, (int)ceil($totalQuotations / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* Get quotations for the current page */
if ($search !== '') {
    $stmt = $conn->prepare(
        "SELECT id, invoice_no, customer_name, phone,
                quotation_date, grand_total
         FROM hd_camera_quotations
         WHERE invoice_no LIKE ?
            OR customer_name LIKE ?
            OR phone LIKE ?
         ORDER BY id DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param(
        'sssii',
        $searchLike,
        $searchLike,
        $searchLike,
        $perPage,
        $offset
    );
} else {
    $stmt = $conn->prepare(
        "SELECT id, invoice_no, customer_name, phone,
                quotation_date, grand_total
         FROM hd_camera_quotations
         ORDER BY id DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$quotations = $stmt->get_result();

$startRecord = $totalQuotations > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $perPage, $totalQuotations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation History | CCTV Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        :root {
            --primary: #263d64;
            --primary-light: #eaf0fb;
            --sidebar-width: 245px;
            --body-bg: #f4f7fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--body-bg);
            color: #243247;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }

        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            inset: 0 auto 0 0;
            background: var(--primary);
            color: #fff;
            padding: 24px 16px;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 25px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            margin-bottom: 25px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.14);
            border-radius: 12px;
            font-size: 23px;
        }

        .brand h5 {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }

        .brand small {
            color: #cbd5e1;
            font-size: 11px;
        }

        .nav-label {
            color: #aebdd5;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.3px;
            padding: 0 12px;
            margin: 22px 0 10px;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #dce5f3;
            padding: 12px 14px;
            text-decoration: none;
            border-radius: 9px;
            margin-bottom: 5px;
            transition: .2s;
        }

        .side-link:hover,
        .side-link.active {
            background: rgba(255,255,255,.14);
            color: #fff;
        }

        .side-link i {
            font-size: 18px;
        }

        .sidebar-note {
            position: absolute;
            bottom: 20px;
            left: 18px;
            right: 18px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.06);
            padding: 13px;
            border-radius: 10px;
            font-size: 11px;
            color: #d5dfed;
        }

        .main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            height: 76px;
            background: #fff;
            border-bottom: 1px solid #e7ebf1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar-title {
            font-weight: 700;
            font-size: 15px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: var(--primary);
            background: var(--primary-light);
            font-size: 18px;
        }

        .content {
            padding: 30px;
            max-width: 1600px;
            margin: auto;
        }

        .page-title {
            font-size: 25px;
            font-weight: 800;
            color: #1e2d44;
            margin-bottom: 6px;
        }

        .page-subtitle {
            color: #7a879a;
            margin: 0;
        }

        .stat-card {
            border: 1px solid #e8edf4;
            border-radius: 14px;
            background: #fff;
            padding: 22px;
            height: 100%;
            box-shadow: 0 3px 12px rgba(27,48,80,.025);
        }

        .stat-label {
            color: #77849a;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-value {
            color: #1e2d44;
            font-size: 25px;
            font-weight: 800;
            margin-top: 10px;
            overflow-wrap: anywhere;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 22px;
        }

        .history-card {
            background: #fff;
            border: 1px solid #e8edf4;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(27,48,80,.025);
        }

        .history-header {
            padding: 22px 24px;
            border-bottom: 1px solid #edf0f5;
        }

        .history-header h5 {
            font-weight: 800;
            font-size: 16px;
            margin: 0 0 5px;
        }

        .history-header p {
            color: #7a879a;
            margin: 0;
            font-size: 12px;
        }

        .search-box {
            position: relative;
            min-width: 270px;
        }

        .search-box i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #8793a5;
        }

        .search-box input {
            padding: 10px 12px 10px 37px;
            border: 1px solid #e0e6ee;
            border-radius: 9px;
            font-size: 13px;
            width: 100%;
        }

        .search-box input:focus {
            border-color: #879fca;
            box-shadow: 0 0 0 3px rgba(38,61,100,.08);
            outline: none;
        }

        .table-responsive {
            min-height: 220px;
        }

        .table {
            margin: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: #f8fafd;
            color: #77849a;
            border-bottom: 1px solid #e9edf3;
            padding: 15px 18px;
            white-space: nowrap;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .table tbody td {
            padding: 17px 18px;
            border-color: #f0f2f6;
            font-size: 12px;
        }

        .invoice-number {
            color: var(--primary);
            font-weight: 800;
            white-space: nowrap;
        }

        .customer-name {
            font-weight: 700;
            color: #27364b;
            margin-bottom: 4px;
        }

        .muted {
            color: #8490a1;
        }

        .amount {
            font-weight: 800;
            color: #1e2d44;
            white-space: nowrap;
        }

        .btn-action {
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            border: 1px solid #e1e7ef;
            border-radius: 8px;
            color: #53637b;
            background: #fff;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .btn-action:hover {
            color: var(--primary);
            border-color: #b9c8e0;
            background: #f4f7fc;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: #fff;
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .btn-primary-custom:hover {
            background: #1c2f50;
            color: #fff;
        }

        .pagination .page-link {
            color: var(--primary);
            border-color: #e1e7ef;
            font-size: 12px;
            padding: 8px 12px;
        }

        .pagination .active .page-link {
            color: #fff;
            background: var(--primary);
            border-color: var(--primary);
        }

        .empty-state {
            padding: 55px 20px;
            text-align: center;
            color: #8793a5;
        }

        .empty-state i {
            font-size: 38px;
            display: block;
            margin-bottom: 12px;
            color: #b7c2d2;
        }

        .modal-header {
            background: var(--primary);
            color: #fff;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .detail-label {
            color: #7b8798;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        @media (max-width: 991px) {
            .sidebar {
                width: 76px;
                padding: 20px 10px;
            }

            .brand {
                justify-content: center;
                padding: 0 0 20px;
            }

            .brand-text,
            .side-link span,
            .nav-label,
            .sidebar-note {
                display: none;
            }

            .side-link {
                justify-content: center;
                padding: 13px 8px;
            }

            .main {
                margin-left: 76px;
            }

            .content {
                padding: 22px 16px;
            }

            .topbar {
                padding: 0 18px;
            }
        }

        @media (max-width: 575px) {
            .page-title {
                font-size: 21px;
            }

            .history-header {
                padding: 18px;
            }

            .search-box {
                min-width: 100%;
            }

            .stat-value {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">
            <i class="bi bi-camera-video-fill"></i>
        </div>
        <div class="brand-text">
            <h5>CCTV Manager</h5>
            <small>Quotation System</small>
        </div>
    </div>

    <div class="nav-label">MAIN MENU</div>

    <a class="side-link" href="hd-camera.php" title="Create Quotation">
        <i class="bi bi-file-earmark-plus"></i>
        <span>Create Quotation</span>
    </a>

    <a class="side-link active"
       href="quotation-history.php"
       title="Quotation History">
        <i class="bi bi-clock-history"></i>
        <span>Quotation History</span>
    </a>

    <div class="nav-label">MANAGEMENT</div>

    <a class="side-link" href="hd-camera.php" title="Camera Products">
        <i class="bi bi-camera-video"></i>
        <span>Camera Products</span>
    </a>

    <div class="sidebar-note">
        <i class="bi bi-shield-check me-1"></i>
        CCTV Quotation Management
        <div class="mt-1">Manage your saved invoices easily.</div>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <div>
            <div class="topbar-title">Quotation Management</div>
            <div class="small text-secondary">Dashboard / Quotation History</div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="fw-bold">Administrator</div>
                <div class="small text-secondary">CCTV Management</div>
            </div>
            <div class="admin-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
        </div>
    </header>

    <section class="content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="page-title">Quotation History</h1>
                <p class="page-subtitle">
                    View, search and print your previously saved quotations.
                </p>
            </div>

            <a href="hd-camera.php" class="btn-primary-custom">
                <i class="bi bi-plus-lg me-1"></i>
                Create New Quotation
            </a>
        </div>

        <!-- Summary cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">TOTAL QUOTATIONS</div>
                            <div class="stat-value">
                                <?= number_format((int)$summary['total_count']) ?>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                    <div class="small text-secondary mt-3">
                        All saved invoices
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">TOTAL QUOTATION VALUE</div>
                            <div class="stat-value">
                                <?= money($summary['total_amount']) ?>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-currency-exchange"></i>
                        </div>
                    </div>
                    <div class="small text-secondary mt-3">
                        Combined value of all quotations
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">SEARCH RESULTS</div>
                            <div class="stat-value">
                                <?= number_format($totalQuotations) ?>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-search"></i>
                        </div>
                    </div>
                    <div class="small text-secondary mt-3">
                        Matching saved quotations
                    </div>
                </div>
            </div>
        </div>

        <!-- History table -->
        <div class="history-card">
            <div class="history-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5>All Quotations</h5>
                        <p>Search and manage your saved invoices.</p>
                    </div>

                    <form method="GET" action="quotation-history.php"
                          class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input
                                type="search"
                                name="search"
                                value="<?= e($search) ?>"
                                placeholder="Invoice, customer or phone..."
                                aria-label="Search quotations">
                        </div>

                        <button type="submit" class="btn-primary-custom">
                            Search
                        </button>

                        <?php if ($search !== ''): ?>
                            <a href="quotation-history.php"
                               class="btn btn-outline-secondary btn-sm py-2">
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Date</th>
                        <th>Grand Total</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if ($quotations->num_rows > 0): ?>
                        <?php while ($row = $quotations->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="invoice-number">
                                        <?= e($row['invoice_no']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="customer-name">
                                        <?= e($row['customer_name'] ?: 'N/A') ?>
                                    </div>
                                    <div class="muted small">
                                        Invoice ID: #<?= (int)$row['id'] ?>
                                    </div>
                                </td>

                                <td>
                                    <?= e($row['phone'] ?: 'N/A') ?>
                                </td>

                                <td class="text-nowrap">
                                    <?= !empty($row['quotation_date'])
                                        ? e(date('d M Y', strtotime($row['quotation_date'])))
                                        : 'N/A' ?>
                                </td>

                                <td>
                                    <span class="amount">
                                        <?= money($row['grand_total']) ?>
                                    </span>
                                </td>

                                <td class="text-center text-nowrap">
                                    <button
                                        type="button"
                                        class="btn-action"
                                        title="View quotation"
                                        aria-label="View quotation"
                                        data-id="<?= (int)$row['id'] ?>"
                                        onclick="viewQuotation(this.dataset.id)">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <a
                                        class="btn-action ms-1"
                                        href="quotation-history.php?print=<?= (int)$row['id'] ?>"
                                        title="Print quotation"
                                        aria-label="Print quotation"
                                        target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <h6 class="fw-bold text-dark">No quotations found</h6>
                                    <p class="mb-3">
                                        <?php if ($search !== ''): ?>
                                            No saved quotations match your search.
                                        <?php else: ?>
                                            You have not saved any quotations yet.
                                        <?php endif; ?>
                                    </p>

                                    <?php if ($search !== ''): ?>
                                        <a href="quotation-history.php"
                                           class="btn-primary-custom">
                                            View All Quotations
                                        </a>
                                    <?php else: ?>
                                        <a href="hd-camera.php"
                                           class="btn-primary-custom">
                                            Create First Quotation
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
                <div class="small text-secondary">
                    Showing <?= $startRecord ?>–<?= $endRecord ?>
                    of <?= number_format($totalQuotations) ?> quotations
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Quotation pages">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= http_build_query([
                                       'search' => $search,
                                       'page' => max(1, $page - 1)
                                   ]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php
                            $firstPage = max(1, $page - 2);
                            $lastPage = min($totalPages, $page + 2);
                            ?>

                            <?php for ($p = $firstPage; $p <= $lastPage; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link"
                                       href="?<?= http_build_query([
                                           'search' => $search,
                                           'page' => $p
                                       ]) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="?<?= http_build_query([
                                       'search' => $search,
                                       'page' => min($totalPages, $page + 1)
                                   ]) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center small text-secondary mt-4">
            CCTV Management System &copy; <?= date('Y') ?>
        </div>
    </section>
</main>

<!-- View Quotation Modal -->
<div class="modal fade" id="quotationModal" tabindex="-1"
     aria-labelledby="quotationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="quotationModalLabel">
                        Quotation Details
                    </h5>
                    <div class="small opacity-75">
                        Saved quotation information
                    </div>
                </div>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" id="quotationDetails">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <div class="mt-2 text-secondary">Loading quotation...</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border"
                        data-bs-dismiss="modal">
                    Close
                </button>
                <button type="button" class="btn btn-primary"
                        id="modalPrintButton" disabled>
                    <i class="bi bi-printer me-1"></i>
                    Print Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
const quotationModalElement = document.getElementById('quotationModal');
const quotationModal = new bootstrap.Modal(quotationModalElement);
const quotationDetails = document.getElementById('quotationDetails');
const modalPrintButton = document.getElementById('modalPrintButton');

let currentQuotationId = null;

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

function formatMoney(value) {
    const amount = Number(value || 0);
    return '৳ ' + amount.toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(value) {
    if (!value) return 'N/A';

    const parts = String(value).split('-');
    if (parts.length !== 3) return escapeHTML(value);

    const date = new Date(
        Number(parts[0]),
        Number(parts[1]) - 1,
        Number(parts[2])
    );

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function viewQuotation(id) {
    currentQuotationId = id;
    modalPrintButton.disabled = true;

    quotationDetails.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <div class="mt-2 text-secondary">Loading quotation...</div>
        </div>`;

    quotationModal.show();

    fetch('quotation-history.php?ajax_view=' + encodeURIComponent(id), {
        headers: { 'Accept': 'application/json' }
    })
    .then(function (response) {
        if (!response.ok) {
            throw new Error('Could not load quotation details.');
        }
        return response.json();
    })
    .then(function (data) {
        if (!data.success) {
            throw new Error(data.message || 'Quotation not found.');
        }

        const q = data.quotation;
        const items = data.items || [];

        let itemRows = '';

        if (items.length) {
            itemRows = items.map(function (item, index) {
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHTML(item.product_name)}</td>
                        <td class="text-end">${escapeHTML(item.quantity)}</td>
                        <td class="text-end">${formatMoney(item.price)}</td>
                        <td class="text-end fw-semibold">${formatMoney(item.total_price)}</td>
                    </tr>`;
            }).join('');
        } else {
            itemRows = `
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        No product items found.
                    </td>
                </tr>`;
        }

        quotationDetails.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="detail-label">INVOICE NUMBER</div>
                        <div class="detail-value text-primary">
                            ${escapeHTML(q.invoice_no)}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="detail-label">QUOTATION DATE</div>
                        <div class="detail-value">
                            ${formatDate(q.quotation_date)}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">CUSTOMER NAME</div>
                    <div class="detail-value">
                        ${escapeHTML(q.customer_name || 'N/A')}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">PHONE</div>
                    <div class="detail-value">
                        ${escapeHTML(q.phone || 'N/A')}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="detail-label">ADDRESS</div>
                    <div class="detail-value">
                        ${escapeHTML(q.address || 'N/A')}
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3">PRODUCT DETAILS</h6>

            <div class="table-responsive border rounded-3">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Product</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemRows}</tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <div class="bg-light rounded-3 p-3 text-end">
                    <div class="small text-secondary">GRAND TOTAL</div>
                    <div class="fs-4 fw-bold text-primary">
                        ${formatMoney(q.grand_total)}
                    </div>
                </div>
            </div>`;

        modalPrintButton.disabled = false;
    })
    .catch(function (error) {
        quotationDetails.innerHTML = `
            <div class="alert alert-danger mb-0">
                ${escapeHTML(error.message)}
            </div>`;
    });
}

modalPrintButton.addEventListener('click', function () {
    if (currentQuotationId) {
        window.open(
            'quotation-history.php?print=' + encodeURIComponent(currentQuotationId),
            '_blank'
        );
    }
});
</script>

</body>
</html>
<?php
$stmt->close();
?>
