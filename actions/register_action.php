<?php
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $query = "INSERT INTO Users (name, email, password) VALUES (?, ?, ?)";
    $stmt  = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            header("Location: ../public/login.php?success=1");
            exit();
        } else {
            echo "Execution failed: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Statement preparation failed: " . $conn->error;
    }

    $conn->close();
}
?>
