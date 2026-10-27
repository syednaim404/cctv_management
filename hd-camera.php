<?php
require_once __DIR__ . '/dbcon.php';

date_default_timezone_set('Asia/Dhaka');

$message = '';
$error = '';

/* Load active products from database */
$products = [];

$sql = "SELECT id, name, type, model, price
        FROM hd_camera_products
        WHERE status = 1
        ORDER BY type ASC, name ASC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

/* Generate invoice number */
$invoiceNo = 'HD-' . date('Ymd-His');
$customerName = '';
$phone = '';
$address = '';
$quotationDate = date('Y-m-d');

/* Save quotation */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_quotation'])
) {

    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $quotationDate = $_POST['quotation_date'] ?? date('Y-m-d');
    $invoiceNo = trim($_POST['invoice_no'] ?? '');

    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    if (
        $invoiceNo === '' ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $quotationDate)
    ) {
        $error = 'Please provide a valid invoice number and date.';
    } elseif (
        !is_array($productIds) ||
        !is_array($quantities) ||
        !is_array($prices)
    ) {
        $error = 'Invalid quotation items.';
    } elseif (
        count($productIds) !== count($quantities) ||
        count($productIds) !== count($prices)
    ) {
        $error = 'Quotation item data does not match.';
    } elseif (count($productIds) === 0) {
        $error = 'Please add at least one product.';
    } else {
        $items = [];
        $grandTotal = 0;
        $valid = true;

        foreach ($productIds as $i => $productId) {
            $productId = (int) $productId;
            $quantity = (float) ($quantities[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            // Ignore completely empty rows
            if ($productId <= 0) {
                continue;
            }

            if (
                !is_finite($quantity) ||
                !is_finite($price) ||
                $quantity < 0 ||
                $price < 0 ||
                $quantity > 1000000 ||
                $price > 100000000
            ) {
                $valid = false;
                $error = 'Invalid quantity or price.';
                break;
            }

            // Confirm the product exists and is active
            $stmt = $conn->prepare(
                "SELECT name FROM hd_camera_products
                 WHERE id = ? AND status = 1"
            );
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $productResult = $stmt->get_result();
            $product = $productResult->fetch_assoc();
            $stmt->close();

            if (!$product) {
                $valid = false;
                $error = 'A selected product was not found.';
                break;
            }

            $lineTotal = round($quantity * $price, 2);
            $grandTotal += $lineTotal;

            $items[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'price' => $price,
                'total_price' => $lineTotal
            ];
        }

        if ($valid && count($items) === 0) {
            $valid = false;
            $error = 'Please select at least one product.';
        }

        if ($valid) {
            try {
                $conn->begin_transaction();

                $stmt = $conn->prepare(
                    "INSERT INTO hd_camera_quotations
                    (invoice_no, customer_name, phone, address,
                     quotation_date, grand_total)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssd",
                    $invoiceNo,
                    $customerName,
                    $phone,
                    $address,
                    $quotationDate,
                    $grandTotal
                );

                $stmt->execute();
                $quotationId = $conn->insert_id;
                $stmt->close();

                $stmt = $conn->prepare(
                    "INSERT INTO hd_camera_quotation_items
                    (quotation_id, product_id, product_name,
                     quantity, price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                foreach ($items as $item) {
                    $productId = $item['product_id'];
                    $productName = $item['product_name'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];
                    $lineTotal = $item['total_price'];

                    $stmt->bind_param(
                        "iisddd",
                        $quotationId,
                        $productId,
                        $productName,
                        $quantity,
                        $price,
                        $lineTotal
                    );

                    $stmt->execute();
                }

                $stmt->close();
                $conn->commit();

                header(
                    'Location: hd-camera.php?saved=1&invoice=' .
                        urlencode($invoiceNo)
                );
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'Quotation save failed. Please check your database tables and try again.';
            }
        }
    }
}

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $message = 'Quotation saved successfully! Invoice: ' .
        htmlspecialchars($_GET['invoice'] ?? '');
}

/* Products are passed safely to JavaScript */
$productJson = json_encode(
    $products,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>HD Camera Quotation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background: #f3f6fb;
            color: #25344a;
        }

        .quotation-card {
            max-width: 1200px;
            margin: 35px auto;
            padding: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 24px rgba(30, 50, 80, .08);
        }

        .page-heading {
            color: #263d64;
            font-weight: 700;
        }

        .form-label {
            font-weight: 600;
        }

        .table thead th {
            background: #263d64;
            color: white;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            min-width: 100px;
        }

        .table .product-cell {
            min-width: 240px;
        }

        .table input,
        .table select {
            min-width: 90px;
        }

        .table .product-select {
            min-width: 230px;
        }

        .total-box {
            background: #edf3ff;
            color: #263d64;
            border-radius: 10px;
            padding: 18px;
            font-size: 22px;
            font-weight: 700;
        }

        .btn-primary {
            background: #263d64;
            border-color: #263d64;
        }

        @media print {
            body {
                background: #fff;
            }

            .quotation-card {
                margin: 0;
                max-width: 100%;
                padding: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            input,
            select,
            textarea {
                border: 0 !important;
                box-shadow: none !important;
                appearance: none;
            }

            .table thead th {
                background: #263d64 !important;
                color: white !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="quotation-card">

            <div class="d-flex justify-content-between align-items-start
                    flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="page-heading mb-1">HD Camera Quotation</h2>
                    <p class="text-secondary mb-0">
                        CCTV Product Quotation Management
                    </p>
                </div>

                <div class="no-print d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.print()">
                        Print
                    </button>

                    <button type="button" class="btn btn-primary"
                        onclick="addRow()">
                        + Add Product
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="quotationForm">

                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name"
                            class="form-control"
                            value="<?= htmlspecialchars($customerName) ?>"
                            placeholder="Enter customer name">
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($phone) ?>"
                            placeholder="Enter phone number">
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Quotation Date</label>
                        <input type="date" name="quotation_date"
                            class="form-control" required
                            value="<?= htmlspecialchars($quotationDate) ?>">
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Invoice No.</label>
                        <input type="text" name="invoice_no"
                            class="form-control" required
                            value="<?= htmlspecialchars($invoiceNo) ?>">
                    </div>

                    <div class="col-12 col-lg-8">
                        <label class="form-label">Customer Address</label>
                        <textarea name="address" class="form-control"
                            rows="2"
                            placeholder="Enter customer address"><?= htmlspecialchars($address) ?></textarea>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered" id="quotationTable">
                        <thead>
                            <tr>
                                <th style="width: 65px">SL</th>
                                <th>Product</th>
                                <th style="width: 130px">Quantity</th>
                                <th style="width: 160px">Price</th>
                                <th style="width: 170px">Total Price</th>
                                <th class="no-print" style="width: 90px">Action</th>
                            </tr>
                        </thead>

                        <tbody id="quotationBody"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center
                        flex-wrap gap-3">
                    <button type="button" class="btn btn-outline-primary no-print"
                        onclick="addRow()">
                        + Add Another Product
                    </button>

                    <div class="total-box">
                        Grand Total: ৳ <span id="grandTotal">0.00</span>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4 no-print">
                    <button type="reset" class="btn btn-outline-secondary"
                        onclick="setTimeout(resetRows, 0)">
                        Reset
                    </button>

                    <button type="submit" name="save_quotation"
                        value="1" class="btn btn-success px-4">
                        Save Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const PRODUCTS = <?= $productJson ?: '[]' ?>;

        const TYPE_LABELS = {
            camera: 'Camera',
            dvr: 'DVR',
            hdd: 'HDD',
            adapter: 'Box Adapter',
            video_balun: 'Video Balun',
            installation: 'Installation',
            accessories: 'Other Accessories',
            cable: 'CAT6 Cable'
        };

        const tbody = document.getElementById('quotationBody');

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char]);
        }

        function productOptions(selectedId = '') {
            let html = '<option value="">-- Select Product --</option>';

            Object.keys(TYPE_LABELS).forEach(type => {
                const items = PRODUCTS.filter(product => product.type === type);

                if (!items.length) return;

                html += `<optgroup label="${escapeHtml(TYPE_LABELS[type])}">`;

                items.forEach(product => {
                    const selected = String(product.id) === String(selectedId) ?
                        'selected' :
                        '';

                    const model = product.model ?
                        ` (${product.model})` :
                        '';

                    html += `
                <option value="${product.id}"
                        data-price="${Number(product.price)}"
                        ${selected}>
                    ${escapeHtml(product.name + model)}
                </option>
            `;
                });

                html += '</optgroup>';
            });

            return html;
        }

        function addRow(productId = '', quantity = 1, price = '') {
            const row = document.createElement('tr');

            row.innerHTML = `
        <td class="serial text-center"></td>

        <td class="product-cell">
            <select name="product_id[]" class="form-select product-select"
                    required>
                ${productOptions(productId)}
            </select>
        </td>

        <td>
            <input type="number" name="quantity[]"
                   class="form-control quantity"
                   min="0" step="0.01" value="${quantity}" required>
        </td>

        <td>
            <input type="number" name="price[]"
                   class="form-control price"
                   min="0" step="0.01" value="${price}" required>
        </td>

        <td class="text-end">
            ৳ <span class="line-total">0.00</span>
        </td>

        <td class="text-center no-print">
            <button type="button" class="btn btn-sm btn-danger delete-row">
                Delete
            </button>
        </td>
    `;

            tbody.appendChild(row);

            const select = row.querySelector('.product-select');
            const priceInput = row.querySelector('.price');

            select.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];

                if (this.value !== '') {
                    priceInput.value = Number(option.dataset.price || 0).toFixed(2);
                } else {
                    priceInput.value = '0.00';
                }

                calculate();
            });

            row.querySelector('.quantity').addEventListener('input', calculate);
            priceInput.addEventListener('input', calculate);

            row.querySelector('.delete-row').addEventListener('click', function() {
                row.remove();
                updateSerials();
                calculate();
            });

            if (productId) {
                const option = select.options[select.selectedIndex];
                if (option && option.dataset.price !== undefined && price === '') {
                    priceInput.value = Number(option.dataset.price).toFixed(2);
                }
            }

            updateSerials();
            calculate();
        }

        function updateSerials() {
            [...tbody.querySelectorAll('tr')].forEach((row, index) => {
                row.querySelector('.serial').textContent = index + 1;
            });
        }

        function calculate() {
            let grandTotal = 0;

            [...tbody.querySelectorAll('tr')].forEach(row => {
                const quantity = Math.max(
                    0, Number(row.querySelector('.quantity').value) || 0
                );

                const price = Math.max(
                    0, Number(row.querySelector('.price').value) || 0
                );

                const total = quantity * price;

                row.querySelector('.line-total').textContent = total.toFixed(2);
                grandTotal += total;
            });

            document.getElementById('grandTotal').textContent =
                grandTotal.toLocaleString('en-BD', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
        }

        function resetRows() {
            tbody.innerHTML = '';
            addRow();
        }

        document.getElementById('quotationForm').addEventListener('submit', function(e) {
            const rows = tbody.querySelectorAll('tr');

            if (rows.length === 0) {
                e.preventDefault();
                alert('Please add at least one product.');
                return;
            }

            let hasProduct = false;

            rows.forEach(row => {
                if (row.querySelector('.product-select').value !== '') {
                    hasProduct = true;
                }
            });

            if (!hasProduct) {
                e.preventDefault();
                alert('Please select at least one product.');
            }
        });

        addRow();
    </script>

</body>

</html>