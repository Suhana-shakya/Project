<?php
session_start();
include("db.php");

$user=$_SESSION['user_id'];
$show=$_POST['show_id'];
$seat=$_POST['seat'];

$stmt=$conn->prepare(
"INSERT INTO bookings(user_id,show_id,seats)
VALUES(?,?,?)"
);

$stmt->bind_param(
"iis",
$user,
$show,
$seat
);

if($stmt->execute()){

echo "<h2>Booking Successful</h2>";

echo "Seat: ".$seat;
}
else{
echo "Booking Failed";
}

?>