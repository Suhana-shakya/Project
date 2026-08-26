<?php
include "db.php";
$error = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["fullname"]);
    $phone_number = trim($_POST["phone_number"]);
    $address = trim($_POST["address"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    // Check passwords
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }
    // Check email
    if ($error == "") {
        $sql = "SELECT customer_id
                FROM customer
                WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "This email is already registered.";
        }
        $stmt->close();
    }
    // Check username
    if ($error == "") {
        $sql = "SELECT customer_id
                FROM customer
                WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "This username is already taken.";
        }
        $stmt->close();
    }
    // Register customer
    if ($error == "") {
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );
        $sql = "INSERT INTO customer
                (name, email, phone_number, address, username, password)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssss",
            $name,
            $email,
            $phone_number,
            $address,
            $username,
            $hashed_password
        );
        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="register.css">

</head>

<body>

<nav>
<a href="index.php" class="logo">

    <i class="fa-solid fa-clapperboard"></i>

    <span>HiMovie</span>

</a>

    <ul>

    <li>
        <a href="index.php">
            Home
        </a>
    </li>


    <li>
        <a href="movies.php">
            Now Showing
        </a>
    </li>


    <li>
        <a href="upcomingMovies.php">
            Upcoming
        </a>
    </li>


    <li class="search-box">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            placeholder="Search movies..."
        >

    </li>


    <li>

        <a href="login.php" class="login-btn">

            <i class="fa-solid fa-user"></i>

            Login

        </a>

    </li>

</ul>

</nav>

<section class="register-section">

<div class="register-box">

<i class="fa-solid fa-user-plus register-icon"></i>

<h2>Create Account</h2>

<p class="subtitle">
Join HiMovie and start booking your favorite movies.
</p>

<?php if (!empty($error)): ?>

    <div class="login-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>

<?php endif; ?>

<form action="register.php" method="post">

<input
type="text"
name="fullname"
placeholder="Full Name"
required>

<input
type="email"
name="email"
placeholder="Email Address"
required>

<input
    type="tel"
    name="phone_number"
    placeholder="Phone Number"
    required>

<input
    type="text"
    name="address"
    placeholder="Address"
    required>

<input
type="text"
name="username"
placeholder="Username"
required>

<input
type="password"
name="password"
placeholder="Password"
required>

<input
type="password"
name="confirm_password"
placeholder="Confirm Password"
required>



<button type="submit" class="register-btn">
Create Account
</button>

<p class="login-link">
Already have an account?
<a href="login.php">Login</a>
</p>

</form>

</div>

</section>

</body>
</html>