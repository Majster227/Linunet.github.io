<?php
require_once 'config.php';
require_once 'auth.php';
$me = currentUser();
$db = getDB();
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$postCount = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$pageTitle = 'About';
include 'tpl_header.php';
?>
<div class="layout-single">
  <div class="about-hero">
    <div class="about-logo">◉</div>
    <h1><?= SITE_NAME ?></h1>
    <p><?= SITE_TAGLINE ?></p>
  </div>
  <div class="card mb">
    <h3>What is <?= SITE_NAME ?>?</h3>
    <p><?= SITE_NAME ?> is a free, open social network built for people who think differently. No algorithms, no ads, no tracking. Just people connecting with people.</p>
    <p class="mt">Built with PHP and SQLite on Linux. Simple, fast, and yours.</p>
  </div>
  <div class="card mb">
    <h3>By the numbers</h3>
    <div class="about-stats">
      <div class="stat-box"><span><?= number_format($userCount) ?></span>Members</div>
      <div class="stat-box"><span><?= number_format($postCount) ?></span>Posts</div>
    </div>
  </div>
  <div class="card">
    <h3>Join us</h3>
    <p>It's free and always will be. <a href="register.php">Create an account →</a></p>
  </div>
</div>
<?php include 'tpl_footer.php'; ?>
