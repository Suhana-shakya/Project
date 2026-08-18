<?php

include "adminAuth.php";
include "../db.php";

$sql = "SELECT customer_id,
               name,
               email,
               phone_number,
               address,
               username
        FROM customer
        ORDER BY customer_id DESC";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>

    <link rel="stylesheet" href="manageUsers.css">
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

            <li>
                <a href="manageSeats.php">
                    <i class="fa-solid fa-chair"></i>
                    Manage Seats
                </a>
            </li>

            <li class="active">
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


</div>
<!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Users</h1>


        <!-- ================= TABLE ================= -->

        <div class="table-box">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Username</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php while ($user = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo $user["customer_id"]; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($user["name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($user["email"]); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user["phone_number"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($user["address"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($user["username"]); ?>
                                </td>

                                <td>

                                    <a href="deleteUser.php?id=<?php echo $user["customer_id"]; ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this user?');"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="no-users">

                                No registered users found.

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
<?php

$conn->close();

?>