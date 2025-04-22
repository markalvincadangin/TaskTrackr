<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Ensure the session is started if it hasn't been
}

if (isset($_SESSION['success_message'])) {
    echo '<div style="background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin-bottom: 15px;">' 
        . $_SESSION['success_message'] . 
        '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 15px;">' 
        . $_SESSION['error_message'] . 
        '</div>';
    unset($_SESSION['error_message']);
}
?>
