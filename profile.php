<?php

session_start();

include "db.php";

/* ================= CHECK LOGIN ================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customer_id"];

/* ================= GET CUSTOMER ================= */

$sql = "SELECT customer_id, name, email, phone_number, address, username
        FROM customer
        WHERE customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$customer = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile - HiMovie</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="profile.css">

</head>

<body>

<?php include "navbar.php"; ?>


<section class="profile-section">

    <div class="profile-container">

        <!-- ================= PROFILE HEADER ================= -->

        <div class="profile-header">

            <div class="profile-avatar">
                <i class="fa-solid fa-user"></i>
            </div>

            <div>
                <h1>
                    <?php echo htmlspecialchars($customer["name"]); ?>
                </h1>

                <p>
                    @<?php echo htmlspecialchars($customer["username"]); ?>
                </p>
            </div>

        </div>


        <!-- ================= PROFILE INFORMATION ================= -->

        <div class="profile-card">

            <h2>
                <i class="fa-solid fa-user"></i>
                Personal Information
            </h2>


            <div class="profile-grid">

                <div class="profile-field">

                    <label>Full Name</label>

                    <div class="field-value">
                        <?php echo htmlspecialchars($customer["name"]); ?>
                    </div>

                </div>


                <div class="profile-field">

                    <label>Username</label>

                    <div class="field-value">
                        <?php echo htmlspecialchars($customer["username"]); ?>
                    </div>

                </div>


                <div class="profile-field">

                    <label>Email</label>

                    <div class="field-value">
                        <?php echo htmlspecialchars($customer["email"]); ?>
                    </div>

                </div>


                <div class="profile-field">

                    <label>Phone Number</label>

                    <div class="field-value">

                        <?php

                        if (!empty($customer["phone_number"])) {
                            echo htmlspecialchars($customer["phone_number"]);
                        } else {
                            echo "Not provided";
                        }

                        ?>

                    </div>

                </div>


                <div class="profile-field full-width">

                    <label>Address</label>

                    <div class="field-value">

                        <?php

                        if (!empty($customer["address"])) {
                            echo htmlspecialchars($customer["address"]);
                        } else {
                            echo "Not provided";
                        }

                        ?>

                    </div>

                </div>

            </div>


            <!-- ================= ACTIONS ================= -->

            <div class="profile-actions">

                <a href="editProfile.php" class="edit-btn">
                    <i class="fa-solid fa-pen"></i>
                    Edit Profile
                </a>

                <a href="myBookings.php" class="booking-btn">
                    <i class="fa-solid fa-ticket"></i>
                    My Bookings
                </a>

            </div>

        </div>

    </div>

</section>

</body>

</html>