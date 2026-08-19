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

        <div class="toolbar">

            <div class="search-box">

                <input
                    type="text"
                    placeholder="Search movie..."
                >

                <button>
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

            </div>


            <a href="addMovie.php" class="add-btn">

                <i class="fa-solid fa-plus"></i>

                Add Movie

            </a>

        </div>


        <!-- ================= MOVIE TABLE ================= -->

        <table>

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

    </main>

</div>

</body>
</html>