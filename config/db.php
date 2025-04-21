<?php
$host = "localhost";
$user = "root";
$password = ""; // Default XAMPP password is blank
$database = "tasktrackr";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} /* else {
    echo "✅ Database connected successfully!";
}
?> */
