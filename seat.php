<?php
session_start();
include "db.php";
/* ================= CHECK LOGIN ================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION["customer_id"];
/* =====================================================
   TEMPORARY SEAT RESERVATION AJAX
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "reserve_seat"
) {

    header("Content-Type: application/json");

    $seat_id = isset($_POST["seat_id"])
        ? (int)$_POST["seat_id"]
        : 0;

    $showtime_id_post = isset($_POST["showtime_id"])
        ? (int)$_POST["showtime_id"]
        : 0;

    if ($seat_id <= 0 || $showtime_id_post <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid seat information."
        ]);
        exit();
    }

    /* Remove expired reservations */

    $cleanup_sql = "
        DELETE FROM temporary_booking
        WHERE expires_at <= NOW()
    ";

    $conn->query($cleanup_sql);


    /* Check if seat is permanently booked */

    $check_ticket = "
        SELECT ticket_id
        FROM ticket
        WHERE seat_id = ?
          AND showtime_id = ?
          AND status = 'Confirmed'
        LIMIT 1
    ";

    $stmt = $conn->prepare($check_ticket);
    $stmt->bind_param(
        "ii",
        $seat_id,
        $showtime_id_post
    );
    $stmt->execute();

    $ticket_result = $stmt->get_result();

    if ($ticket_result->num_rows > 0) {

        echo json_encode([
            "success" => false,
            "message" => "This seat has already been booked."
        ]);

        exit();
    }


    /* Check temporary reservation */

    $check_temp = "
        SELECT temp_booking_id, customer_id
        FROM temporary_booking
        WHERE seat_id = ?
          AND showtime_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($check_temp);
    $stmt->bind_param(
        "ii",
        $seat_id,
        $showtime_id_post
    );
    $stmt->execute();

    $temp_result = $stmt->get_result();


    if ($temp_result->num_rows > 0) {

        $temp = $temp_result->fetch_assoc();

        /* Already reserved by this customer */

        if ((int)$temp["customer_id"] === $customer_id) {

            echo json_encode([
                "success" => true,
                "message" => "Seat is already reserved by you."
            ]);

            exit();

        } else {

            echo json_encode([
                "success" => false,
                "message" => "This seat is temporarily reserved by another customer."
            ]);

            exit();
        }
    }


    /* Reserve for 10 minutes */

    /* =====================================================
   GET EXISTING RESERVATION EXPIRY
===================================================== */

$existing_expiry = null;

$expiry_sql = "
    SELECT MIN(expires_at) AS expires_at
    FROM temporary_booking
    WHERE customer_id = ?
      AND showtime_id = ?
      AND expires_at > NOW()
";

$stmt = $conn->prepare($expiry_sql);

$stmt->bind_param(
    "ii",
    $customer_id,
    $showtime_id_post
);

$stmt->execute();

$expiry_result = $stmt->get_result();

if ($expiry_row = $expiry_result->fetch_assoc()) {
    $existing_expiry = $expiry_row["expires_at"];
}


/* =====================================================
   INSERT TEMPORARY BOOKING
===================================================== */

if ($existing_expiry !== null) {

    /*
     * Customer already has a reservation.
     * Give the new seat the SAME expiry time.
     */

    $insert_temp = "
        INSERT INTO temporary_booking
        (
            customer_id,
            seat_id,
            showtime_id,
            reserved_at,
            expires_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW(),
            ?
        )
    ";

    $stmt = $conn->prepare($insert_temp);

    $stmt->bind_param(
        "iiis",
        $customer_id,
        $seat_id,
        $showtime_id_post,
        $existing_expiry
    );

} else {

    /*
     * This is the customer's first seat.
     * Start a new 10-minute reservation.
     */

    $insert_temp = "
        INSERT INTO temporary_booking
        (
            customer_id,
            seat_id,
            showtime_id,
            reserved_at,
            expires_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW(),
            DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        )
    ";

    $stmt = $conn->prepare($insert_temp);

    $stmt->bind_param(
        "iii",
        $customer_id,
        $seat_id,
        $showtime_id_post
    );
}


/* =====================================================
   EXECUTE
===================================================== */

if ($stmt->execute()) {

    /*
     * Return the actual expiry time to JavaScript.
     */

    if ($existing_expiry !== null) {

        $expiry_for_client = $existing_expiry;

    } else {

        $expiry_for_client =
            date(
                "Y-m-d H:i:s",
                time() + (10 * 60)
            );
    }

    echo json_encode([
        "success" => true,
        "message" => "Seat reserved for 10 minutes.",
        "expires_at" => $expiry_for_client
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to reserve seat."
    ]);
}
exit();
}



/* =====================================================
   RELEASE TEMPORARY SEAT
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "release_seat"
) {

    header("Content-Type: application/json");

    $seat_id = isset($_POST["seat_id"])
        ? (int)$_POST["seat_id"]
        : 0;

    $showtime_id_post = isset($_POST["showtime_id"])
        ? (int)$_POST["showtime_id"]
        : 0;

    if ($seat_id <= 0 || $showtime_id_post <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid seat information."
        ]);

        exit();
    }


    /* Delete only this customer's reservation */

    $release_sql = "
        DELETE FROM temporary_booking
        WHERE seat_id = ?
          AND showtime_id = ?
          AND customer_id = ?
    ";

    $stmt = $conn->prepare($release_sql);

    $stmt->bind_param(
        "iii",
        $seat_id,
        $showtime_id_post,
        $customer_id
    );

    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Seat released."
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Unable to release seat."
        ]);
    }

    exit();
}
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
   REMOVE EXPIRED TEMPORARY BOOKINGS
===================================================== */

$cleanup_sql = "
    DELETE FROM temporary_booking
    WHERE expires_at <= NOW()
";

$conn->query($cleanup_sql);

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
/********************************************************
   GET TEMPORARILY RESERVED SEATS
********************************************************/

$temp_booked_seats = [];
$my_temp_seats = [];
$my_expiry_time = null;
echo "<!-- MY EXPIRY START: ";
var_dump($my_expiry_time);
echo " -->";

$temp_sql = "
    SELECT seat_id, customer_id, expires_at
    FROM temporary_booking
    WHERE showtime_id = ?
      AND expires_at > NOW()
";

$stmt = $conn->prepare($temp_sql);
$stmt->bind_param("i", $showtime_id);
$stmt->execute();

$temp_result = $stmt->get_result();

while ($row = $temp_result->fetch_assoc()) {

    $temp_seat_id = (int)$row['seat_id'];
    $temp_customer_id = (int)$row['customer_id'];

    $temp_booked_seats[] = $temp_seat_id;

    if ($temp_customer_id === $customer_id) {

        $my_temp_seats[] = $temp_seat_id;

        /*
         * Get the earliest expiry time belonging
         * to this customer's current reservation.
         */
        if (
            $my_expiry_time === null ||
            strtotime($row['expires_at']) < strtotime($my_expiry_time)
        ) {
            $my_expiry_time = $row['expires_at'];
        }
    }
}
echo "<!-- DEBUG EXPIRY: ";
var_dump($my_expiry_time);
echo " -->";

/* =====================================================
   PRICE SETTINGS
=====================================================

   Before 12 PM:
   Regular  = 350
   Premium  = 400
   VIP      = 450

   12 PM and later:
   Regular  = 450
   Premium  = 500
   VIP      = 550
===================================================== */

$show_time = strtotime($show['show_time']);

$hour = (int)date("H", $show_time);

if ($hour < 12) {

    $regular_price = 350;
    $premium_price = 400;
    $vip_price = 450;

} else {

    $regular_price = 450;
    $premium_price = 500;
    $vip_price = 550;
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
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet"
href="seat.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>


<body>

<?php include "navbar.php"; ?>

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

<img src="uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
     alt="<?php echo htmlspecialchars($movie['title']); ?>">

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

<div class="available"></div>

Available

</div>


<div>

<div class="selected-box"></div>

Selected

</div>


<div>

<div class="taken-box"></div>

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

    $is_temp_booked =
        in_array($seat_id, $temp_booked_seats);

    $is_my_temp =
        in_array($seat_id, $my_temp_seats);

?>


<div
class="seat <?php
    echo $is_booked ? 'taken' : '';
    echo ($is_temp_booked && !$is_my_temp) ? ' temp-taken' : '';
    echo $is_my_temp ? ' selected' : '';
?>"
data-seat-id="<?php echo $seat_id; ?>"
data-seat-number="<?php echo $seat_number; ?>"
data-seat-type="Regular"
data-price="<?php echo $regular_price; ?>"
data-temp-booked="<?php echo $is_temp_booked ? '1' : '0'; ?>"
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

    $is_temp_booked =
        in_array($seat_id, $temp_booked_seats);

    $is_my_temp =
        in_array($seat_id, $my_temp_seats);

?>


<div
class="seat premium-seat <?php
    echo $is_booked ? 'taken' : '';
    echo ($is_temp_booked && !$is_my_temp) ? ' temp-taken' : '';
    echo $is_my_temp ? ' selected' : '';
?>"
data-seat-id="<?php echo $seat_id; ?>"
data-seat-number="<?php echo $seat_number; ?>"
data-seat-type="Premium"
data-price="<?php echo $premium_price; ?>"
data-temp-booked="<?php echo $is_temp_booked ? '1' : '0'; ?>"
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

    $is_temp_booked =
        in_array($seat_id, $temp_booked_seats);

    $is_my_temp =
        in_array($seat_id, $my_temp_seats);

?>


<div
class="seat vip-seat <?php
    echo $is_booked ? 'taken' : '';
    echo ($is_temp_booked && !$is_my_temp) ? ' temp-taken' : '';
    echo $is_my_temp ? ' selected' : '';
?>"
data-seat-id="<?php echo $seat_id; ?>"
data-seat-number="<?php echo $seat_number; ?>"
data-seat-type="VIP"
data-price="<?php echo $vip_price; ?>"
data-temp-booked="<?php echo $is_temp_booked ? '1' : '0'; ?>"
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
<div id="reservationTimer" class="reservation-timer" style="display:none;">
    <i class="fa-regular fa-clock"></i>
    Seats reserved for <strong id="timerText"></strong>
</div>

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


<button type="submit" id="continueBtn" disabled>
    Continue
    <i class="fa-solid fa-arrow-right"></i>
</button>


</form>

</div>


</div>


</div>

</div>

</section>

<script>

const seats = document.querySelectorAll(
    ".seat:not(.taken):not(.temp-taken)"
);

const selectedSeats =
    document.getElementById("selectedSeats");

const totalPrice =
    document.getElementById("totalPrice");

const seatIds =
    document.getElementById("seat_ids");

const seatNumbers =
    document.getElementById("seat_numbers");

const totalPriceInput =
    document.getElementById("total_price");

const continueButton =
    document.getElementById("continueBtn");

const reservationTimer =
    document.getElementById("reservationTimer");

const timerText =
    document.getElementById("timerText");


/* =====================================================
   DATABASE EXPIRY TIME
===================================================== */

const databaseExpiryTime =
    <?php
    echo $my_expiry_time
        ? json_encode($my_expiry_time)
        : "null";
    ?>;


/* =====================================================
   TIMER VARIABLES
===================================================== */

let reservationEndTime = null;
let countdownInterval = null;


/* =====================================================
   SELECTED SEATS
===================================================== */

let selected = [];


/* =====================================================
   RESTORE EXISTING USER RESERVATIONS
===================================================== */

document.querySelectorAll(".seat.selected").forEach(function(seat) {

    selected.push({
        id: seat.dataset.seatId,
        number: seat.dataset.seatNumber,
        type: seat.dataset.seatType,
        price: Number(seat.dataset.price)
    });

});


/* =====================================================
   INITIAL PAGE STATE
===================================================== */

updateSummary();


/*
 * Only show timer if the customer REALLY has
 * an existing temporary reservation.
 */

if (
    databaseExpiryTime !== null &&
    selected.length > 0
) {

    reservationEndTime =
        new Date(
            databaseExpiryTime.replace(" ", "T")
        ).getTime();

    startReservationTimer();
}


/* =====================================================
   SEAT CLICK
===================================================== */

seats.forEach(function(seat) {

    seat.addEventListener("click", function() {

        const seatElement = this;

        const seatId =
            seatElement.dataset.seatId;

        const seatNumber =
            seatElement.dataset.seatNumber;

        const seatType =
            seatElement.dataset.seatType;

        const price =
            Number(seatElement.dataset.price);


        /* =================================================
           CHECK IF THIS SEAT IS ALREADY SELECTED
        ================================================= */

        const existing =
            selected.find(function(item) {

                return item.id === seatId;

            });


        /* =================================================
           DESELECT
        ================================================= */

        if (existing) {

            const formData = new FormData();

            formData.append(
                "action",
                "release_seat"
            );

            formData.append(
                "seat_id",
                seatId
            );

            formData.append(
                "showtime_id",
                "<?php echo $showtime_id; ?>"
            );


            fetch("seat.php", {
                method: "POST",
                body: formData
            })

            .then(response => response.json())

            .then(data => {

                if (!data.success) {

                    alert(data.message);
                    return;

                }


                selected =
                    selected.filter(function(item) {

                        return item.id !== seatId;

                    });


                seatElement.classList.remove(
                    "selected"
                );


                /*
                 * If there are no seats left,
                 * stop the timer.
                 */

                if (selected.length === 0) {

                    reservationEndTime = null;

                    if (countdownInterval !== null) {

                        clearInterval(
                            countdownInterval
                        );

                        countdownInterval = null;
                    }

                    reservationTimer.style.display =
                        "none";
                }


                updateSummary();

            })

            .catch(error => {

                console.error(
                    "Release error:",
                    error
                );

                alert(
                    "Unable to release the seat."
                );

            });

            return;
        }


        /* =================================================
           RESERVE NEW SEAT
        ================================================= */

        const formData = new FormData();

        formData.append(
            "action",
            "reserve_seat"
        );

        formData.append(
            "seat_id",
            seatId
        );

        formData.append(
            "showtime_id",
            "<?php echo $showtime_id; ?>"
        );


        fetch("seat.php", {
            method: "POST",
            body: formData
        })

        .then(response => response.json())

        .then(data => {

            console.log("Reservation response:", data);


            if (!data.success) {

                alert(data.message);
                return;

            }

            console.log("AJAX RESPONSE:", data);
            /* =================================================
               SAVE DATABASE EXPIRY TIME
            ================================================= */

            if (data.expires_at) {
                
                reservationEndTime =
                    new Date(
                        data.expires_at.replace(" ", "T")
                    ).getTime();
                    

            }


            /* =================================================
               ADD SELECTED SEAT
            ================================================= */

            selected.push({

                id: seatId,
                number: seatNumber,
                type: seatType,
                price: price

            });


            seatElement.classList.add(
                "selected"
            );


            /* =================================================
               UPDATE SUMMARY
            ================================================= */

            updateSummary();


            /* =================================================
               START TIMER
            ================================================= */

            if (
                reservationEndTime !== null &&
                !isNaN(reservationEndTime)
            ) {

                startReservationTimer();

            }

        })

        .catch(error => {

            console.error(
                "Reservation error:",
                error
            );

            alert(
                "Unable to reserve this seat. Please try again."
            );

        });

    });

});


/* =====================================================
   UPDATE BOOKING SUMMARY
===================================================== */

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
        selected.map(function(item) {

            return item.number;

        });


    const ids =
        selected.map(function(item) {

            return item.id;

        });


    const total =
        selected.reduce(function(sum, item) {

            return sum + item.price;

        }, 0);


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


/* =====================================================
   START TIMER
===================================================== */

function startReservationTimer() {

    if (
        reservationEndTime === null ||
        isNaN(reservationEndTime)
    ) {

        return;

    }


    reservationTimer.style.display =
        "block";


    if (countdownInterval !== null) {

        clearInterval(
            countdownInterval
        );

    }


    updateReservationTimer();


    countdownInterval =
        setInterval(
            updateReservationTimer,
            1000
        );

}


/* =====================================================
   UPDATE TIMER
===================================================== */

function updateReservationTimer() {

    if (
        reservationEndTime === null ||
        isNaN(reservationEndTime)
    ) {

        return;

    }


    const remaining =
        reservationEndTime - Date.now();


    /* =================================================
       ACTUALLY EXPIRED
    ================================================= */

    if (remaining <= 0) {

        clearInterval(
            countdownInterval
        );

        countdownInterval = null;

        reservationTimer.innerHTML =
            '<i class="fa-solid fa-circle-exclamation"></i> ' +
            'Your seat reservation has expired.';

        reservationEndTime = null;

        setTimeout(function() {

            window.location.reload();

        }, 1500);

        return;
    }


    /* =================================================
       CALCULATE TIME
    ================================================= */

    const totalSeconds =
        Math.floor(remaining / 1000);


    const minutes =
        Math.floor(totalSeconds / 60);


    const seconds =
        totalSeconds % 60;


    timerText.innerText =
        String(minutes).padStart(2, "0") +
        ":" +
        String(seconds).padStart(2, "0");

}

</script>

</body>

</html>