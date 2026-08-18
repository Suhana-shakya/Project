<?php

include "adminAuth.php";
include "../db.php";


/* ================= GET MOVIE ID ================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: manageMovies.php");
    exit();

}

$movie_id = (int) $_GET["id"];


/* ================= DELETE MOVIE ================= */

$sql = "DELETE FROM Movie
        WHERE movie_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $movie_id);


if ($stmt->execute()) {

    header("Location: manageMovies.php");
    exit();

} else {

    echo "Failed to delete movie.";

}


$stmt->close();
$conn->close();

?>