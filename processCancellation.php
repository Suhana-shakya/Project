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

$customer_id = (int) $_SESSION["customer_id"];


/* =====================================================
   CHECK TICKET ID
===================================================== */

if (
    !isset($_POST["ticket_id"]) ||
    !is_numeric($_POST["ticket_id"])
) {
    header("Location: myBookings.php");
    exit();
}

$ticket_id = (int) $_POST["ticket_id"];


/* =====================================================
   GET TICKET DETAILS
===================================================== */

$sql = "SELECT
            t.ticket_id,
            t.customer_id,
            t.status,
            t.payment_status,
            t.refund_percentage,
            t.refund_amount,
            m.status AS movie_status,
            st.show_date,
            st.show_time,
            s.seat_type,
            st.ticket_price

        FROM ticket t

        INNER JOIN showtime st
            ON t.showtime_id = st.showtime_id

        INNER JOIN seat s
            ON t.seat_id = s.seat_id
        INNER JOIN movie m
            ON t.movie_id = m.movie_id

        WHERE t.ticket_id = ?
        AND t.customer_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $ticket_id,
    $customer_id
);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   CHECK TICKET
===================================================== */

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    header("Location: myBookings.php");
    exit();
}

$ticket = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   CHECK STATUS
===================================================== */

if ($ticket["status"] !== "Confirmed") {

    $conn->close();

    header("Location: myBookings.php");
    exit();
}
/* BLOCK CANCELLATION FOR ARCHIVED MOVIES */

if ($ticket["movie_status"] === "Archived") {
    $_SESSION["cancellation_error"] =
        "Sorry, this ticket cannot be cancelled because the movie is archived.";

    $conn->close();
    header("Location: myBookings.php");
    exit();
}
/* =====================================================
   CALCULATE SHOW TIME AGAIN
===================================================== */

$show_datetime =
    $ticket["show_date"] . " " . $ticket["show_time"];

$show_timestamp = strtotime($show_datetime);

$current_timestamp = time();

$seconds_until_show =
    $show_timestamp - $current_timestamp;


/* =====================================================
   DO NOT ALLOW CANCELLATION AFTER SHOW STARTS
===================================================== */

if ($seconds_until_show <= 0) {
    $_SESSION["cancellation_error"] =
        "Sorry, cancellation is not allowed because this show's date and time have already passed.";

    $conn->close();
    header("Location: myBookings.php");
    exit();
}


/* =====================================================
   CALCULATE HOURS REMAINING
===================================================== */

$hours_until_show =
    $seconds_until_show / 3600;


/* =====================================================
   CALCULATE REFUND PERCENTAGE
===================================================== */

if ($hours_until_show > 24) {

    $refund_percentage = 90;

} elseif ($hours_until_show > 12) {

    $refund_percentage = 75;

} elseif ($hours_until_show >= 2) {

    $refund_percentage = 50;

} else {

    $refund_percentage = 0;

}



/* =====================================================
   CALCULATE TICKET PRICE FROM SEAT TYPE AND SHOW TIME
===================================================== */

$seat_type = $ticket["seat_type"];
$show_time = $ticket["show_time"];

$hour = (int) date("G", strtotime($show_time));

if ($hour < 12) {

    if ($seat_type === "Regular") {
        $ticket_price = 350;
    } elseif ($seat_type === "Premium") {
        $ticket_price = 400;
    } elseif ($seat_type === "VIP") {
        $ticket_price = 450;
    } else {
        throw new Exception("Invalid seat type.");
    }

} else {

    if ($seat_type === "Regular") {
        $ticket_price = 450;
    } elseif ($seat_type === "Premium") {
        $ticket_price = 500;
    } elseif ($seat_type === "VIP") {
        $ticket_price = 550;
    } else {
        throw new Exception("Invalid seat type.");
    }
}

/* =====================================================
   CALCULATE REFUND AMOUNT
===================================================== */

$refund_amount =
    ($ticket_price * $refund_percentage) / 100;



/* =====================================================
   START DATABASE TRANSACTION
===================================================== */

$conn->begin_transaction();

try {

    /* =================================================
       UPDATE TICKET
    ================================================= */
	$refund_status = ($refund_amount > 0)
    ? "Refund Pending"
    : "Not Applicable";
    $update_sql = "UPDATE ticket

                   SET status = 'Cancelled',
                       refund_percentage = ?,
                       refund_amount = ?,
					   refund_status = ?,
                       cancelled_at = NOW()

                   WHERE ticket_id = ?
                   AND customer_id = ?
                   AND status = 'Confirmed'";

    $update_stmt = $conn->prepare($update_sql);

    $update_stmt->bind_param(
        "ddsii",
        $refund_percentage,
        $refund_amount,
		$refund_status,
        $ticket_id,
        $customer_id
    );

    $update_stmt->execute();


    /* =================================================
       CHECK UPDATE
    ================================================= */

    if ($update_stmt->affected_rows !== 1) {

        throw new Exception(
            "Ticket could not be cancelled."
        );
    }

    $update_stmt->close();


    /* =================================================
       CREATE CUSTOMER NOTIFICATION
    ================================================= */

    if ($refund_percentage > 0) {

        $message =
            "Your ticket #$ticket_id has been cancelled. " .
            "A refund of Rs. " .
            number_format($refund_amount, 2) .
            " ($refund_percentage%) is applicable.";

    } else {

        $message =
            "Your ticket #$ticket_id has been cancelled. " .
            "No refund is applicable because the cancellation " .
            "was made less than 2 hours before the show.";
    }


    $notification_sql =
        "INSERT INTO notification
        (customer_id, ticket_id, message)
        VALUES (?, ?, ?)";

    $notification_stmt =
        $conn->prepare($notification_sql);

    $notification_stmt->bind_param(
        "iis",
        $customer_id,
        $ticket_id,
        $message
    );

    $notification_stmt->execute();

    $notification_stmt->close();


    /* =================================================
       COMMIT
    ================================================= */

    $conn->commit();


    /* =================================================
       REDIRECT
    ================================================= */

    header(
        "Location: cancellationSuccess.php?id=" .
        $ticket_id
    );

    exit();


} catch (Exception $e) {

    /* =================================================
       ROLLBACK
    ================================================= */

    $conn->rollback();

    $conn->close();

    die(
        "Unable to cancel the ticket. Please try again."
    );
}

?>