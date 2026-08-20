
<?php

include "adminAuth.php";
include "../db.php";
$success = "";
$error = "";

if (isset($_SESSION["success"])) {

    $success = $_SESSION["success"];

    unset($_SESSION["success"]);

}

if (isset($_SESSION["error"])) {

    $error = $_SESSION["error"];

    unset($_SESSION["error"]);

}


/* ================= ADD MOVIE ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $genre = trim($_POST["genre"]);
    $duration = (int) $_POST["duration"];
    $release_date = $_POST["release_date"];

    $director = trim($_POST["director"]);
    $cast = trim($_POST["cast"]);
    $rating = (float) $_POST["rating"];
    $description = trim($_POST["description"]);
    $trailer_url = trim($_POST["trailer_url"]);
    $language = trim($_POST["language"]);
    $status = trim($_POST["status"]);

    $admin_id = $_SESSION["admin_id"];


    /* ================= POSTER UPLOAD ================= */

    $poster = "";

    if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] == 0) {

        $upload_dir = "../uploads/posters/";

        /* Create folder if it doesn't exist */

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }


        $file_name = basename($_FILES["poster"]["name"]);

        $file_extension = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );


        $allowed_extensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (!in_array($file_extension, $allowed_extensions)) {

            $error = "Invalid poster format. Use JPG, JPEG, PNG or WEBP.";

        } else {

            /* Generate unique file name */

            $new_file_name =
                time() . "_" .
                preg_replace(
                    "/[^a-zA-Z0-9._-]/",
                    "_",
                    $file_name
                );


            $target_file = $upload_dir . $new_file_name;


            if (move_uploaded_file(
                $_FILES["poster"]["tmp_name"],
                $target_file
            )) {

                $poster = $new_file_name;

            } else {

                $error = "Failed to upload poster.";

            }

        }

    }


    /* ================= INSERT MOVIE ================= */

    if (empty($error)) {

        $sql = "INSERT INTO Movie
                (
                    title,
                    genre,
                    duration,
                    release_date,
                    director,
                    `cast`,
                    rating,
                    description,
                    poster,
                    trailer_url,
                    language,
                    status,
                    admin_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


        $stmt = $conn->prepare($sql);


        $stmt->bind_param(
            "ssisssdsssssi",
            $title,
            $genre,
            $duration,
            $release_date,
            $director,
            $cast,
            $rating,
            $description,
            $poster,
            $trailer_url,
            $language,
            $status,
            $admin_id
        );

        if ($stmt->execute()) {

            $_SESSION["success"] = "Movie added successfully.";

            header("Location: manageMovies.php");
            exit();

        } else {

            $_SESSION["error"] = "Failed to add movie. Please try again.";

            header("Location: manageMovies.php");
            exit();

        }

        $stmt->close();

    }

}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Movie</title>


    <link
        rel="stylesheet"
        href="addMovie.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

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


    <!-- ================= MAIN ================= -->

    <main class="main">


        <h1>Add New Movie</h1>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form
            action="addMovie.php"
            method="post"
            enctype="multipart/form-data"
        >


            <!-- ================= MOVIE NAME ================= -->

            <div class="form-group">

                <label>Movie Name</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Enter movie name"
                    required
                >

            </div>


            <!-- ================= GENRE + DURATION ================= -->

            <div class="row">


                <div class="form-group">

                    <label>Genre</label>

                    <input
                        type="text"
                        name="genre"
                        placeholder="e.g. Action, Comedy"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Duration (minutes)</label>

                    <input
                        type="number"
                        name="duration"
                        placeholder="e.g. 120"
                        min="1"
                        required
                    >

                </div>


            </div>


            <!-- ================= RELEASE DATE ================= -->

            <div class="form-group">

                <label>Release Date</label>

                <input
                    type="date"
                    name="release_date"
                    required
                >

            </div>


            <!-- ================= DIRECTOR ================= -->

            <div class="form-group">

                <label>Director</label>

                <input
                    type="text"
                    name="director"
                    placeholder="Enter director name"
                    required
                >

            </div>


            <!-- ================= CAST ================= -->

            <div class="form-group">

                <label>Cast</label>

                <input
                    type="text"
                    name="cast"
                    placeholder="e.g. Actor 1, Actor 2, Actor 3"
                    required
                >

            </div>


            <!-- ================= RATING + LANGUAGE ================= -->

            <div class="row">


                <div class="form-group">

                    <label>Rating</label>

                    <input
                        type="number"
                        name="rating"
                        placeholder="e.g. 8.5"
                        min="0"
                        max="10"
                        step="0.1"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Language</label>

                    <input
                        type="text"
                        name="language"
                        placeholder="e.g. Hindi"
                        required
                    >

                </div>


            </div>


            <!-- ================= DESCRIPTION ================= -->

            <div class="form-group">

                <label>Short Description</label>

                <textarea
                    name="description"
                    rows="5"
                    placeholder="Enter a short description of the movie"
                    required
                ></textarea>

            </div>


            <!-- ================= POSTER ================= -->

            <div class="form-group">

                <label>Movie Poster</label>

                <input
                    type="file"
                    name="poster"
                    accept=".jpg,.jpeg,.png,.webp"
                    required
                >

            </div>


            <!-- ================= TRAILER ================= -->

            <div class="form-group">

                <label>Trailer URL</label>

                <input
                    type="url"
                    name="trailer_url"
                    placeholder="https://www.youtube.com/..."
                >

            </div>


            <!-- ================= STATUS ================= -->

            <div class="form-group">

                <label>Status</label>

                <select name="status" required>

                    <option value="">Select Status</option>

                    <option value="Now Showing">
                        Now Showing
                    </option>

                    <option value="Upcoming">
                        Upcoming
                    </option>

                    <option value="Ended">
                        Ended
                    </option>

                </select>

            </div>


            <!-- ================= BUTTONS ================= -->

            <button
                type="submit"
                class="save-btn"
            >

                <i class="fa-solid fa-floppy-disk"></i>

                Save Movie

            </button>


            <a
                href="manageMovies.php"
                class="cancel-btn"
            >

                Cancel

            </a>


        </form>


    </main>


</div>


</body>

</html>


