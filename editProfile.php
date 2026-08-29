
<?php

session_start();

include "db.php";

/* ================= CHECK LOGIN ================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customer_id"];

$error = "";
$success = "";

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


/* ================= UPDATE PROFILE ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone_number = trim($_POST["phone_number"]);
    $address = trim($_POST["address"]);
    $username = trim($_POST["username"]);

    /* ================= BASIC VALIDATION ================= */

    if (
        empty($name) ||
        empty($email) ||
        empty($username)
    ) {

        $error = "Name, email and username are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* ================= CHECK DUPLICATE EMAIL ================= */

        $sql = "SELECT customer_id
                FROM customer
                WHERE email = ?
                AND customer_id != ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $customer_id);
        $stmt->execute();

        $email_result = $stmt->get_result();

        $stmt->close();


        /* ================= CHECK DUPLICATE USERNAME ================= */

        $sql = "SELECT customer_id
                FROM customer
                WHERE username = ?
                AND customer_id != ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $username, $customer_id);
        $stmt->execute();

        $username_result = $stmt->get_result();

        $stmt->close();


        if ($email_result->num_rows > 0) {

            $error = "This email is already being used by another account.";

        } elseif ($username_result->num_rows > 0) {

            $error = "This username is already taken.";

        } else {

            /* ================= UPDATE DATABASE ================= */

            $sql = "UPDATE customer
                    SET name = ?,
                        email = ?,
                        phone_number = ?,
                        address = ?,
                        username = ?
                    WHERE customer_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sssssi",
                $name,
                $email,
                $phone_number,
                $address,
                $username,
                $customer_id
            );

            if ($stmt->execute()) {

                /* ================= UPDATE SESSION ================= */

                $_SESSION["customer_name"] = $name;
                $_SESSION["customer_email"] = $email;
                $_SESSION["customer_username"] = $username;

                header("Location: profile.php");
                exit();

            } else {

                $error = "Something went wrong. Please try again.";

            }

            $stmt->close();
        }
    }

    /* Keep entered values if validation fails */

    $customer["name"] = $name;
    $customer["email"] = $email;
    $customer["phone_number"] = $phone_number;
    $customer["address"] = $address;
    $customer["username"] = $username;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile - HiMovie</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet" href="navbar.css">

    <link rel="stylesheet" href="editProfile.css">

</head>

<body>

<?php include "navbar.php"; ?>


<section class="edit-profile-section">

    <div class="edit-profile-container">

        <!-- ================= HEADER ================= -->

        <div class="edit-header">

            <div class="edit-icon">

                <i class="fa-solid fa-user-pen"></i>

            </div>

            <div>

                <h1>Edit Profile</h1>

                <p>Update your personal information</p>

            </div>

        </div>


        <!-- ================= FORM CARD ================= -->

        <div class="edit-profile-card">

            <?php if (!empty($error)): ?>

                <div class="profile-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?php echo htmlspecialchars($error); ?>
                    </span>

                </div>

            <?php endif; ?>


            <form action="editProfile.php" method="post">


                <!-- ================= NAME ================= -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-user"></i>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($customer["name"]); ?>"
                            maxlength="30"
                            required>

                    </div>

                </div>


                <!-- ================= USERNAME ================= -->

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-at"></i>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?php echo htmlspecialchars($customer["username"]); ?>"
                            maxlength="20"
                            required>

                    </div>

                </div>


                <!-- ================= EMAIL ================= -->

                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($customer["email"]); ?>"
                            maxlength="40"
                            required>

                    </div>

                </div>


                <!-- ================= PHONE ================= -->

                <div class="form-group">

                    <label for="phone_number">
                        Phone Number
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-phone"></i>

                        <input
                            type="text"
                            id="phone_number"
                            name="phone_number"
                            value="<?php echo htmlspecialchars($customer["phone_number"]); ?>"
                            maxlength="15">

                    </div>

                </div>


                <!-- ================= ADDRESS ================= -->

                <div class="form-group">

                    <label for="address">
                        Address
                    </label>

                    <div class="input-wrapper textarea-wrapper">

                        <i class="fa-solid fa-location-dot"></i>

                        <textarea
                            id="address"
                            name="address"
                            maxlength="50"><?php echo htmlspecialchars($customer["address"]); ?></textarea>

                    </div>

                </div>


                <!-- ================= BUTTONS ================= -->

                <div class="edit-actions">

                    <a href="profile.php" class="cancel-btn">
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                    </a>

                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-check"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</section>

</body>

</html>
