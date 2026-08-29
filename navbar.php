<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

        <li class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                placeholder="Search movies..."
            >

        </li>

        <?php if (isset($_SESSION["customer_id"])): ?>

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