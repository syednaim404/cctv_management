<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hikvision CCTV Quotation</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">


    <style>

        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .quotation-box {
            background: #ffffff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Title */
        .quotation-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .quotation-title h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .quotation-title p {
            font-size: 20px;
            margin: 0;
        }


        /* Table */
        .quotation-table {
            margin-bottom: 0;
        }

        .quotation-table thead th {
            background: #e9ecef;
            color: #111;
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
            font-size: 16px;
            padding: 12px 8px;
        }

        .quotation-table tbody td {
            vertical-align: middle;
            padding: 7px;
        }


        /* Checkbox */
        .check-column {
            width: 55px;
            text-align: center;
        }

        .sl-column {
            width: 65px;
            text-align: center;
        }

        .quotation-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }


        /* SL */
        .sl-number {
            text-align: center;
            font-weight: 500;
        }


        /* Inputs */
        .quotation-table .form-control {
            border: 1px solid #ced4da;
            border-radius: 5px;
            min-height: 40px;
        }

        .quotation-table .form-control:focus {
            box-shadow: none;
            border-color: #86b7fe;
        }


        /* Total input */
        .row-total {
            background: #f1f3f5 !important;
            font-weight: 600;
        }


        /* Footer */
        .quotation-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
        }


        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons .btn {
            min-width: 110px;
            font-weight: 600;
        }


        /* Grand total */
        .grand-total {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 20px;
            font-weight: 700;
        }

        #grandTotal {
            width: 170px;
            font-size: 20px;
            font-weight: 700;
            text-align: right;
            background: #f1f3f5;
        }


        /* Mobile */
        @media (max-width: 768px) {

            .quotation-box {
                padding: 15px;
            }

            .quotation-title h1 {
                font-size: 23px;
            }

            .quotation-title p {
                font-size: 16px;
            }

            .quotation-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .grand-total {
                justify-content: flex-end;
            }

        }

    </style>

</head>


<body>


<div class="container-fluid py-4">

    <div class="quotation-box">


        <!-- ========================= -->
        <!-- TITLE -->
        <!-- ========================= -->

        <div class="quotation-title">

            <h1>
                HIKVISION CCTV CAMERA - 4 PORT
            </h1>

            <p>
                2 Mega Pixels
            </p>

        </div>


        <!-- ========================= -->
        <!-- TABLE -->
        <!-- ========================= -->

        <div class="table-responsive">

            <table class="table table-bordered quotation-table">

                <thead>

                    <tr>

                        <!-- Select -->
                        <th class="check-column">

                            <input type="checkbox"
                                   id="selectAll">

                        </th>


                        <!-- SL -->
                        <th class="sl-column">
                            SL
                        </th>


                        <!-- Product -->
                        <th>
                            Product Name
                        </th>


                        <!-- Quantity -->
                        <th>
                            Quantity
                        </th>


                        <!-- Unit Price -->
                        <th>
                            Unit Price
                        </th>


                        <!-- Total -->
                        <th>
                            Total Price
                        </th>

                    </tr>

                </thead>


                <tbody id="quotationBody">


                    <!-- ========================= -->
                    <!-- ROW 01 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            01
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Hikvision 2MP CCTV Camera">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="4 Pcs">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="1750">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 02 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            02
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Hikvision 4 Channel DVR">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="1 Pc">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="3500">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 03 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            03
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Surveillance HDD 1TB">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="1 Pc">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="4400">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 04 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            04
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Power Adapter">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="8 Pcs">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="300">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 05 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            05
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Video Balun">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="8 Pair">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="200">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 06 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            06
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Installation Charge">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="4 Camera">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="500">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 07 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            07
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="Other Accessories">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="-">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="500">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                    <!-- ========================= -->
                    <!-- ROW 08 -->
                    <!-- ========================= -->

                    <tr>

                        <td class="check-column">

                            <input type="checkbox"
                                   class="row-check">

                        </td>


                        <td class="sl-number">
                            08
                        </td>


                        <td>

                            <input type="text"
                                   class="form-control product"
                                   value="CAT6 Cable">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control quantity"
                                   value="1m">

                        </td>


                        <td>

                            <input type="number"
                                   class="form-control price"
                                   value="25">

                        </td>


                        <td>

                            <input type="text"
                                   class="form-control row-total"
                                   readonly>

                        </td>

                    </tr>


                </tbody>


                <!-- ========================= -->
                <!-- TOTAL -->
                <!-- ========================= -->

                <tfoot>

                    <tr>

                        <td colspan="5"
                            class="text-end fw-bold fs-5">

                            TOTAL

                        </td>

                        <td>

                            <input type="text"
                                   id="grandTotal"
                                   class="form-control"
                                   readonly>

                        </td>

                    </tr>

                </tfoot>

            </table>

        </div>


        <!-- ========================= -->
        <!-- FOOTER BUTTONS -->
        <!-- ========================= -->

        <div class="quotation-footer">


            <div class="action-buttons">

                <button type="button"
                        class="btn btn-success"
                        id="addRow">

                    <i class="bi bi-plus-lg"></i>

                    Add

                </button>


                <button type="button"
                        class="btn btn-danger"
                        id="deleteRow">

                    <i class="bi bi-trash"></i>

                    Delete

                </button>

            </div>


        </div>


    </div>

</div>



<script>


/*
========================================
    CALCULATE TOTAL
========================================
*/

function calculateTotal() {

    let grandTotal = 0;


    document.querySelectorAll("#quotationBody tr")
        .forEach(function(row) {


            let quantityText =
                row.querySelector(".quantity").value;


            /*
                Quantity থেকে শুধু প্রথম number নেওয়া হচ্ছে।

                Example:
                "4 Pcs"    → 4
                "8 Pcs"    → 8
                "8 Pair"   → 8
                "4 Camera" → 4
                "1m"       → 1
                "-"        → 1
            */

            let quantity =
                parseFloat(quantityText) || 1;


            let price =
                parseFloat(
                    row.querySelector(".price").value
                ) || 0;


            let total =
                quantity * price;


            row.querySelector(".row-total").value =
                total.toLocaleString();


            grandTotal += total;

        });


    document.getElementById("grandTotal").value =
        grandTotal.toLocaleString();

}



/*
========================================
    PRICE / QUANTITY CHANGE
========================================
*/

document.addEventListener("input", function(e) {

    if (
        e.target.classList.contains("price") ||
        e.target.classList.contains("quantity")
    ) {

        calculateTotal();

    }

});



/*
========================================
    SELECT ALL
========================================
*/

document.getElementById("selectAll")
    .addEventListener("change", function() {


        document.querySelectorAll(".row-check")
            .forEach(function(check) {

                check.checked = this.checked;

            }, this);

    });



/*
========================================
    ADD NEW ROW
========================================
*/

document.getElementById("addRow")
    .addEventListener("click", function() {


        let tbody =
            document.getElementById("quotationBody");


        let row =
            document.createElement("tr");


        row.innerHTML = `

            <td class="check-column">

                <input type="checkbox"
                       class="row-check">

            </td>


            <td class="sl-number">
                00
            </td>


            <td>

                <input type="text"
                       class="form-control product"
                       placeholder="Product Name">

            </td>


            <td>

                <input type="text"
                       class="form-control quantity"
                       value="1 Pcs">

            </td>


            <td>

                <input type="number"
                       class="form-control price"
                       value="0">

            </td>


            <td>

                <input type="text"
                       class="form-control row-total"
                       value="0"
                       readonly>

            </td>

        `;


        tbody.appendChild(row);


        updateSL();


        calculateTotal();

    });



/*
========================================
    DELETE SELECTED ROW
========================================
*/

document.getElementById("deleteRow")
    .addEventListener("click", function() {


        document.querySelectorAll(
            ".row-check:checked"
        ).forEach(function(check) {


            check.closest("tr").remove();


        });


        updateSL();


        calculateTotal();


        document.getElementById(
            "selectAll"
        ).checked = false;

    });



/*
========================================
    UPDATE SL
========================================
*/

function updateSL() {


    document.querySelectorAll(
        "#quotationBody tr"
    ).forEach(function(row, index) {


        let sl =
            String(index + 1).padStart(2, "0");


        row.querySelector(
            ".sl-number"
        ).textContent = sl;


    });

}



/*
========================================
    INITIAL CALCULATION
========================================
*/

calculateTotal();

</script>


</body>
</html>