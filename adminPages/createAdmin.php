<?php

include "../db.php";

$name = "Suhana Shakya";
$email = "suhana@himovie.com";
$username = "suhana";
$password = password_hash("suhana123", PASSWORD_DEFAULT);

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