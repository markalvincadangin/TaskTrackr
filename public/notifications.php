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
    $query = "SELECT * FROM Notifications WHERE user_id = ? ORDER BY timestamp DESC";
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
    <div class="main-content flex-grow-1 p-4">
        <h2 class="mb-4 text-center">Notifications</h2>

        <!-- Display Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
                You have <?= $notifications->num_rows ?> notification<?= $notifications->num_rows !== 1 ? 's' : '' ?>.
            </p>
            <?php if ($notifications->num_rows > 0): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-primary">Mark All as Read</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($notifications->num_rows === 0): ?>
            <p class="text-muted text-center">You have no notifications.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start <?= $notification['is_read'] ? '' : 'list-group-item-warning' ?>">
                        <div>
                            <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                            <small class="text-muted"><?= date('F j, Y, g:i a', strtotime($notification['date_created'])) ?></small>
                        </div>
                        <div class="btn-group">
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-success">Mark as Read</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                <button type="submit" name="delete_notification" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>