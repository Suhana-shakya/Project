<?php

include "adminAuth.php";
include "../db.php";

if (!isset($_GET["id"])) {
    header("Location: manageShowtimes.php");
    exit();
}

$showtime_id = (int) $_GET["id"];

$sql = "DELETE FROM Showtime
        WHERE showtime_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $showtime_id);

if ($stmt->execute()) {

    header("Location: manageShowtimes.php");
    exit();

} else {

    echo "Failed to delete showtime.";

}

$stmt->close();
$conn->close();

?>