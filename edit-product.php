<?php

include('dbcon.php');


// ID check
if (isset($_GET['id'])) {

    $id = $_GET['id'];


    $query = "SELECT * FROM products WHERE id = ?";


    $stmt = mysqli_prepare($connection, $query);


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    if (mysqli_num_rows($result) > 0) {

        $row = mysqli_fetch_assoc($result);

    } else {

        echo "Product not found.";
        exit();

    }

} else {

    echo "Product ID not found.";
    exit();

}


include('header.php');

?>

<div class="container py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Update Product</h2>

    </div>


    <!-- Product Form -->
    <div class="card shadow-sm">

        <div class="card-header">
            <h5 class="mb-0">Product Information</h5>
        </div>


        <div class="card-body">

            <form action="update-product.php" method="POST">

                <!-- Product ID -->
                <input type="hidden"
                    name="id"
                    value="<?php echo $row['id']; ?>">


                <div class="row">

                    <!-- Camera -->
                    <div class="col-md-4 mb-3">

                        <label for="camera" class="form-label">
                            Camera
                        </label>

                        <select name="camera" id="camera" class="form-select">
                            <option value="<?php echo $row['camera']; ?>"><?php echo $row['camera']; ?></option>
                            <option value="Hikvision">Hikvision</option>
                            <option value="Dahua">Dahua</option>
                            <option value="CP Plus">CP Plus</option>
                            <option value="Uniview">Uniview</option>
                        </select>

                    </div>


                    <!-- Type -->
                    <div class="col-md-4 mb-3">

                        <label for="type" class="form-label">
                            Type
                        </label>

                        <select name="type" id="type" class="form-select">
                            <option value="<?php echo $row['type']; ?>"><?php echo $row['type']; ?></option>
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
                    <div class="col-md-4 mb-3">

                        <label for="quality" class="form-label">
                            Quality
                        </label>

                        <select name="quality" id="quality" class="form-select">
                            <option value="<?php echo $row['quality']; ?>"><?php echo $row['quality']; ?></option>
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
                    <div class="col-md-4 mb-3">

                        <label for="pixel" class="form-label">
                            Pixel
                        </label>

                         <select name="pixel" id="pixel" class="form-select">
                            <option value="<?php echo $row['pixel']; ?>"><?php echo $row['pixel']; ?></option>
                            
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
                    <div class="col-md-4 mb-3">

                        <label for="model" class="form-label">
                            Model
                        </label>

                        <input type="text"
                            name="model"
                            id="model"
                            class="form-control"
                            value="<?php echo $row['model']; ?>"
                            placeholder="Enter model number">

                    </div>


                    <!-- Price -->
                    <div class="col-md-4 mb-3">

                        <label for="price" class="form-label">
                            Price
                        </label>

                        <input type="number"
                            name="price"
                            id="price"
                            class="form-control"
                            value="<?php echo $row['price']; ?>"
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
                            rows="6"
                            placeholder="Enter product details"><?php echo $row['details']; ?></textarea>

                    </div>

                </div>


                <!-- Buttons -->
                <div class="mt-3">

                    <button type="submit"
                        name="update_product"
                        class="btn btn-primary">

                        Update Product

                    </button>


                    <a href="index.php"
                        class="btn btn-secondary">

                        Back

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<?php

include('footer.php');

?>