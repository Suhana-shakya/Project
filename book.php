<?php
session_start();
?>

<form action="confirm.php" method="post">

<input type="hidden"
name="show_id"
value="<?php echo $_GET['show']; ?>">

Seat Number:
<input type="text" name="seat">

<input type="submit"
value="Book">

</form>