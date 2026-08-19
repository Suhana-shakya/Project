<?php

include "../db.php";

$name = "Sarina Gole";
$email = "sarina@himovie.com";
$username = "sarina";
$password = password_hash("sarina123", PASSWORD_DEFAULT);

$sql = "INSERT INTO admin (name, email, username, password)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssss",
    $name,
    $email,
    $username,
    $password
);

if ($stmt->execute()) {
    echo "Admin account created successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>