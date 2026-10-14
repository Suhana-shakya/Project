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
/* CHECK CURRENT MOVIE STATUS */

$check_sql = "SELECT m.status
              FROM Showtime s
              JOIN Movie m ON s.movie_id = m.movie_id
              WHERE s.showtime_id = ?";

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $showtime_id);
$check_stmt->execute();

$current_showtime = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$current_showtime) {
    header("Location: manageShowtimes.php");
    exit();
}

if ($current_showtime["status"] != "Now Showing") {
    $_SESSION["error"] =
        "Only showtimes for Now Showing movies can be edited.";

    header("Location: manageShowtimes.php");
    exit();
}

/* ================= UPDATE SHOWTIME ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $show_date = $_POST["show_date"];
    $show_time = trim($_POST["show_time"]);
    $movie_id = (int) $_POST["movie_id"];
    $hall_id = (int) $_POST["hall_id"];
    /* CHECK SELECTED MOVIE STATUS AND RELEASE DATE */

$check_sql = "SELECT status, release_date
              FROM Movie
              WHERE movie_id = ?";

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $movie_id);
$check_stmt->execute();

$selected_movie = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (
    !$selected_movie ||
    $selected_movie["status"] != "Now Showing"
) {
    $_SESSION["error"] =
        "Only Now Showing movies can have their showtimes edited.";

    header("Location: manageShowtimes.php");
    exit();
}

if ($show_date < $selected_movie["release_date"]) {
    $_SESSION["error"] =
        "Show date cannot be before the movie release date.";

    header("Location: manageShowtimes.php");
    exit();
}

if (strtotime($show_date . " " . $show_time) <= time()) {
    $_SESSION["error"] =
        "Showtime must be in the future.";

    header("Location: manageShowtimes.php");
    exit();
}
    $sql = "UPDATE Showtime
            SET show_date = ?,
                show_time = ?,
                movie_id = ?,
                hall_id = ?
            WHERE showtime_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssiii",
        $show_date,
        $show_time,
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
<link rel="stylesheet" href="sidebar.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>
<body>

<div class="container">

    <?php include "sidebar.php"; ?>


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