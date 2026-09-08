<?php

session_start();

date_default_timezone_set("Asia/Kathmandu");

include "db.php";
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Remove expired or already-used password reset requests
$conn->query("
    DELETE FROM password_resets
    WHERE expires_at < NOW()
       OR used = 1
");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);

    if (empty($email)) {

        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* ==========================================
           CHECK CUSTOMER EMAIL
        ========================================== */

        $sql = "SELECT customer_id, name, email
                FROM customer
                WHERE email = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $customer = $result->fetch_assoc();
            // Prevent too many reset requests
            $check_stmt = $conn->prepare("
                SELECT reset_id
                FROM password_resets
                WHERE customer_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                LIMIT 1
            ");

            $check_stmt->bind_param("i", $customer["customer_id"]);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Please wait a minute before requesting another reset link.";
                $check_stmt->close();
            } else {
                $check_stmt->close();

                // Continue with your existing OTP/token generation code here
            }

            $customer_id = $customer["customer_id"];

            /* ==========================================
               GENERATE 6-DIGIT OTP
            ========================================== */

            $otp = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);

            /* ==========================================
               GENERATE SECURE RESET TOKEN
            ========================================== */

            $token = bin2hex(random_bytes(32));

            /* ==========================================
               OTP VALID FOR 10 MINUTES
            ========================================== */
            $created_at = date("Y-m-d H:i:s");
            $expires_at = date(
                "Y-m-d H:i:s",
                time() + (10 * 60)
            );

            /* ==========================================
               REMOVE OLD RESET REQUESTS
            ========================================== */

            $delete_sql = "DELETE FROM password_resets
                           WHERE customer_id = ?";

            $delete_stmt = $conn->prepare($delete_sql);

            $delete_stmt->bind_param(
                "i",
                $customer_id
            );

            $delete_stmt->execute();

            $delete_stmt->close();

            /* ==========================================
               SAVE RESET REQUEST
            ========================================== */

            $insert_sql = "INSERT INTO password_resets
                           (customer_id, email, otp, token, expires_at, created_at)
                           VALUES (?, ?, ?, ?, ?, ?)";

            $insert_stmt = $conn->prepare($insert_sql);

            $insert_stmt->bind_param(
                "isssss",
                $customer_id,
                $email,
                $otp,
                $token,
                $expires_at,
                $created_at
            );

            if ($insert_stmt->execute()) {

    /* ==========================================
       CREATE RESET LINK
    ========================================== */

    $reset_link =
        "http://localhost/Demo/reset_password.php?token="
        . urlencode($token);


    /* ==========================================
       SEND EMAIL USING PHPMailer
    ========================================== */

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = "smtp.gmail.com";

        $mail->SMTPAuth = true;

        $mail->Username = "ajajsjkska@gmail.com";

        $mail->Password = "wpvkdqekdahaiwcz";

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;


        /* ==========================================
           SENDER
        ========================================== */

        $mail->setFrom(
            "ajajsjkska@gmail.com",
            "Movie Booking System"
        );


        /* ==========================================
           RECEIVER
        ========================================== */

        $mail->addAddress(
            $email,
            $customer["name"]
        );


        /* ==========================================
           EMAIL
        ========================================== */

        $mail->isHTML(true);

        $mail->Subject =
            "Movie Booking System - Password Reset";


        $mail->Body = "

            <div style='
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: auto;
                padding: 30px;
                border: 1px solid #ddd;
                border-radius: 10px;
            '>

                <h2 style='text-align:center;'>
                    Movie Booking System
                </h2>

                <h3>
                    Password Reset Request
                </h3>

                <p>
                    Hello " .
                    htmlspecialchars($customer["name"]) .
                    ",
                </p>

                <p>
                    We received a request to reset your
                    Movie Booking System password.
                </p>

                <p>
                    Your OTP is:
                </p>

                <div style='
                    font-size: 28px;
                    font-weight: bold;
                    letter-spacing: 6px;
                    text-align: center;
                    padding: 15px;
                    background: #f3f3f3;
                    border-radius: 8px;
                '>
                    " . htmlspecialchars($otp) . "
                </div>

                <p>
                    Click the button below to reset
                    your password:
                </p>

                <p style='text-align:center;'>

                    <a href='" .
                        htmlspecialchars($reset_link) .
                        "'
                       style='
                           display:inline-block;
                           padding:12px 25px;
                           background:#e50914;
                           color:white;
                           text-decoration:none;
                           border-radius:6px;
                           font-weight:bold;
                       '>

                        Reset Password

                    </a>

                </p>

                <p>
                    This OTP and reset link will expire
                    in <strong>10 minutes</strong>.
                </p>

                <p>
                    If you did not request a password
                    reset, you can safely ignore this email.
                </p>

                <hr>

                <p style='font-size:12px;color:#777;'>
                    Movie Booking System
                </p>

            </div>

        ";


        /* ==========================================
           PLAIN TEXT VERSION
        ========================================== */

        $mail->AltBody =
            "Movie Booking System\n\n" .
            "Your password reset OTP is: " .
            $otp . "\n\n" .
            "Reset your password using this link:\n" .
            $reset_link . "\n\n" .
            "This link expires in 10 minutes.";


        /* ==========================================
           SEND EMAIL
        ========================================== */

        $mail->send();

        $success =
            "A password reset link has been sent to your email.";


    } catch (Exception $e) {

        $error = "Unable to send the reset email. Please try again.";

    }

} else {

    $error =
        "Something went wrong. Please try again.";

}

$insert_stmt->close();

        } else {

            /*
             * We don't reveal whether an email exists.
             * This is safer than saying "email not found".
             */

            $success =
                "If an account exists with that email, " .
                "a password reset link will be sent.";

        }

        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Forgot Password</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="login.css">

    <style>

        .login-message {

            display: flex;
            align-items: center;
            gap: 10px;

            width: 100%;

            padding: 12px 15px;

            margin: 15px 0;

            border-radius: 10px;

            font-size: 14px;
            font-weight: 600;

            text-align: left;
        }

        .login-error {

            background: rgba(239, 68, 68, 0.15);

            border: 1px solid #ef4444;

            color: #fca5a5;
        }

        .login-success {

            background: rgba(34, 197, 94, 0.15);

            border: 1px solid #22c55e;

            color: #86efac;
        }

        .login-message i {
            font-size: 16px;
        }

        .back-login {

            margin-top: 15px;

            text-align: center;

            font-size: 14px;
        }

        .back-login a {

            color: inherit;

            text-decoration: none;

            font-weight: 600;
        }

        .back-login a:hover {

            text-decoration: underline;
        }

        .reset-link-test {

            margin-top: 15px;

            padding: 10px;

            background: rgba(255,255,255,0.05);

            border-radius: 8px;

            word-break: break-all;

            font-size: 12px;

        }

    </style>

</head>

<body>

<?php include "navbar.php"; ?>

<section class="login-section">

    <div class="submit-box">

        <i class="fa-solid fa-key ticket-icon"></i>

        <h2>Forgot Password?</h2>

        <p class="subtitle">
            Enter your registered email address
            to reset your password.
        </p>

        <?php if (!empty($error)): ?>

            <div class="login-message login-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?php echo htmlspecialchars($error); ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>

            <div class="login-message login-success">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?php echo htmlspecialchars($success); ?>
                </span>

            </div>

        <?php endif; ?>


        <form
            action="forgot_password.php"
            method="post">

            <input
                type="email"
                name="email"
                placeholder="Enter your Email"
                required>

            <button
                type="submit"
                class="submit-btn">

                Send Reset Link

            </button>

        </form>


        <p class="back-login">

            <a href="login.php">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Login

            </a>

        </p>

    </div>

</section>

</body>

</html>