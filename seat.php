<?php

include "db.php";

/* =========================================================
   GET MOVIE AND SHOWTIME
========================================================= */

$movie_id = isset($_GET["movie_id"]) ? (int)$_GET["movie_id"] : 0;
$showtime_id = isset($_GET["showtime_id"]) ? (int)$_GET["showtime_id"] : 0;

if ($movie_id <= 0 || $showtime_id <= 0) {
    die("Invalid movie or showtime.");
}


/* =========================================================
   GET MOVIE
========================================================= */

$movie_sql = "
    SELECT *
    FROM movie
    WHERE movie_id = ?
";

$stmt = $conn->prepare($movie_sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();

$movie_result = $stmt->get_result();
$movie = $movie_result->fetch_assoc();

$stmt->close();

if (!$movie) {
    die("Movie not found.");
}


/* =========================================================
   GET SHOWTIME + HALL
========================================================= */

$showtime_sql = "
    SELECT
        s.showtime_id,
        s.show_date,
        s.show_time,
        s.ticket_price,
        h.hall_id,
        h.hall_name,
        h.location
    FROM showtime s
    JOIN hall h
        ON s.hall_id = h.hall_id
    WHERE s.showtime_id = ?
      AND s.movie_id = ?
";

$stmt = $conn->prepare($showtime_sql);
$stmt->bind_param("ii", $showtime_id, $movie_id);
$stmt->execute();

$showtime_result = $stmt->get_result();
$showtime = $showtime_result->fetch_assoc();

$stmt->close();

if (!$showtime) {
    die("Showtime not found.");
}


/* =========================================================
   GET SEATS FOR THIS HALL
========================================================= */

$seat_sql = "
    SELECT
        seat_id,
        seat_number,
        seat_type
    FROM seat
    WHERE hall_id = ?
    ORDER BY seat_number
";

$stmt = $conn->prepare($seat_sql);
$stmt->bind_param("i", $showtime["hall_id"]);
$stmt->execute();

$seat_result = $stmt->get_result();

$seats = [];

while ($row = $seat_result->fetch_assoc()) {
    $seats[] = $row;
}

$stmt->close();


/* =========================================================
   GET BOOKED SEATS
========================================================= */

$booked_sql = "
    SELECT seat_id
    FROM ticket
    WHERE showtime_id = ?
      AND status = 'Confirmed'
";

$stmt = $conn->prepare($booked_sql);
$stmt->bind_param("i", $showtime_id);
$stmt->execute();

$booked_result = $stmt->get_result();

$booked_seats = [];

while ($row = $booked_result->fetch_assoc()) {
    $booked_seats[] = (int)$row["seat_id"];
}

$stmt->close();


/* =========================================================
   SEPARATE SEATS BY TYPE
========================================================= */

$regular_seats = [];
$premium_seats = [];
$vip_seats = [];

foreach ($seats as $seat) {

    $type = strtolower(trim($seat["seat_type"]));

    if ($type == "regular") {

        $regular_seats[] = $seat;

    } elseif ($type == "premium") {

        $premium_seats[] = $seat;

    } elseif ($type == "vip") {

        $vip_seats[] = $seat;

    }
}


/* =========================================================
   POSTER PATH
========================================================= */

$poster = trim($movie["poster"]);

if ($poster == "") {

    $poster_path = "images/default.jpg";

} elseif (
    strpos($poster, "http://") === 0 ||
    strpos($poster, "https://") === 0
) {

    $poster_path = $poster;

} elseif (strpos($poster, "images/") === 0) {

    $poster_path = $poster;

} else {

    $poster_path = "images/" . basename($poster);

}


/* =========================================================
   DURATION
========================================================= */

$duration = (int)$movie["duration"];

$hours = floor($duration / 60);
$minutes = $duration % 60;

$duration_text = $hours . "h " . $minutes . "m";


/* =========================================================
   SHOW DATE
========================================================= */

$formatted_date = date(
    "d M Y",
    strtotime($showtime["show_date"])
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Select Seats | HiMovie</title>

<link rel="stylesheet" href="navbar.css">

<link rel="stylesheet" href="seat.css">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>

/* =========================================================
   SEAT SECTIONS
========================================================= */

.seat-section {

    margin-bottom: 45px;

}

.seat-section-title {

    text-align: center;

    color: #FFD166;

    font-size: 22px;

    letter-spacing: 3px;

    margin-bottom: 25px;

}


/* =========================================================
   SEAT CONTAINER
========================================================= */

.seats {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    gap: 12px;

    max-width: 850px;

    margin: auto;

}


/* =========================================================
   SEAT
========================================================= */

.seat {

    width: 42px;

    height: 38px;

    border-radius: 10px 10px 6px 6px;

    background: #64748b;

    cursor: pointer;

    position: relative;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 11px;

    font-weight: bold;

    color: white;

    transition: .25s;

}

.seat::before {

    content: "";

    position: absolute;

    width: 24px;

    height: 5px;

    top: 5px;

    left: 9px;

    border-radius: 10px;

    background: rgba(255,255,255,.4);

}

.seat:hover {

    transform: translateY(-4px);

    background: #ec4899;

    box-shadow: 0 8px 20px rgba(236,72,153,.4);

}

.seat.selected {

    background: #FFD166;

    color: #111827;

    box-shadow: 0 0 20px rgba(255,209,102,.7);

}

.seat.booked {

    background: #ef4444;

    cursor: not-allowed;

    opacity: .8;

}

.seat.booked:hover {

    transform: none;

    box-shadow: none;

}


/* =========================================================
   DIFFERENT SEAT TYPE COLORS
========================================================= */

.vip-section .seat {

    background: #9333ea;

}

.vip-section .seat:hover {

    background: #ec4899;

}

.premium-section .seat {

    background: #2563eb;

}

.premium-section .seat:hover {

    background: #ec4899;

}

.regular-section .seat {

    background: #64748b;

}


/* =========================================================
   EMPTY MESSAGE
========================================================= */

.no-seats {

    text-align: center;

    color: #bbb;

    padding: 20px;

}


/* =========================================================
   POSTER
========================================================= */

.movie-card img {

    width: 100%;

    height: 470px;

    object-fit: cover;

    display: block;

}

</style>

</head>


<body>


<!-- =====================================================
                         NAVBAR
===================================================== -->

<nav>

<a href="index.html" class="logo">

    <i class="fa-solid fa-clapperboard"></i>

    <span>HiMovie</span>

</a>

<ul>

<li>
<a href="index.html">Home</a>
</li>

<li>
<a href="movies.php">Now Showing</a>
</li>

<li>
<a href="upcomingMovies.php">Upcoming</a>
</li>

<li class="search-box">

<i class="fa-solid fa-magnifying-glass"></i>

<input
type="text"
placeholder="Search movies...">

</li>

<li>

<a href="login.php" class="login-btn">

<i class="fa-solid fa-user"></i>

Login

</a>

</li>

</ul>

</nav>


<!-- =====================================================
                       SEAT PAGE
===================================================== -->

<section class="seat-page">


<div class="seat-container">


<!-- =====================================================
                       MOVIE CARD
===================================================== -->

<div class="movie-card">

<img
src="<?php echo htmlspecialchars($poster_path); ?>"
alt="<?php echo htmlspecialchars($movie["title"]); ?>"
onerror="this.src='images/default.jpg';">


<div class="movie-content">

<span class="tag">

NOW SHOWING

</span>


<h2>

<?php echo htmlspecialchars($movie["title"]); ?>

</h2>


<p>

<?php echo htmlspecialchars($movie["genre"]); ?>

</p>


<div class="movie-info">


<div>

<i class="fa-solid fa-star"></i>

<?php echo htmlspecialchars($movie["rating"]); ?>/10

</div>


<div>

<i class="fa-regular fa-clock"></i>

<?php echo $duration_text; ?>

</div>


</div>


<div class="details">


<div class="detail">

<span>Cinema</span>

<strong>

<?php echo htmlspecialchars($showtime["hall_name"]); ?>

</strong>

</div>


<div class="detail">

<span>Date</span>

<strong>

<?php echo $formatted_date; ?>

</strong>

</div>


<div class="detail">

<span>Time</span>

<strong>

<?php echo htmlspecialchars($showtime["show_time"]); ?>

</strong>

</div>


</div>

</div>

</div>


<!-- =====================================================
                     BOOKING PANEL
===================================================== -->

<div class="booking-panel">


<div class="top-bar">


<div>

<h2>

Select Seats

</h2>

<p>

Choose your preferred seats

</p>

</div>


<div class="price-box">

<span>Ticket</span>

<h3>

Rs. <?php echo number_format($showtime["ticket_price"], 2); ?>

</h3>

</div>


</div>


<!-- =====================================================
                         SCREEN
===================================================== -->

<div class="screen-area">

<div class="screen-light"></div>

<div class="screen">

SCREEN

</div>

</div>


<!-- =====================================================
                     REGULAR SEATS
===================================================== -->

<div class="seat-section regular-section">

<h3 class="seat-section-title">

REGULAR

</h3>


<div class="seats">


<?php

if (count($regular_seats) > 0) {

    foreach ($regular_seats as $seat) {

        $seat_id = (int)$seat["seat_id"];

        $seat_number = htmlspecialchars($seat["seat_number"]);

        $is_booked = in_array($seat_id, $booked_seats);

        ?>

        <div
            class="seat <?php echo $is_booked ? 'booked' : ''; ?>"
            data-seat-id="<?php echo $seat_id; ?>"
            data-seat-number="<?php echo $seat_number; ?>"
            data-price="<?php echo $showtime["ticket_price"]; ?>"
        >

        <?php echo $seat_number; ?>

        </div>

        <?php

    }

} else {

    echo '<p class="no-seats">No regular seats available.</p>';

}

?>

</div>

</div>


<!-- =====================================================
                     PREMIUM SEATS
===================================================== -->

<div class="seat-section premium-section">

<h3 class="seat-section-title">

PREMIUM

</h3>


<div class="seats">


<?php

if (count($premium_seats) > 0) {

    foreach ($premium_seats as $seat) {

        $seat_id = (int)$seat["seat_id"];

        $seat_number = htmlspecialchars($seat["seat_number"]);

        $is_booked = in_array($seat_id, $booked_seats);

        ?>

        <div
            class="seat <?php echo $is_booked ? 'booked' : ''; ?>"
            data-seat-id="<?php echo $seat_id; ?>"
            data-seat-number="<?php echo $seat_number; ?>"
            data-price="<?php echo $showtime["ticket_price"]; ?>"
        >

        <?php echo $seat_number; ?>

        </div>

        <?php

    }

} else {

    echo '<p class="no-seats">No premium seats available.</p>';

}

?>

</div>

</div>


<!-- =====================================================
                         VIP SEATS
===================================================== -->

<div class="seat-section vip-section">

<h3 class="seat-section-title">

VIP

</h3>


<div class="seats">


<?php

if (count($vip_seats) > 0) {

    foreach ($vip_seats as $seat) {

        $seat_id = (int)$seat["seat_id"];

        $seat_number = htmlspecialchars($seat["seat_number"]);

        $is_booked = in_array($seat_id, $booked_seats);

        ?>

        <div
            class="seat <?php echo $is_booked ? 'booked' : ''; ?>"
            data-seat-id="<?php echo $seat_id; ?>"
            data-seat-number="<?php echo $seat_number; ?>"
            data-price="<?php echo $showtime["ticket_price"]; ?>"
        >

        <?php echo $seat_number; ?>

        </div>

        <?php

    }

} else {

    echo '<p class="no-seats">No VIP seats available.</p>';

}

?>

</div>

</div>


<!-- =====================================================
                         LEGEND
===================================================== -->

<div class="legend">

<div>

<div class="box available"></div>

Available

</div>


<div>

<div class="box selected-box"></div>

Selected

</div>


<div>

<div class="box taken-box"></div>

Booked

</div>

</div>


<!-- =====================================================
                     BOOKING SUMMARY
===================================================== -->

<div class="summary">


<div class="summary-left">

<h3>

Booking Summary

</h3>

<p id="selectedSeats">

No seats selected

</p>

</div>


<div class="summary-right">

<h2 id="totalPrice">

Rs.0.00

</h2>


<a href="#"
   id="continueButton"
   style="opacity:.5;pointer-events:none;">

Continue

<i class="fa-solid fa-arrow-right"></i>

</a>

</div>


</div>


</div>

</div>

</section>


<!-- =====================================================
                         JAVASCRIPT
===================================================== -->

<script>

const seats = document.querySelectorAll(".seat:not(.booked)");

const selectedSeatsText =
document.getElementById("selectedSeats");

const totalPrice =
document.getElementById("totalPrice");

const continueButton =
document.getElementById("continueButton");

let selectedSeats = [];


seats.forEach(seat => {

    seat.addEventListener("click", function() {

        const seatId =
        this.dataset.seatId;

        const seatNumber =
        this.dataset.seatNumber;

        const price =
        parseFloat(this.dataset.price);


        if (this.classList.contains("selected")) {

            this.classList.remove("selected");

            selectedSeats =
            selectedSeats.filter(
                id => id !== seatId
            );

        } else {

            this.classList.add("selected");

            selectedSeats.push(seatId);

        }


        updateSummary();

    });

});


function updateSummary() {

    const selectedElements =
    document.querySelectorAll(".seat.selected");


    let names = [];

    let total = 0;


    selectedElements.forEach(seat => {

        names.push(
            seat.dataset.seatNumber
        );

        total +=
            parseFloat(seat.dataset.price);

    });


    if (names.length === 0) {

        selectedSeatsText.innerText =
            "No seats selected";

        totalPrice.innerText =
            "Rs.0.00";


        continueButton.style.opacity =
            ".5";

        continueButton.style.pointerEvents =
            "none";

        continueButton.href =
            "#";

        return;

    }


    selectedSeatsText.innerText =
        names.join(", ");


    totalPrice.innerText =
        "Rs." + total.toFixed(2);


    continueButton.style.opacity =
        "1";

    continueButton.style.pointerEvents =
        "auto";


    continueButton.href =
        "payment.php?movie_id=<?php echo $movie_id; ?>&showtime_id=<?php echo $showtime_id; ?>&seats="
        + selectedSeats.join(",");

}

</script>


</body>

</html>