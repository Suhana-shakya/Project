<?php

session_start();

include "../db.php";
$error="";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT admin_id, name, username, password
            FROM admin
            WHERE username = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $username);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin["password"])) {

            $_SESSION["admin_id"] = $admin["admin_id"];
            $_SESSION["admin_name"] = $admin["name"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: dashboard.php");
            exit();

        } else {

            $error = "Invalid username or password.";

        }

    } else {

        $error = "Invalid username or password.";

    }

    $stmt->close();
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HiMovie Admin Login</title>

    <link rel="stylesheet" href="adminLogin.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>
<header>

    <div class="admin-logo">

        <div class="logo-icon">
            <i class="fa-solid fa-clapperboard"></i>
        </div>

        <div class="logo-text">

            <span class="brand">HiMovie</span>

            <span class="admin-badge">
                ADMIN
            </span>

        </div>

    </div>

</header>


<section class="login-section">

    <div class="submit-box">

        <i class="fa-solid fa-user-shield ticket-icon"></i>

        <h2>Welcome Admin</h2>

        <p class="subtitle">

            Sign in to manage the HiMovie system.

        </p>
        

        <?php if (!empty($error)): ?>

            <div class="login-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>

        <?php endif; ?>

        <form action="adminLogin.php" method="post">
            
            <input
                type="text"
                name="username"
                placeholder="Enter your Username"
                required>


            <input
                type="password"
                name="password"
                placeholder="Enter your Password"
                required>


            <button
                type="submit"
                class="submit-btn">

                Login

            </button>


            <p class="forgot">

                <a href="#">
                    Forgot Password?
                </a>

            </p>


            <p class="register">

                Authorized administrators only.

            </p>

        </form>

    </div>

</section>

</body>

</html>