<?php

include "adminAuth.php";
include "../db.php";

$error = "";


/* ================= ADD SEAT ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $seat_number = trim($_POST["seat_number"]);
    $seat_type = trim($_POST["seat_type"]);
    $hall_id = (int) $_POST["hall_id"];


    /* Check if seat already exists in this hall */

    $check_sql = "SELECT seat_id
                  FROM seat
                  WHERE seat_number = ?
                  AND hall_id = ?";

    $check_stmt = $conn->prepare($check_sql);

    $check_stmt->bind_param(
        "si",
        $seat_number,
        $hall_id
    );

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();


    if ($check_result->num_rows > 0) {

        $error = "This seat already exists in the selected hall.";

    }

    $check_stmt->close();


    /* Insert seat */

    if ($error == "") {

        $sql = "INSERT INTO seat
                (seat_number, seat_type, hall_id)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssi",
            $seat_number,
            $seat_type,
            $hall_id
        );


        if ($stmt->execute()) {

            $_SESSION["success"] = "Seat added successfully.";

            header("Location: manageSeats.php");
            exit();

        }else {

            $_SESSION["error"] = "Failed to add seat. Please try again.";

            header("Location: manageSeats.php");
            exit();

        }

        $stmt->close();

    }

}


/* ================= GET HALLS ================= */

$hall_sql = "SELECT hall_id, hall_name
             FROM hall
             ORDER BY hall_name";

$hall_result = $conn->query($hall_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Seat | HiMovie</title>

    <link rel="stylesheet" href="addSeat.css">

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

        <h1>Add New Seat</h1>

        <p class="subtitle">
            Add a seat to a cinema hall.
        </p>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form
            action="addSeat.php"
            method="post">


            <!-- SEAT NUMBER -->

            <div class="form-group">

                <label>
                    Seat Number
                </label>

                <input
                    type="text"
                    name="seat_number"
                    placeholder="Example: A1"
                    required>

            </div>


            <!-- SEAT TYPE -->

            <div class="form-group">

                <label>
                    Seat Type
                </label>

                <select
                    name="seat_type"
                    required>

                    <option value="">
                        Select Seat Type
                    </option>

                    <option value="Regular">
                        Regular
                    </option>

                    <option value="Premium">
                        Premium
                    </option>

                    <option value="VIP">
                        VIP
                    </option>

                </select>

            </div>


            <!-- HALL -->

            <div class="form-group">

                <label>
                    Cinema Hall
                </label>

                <select
                    name="hall_id"
                    required>

                    <option value="">
                        Select Cinema Hall
                    </option>

                    <?php while ($hall = $hall_result->fetch_assoc()): ?>

                        <option
                            value="<?php echo $hall["hall_id"]; ?>">

                            <?php echo htmlspecialchars(
                                $hall["hall_name"]
                            ); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- BUTTONS -->

            <button
                type="submit"
                class="save-btn">

                <i class="fa-solid fa-floppy-disk"></i>

                Save Seat

            </button>


            <a
                href="manageSeats.php"
                class="cancel-btn">

                Cancel

            </a>


        </form>

    </main>

</div>

</body>

</html>