<?php
include "db.php";

if (isset($_GET["username"])) {
    $username = trim($_GET["username"]);

    if ($username === "") {
        exit();
    }

    $stmt = $conn->prepare(
        "SELECT customer_id FROM customer WHERE username = ?"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    echo ($result->num_rows > 0) ? "taken" : "available";

    $stmt->close();
}

$conn->close();
?>