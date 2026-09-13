<?php

include "adminAuth.php";
include "../db.php";

$sql = "SELECT customer_id,
               name,
               email,
               phone_number,
               address,
               username,
               status
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
	<link rel="stylesheet" href="sidebar.css">
	
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>

<div class="container">

    <?php include "sidebar.php"; ?>


</div>
<!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Users</h1>
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
                        <th>Status</th>
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
                                    <?php echo htmlspecialchars($user["status"]); ?>
                                </td>

                                <td>
                                    <div class="actions">
                                        <a href="deleteUser.php?id=<?php echo $user["customer_id"]; ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to deactivate this user?');"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="no-users">

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