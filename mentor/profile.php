<?php
require_once '../config/auth.php';
require_role('mentor');

$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
?>

<?php include 'sidebar.php'; ?>

<div class="main">

<h2>My Profile</h2>

<p><b>Name:</b> <?php echo htmlspecialchars($username); ?></p>

<p><b>Email:</b> <?php echo htmlspecialchars($email); ?></p>

</div>