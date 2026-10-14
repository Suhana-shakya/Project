<?php

session_start();

include "db.php";

$error = "";
$success = "";


/* ==========================================
   CHECK TOKEN
========================================== */

$token = "";

if (!isset($_GET["token"]) || empty($_GET["token"])) {

    $error = "Invalid or missing password reset link.";

} else {

    $token = trim($_GET["token"]);

}

/* ==========================================
   FIND RESET REQUEST
========================================== */

$reset = null;

if (empty($error)) {

    $sql = "SELECT reset_id, customer_id, email, otp, expires_at, used
            FROM password_resets
            WHERE token = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $token);

    $stmt->execute();

    $result = $stmt->get_result();


    /* ==========================================
       CHECK TOKEN EXISTS
    ========================================== */

    if ($result->num_rows != 1) {

        $error = "Invalid password reset link.";

    } else {

        $reset = $result->fetch_assoc();

    }

    $stmt->close();


    /* ==========================================
       CHECK IF ALREADY USED
    ========================================== */

    if ($reset !== null && $reset["used"] == 1) {

        $error = "This password reset link has already been used.";

    }


    /* ==========================================
       CHECK EXPIRY
    ========================================== */

    if (
        $reset !== null &&
        strtotime($reset["expires_at"]) < time()
    ) {

        $error =
            "This password reset link has expired. Please request a new one.";

    }

}


/* ==========================================
   PROCESS NEW PASSWORD
========================================== */

if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    $reset !== null &&
    empty($error)
) {
    $otp = trim($_POST["otp"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate OTP
    if (!preg_match('/^[0-9]{6}$/', $otp) ||
        !hash_equals((string)$reset["otp"], $otp)) {

        $error = "Invalid OTP.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters long.";

    } elseif (strlen($password) > 128) {

        $error = "Password must not exceed 128 characters.";

    } elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {

        $error = "Password must contain an uppercase letter, a lowercase letter, a number, and a special character.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Update password and use the reset request
        // within one database transaction.
        $conn->begin_transaction();

        try {
            $used_sql = "
                UPDATE password_resets
                SET used = 1
                WHERE reset_id = ?
                  AND customer_id = ?
                  AND otp = ?
                  AND used = 0
                  AND expires_at >= NOW()
            ";

            $used_stmt = $conn->prepare($used_sql);

            $used_stmt->bind_param(
                "iis",
                $reset["reset_id"],
                $reset["customer_id"],
                $otp
            );

            $used_stmt->execute();

            if ($used_stmt->affected_rows !== 1) {
                throw new Exception(
                    "This reset request is invalid, expired, or already used."
                );
            }

            $used_stmt->close();

            $update_sql = "
                UPDATE customer
                SET password = ?
                WHERE customer_id = ?
            ";

            $update_stmt = $conn->prepare($update_sql);

            $update_stmt->bind_param(
                "si",
                $hashed_password,
                $reset["customer_id"]
            );

            $update_stmt->execute();
            $update_stmt->close();

            $conn->commit();

            $success = "Your password has been reset successfully.";

        } catch (Throwable $e) {

            $conn->rollback();

            $error = "Unable to reset your password. Please request a new reset link and try again.";
        }
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

    <title>Reset Password</title>

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


        .password-note {

            margin-top: 8px;

            margin-bottom: 15px;

            font-size: 12px;

            color: #aaa;

            text-align: left;

        }


        .back-login {

            margin-top: 15px;

            text-align: center;

            font-size: 14px;

        }


        .back-login a {

            text-decoration: none;

            font-weight: 600;

        }


        .back-login a:hover {

            text-decoration: underline;

        }

    </style>

</head>


<body>

<?php include "navbar.php"; ?>


<section class="login-section">

    <div class="submit-box">

        <i class="fa-solid fa-lock ticket-icon"></i>


        <h2>Reset Password</h2>


        <p class="subtitle">

            Enter the OTP from your email
            and create a new password.

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


            <p class="back-login">

                <a href="login.php">

                    <i class="fa-solid fa-arrow-left"></i>

                    Go to Login

                </a>

            </p>


        <?php elseif (empty($error)): ?>


            <form
                action="reset_password.php?token=<?php echo urlencode($token); ?>"
                method="post">


                <input
                    type="text"
                    name="otp"
                    placeholder="Enter 6-digit OTP"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required>


                <input
                    type="password"
                    name="password"
                    placeholder="Enter New Password"
                    minlength="8"
                    maxlength="128"
                    autocomplete="new-password"
                    required>


                <p class="password-note">
                    Use 8–128 characters, including uppercase and lowercase
                    letters, a number, and a special character.
                </p>


                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm New Password"
                    minlength="8"
                    maxlength="128"
                    autocomplete="new-password"
                    required>


                <button
                    type="submit"
                    class="submit-btn">

                    Reset Password

                </button>


            </form>


            <p class="back-login">

                <a href="login.php">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Login

                </a>

            </p>


        <?php endif; ?>

    </div>

</section>


</body>

</html>