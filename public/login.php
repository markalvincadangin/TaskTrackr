<?php include '../includes/header.php'; ?>

<h2>Login</h2>

<form action="../actions/login_action.php" method="POST">
    <input type="email" name="email" placeholder="Email" required =""><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form> 

<p>Don't have an account? <a href="../public/register.php">Register</a></p>

<?php include '../includes/footer.php'; ?>
