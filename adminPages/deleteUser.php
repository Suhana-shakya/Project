<?php

include "adminAuth.php";
include "../db.php";

if (isset($_GET["id"])) {

    $customer_id = (int) $_GET["id"];

    $sql = "DELETE FROM customer
            WHERE customer_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $customer_id);

    if ($stmt->execute()) {

        header("Location: manageUsers.php");
        exit();

    } else {

        echo "Failed to delete user.";

    }

    $stmt->close();

} else {

    header("Location: manageUsers.php");
    exit();

}

$conn->close();

?>