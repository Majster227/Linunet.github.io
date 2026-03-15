<?php
// Upewnij się że auth jest załadowany (na wypadek gdyby plik był includowany bez niego)
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
if (!isset($me)) {
    $me = currentUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' – ' : '' ?><?= SITE_NAME ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header id="topbar">
  <div class="topbar-inner">
    <a href="home.php" class="topbar-logo">
      <span class="logo-icon">◉</span> <?= SITE_NAME ?>
    </a>
    <?php if ($me): ?>
    <nav class="topbar-nav">
      <a href="home.php">Feed</a>
      <a href="search.php">Search</a>
      <a href="members.php">Members</a>
      <a href="messages.php">Messages<?php
        $unread = unreadMessages($me['id']);
        if ($unread > 0) echo ' <span class="badge">'.$unread.'</span>';
      ?></a>
      <?php
        $db = getDB();
        $ps = $db->prepare("SELECT COUNT(*) FROM friends WHERE friend_id=? AND status='pending'");
        $ps->execute([$me['id']]);
        $pc = (int)$ps->fetchColumn();
      ?>
      <a href="friends.php">Friends<?php if ($pc > 0) echo ' <span class="badge">'.$pc.'</span>'; ?></a>
      <a href="profile.php?u=<?= htmlspecialchars($me['username']) ?>">Profile</a>
      <a href="settings.php">Settings</a>
      <?php if ($me['is_admin']): ?><a href="admin.php" class="admin-link">Admin</a><?php endif; ?>
      <a href="logout.php" class="logout-link">Log out</a>
    </nav>
    <?php endif; ?>
  </div>
</header>
<div id="wrap">
