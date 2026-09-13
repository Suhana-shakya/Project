<?php
$current_page = basename($_SERVER["PHP_SELF"]);
?>
<!-- ================= SIDEBAR ================= -->

<aside class="sidebar">

    <h2>HiMovie</h2>

    <ul>

        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
			<a href="dashboard.php">
				<i class="fa-solid fa-house"></i>
				Dashboard
			</a>
		</li>

        <li class="<?php echo ($current_page == 'manageMovies.php') ? 'active' : ''; ?>">
			<a href="manageMovies.php">
				<i class="fa-solid fa-film"></i>
				Manage Movies
			</a>
		</li>

		<li class="<?php echo ($current_page == 'manageShowtimes.php') ? 'active' : ''; ?>">
			<a href="manageShowtimes.php">
				<i class="fa-solid fa-clock"></i>
				Showtimes
			</a>
		</li>

		<li class="<?php echo ($current_page == 'manageSeats.php') ? 'active' : ''; ?>">
			<a href="manageSeats.php">
				<i class="fa-solid fa-chair"></i>
				Manage Seats
			</a>
		</li>

		<li class="<?php echo ($current_page == 'manageUsers.php') ? 'active' : ''; ?>">
			<a href="manageUsers.php">
				<i class="fa-solid fa-users"></i>
				Manage Users
			</a>
		</li>

		<li class="<?php echo ($current_page == 'manageTicketStatus.php') ? 'active' : ''; ?>">
			<a href="manageTicketStatus.php">
				<i class="fa-solid fa-ticket"></i>
				Manage Ticket Status
			</a>
		</li>

		<li class="<?php echo ($current_page == 'manageRefunds.php') ? 'active' : ''; ?>">
			<a href="manageRefunds.php">
				<i class="fa-solid fa-money-bill-transfer"></i>
				Manage Refunds
			</a>
		</li>

		<li>
			<a href="adminLogout.php">
				<i class="fa-solid fa-right-from-bracket"></i>
				Logout
			</a>
		</li>

    </ul>

</aside>
