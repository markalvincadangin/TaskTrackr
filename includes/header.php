<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TaskTrackr</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/TaskTrackr/assets/css/style.css"> <!-- adjust path if needed -->
</head>
<body>
    <header>
        <h1>TaskTrackr</h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <nav>
                <ul class="navbar">
                    <li><a href="/TaskTrackr/public/dashboard.php">Dashboard</a></li>
                    <li><a href="/TaskTrackr/public/projects.php">Projects</a></li>
                    <li><a href="/TaskTrackr/public/tasks.php">Tasks</a></li>
                    <li><a href="/TaskTrackr/public/groups.php">Groups</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="/TaskTrackr/public/categories.php">Categories</a></li>
                        <li><a href="/TaskTrackr/public/manage_users.php">Users</a></li>
                    <?php endif; ?>
                    <li><a href="/TaskTrackr/public/notifications.php">Notifications</a></li>
                    <li><a href="/TaskTrackr/public/profile.php">Profile</a></li>
                    <li><a href="/TaskTrackr/actions/logout.php">Logout</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <hr>
    </header>
        <div class="container">
        <?php include('../includes/alerts.php'); ?>
        </div>
    <main>
