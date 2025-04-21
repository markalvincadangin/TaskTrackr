<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
<p>You are now logged in as a <?php echo $_SESSION['role']; ?>.</p>


<?php include '../includes/footer.php'; ?>
