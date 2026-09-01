<?php

session_start();

include "db.php";


/* =====================================================
   CHECK LOGIN
===================================================== */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION["customer_id"];


/* =====================================================
   GET DATA FROM SEAT.PHP
===================================================== */

$movie_id = isset($_POST["movie_id"])
    ? (int)$_POST["movie_id"]
    : 0;

$showtime_id = isset($_POST["showtime_id"])
    ? (int)$_POST["showtime_id"]
    : 0;

$hall_id = isset($_POST["hall_id"])
    ? (int)$_POST["hall_id"]
    : 0;

$seat_ids_string = isset($_POST["seat_ids"])
    ? trim($_POST["seat_ids"])
    : "";


if (
    $movie_id <= 0 ||
    $showtime_id <= 0 ||
    $hall_id <= 0 ||
    $seat_ids_string === ""
) {
    die("Invalid booking information.");
}


/* =====================================================
   CONVERT SEAT IDS INTO ARRAY
===================================================== */

$seat_ids = array_filter(
    array_map(
        "intval",
        explode(",", $seat_ids_string)
    )
);

if (empty($seat_ids)) {
    die("No seats selected.");
}


/* =====================================================
   REMOVE EXPIRED TEMPORARY BOOKINGS
===================================================== */

$cleanup_sql = "
    DELETE FROM temporary_booking
    WHERE expires_at <= NOW()
";

$conn->query($cleanup_sql);


/* =====================================================
   CHECK THAT ALL SEATS BELONG TO CURRENT CUSTOMER
===================================================== */

$placeholders = implode(
    ",",
    array_fill(0, count($seat_ids), "?")
);

$types = str_repeat("i", count($seat_ids) + 2);

$params = array_merge(
    [$showtime_id, $customer_id],
    $seat_ids
);


/*
 * Build bind_param dynamically.
 */

$check_sql = "
    SELECT seat_id, expires_at
    FROM temporary_booking
    WHERE showtime_id = ?
      AND customer_id = ?
      AND seat_id IN ($placeholders)
      AND expires_at > NOW()
";

$stmt = $conn->prepare($check_sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   VERIFY ALL SELECTED SEATS
===================================================== */

$valid_seats = [];
$expiry_time = null;

while ($row = $result->fetch_assoc()) {

    $valid_seats[] = (int)$row["seat_id"];

    if (
        $expiry_time === null ||
        strtotime($row["expires_at"]) < strtotime($expiry_time)
    ) {
        $expiry_time = $row["expires_at"];
    }
}


if (count($valid_seats) !== count($seat_ids)) {

    die(
        "One or more selected seats are no longer reserved. Please return to seat selection."
    );
}


/* =====================================================
   GET MOVIE + SHOWTIME + HALL
===================================================== */

$details_sql = "
    SELECT
        m.movie_id,
        m.title,
        m.genre,
        m.duration,
        m.poster,
        m.rating,
        s.showtime_id,
        s.show_date,
        s.show_time,
        s.ticket_price,
        h.hall_id,
        h.hall_name
    FROM movie m
    INNER JOIN showtime s
        ON m.movie_id = s.movie_id
    INNER JOIN hall h
        ON s.hall_id = h.hall_id
    WHERE m.movie_id = ?
      AND s.showtime_id = ?
      AND h.hall_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($details_sql);

$stmt->bind_param(
    "iii",
    $movie_id,
    $showtime_id,
    $hall_id
);

$stmt->execute();

$details_result = $stmt->get_result();

if ($details_result->num_rows === 0) {
    die("Movie or showtime not found.");
}

$details = $details_result->fetch_assoc();


/* =====================================================
   GET SEAT NUMBERS
===================================================== */

$seat_placeholders = implode(
    ",",
    array_fill(0, count($seat_ids), "?")
);

$seat_types = str_repeat("i", count($seat_ids));

$seat_sql = "
    SELECT seat_id, seat_number, seat_type
    FROM seat
    WHERE hall_id = ?
      AND seat_id IN ($seat_placeholders)
    ORDER BY seat_id
";

$seat_stmt = $conn->prepare($seat_sql);

$seat_params = array_merge(
    [$hall_id],
    $seat_ids
);

$seat_stmt->bind_param(
    "i" . $seat_types,
    ...$seat_params
);

$seat_stmt->execute();

$seat_result = $seat_stmt->get_result();

$seats = [];

while ($row = $seat_result->fetch_assoc()) {
    $seats[] = $row;
}


/* =====================================================
   CALCULATE TOTAL FROM DATABASE
===================================================== */

$ticket_price = (float)$details["ticket_price"];

$ticket_count = count($seats);

$total_price =
    $ticket_price * $ticket_count;


/* =====================================================
   FORMAT VALUES
===================================================== */

$movie_title =
    htmlspecialchars($details["title"]);

$genre =
    htmlspecialchars($details["genre"]);

$duration_minutes = (int)$details["duration"];

$hours = floor($duration_minutes / 60);
$minutes = $duration_minutes % 60;

if ($hours > 0 && $minutes > 0) {
    $duration = $hours . "h " . $minutes . "m";
} elseif ($hours > 0) {
    $duration = $hours . "h";
} else {
    $duration = $minutes . "m";
}

$hall_name =
    htmlspecialchars($details["hall_name"]);

$show_date =
    date(
        "d M Y",
        strtotime($details["show_date"])
    );

$show_time =
    htmlspecialchars($details["show_time"]);


$seat_numbers = [];

foreach ($seats as $seat) {

    $seat_numbers[] =
        htmlspecialchars($seat["seat_number"]);
}

$seat_display =
    implode(" • ", $seat_numbers);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Payment | HiMovie</title>

<link rel="stylesheet"
      href="navbar.css">

<link rel="stylesheet"
      href="payment.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>


<body>

<?php include "navbar.php"; ?>


<section class="payment-page">

<div class="payment-container">


<!-- =====================================================
     LEFT SIDE
===================================================== -->

<aside class="movie-card">

<div class="poster">

<?php if (!empty($details["poster"])): ?>

<img 
    src="uploads/posters/<?php echo htmlspecialchars($details["poster"]); ?>" 
    alt="<?php echo $movie_title; ?>"
>

<?php endif; ?>

<span class="badge">
READY TO PAY
</span>

</div>


<div class="movie-details">

<h2>
<?php echo $movie_title; ?>
</h2>


<p class="genre">
<?php echo $genre; ?>
</p>


<div class="movie-stats">

<div>

<i class="fa-solid fa-star"></i>

<span>
    <?php echo htmlspecialchars($details["rating"]); ?>
</span>

</div>


<div>

<i class="fa-regular fa-clock"></i>

<span>
<?php echo $duration; ?>
</span>

</div>

</div>


<div class="booking-details">


<div class="info-box">

<div class="icon">

<i class="fa-solid fa-location-dot"></i>

</div>

<div>

<h4>Cinema</h4>

<p>
<?php echo $hall_name; ?>
</p>

</div>

</div>


<div class="info-box">

<div class="icon">

<i class="fa-solid fa-calendar"></i>

</div>

<div>

<h4>Date</h4>

<p>
<?php echo $show_date; ?>
</p>

</div>

</div>


<div class="info-box">

<div class="icon">

<i class="fa-solid fa-clock"></i>

</div>

<div>

<h4>Time</h4>

<p>
<?php echo $show_time; ?>
</p>

</div>

</div>


<div class="info-box">

<div class="icon">

<i class="fa-solid fa-couch"></i>

</div>

<div>

<h4>Seats</h4>

<p>
<?php echo $seat_display; ?>
</p>

</div>

</div>


</div>

</div>

</aside>



<!-- =====================================================
     RIGHT SIDE
===================================================== -->

<div class="payment-card">


<div class="payment-header">

<div>

<h2>Payment Details</h2>

<p>
Complete your booking securely
</p>

</div>


<div class="price-card">

<h3>Total</h3>

<h1>
Rs.<?php echo number_format($total_price, 2); ?>
</h1>

</div>

</div>



<!-- =====================================================
     BOOKING SUMMARY
===================================================== -->

<div class="summary-card">

<h3>Booking Summary</h3>


<div class="summary-row">

<span>Movie</span>

<strong>
<?php echo $movie_title; ?>
</strong>

</div>


<div class="summary-row">

<span>Cinema</span>

<strong>
<?php echo $hall_name; ?>
</strong>

</div>


<div class="summary-row">

<span>Date</span>

<strong>
<?php echo $show_date; ?>
</strong>

</div>


<div class="summary-row">

<span>Time</span>

<strong>
<?php echo $show_time; ?>
</strong>

</div>


<div class="summary-row">

<span>Seats</span>

<strong>
<?php echo $seat_display; ?>
</strong>

</div>


<div class="summary-row">

<span>Tickets</span>

<strong>
<?php echo $ticket_count; ?>
</strong>

</div>


<div class="summary-row">

<span>Price</span>

<strong>
Rs.<?php echo number_format($ticket_price, 2); ?>
 × <?php echo $ticket_count; ?>
</strong>

</div>


<div class="summary-total">

<span>Total</span>

<strong>
Rs.<?php echo number_format($total_price, 2); ?>
</strong>

</div>

</div>



<!-- =====================================================
     PAYMENT METHODS
===================================================== -->

<div class="payment-methods">

<h3>Select Payment Method</h3>


<div class="method-grid">


<!-- eSEWA -->

<label class="method-card">

<input
    type="radio"
    name="payment"
    value="esewa"
    checked
>

<div class="method-icon">

<i class="fa-solid fa-wallet"></i>

</div>

<div>

<h4>eSewa</h4>

<p>
Fast Digital Wallet
</p>

</div>

</label>


<!-- KHALTI -->

<label class="method-card">

<input
    type="radio"
    name="payment"
    value="khalti"
>

<div class="method-icon">

<i class="fa-solid fa-mobile-screen-button"></i>

</div>

<div>

<h4>Khalti</h4>

<p>
Coming Soon
</p>

</div>

</label>


<!-- IME PAY -->

<label class="method-card">

<input
    type="radio"
    name="payment"
    value="imepay"
>

<div class="method-icon">

<i class="fa-solid fa-money-bill-wave"></i>

</div>

<div>

<h4>IME Pay</h4>

<p>
Coming Soon
</p>

</div>

</label>


<!-- CARD -->

<label class="method-card">

<input
    type="radio"
    name="payment"
    value="card"
>

<div class="method-icon">

<i class="fa-regular fa-credit-card"></i>

</div>

<div>

<h4>Debit / Credit Card</h4>

<p>
Coming Soon
</p>

</div>

</label>


</div>

</div>



<!-- =====================================================
     RESERVATION TIMER
===================================================== -->

<div class="secure-box">

<i class="fa-regular fa-clock"></i>

<span>

Your seats are reserved until

<strong>
<?php echo date("h:i A", strtotime($expiry_time)); ?>
</strong>

</span>

</div>



<!-- =====================================================
     ESEWA FORM
===================================================== -->

<form
    method="POST"
    action="esewa_payment.php"
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
    value="<?php echo implode(",", $seat_ids); ?>"
>

<button
    type="submit"
    class="pay-button"
>

Pay Rs.<?php echo number_format($total_price, 2); ?>

<i class="fa-solid fa-arrow-right"></i>

</button>

</form>


</div>

</div>

</section>

</body>

</html>