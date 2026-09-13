<?php

include "adminAuth.php";
include "../db.php";

$error = "";
$success = "";


/* ================= UPDATE TICKET STATUS ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ticket_id = (int) $_POST["ticket_id"];
    $status = trim($_POST["status"]);


    /* ============================================
       GET CURRENT STATUS AND CUSTOMER
    ============================================ */

    $check_sql = "SELECT
                  t.customer_id,
                  t.status,
                  m.title AS movie_title,
                  s.seat_number,
                  st.show_date,
                  st.show_time
              FROM ticket t
              LEFT JOIN movie m
                  ON t.movie_id = m.movie_id
              LEFT JOIN seat s
                  ON t.seat_id = s.seat_id
              LEFT JOIN showtime st
                  ON t.showtime_id = st.showtime_id
              WHERE t.ticket_id = ?";

    $check_stmt = $conn->prepare($check_sql);

    $check_stmt->bind_param("i", $ticket_id);

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();


    if ($check_result->num_rows == 1) {

        $ticket_data = $check_result->fetch_assoc();

        $customer_id = $ticket_data["customer_id"];
        $old_status = $ticket_data["status"];
		$movie_title = $ticket_data["movie_title"];
		$seat_number = $ticket_data["seat_number"];
		$show_date = $ticket_data["show_date"];
		$show_time = $ticket_data["show_time"];


        /* ============================================
           ONLY UPDATE IF STATUS ACTUALLY CHANGED
        ============================================ */

        if ($old_status !== $status) {

            /* ================= UPDATE TICKET ================= */

            $sql = "UPDATE ticket
                    SET status = ?
                    WHERE ticket_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "si",
                $status,
                $ticket_id
            );


            if ($stmt->execute()) {


                /* ============================================
                   CREATE NOTIFICATION
                ============================================ */

                if ($status === "Confirmed") {

					$message =
						"Your ticket #$ticket_id for $movie_title " .
						"(Seat $seat_number, $show_date at $show_time) " .
						"has been confirmed.";

				} elseif ($status === "Pending") {

					$message =
						"Your ticket #$ticket_id for $movie_title " .
						"(Seat $seat_number, $show_date at $show_time) " .
						"is now pending.";

				} elseif ($status === "Cancelled") {

					$message =
						"Your ticket #$ticket_id for $movie_title " .
						"(Seat $seat_number, $show_date at $show_time) " .
						"has been cancelled.";

				} else {

					$message =
						"The status of your ticket #$ticket_id for $movie_title " .
						"has been changed to $status.";

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

                if ($notification_stmt->execute()) {

                    $success =
                        "Ticket status updated and customer notified.";

                } else {

                    $success =
                        "Ticket status updated, but notification could not be created.";

                }

                $notification_stmt->close();

            } else {

                $error = "Failed to update ticket status.";

            }

            $stmt->close();

        } else {

            $success =
                "Ticket status is already " . $status . ".";

        }

    } else {

        $error = "Ticket not found.";

    }

    $check_stmt->close();
}



/* ================= GET TICKETS ================= */

$sql = "SELECT
            t.ticket_id,
            t.booking_date,
            t.status,
            t.payment_status,

            c.name AS customer_name,

            m.title AS movie_title,

            s.seat_number,

            st.show_date,
            st.show_time

        FROM ticket t

        LEFT JOIN customer c
            ON t.customer_id = c.customer_id

        LEFT JOIN Movie m
            ON t.movie_id = m.movie_id

        LEFT JOIN seat s
            ON t.seat_id = s.seat_id

        LEFT JOIN Showtime st
            ON t.showtime_id = st.showtime_id

        ORDER BY t.ticket_id DESC";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Ticket Status</title>

    <link rel="stylesheet" href="manageTicketStatus.css">
	<link rel="stylesheet" href="sidebar.css">
	

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<div class="container">


    <?php include "sidebar.php"; ?>


    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Ticket Status</h1>


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

                        <th>Date</th>

                        <th>Time</th>

                        <th>Payment</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($ticket = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $ticket["ticket_id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ticket["customer_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ticket["movie_title"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ticket["seat_number"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ticket["show_date"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ticket["show_time"]); ?>
                            </td>

                            <td>
                                <span class="payment-status">
                                    <?php echo htmlspecialchars($ticket["payment_status"]); ?>
                                </span>
                            </td>

                            <td>

                                <span class="ticket-status">

                                    <?php echo htmlspecialchars($ticket["status"]); ?>

                                </span>

                            </td>

                            <td>

                                <form method="POST"
                                      action="manageTicketStatus.php"
                                      class="status-form">

                                    <input
                                        type="hidden"
                                        name="ticket_id"
                                        value="<?php echo $ticket["ticket_id"]; ?>"
                                    >

                                    <select name="status">

                                        <option value="Pending"
                                            <?php if ($ticket["status"] == "Pending") echo "selected"; ?>>
                                            Pending
                                        </option>

                                        <option value="Confirmed"
                                            <?php if ($ticket["status"] == "Confirmed") echo "selected"; ?>>
                                            Confirmed
                                        </option>

                                        <option value="Cancelled"
                                            <?php if ($ticket["status"] == "Cancelled") echo "selected"; ?>>
                                            Cancelled
                                        </option>

                                    </select>

                                    <button type="submit">

                                        <i class="fa-solid fa-check"></i>

                                        Update

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9" class="no-tickets">

                            No tickets found.

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