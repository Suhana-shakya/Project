
<?php

include "db.php";


/* ================= GET NOW SHOWING MOVIES ================= */

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
        WHERE status = 'Now Showing'
        ORDER BY movie_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Now Showing | HiMovie</title>

<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="movies.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>


<body>


<!-- ================= NAVBAR ================= -->

<nav>

<a href="index.php" class="logo">

    <i class="fa-solid fa-clapperboard"></i>

    <span>HiMovie</span>

</a>


<ul>

    <li>
        <a href="index.php">
            Home
        </a>
    </li>


    <li>
        <a href="movies.php" class="active">
            Now Showing
        </a>
    </li>


    <li>
        <a href="upcomingMovies.php">
            Upcoming
        </a>
    </li>


    <li class="search-box">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            placeholder="Search movies..."
        >

    </li>


    <li>

        <a href="login.php" class="login-btn">

            <i class="fa-solid fa-user"></i>

            Login

        </a>

    </li>

</ul>

</nav>



<!-- ================= HERO ================= -->

<section class="hero">

    <h1>Now Showing</h1>

    <p>
        Book your tickets for the latest movies playing in theatres now.
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


        /* ================= RATING ================= */

        $rating = $movie["rating"];

        if ($rating === null || $rating === "") {

            $rating = "N/A";

        }


        /* ================= DESCRIPTION ================= */

        $description = $movie["description"];

        if ($description === null || $description === "") {

            $description = "No description available.";

        }


        /* ================= LANGUAGE ================= */

        $language = $movie["language"];

        if ($language === null || $language === "") {

            $language = "N/A";

        }

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

                    Now Showing

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



                <!-- INFO -->

                <div class="info">


                    <!-- RATING -->

                    <span>

                        <i class="fa-solid fa-star"></i>

                        <?php
                        echo htmlspecialchars($rating);
                        ?>/10

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
                        echo htmlspecialchars(
                            $movie["duration"]
                        );
                        ?> min

                    </span>



                    <!-- LANGUAGE -->

                    <span>

                        <i class="fa-solid fa-language"></i>

                        <?php
                        echo htmlspecialchars(
                            $language
                        );
                        ?>

                    </span>


                </div>



                <!-- DESCRIPTION -->

                <p>

                    <?php
                    echo htmlspecialchars(
                        $description
                    );
                    ?>

                </p>



                <!-- BOOK NOW -->

                <a
                    href="movieDetail.php?id=<?php echo $movie["movie_id"]; ?>"
                    class="btn"
                >

                    Book Now

                    <i class="fa-solid fa-ticket"></i>

                </a>


            </div>

        </div>


    <?php endwhile; ?>


<?php else: ?>


    <p style="text-align:center; font-size:18px; color:#d6d6d6;">

        No movies are currently showing.

    </p>


<?php endif; ?>


</section>


</body>

</html>
