<?php

include "adminAuth.php";
include "../db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $genre = trim($_POST["genre"]);
    $duration = (int) $_POST["duration"];
    $release_date = $_POST["release_date"];

    $admin_id = $_SESSION["admin_id"];

    // Insert movie
    $sql = "INSERT INTO Movie
            (title, genre, duration, release_date, admin_id)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssisi",
        $title,
        $genre,
        $duration,
        $release_date,
        $admin_id
    );

    if ($stmt->execute()) {

        header("Location: manageMovies.php");
        exit();

    } else {

        $error = "Failed to add movie. Please try again.";

    }

    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie</title>

    <link rel="stylesheet" href="addMovie.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>

<div class="container">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <h2>HiMovie</h2>

        <ul>

            <li>
                <a href="dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li class="active">
                <a href="manageMovies.php">
                    <i class="fa-solid fa-film"></i>
                    Manage Movies
                </a>
            </li>

            <li>
                <a href="manageShowtimes.php">
                    <i class="fa-solid fa-clock"></i>
                    Showtimes
                </a>
            </li>

            <li>
                <a href="manageSeats.php">
                    <i class="fa-solid fa-chair"></i>
                    Manage Seats
                </a>
            </li>

            <li>
                <a href="manageUsers.php">
                    <i class="fa-solid fa-users"></i>
                    Manage Users
                </a>
            </li>

            <li>
                <a href="manageTicketStatus.php">
                    <i class="fa-solid fa-ticket"></i>
                    Manage Ticket Status
                </a>
            </li>

            <li>
                <a href="adminLogout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </li>

        </ul>

    </aside>


    <!-- Main -->

    <main class="main">

        <h1>Add New Movie</h1>

        <form action="addMovie.php" method="post">

            <div class="form-group">
                <label>Movie Name</label>
                <input
                    type="text"
                    name="title"
                    placeholder="Enter movie name"
                    required>
            </div>

            <div class="row">

                <div class="form-group">
                    <label>Genre</label>
                    <input
                        type="text"
                        name="genre"
                        placeholder="Genre"
                        required>
                </div>

                <div class="form-group">
                    <label>Duration</label>
                    <input
                        type="number"
                        name="duration"
                        placeholder="Duration in minutes"
                        min="1"
                        required>
                </div>

            </div>

            <div class="form-group">
                <label>Release Date</label>
                <input
                    type="date"
                    name="release_date"
                    required>
            </div>

            <button type="submit" class="save-btn">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Movie
            </button>

        </form>

    </main>

</div>

</body>
</html>