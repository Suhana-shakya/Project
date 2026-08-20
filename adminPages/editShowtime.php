<?php

include "adminAuth.php";
include "../db.php";

$error = "";


/* ================= GET SHOWTIME ID ================= */

if (!isset($_GET["id"])) {

    header("Location: manageShowtimes.php");
    exit();

}

$showtime_id = (int) $_GET["id"];


/* ================= UPDATE SHOWTIME ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $show_date = $_POST["show_date"];
    $show_time = trim($_POST["show_time"]);
    $ticket_price = $_POST["ticket_price"];
    $movie_id = (int) $_POST["movie_id"];
    $hall_id = (int) $_POST["hall_id"];
    $sql = "UPDATE Showtime
            SET show_date = ?,
                show_time = ?,
                ticket_price = ?,
                movie_id = ?,
                hall_id = ?
            WHERE showtime_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssdiii",
        $show_date,
        $show_time,
        $ticket_price,
        $movie_id,
        $hall_id,
        $showtime_id
    );


   if ($stmt->execute()) {

        $_SESSION["success"] = "Showtime updated successfully.";

        header("Location: manageShowtimes.php");
        exit();

    } else {

        $_SESSION["error"] = "Failed to update showtime. Please try again.";

        header("Location: manageShowtimes.php");
        exit();

    }

    $stmt->close();
}


/* ================= GET CURRENT SHOWTIME ================= */

$sql = "SELECT *
        FROM Showtime
        WHERE showtime_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $showtime_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows != 1) {

    header("Location: manageShowtimes.php");
    exit();

}
$showtime = $result->fetch_assoc();

$stmt->close();

/* ================= GET MOVIES ================= */

$movie_sql = "SELECT movie_id, title
              FROM Movie
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

<title>Edit Showtime</title>

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


    <!-- Main Content -->

    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Edit Showtime</h1>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="editShowtime.php?id=<?php echo $showtime_id; ?>">


            <!-- MOVIE -->

            <div class="form-group">

                <label>Movie</label>
                <select name="movie_id" required>

                    <option value="">
                        Select Movie
                    </option>

                    <?php while ($movie = $movie_result->fetch_assoc()): ?>

                        <option
                            value="<?php echo $movie["movie_id"]; ?>"
                            <?php
                            if ($movie["movie_id"] == $showtime["movie_id"]) {
                                echo "selected";
                            }
                            ?>>

                            <?php echo htmlspecialchars(
                                $movie["title"]
                            ); ?>

                        </option>

                    <?php endwhile; ?>
                    </select>

            </div>


            <!-- HALL -->

            <div class="form-group">

                <label>Cinema Hall</label>

                <select name="hall_id" required>

                    <option value="">
                        Select Hall
                    </option>

                    <?php while ($hall = $hall_result->fetch_assoc()): ?>

                        <option
                            value="<?php echo $hall["hall_id"]; ?>"
                            <?php
                            if ($hall["hall_id"] == $showtime["hall_id"]) {
                                echo "selected";
                            }
                            ?>>
                            <?php echo htmlspecialchars(
                                $hall["hall_name"]
                            ); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- DATE -->

            <div class="form-group">

                <label>Show Date</label>

                <input
                    type="date"
                    name="show_date"
                    value="<?php echo htmlspecialchars($showtime["show_date"]); ?>"
                    required>
                    </div>


            <!-- TIME -->

            <div class="form-group">

                <label>Show Time</label>

                <input
                    type="time"
                    name="show_time"
                    value="<?php echo htmlspecialchars($showtime["show_time"]); ?>"
                    required>

            </div>


            <!-- PRICE -->

            <div class="form-group">

                <label>Ticket Price</label>

                <input
                    type="number"
                    name="ticket_price"
                    step="0.01"
                    min="0"
                    value="<?php echo htmlspecialchars($showtime["ticket_price"]); ?>"
                    required>

            </div>


            <!-- BUTTONS -->

            <button type="submit" class="save-btn">

                <i class="fa-solid fa-floppy-disk"></i>

                Update Showtime

            </button>


            <a
                href="manageShowtimes.php"
                class="cancel-btn">

                Cancel

            </a>
            </form>

    </main>

</div>

</body>
</html>