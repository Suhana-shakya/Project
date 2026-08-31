<?php

include "db.php";

$hero_sql = "SELECT movie_id, title, genre, duration, rating, description, poster, trailer_url
             FROM movie
             WHERE status = 'Now Showing'
             ORDER BY movie_id ASC
             LIMIT 1";

$hero_result = mysqli_query($conn, $hero_sql);
$hero_movie = mysqli_fetch_assoc($hero_result);
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>HiMovie</title>
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="index.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<?php include "navbar.php"; ?>

<!-- ================= HERO ================= -->

<?php if ($hero_movie) { ?>

<section class="hero"
    style="background-image: url('uploads/posters/<?php echo htmlspecialchars($hero_movie['poster']); ?>');">

    <div class="hero-overlay"></div>

    <div class="hero-content">

        <span class="badge">
            NOW SHOWING
        </span>

        <h1>
            <?php echo htmlspecialchars($hero_movie['title']); ?>
        </h1>

        <p>
            <?php echo htmlspecialchars($hero_movie['description']); ?>
        </p>

        <div class="hero-info">

            <span>
                <i class="fa-solid fa-star"></i>
                <?php echo htmlspecialchars($hero_movie['rating']); ?>/10
            </span>

            <span>
                <i class="fa-regular fa-clock"></i>
                <?php echo htmlspecialchars($hero_movie['duration']); ?> min
            </span>

            <span>
                <i class="fa-solid fa-film"></i>
                <?php echo htmlspecialchars($hero_movie['genre']); ?>
            </span>

        </div>

        <div class="hero-buttons">

            <?php if (!empty($hero_movie['trailer_url'])) { ?>

            <a href="<?php echo htmlspecialchars($hero_movie['trailer_url']); ?>"
               class="watch-btn"
               target="_blank">

                <i class="fa-solid fa-play"></i>
                Watch Trailer

            </a>

            <?php } ?>

            <a href="movieDetail.php?id=<?php echo $hero_movie['movie_id']; ?>"
               class="book-btn">

                <i class="fa-solid fa-ticket"></i>
                Book Tickets

            </a>

        </div>

    </div>

</section>

<?php } ?>



<!-- ================= NOW SHOWING ================= -->

<section class="movies-section">

<div class="section-header">

<h2>Now Showing</h2>

<p>

Experience today's biggest blockbusters.

</p>

</div>

<div class="movie-grid">

<?php

$sql = "SELECT movie_id, title, genre, duration, rating, poster
        FROM movie
        WHERE status = 'Now Showing'";

$result = mysqli_query($conn, $sql);

while ($movie = mysqli_fetch_assoc($result)) {

?>

<a href="movieDetail.php?id=<?php echo $movie['movie_id']; ?>" class="movie-link">

    <div class="movie-card">

        <div class="poster">

            <img src="uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
            alt="<?php echo htmlspecialchars($movie['title']); ?>">

            <span class="rating">

                ⭐ <?php echo $movie['rating']; ?>

            </span>

        </div>

        <div class="movie-content">

            <h3>
                <?php echo htmlspecialchars($movie['title']); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars($movie['genre']); ?>
            </p>

            <div class="bottom">

                <span>

                    <i class="fa-regular fa-clock"></i>

                    <?php echo $movie['duration']; ?> min

                </span>

                <span>
                    View Details →
                </span>

            </div>

        </div>

    </div>

</a>

<?php

}

?>

</div>
</section>

<!-- ================= UPCOMING ================= -->

<section class="movies-section">

<div class="section-header">

<h2>Upcoming Movies</h2>

<p>

Movies arriving soon in theatres.

</p>

</div>

<div class="movie-grid">

<?php

$sql = "SELECT movie_id, title, genre, duration, poster
        FROM movie
        WHERE status = 'Upcoming'";

$result = mysqli_query($conn, $sql);

while ($movie = mysqli_fetch_assoc($result)) {

?>

<a href="movieDetail.php?id=<?php echo $movie['movie_id']; ?>" class="movie-link">

    <div class="movie-card">

        <div class="poster">

            <img src="uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
            alt="<?php echo htmlspecialchars($movie['title']); ?>">

            <span class="coming">
                COMING SOON
            </span>

        </div>

        <div class="movie-content">

            <h3>
                <?php echo htmlspecialchars($movie['title']); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars($movie['genre']); ?>
            </p>

            <div class="bottom">

                <span>

                    <i class="fa-regular fa-clock"></i>

                    <?php echo $movie['duration']; ?> min

                </span>

                <span>
                    View Details →
                </span>

            </div>

        </div>

    </div>

</a>

<?php

}

?>

</div>

</section>

</body>

</html>