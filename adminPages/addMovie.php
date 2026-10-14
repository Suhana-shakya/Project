
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get and clean form values
    $title = trim($_POST["title"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $duration_input = trim($_POST["duration"] ?? "");
    $release_date = trim($_POST["release_date"] ?? "");
    $director = trim($_POST["director"] ?? "");
    $cast = trim($_POST["cast"] ?? "");
    $rating_input = trim($_POST["rating"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $trailer_url = trim($_POST["trailer_url"] ?? "");
    $language = trim($_POST["language"] ?? "");

    $admin_id = (int) $_SESSION["admin_id"];
    $poster = "";

    /* ================= SERVER-SIDE VALIDATION ================= */

    // Movie title
    if ($title === "" || mb_strlen($title) > 150) {
        $error = "Movie name is required and must not exceed 150 characters.";
    }

    // Genre
    elseif ($genre === "" || mb_strlen($genre) > 50) {
        $error = "Genre is required and must not exceed 50 characters.";
    }

    // Duration
    elseif (
        $duration_input === "" ||
        !ctype_digit($duration_input) ||
        (int) $duration_input < 1 ||
        (int) $duration_input > 600
    ) {
        $error = "Duration must be a whole number between 1 and 600 minutes.";
    }

    // Release date
    elseif ($release_date === "") {
        $error = "Please select a release date.";
    } else {
        $date_object = DateTime::createFromFormat(
            "!Y-m-d",
            $release_date
        );

        $date_errors = DateTime::getLastErrors();

        if (
            !$date_object ||
            ($date_errors !== false &&
                ($date_errors["warning_count"] > 0 ||
                 $date_errors["error_count"] > 0)) ||
            $date_object->format("Y-m-d") !== $release_date
        ) {
            $error = "Please enter a valid release date.";
        }
    }

    // Director
    if (
        empty($error) &&
        ($director === "" || mb_strlen($director) > 100)
    ) {
        $error = "Director is required and must not exceed 100 characters.";
    }

    // Cast
    if (empty($error) && $cast === "") {
        $error = "Cast information is required.";
    }

    // Rating
    if (
        empty($error) &&
        (
            $rating_input === "" ||
            !is_numeric($rating_input) ||
            (float) $rating_input < 0 ||
            (float) $rating_input > 10 ||
            strlen(substr(strrchr($rating_input, ".") ?: "", 1)) > 1
        )
    ) {
        $error = "Rating must be between 0 and 10, with at most one decimal place.";
    }

    // Description
    if (empty($error) && $description === "") {
        $error = "Movie description is required.";
    }

    // Language
    if (
        empty($error) &&
        ($language === "" || mb_strlen($language) > 50)
    ) {
        $error = "Language is required and must not exceed 50 characters.";
    }

    // Optional trailer URL
    if ($trailer_url !== "" && empty($error)) {

        if (
            !filter_var($trailer_url, FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower(parse_url($trailer_url, PHP_URL_SCHEME) ?? ""),
                ["http", "https"],
                true
            ) ||
            mb_strlen($trailer_url) > 255
        ) {
            $error = "Enter a valid trailer URL beginning with http:// or https://.";
        }
    }

    /* ================= POSTER VALIDATION ================= */

    if (empty($error)) {

        if (
            !isset($_FILES["poster"]) ||
            $_FILES["poster"]["error"] !== UPLOAD_ERR_OK
        ) {
            $error = "Please select a movie poster to upload.";
        }

        elseif ($_FILES["poster"]["size"] > 5 * 1024 * 1024) {
            $error = "Poster size must not exceed 5 MB.";
        }

        else {

            $tmp_file = $_FILES["poster"]["tmp_name"];

            // Check the actual image, not only its filename
            $image_info = @getimagesize($tmp_file);

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($tmp_file);

            $allowed_images = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            if (
                $image_info === false ||
                !isset($allowed_images[$mime_type]) ||
                $image_info[2] !== IMAGETYPE_JPEG &&
                $image_info[2] !== IMAGETYPE_PNG &&
                $image_info[2] !== IMAGETYPE_WEBP
            ) {
                $error = "Poster must be a valid JPG, PNG or WEBP image.";
            }
        }
    }

    /* ================= UPLOAD POSTER ================= */

    if (empty($error)) {

        $upload_dir = "../uploads/posters/";

        if (
            !is_dir($upload_dir) &&
            !mkdir($upload_dir, 0755, true)
        ) {
            $error = "Could not create the poster upload folder.";
        }

        if (empty($error) && !is_writable($upload_dir)) {
            $error = "The poster upload folder is not writable.";
        }

        if (empty($error)) {

            $extension = $allowed_images[$mime_type];

            // Generate a unique filename
            $new_file_name = bin2hex(random_bytes(16))
                           . "." . $extension;

            $target_file = $upload_dir . $new_file_name;

            if (move_uploaded_file($tmp_file, $target_file)) {
                $poster = $new_file_name;
            } else {
                $error = "Failed to upload poster.";
            }
        }
    }

    /* ================= SET MOVIE STATUS ================= */

    if (empty($error)) {

        // Automatically calculate status from release date
        $today = date("Y-m-d");
        $one_month_ago = date(
            "Y-m-d",
            strtotime("-1 month")
        );

        if ($release_date > $today) {
            $status = "Upcoming";
        } elseif ($release_date > $one_month_ago) {
            $status = "Now Showing";
        } else {
            $status = "Archived";
        }

        $duration = (int) $duration_input;
        $rating = (float) $rating_input;

        /* ================= INSERT MOVIE ================= */

        $sql = "INSERT INTO movie
                (
                    title, genre, duration, release_date,
                    director, `cast`, rating, description,
                    poster, trailer_url, language, status, admin_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

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

                $stmt->close();

                $_SESSION["success"] = "Movie added successfully.";

                header("Location: manageMovies.php");
                exit();

            } else {

                // Remove the uploaded poster if the insert fails
                if ($poster !== "" && is_file($target_file)) {
                    unlink($target_file);
                }

                $error = "Failed to add movie. Please check the information and try again.";
                $stmt->close();
            }

        } else {

            if ($poster !== "" && is_file($target_file)) {
                unlink($target_file);
            }

            $error = "Unable to prepare the movie record.";
        }
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
        href="sidebar.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

</head>


<body>


<div class="container">


    <?php include "sidebar.php"; ?>


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
                    maxlength="150"
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
                        maxlength="50"
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
                        max="600"
                        step="1"
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
                    maxlength="100"
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
                        maxlength="50"
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
                    maxlength="5000"
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
                    maxlength="255"
                    placeholder="https://www.youtube.com/..."
                >

            </div>


            <!-- ================= STATUS =================

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

            </div> -->


            <!-- ================= BUTTONS ================= -->

            <button
                type="submit"
                class="save-btn"
            >

                <i class="fa-solid fa-floppy-disk"></i>

                Save Movie

            </button>


            <a href="manageMovies.php"
                class="cancel-btn">

                Cancel

            </a>


        </form>


    </main>


</div>


</body>

</html>


