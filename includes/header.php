<!-- filepath: c:\xampp\htdocs\TaskTrackr\includes\header.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Provide a fallback value for the username if not logged in
$username = isset($_SESSION['firstname'], $_SESSION['lastname']) ? htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) : "Guest";

// Fetch the profile picture if the user is logged in
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

$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <title>TaskTrackr</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($theme === 'dark'): ?>
        <link id="theme-css" rel="stylesheet" href="/TaskTrackr/assets/css/dark-theme.css">
    <?php else: ?>
        <link id="theme-css" rel="stylesheet" href="/TaskTrackr/assets/css/light-theme.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<?php if ($current_page !== 'login.php' && $current_page !== 'register.php'): ?>
<body>
    <!-- Full-width Header -->
    <header class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top py-2" style="height:64px; z-index:1040;">
        <div class="container-fluid px-4">
            <!-- Logo and Brand -->
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center me-4" href="/TaskTrackr/public/dashboard.php" style="height:48px;">
                <span class="d-none d-sm-inline">TaskTrackr</span>
            </a>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <!-- Notifications Icon -->
                <a href="/TaskTrackr/public/notifications.php" class="btn btn-light position-relative d-flex align-items-center justify-content-center p-2 me-2" aria-label="Notifications" style="width:40px; height:40px;">
                    <i class="bi bi-bell fs-5"></i>
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
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center px-2 py-1" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profile menu">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?= $profile_picture ?>" alt="Profile picture of <?= $username ?>" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover;">
                        <?php else: ?>
                            <span class="d-inline-flex justify-content-center align-items-center rounded-circle bg-secondary me-2" style="width:36px; height:36px;">
                                <i class="bi bi-person-circle text-white fs-4"></i>
                            </span>
                        <?php endif; ?>
                        <span class="fw-semibold d-none d-md-inline"><?= $username ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm animate__animated animate__fadeIn" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="/TaskTrackr/public/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="/TaskTrackr/public/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/TaskTrackr/actions/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    <div style="height:64px;"></div> <!-- Spacer for fixed header -->
<?php endif; ?>
</html>
