<?php

include "adminAuth.php";
include "../db.php";


/* ================= GET SHOWTIMES ================= */

$sql = "SELECT
            s.showtime_id,
            s.show_date,
            s.show_time,
            s.ticket_price,
            m.title AS movie_name,
            h.hall_name
        FROM Showtime s
        JOIN Movie m
            ON s.movie_id = m.movie_id
        JOIN hall h
            ON s.hall_id = h.hall_id
        ORDER BY s.show_date, s.show_time";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Showtimes</title>

<link rel="stylesheet" href="manageShowtimes.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>

<div class="container">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <h2>HiMovie</h2>

        <ul>

            <li>
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

            <li class="active">
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


    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Showtimes</h1>


        <?php if (isset($_SESSION["success"])): ?>

        <div class="success-message">
            <i class="fa-solid fa-circle-check"></i>

            <?php
            echo htmlspecialchars($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?>
        </div>

        <?php endif; ?>

        <?php if (isset($_SESSION["delete_success"])): ?>

            <div class="delete-message">

                <i class="fa-solid fa-trash"></i>

                <?php
                echo htmlspecialchars($_SESSION["delete_success"]);
                unset($_SESSION["delete_success"]);
                ?>

            </div>

        <?php endif; ?>

        <?php if (isset($_SESSION["error"])): ?>

            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>

                <?php
                echo htmlspecialchars($_SESSION["error"]);
                unset($_SESSION["error"]);
                ?>
            </div>

        <?php endif; ?>

          

        <a href="addShowtime.php" class="add-btn">

            <i class="fa-solid fa-plus"></i>

            Add Showtime

        </a>
        <!-- ================= TABLE ================= -->

        <div class="table-box">

            <table>

                <thead>

                    <tr>

                        <th>Showtime ID</th>
                        <th>Movie</th>
                        <th>Hall</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Ticket Price</th>
                        <th>Action</th>

                    </tr>

                </thead>
                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($showtime = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                ST<?php echo str_pad(
                                    $showtime["showtime_id"],
                                    3,
                                    "0",
                                    STR_PAD_LEFT
                                ); ?>
                            </td>


                            <td>
                                <?php echo htmlspecialchars(
                                    $showtime["movie_name"]
                                ); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(
                                    $showtime["hall_name"]
                                ); ?>
                            </td>


                            <td>
                                <?php echo date(
                                    "d M Y",
                                    strtotime($showtime["show_date"])
                                ); ?>
                            </td>


                            <td>
                                <?php echo htmlspecialchars(
                                    $showtime["show_time"]
                                ); ?>
                            </td>
                            <td>
                                Rs. <?php echo number_format(
                                    $showtime["ticket_price"],
                                    2
                                ); ?>
                            </td>


                            <td>

                                <div class="actions">

                                    <a
                                        href="editShowtime.php?id=<?php echo $showtime["showtime_id"]; ?>"
                                        class="edit">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <a
                                        href="deleteShowtime.php?id=<?php echo $showtime["showtime_id"]; ?>"
                                        class="delete"
                                        onclick="return confirm('Are you sure you want to delete this showtime?');">

                                        <i class="fa-solid fa-trash"></i>

                                    </a>
                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7">

                            No showtimes found.

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