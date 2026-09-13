
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
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: myBookings.php");
    exit();
}

$ticket_id = (int) $_GET["id"];


/* =====================================================
   GET CANCELLATION DETAILS
===================================================== */

$sql = "
    SELECT
        t.ticket_id,
        t.refund_percentage,
        t.refund_amount,
        t.refund_status,
        t.status,

        m.title AS movie_title,

        st.show_date,
        st.show_time,

        s.seat_number,

        h.hall_name

    FROM ticket t

    INNER JOIN movie m
        ON t.movie_id = m.movie_id

    INNER JOIN showtime st
        ON t.showtime_id = st.showtime_id

    INNER JOIN seat s
        ON t.seat_id = s.seat_id

    INNER JOIN hall h
        ON st.hall_id = h.hall_id

    WHERE t.ticket_id = ?
    AND t.customer_id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $ticket_id,
    $customer_id
);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   CHECK RESULT
===================================================== */

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    header("Location: myBookings.php");
    exit();
}

$ticket = $result->fetch_assoc();

$stmt->close();
$conn->close();


/* =====================================================
   FORMAT DATA
===================================================== */

$show_date = date(
    "d M Y",
    strtotime($ticket["show_date"])
);

$show_time = date(
    "h:i A",
    strtotime($ticket["show_time"])
);

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cancellation Successful - HiMovie</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }

        body {

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            color: white;

            background:
            radial-gradient(
                circle at top right,
                #7c3aed55 0%,
                transparent 35%
            ),

            radial-gradient(
                circle at bottom left,
                #ec489955 0%,
                transparent 35%
            ),

            linear-gradient(
                135deg,
                #111827,
                #1e1b4b,
                #4c1d95
            );

            padding: 30px;

        }


        /* =========================
           SUCCESS BOX
        ========================= */

        .success-container {

            width: 100%;
            max-width: 650px;

            padding: 40px;

            background: rgba(255,255,255,.08);

            backdrop-filter: blur(18px);

            border: 1px solid rgba(255,255,255,.10);

            border-radius: 22px;

            box-shadow:
                0 20px 50px
                rgba(0,0,0,.30);

            text-align: center;

        }


        /* =========================
           SUCCESS ICON
        ========================= */

        .success-icon {

            width: 80px;
            height: 80px;

            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(34,197,94,.15);

            border: 2px solid rgba(34,197,94,.45);

        }

        .success-icon i {

            color: #22c55e;

            font-size: 38px;

        }


        /* =========================
           HEADING
        ========================= */

        h1 {

            font-size: 32px;

            margin-bottom: 10px;

        }

        .subtitle {

            color: #c4b5fd;

            font-size: 15px;

            margin-bottom: 30px;

        }


        /* =========================
           DETAILS
        ========================= */

        .details {

            text-align: left;

            background: rgba(0,0,0,.18);

            border: 1px solid rgba(255,255,255,.08);

            border-radius: 15px;

            padding: 20px;

            margin-bottom: 25px;

        }

        .detail-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom:
                1px solid
                rgba(255,255,255,.08);

        }

        .detail-row:last-child {

            border-bottom: none;

        }

        .label {

            color: #9ca3af;

        }

        .value {

            color: white;

            font-weight: 600;

            text-align: right;

        }


        /* =========================
           REFUND
        ========================= */

        .refund-box {

            margin-bottom: 25px;

            padding: 18px;

            border-radius: 14px;

            background: rgba(234,179,8,.10);

            border:
                1px solid
                rgba(234,179,8,.30);

        }

        .refund-box h3 {

            color: #fde68a;

            font-size: 16px;

            margin-bottom: 10px;

        }

        .refund-amount {

            font-size: 24px;

            font-weight: 700;

            color: #facc15;

        }

        .refund-status {

            margin-top: 6px;

            color: #eab308;

            font-size: 14px;

        }


        /* =========================
           BUTTONS
        ========================= */

        .buttons {

            display: flex;

            justify-content: center;

            gap: 12px;

            flex-wrap: wrap;

        }

        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 12px 20px;

            border-radius: 10px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            transition: .3s;

        }

        .primary-btn {

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #ec4899,
                    #9333ea
                );

        }

        .secondary-btn {

            color: #e5e7eb;

            background: rgba(255,255,255,.08);

            border:
                1px solid
                rgba(255,255,255,.12);

        }

        .btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(168,85,247,.30);

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width:600px) {

            .success-container {

                padding: 25px;

            }

            h1 {

                font-size: 26px;

            }

            .detail-row {

                flex-direction: column;

                gap: 5px;

            }

            .value {

                text-align: left;

            }

            .btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>

<div class="success-container">


    <div class="success-icon">

        <i class="fa-solid fa-check"></i>

    </div>


    <h1>
        Ticket Cancelled Successfully
    </h1>


    <p class="subtitle">
        Your ticket has been successfully cancelled.
    </p>


    <div class="details">


        <div class="detail-row">

            <span class="label">
                Ticket ID
            </span>

            <span class="value">
                #<?php echo $ticket["ticket_id"]; ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="label">
                Movie
            </span>

            <span class="value">
                <?php echo htmlspecialchars($ticket["movie_title"]); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="label">
                Cinema Hall
            </span>

            <span class="value">
                <?php echo htmlspecialchars($ticket["hall_name"]); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="label">
                Date
            </span>

            <span class="value">
                <?php echo htmlspecialchars($show_date); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="label">
                Time
            </span>

            <span class="value">
                <?php echo htmlspecialchars($show_time); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="label">
                Seat
            </span>

            <span class="value">
                <?php echo htmlspecialchars($ticket["seat_number"]); ?>
            </span>

        </div>


    </div>


    <?php if ((float)$ticket["refund_amount"] > 0): ?>

        <div class="refund-box">

            <h3>
                <i class="fa-solid fa-money-bill-transfer"></i>
                Refund Information
            </h3>

            <div class="refund-amount">

                Rs.
                <?php
                echo number_format(
                    $ticket["refund_amount"],
                    2
                );
                ?>

            </div>

            <div class="refund-status">

                <?php
                echo number_format(
                    $ticket["refund_percentage"],
                    0
                );
                ?>% refund —

                <?php
                echo htmlspecialchars(
                    $ticket["refund_status"]
                );
                ?>

            </div>

        </div>

    <?php else: ?>

        <div class="refund-box">

            <h3>
                <i class="fa-solid fa-circle-info"></i>
                Refund Information
            </h3>

            <div class="refund-status">

                No refund is applicable for this cancellation.

            </div>

        </div>

    <?php endif; ?>


    <div class="buttons">

        <a href="myBookings.php"
           class="btn primary-btn">

            <i class="fa-solid fa-ticket"></i>

            My Bookings

        </a>


        <a href="index.php"
           class="btn secondary-btn">

            <i class="fa-solid fa-house"></i>

            Back to Home

        </a>

    </div>


</div>

</body>

</html>
