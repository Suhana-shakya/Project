<?php

include "db.php";


// ================= GET MOVIE ID =================

if (!isset($_GET["id"])) {

    header("Location: movies.php");
    exit();

}

$movie_id = (int) $_GET["id"];


// ================= GET MOVIE =================

$sql = "SELECT
            movie_id,
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
            status
        FROM movie
        WHERE movie_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $movie_id);

$stmt->execute();

$result = $stmt->get_result();


// ================= CHECK MOVIE =================

if ($result->num_rows == 0) {

    header("Location: movies.php");
    exit();

}

$movie = $result->fetch_assoc();

$stmt->close();


// ================= POSTER =================

$poster = trim($movie["poster"] ?? "");

if ($poster == "") {

    $poster = "images/default.jpg";

} else {

    $poster = "uploads/posters/" . $poster;

}


// ================= DURATION =================

$minutes = (int) $movie["duration"];

$hours = floor($minutes / 60);
$remaining_minutes = $minutes % 60;

if ($hours > 0 && $remaining_minutes > 0) {

    $duration = $hours . "h " . $remaining_minutes . "m";

} elseif ($hours > 0) {

    $duration = $hours . "h";

} else {

    $duration = $remaining_minutes . "m";

}


// ================= RELEASE DATE =================

$release_date = date(
    "d F Y",
    strtotime($movie["release_date"])
);


// ================= GET SHOWTIMES =================

$showtime_sql = "SELECT
                    s.showtime_id,
                    s.show_date,
                    s.show_time,
                    s.ticket_price,
                    h.hall_id,
                    h.hall_name
                 FROM showtime s
                 INNER JOIN hall h
                    ON s.hall_id = h.hall_id
                 WHERE s.movie_id = ?
                 AND s.show_date >= CURDATE()
                 ORDER BY s.show_date, s.show_time";

$showtime_stmt = $conn->prepare($showtime_sql);

$showtime_stmt->bind_param(
    "i",
    $movie_id
);

$showtime_stmt->execute();

$showtime_result = $showtime_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    <?php echo htmlspecialchars($movie["title"]); ?> | HiMovie
</title>

<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="movieDetail.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>


<body>
<?php include "navbar.php"; ?>

<!-- ================= MOVIE PAGE ================= -->

<section class="movie-page">


<div class="movie-container">


<!-- ================= POSTER ================= -->

<div class="poster">

    <img
        src="<?php echo htmlspecialchars($poster); ?>"
        alt="<?php echo htmlspecialchars($movie["title"]); ?>"
    >


    <span class="badge">

        <?php

        if ($movie["status"] == "Now Showing") {

            echo "NOW SHOWING";

        } else {

            echo "COMING SOON";

        }

        ?>

    </span>

</div>



<!-- ================= DETAILS ================= -->

<div class="details">


<h1>

    <?php
    echo htmlspecialchars(
        strtoupper($movie["title"])
    );
    ?>

</h1>



<!-- ================= STATS ================= -->

<div class="stats">


    <span>

        <i class="fa-solid fa-star"></i>

        <?php

        echo $movie["rating"] !== null
            ? htmlspecialchars($movie["rating"]) . " / 10"
            : "N/A";

        ?>

    </span>


    <span>

        <i class="fa-regular fa-clock"></i>

        <?php
        echo $duration;
        ?>

    </span>


    <span>

        <i class="fa-solid fa-film"></i>

        <?php
        echo htmlspecialchars($movie["genre"]);
        ?>

    </span>


    <span>

        <i class="fa-solid fa-language"></i>

        <?php
        echo htmlspecialchars(
            $movie["language"] ?? "N/A"
        );
        ?>

    </span>


</div>



<!-- ================= DESCRIPTION ================= -->

<p class="description">

    <?php

    echo htmlspecialchars(
        $movie["description"] ?? "No description available."
    );

    ?>

</p>



<!-- ================= INFORMATION ================= -->

<h2>

    Movie Information

</h2>


<div class="movie-info">


    <!-- DIRECTOR -->

    <div class="info-row">

        <span class="title">
            Director
        </span>

        <span>

            <?php

            echo htmlspecialchars(
                $movie["director"] ?? "N/A"
            );

            ?>

        </span>

    </div>



    <!-- CAST -->

    <div class="info-row">

        <span class="title">
            Cast
        </span>

        <span>

            <?php

            echo htmlspecialchars(
                $movie["cast"] ?? "N/A"
            );

            ?>

        </span>

    </div>



    <!-- RELEASE DATE -->

    <div class="info-row">

        <span class="title">
            Release Date
        </span>

        <span>

            <?php
            echo $release_date;
            ?>

        </span>

    </div>


</div>



<!-- ================= TRAILER ================= -->

<?php if (!empty($movie["trailer_url"])): ?>

<div class="buttons">

    <a
        href="<?php echo htmlspecialchars($movie["trailer_url"]); ?>"
        target="_blank"
        class="watch-btn"
    >

        <i class="fa-solid fa-play"></i>

        Watch Trailer

    </a>

</div>

<?php endif; ?>


</div>

</div>



<!-- ===================================================
                    BOOKING
=================================================== -->

<?php if ($movie["status"] == "Now Showing"): ?>

<div class="booking-container" id="booking">

    <h2>
        Book Your Ticket
    </h2>

    <?php if ($showtime_result->num_rows > 0): ?>

        <?php
        /* ================= GROUP SHOWTIMES BY DATE ================= */

        $dates = [];

        while ($showtime = $showtime_result->fetch_assoc()) {

            $date = $showtime["show_date"];

            if (!isset($dates[$date])) {

                $dates[$date] = [];

            }

            $dates[$date][] = $showtime;
        }
        ?>


        <form
            action="seat.php"
            method="GET"
            id="bookingForm"
        >

            <!-- ================= DATE ================= -->

            <div class="booking-box">

                <h3>
                    Select Date
                </h3>

                <div class="option-group">

                    <?php foreach ($dates as $date => $date_showtimes): ?>

                        <input
                            type="radio"
                            id="date_<?php echo $date; ?>"
                            name="show_date"
                            value="<?php echo $date; ?>"
                            required
                        >

                        <label
                            for="date_<?php echo $date; ?>"
                        >

                            <?php
                            echo date(
                                "d M",
                                strtotime($date)
                            );
                            ?>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- ================= SHOWTIME ================= -->

            <div class="booking-box" id="showtimeBox" style="display: none;">

                <h3>
                    Select Showtime
                </h3>

                <div class="option-group">

                    <?php foreach ($dates as $date => $date_showtimes): ?>

                        <?php foreach ($date_showtimes as $showtime): ?>

                            <input
                                type="radio"
                                id="showtime_<?php echo $showtime["showtime_id"]; ?>"
                                name="showtime_id"
                                value="<?php echo $showtime["showtime_id"]; ?>"
                                data-date="<?php echo $date; ?>"
                                data-hall-id="<?php echo $showtime["hall_id"]; ?>"
                                required
                            >

                            <label
                                for="showtime_<?php echo $showtime["showtime_id"]; ?>"
                                data-showtime-date="<?php echo $date; ?>"
                            >

                                <?php
                                echo date(
                                    "g:i A",
                                    strtotime($showtime["show_time"])
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $showtime["hall_name"]
                                );
                                ?>

                            </label>

                        <?php endforeach; ?>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- ================= HIDDEN VALUES ================= -->

            <input
                type="hidden"
                name="movie_id"
                value="<?php echo $movie_id; ?>"
            >

            <input
                type="hidden"
                name="hall_id"
                id="hall_id"
            >


            <!-- ================= BUTTON ================= -->

            <div class="select-seat">

                <button
                    type="submit"
                    class="seat-btn"
                >

                    Select Seats

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </div>

        </form>


        <!-- ================= DATE / SHOWTIME SCRIPT ================= -->

        <script>

            const dateOptions =
                document.querySelectorAll(
                    'input[name="show_date"]'
                );

            const showtimeOptions =
                document.querySelectorAll(
                    'input[name="showtime_id"]'
                );

            const showtimeLabels =
                document.querySelectorAll(
                    '[data-showtime-date]'
                );

            const hallInput =
                document.getElementById("hall_id");

            const showtimeBox =
                document.getElementById("showtimeBox");


            /* ================= INITIAL STATE ================= */

            /* Hide all showtimes initially */

            showtimeOptions.forEach(function(showtime) {

                showtime.style.display = "none";

            });

            showtimeLabels.forEach(function(label) {

                label.style.display = "none";

            });


            /* ================= DATE SELECTION ================= */

            dateOptions.forEach(function(dateOption) {

                dateOption.addEventListener("change", function() {

                    const selectedDate = this.value;


                    /* ================= SHOW SHOWTIME BOX ================= */

                    showtimeBox.style.display = "block";


                    /* ================= RESET SHOWTIME ================= */

                    showtimeOptions.forEach(function(showtime) {

                        showtime.checked = false;

                    });


                    /* ================= SHOW/HIDE LABELS ================= */

                    showtimeLabels.forEach(function(label) {

                        if (
                            label.dataset.showtimeDate === selectedDate
                        ) {

                            label.style.display = "inline-block";

                        } else {

                            label.style.display = "none";

                        }

                    });


                    /* ================= RESET HALL ================= */

                    hallInput.value = "";

                });

            });


            /* ================= SHOWTIME SELECTION ================= */

            showtimeOptions.forEach(function(showtime) {

                showtime.addEventListener("change", function() {

                    hallInput.value =
                        this.dataset.hallId;

                });

            });

        </script>


    <?php else: ?>

        <p class="no-showtimes">

            No showtimes are currently available
            for this movie.

        </p>

    <?php endif; ?>

</div>

<?php endif; ?>


</section>


</body>

</html>