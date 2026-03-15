<?php
require_once 'config.php';
require_once 'auth.php';
if (isLoggedIn()) { header('Location: home.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = registerUser(trim($_POST['username']??''), trim($_POST['email']??''), $_POST['password']??'', trim($_POST['full_name']??''));
    if ($r === true) { header('Location: home.php'); exit; }
    $error = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register – <?= SITE_NAME ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
<div class="login-page-inner">
  <div class="login-hero">
    <div class="login-logo-big">◉</div>
    <h1><?= SITE_NAME ?></h1>
    <p class="login-tagline">Join the network. It's free.</p>
  </div>
  <div class="login-forms">
    <div class="login-box">
      <h2>Create Account</h2>
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="POST">
        <label>Full Name</label>
        <input type="text" name="full_name" required maxlength="60">
        <label>Username</label>
        <input type="text" name="username" required maxlength="30" placeholder="e.g. john.doe">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password (min. 6 chars)</label>
        <input type="password" name="password" required>
        <button type="submit">Create Account →</button>
      </form>
    </div>
    <div class="login-register-cta">
      <p>Already have an account?</p>
      <a href="index.php" class="btn-register">Log In</a>
    </div>
  </div>
</div>
</body>
</html>
