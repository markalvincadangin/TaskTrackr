<?php include('../includes/header.php'); ?>
<h2>Register</h2>
<form action="../actions/register_action.php" method="POST">
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="../public/login.php">Login</a></p>

<?php include('../includes/footer.php'); ?>
