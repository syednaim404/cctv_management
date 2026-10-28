<?php
require_once __DIR__ . '/dbcon.php';

date_default_timezone_set('Asia/Dhaka');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$products = [];
$result = $conn->query(
    "SELECT id, name, type, model, price
     FROM hd_camera_products
     WHERE status = 1
     ORDER BY name ASC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$saveMessage = '';
$saveError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quotation'])) {
    $invoiceNo = trim($_POST['invoice_no'] ?? '');
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $quotationDate = $_POST['quotation_date'] ?? date('Y-m-d');

    $productIds = $_POST['product_id'] ?? [];
    $productNames = $_POST['product_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];
    $priceOnlyFlags = $_POST['price_only'] ?? [];

    if ($invoiceNo === '') {
        $saveError = 'Invoice number is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $quotationDate)) {
        $saveError = 'Please enter a valid quotation date.';
    } elseif (
        !is_array($productIds) || !is_array($productNames) ||
        !is_array($quantities) || !is_array($prices) ||
        !is_array($priceOnlyFlags) ||
        count($productIds) !== count($productNames) ||
        count($productIds) !== count($quantities) ||
        count($productIds) !== count($prices) ||
        count($productIds) !== count($priceOnlyFlags)
    ) {
        $saveError = 'Invalid quotation item data.';
    } else {
        $items = [];
        $grandTotal = 0;
        $valid = true;

        foreach ($productIds as $i => $rawId) {
            $productId = (int)$rawId;
            $productName = trim((string)($productNames[$i] ?? ''));
            $quantity = (float)($quantities[$i] ?? 0);
            $price = (float)($prices[$i] ?? 0);
            $priceOnly = (string)($priceOnlyFlags[$i] ?? '0') === '1';

            if ($productName === '') {
                continue;
            }

            if (
                !is_finite($quantity) || !is_finite($price) ||
                $quantity < 0 || $price < 0
            ) {
                $valid = false;
                $saveError = 'Invalid quantity or price.';
                break;
            }

            if ($productId > 0) {
                $check = $conn->prepare(
                    "SELECT name FROM hd_camera_products
                     WHERE id = ? AND status = 1"
                );
                $check->bind_param("i", $productId);
                $check->execute();
                $found = $check->get_result()->fetch_assoc();
                $check->close();

                if (!$found) {
                    $valid = false;
                    $saveError = 'A selected product is no longer available.';
                    break;
                }

                $productName = $found['name'];
            } else {
                $productId = null;
            }

            $lineTotal = $priceOnly ? $price : round($quantity * $price, 2);
            $grandTotal += $lineTotal;

            $items[] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'quantity' => $quantity,
                'price' => $price,
                'total_price' => $lineTotal
            ];
        }

        if ($valid && !$items) {
            $valid = false;
            $saveError = 'There are no products to save.';
        }

        if ($valid) {
            try {
                $conn->begin_transaction();

                $stmt = $conn->prepare(
                    "INSERT INTO hd_camera_quotations
                    (invoice_no, customer_name, phone, address, quotation_date, grand_total)
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
                    (quotation_id, product_id, product_name, quantity, price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                foreach ($items as $item) {
                    $productIdForBind = $item['product_id'];
                    $productNameForBind = $item['product_name'];
                    $quantityForBind = $item['quantity'];
                    $priceForBind = $item['price'];
                    $totalForBind = $item['total_price'];

                    $stmt->bind_param(
                        "iisddd",
                        $quotationId,
                        $productIdForBind,
                        $productNameForBind,
                        $quantityForBind,
                        $priceForBind,
                        $totalForBind
                    );
                    $stmt->execute();
                }

                $stmt->close();
                $conn->commit();

                header('Location: hd-camera.php?saved=1&invoice=' . urlencode($invoiceNo));
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $saveError = 'Quotation save failed. Invoice number may already exist, or check your database tables.';
            }
        }
    }
}

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $saveMessage = 'Quotation saved successfully. Invoice: ' . h($_GET['invoice'] ?? '');
}

$productJson = json_encode(
    $products,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HD CAMERA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6f8
        }

        .box {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .08)
        }

        .table th {
            background: #212529;
            color: #fff;
            text-align: center;
            vertical-align: middle
        }

        .table td {
            vertical-align: middle
        }

        .sl,
        .check {
            text-align: center;
            width: 55px
        }

        .price,
        .quantity,
        .total {
            text-align: center
        }

        .product-select {
            min-width: 220px
        }

        .plus-btn {
            margin-left: 5px
        }

        .grand-total {
            font-size: 18px;
            font-weight: 700
        }

        #invoiceSection {
            display: none
        }

        @media print {
            body * {
                visibility: hidden
            }

            #invoice,
            #invoice * {
                visibility: visible
            }

            #invoice {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%
            }
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            display: none
        }

        .input-group-text {
            background: transparent;
            border-right: 0
        }

        .input-group .form-control {
            border-left: 0;
            padding-left: 0
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand text-white" href="index.php">CC Camera</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link text-white active" href="index.php">HOME</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="hd-camera.php">HD CAMERA</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="ip-camera.php">IP CAMERA</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link text-white dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">MORE</a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php if ($saveMessage): ?>
            <div class="alert alert-success"><?= $saveMessage ?></div>
        <?php endif; ?>

        <?php if ($saveError): ?>
            <div class="alert alert-danger"><?= h($saveError) ?></div>
        <?php endif; ?>

        <div class="box" id="formSection">
            <h3 class="text-center fw-bold mb-4">HD Camera Quotation</h3>

            <div class="row g-2 mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input id="cName" class="form-control" placeholder="Customer Name">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                        <input id="cPhone" class="form-control" placeholder="Phone">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                        <input id="cDate" type="date" class="form-control">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                        <input id="cAddress" class="form-control" placeholder="Address">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                        <input id="cNo" class="form-control" placeholder="Invoice No." value="0">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th class="check"><input type="checkbox" id="selectAll"></th>
                            <th class="sl">SL</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody id="productTable"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end grand-total">Grand Total</td>
                            <td><input type="number" id="grandTotal" class="form-control grand-total text-center" value="0" readonly></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-primary" id="addProduct"><i class="bi bi-plus-lg"></i> Add Product</button>
                <button type="button" class="btn btn-danger" id="deleteSelected"><i class="bi bi-trash"></i> Delete Selected</button>
                <button type="button" class="btn btn-dark ms-auto" id="makeInvoice"><i class="bi bi-receipt"></i> Invoice</button>
            </div>
        </div>

        <div id="invoiceSection">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-secondary" id="backBtn"><i class="bi bi-arrow-left"></i> Back</button>
                <button type="button" class="btn btn-success" id="pdfBtn"><i class="bi bi-file-earmark-pdf"></i> PDF Download</button>
                <button type="button" class="btn btn-outline-dark" id="printBtn"><i class="bi bi-printer"></i> Print</button>
                <button type="button" class="btn btn-primary" id="saveBtn"><i class="bi bi-database-check"></i> Save Quotation</button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4 bg-white" id="invoice"></div>
            </div>
        </div>
    </div>

    <form method="POST" id="saveForm" class="d-none">
        <input type="hidden" name="save_quotation" value="1">
        <input type="hidden" name="invoice_no" id="saveInvoiceNo">
        <input type="hidden" name="customer_name" id="saveCustomerName">
        <input type="hidden" name="phone" id="savePhone">
        <input type="hidden" name="address" id="saveAddress">
        <input type="hidden" name="quotation_date" id="saveDate">
        <div id="saveItems"></div>
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const PRODUCTS = <?= $productJson ?: '[]' ?>;

        const TYPE_LABEL = {
            camera: "Camera",
            dvr: "DVR",
            hdd: "HDD",
            adapter: "Box Adapter",
            video_balun: "Video Balun",
            installation: "Installation Charge",
            accessories: "Other Accessories",
            cable: "CAT6 Cable"
        };

        const $ = s => document.querySelector(s);
        const $$ = s => [...document.querySelectorAll(s)];
        const tbody = $("#productTable");

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [c]));
        }

        function getProducts(kind) {
            return PRODUCTS.filter(p => p.type === kind);
        }

        function productSelect(kind, selectedId = '') {
            const list = getProducts(kind);

            const options = list.map(p => {
                const model = p.model ? ` (${p.model})` : '';
                const selected = String(p.id) === String(selectedId) ? ' selected' : '';

                return `<option value="${Number(p.id)}"
                data-price="${Number(p.price)}"${selected}>${esc(p.name + model)}</option>`;
            }).join('');

            return `<div class="d-flex">
            <select class="form-select product-select" data-type="${esc(kind)}">
                <option value="">Select ${esc(TYPE_LABEL[kind] || 'Product')}</option>
                ${options}
            </select>
            <button type="button" class="btn btn-success plus-btn add-type-row"
                    data-type="${esc(kind)}"
                    title="Add another ${esc(TYPE_LABEL[kind] || 'product')}">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>`;
        }

        // এই পাঁচটি সেকশনে শুধু নাম দেখাবে, dropdown বা + button নয়।
        function fixedProductCell(kind, fixedName) {
            const list = getProducts(kind);

            const selected = list.find(
                p => p.name.toLowerCase() === fixedName.toLowerCase()
            );

            const productId = selected ? Number(selected.id) : 0;
            const price = selected ? Number(selected.price) : 0;

            return `<div class="fixed-product-name">${esc(fixedName)}</div>
            <input type="hidden" class="product-id"
                   data-type="${esc(kind)}"
                   value="${productId}"
                   data-price="${price}">`;
        }

        function rowHTML(kind = 'custom', name = '', priceOnly = false) {
            let productCell = '';

            if (['camera', 'dvr', 'hdd'].includes(kind)) {
                // Camera, DVR ও HDD-তে dropdown এবং + button থাকবে।
                productCell = productSelect(kind);
            } else if (
                ['adapter', 'video_balun', 'installation', 'accessories', 'cable'].includes(kind)
            ) {
                // Box Adapter, Video Balun, Installation Charge,
                // Other Accessories ও CAT6 Cable-তে শুধু নাম থাকবে।
                productCell = fixedProductCell(kind, name);
            } else {
                // Add Product বাটন দিয়ে যোগ করা custom product।
                productCell = `<input type="text"
                class="form-control name-input"
                value="${esc(name)}"
                placeholder="Product Name">`;
            }

            return `<td class="check">
                <input type="checkbox" class="row-check">
            </td>
            <td class="sl"></td>
            <td>${productCell}</td>
            <td>
                <input type="number" class="form-control quantity"
                       value="" min="0" step="any" placeholder="0">
            </td>
            <td>
                <input type="number" class="form-control price"
                       value="" min="0" step="any" placeholder="0">
            </td>
            <td>
                <input type="number" class="form-control total"
                       value="0" readonly>
            </td>`;
        }

        function addRow(kind = 'custom', name = '', priceOnly = false, after = null) {
            const tr = document.createElement('tr');

            tr.className = 'product-row';
            tr.dataset.kind = kind;

            if (priceOnly) {
                tr.dataset.priceOnly = '1';
            }

            tr.innerHTML = rowHTML(kind, name, priceOnly);

            if (after) {
                after.after(tr);
            } else {
                tbody.appendChild(tr);
            }

            const select = tr.querySelector('.product-select');
            const fixedProduct = tr.querySelector('.product-id');

            if (select && select.value) {
                const option = select.options[select.selectedIndex];
                tr.querySelector('.price').value = Number(option.dataset.price || 0);
            } else if (fixedProduct) {
                tr.querySelector('.price').value = Number(fixedProduct.dataset.price || 0);
            }

            refresh();
        }

        function selectedProductName(row) {
            const select = row.querySelector('.product-select');

            if (select && select.value) {
                const product = PRODUCTS.find(
                    p => String(p.id) === String(select.value)
                );

                return product ? product.name : '';
            }

            const fixedName = row.querySelector('.fixed-product-name');

            if (fixedName) {
                return fixedName.textContent.trim();
            }

            const input = row.querySelector('.name-input');

            return input ? input.value.trim() : '';
        }

        function refresh() {
            let grand = 0;

            $$('#productTable .product-row').forEach((row, index) => {
                row.querySelector('.sl').textContent = index + 1;

                const price = Math.max(
                    0,
                    parseFloat(row.querySelector('.price').value) || 0
                );

                const quantity = Math.max(
                    0,
                    parseFloat(row.querySelector('.quantity').value) || 0
                );

                // Other Accessories-এর total শুধু price।
                // অন্য সব row-তে quantity × price।
                const total = row.dataset.priceOnly === '1' ?
                    price :
                    price * quantity;

                row.querySelector('.total').value = total.toFixed(2);
                grand += total;
            });

            $('#grandTotal').value = grand.toFixed(2);

            const checks = $$('.row-check');

            $('#selectAll').checked =
                checks.length > 0 && checks.every(c => c.checked);
        }

        // Initial product rows.
        addRow('camera');
        addRow('dvr');
        addRow('hdd');
        addRow('adapter', 'Box Adapter');
        addRow('video_balun', 'Video Balun');
        addRow('installation', 'Installation Charge');
        addRow('accessories', 'Other Accessories', true);
        addRow('cable', 'CAT6 Cable');

        // Camera, DVR ও HDD-এর + button কাজ করবে।
        document.addEventListener('click', e => {
            const button = e.target.closest('.add-type-row');

            if (button) {
                const row = button.closest('.product-row');
                addRow(button.dataset.type, '', false, row);
            }
        });

        // Dropdown থেকে product নির্বাচন করলে price auto-fill হবে।
        document.addEventListener('change', e => {
            if (e.target.matches('.product-select')) {
                const row = e.target.closest('.product-row');
                const option = e.target.options[e.target.selectedIndex];

                row.querySelector('.price').value = e.target.value ?
                    Number(option.dataset.price || 0).toFixed(2) :
                    '';

                refresh();
            }

            if (e.target.matches('.row-check')) {
                refresh();
            }
        });

        document.addEventListener('input', e => {
            if (e.target.matches('.price, .quantity, .name-input')) {
                refresh();
            }
        });

        $('#selectAll').addEventListener('change', function() {
            $$('.row-check').forEach(check => {
                check.checked = this.checked;
            });
        });

        $('#addProduct').addEventListener('click', () => addRow('custom'));

        $('#deleteSelected').addEventListener('click', () => {
            $$('.row-check:checked').forEach(check => {
                check.closest('.product-row').remove();
            });

            refresh();
        });

        // Default date.
        $('#cDate').value = new Date().toLocaleDateString('en-CA');

        const money = n => '৳ ' + Number(n || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        function invoiceRows() {
            let rows = '';
            let count = 0;

            $$('#productTable .product-row').forEach(row => {
                const name = selectedProductName(row);
                const price = parseFloat(row.querySelector('.price').value) || 0;
                const quantityValue = row.querySelector('.quantity').value;
                const quantity = parseFloat(quantityValue) || 0;
                const total = parseFloat(row.querySelector('.total').value) || 0;

                if (!name || total <= 0) {
                    return;
                }

                count++;

                const qtyText = row.dataset.priceOnly === '1' ?
                    '—' :
                    (quantityValue || '—');

                rows += `<tr>
                <td class="text-center">${count}</td>
                <td>${esc(name)}</td>
                <td class="text-center">${esc(qtyText)}</td>
                <td class="text-end">${money(price)}</td>
                <td class="text-end">${money(total)}</td>
            </tr>`;
            });

            return {
                rows,
                count
            };
        }

        $('#makeInvoice').addEventListener('click', () => {
            refresh();

            const result = invoiceRows();

            if (!result.count) {
                alert('কমপক্ষে একটি প্রোডাক্ট সিলেক্ট করে দাম ও পরিমাণ দিন।');
                return;
            }

            $('#invoice').innerHTML = `
            <div class="text-center mb-4">
                <h2 class="mb-1">
                    <i class="bi bi-camera-video"></i> INVOICE
                </h2>
                <p class="text-muted mb-0">
                    Your Security, Our Responsibility – Next Barisal
                </p>
            </div>
            <hr>
            <div class="row mb-4">
                <div class="col-6">
                    <div>
                        <i class="bi bi-person-fill"></i>
                        <b>Customer:</b> ${esc($('#cName').value)}
                    </div>
                    <div>
                        <i class="bi bi-telephone-fill"></i>
                        <b>Phone:</b> ${esc($('#cPhone').value)}
                    </div>
                    <div>
                        <i class="bi bi-geo-alt-fill"></i>
                        <b>Address:</b> ${esc($('#cAddress').value)}
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div>
                        <i class="bi bi-hash"></i>
                        <b>Invoice No:</b> ${esc($('#cNo').value)}
                    </div>
                    <div>
                        <i class="bi bi-calendar-event"></i>
                        <b>Date:</b> ${esc($('#cDate').value)}
                    </div>
                </div>
            </div>
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>SL</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>${result.rows}</tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-bold">Grand Total</td>
                        <td class="text-end fw-bold">${money($('#grandTotal').value)}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="d-flex justify-content-between text-center mt-5 pt-4">
                <div class="border-top pt-1 px-4">Customer Signature</div>
                <div class="border-top pt-1 px-4">Authorized Signature</div>
            </div>`;

            $('#formSection').style.display = 'none';
            $('#invoiceSection').style.display = 'block';

            window.scrollTo(0, 0);
        });

        $('#backBtn').addEventListener('click', () => {
            $('#invoiceSection').style.display = 'none';
            $('#formSection').style.display = 'block';
        });

        $('#printBtn').addEventListener('click', () => window.print());

        $('#pdfBtn').addEventListener('click', () => {
            const cameraSelect = document.querySelector(
                '#productTable .product-row select'
            );

            const cameraName = cameraSelect ?
                cameraSelect.options[cameraSelect.selectedIndex]?.text.trim() :
                '';

            const fileName = cameraName && cameraSelect.value ?
                cameraName.replace(/[<>:"/\\|?*]/g, '-') + '.pdf' :
                'CCTV-Invoice.pdf';

            html2pdf().set({
                margin: 10,
                filename: fileName,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            }).from($('#invoice')).save();
        });

        // Save quotation to the database.
        $('#saveBtn').addEventListener('click', () => {
            const invoice = $('#cNo').value.trim();

            if (!invoice) {
                alert('Invoice No. দিন।');
                return;
            }

            const items = $('#saveItems');
            items.innerHTML = '';

            const addHidden = (name, value) => {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = name;
                input.value = value;

                items.appendChild(input);
            };

            let validItems = 0;

            $$('#productTable .product-row').forEach(row => {
                const name = selectedProductName(row);
                const select = row.querySelector('.product-select');
                const fixedProduct = row.querySelector('.product-id');

                const productId = select && select.value ?
                    select.value :
                    (fixedProduct ? fixedProduct.value : '0');

                const price = parseFloat(row.querySelector('.price').value) || 0;
                const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                const total = parseFloat(row.querySelector('.total').value) || 0;

                if (!name || total <= 0) {
                    return;
                }

                validItems++;

                addHidden('product_id[]', productId);
                addHidden('product_name[]', name);
                addHidden('quantity[]', quantity);
                addHidden('price[]', price);
                addHidden(
                    'price_only[]',
                    row.dataset.priceOnly === '1' ? '1' : '0'
                );
            });

            if (!validItems) {
                alert('Save করার আগে অন্তত একটি product select করে price/quantity দিন।');
                return;
            }

            $('#saveInvoiceNo').value = invoice;
            $('#saveCustomerName').value = $('#cName').value;
            $('#savePhone').value = $('#cPhone').value;
            $('#saveAddress').value = $('#cAddress').value;
            $('#saveDate').value = $('#cDate').value;

            $('#saveForm').submit();
        });
    </script>
</body>

</html>