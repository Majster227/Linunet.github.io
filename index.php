<?php
require_once 'config.php';
require_once 'auth.php';
if (isLoggedIn()) { header('Location: home.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = loginUser(trim($_POST['login'] ?? ''), $_POST['password'] ?? '');
    if ($r === true) { header('Location: home.php'); exit; }
    $error = $r;
}
$db = getDB();
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= SITE_NAME ?> – Connect</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
<div class="login-page-inner">
  <div class="login-hero">
    <div class="login-logo-big">◉</div>
    <h1><?= SITE_NAME ?></h1>
    <p class="login-tagline"><?= SITE_TAGLINE ?></p>
    <div class="login-stats">
      <span><strong><?= number_format($userCount) ?></strong> members</span>
    </div>
    <ul class="login-features">
      <li>◈ Share posts, images &amp; links</li>
      <li>◈ Connect with friends</li>
      <li>◈ Private messages</li>
      <li>◈ Control your privacy</li>
    </ul>
  </div>
  <div class="login-forms">
    <div class="login-box">
      <h2>Log In</h2>
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="POST">
        <label>Username or Email</label>
        <input type="text" name="login" required autocomplete="username">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password">
        <button type="submit">Log In →</button>
      </form>
    </div>
    <div class="login-register-cta">
      <p>New to <?= SITE_NAME ?>?</p>
      <a href="register.php" class="btn-register">Create an Account</a>
    </div>
  </div>
</div>
<footer class="login-footer">
  <a href="about.php">About</a> · <a href="contact.php">Contact</a> · &copy; <?= date('Y') ?> <?= SITE_NAME ?>
</footer>
</body>
</html>
