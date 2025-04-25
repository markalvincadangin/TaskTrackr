<?php include '../includes/header.php'; ?>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
        <h2 class="text-center mb-4">Login</h2>

        <!-- Display Alerts (if any) -->
        <?php include '../includes/alerts.php'; ?>

        <form action="../actions/login_action.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <p class="text-center mt-3">
            Don't have an account? <a href="../public/register.php">Register</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
