<?php include('../includes/header.php'); ?>

<div class="container-fluid min-vh-100 d-flex justify-content-center align-items-center" style="background: #f8f9fa;">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <h2 class="mt-2 mb-0 fw-bold">Register</h2>
            <p class="text-muted small mb-0">Create your TaskTrackr account</p>
        </div>

        <?php include('../includes/alerts.php'); ?>

        <form action="../actions/register_action.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="first_name" class="form-label fw-semibold">First Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Enter your first name" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label fw-semibold">Last Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Enter your last name" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2 d-flex align-items-center justify-content-center">
                <i class="bi bi-person-plus me-2"></i> Register
            </button>
        </form>

        <p class="text-center mt-3 mb-0 small text-muted">
            Already have an account? <a href="../public/login.php" class="fw-semibold">Login</a>
        </p>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
