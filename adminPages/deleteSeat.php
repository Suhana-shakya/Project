<?php

include "adminAuth.php";
include "../db.php";

if (isset($_GET["id"])) {

    $seat_id = (int) $_GET["id"];

    $sql = "DELETE FROM seat WHERE seat_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $seat_id);

    if ($stmt->execute()) {

        header("Location: manageSeats.php");
        exit();

    } else {

        echo "Failed to delete seat.";

    }

    $stmt->close();
}

$conn->close();

?>