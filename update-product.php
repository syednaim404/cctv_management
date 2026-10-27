<?php

include('dbcon.php');

session_start();

if (isset($_POST['update_product'])) {

    $id      = $_POST['id'];
    $camera  = $_POST['camera'];
    $type    = $_POST['type'];
    $quality = $_POST['quality'];
    $pixel   = $_POST['pixel'];
    $model   = $_POST['model'];
    $price   = $_POST['price'];
    $details = $_POST['details'];


    $query = "UPDATE products SET
                camera = ?,
                type = ?,
                quality = ?,
                pixel = ?,
                model = ?,
                price = ?,
                details = ?
              WHERE id = ?";


    $stmt = mysqli_prepare($connection, $query);


    mysqli_stmt_bind_param(
        $stmt,
        "sssss dsi",
        $camera,
        $type,
        $quality,
        $pixel,
        $model,
        $price,
        $details,
        $id
    );


    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success_message'] = "Product updated successfully!";

        header("Location: index.php");
        exit();

    } else {

        echo "Data Update Failed: " . mysqli_stmt_error($stmt);

    }

}
?>