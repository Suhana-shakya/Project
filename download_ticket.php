<?php

session_start();

include "db.php";

require_once "dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;


/* =====================================================
   CHECK LOGIN
===================================================== */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION["customer_id"];


/* =====================================================
   CHECK CONFIRMATION DATA
===================================================== */

if (!isset($_SESSION["booking_confirmation"])) {
    die("Booking confirmation not found.");
}

$confirmation = $_SESSION["booking_confirmation"];

$ticket_ids = $confirmation["ticket_ids"] ?? [];

if (empty($ticket_ids)) {
    die("Invalid booking confirmation.");
}


/* =====================================================
   GET TICKET IDS
===================================================== */

$placeholders = implode(
    ",",
    array_fill(0, count($ticket_ids), "?")
);

$types = str_repeat("i", count($ticket_ids));

$sql = "
    SELECT
        t.ticket_id,
        t.customer_id,

        m.title,

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


$params = array_merge(
    $ticket_ids,
    [$customer_id]
);

$types .= "i";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result = $stmt->get_result();


$tickets = [];

while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}


if (empty($tickets)) {
    die("Booking information could not be found.");
}


/* =====================================================
   TICKET INFORMATION
===================================================== */

$first_ticket = $tickets[0];

$movie_title = $first_ticket["title"];

$hall_name = $first_ticket["hall_name"];

$show_date = date(
    "d M Y",
    strtotime($first_ticket["show_date"])
);

$show_time = date(
    "h:i A",
    strtotime($first_ticket["show_time"])
);


/* =====================================================
   SEATS
===================================================== */

$seat_numbers = [];

foreach ($tickets as $ticket) {
    $seat_numbers[] = $ticket["seat_number"];
}

$seat_display = implode(
    ", ",
    $seat_numbers
);


/* =====================================================
   NUMBER OF TICKETS
===================================================== */

$ticket_count = count($tickets);


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
   TOTAL PAID
===================================================== */
$total_paid =
    (float)($confirmation["total_amount"] ?? 0);


/* =====================================================
   CREATE DOMPDF
===================================================== */

$options = new Options();

$options->set(
    "defaultFont",
    "DejaVu Sans"
);

$dompdf = new Dompdf($options);


/* =====================================================
   PDF CONTENT
===================================================== */

$html = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    background: #ffffff;
    margin: 0;
    padding: 30px;
    color: #222;
}

.ticket {
    width: 500px;
    margin: auto;
    border: 2px solid #222;
    padding: 25px;
}

.header {
    text-align: center;
    border-bottom: 2px solid #222;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.logo {
    font-size: 28px;
    font-weight: bold;
}

.subtitle {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

.details {
    width: 100%;
    border-collapse: collapse;
}

.details td {
    padding: 10px 5px;
    border-bottom: 1px solid #ddd;
    font-size: 13px;
}

.label {
    font-weight: bold;
    width: 35%;
}

.value {
    width: 65%;
}

.total td {
    font-size: 16px;
    font-weight: bold;
}

.booking {
    margin-top: 20px;
    text-align: center;
}

.booking-id {
    font-size: 14px;
    font-weight: bold;
}

.status {
    margin-top: 18px;
    text-align: center;
    font-weight: bold;
    font-size: 14px;
}

.footer {
    margin-top: 25px;
    text-align: center;
    font-size: 10px;
    color: #777;
}

</style>

</head>

<body>

<div class="ticket">

    <div class="header">

        <div class="logo">
            HiMovie
        </div>

        <div class="subtitle">
            MOVIE TICKET
        </div>

    </div>


    <table class="details">

        <tr>
            <td class="label">
                Movie
            </td>

            <td class="value">
                ' . htmlspecialchars($movie_title) . '
            </td>
        </tr>


        <tr>
            <td class="label">
                Cinema Hall
            </td>

            <td class="value">
                ' . htmlspecialchars($hall_name) . '
            </td>
        </tr>


        <tr>
            <td class="label">
                Date
            </td>

            <td class="value">
                ' . htmlspecialchars($show_date) . '
            </td>
        </tr>


        <tr>
            <td class="label">
                Time
            </td>

            <td class="value">
                ' . htmlspecialchars($show_time) . '
            </td>
        </tr>


        <tr>
            <td class="label">
                Seats
            </td>

            <td class="value">
                ' . htmlspecialchars($seat_display) . '
            </td>
        </tr>


        <tr>
            <td class="label">
                Tickets
            </td>

            <td class="value">
                ' . $ticket_count . '
            </td>
        </tr>


        <tr>
            <td class="label">
                Booking ID
            </td>

            <td class="value">
                ' . htmlspecialchars($booking_id) . '
            </td>
        </tr>


        <tr class="total">

            <td>
                Total Paid
            </td>

            <td>
                Rs. ' . number_format($total_paid, 2) . '
            </td>

        </tr>

    </table>


    <div class="status">
        ✓ Payment Confirmed
    </div>


    <div class="footer">
        Thank you for booking with HiMovie.
    </div>

</div>

</body>

</html>

';


/* =====================================================
   GENERATE PDF
===================================================== */

$dompdf->loadHtml($html);

$dompdf->setPaper(
    "A4",
    "portrait"
);

$dompdf->render();


/* =====================================================
   DOWNLOAD PDF
===================================================== */

$dompdf->stream(
    "HiMovie_Ticket_" . $booking_id . ".pdf",
    [
        "Attachment" => true
    ]
);

exit;

?>