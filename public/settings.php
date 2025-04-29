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

// Fetch user settings (now only fetch reminder_days_before)
$query = "SELECT reminder_days_before FROM User_Settings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

$reminder_days_before = $settings['reminder_days_before'] ?? 1;
?>

<div class="d-flex">
    <!-- Sidebar -->
    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4"><i class="bi bi-gear me-2"></i>Settings</h2>

        <!-- Alerts -->
        <?php include('../includes/alerts.php'); ?>

        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex align-items-center">
                        <i class="bi bi-bell me-2"></i>
                        <h5 class="mb-0">Task Reminder</h5>
                    </div>
                    <div class="card-body">
                        <form action="../actions/update_settings.php" method="POST">
                            <div class="mb-4">
                                <label for="reminder_days_before" class="form-label fw-semibold">
                                    Remind me about tasks this many days before the due date:
                                </label>
                                <input type="number" min="1" max="30" class="form-control w-50" id="reminder_days_before" name="reminder_days_before" value="<?= htmlspecialchars($reminder_days_before) ?>" required>
                                <div class="form-text">You will receive reminders this many days before a task is due.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>