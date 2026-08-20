<?php

include "adminAuth.php";
include "../db.php";


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    $_SESSION["error"] = "Invalid seat ID.";

    header("Location: manageSeats.php");
    exit();

}


$seat_id = (int) $_GET["id"];


$sql = "DELETE FROM seat
        WHERE seat_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $seat_id);


try {

    if ($stmt->execute()) {

        if ($stmt->affected_rows > 0) {

            $_SESSION["delete_success"] = "Seat deleted successfully.";

        } else {

            $_SESSION["error"] = "Seat not found.";

        }

    } else {

        $_SESSION["error"] = "Failed to delete seat.";

    }

} catch (mysqli_sql_exception $e) {

    $_SESSION["error"] = "This seat cannot be deleted because it is already associated with a ticket.";

}


$stmt->close();
$conn->close();


header("Location: manageSeats.php");
exit();

?>