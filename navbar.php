<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "db.php";
?>
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


        <?php if (isset($_SESSION["customer_id"])): ?>
		
		<li class="notification-menu">

			<?php

			$customer_id = $_SESSION["customer_id"];

			$notification_sql = "SELECT COUNT(*) AS unread_count
								 FROM notification
								 WHERE customer_id = ?
								 AND is_read = 0";

			$notification_stmt = $conn->prepare($notification_sql);

			$notification_stmt->bind_param("i", $customer_id);

			$notification_stmt->execute();

			$notification_result = $notification_stmt->get_result();

			$notification_data = $notification_result->fetch_assoc();

			$unread_count = $notification_data["unread_count"];

			$notification_stmt->close();

			?>

			<a href="notifications.php" class="notification-btn">

				<i class="fa-solid fa-bell"></i>

				<?php if ($unread_count > 0): ?>

					<span class="notification-badge">
						<?php echo $unread_count; ?>
					</span>

				<?php endif; ?>

			</a>

		</li>

			<li class="profile-menu">

				<button class="profile-btn">

					<span class="profile-icon">
						<i class="fa-solid fa-user"></i>
					</span>

					<span class="profile-name">
						<?php echo htmlspecialchars($_SESSION["customer_username"]); ?>
					</span>

					<i class="fa-solid fa-chevron-down profile-arrow"></i>

				</button>

				<div class="profile-dropdown">

					<a href="profile.php">
						<i class="fa-solid fa-user"></i>
						<span>My Profile</span>
					</a>

					<a href="myBookings.php">
						<i class="fa-solid fa-ticket"></i>
						<span>My Bookings</span>
					</a>

					<div class="dropdown-divider"></div>

					<a href="logout.php" class="logout-link">
						<i class="fa-solid fa-right-from-bracket"></i>
						<span>Logout</span>
					</a>

				</div>

			</li>

			<?php else: ?>

				<li>
					<a href="login.php" class="login-btn">

						<i class="fa-solid fa-user"></i>
						Login

					</a>
				</li>

			<?php endif; ?>

    </ul>

</nav>