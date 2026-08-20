
<?php

include "adminAuth.php";
include "../db.php";

$sql = "SELECT 
            movie_id,
            title,
            genre,
            duration,
            release_date,
            language,
            status
        FROM Movie
        ORDER BY movie_id DESC";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Movies</title>

<link rel="stylesheet" href="manageMovies.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>

<div class="container">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <h2>HiMovie</h2>

        <ul>

            <li>
                <a href="dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li class="active">
                <a href="manageMovies.php">
                    <i class="fa-solid fa-film"></i>
                    Manage Movies
                </a>
            </li>

            <li>
                <a href="manageShowtimes.php">
                    <i class="fa-solid fa-clock"></i>
                    Showtimes
                </a>
            </li>

            <li>
                <a href="manageSeats.php">
                    <i class="fa-solid fa-chair"></i>
                    Manage Seats
                </a>
            </li>

            <li>
                <a href="manageUsers.php">
                    <i class="fa-solid fa-users"></i>
                    Manage Users
                </a>
            </li>

            <li>
                <a href="manageTicketStatus.php">
                    <i class="fa-solid fa-ticket"></i>
                    Manage Ticket Status
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


    <!-- ================= MAIN ================= -->

    <main class="main">

        <h1>Manage Movies</h1>

        <?php if (isset($_SESSION["success"])): ?>

            <div class="success-message">

                <i class="fa-solid fa-circle-check"></i>

                <?php
                echo htmlspecialchars($_SESSION["success"]);
                unset($_SESSION["success"]);
                ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_SESSION["error"])): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php
                echo htmlspecialchars($_SESSION["error"]);
                unset($_SESSION["error"]);
                ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_SESSION["delete_success"])): ?>

            <div class="delete-success-message">

                <i class="fa-solid fa-trash"></i>

                <?php
                echo htmlspecialchars($_SESSION["delete_success"]);
                unset($_SESSION["delete_success"]);
                ?>

            </div>

        <?php endif; ?>


        <div class="toolbar">

            <a href="addMovie.php" class="add-btn">

                <i class="fa-solid fa-plus"></i>

                Add Movie

            </a>

        </div>


        <!-- ================= MOVIE TABLE ================= -->

        <div class="table-box">

        <table>

            <thead>

                <tr>

                    <th>Movie ID</th>
                    <th>Movie Name</th>
                    <th>Genre</th>
                    <th>Duration</th>
                    <th>Release Date</th>
                    <th>Language</th>
                    <th>Release Status</th>
                    <th>Status</th>
                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

            <?php if ($result->num_rows > 0): ?>

                <?php while ($movie = $result->fetch_assoc()): ?>

                    <?php

                    /*
                     * Determine whether the movie is
                     * Upcoming or Now Showing.
                     */

                    $today = date("Y-m-d");

                    if ($movie["release_date"] > $today) {

                        $release_status = "Upcoming";

                    } else {

                        $release_status = "Now Showing";

                    }

                    ?>

                    <tr>

                        <td>
                            <?php echo $movie["movie_id"]; ?>
                        </td>


                        <td>
                            <?php echo htmlspecialchars($movie["title"]); ?>
                        </td>


                        <td>
                            <?php echo htmlspecialchars($movie["genre"]); ?>
                        </td>


                        <td>
                            <?php echo $movie["duration"]; ?> min
                        </td>


                        <td>
                            <?php

                            echo date(
                                "d M Y",
                                strtotime($movie["release_date"])
                            );

                            ?>
                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $movie["language"] ?? "N/A"
                            );

                            ?>

                        </td>


                        <!-- ================= RELEASE STATUS ================= -->

                        <td>

                            <?php if ($release_status == "Upcoming"): ?>

                                <span class="upcoming">

                                    Upcoming

                                </span>

                            <?php else: ?>

                                <span class="now-showing">

                                    Now Showing

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- ================= ACTIVE / INACTIVE ================= -->

                        <td>

                            <?php if ($movie["status"] == "Active"): ?>

                                <span class="active-user">

                                    Active

                                </span>

                            <?php else: ?>

                                <span class="inactive-user">

                                    Inactive

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- ================= ACTION ================= -->

                        <td>

                            <div class="actions">

                                <a
                                    href="editMovie.php?id=<?php echo $movie["movie_id"]; ?>"
                                    class="edit"
                                >

                                    <i class="fa-solid fa-pen"></i>

                                </a>


                                <a
                                    href="deleteMovie.php?id=<?php echo $movie["movie_id"]; ?>"
                                    class="delete"
                                    onclick="return confirm('Are you sure you want to delete this movie?');"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>


            <?php else: ?>

                <tr>

                    <td colspan="9">

                        No movies found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

        </div>

    </main>

</div>

</body>
</html>
