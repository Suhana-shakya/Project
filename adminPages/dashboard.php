<?php

include "adminAuth.php";
include "../db.php";

// ================= ADMIN NAME =================

$admin_id = $_SESSION["admin_id"];

$sql = "SELECT name
        FROM admin
        WHERE admin_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $admin_id);

$stmt->execute();

$result = $stmt->get_result();

$admin = $result->fetch_assoc();

$admin_name = $admin["name"] ?? "Admin";

$stmt->close();

// ================= TOTAL MOVIES =================

$sql = "SELECT COUNT(*) AS total
        FROM Movie";

$result = $conn->query($sql);

$row = $result->fetch_assoc();

$total_movies = $row["total"];


// ================= REGISTERED USERS =================

$sql = "SELECT COUNT(*) AS total
        FROM customer";

$result = $conn->query($sql);

$row = $result->fetch_assoc();

$total_users = $row["total"];


// ================= TODAY'S TICKETS =================

$sql = "SELECT COUNT(*) AS total
        FROM ticket
        WHERE booking_date = CURDATE()";

$result = $conn->query($sql);

$row = $result->fetch_assoc();

$today_tickets = $row["total"];


// ================= TOTAL SHOWTIMES =================

$sql = "SELECT COUNT(*) AS total
        FROM Showtime";

$result = $conn->query($sql);

$row = $result->fetch_assoc();

$total_showtimes = $row["total"];

// ================= RECENT TICKETS =================

$sql = "SELECT
            t.ticket_id,
            t.booking_date,
            t.status,
            t.payment_status,

            c.name AS customer_name,

            m.title AS movie_title,

            s.seat_number,

            st.show_time,
            st.ticket_price

        FROM ticket t

        LEFT JOIN customer c
            ON t.customer_id = c.customer_id

        LEFT JOIN Movie m
            ON t.movie_id = m.movie_id

        LEFT JOIN seat s
            ON t.seat_id = s.seat_id

        LEFT JOIN Showtime st
            ON t.showtime_id = st.showtime_id

        ORDER BY t.ticket_id DESC

        LIMIT 5";

$ticket_result = $conn->query($sql);

// ================= CINEMA HALLS =================

$sql = "SELECT
            hall_id,
            hall_name,
            location,
            capacity
        FROM hall
        ORDER BY hall_id";

$hall_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HiMovie Admin Dashboard</title>

    <link rel="stylesheet" href="dashboard.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<div class="container">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <h2>HiMovie</h2>

        <ul>

            <li class="active">
                <a href="dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li>
                <a href="manageMovies.php">
                    <i class="fa-solid fa-film"></i>
                    Manage Movies
                </a>
            </li>

            <li>
                <a href="manageShowtimes.php">
                    <i class="fa-solid fa-clock"></i>
                    Showtimes
                </a>
            </li>

            <li>
                <a href="manageSeats.php">
                    <i class="fa-solid fa-chair"></i>
                    Manage Seats
                </a>
            </li>

            <li>
                <a href="manageUsers.php">
                    <i class="fa-solid fa-users"></i>
                    Manage Users
                </a>
            </li>

            <li>
                <a href="manageTicketStatus.php">
                    <i class="fa-solid fa-ticket"></i>
                    Manage Ticket Status
                </a>
            </li>

            <li>
                <a href="adminLogout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </li>

        </ul>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main">

        <header>

            <h1>Dashboard</h1>

            <div class="admin">
                Welcome, <?php echo htmlspecialchars($admin_name); ?>
            </div>

        </header>


        <!-- ================= DASHBOARD CARDS ================= -->

        <div class="cards">

            <!-- Movies -->

            <div class="card">

                <i class="fa-solid fa-film"></i>

                <h2><?php echo $total_movies; ?></h2>

                <p>Total Movies</p>

            </div>


            <!-- Users -->

            <div class="card">

                <i class="fa-solid fa-users"></i>

                <h2><?php echo $total_users; ?></h2>

                <p>Registered Users</p>

            </div>


            <!-- Tickets -->

            <div class="card">

                <i class="fa-solid fa-ticket"></i>

                <h2><?php echo $today_tickets; ?></h2>

                <p>Today's Tickets</p>

            </div>


            <!-- Showtimes -->

            <div class="card">

                <i class="fa-solid fa-clock"></i>

                <h2><?php echo $total_showtimes; ?></h2>

                <p>Showtimes</p>

            </div>

        </div>


        <!-- ================= RECENT TICKETS ================= -->

        <div class="table-box">

    <h2>Recent Tickets</h2>

    <table>

        <tr>

            <th>Ticket ID</th>
            <th>Customer</th>
            <th>Movie</th>
            <th>Date</th>
            <th>Time</th>
            <th>Seat</th>
            <th>Amount</th>
            <th>Payment Status</th>
            <th>Ticket Status</th>

        </tr>


        <?php if ($ticket_result->num_rows > 0): ?>

            <?php while ($ticket = $ticket_result->fetch_assoc()): ?>

                <tr>

                    <td>
                        T<?php echo str_pad($ticket["ticket_id"], 3, "0", STR_PAD_LEFT); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($ticket["customer_name"] ?? "Unknown"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($ticket["movie_title"] ?? "Unknown"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($ticket["booking_date"] ?? "-"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($ticket["show_time"] ?? "-"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($ticket["seat_number"] ?? "-"); ?>
                    </td>

                    <td>
                        Rs.<?php echo htmlspecialchars($ticket["ticket_price"] ?? "0"); ?>
                    </td>


                    <!-- PAYMENT STATUS -->

                    <td>

                        <?php if (strtolower($ticket["payment_status"]) == "paid"): ?>

                            <span class="paid">
                                Paid
                            </span>

                        <?php else: ?>

                            <span class="unpaid">
                                <?php echo htmlspecialchars($ticket["payment_status"] ?? "Unpaid"); ?>
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- TICKET STATUS -->

                    <td>

                        <?php
                        $status = strtolower($ticket["status"] ?? "");
                        ?>

                        <?php if ($status == "confirmed"): ?>

                            <span class="confirmed">
                                Confirmed
                            </span>

                        <?php elseif ($status == "cancelled"): ?>

                            <span class="cancelled">
                                Cancelled
                            </span>

                        <?php else: ?>

                            <span>
                                <?php echo htmlspecialchars($ticket["status"] ?? "Pending"); ?>
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="9" style="text-align:center;">
                    No tickets found.
                </td>

            </tr>

        <?php endif; ?>

    </table>

</div>


        <!-- ================= CINEMA HALL INFORMATION ================= -->

<div class="hall-box">

    <h2>Cinema Halls</h2>

    <?php if ($hall_result->num_rows > 0): ?>

        <?php while ($hall = $hall_result->fetch_assoc()): ?>

            <div class="hall">

                <p>
                    <i class="fa-solid fa-building"></i>

                    <?php echo htmlspecialchars($hall["hall_name"]); ?>

                </p>

                <p>
                    <i class="fa-solid fa-chair"></i>

                    <?php echo htmlspecialchars($hall["capacity"]); ?>
                    Seats
                </p>

                <p>
                    <i class="fa-solid fa-location-dot"></i>

                    <?php echo htmlspecialchars($hall["location"]); ?>

                </p>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No cinema halls found.</p>

    <?php endif; ?>

</div>

    </main>

</div>

</body>
</html>