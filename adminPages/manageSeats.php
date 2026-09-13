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
	<link rel="stylesheet" href="sidebar.css">
	

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<div class="container">

    <?php include "sidebar.php"; ?>


    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Seats</h1>
        
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

        <div class="add-seat-container">

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