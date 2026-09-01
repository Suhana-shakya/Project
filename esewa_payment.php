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
   GET BOOKING INFORMATION
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
   SEAT IDS
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

$conn->query("
    DELETE FROM temporary_booking
    WHERE expires_at <= NOW()
");


/* =====================================================
   VERIFY TEMPORARY BOOKINGS
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

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result = $stmt->get_result();


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


/* =====================================================
   MAKE SURE EVERY SEAT IS STILL RESERVED
===================================================== */

if (
    count($valid_seats)
    !== count($seat_ids)
) {

    die(
        "Your seat reservation has expired. Please select your seats again."
    );
}


/* =====================================================
   GET SHOWTIME INFORMATION
===================================================== */

$sql = "
    SELECT
        s.ticket_price,
        s.movie_id,
        s.hall_id
    FROM showtime s
    WHERE s.showtime_id = ?
      AND s.movie_id = ?
      AND s.hall_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iii",
    $showtime_id,
    $movie_id,
    $hall_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {
    die("Showtime not found.");
}


$showtime =
    $result->fetch_assoc();


/* =====================================================
   CALCULATE TOTAL
===================================================== */

$ticket_price =
    (float)$showtime["ticket_price"];

$number_of_seats =
    count($seat_ids);

$total_amount =
    $ticket_price * $number_of_seats;


/* =====================================================
   ESEWA TEST CONFIGURATION
===================================================== */

$product_code =
    "EPAYTEST";

$secret_key =
    "8gBm/:&EnhH.1/q";


/*
 * eSewa requires a unique transaction UUID.
 *
 * Only alphanumeric characters and hyphens
 * should be used.
 */

$transaction_uuid =
    "HIMOVIE-" .
    date("YmdHis") .
    "-" .
    $customer_id;

    $_SESSION["esewa_booking"] = [
    "movie_id" => $movie_id,
    "showtime_id" => $showtime_id,
    "hall_id" => $hall_id,
    "seat_ids" => $seat_ids,
    "transaction_uuid" => $transaction_uuid,
];

/* =====================================================
   ESEWA AMOUNTS
===================================================== */

$amount =
    number_format(
        $total_amount,
        2,
        ".",
        ""
    );

$tax_amount = "0";

$product_service_charge = "0";

$product_delivery_charge = "0";

$total_amount_esewa =
    $amount;


/* =====================================================
   SUCCESS / FAILURE URL
===================================================== */

$base_url =
    "http://" .
    $_SERVER["HTTP_HOST"] .
    dirname($_SERVER["PHP_SELF"]);


/*
 * Remove trailing slash if necessary.
 */

$base_url =
    rtrim($base_url, "/");


$success_url =
    $base_url . "/esewa_success.php";


$failure_url =
    $base_url . "/esewa_failure.php";


/* =====================================================
   SIGNATURE
===================================================== */

$signed_field_names =
    "total_amount,transaction_uuid,product_code";


$message =
    "total_amount=" .
    $total_amount_esewa .
    ",transaction_uuid=" .
    $transaction_uuid .
    ",product_code=" .
    $product_code;


$hash =
    hash_hmac(
        "sha256",
        $message,
        $secret_key,
        true
    );


$signature =
    base64_encode($hash);


/* =====================================================
   SEND PAYMENT REQUEST TO ESEWA
===================================================== */

$esewa_url =
    "https://rc-epay.esewa.com.np/api/epay/main/v2/form";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<title>Redirecting to eSewa</title>

</head>


<body>

<p>
    Redirecting to eSewa...
</p>


<form
    id="esewaForm"
    action="<?php echo htmlspecialchars($esewa_url); ?>"
    method="POST"
>


<input
    type="hidden"
    name="amount"
    value="<?php echo htmlspecialchars($amount); ?>"
>


<input
    type="hidden"
    name="tax_amount"
    value="<?php echo $tax_amount; ?>"
>


<input
    type="hidden"
    name="total_amount"
    value="<?php echo htmlspecialchars($total_amount_esewa); ?>"
>


<input
    type="hidden"
    name="transaction_uuid"
    value="<?php echo htmlspecialchars($transaction_uuid); ?>"
>


<input
    type="hidden"
    name="product_code"
    value="<?php echo htmlspecialchars($product_code); ?>"
>


<input
    type="hidden"
    name="product_service_charge"
    value="<?php echo $product_service_charge; ?>"
>


<input
    type="hidden"
    name="product_delivery_charge"
    value="<?php echo $product_delivery_charge; ?>"
>


<input
    type="hidden"
    name="success_url"
    value="<?php echo htmlspecialchars($success_url); ?>"
>


<input
    type="hidden"
    name="failure_url"
    value="<?php echo htmlspecialchars($failure_url); ?>"
>


<input
    type="hidden"
    name="signed_field_names"
    value="<?php echo $signed_field_names; ?>"
>


<input
    type="hidden"
    name="signature"
    value="<?php echo htmlspecialchars($signature); ?>"
>


</form>


<script>

document.getElementById("esewaForm").submit();

</script>


</body>

</html>