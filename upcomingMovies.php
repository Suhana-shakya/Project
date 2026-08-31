<?php

include "db.php";


/* ================= GET UPCOMING MOVIES ================= */

$sql = "SELECT
            movie_id,
            title,
            genre,
            duration,
            release_date,
            rating,
            description,
            poster,
            language,
            status
        FROM movie
        WHERE status = 'Upcoming'
        ORDER BY release_date;

$result = $conn->query($sql);


/* ================= FORMAT DURATION ================= */

function formatDuration($minutes)
{
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($hours > 0 && $mins > 0) {

        return $hours . "h " . $mins . "m";

    } elseif ($hours > 0) {

        return $hours . "h";

    } else {

        return $mins . "m";

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Upcoming Movies | HiMovie</title>
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="upcoming.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>


<body>


<!-- ================= NAVBAR ================= -->
<?php include "navbar.php"; ?>

<!-- ================= HERO ================= -->

<section class="hero">

    <h1>
        Upcoming Movies
    </h1>

    <p>
        Discover the biggest movies arriving in theatres soon.
    </p>

</section>



<!-- ================= MOVIE LIST ================= -->

<section class="movie-list">


<?php if ($result->num_rows > 0): ?>


    <?php while ($movie = $result->fetch_assoc()): ?>


        <?php

        /* ================= POSTER ================= */

        $poster = trim($movie["poster"] ?? "");

        if ($poster == "") {

            $poster = "images/default.jpg";

        } else {

            $poster = "uploads/posters/" . $poster;

        }


        /* ================= RELEASE DATE ================= */

        $release_date = date(
            "d F Y",
            strtotime($movie["release_date"])
        );


        /* ================= DURATION ================= */

        $duration = formatDuration(
            (int)$movie["duration"]
        );

        ?>


        <!-- ================= MOVIE CARD ================= -->

        <div class="movie-card">


            <!-- POSTER -->

            <div class="poster">

                <img
                    src="<?php echo htmlspecialchars($poster); ?>"
                    alt="<?php echo htmlspecialchars($movie["title"]); ?>"
                >


                <span class="badge">

                    Coming Soon

                </span>

            </div>



            <!-- DETAILS -->

            <div class="details">


                <h2>

                    <?php
                    echo htmlspecialchars(
                        $movie["title"]
                    );
                    ?>

                </h2>



                <div class="info">


                    <!-- RELEASE DATE -->

                    <span>

                        <i class="fa-solid fa-calendar"></i>

                        <?php
                        echo $release_date;
                        ?>

                    </span>



                    <!-- GENRE -->

                    <span>

                        <i class="fa-solid fa-film"></i>

                        <?php
                        echo htmlspecialchars(
                            $movie["genre"]
                        );
                        ?>

                    </span>



                    <!-- DURATION -->

                    <span>

                        <i class="fa-regular fa-clock"></i>

                        <?php
                        echo $duration;
                        ?>

                    </span>


                </div>



                <!-- DESCRIPTION -->

                <p>

                    <?php

                    echo htmlspecialchars(
                        $movie["description"] ?? "No description available."
                    );

                    ?>

                </p>



                <!-- MORE DETAILS -->

                <a
                    href="movieDetail.php?id=<?php echo $movie["movie_id"]; ?>"
                    class="btn"
                >

                    More Details

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


            </div>

        </div>


    <?php endwhile; ?>


<?php else: ?>


    <div class="no-movies">

        <p>
            No upcoming movies available.
        </p>

    </div>


<?php endif; ?>


</section>


</body>

</html>