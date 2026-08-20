<?php

include "adminAuth.php";
include "../db.php";

$error = "";


/* ================= ADD SHOWTIME ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $show_date = $_POST["show_date"];
    $show_time = trim($_POST["show_time"]);
    $ticket_price = $_POST["ticket_price"];
    $movie_id = (int) $_POST["movie_id"];
    $hall_id = (int) $_POST["hall_id"];
    $admin_id = $_SESSION["admin_id"];


    /* ================= CHECK MOVIE RELEASE DATE ================= */

    $check_sql = "SELECT release_date
                  FROM Movie
                  WHERE movie_id = ?";

    $check_stmt = $conn->prepare($check_sql);

    $check_stmt->bind_param("i", $movie_id);

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {

        $_SESSION["error"] = "Movie not found.";

        $check_stmt->close();

        header("Location: addShowtime.php");
        exit();

    }

    $movie = $check_result->fetch_assoc();

    $today = date("Y-m-d");


    /*
     * Do not allow a showtime for an upcoming movie.
     */

    if ($movie["release_date"] > $today) {

        $_SESSION["error"] =
            "Showtime cannot be added for an upcoming movie.";

        $check_stmt->close();

        header("Location: addShowtime.php");
        exit();

    }

    $check_stmt->close();


    /* ================= INSERT SHOWTIME ================= */

    $sql = "INSERT INTO Showtime
            (show_date, show_time, ticket_price, movie_id, hall_id, admin_id)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssdiii",
        $show_date,
        $show_time,
        $ticket_price,
        $movie_id,
        $hall_id,
        $admin_id
    );

    if ($stmt->execute()) {

        $_SESSION["success"] = "Showtime added successfully.";

        $stmt->close();

        header("Location: manageShowtimes.php");
        exit();

    } else {

        $_SESSION["error"] =
            "Failed to add showtime. Please try again.";

        $stmt->close();

        header("Location: addShowtime.php");
        exit();

    }

}


/* ================= GET MOVIES ================= */

/*
 * Only movies that have already been released
 * are shown in the dropdown.
 */

$movie_sql = "SELECT movie_id, title
              FROM Movie
              WHERE release_date <= CURDATE()
              ORDER BY title";

$movie_result = $conn->query($movie_sql);


/* ================= GET HALLS ================= */

$hall_sql = "SELECT hall_id, hall_name
             FROM hall
             ORDER BY hall_name";

$hall_result = $conn->query($hall_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Showtime</title>

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

            <li>
                <a href="manageMovies.php">
                    <i class="fa-solid fa-film"></i>
                    Manage Movies
                </a>
            </li>

            <li class="active">
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


    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Add New Showtime</h1>


        <?php if (isset($_SESSION["error"])): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php
                echo htmlspecialchars($_SESSION["error"]);
                unset($_SESSION["error"]);
                ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="addShowtime.php">


            <!-- ================= MOVIE ================= -->

            <div class="form-group">

                <label>Movie</label>

                <select name="movie_id" required>

                    <option value="">Select Movie</option>

                    <?php if ($movie_result->num_rows > 0): ?>

                        <?php while ($movie = $movie_result->fetch_assoc()): ?>

                            <option value="<?php echo $movie["movie_id"]; ?>">

                                <?php echo htmlspecialchars($movie["title"]); ?>

                            </option>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <option value="" disabled>

                            No movies available for showtime

                        </option>

                    <?php endif; ?>

                </select>

            </div>


            <!-- ================= HALL ================= -->

            <div class="form-group">

                <label>Cinema Hall</label>

                <select name="hall_id" required>

                    <option value="">Select Hall</option>

                    <?php while ($hall = $hall_result->fetch_assoc()): ?>

                        <option value="<?php echo $hall["hall_id"]; ?>">

                            <?php echo htmlspecialchars($hall["hall_name"]); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- ================= DATE ================= -->

            <div class="form-group">

                <label>Show Date</label>

                <input
                    type="date"
                    name="show_date"
                    required>

            </div>


            <!-- ================= TIME ================= -->

            <div class="form-group">

                <label>Show Time</label>

                <input
                    type="time"
                    name="show_time"
                    required>

            </div>


            <!-- ================= PRICE ================= -->

            <div class="form-group">

                <label>Ticket Price</label>

                <input
                    type="number"
                    name="ticket_price"
                    step="0.01"
                    min="0"
                    placeholder="Enter ticket price"
                    required>

            </div>


            <button type="submit" class="save-btn">

                <i class="fa-solid fa-floppy-disk"></i>

                Save Showtime

            </button>


            <a href="manageShowtimes.php" class="cancel-btn">

                Cancel

            </a>


        </form>

    </main>

</div>

</body>
</html>
