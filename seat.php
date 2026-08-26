<?php

include "db.php";

/* =====================================================
   GET BOOKING INFORMATION
===================================================== */

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;
$hall_id = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;

if ($movie_id <= 0 || $showtime_id <= 0 || $hall_id <= 0) {
    die("Invalid booking information.");
}


/* =====================================================
   GET MOVIE
===================================================== */

$movie_sql = "
    SELECT *
    FROM movie
    WHERE movie_id = ?
";

$stmt = $conn->prepare($movie_sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();

$movie_result = $stmt->get_result();

if ($movie_result->num_rows == 0) {
    die("Movie not found.");
}

$movie = $movie_result->fetch_assoc();


/* =====================================================
   GET SHOWTIME + HALL
===================================================== */

$show_sql = "
    SELECT
        s.showtime_id,
        s.show_date,
        s.show_time,
        s.ticket_price,
        h.hall_id,
        h.hall_name,
        h.location
    FROM showtime s
    INNER JOIN hall h
        ON s.hall_id = h.hall_id
    WHERE s.showtime_id = ?
      AND s.movie_id = ?
      AND s.hall_id = ?
";

$stmt = $conn->prepare($show_sql);
$stmt->bind_param("iii", $showtime_id, $movie_id, $hall_id);
$stmt->execute();

$show_result = $stmt->get_result();

if ($show_result->num_rows == 0) {
    die("Showtime not found.");
}

$show = $show_result->fetch_assoc();


/* =====================================================
   GET SEATS
===================================================== */

$seat_sql = "
    SELECT
        seat_id,
        seat_number,
        seat_type
    FROM seat
    WHERE hall_id = ?
    ORDER BY seat_id
";

$stmt = $conn->prepare($seat_sql);
$stmt->bind_param("i", $hall_id);
$stmt->execute();

$seat_result = $stmt->get_result();


/* =====================================================
   GET BOOKED SEATS
===================================================== */

$booked_seats = [];

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

while ($row = $booked_result->fetch_assoc()) {
    $booked_seats[] = (int)$row['seat_id'];
}


/* =====================================================
   PRICE SETTINGS
=====================================================

   Before 12 AM:
   Regular  = 250
   Premium  = 350
   VIP      = 500

   12 AM and later:
   Regular  = 300
   Premium  = 400
   VIP      = 550
===================================================== */

$show_time = strtotime($show['show_time']);

$midnight = strtotime("00:00:00");
$noon_cutoff = strtotime("12:00:00");

/*
   Your previous rule was:
   before/at 12 AM = cheaper
   after 12 AM = 450

   Since a normal cinema showtime can be PM,
   we use 12:00 AM as the late-night boundary.

   For example:
   10:30 PM -> normal price
   11:30 PM -> normal price
   12:30 AM -> late-night price
*/

$hour = (int)date("H", $show_time);

if ($hour >= 0 && $hour < 6) {

    $regular_price = 300;
    $premium_price = 400;
    $vip_price = 550;

} else {

    $regular_price = 250;
    $premium_price = 350;
    $vip_price = 500;
}


/* =====================================================
   POSTER PATH
===================================================== */

$poster = trim($movie['poster']);

if ($poster == "") {

    $poster_path = "images/default-poster.jpg";

} else {

    /*
       Your database should contain something like:

       uploads/poster/obsession.jpg

       Therefore the browser path becomes:

       uploads/poster/obsession.jpg
    */

    $poster_path = $poster;
}


/* =====================================================
   FORMAT DATE
===================================================== */

$show_date = date(
    "d M Y",
    strtotime($show['show_date'])
);


/* =====================================================
   FORMAT TIME
===================================================== */

$formatted_time = date(
    "h:i A",
    strtotime($show['show_time'])
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Select Seats | HiMovie
</title>

<link rel="stylesheet"
href="navbar.css">

<link rel="stylesheet"
href="seat.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>


<body>


<!-- =====================================================
                         NAVBAR
===================================================== -->

<nav>

<a href="index.php"
class="logo">

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

<a href="movies.php">
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

<a href="login.php"
class="login-btn">

<i class="fa-solid fa-user"></i>

Login

</a>

</li>

</ul>

</nav>



<!-- =====================================================
                     PAGE
===================================================== -->

<section class="seat-page">


<div class="seat-container">


<!-- =====================================================
                       MOVIE CARD
===================================================== -->

<div class="movie-card">


<?php if ($poster != ""): ?>

<img
src="<?php echo htmlspecialchars($poster_path); ?>"
alt="<?php echo htmlspecialchars($movie['title']); ?>"
onerror="this.src='images/default-poster.jpg';"
>

<?php else: ?>

<img
src="images/default-poster.jpg"
alt="Movie Poster"
>

<?php endif; ?>


<div class="movie-content">


<span class="tag">

NOW SHOWING

</span>


<h2>

<?php
echo htmlspecialchars($movie['title']);
?>

</h2>


<p>

<?php
echo htmlspecialchars($movie['genre']);
?>

</p>


<div class="movie-info">


<div>

<i class="fa-solid fa-star"></i>

<?php
echo htmlspecialchars($movie['rating']);
?>

/10

</div>


<div>

<i class="fa-regular fa-clock"></i>

<?php

$duration = (int)$movie['duration'];

$hours = floor($duration / 60);

$minutes = $duration % 60;

echo $hours . "h ";

if ($minutes > 0) {
    echo $minutes . "m";
}

?>

</div>


</div>



<div class="details">


<div class="detail">

<span>
Cinema
</span>

<strong>

<?php
echo htmlspecialchars($show['hall_name']);
?>

</strong>

</div>


<div class="detail">

<span>
Date
</span>

<strong>

<?php
echo $show_date;
?>

</strong>

</div>


<div class="detail">

<span>
Time
</span>

<strong>

<?php
echo $formatted_time;
?>

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

<span>
Starting From
</span>

<h3>
Rs. <?php echo $regular_price; ?>
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
                     SEAT LEGEND
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
                  REGULAR SEATS
===================================================== -->

<h3 class="section-title">

REGULAR

<span class="seat-price">

Rs. <?php echo $regular_price; ?>

</span>

</h3>


<div class="seat-grid">


<?php

$regular_found = false;


/*
   Because the seat table contains all seat types,
   we first get the rows and display only Regular.
*/

$seat_result->data_seek(0);


while ($seat = $seat_result->fetch_assoc()):

    if (
        strtolower(trim($seat['seat_type'])) !=
        'regular'
    ) {
        continue;
    }

    $regular_found = true;

    $seat_id = (int)$seat['seat_id'];

    $seat_number =
        htmlspecialchars($seat['seat_number']);

    $is_booked =
        in_array($seat_id, $booked_seats);

?>


<div
class="seat <?php echo $is_booked ? 'taken' : ''; ?>"
data-seat-id="<?php echo $seat_id; ?>"
data-seat-number="<?php echo $seat_number; ?>"
data-seat-type="Regular"
data-price="<?php echo $regular_price; ?>"
>

<?php
echo $seat_number;
?>

</div>


<?php endwhile; ?>


<?php if (!$regular_found): ?>

<p class="no-seats">
No Regular seats available.
</p>

<?php endif; ?>

</div>



<!-- =====================================================
                  PREMIUM SEATS
===================================================== -->

<h3 class="section-title">

PREMIUM

<span class="seat-price">

Rs. <?php echo $premium_price; ?>

</span>

</h3>


<div class="seat-grid">


<?php

$premium_found = false;

$seat_result->data_seek(0);


while ($seat = $seat_result->fetch_assoc()):

    if (
        strtolower(trim($seat['seat_type'])) !=
        'premium'
    ) {
        continue;
    }

    $premium_found = true;

    $seat_id = (int)$seat['seat_id'];

    $seat_number =
        htmlspecialchars($seat['seat_number']);

    $is_booked =
        in_array($seat_id, $booked_seats);

?>


<div
class="seat premium-seat <?php echo $is_booked ? 'taken' : ''; ?>"
data-seat-id="<?php echo $seat_id; ?>"
data-seat-number="<?php echo $seat_number; ?>"
data-seat-type="Premium"
data-price="<?php echo $premium_price; ?>"
>

<?php
echo $seat_number;
?>

</div>


<?php endwhile; ?>


<?php if (!$premium_found): ?>

<p class="no-seats">
No Premium seats available.
</p>

<?php endif; ?>

</div>



<!-- =====================================================
                      VIP SEATS
===================================================== -->

<h3 class="section-title">

VIP

<span class="seat-price">

Rs. <?php echo $vip_price; ?>

</span>

</h3>


<div class="seat-grid">


<?php

$vip_found = false;

$seat_result->data_seek(0);


while ($seat = $seat_result->fetch_assoc()):

    if (
        strtolower(trim($seat['seat_type'])) !=
        'vip'
    ) {
        continue;
    }

    $vip_found = true;

    $seat_id = (int)$seat['seat_id'];

    $seat_number =
        htmlspecialchars($seat['seat_number']);

    $is_booked =
        in_array($seat_id, $booked_seats);

?>


<div
class="seat vip-seat <?php echo $is_booked ? 'taken' : ''; ?>"
data-seat-id="<?php echo $seat_id; ?>"
data-seat-number="<?php echo $seat_number; ?>"
data-seat-type="VIP"
data-price="<?php echo $vip_price; ?>"
>

<?php
echo $seat_number;
?>

</div>


<?php endwhile; ?>


<?php if (!$vip_found): ?>

<p class="no-seats">
No VIP seats available.
</p>

<?php endif; ?>

</div>



<!-- =====================================================
                      PRICE LEGEND
===================================================== -->

<div class="price-legend">

<div>
Regular:
<strong>
Rs. <?php echo $regular_price; ?>
</strong>
</div>

<div>
Premium:
<strong>
Rs. <?php echo $premium_price; ?>
</strong>
</div>

<div>
VIP:
<strong>
Rs. <?php echo $vip_price; ?>
</strong>
</div>

</div>



<!-- =====================================================
                      SUMMARY
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
Rs.0
</h2>


<form
action="payment.php"
method="POST"
id="bookingForm"
>


<input
type="hidden"
name="movie_id"
value="<?php echo $movie_id; ?>"
>


<input
type="hidden"
name="showtime_id"
value="<?php echo $showtime_id; ?>"
>


<input
type="hidden"
name="hall_id"
value="<?php echo $hall_id; ?>"
>


<input
type="hidden"
name="seat_ids"
id="seat_ids"
>


<input
type="hidden"
name="seat_numbers"
id="seat_numbers"
>


<input
type="hidden"
name="total_price"
id="total_price"
>


<button
type="submit"
id="continueButton"
disabled
>

Continue

<i class="fa-solid fa-arrow-right"></i>

</button>


</form>

</div>


</div>


</div>

</div>

</section>



<!-- =====================================================
                       JAVASCRIPT
===================================================== -->

<script>

const seats =
document.querySelectorAll(
    ".seat:not(.taken)"
);

const selectedSeats =
document.getElementById(
    "selectedSeats"
);

const totalPrice =
document.getElementById(
    "totalPrice"
);

const seatIds =
document.getElementById(
    "seat_ids"
);

const seatNumbers =
document.getElementById(
    "seat_numbers"
);

const totalPriceInput =
document.getElementById(
    "total_price"
);

const continueButton =
document.getElementById(
    "continueButton"
);


let selected = [];


seats.forEach(function(seat) {

    seat.addEventListener(
        "click",
        function() {

            const seatId =
                this.dataset.seatId;

            const seatNumber =
                this.dataset.seatNumber;

            const seatType =
                this.dataset.seatType;

            const price =
                Number(this.dataset.price);


            const existing =
                selected.find(
                    function(item) {
                        return item.id === seatId;
                    }
                );


            if (existing) {

                selected =
                    selected.filter(
                        function(item) {
                            return item.id !== seatId;
                        }
                    );

                this.classList.remove(
                    "selected"
                );

            } else {

                selected.push({
                    id: seatId,
                    number: seatNumber,
                    type: seatType,
                    price: price
                });

                this.classList.add(
                    "selected"
                );

            }


            updateSummary();

        }
    );

});


function updateSummary() {

    if (selected.length === 0) {

        selectedSeats.innerText =
            "No seats selected";

        totalPrice.innerText =
            "Rs.0";

        seatIds.value =
            "";

        seatNumbers.value =
            "";

        totalPriceInput.value =
            "0";

        continueButton.disabled =
            true;

        return;

    }


    const numbers =
        selected.map(
            function(item) {
                return item.number;
            }
        );


    const ids =
        selected.map(
            function(item) {
                return item.id;
            }
        );


    const total =
        selected.reduce(
            function(sum, item) {
                return sum + item.price;
            },
            0
        );


    selectedSeats.innerText =
        numbers.join(", ");


    totalPrice.innerText =
        "Rs." + total;


    seatIds.value =
        ids.join(",");


    seatNumbers.value =
        numbers.join(",");


    totalPriceInput.value =
        total;


    continueButton.disabled =
        false;

}

</script>


</body>

</html>