<?php

include('dbcon.php');

session_start();


if (isset($_POST['id'])) {

    $id = $_POST['id'];


    $query = "DELETE FROM products WHERE id = ?";


    $stmt = mysqli_prepare($connection, $query);


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );


    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success_message'] = "Data deleted successfully!";

        header("Location: index.php");
        exit();

    } else {

        echo "Data Delete Failed: " . mysqli_stmt_error($stmt);

    }

} else {

    echo "Product ID not found.";

}
?>