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
   CHECK BOOKING SESSION
===================================================== */

if (!isset($_SESSION["esewa_booking"])) {
    die("Booking information not found.");
}

$booking = $_SESSION["esewa_booking"];

$movie_id =
    (int)$booking["movie_id"];

$showtime_id =
    (int)$booking["showtime_id"];

$hall_id =
    (int)$booking["hall_id"];

$seat_ids =
    $booking["seat_ids"];

$transaction_uuid =
    $booking["transaction_uuid"];


if (
    $movie_id <= 0 ||
    $showtime_id <= 0 ||
    $hall_id <= 0 ||
    empty($seat_ids)
) {
    die("Invalid booking information.");
}


/* =====================================================
   ESEWA CONFIGURATION
===================================================== */

$product_code = "EPAYTEST";

$secret_key = "8gBm/:&EnhH.1/q";


/* =====================================================
   GET ESEWA RESPONSE
===================================================== */

if (!isset($_GET["data"])) {
    die("Invalid eSewa response.");
}

$encoded_data = $_GET["data"];


/* =====================================================
   DECODE BASE64 RESPONSE
===================================================== */

$decoded_data = base64_decode(
    $encoded_data,
    true
);

if ($decoded_data === false) {
    die("Unable to decode eSewa response.");
}


$response = json_decode(
    $decoded_data,
    true
);

if (!is_array($response)) {
    die("Invalid eSewa response data.");
}


/* =====================================================
   GET RESPONSE VALUES
===================================================== */

$response_status =
    $response["status"] ?? "";

$response_total_amount =
    isset($response["total_amount"])
    ? (float)$response["total_amount"]
    : 0;

$response_transaction_uuid =
    $response["transaction_uuid"] ?? "";

$response_product_code =
    $response["product_code"] ?? "";

$response_signature =
    $response["signature"] ?? "";


/* =====================================================
   CHECK BASIC RESPONSE VALUES
===================================================== */

if ($response_status !== "COMPLETE") {
    die("Payment was not completed.");
}

if ($response_product_code !== $product_code) {
    die("Invalid eSewa product code.");
}

if (
    !hash_equals(
        $transaction_uuid,
        $response_transaction_uuid
    )
) {
    die("Invalid transaction UUID.");
}

if ($response_signature === "") {
    die("Missing eSewa signature.");
}


/* =====================================================
   VERIFY ESEWA RESPONSE SIGNATURE
===================================================== */

$signed_field_names =
    $response["signed_field_names"] ?? "";

if ($signed_field_names === "") {
    die("Missing signed field information.");
}


$field_names =
    explode(
        ",",
        $signed_field_names
    );

$message_parts = [];

foreach ($field_names as $field_name) {

    if (!array_key_exists(
        $field_name,
        $response
    )) {
        die("Invalid signed response.");
    }

    $message_parts[] =
        $field_name .
        "=" .
        $response[$field_name];
}

$message =
    implode(
        ",",
        $message_parts
    );


$hash =
    hash_hmac(
        "sha256",
        $message,
        $secret_key,
        true
    );

$expected_signature =
    base64_encode($hash);


if (
    !hash_equals(
        $expected_signature,
        $response_signature
    )
) {
    die("Invalid eSewa signature.");
}


/* =====================================================
   REMOVE EXPIRED TEMPORARY BOOKINGS
===================================================== */

$conn->query("
    DELETE FROM temporary_booking
    WHERE expires_at <= NOW()
");


/* =====================================================
   GET ACTUAL TICKET PRICE
===================================================== */

$price_sql = "
    SELECT ticket_price
    FROM showtime
    WHERE showtime_id = ?
      AND movie_id = ?
      AND hall_id = ?
    LIMIT 1
";

$price_stmt =
    $conn->prepare($price_sql);

$price_stmt->bind_param(
    "iii",
    $showtime_id,
    $movie_id,
    $hall_id
);

$price_stmt->execute();

$price_result =
    $price_stmt->get_result();

if ($price_result->num_rows === 0) {
    die("Showtime not found.");
}

$price_row =
    $price_result->fetch_assoc();

$ticket_price =
    (float)$price_row["ticket_price"];

$expected_amount =
    $ticket_price * count($seat_ids);


/* =====================================================
   VERIFY PAYMENT AMOUNT
===================================================== */

if (
    abs(
        $response_total_amount -
        $expected_amount
    ) > 0.01
) {
    die("Payment amount does not match booking amount.");
}


/* =====================================================
   START DATABASE TRANSACTION
===================================================== */

$conn->begin_transaction();

try {

    /* =================================================
       VERIFY TEMPORARY BOOKINGS
    ================================================= */

    $placeholders = implode(
        ",",
        array_fill(
            0,
            count($seat_ids),
            "?"
        )
    );

    $types =
        "ii" .
        str_repeat(
            "i",
            count($seat_ids)
        );

    $params = array_merge(
        [
            $showtime_id,
            $customer_id
        ],
        $seat_ids
    );


    $sql = "
        SELECT seat_id
        FROM temporary_booking
        WHERE showtime_id = ?
          AND customer_id = ?
          AND seat_id IN ($placeholders)
          AND expires_at > NOW()
        FOR UPDATE
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

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $valid_seats[] =
            (int)$row["seat_id"];
    }


    if (
        count($valid_seats) !==
        count($seat_ids)
    ) {

        throw new Exception(
            "Your seat reservation has expired."
        );
    }


    /* =================================================
       CHECK PERMANENTLY BOOKED SEATS
    ================================================= */

    $ticket_placeholders = implode(
        ",",
        array_fill(
            0,
            count($seat_ids),
            "?"
        )
    );

    $ticket_types =
        "i" .
        str_repeat(
            "i",
            count($seat_ids)
        );

    $ticket_params = array_merge(
        [
            $showtime_id
        ],
        $seat_ids
    );


    $ticket_sql = "
        SELECT seat_id
        FROM ticket
        WHERE showtime_id = ?
          AND seat_id IN ($ticket_placeholders)
          AND payment_status = 'Paid'
        FOR UPDATE
    ";

    $ticket_stmt =
        $conn->prepare($ticket_sql);

    $ticket_stmt->bind_param(
        $ticket_types,
        ...$ticket_params
    );

    $ticket_stmt->execute();

    $ticket_result =
        $ticket_stmt->get_result();


    if ($ticket_result->num_rows > 0) {

        throw new Exception(
            "One or more seats have already been booked."
        );
    }


    /* =================================================
       INSERT TICKETS
    ================================================= */

    $insert_sql = "
        INSERT INTO ticket
        (
            booking_date,
            status,
            movie_id,
            seat_id,
            showtime_id,
            customer_id,
            payment_status
        )
        VALUES
        (
            CURDATE(),
            'Confirmed',
            ?,
            ?,
            ?,
            ?,
            'Paid'
        )
    ";

    $insert_stmt =
        $conn->prepare($insert_sql);

    $ticket_ids = [];
    foreach ($seat_ids as $seat_id) {

        $insert_stmt->bind_param(
            "iiii",
            $movie_id,
            $seat_id,
            $showtime_id,
            $customer_id
        );

        $insert_stmt->execute();
        $ticket_ids[] = $conn->insert_id;
    }


    /* =================================================
       DELETE TEMPORARY BOOKINGS
    ================================================= */

    $delete_placeholders = implode(
        ",",
        array_fill(
            0,
            count($seat_ids),
            "?"
        )
    );

    $delete_types =
        "ii" .
        str_repeat(
            "i",
            count($seat_ids)
        );

    $delete_params = array_merge(
        [
            $showtime_id,
            $customer_id
        ],
        $seat_ids
    );


    $delete_sql = "
        DELETE FROM temporary_booking
        WHERE showtime_id = ?
          AND customer_id = ?
          AND seat_id IN ($delete_placeholders)
    ";

    $delete_stmt =
        $conn->prepare($delete_sql);

    $delete_stmt->bind_param(
        $delete_types,
        ...$delete_params
    );

    $delete_stmt->execute();


    /* =================================================
       COMMIT
    ================================================= */

    $conn->commit();


    /* =================================================
       SAVE CONFIRMATION DATA
    ================================================= */

    $_SESSION["booking_confirmation"] = [
        "ticket_ids" => $ticket_ids,
        "transaction_uuid" => $transaction_uuid,
        "total_amount" => $expected_amount
    ];


    /* =================================================
       CLEAR ESEWA SESSION
    ================================================= */

    unset(
        $_SESSION["esewa_booking"]
    );


    /* =================================================
       REDIRECT
    ================================================= */

    header(
        "Location: confirmation.php"
    );

    exit();


} catch (Exception $e) {

    $conn->rollback();

    die(
        "Payment was successful, but the booking could not be completed. " .
        $e->getMessage()
    );
}

?>
