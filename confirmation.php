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

$customer_id =
    (int)$_SESSION["customer_id"];


/* =====================================================
   CHECK CONFIRMATION DATA
===================================================== */

if (!isset($_SESSION["booking_confirmation"])) {
    die("Booking confirmation not found.");
}

$confirmation =
    $_SESSION["booking_confirmation"];


$ticket_ids =
    $confirmation["ticket_ids"] ?? [];

$transaction_uuid =
    $confirmation["transaction_uuid"] ?? "";

$total_paid =
    (float)($confirmation["total_amount"] ?? 0);


if (empty($ticket_ids)) {
    die("Invalid booking confirmation.");
}


/* =====================================================
   GET TICKET IDS
===================================================== */

$placeholders = implode(
    ",",
    array_fill(
        0,
        count($ticket_ids),
        "?"
    )
);

$types =
    str_repeat(
        "i",
        count($ticket_ids)
    );


$sql = "
    SELECT
        t.ticket_id,
        t.booking_date,
        t.status,
        t.movie_id,
        t.seat_id,
        t.showtime_id,
        t.customer_id,
        t.payment_status,

        m.title,
        m.genre,
        m.duration,
        m.poster,
        m.rating,

        s.seat_number,

        st.show_date,
        st.show_time,
        st.ticket_price,

        h.hall_name

    FROM ticket t

    INNER JOIN movie m
        ON t.movie_id = m.movie_id

    INNER JOIN seat s
        ON t.seat_id = s.seat_id

    INNER JOIN showtime st
        ON t.showtime_id = st.showtime_id

    INNER JOIN hall h
        ON st.hall_id = h.hall_id

    WHERE t.ticket_id IN ($placeholders)
      AND t.customer_id = ?

    ORDER BY t.ticket_id
";


$params =
    array_merge(
        $ticket_ids,
        [$customer_id]
    );


$types .= "i";


$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result =
    $stmt->get_result();


$tickets = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $tickets[] = $row;
}


if (empty($tickets)) {
    die("Booking information could not be found.");
}


/* =====================================================
   GET FIRST TICKET
===================================================== */

$first_ticket =
    $tickets[0];


/* =====================================================
   MOVIE INFORMATION
===================================================== */

$movie_title =
    htmlspecialchars(
        $first_ticket["title"]
    );

$genre =
    htmlspecialchars(
        $first_ticket["genre"]
    );

$poster =
    htmlspecialchars(
        $first_ticket["poster"]
    );

$rating =
    htmlspecialchars(
        $first_ticket["rating"]
    );


/* =====================================================
   DURATION
===================================================== */

$duration_minutes =
    (int)$first_ticket["duration"];

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


/* =====================================================
   SHOWTIME INFORMATION
===================================================== */

$hall_name =
    htmlspecialchars(
        $first_ticket["hall_name"]
    );

$show_date =
    date(
        "d M Y",
        strtotime(
            $first_ticket["show_date"]
        )
    );

$show_time =
    date(
        "h:i A",
        strtotime(
            $first_ticket["show_time"]
        )
    );


/* =====================================================
   SEAT NUMBERS
===================================================== */

$seat_numbers = [];

foreach ($tickets as $ticket) {

    $seat_numbers[] =
        htmlspecialchars(
            $ticket["seat_number"]
        );
}

$seat_display =
    implode(
        " • ",
        $seat_numbers
    );


/* =====================================================
   NUMBER OF TICKETS
===================================================== */

$ticket_count =
    count($tickets);


/* =====================================================
   BOOKING ID
===================================================== */

$booking_id =
    "HM" .
    str_pad(
        $first_ticket["ticket_id"],
        6,
        "0",
        STR_PAD_LEFT
    );


/* =====================================================
   TOTAL
===================================================== */

$total_paid =
    $first_ticket["ticket_price"] *
    $ticket_count;

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
Booking Confirmation | HiMovie
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

<?php if (!empty($details["poster"])): ?>

<img 
    src="uploads/posters/<?php echo htmlspecialchars($details["poster"]); ?>" 
    alt="<?php echo $movie_title; ?>"
>

<?php endif; ?>

<span class="badge">

READY TO WATCH

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

<div class="success-circle">

<i class="fa-solid fa-check"></i>

</div>

<h2>

Booking Confirmed!

</h2>

<p>

Your tickets are ready.
Enjoy your movie and have a wonderful time!

</p>

</div>

<!-- ================= TICKET ================= -->

<div class="ticket">

<div class="ticket-header">

<div>

<h3>

Digital Movie Ticket

</h3>

<p>

Booking ID :

<?php echo $booking_id; ?>

</p>

</div>

<div class="paid">

PAID

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

<span>Total Paid</span>

<strong>

Rs.<?php
echo number_format(
 $total_paid,
 2
);
?>

</strong>

</div>

<div>

<span>eSewa Transaction</span>

<strong>

<?php echo htmlspecialchars(
    $transaction_uuid
); ?>

</strong>

</div>

</div>

<div class="ticket-note">

<i class="fa-solid fa-circle-info"></i>

<div>

<h4>

Important Information

</h4>

<p>

Please arrive at least 15 minutes before the show begins.
Carry a valid ID and present your Booking ID at the ticket counter if requested.

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

<button 
    type="button" 
    class="download-btn" 
    onclick="window.print()"
    style="
        background: #111827;
        color: #ffffff;
        border: none;
        padding: 12px 22px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: 0.3s ease;
    "
>
    <i class="fa-solid fa-download"></i>
    Download Ticket
</button>

<a
href="movies.php"
class="book-btn"

>

<i class="fa-solid fa-film"></i>

Book Again

</a>

</div>

</div>

</div>

</section>

</body>

</html>

<?php

/* =====================================================
   CLEAR CONFIRMATION SESSION
===================================================== */

unset(
    $_SESSION["booking_confirmation"]
);

?>
