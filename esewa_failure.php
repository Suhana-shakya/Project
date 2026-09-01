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
   CHECK ESEWA BOOKING SESSION
===================================================== */

if (!isset($_SESSION["esewa_booking"])) {
    die("Booking information not found.");
}

$booking =
    $_SESSION["esewa_booking"];


/* =====================================================
   GET BOOKING INFORMATION
===================================================== */

$movie_id =
    (int)$booking["movie_id"];

$showtime_id =
    (int)$booking["showtime_id"];

$hall_id =
    (int)$booking["hall_id"];

$seat_ids =
    $booking["seat_ids"];


/* =====================================================
   REMOVE ONLY EXPIRED RESERVATIONS
===================================================== */

$conn->query("
    DELETE FROM temporary_booking
    WHERE expires_at <= NOW()
");


/* =====================================================
   VERIFY SEATS ARE STILL RESERVED
===================================================== */

$placeholders = implode(
    ",",
    array_fill(0, count($seat_ids), "?")
);

$types =
    "ii" . str_repeat("i", count($seat_ids));

$params = array_merge(
    [
        $showtime_id,
        $customer_id
    ],
    $seat_ids
);

$sql = "
    SELECT seat_id, expires_at
    FROM temporary_booking
    WHERE showtime_id = ?
      AND customer_id = ?
      AND seat_id IN ($placeholders)
      AND expires_at > NOW()
";

$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result =
    $stmt->get_result();


$valid_seats = [];

$expiry_time = null;

while ($row = $result->fetch_assoc()) {

    $valid_seats[] =
        (int)$row["seat_id"];

    if (
        $expiry_time === null ||
        strtotime($row["expires_at"])
        < strtotime($expiry_time)
    ) {

        $expiry_time =
            $row["expires_at"];
    }
}
if (empty($valid_seats)) {
    unset($_SESSION["esewa_booking"]);

    die("Your seat reservation has expired. Please select your seats again.");
}

/* =====================================================
   SAVE FAILURE INFORMATION
===================================================== */

$_SESSION["payment_failed"] = true;

$_SESSION["payment_failure"] = [
    "movie_id" => $movie_id,
    "showtime_id" => $showtime_id,
    "hall_id" => $hall_id,
    "seat_ids" => $valid_seats,
    "expiry_time" => $expiry_time
];


/* =====================================================
   REDIRECT
===================================================== */

header("Location: payment_failed.php");
exit();

?>
