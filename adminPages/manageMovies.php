
<?php

include "adminAuth.php";
include "../db.php";


/* ================= UPDATE MOVIE STATUS ================= */

/*
 * Automatically update movie status based on release date.
 *
 * Future release date  = Upcoming
 * Today/past date      = Now Showing
 */

$update_sql = "UPDATE movie
            SET status = CASE
                WHEN release_date > CURDATE()
                    THEN 'Upcoming'

                WHEN release_date <= CURDATE()
                    AND release_date > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                    THEN 'Now Showing'

                ELSE 'Archived'
            END";

$conn->query($update_sql);


/* ================= GET MOVIES ================= */

$sql = "SELECT 
            movie_id,
            title,
            genre,
            duration,
            release_date,
            language,
            status
        FROM movie
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


        <!-- ================= SUCCESS MESSAGE ================= -->

        <?php if (isset($_SESSION["success"])): ?>

            <div class="success-message">

                <i class="fa-solid fa-circle-check"></i>

                <?php

                echo htmlspecialchars($_SESSION["success"]);

                unset($_SESSION["success"]);

                ?>

            </div>

        <?php endif; ?>


        <!-- ================= ERROR MESSAGE ================= -->

        <?php if (isset($_SESSION["error"])): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php

                echo htmlspecialchars($_SESSION["error"]);

                unset($_SESSION["error"]);

                ?>

            </div>

        <?php endif; ?>


        <!-- ================= DELETE SUCCESS MESSAGE ================= -->

        <?php if (isset($_SESSION["delete_success"])): ?>

            <div class="delete-success-message">

                <i class="fa-solid fa-trash"></i>

                <?php

                echo htmlspecialchars($_SESSION["delete_success"]);

                unset($_SESSION["delete_success"]);

                ?>

            </div>

        <?php endif; ?>


        <!-- ================= TOOLBAR ================= -->

        <div class="toolbar">

            <a href="addMovie.php" class="add-btn">

                <i class="fa-solid fa-plus"></i>

                Add Movie

            </a>

        </div>


        <!-- ================= MOVIE TABLE ================= -->

        <div class="table-box">

            <table class="movie-table">

                <thead>

                    <tr>

                        <th>Movie ID</th>

                        <th>Movie Name</th>

                        <th>Genre</th>

                        <th>Duration</th>

                        <th>Release Date</th>

                        <th>Language</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($result->num_rows > 0): ?>


                    <?php while ($movie = $result->fetch_assoc()): ?>


                        <tr>


                            <!-- ================= MOVIE ID ================= -->

                            <td>

                                <?php echo $movie["movie_id"]; ?>

                            </td>


                            <!-- ================= MOVIE NAME ================= -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $movie["title"]
                                );

                                ?>

                            </td>


                            <!-- ================= GENRE ================= -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $movie["genre"]
                                );

                                ?>

                            </td>


                            <!-- ================= DURATION ================= -->

                            <td>

                                <?php

                                echo $movie["duration"];

                                ?>

                                min

                            </td>


                            <!-- ================= RELEASE DATE ================= -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime($movie["release_date"])
                                );

                                ?>

                            </td>


                            <!-- ================= LANGUAGE ================= -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $movie["language"] ?? "N/A"
                                );

                                ?>

                            </td>


                            <!-- ================= STATUS ================= -->

                            <td>


                                <?php if ($movie["status"] == "Now Showing"): ?>

                                    <span class="active-user">
                                        Now Showing
                                    </span>

                                <?php elseif ($movie["status"] == "Upcoming"): ?>

                                    <span class="inactive-user">
                                        Upcoming
                                    </span>

                                <?php else: ?>

                                    <span class="archived-user">
                                        Archived
                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- ================= ACTION ================= -->

                            <td>

                                <div class="actions">


                                    <!-- EDIT -->

                                    <a
                                        href="editMovie.php?id=<?php echo $movie["movie_id"]; ?>"
                                        class="edit"
                                    >

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <!-- DELETE -->

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

                        <td colspan="8">

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
