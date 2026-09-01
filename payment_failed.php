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


/* =====================================================
   CHECK PAYMENT FAILURE DATA
===================================================== */

if (!isset($_SESSION["payment_failure"])) {
    header("Location: index.php");
    exit();
}


$failure =
    $_SESSION["payment_failure"];


$movie_id =
    (int)$failure["movie_id"];

$showtime_id =
    (int)$failure["showtime_id"];

$hall_id =
    (int)$failure["hall_id"];

$seat_ids =
    $failure["seat_ids"];

$expiry_time =
    $failure["expiry_time"];


/* =====================================================
   GET MOVIE / SHOWTIME / HALL
===================================================== */

$sql = "
    SELECT
        m.title,
        m.genre,
        m.duration,
        m.poster,
        m.rating,

        s.show_date,
        s.show_time,
        s.ticket_price,

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

$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "iii",
    $movie_id,
    $showtime_id,
    $hall_id
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows === 0) {
    die("Booking information not found.");
}


$details =
    $result->fetch_assoc();


/* =====================================================
   GET SEAT NUMBERS
===================================================== */

$placeholders = implode(
    ",",
    array_fill(
        0,
        count($seat_ids),
        "?"
    )
);

$types =
    "i" .
    str_repeat(
        "i",
        count($seat_ids)
    );

$params =
    array_merge(
        [$hall_id],
        $seat_ids
    );


$seat_sql = "
    SELECT seat_number
    FROM seat
    WHERE hall_id = ?
      AND seat_id IN ($placeholders)
    ORDER BY seat_id
";


$seat_stmt =
    $conn->prepare($seat_sql);

$seat_stmt->bind_param(
    $types,
    ...$params
);

$seat_stmt->execute();

$seat_result =
    $seat_stmt->get_result();


$seat_numbers = [];

while (
    $row =
    $seat_result->fetch_assoc()
) {

    $seat_numbers[] =
        htmlspecialchars(
            $row["seat_number"]
        );
}


$seat_display =
    implode(
        " • ",
        $seat_numbers
);


/* =====================================================
   FORMAT INFORMATION
===================================================== */

$movie_title =
    htmlspecialchars(
        $details["title"]
    );

$genre =
    htmlspecialchars(
        $details["genre"]
    );

$poster =
    htmlspecialchars(
        $details["poster"]
    );

$rating =
    htmlspecialchars(
        $details["rating"]
    );


$duration_minutes =
    (int)$details["duration"];

$hours =
    floor(
        $duration_minutes / 60
    );

$minutes =
    $duration_minutes % 60;


if (
    $hours > 0 &&
    $minutes > 0
) {

    $duration =
        $hours . "h " .
        $minutes . "m";

} elseif ($hours > 0) {

    $duration =
        $hours . "h";

} else {

    $duration =
        $minutes . "m";
}


$hall_name =
    htmlspecialchars(
        $details["hall_name"]
    );


$show_date =
    date(
        "d M Y",
        strtotime(
            $details["show_date"]
        )
    );


$show_time =
    date(
        "h:i A",
        strtotime(
            $details["show_time"]
        )
    );


$ticket_price =
    (float)$details["ticket_price"];


$ticket_count =
    count($seat_ids);


$total_price =
    $ticket_price *
    $ticket_count;


/* =====================================================
   FORMAT EXPIRY TIME
===================================================== */

$expiry_display =
    $expiry_time
    ? date(
        "h:i A",
        strtotime($expiry_time)
    )
    : "expired";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

>

<title>
Payment Failed | HiMovie
</title>

<link
    rel="stylesheet"
    href="navbar.css"
>

<link
    rel="stylesheet"
    href="confirmation.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
>

<style>

.payment-failed-icon {

    width: 70px;
    height: 70px;

    margin: 0 auto 20px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #ffe5e5;

    color: #d63031;

    font-size: 32px;
}

.failure-title {

    text-align: center;

    margin-bottom: 10px;
}

.failure-message {

    text-align: center;

    margin-bottom: 25px;
}

.reservation-warning {

    display: flex;

    gap: 12px;

    padding: 15px;

    margin-top: 20px;

    border-radius: 8px;

    background: #fff4d6;

    color: #6b5200;
}

.reservation-warning i {

    margin-top: 3px;
}

.retry-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 12px 20px;

    text-decoration: none;

    border-radius: 6px;
}

</style>

</head>

<body>

<!-- ================= NAVBAR ================= -->

<?php include "navbar.php"; ?>

<!-- ================= PAGE ================= -->

<section class="confirmation-page">

<div class="confirmation-container">

<!-- ================= LEFT ================= -->

<div class="movie-card">

<div class="poster">

<img
src="<?php echo $poster; ?>"
alt="<?php echo $movie_title; ?>"

>

<span class="badge">

PAYMENT FAILED

</span>

</div>

<div class="movie-content">

<h2>

<?php echo $movie_title; ?>

</h2>

<p class="genre">

<?php echo $genre; ?>

</p>

<div class="rating">

<span>

<i class="fa-solid fa-star"></i>

<?php echo $rating; ?>

</span>

<span>

<i class="fa-regular fa-clock"></i>

<?php echo $duration; ?>

</span>

</div>

<div class="movie-info">

<div class="info">

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

<div class="info">

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

<div class="info">

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

<div class="info">

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

</div>

<!-- ================= RIGHT ================= -->

<div class="ticket-card">

<div class="success">

<div class="payment-failed-icon">

<i class="fa-solid fa-xmark"></i>

</div>

<h2 class="failure-title">

Payment Failed

</h2>

<p class="failure-message">

Your payment was not completed.
No tickets have been booked.

</p>

</div>

<!-- ================= PAYMENT DETAILS ================= -->

<div class="ticket">

<div class="ticket-header">

<div>

<h3>

Payment Unsuccessful

</h3>

<p>

No ticket was created.

</p>

</div>

<div class="paid">

FAILED

</div>

</div>

<div class="ticket-details">

<div>

<span>Movie</span>

<strong>

<?php echo $movie_title; ?>

</strong>

</div>

<div>

<span>Cinema</span>

<strong>

<?php echo $hall_name; ?>

</strong>

</div>

<div>

<span>Date</span>

<strong>

<?php echo $show_date; ?>

</strong>

</div>

<div>

<span>Time</span>

<strong>

<?php echo $show_time; ?>

</strong>

</div>

<div>

<span>Seats</span>

<strong>

<?php echo $seat_display; ?>

</strong>

</div>

<div>

<span>Tickets</span>

<strong>

<?php echo $ticket_count; ?>

</strong>

</div>

<div class="total">

<span>Amount</span>

<strong>

Rs.<?php
echo number_format(
 $total_price,
 2
);
?>

</strong>

</div>

</div>

<!-- ================= RESERVATION WARNING ================= -->

<div class="reservation-warning">

<i class="fa-regular fa-clock"></i>

<div>

<strong>
Seats temporarily reserved
</strong>

<p>

Your selected seats are still reserved until

<?php echo $expiry_display; ?>.

You can return to the payment page and try again before the reservation expires.

</p>

</div>

</div>

</div>

<!-- ================= BUTTONS ================= -->

<div class="buttons">

<a
    href="index.php"
    class="home-btn"
>

<i class="fa-solid fa-house"></i>

Home

</a>


<form
    method="POST"
    action="payment.php"
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
    class="retry-button book-btn"
>

<i class="fa-solid fa-rotate-right"></i>

Try Payment Again

</button>

</form>

</div>

</div>

</div>

</section>

</body>

</html>

<?php

/* =====================================================
   CLEAR FAILURE SESSION
===================================================== */

unset(
    $_SESSION["payment_failed"],
    $_SESSION["payment_failure"]
);

?>
