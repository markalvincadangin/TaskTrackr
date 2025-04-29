<?php include '../includes/header.php'; ?>

<div class="container-fluid min-vh-100 d-flex justify-content-center align-items-center" style="background: #f8f9fa;">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <img src="/TaskTrackr/assets/images/logo.png" alt="TaskTrackr Logo" style="width:48px;height:48px;">
            <h2 class="mt-2 mb-0">Login</h2>
        </div>

        <?php include '../includes/alerts.php'; ?>

        <form action="../actions/login_action.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">Login</button>
        </form>

        <p class="text-center mt-3 mb-0 small">
            Don't have an account? <a href="../public/register.php">Register</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
