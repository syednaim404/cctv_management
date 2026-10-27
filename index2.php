<?php
session_start();

include('dbcon.php');
include('header.php');


?>

<div class="container py-4">
    <?php if (isset($_SESSION['success_message'])) { ?>

        <div class="alert alert-success alert-dismissible fade show"
            role="alert"
            style="width: 30%;">

            <?php echo $_SESSION['success_message']; ?>

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close">
            </button>

        </div>

    <?php
        unset($_SESSION['success_message']);
    }

    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Camera Products</h2>
 <a href="hd-camera.php" class="btn btn-info">HD CAMERA</a>
 <a href="ip-camera.php" class="btn btn-info">IP CAMERA</a>
 <a href="2mp-8port.php" class="btn btn-info">2mp-8port</a>
        <button type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#exampleModal">
            Add Product
        </button>
       
    </div>


    <div class="table-responsive">

        <table class="table table-bordered table-striped">

            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Camera</th>
                    <th>Type</th>
                    <th>Quality</th>
                    <th>Pixel</th>
                    <th>Model</th>
                    <th>Price</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>


            <tbody>

                <?php

                $query = "SELECT * FROM products";

                $stmt = mysqli_prepare($connection, $query);

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {

                ?>

                    <tr>

                        <td>
                            <?php echo $row['id']; ?>
                        </td>

                        <td>
                            <?php echo $row['camera']; ?>
                        </td>

                        <td>
                            <?php echo $row['type']; ?>
                        </td>

                        <td>
                            <?php echo $row['quality']; ?>
                        </td>

                        <td>
                            <?php echo $row['pixel']; ?>
                        </td>

                        <td>
                            <?php echo $row['model']; ?>
                        </td>

                        <td>
                            <?php echo $row['price']; ?>
                        </td>

                        <td>
                            <?php echo $row['details']; ?>
                        </td>

                        <td>

                            <div class="d-flex gap-1">

                                <a href="edit-product.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <form action="delete-product.php" method="POST">

                                    <input type="hidden"
                                        name="id"
                                        value="<?php echo $row['id']; ?>">

                                    <button type="submit"
                                        name="delete"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this product?');">
                                        Delete
                                    </button>

                                </form>

                            </div>


                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>



<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    Add New Product
                </h1>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <form action="store-product.php" method="POST">

                <div class="modal-body">

                    <div class="row">

                        <!-- Camera -->
                        <div class="col-md-6 mb-3">
                            <label for="camera" class="form-label">
                                Camera
                            </label>


                            <select name="camera" id="camera" class="form-select">
                                <option value="">Select Camera</option>
                                <option value="Hikvision">Hikvision</option>
                                <option value="Dahua">Dahua</option>
                                <option value="CP Plus">CP Plus</option>
                                <option value="Uniview">Uniview</option>
                            </select>
                        </div>


                        <!-- Type -->
                        <div class="col-md-6 mb-3">

                            <label for="type" class="form-label">
                                Type
                            </label>

                            <select name="type"
                                id="type"
                                class="form-select">

                                <option value="">Select Type</option>

                                <option value="IP Camera">
                                    IP Camera
                                </option>

                                <option value="Dome Camera">
                                    Dome Camera
                                </option>

                                <option value="Bullet Camera">
                                    Bullet Camera
                                </option>

                                <option value="Analog Camera">
                                    Analog Camera
                                </option>

                            </select>

                        </div>


                        <!-- Quality -->
                        <div class="col-md-6 mb-3">

                            <label for="quality" class="form-label">
                                Quality
                            </label>

                            <select name="quality"
                                id="quality"
                                class="form-select">

                                <option value="">Select Quality</option>

                                <option value="HD">
                                    HD
                                </option>

                                <option value="Full HD">
                                    Full HD
                                </option>

                                <option value="Ultra HD">
                                    Ultra HD
                                </option>

                                <option value="4K">
                                    4K
                                </option>

                            </select>

                        </div>


                        <!-- Pixel -->
                        <div class="col-md-6 mb-3">

                            <label for="pixel" class="form-label">
                                Pixel
                            </label>

                            <select name="pixel"
                                id="pixel"
                                class="form-select">

                                <option value="">Select Pixel</option>

                                <option value="2MP">
                                    2MP
                                </option>

                                <option value="3MP">
                                    3MP
                                </option>

                                <option value="4MP">
                                    4MP
                                </option>

                                <option value="5MP">
                                    5MP
                                </option>

                                <option value="8MP">
                                    8MP
                                </option>

                            </select>

                        </div>


                        <!-- Model -->
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">
                                Model
                            </label>

                            <input type="text"
                                name="model"
                                id="model"
                                class="form-control"
                                placeholder="Enter model number">
                        </div>


                        <!-- Price -->
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">
                                Price
                            </label>

                            <input type="number"
                                name="price"
                                id="price"
                                class="form-control"
                                placeholder="Enter price"
                                step="0.01">
                        </div>


                        <!-- Details -->
                        <div class="col-12 mb-3">
                            <label for="details" class="form-label">
                                Details
                            </label>

                            <textarea name="details"
                                id="details"
                                class="form-control"
                                rows="4"
                                placeholder="Enter product details"></textarea>
                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Close
                    </button>

                    <button type="submit"
                        name="save_product"
                        class="btn btn-primary">
                        Save Product
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>




<?php include('footer.php') ?>