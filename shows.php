<?php
include("db.php");

$movie=$_GET['movie'];

$sql="
SELECT
shows.show_id,
movies.title,
cinemas.cinema_name,
shows.show_date,
shows.show_time,
shows.price
FROM shows
JOIN movies ON shows.movie_id=movies.movie_id
JOIN halls ON shows.hall_id=halls.hall_id
JOIN cinemas ON halls.cinema_id=cinemas.cinema_id
WHERE movies.movie_id=$movie
";

$result=$conn->query($sql);

while($row=$result->fetch_assoc()){

echo "
Movie: ".$row['title']."<br>
Cinema: ".$row['cinema_name']."<br>
Date: ".$row['show_date']."<br>
Time: ".$row['show_time']."<br>
Price: ".$row['price']."<br>

<a href='book.php?show=".$row['show_id']."'>
Book Ticket
</a>
<hr>";

}
?>