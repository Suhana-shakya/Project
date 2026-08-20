<?php

include "adminAuth.php";
include "../db.php";


/* ================= GET MOVIE ID ================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    $_SESSION["error"] = "Invalid movie ID.";

    header("Location: manageMovies.php");
    exit();

}

$movie_id = (int) $_GET["id"];


/* ================= DELETE MOVIE ================= */

$sql = "DELETE FROM Movie
        WHERE movie_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $movie_id);

try{

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {

        $_SESSION["delete_success"] = "Movie deleted successfully.";

    } else {

        $_SESSION["error"] = "Movie not found.";

    }

} else {

    $_SESSION["error"] = "Failed to delete movie.";

}
}catch (mysqli_sql_exception $e) {

    $_SESSION["error"] = "This movie cannot be deleted because it is already associated with existing showtimes or tickets.";

}


$stmt->close();
$conn->close();


header("Location: manageMovies.php");
exit();

?>