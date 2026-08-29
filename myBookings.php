<?php

session_start();

include "db.php";

/* ================= CHECK LOGIN ================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customer_id"];


/* ================= GET BOOKINGS ================= */

$sql = "SELECT 
            t.ticket_id,
            t.booking_date,
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

        WHERE t.customer_id = ?

        ORDER BY t.booking_date DESC, st.show_date DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $customer_id);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings - HiMovie</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="myBookings.css">

</head>

<body>

<?php include "navbar.php"; ?>


<section class="bookings-section">

    <div class="bookings-container">

        <div class="page-header">

            <h1>My Bookings</h1>

            <p>
                View your movie tickets and booking details.
            </p>

        </div>


        <?php if ($result->num_rows > 0): ?>

            <div class="booking-list">

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <div class="booking-card">

                        <!-- ================= MOVIE POSTER ================= -->

                        <div class="booking-poster">

                            <img
                                src="uploads/posters/<?php echo htmlspecialchars($booking["poster"]); ?>"
                                alt="<?php echo htmlspecialchars($booking["title"]); ?>">

                        </div>


                        <!-- ================= BOOKING DETAILS ================= -->

                        <div class="booking-details">

                            <h2>
                                <?php echo htmlspecialchars($booking["title"]); ?>
                            </h2>

                            <div class="booking-info">

                                <div>
                                    <i class="fa-regular fa-calendar"></i>

                                    <span>
                                        <?php echo htmlspecialchars($booking["show_date"]); ?>
                                    </span>
                                </div>


                                <div>
                                    <i class="fa-regular fa-clock"></i>

                                    <span>
                                        <?php echo htmlspecialchars($booking["show_time"]); ?>
                                    </span>
                                </div>


                                <div>
                                    <i class="fa-solid fa-building"></i>

                                    <span>
                                        <?php echo htmlspecialchars($booking["hall_name"]); ?>
                                    </span>
                                </div>


                                <div>
                                    <i class="fa-solid fa-couch"></i>

                                    <span>
                                        Seat <?php echo htmlspecialchars($booking["seat_number"]); ?>
                                    </span>
                                </div>

                            </div>


                            <div class="booking-bottom">

                                <div class="ticket-number">

                                    <span>Ticket ID</span>

                                    <strong>
                                        #<?php echo $booking["ticket_id"]; ?>
                                    </strong>

                                </div>


                                <div class="status-container">

                                    <span class="payment-status">

                                        <?php echo htmlspecialchars($booking["payment_status"]); ?>

                                    </span>


                                    <span class="booking-status">

                                        <?php echo htmlspecialchars($booking["status"]); ?>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="no-bookings">

                <i class="fa-solid fa-ticket"></i>

                <h2>No Bookings Yet</h2>

                <p>
                    You haven't booked any movies yet.
                </p>

                <a href="movies.php">
                    Browse Movies
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>

</body>

</html>

<?php

$stmt->close();

?>