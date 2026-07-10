<?php
include("db.php");

$result=$conn->query("SELECT * FROM movies");

echo "<h1>Movies</h1>";

while($row=$result->fetch_assoc()){

echo "
<h3>".$row['title']."</h3>
<p>".$row['genre']."</p>
<a href='shows.php?movie=".$row['movie_id']."'>
View Shows
</a>
<hr>";

}
?>