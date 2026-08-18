<?php

include "adminAuth.php";
include "../db.php";


/* ================= GET SEATS ================= */

$sql = "SELECT
            s.seat_id,
            s.seat_number,
            s.seat_type,
            h.hall_name
        FROM seat s
        JOIN hall h
            ON s.hall_id = h.hall_id
        ORDER BY h.hall_name, s.seat_number";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Seats | HiMovie</title>

    <link rel="stylesheet" href="manageSeats.css">

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

            <li>
                <a href="manageShowtimes.php">
                    <i class="fa-solid fa-clock"></i>
                    Showtimes
                </a>
            </li>

            <li class="active">
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

        <div class="top">

            <div>
                <h1>Manage Seats</h1>

                <p class="subtitle">
                    Manage seats in HiMovie Cinema Hall
                </p>
            </div>
            <a href="addSeat.php" class="add-btn">

                <i class="fa-solid fa-plus"></i>

                Add Seat

            </a>

        </div>


        <!-- ================= TABLE ================= -->

        <div class="table-box">

            <table>

                <thead>

                    <tr>

                        <th>Seat ID</th>

                        <th>Seat Number</th>

                        <th>Seat Type</th>

                        <th>Cinema Hall</th>

                        <th>Action</th>

                    </tr>

                </thead>
                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($seat = $result->fetch_assoc()): ?>

                        <tr>


                            <td>

                                S<?php echo str_pad(
                                    $seat["seat_id"],
                                    3,
                                    "0",
                                    STR_PAD_LEFT
                                ); ?>

                            </td>


                            <td>

                                <?php echo htmlspecialchars(
                                    $seat["seat_number"]
                                ); ?>
                                </td>


                            <td>

                                <span class="seat-type">

                                    <?php echo htmlspecialchars(
                                        $seat["seat_type"]
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <?php echo htmlspecialchars(
                                    $seat["hall_name"]
                                ); ?>

                            </td>


                            <td>

                                <div class="actions">
                                    <a
                                        href="editSeat.php?id=<?php echo $seat["seat_id"]; ?>"
                                        class="edit">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <a href="deleteSeat.php?id=<?php echo $seat["seat_id"]; ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this seat?');">

                                        <i class="fa-solid fa-trash"></i>
                                        

                                    </a>


                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>
                    <?php else: ?>

                    <tr>

                        <td colspan="5">

                            No seats found.

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