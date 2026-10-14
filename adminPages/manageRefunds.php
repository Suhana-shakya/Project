<?php

include "adminAuth.php";
include "../db.php";

$error = "";
$success = "";


/* =====================================================
   MARK REFUND AS REFUNDED
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ticket_id = isset($_POST["ticket_id"])
        ? (int) $_POST["ticket_id"]
        : 0;

    if ($ticket_id <= 0) {

        $error = "Invalid ticket.";

    } else {

        
/* =============================================
   CHECK REFUND
============================================= */

$check_sql = "SELECT
                customer_id,
                status,
                refund_status,
                refund_amount
              FROM ticket
              WHERE ticket_id = ?";

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $ticket_id);
$check_stmt->execute();

$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 1) {

    $ticket = $check_result->fetch_assoc();

    if ($ticket["status"] !== "Cancelled") {

        $error = "Only cancelled tickets can be refunded.";

    } elseif ($ticket["refund_status"] !== "Refund Pending") {

        $error = "This ticket does not have a pending refund.";

    } elseif ($ticket["refund_amount"] === null ||
              (float)$ticket["refund_amount"] <= 0) {

        $error = "The refund amount is invalid.";

    } else {

        $customer_id = (int)$ticket["customer_id"];
        $refund_amount = (float)$ticket["refund_amount"];

        $update_sql = "UPDATE ticket
                       SET refund_status = 'Refunded'
                       WHERE ticket_id = ?
                         AND status = 'Cancelled'
                         AND refund_status = 'Refund Pending'
                         AND refund_amount > 0";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $ticket_id);

        if ($update_stmt->execute() &&
            $update_stmt->affected_rows === 1) {

            $message = "Your refund for ticket #$ticket_id has been processed successfully. Refund amount: Rs. "
                     . number_format($refund_amount, 2) . ".";

            $notification_sql = "INSERT INTO notification
                                 (customer_id, ticket_id, message)
                                 VALUES (?, ?, ?)";

            $notification_stmt = $conn->prepare($notification_sql);
            $notification_stmt->bind_param(
                "iis",
                $customer_id,
                $ticket_id,
                $message
            );

            if ($notification_stmt->execute()) {
                $success = "Refund marked as completed and customer notified.";
            } else {
                $success = "Refund marked as completed, but notification could not be created.";
            }

            $notification_stmt->close();

        } else {

            $error = "Refund was not updated. It may already have been processed.";

        }

        $update_stmt->close();
    }

} else {

    $error = "Ticket not found.";

}

$check_stmt->close();
}
}


/* =====================================================
   GET REFUND RECORDS
===================================================== */

$sql = "SELECT
            t.ticket_id,
            t.booking_date,
            t.status,
            t.refund_percentage,
            t.refund_amount,
            t.refund_status,
            t.cancelled_at,

            c.name AS customer_name,

            m.title AS movie_title,

            s.seat_number,

            st.show_date,
            st.show_time,

            h.hall_name

        FROM ticket t

        LEFT JOIN customer c
            ON t.customer_id = c.customer_id

        LEFT JOIN movie m
            ON t.movie_id = m.movie_id

        LEFT JOIN seat s
            ON t.seat_id = s.seat_id

        LEFT JOIN showtime st
            ON t.showtime_id = st.showtime_id

        LEFT JOIN hall h
            ON st.hall_id = h.hall_id

        WHERE t.status = 'Cancelled'

        AND t.refund_status IN ('Refund Pending', 'Refunded')

        ORDER BY t.cancelled_at DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Refunds</title>

    <link
        rel="stylesheet"
        href="manageRefunds.css"
    >
	<link
        rel="stylesheet"
        href="sidebar.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

</head>


<body>

<div class="container">


    <?php include "sidebar.php"; ?>



    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Refunds</h1>


        <!-- ================= SUCCESS ================= -->

        <?php if (!empty($success)): ?>

            <div class="success-message">

                <i class="fa-solid fa-circle-check"></i>

                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>


        <!-- ================= ERROR ================= -->

        <?php if (!empty($error)): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- ================= TABLE ================= -->

        <div class="table-box">

            <table>

                <thead>

                    <tr>

                        <th>Ticket ID</th>

                        <th>Customer</th>

                        <th>Movie</th>

                        <th>Seat</th>

                        <th>Show Date</th>

                        <th>Show Time</th>

                        <th>Refund %</th>

                        <th>Refund Amount</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($refund = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php
                                echo $refund["ticket_id"];
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $refund["customer_name"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $refund["movie_title"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $refund["seat_number"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $refund["show_date"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $refund["show_time"]
                                );
                                ?>
                            </td>


                            <td>

                                <span class="refund-percentage">

                                    <?php
                                    echo number_format(
                                        $refund["refund_percentage"],
                                        0
                                    );
                                    ?>%

                                </span>

                            </td>


                            <td>

                                <strong class="refund-amount">

                                    Rs.
                                    <?php
                                    echo number_format(
                                        $refund["refund_amount"],
                                        2
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php if (
                                    $refund["refund_status"]
                                    === "Refund Pending"
                                ): ?>

                                    <span class="refund-status pending">

                                        <i class="fa-solid fa-clock"></i>

                                        Refund Pending

                                    </span>

                                <?php else: ?>

                                    <span class="refund-status refunded">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Refunded

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if (
                                    $refund["refund_status"]
                                    === "Refund Pending"
                                ): ?>

                                    <form
                                        method="POST"
                                        action="manageRefunds.php"
                                        onsubmit="return confirm(
                                            'Have you processed this refund?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="ticket_id"
                                            value="<?php
                                                echo $refund["ticket_id"];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="refund-button"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                            Mark as Refunded

                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="completed-text">

                                        <i class="fa-solid fa-check"></i>

                                        Completed

                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="10"
                            class="no-refunds"
                        >

                            <i class="fa-solid fa-receipt"></i>

                            No pending refunds found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>

</html>