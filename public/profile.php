<!-- filepath: c:\xampp\htdocs\TaskTrackr\public\profile.php -->
<?php
session_start();
include('../config/db.php');
include('../includes/header.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /TaskTrackr/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT first_name, last_name, email, role, date_created, profile_picture FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div class="d-flex">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="container-fluid px-0">
            <h2 class="mb-4 fw-bold"><i class="bi bi-person-circle me-2"></i>My Profile</h2>

            <!-- Alerts -->
            <?php include('../includes/alerts.php'); ?>

            <div class="row">
                <!-- Profile Info -->
                <div class="col-md-4 mb-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" class="rounded-circle mb-3" width="120" height="120" alt="Profile Picture">
                            <?php else: ?>
                                <span class="d-inline-flex justify-content-center align-items-center rounded-circle bg-secondary mb-3" style="width:120px; height:120px;">
                                    <i class="bi bi-person-circle text-white" style="font-size: 3rem;"></i>
                                </span>                            
                            <?php endif; ?>
                            <h5 class="card-title"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($user['email']) ?></p>
                            <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                            <p class="text-muted mt-2">Joined: <?= date('F j, Y', strtotime($user['date_created'])) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="col-md-8">
                    <!-- Edit Profile -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <form action="../actions/update_profile.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="firstname" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                                 <div class="mb-3">
                                    <label for="lastname" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_img" class="form-label">Profile Picture</label>
                                    <input class="form-control" type="file" id="profile_img" name="profile_img">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="../actions/change_password.php" method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-arrow-repeat me-1"></i>Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Account Section -->
            <div class="row mt-4">
                <div class="col-md-8 offset-md-4">
                    <div class="card shadow-sm mb-4 border-danger">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 text-danger"><i class="bi bi-trash me-2"></i>Delete Account</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text mb-3">Warning: This action is <strong>irreversible</strong>. All your data will be permanently deleted.</p>
                            <form action="../actions/delete_account.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-trash"></i> Delete My Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>