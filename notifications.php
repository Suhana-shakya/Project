
<?php

session_start();

include "db.php";


/* =====================================================
   CHECK LOGIN
===================================================== */

if (!isset($_SESSION["customer_id"])) {

    header("Location: login.php");
    exit();

}

$customer_id = (int) $_SESSION["customer_id"];


/* =====================================================
   GET CUSTOMER NOTIFICATIONS
===================================================== */

$sql = "SELECT
            n.notification_id,
            n.ticket_id,
            n.message,
            n.is_read,
            n.created_at,
            t.status
        FROM notification n
        LEFT JOIN ticket t
            ON n.ticket_id = t.ticket_id
        WHERE n.customer_id = ?
        ORDER BY n.created_at DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $customer_id);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   MARK NOTIFICATIONS AS READ
===================================================== */

$read_sql = "UPDATE notification
             SET is_read = 1
             WHERE customer_id = ?
             AND is_read = 0";

$read_stmt = $conn->prepare($read_sql);

$read_stmt->bind_param("i", $customer_id);

$read_stmt->execute();

$read_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Notifications - HiMovie</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet" href="navbar.css">

    <style>

      
/* ==============================
   RESET
============================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;

    background:
        radial-gradient(circle at top left, #4c1d95, transparent 40%),
        radial-gradient(circle at bottom right, #831843, transparent 40%),
        #111827;

    color: white;
    font-family: Arial, sans-serif;
}


/* ==============================
   NOTIFICATION SECTION
============================== */

.notification-section {
    width: 90%;
    max-width: 900px;

    margin: 120px auto 50px;
}

.notification-section h1 {
    font-size: 32px;
    margin-bottom: 30px;

    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-section h1 i {
    color: #c084fc;
}


/* ==============================
   NOTIFICATION CARD
============================== */

.notification-box {
    background: rgba(255, 255, 255, 0.08);

    border: 1px solid rgba(255, 255, 255, 0.12);

    border-radius: 16px;

    padding: 20px 22px;

    margin-bottom: 15px;

    backdrop-filter: blur(12px);

    transition: 0.3s ease;
}

.notification-box:hover {
    transform: translateY(-2px);

    background: rgba(255, 255, 255, 0.12);

    border-color: rgba(192, 132, 252, 0.4);
}


/* ==============================
   NOTIFICATION CONTENT
============================== */

.notification-content {
    display: flex;

    align-items: flex-start;

    gap: 16px;
}


/* ==============================
   NOTIFICATION ICON
============================== */

.notification-icon {
    width: 48px;
    height: 48px;

    min-width: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(168, 85, 247, 0.2);

    border-radius: 50%;
}

.notification-icon i {
    color: #c084fc;

    font-size: 19px;
}
/* ==============================
   CONFIRMED
============================== */

.notification-icon.confirmed {
    background: rgba(34, 197, 94, 0.18);
}

.notification-icon.confirmed i {
    color: #22c55e;
}


/* ==============================
   PENDING
============================== */

.notification-icon.pending {
    background: rgba(234, 179, 8, 0.18);
}

.notification-icon.pending i {
    color: #eab308;
}


/* ==============================
   CANCELLED
============================== */

.notification-icon.cancelled {
    background: rgba(239, 68, 68, 0.18);
}

.notification-icon.cancelled i {
    color: #ef4444;
}


/* ==============================
   MESSAGE
============================== */

.notification-message {
    font-size: 16px;

    line-height: 1.6;

    margin-bottom: 8px;

    color: #f5f5f5;
}


/* ==============================
   DATE / TIME
============================== */

.notification-date {
    font-size: 13px;

    color: #a1a1aa;

    display: flex;

    align-items: center;

    gap: 6px;
}


/* ==============================
   UNREAD NOTIFICATION
============================== */

.notification-box.unread {
    border-left: 4px solid #a855f7;

    background: rgba(168, 85, 247, 0.12);
}


/* ==============================
   NO NOTIFICATIONS
============================== */

.no-notifications {
    text-align: center;

    padding: 70px 20px;

    color: #a1a1aa;
}

.no-notifications:hover {
    transform: none;
}

.no-notifications i {
    font-size: 50px;

    margin-bottom: 18px;

    color: #71717a;
}

.no-notifications p {
    font-size: 17px;

    margin: 0;
}


/* ==============================
   MOBILE
============================== */

@media (max-width: 600px) {

    .notification-section {
        width: 94%;

        margin-top: 105px;
    }

    .notification-section h1 {
        font-size: 26px;
    }

    .notification-box {
        padding: 16px;
    }

    .notification-content {
        gap: 12px;
    }

    .notification-icon {
        width: 42px;
        height: 42px;

        min-width: 42px;
    }

    .notification-message {
        font-size: 14px;
    }

    .notification-date {
        font-size: 12px;
    }
}


    </style>

</head>


<body>


<?php include "navbar.php"; ?>


<section class="notification-section">

    <h1>
        <i class="fa-solid fa-bell"></i>
        Notifications
    </h1>


    <?php if ($result->num_rows > 0): ?>


        <?php while ($notification = $result->fetch_assoc()): ?>

            <div class="notification-box">

                <div class="notification-content">


                    <div class="notification-icon
						<?php echo strtolower($notification["status"]); ?>">

						<?php if ($notification["status"] === "Confirmed"): ?>

							<i class="fa-solid fa-circle-check"></i>

						<?php elseif ($notification["status"] === "Pending"): ?>

							<i class="fa-solid fa-clock"></i>

						<?php elseif ($notification["status"] === "Cancelled"): ?>

							<i class="fa-solid fa-circle-xmark"></i>

						<?php else: ?>

							<i class="fa-solid fa-bell"></i>

						<?php endif; ?>

					</div>


                    <div>

                        <div class="notification-message">

                            <?php
                            echo htmlspecialchars(
                                $notification["message"]
                            );
                            ?>

                        </div>


                        <div class="notification-date">

                            <i class="fa-regular fa-clock"></i>

                            <?php
                            echo htmlspecialchars(
                                $notification["created_at"]
                            );
                            ?>

                        </div>

                    </div>

                </div>

            </div>

        <?php endwhile; ?>


    <?php else: ?>


        <div class="notification-box no-notifications">

            <i class="fa-regular fa-bell-slash"></i>

            <p>
                You don't have any notifications yet.
            </p>

        </div>


    <?php endif; ?>


</section>


</body>

</html>

<?php

$stmt->close();

$conn->close();

?>
