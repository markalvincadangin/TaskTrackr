<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\header.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Provide a fallback value for the username if not logged in
$username = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : "Guest";

// Fetch the profile picture if the user is logged in
$profile_picture = "/TaskTrackr/assets/images/default-profile.png"; // Default profile picture
if (isset($_SESSION['user_id'])) {
    include_once('../config/db.php');
    $user_id = $_SESSION['user_id'];
    $query = "SELECT profile_picture FROM Users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!empty($user['profile_picture'])) {
        $profile_picture = htmlspecialchars($user['profile_picture']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TaskTrackr</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="/TaskTrackr/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
    <!-- Top Header -->
    <header class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/TaskTrackr/public/dashboard.php">TaskTrackr</a>
            <div class="d-flex align-items-center ms-auto">
                <?php if ($current_page !== 'login.php' && $current_page !== 'register.php'): ?>
                    <!-- Notifications Icon -->
                    <a href="/TaskTrackr/public/notifications.php" class="btn btn-light position-relative me-3">
                        <i class="bi bi-bell"></i>
                        <?php
                        include_once('../config/db.php');
                        if (isset($_SESSION['user_id'])) {
                            $user_id = $_SESSION['user_id'];
                            $notifications_query = "SELECT COUNT(*) AS unread_count FROM Notifications WHERE user_id = ? AND is_read = 0";
                            $stmt = $conn->prepare($notifications_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $unread_count = $result->fetch_assoc()['unread_count'] ?? 0;
                        }
                        ?>
                        <?php if (!empty($unread_count) && $unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= $profile_picture ?>" alt="Profile" class="rounded-circle me-2" style="width: 30px; height: 30px;">
                            <span><?php echo $username; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="/TaskTrackr/public/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="/TaskTrackr/public/settings.php">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/TaskTrackr/actions/logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
</body>
</html>