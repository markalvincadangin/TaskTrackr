<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\alerts.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Ensure the session is started if it hasn't been
}

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' 
        . htmlspecialchars($_SESSION['success_message']) . 
        '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' 
        . htmlspecialchars($_SESSION['error_message']) . 
        '</div>';
    unset($_SESSION['error_message']);
}
?>
