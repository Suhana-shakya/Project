<?php

include "adminAuth.php";
include "../db.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    $_SESSION["error"] = "Invalid showtime ID.";
    header("Location: manageShowtimes.php");
    exit();
}

$showtime_id = (int) $_GET["id"];

$sql = "DELETE FROM Showtime
        WHERE showtime_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $showtime_id);

try{
if ($stmt->execute()) {

        if ($stmt->affected_rows > 0) {

        $_SESSION["delete_success"] = "Showtime deleted successfully.";

    } else {

        $_SESSION["error"] = "Showtime not found.";

    }

} else {

    $_SESSION["error"] = "Failed to delete showtime.";

}
}catch (mysqli_sql_exception $e) {

    $_SESSION["error"] = "This showtime cannot be deleted because it is already associated with existing tickets.";

}

$stmt->close();
$conn->close();


header("Location: manageShowtimes.php");
exit();

?>