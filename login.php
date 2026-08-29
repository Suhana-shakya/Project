<?php

session_start();

include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT customer_id, name, email, username, password
            FROM customer
            WHERE email = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $customer = $result->fetch_assoc();

        if (password_verify($password, $customer["password"])) {

            $_SESSION["customer_id"] = $customer["customer_id"];
            $_SESSION["customer_name"] = $customer["name"];
            $_SESSION["customer_email"] = $customer["email"];
            $_SESSION["customer_username"] = $customer["username"];

            header("Location: index.php");
            exit();

        } else {

            $error = "Invalid email or password.";

        }

    } else {

        $error = "Invalid email or password.";

    }

    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Booking Login</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="login.css">
    <style>
        .login-error {
            display: flex;
            align-items: center;
            gap: 10px;

            width: 100%;
            padding: 12px 15px;

            margin: 15px 0;

            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            border-radius: 10px;

            color: #fca5a5;

            font-size: 14px;
            font-weight: 600;

            text-align: left;
        }

        .login-error i {
            color: #ef4444;
            font-size: 16px;
        }
    </style>
</head>

<body>

<?php include "navbar.php"; ?>

<section class="login-section">

    <div class="submit-box">

        <i class="fa-solid fa-ticket ticket-icon"></i>

        <h2>Welcome Back</h2>

        <p class="subtitle">
            Sign in to continue booking your favorite movies.
        </p>
        <?php if (!empty($error)): ?>

            <div class="login-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?php echo htmlspecialchars($error); ?>
                </span>

            </div>

        <?php endif; ?>
        <form action="login.php" method="post">

            <input
                type="email"
                name="email"
                placeholder="Enter your Email"
                required>

            <input
                type="password"
                name="password"
                placeholder="Enter your Password"
                required>

            <button type="submit" class="submit-btn">
                Login
            </button>

            <p class="forgot">
                <a href="#">Forgot Password?</a>
            </p>

            <p class="register">
                Don't have an account?
                <a href="register.php">Sign Up</a>
            </p>

        </form>

    </div>

</section>

</body>
</html>