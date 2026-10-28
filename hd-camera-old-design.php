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
            border-right: 0;
        }

        .input-group .form-control {
            border-left: 0;
            padding-left: 0;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand text-white" href="index.php">CC Camera</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-white active" aria-current="page" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="hd-camera.php">HD CAMERA</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="ip-camera.php">IP CAMERA</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            MORE
                        </a>
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
        <!-- ================= FORM ================= -->
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
                <button class="btn btn-primary" id="addProduct"><i class="bi bi-plus-lg"></i> Add Product</button>
                <button class="btn btn-danger" id="deleteSelected"><i class="bi bi-trash"></i> Delete Selected</button>
                <button class="btn btn-dark ms-auto" id="makeInvoice"><i class="bi bi-receipt"></i> Invoice</button>
            </div>
        </div>

        <!-- ================= INVOICE ================= -->
        <div id="invoiceSection">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button class="btn btn-secondary" id="backBtn"><i class="bi bi-arrow-left"></i> Back</button>
                <button class="btn btn-success" id="pdfBtn"><i class="bi bi-file-earmark-pdf"></i> PDF Download</button>
                <button class="btn btn-outline-dark" id="printBtn"><i class="bi bi-printer"></i> Print</button>
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-4 bg-white" id="invoice"></div>
            </div>
        </div>

    </div>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const OPTS = {
            camera: [
                "Hikvision Bullet Camera-2MP",
                "Hikvision Bullet Camera-3MP",
                "Hikvision Dome Camera-2MP",
                "Hikvision Dome Camera-3MP",
                "Dahua Bullet Camera-2MP",
                "Dahua Bullet Camera-3MP",
                "Dahua Dome Camera-2MP",
                "Dahua Dome Camera-3MP"
            ],
            dvr: ["Hikvision 4 Channel DVR", "Hikvision 8 Channel DVR", "Hikvision 16 Channel DVR", "Dahua 4 Channel DVR", "Dahua 8 Channel DVR", "Dahua 16 Channel DVR"],
            hdd: ["500 GB Surveillance HDD", "1TB Surveillance HDD", "2TB Surveillance HDD", "4TB Surveillance HDD", "6TB Surveillance HDD", "8TB Surveillance HDD"]
        };
        const LABEL = {
            camera: "Camera",
            dvr: "DVR",
            hdd: "HDD"
        };
        const $ = s => document.querySelector(s);
        const $$ = s => [...document.querySelectorAll(s)];
        const tbody = $("#productTable");

        function rowHTML(kind, name = "", priceOnly = false) {
            let product;
            if (OPTS[kind]) {
                product = `<div class="d-flex"><select class="form-select product-select">
      <option value="">Select ${LABEL[kind]}</option>${OPTS[kind].map(o=>`<option>${o}</option>`).join("")}
      </select><button type="button" class="btn btn-success plus-btn add-type-row" data-type="${kind}"><i class="bi bi-plus-lg"></i></button></div>`;
            } else {
                product = `<input type="text" class="form-control name-input" value="${name}" placeholder="Product Name">`;
            }
            const qty = `<input type="number" class="form-control quantity" value="" min="0" placeholder="0">`;
            return `<td class="check"><input type="checkbox" class="row-check"></td><td class="sl"></td>
    <td>${product}</td>
    <td>${qty}</td>
    <td><input type="number" class="form-control price" value="" min="0" placeholder="0"></td>
    <td><input type="number" class="form-control total" value="0" readonly></td>`;
        }

        function addRow(kind, name, priceOnly, after) {
            const tr = document.createElement("tr");
            tr.className = "product-row";
            if (priceOnly) tr.dataset.priceOnly = "1";
            tr.innerHTML = rowHTML(kind, name, priceOnly);
            after ? after.after(tr) : tbody.appendChild(tr);
            refresh();
        }

        function refresh() {
            let grand = 0;
            $$("#productTable .product-row").forEach((r, i) => {
                r.querySelector(".sl").innerText = i + 1;
                const p = parseFloat(r.querySelector(".price").value) || 0;
                const q = parseFloat(r.querySelector(".quantity").value) || 0;
                const t = r.dataset.priceOnly ? p : p * q;
                r.querySelector(".total").value = t;
                grand += t;
            });
            $("#grandTotal").value = grand;
            const all = $$(".row-check");
            $("#selectAll").checked = all.length > 0 && all.every(c => c.checked);
        }

        // Initial rows
        addRow("camera");
        addRow("dvr");
        addRow("hdd");
        ["Box Adapter", "Video Balun", "Installation Charge"].forEach(n => addRow("text", n));
        addRow("text", "Other Accessories", true);
        addRow("text", "CAT6 Cable");

        document.addEventListener("click", e => {
            const b = e.target.closest(".add-type-row");
            if (b) addRow(b.dataset.type, "", false, b.closest(".product-row"));
        });
        document.addEventListener("input", e => {
            if (e.target.matches(".price,.quantity")) refresh();
        });
        document.addEventListener("change", e => {
            if (e.target.matches(".row-check")) refresh();
        });
        $("#selectAll").addEventListener("change", function() {
            $$(".row-check").forEach(c => c.checked = this.checked);
        });
        $("#addProduct").addEventListener("click", () => addRow("text"));
        $("#deleteSelected").addEventListener("click", () => {
            $$(".row-check:checked").forEach(c => c.closest(".product-row").remove());
            refresh();
        });

        // Default date
        $("#cDate").value = new Date().toISOString().slice(0, 10);

        // ================= INVOICE =================
        const money = n => "৳ " + Number(n).toLocaleString("en-IN");
        const esc = s => String(s).replace(/[&<>"]/g, c => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;"
        } [c]));

        $("#makeInvoice").addEventListener("click", () => {
            refresh();
            let rows = "",
                n = 0;
            $$("#productTable .product-row").forEach(r => {
                const sel = r.querySelector("select");
                const name = sel ? sel.value : r.querySelector(".name-input").value.trim();
                const price = parseFloat(r.querySelector(".price").value) || 0;
                const total = parseFloat(r.querySelector(".total").value) || 0;
                if (!name || total <= 0) return;
                const qty = r.querySelector(".quantity").value || "—";
                n++;
                rows += `<tr><td class="text-center">${n}</td><td>${esc(name)}</td>
      <td class="text-center">${qty}</td><td class="text-end">${money(price)}</td>
      <td class="text-end">${money(total)}</td></tr>`;
            });
            if (!n) {
                alert("কমপক্ষে একটি প্রোডাক্টের নাম, দাম ও পরিমাণ দিন।");
                return;
            }

            $("#invoice").innerHTML = `
    <div class="text-center mb-4">
      <h2 class=" mb-1"><i class="bi bi-camera-video"></i> INVOICE</h2>
      <p class="text-muted mb-0">Your Security, Our Responsibility – Next Barisal</p>
    </div>
    <hr>
    <div class="row mb-4">
      <div class="col-6">
        <div><i class="bi bi-person-fill"></i> <b>Customer:</b> ${esc($("#cName").value)}</div>
        <div><i class="bi bi-telephone-fill"></i> <b>Phone:</b> ${esc($("#cPhone").value)}</div>
        <div><i class="bi bi-geo-alt-fill"></i> <b>Address:</b> ${esc($("#cAddress").value)}</div>
      </div>
      <div class="col-6 text-end">
        <div><i class="bi bi-hash"></i> <b>Invoice No:</b> ${esc($("#cNo").value)}</div>
        <div><i class="bi bi-calendar-event"></i> <b>Date:</b> ${esc($("#cDate").value)}</div>
      </div>
    </div>
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-dark text-center"><tr><th>SL</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
      <tbody>${rows}</tbody>
      <tfoot><tr class="table-light"><td colspan="4" class="text-end fw-bold">Grand Total</td>
        <td class="text-end fw-bold">${money($("#grandTotal").value)}</td></tr></tfoot>
    </table>
    <div class="d-flex justify-content-between text-center mt-5 pt-4">
      <div class="border-top pt-1 px-4">Customer Signature</div>
      <div class="border-top pt-1 px-4">Authorized Signature</div>
    </div>`;
            $("#formSection").style.display = "none";
            $("#invoiceSection").style.display = "block";
            window.scrollTo(0, 0);
        });

        $("#backBtn").addEventListener("click", () => {
            $("#invoiceSection").style.display = "none";
            $("#formSection").style.display = "block";
        });
        $("#printBtn").addEventListener("click", () => window.print());

        $("#pdfBtn").addEventListener("click", () => {
            // Selected camera name
            const cameraSelect = document.querySelector(
                '#productTable .product-row select'
            );

            const cameraName = cameraSelect ?
                cameraSelect.value.trim() :
                "";

            // File name
            const fileName = cameraName ?
                cameraName.replace(/[<>:"/\\|?*]/g, "-") + ".pdf" :
                "CCTV-Invoice.pdf";

            html2pdf().set({
                margin: 10,
                filename: fileName,
                image: {
                    type: "jpeg",
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: "mm",
                    format: "a4",
                    orientation: "portrait"
                }
            }).from($("#invoice")).save();
        });
    </script>
</body>

</html>