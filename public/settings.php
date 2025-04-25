<!-- filepath: c:\xampp\htdocs\TaskTrackr\public\settings.php -->
<?php
session_start();
include('../config/db.php');
include('../includes/header.php'); // Top nav
include('../includes/sidebar.php'); // Sidebar nav

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user settings
$query = "SELECT task_alerts, deadline_reminders, email_notifications, dark_mode FROM User_Settings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// Set default values if no settings exist
$task_alerts = $settings['task_alerts'] ?? 1;
$deadline_reminders = $settings['deadline_reminders'] ?? 1;
$email_notifications = $settings['email_notifications'] ?? 0;
$dark_mode = $settings['dark_mode'] ?? 0;
?>

<div class="d-flex">
    <!-- Sidebar -->
    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4">⚙️ Settings</h2>

        <!-- Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <!-- Notification Preferences -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">🔔 Notification Preferences</h5>
            </div>
            <div class="card-body">
                <form action="../actions/update_notifications.php" method="POST">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="taskAlerts" name="task_alerts" <?= $task_alerts ? 'checked' : '' ?>>
                        <label class="form-check-label" for="taskAlerts">Task Assignment Alerts</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="deadlineReminders" name="deadline_reminders" <?= $deadline_reminders ? 'checked' : '' ?>>
                        <label class="form-check-label" for="deadlineReminders">Deadline Reminders</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" <?= $email_notifications ? 'checked' : '' ?>>
                        <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Preferences</button>
                </form>
            </div>
        </div>

        <!-- Appearance & Personalization -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">🎨 Appearance & Personalization</h5>
            </div>
            <div class="card-body">
                <form action="../actions/update_appearance.php" method="POST">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="darkMode" name="dark_mode" <?= $dark_mode ? 'checked' : '' ?>>
                        <label class="form-check-label" for="darkMode">Enable Dark Mode</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>