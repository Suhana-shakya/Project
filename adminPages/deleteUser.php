<?php

include "adminAuth.php";
include "../db.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    $_SESSION["error"] = "Invalid customer ID.";
    header("Location: manageUsers.php");
    exit();
}

$customer_id = (int) $_GET["id"];

$sql = "DELETE FROM customer
        WHERE customer_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $customer_id);

try {

    if ($stmt->execute()) {

        if ($stmt->affected_rows > 0) {

            $_SESSION["delete_success"] = "User deleted successfully.";

        } else {

            $_SESSION["error"] = "User not found.";

        }

    } else {

        $_SESSION["error"] = "Failed to delete user.";

    }

} catch (mysqli_sql_exception $e) {

    $_SESSION["error"] =
        "This user cannot be deleted because they have existing tickets.";

}

$stmt->close();
$conn->close();

header("Location: manageUsers.php");
exit();

?>