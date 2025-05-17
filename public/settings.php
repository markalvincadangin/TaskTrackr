<!-- filepath: c:\xampp\htdocs\TaskTrackr\public\settings.php -->
<?php
session_start();
include('../config/db.php');
include('../includes/header.php'); // Top nav

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user settings (fetch both reminder_days_before and theme)
$query = "SELECT reminder_days_before, theme FROM User_Settings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

$reminder_days_before = $settings['reminder_days_before'] ?? 1;
$theme = $settings['theme'] ?? 'light';
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>
    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">
            <h2 class="mb-4 fw-bold"><i class="bi bi-gear me-2"></i>Settings</h2>

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-10">
                    <form action="../actions/update_settings.php" method="POST">
                        <!-- Task Reminder Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light d-flex align-items-center">
                                <i class="bi bi-bell me-2"></i>
                                <h5 class="mb-0">Task Reminder</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="reminder_days_before" class="form-label fw-semibold">
                                        Remind me about tasks this many days before the due date:
                                    </label>
                                    <input type="number" min="1" max="30" class="form-control w-50" id="reminder_days_before" name="reminder_days_before" value="<?= htmlspecialchars($reminder_days_before) ?>" required>
                                    <div class="form-text">You will receive reminders this many days before a task is due.</div>
                                </div>
                            </div>
                        </div>
                        <!-- Theme Preference Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light d-flex align-items-center">
                                <i class="bi bi-palette me-2"></i>
                                <h5 class="mb-0">Theme Preference</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="theme" class="form-label fw-semibold">Choose your theme:</label>
                                    <select class="form-select w-50" id="theme" name="theme">
                                        <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Light</option>
                                        <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Dark</option>
                                        <!-- Add more themes here if needed -->
                                    </select>
                                    <div class="form-text">Change the appearance of TaskTrackr to your preference.</div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-4">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>