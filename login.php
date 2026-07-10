<?php
session_start();
include("db.php");

$email=$_POST['email'];
$password=$_POST['password'];

$stmt=$conn->prepare(
"SELECT * FROM users WHERE email=?"
);

$stmt->bind_param("s",$email);
$stmt->execute();

$result=$stmt->get_result();

if($row=$result->fetch_assoc()){

    if(password_verify($password,$row['password'])){

        $_SESSION['user_id']=$row['user_id'];
        $_SESSION['name']=$row['name'];

        echo "Login Successful";
    }
    else{
        echo "Wrong Password";
    }
}
else{
    echo "User Not Found";
}
?>