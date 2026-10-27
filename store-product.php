<?php

include('dbcon.php');

session_start();

if (isset($_POST['save_product'])) {

    $camera  = $_POST['camera'];
    $type    = $_POST['type'];
    $quality = $_POST['quality'];
    $pixel   = $_POST['pixel'];
    $model   = $_POST['model'];
    $price   = $_POST['price'];
    $details = $_POST['details'];


    $query = "INSERT INTO products
              (camera, type, quality, pixel, model, price, details)
              VALUES (?, ?, ?, ?, ?, ?, ?)";


    $stmt = mysqli_prepare($connection, $query);


    mysqli_stmt_bind_param(
        $stmt,
        "sssssds",
        $camera,
        $type,
        $quality,
        $pixel,
        $model,
        $price,
        $details
    );


    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success_message'] = "Product uploaded successfully!";

        header("Location: index.php");
        exit();

    } else {

        echo "Data Insert Failed: " . mysqli_stmt_error($stmt);

    }

}
?>