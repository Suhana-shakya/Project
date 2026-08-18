<?php

include "adminAuth.php";
include "../db.php";

$error = "";

/* ================= GET MOVIE ID ================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: manageMovies.php");
    exit();

}

$movie_id = (int) $_GET["id"];


/* ================= UPDATE MOVIE ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $genre = trim($_POST["genre"]);
    $duration = (int) $_POST["duration"];
    $release_date = $_POST["release_date"];


    $sql = "UPDATE Movie
            SET title = ?,
                genre = ?,
                duration = ?,
                release_date = ?
            WHERE movie_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssisi",
        $title,
        $genre,
        $duration,
        $release_date,
        $movie_id
    );


    if ($stmt->execute()) {

        header("Location: manageMovies.php");
        exit();

    } else {

        $error = "Failed to update movie. Please try again.";

    }

    $stmt->close();
}


/* ================= GET EXISTING MOVIE ================= */

$sql = "SELECT movie_id, title, genre, duration, release_date
        FROM Movie
        WHERE movie_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $movie_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows != 1) {

    header("Location: manageMovies.php");
    exit();

}

$movie = $result->fetch_assoc();

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie</title>

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
                <a href="manageMovies.hphp">
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


    <!-- Main Content -->

    <main class="main">

        <h1>Edit Movie</h1>

        <?php if (!empty($error)): ?>

            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form action="editMovie.php?id=<?php echo $movie_id; ?>"
              method="post">


            <div class="form-group">

                <label>Movie Name</label>

                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($movie["title"]); ?>"
                    required>

            </div>


            <div class="row">

                <div class="form-group">

                    <label>Genre</label>

                    <input
                        type="text"
                        name="genre"
                        value="<?php echo htmlspecialchars($movie["genre"]); ?>"
                        required>

                </div>


                <div class="form-group">

                    <label>Duration</label>

                    <input
                        type="number"
                        name="duration"
                        value="<?php echo $movie["duration"]; ?>"
                        min="1"
                        required>

                </div>

            </div>


            <div class="form-group">

                <label>Release Date</label>

                <input
                    type="date"
                    name="release_date"
                    value="<?php echo $movie["release_date"]; ?>"
                    required>

            </div>


            <button type="submit" class="save-btn">

                <i class="fa-solid fa-pen"></i>

                Update Movie

            </button>


            <a href="manageMovies.php" class="cancel-btn">

                Cancel

            </a>

        </form>

    </main>

</div>

</body>
</html>