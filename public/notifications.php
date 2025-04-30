<?php
include('../config/db.php');
include('../includes/header.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the user
function fetchNotifications($conn, $user_id) {
    $query = "SELECT * FROM Notifications WHERE user_id = ? ORDER BY date_created DESC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Mark all notifications as read
function markAllNotificationsAsRead($conn, $user_id) {
    $query = "UPDATE Notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Mark a single notification as read
function markNotificationAsRead($conn, $notification_id) {
    $query = "UPDATE Notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
}

// Delete a single notification
function deleteNotification($conn, $notification_id) {
    $query = "DELETE FROM Notifications WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        markAllNotificationsAsRead($conn, $user_id);
        $_SESSION['success_message'] = "All notifications marked as read.";
        header("Location: notifications.php");
        exit();
    }

    if (isset($_POST['mark_read'])) {
        markNotificationAsRead($conn, $_POST['notification_id']);
        $_SESSION['success_message'] = "Notification marked as read.";
        header("Location: notifications.php");
        exit();
    }

    if (isset($_POST['delete_notification'])) {
        deleteNotification($conn, $_POST['notification_id']);
        $_SESSION['success_message'] = "Notification deleted.";
        header("Location: notifications.php");
        exit();
    }
}

// Fetch notifications
$notifications = fetchNotifications($conn, $user_id);
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">
            <h2 class="mb-4 fw-bold text-center"><i class="bi bi-bell me-2"></i>Notifications</h2>

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <p class="text-muted mb-2 mb-md-0">
                    <?php if ($notifications->num_rows > 0): ?>
                    You have <?= $notifications->num_rows ?> notification<?= $notifications->num_rows !== 1 ? 's' : '' ?>.
                    <?php endif; ?>
                </p>
                <?php if ($notifications->num_rows > 0): ?>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="mark_all_read" class="btn btn-sm btn-primary">
                            <i class="bi bi-check2-all me-1"></i> Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($notifications->num_rows === 0): ?>
                <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center text-muted">
                    <i class="bi bi-bell-slash" style="font-size: 2.5rem;"></i>
                    <p class="mt-3 mb-0">You have no notifications.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                        <div class="col-12">
                            <div class="card shadow-sm p-3 d-flex flex-row align-items-center justify-content-between
                                <?= !$notification['is_read'] ? 'border-primary bg-light' : 'border-0' ?>"
                                style="border-left: 5px solid <?= !$notification['is_read'] ? '#0d6efd' : '#dee2e6' ?>;">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary me-2">Unread</span>
                                        <?php endif; ?>
                                        <span class="<?= !$notification['is_read'] ? 'fw-bold' : 'text-muted' ?>">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('F j, Y, g:i a', strtotime($notification['date_created'])) ?>
                                    </small>
                                </div>
                                <div class="ms-3 d-flex flex-row gap-2">
                                    <?php if (!$notification['is_read']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                            <button type="submit" name="mark_read" class="btn btn-outline-success btn-sm" title="Mark as Read">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                        <button type="submit" name="delete_notification" class="btn btn-outline-danger btn-sm" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>