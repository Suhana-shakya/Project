
<?php
include "db.php";

$error = "";
$success = "";

$name = "";
$phone_number = "";
$address = "";
$email = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data safely
    $name = trim($_POST["fullname"] ?? "");
    $phone_number = trim($_POST["phone_number"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // 1. Validate full name
    if ($name === "") {
        $error = "Full name is required.";
    } elseif (!preg_match("/^[\p{L} .'-]+$/u", $name)) {
        $error = "Full name can contain only letters, spaces, dots, apostrophes and hyphens.";
    } elseif (strlen($name) > 100) {
        $error = "Full name must not exceed 100 characters.";
    }

    // 2. Validate email
    if ($error === "") {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($email) > 150) {
            $error = "Email must not exceed 150 characters.";
        }
    }

    // 3. Validate Nepali mobile number
    if ($error === "") {
        $phone = preg_replace('/[\s-]+/', '', $phone_number);

        if (!preg_match('/^(?:\+977)?9[678]\d{8}$/', $phone)) {
            $error = "Enter a valid Nepali mobile number, e.g. 98XXXXXXXX or +97798XXXXXXXX.";
        }
    }

    // 4. Validate address
    if ($error === "") {
        if ($address === "") {
            $error = "Address is required.";
        } elseif (strlen($address) > 255) {
            $error = "Address must not exceed 255 characters.";
        }
    }

    // 5. Validate username
    if ($error === "") {
        if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
            $error = "Username must be 3–20 characters and contain only letters, numbers and underscores.";
        }
    }

    // 6. Validate password
    if ($error === "") {
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            $error = "Password must contain uppercase and lowercase letters, a number and a special character.";
        }
    }

    // 7. Confirm password
    if ($error === "") {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        }
    }

    // 8. Check duplicate email
    if ($error === "") {
        $sql = "SELECT customer_id FROM customer WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "This email is already registered.";
        }

        $stmt->close();
    }

    // 9. Check duplicate username
    if ($error === "") {
        $sql = "SELECT customer_id FROM customer WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "This username is already taken.";
        }

        $stmt->close();
    }

    // 10. Register customer
    if ($error === "") {
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
            $stmt->close();
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

<?php include "navbar.php"; ?>

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
    maxlength="100"
    pattern="[A-Za-zÀ-ÿ .'-]+"
    title="Use letters, spaces, dots, apostrophes or hyphens."
    value="<?php echo htmlspecialchars($name); ?>"
    required>

<input
    type="email"
    name="email"
    placeholder="Email Address"
    maxlength="150"
    value="<?php echo htmlspecialchars($email); ?>"
    required>

<input
    type="tel"
    name="phone_number"
    placeholder="Phone Number"
    pattern="(\+977)?9[678][0-9]{8}"
    title="Enter a valid Nepali mobile number, e.g. 98XXXXXXXX or +97798XXXXXXXX."
    value="<?php echo htmlspecialchars($phone_number); ?>"
    required>

<input
    type="text"
    name="address"
    placeholder="Address"
    maxlength="255"
    value="<?php echo htmlspecialchars($address); ?>"
    required>

<input
    type="text"
    name="username"
    id="username"
    placeholder="Username"
    minlength="3"
    maxlength="20"
    pattern="[A-Za-z0-9_]+"
    autocomplete="off"
    value=""
    required>

<span id="usernameMessage"></span>


<input
    type="password"
    name="password"
    placeholder="Password"
    minlength="8"
    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
    title="Use at least 8 characters with uppercase and lowercase letters, a number and a special character."
    required>

<input
    type="password"
    name="confirm_password"
    placeholder="Confirm Password"
    minlength="8"
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

<script>
var usernameInput = document.getElementById("username");
var usernameMessage = document.getElementById("usernameMessage");
usernameMessage.innerHTML = "";

usernameInput.addEventListener("input", function () {
    var username = this.value.trim();

    if (username === "") {
        usernameMessage.innerHTML = "";
        return;
    }

    var xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var response = xhr.responseText.trim();

                if (response === "available") {
                    usernameMessage.innerHTML =
                        "&#10004; Username is available";
                    usernameMessage.style.color = "lightgreen";
                } else if (response === "taken") {
                    usernameMessage.innerHTML =
                        "&#10006; Username is already taken";
                    usernameMessage.style.color = "#ff6b6b";
                } else {
                    usernameMessage.innerHTML =
                        "Unable to check username.";
                    usernameMessage.style.color = "orange";
                    console.log("Unexpected response:", response);
                }
            } else {
                usernameMessage.innerHTML =
                    "Error checking username.";
                console.log("HTTP error:", xhr.status);
            }
        }
    };

    xhr.open(
        "GET",
        "checkUsername.php?username=" + encodeURIComponent(username),
        true
    );

    xhr.send();
});
</script>

</body>
</html>