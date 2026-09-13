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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: myBookings.php");
    exit();
}

$ticket_id = (int) $_GET["id"];


/* =====================================================
   GET TICKET DETAILS
===================================================== */

$sql = "SELECT
            t.ticket_id,
            t.customer_id,
            t.status,
            t.payment_status,

            m.title,
            m.poster,

            st.show_date,
            st.show_time,
            st.ticket_price,

            s.seat_number,
            s.seat_type,

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
   CHECK TICKET EXISTS
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
   CHECK TICKET STATUS
===================================================== */

if ($ticket["status"] !== "Confirmed") {

    $conn->close();

    header("Location: myBookings.php");
    exit();
}


/* =====================================================
   CALCULATE SHOW DATETIME
===================================================== */

$show_datetime = $ticket["show_date"] . " " . $ticket["show_time"];

$show_timestamp = strtotime($show_datetime);

$current_timestamp = time();


/* =====================================================
   CALCULATE HOURS UNTIL SHOW
===================================================== */

$seconds_until_show = $show_timestamp - $current_timestamp;

$hours_until_show = $seconds_until_show / 3600;


/* =====================================================
   CHECK WHETHER SHOW HAS PASSED
===================================================== */

if ($seconds_until_show <= 0) {

    $refund_percentage = 0;
    $refund_amount = 0;

    $cancellation_message =
        "This show has already started or has passed. No refund is available.";

} else {

    /* =================================================
       REFUND RULES
    ================================================= */

    if ($hours_until_show > 24) {

        $refund_percentage = 90;

    } elseif ($hours_until_show > 12) {

        $refund_percentage = 75;

    } elseif ($hours_until_show >= 2) {

        $refund_percentage = 50;

    } else {

        $refund_percentage = 0;

    }

    /* =================================================
       CALCULATE REFUND
    ================================================= */

    $refund_amount =
        ($ticket["ticket_price"] * $refund_percentage) / 100;


    /* =================================================
       CANCELLATION MESSAGE
    ================================================= */

    if ($refund_percentage == 90) {

        $cancellation_message =
            "You are cancelling more than 24 hours before the show.";

    } elseif ($refund_percentage == 75) {

        $cancellation_message =
            "You are cancelling between 12 and 24 hours before the show.";

    } elseif ($refund_percentage == 50) {

        $cancellation_message =
            "You are cancelling between 2 and 12 hours before the show.";

    } else {

        $cancellation_message =
            "You are cancelling less than 2 hours before the show. No refund is available.";

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cancel Ticket - HiMovie</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet" href="navbar.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            min-height: 100vh;

            background:
                radial-gradient(
                    circle at top left,
                    #4c1d95,
                    transparent 40%
                ),
                radial-gradient(
                    circle at bottom right,
                    #831843,
                    transparent 40%
                ),
                #111827;

            color: white;

            font-family: Arial, sans-serif;
        }


        .cancel-section {

            width: 90%;

            max-width: 700px;

            margin: 130px auto 50px;
        }


        .cancel-card {

            background: rgba(255,255,255,0.08);

            border: 1px solid rgba(255,255,255,0.12);

            border-radius: 18px;

            padding: 30px;

            backdrop-filter: blur(12px);

            box-shadow:
                0 20px 40px rgba(0,0,0,0.25);
        }


        .cancel-title {

            text-align: center;

            margin-bottom: 30px;
        }


        .cancel-title i {

            font-size: 42px;

            color: #f87171;

            margin-bottom: 12px;
        }


        .cancel-title h1 {

            margin: 0 0 8px;

            font-size: 30px;
        }


        .cancel-title p {

            margin: 0;

            color: #a1a1aa;
        }


        .movie-info {

            display: flex;

            gap: 20px;

            padding: 20px;

            background: rgba(0,0,0,0.18);

            border-radius: 12px;

            margin-bottom: 25px;
        }


        .movie-poster {

            width: 90px;

            height: 125px;

            object-fit: cover;

            border-radius: 8px;
        }


        .movie-details h2 {

            margin: 0 0 15px;

            font-size: 22px;
        }


        .movie-details p {

            margin: 7px 0;

            color: #d4d4d8;

            font-size: 14px;
        }


        .movie-details i {

            width: 20px;

            color: #c084fc;
        }


        .refund-box {

            padding: 22px;

            background: rgba(168,85,247,0.10);

            border: 1px solid rgba(168,85,247,0.25);

            border-radius: 12px;

            margin-bottom: 20px;
        }


        .refund-row {

            display: flex;

            justify-content: space-between;

            padding: 9px 0;

            color: #d4d4d8;
        }


        .refund-row strong {

            color: white;
        }


        .refund-total {

            border-top: 1px solid rgba(255,255,255,0.15);

            margin-top: 10px;

            padding-top: 15px;

            font-size: 18px;
        }


        .refund-amount {

            color: #4ade80 !important;

            font-size: 22px;
        }


        .warning {

            background: rgba(234,179,8,0.10);

            border: 1px solid rgba(234,179,8,0.25);

            color: #fde68a;

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 22px;

            font-size: 14px;

            line-height: 1.5;
        }


        .button-container {

            display: flex;

            justify-content: center;

            gap: 12px;

            margin-top: 25px;
        }


        .back-btn,
        .confirm-cancel-btn {

            padding: 12px 20px;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;

            border: none;

            transition: 0.25s ease;
        }


        .back-btn {

            background: rgba(255,255,255,0.08);

            border: 1px solid rgba(255,255,255,0.15);

            color: #d4d4d8;
        }


        .back-btn:hover {

            background: rgba(255,255,255,0.14);

            color: white;
        }


        .confirm-cancel-btn {

            background: #dc2626;

            color: white;
        }


        .confirm-cancel-btn:hover {

            background: #ef4444;

            transform: translateY(-2px);
        }


        @media (max-width: 600px) {

            .cancel-section {

                width: 94%;

                margin-top: 110px;
            }

            .cancel-card {

                padding: 20px;
            }

            .movie-info {

                flex-direction: column;

                align-items: center;

                text-align: center;
            }

            .button-container {

                flex-direction: column;
            }

            .back-btn,
            .confirm-cancel-btn {

                text-align: center;

                width: 100%;
            }
        }

    </style>

</head>


<body>

<?php include "navbar.php"; ?>


<section class="cancel-section">

    <div class="cancel-card">


        <!-- ================= TITLE ================= -->

        <div class="cancel-title">

            <i class="fa-solid fa-circle-exclamation"></i>

            <h1>Cancel Ticket</h1>

            <p>
                Please review your refund before cancelling.
            </p>

        </div>


        <!-- ================= MOVIE ================= -->

        <div class="movie-info">

            <img
                class="movie-poster"
                src="uploads/posters/<?php echo htmlspecialchars($ticket["poster"]); ?>"
                alt="<?php echo htmlspecialchars($ticket["title"]); ?>"
            >

            <div class="movie-details">

                <h2>
                    <?php echo htmlspecialchars($ticket["title"]); ?>
                </h2>

                <p>
                    <i class="fa-regular fa-calendar"></i>

                    <?php echo htmlspecialchars($ticket["show_date"]); ?>
                </p>

                <p>
                    <i class="fa-regular fa-clock"></i>

                    <?php echo htmlspecialchars($ticket["show_time"]); ?>
                </p>

                <p>
                    <i class="fa-solid fa-building"></i>

                    <?php echo htmlspecialchars($ticket["hall_name"]); ?>
                </p>

                <p>
                    <i class="fa-solid fa-couch"></i>

                    Seat <?php echo htmlspecialchars($ticket["seat_number"]); ?>
                </p>

            </div>

        </div>


        <!-- ================= WARNING ================= -->

        <div class="warning">

            <i class="fa-solid fa-circle-info"></i>

            <?php echo htmlspecialchars($cancellation_message); ?>

        </div>


        <!-- ================= REFUND ================= -->

        <div class="refund-box">

            <div class="refund-row">

                <span>Ticket Price</span>

                <strong>
                    Rs. <?php echo number_format($ticket["ticket_price"], 2); ?>
                </strong>

            </div>


            <div class="refund-row">

                <span>Refund Percentage</span>

                <strong>
                    <?php echo number_format($refund_percentage, 0); ?>%
                </strong>

            </div>


            <div class="refund-row refund-total">

                <span>Refund Amount</span>

                <strong class="refund-amount">

                    Rs.
                    <?php echo number_format($refund_amount, 2); ?>

                </strong>

            </div>

        </div>


        <!-- ================= BUTTONS ================= -->

        <div class="button-container">

            <a href="myBookings.php"
               class="back-btn">

                <i class="fa-solid fa-arrow-left"></i>

                Go Back

            </a>


            <form method="POST"
                  action="processCancellation.php">

                <input type="hidden"
                       name="ticket_id"
                       value="<?php echo $ticket_id; ?>">

                <button type="submit"
                        class="confirm-cancel-btn"
                        onclick="return confirm('Are you sure you want to cancel this ticket?');">

                    <i class="fa-solid fa-ban"></i>

                    Confirm Cancellation

                </button>

            </form>

        </div>


    </div>

</section>

</body>

</html>

<?php

$conn->close();

?>