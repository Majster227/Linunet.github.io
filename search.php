<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$q = trim($_GET['q'] ?? '');
$results = [];
if (strlen($q) >= 2) {
    $stmt = $db->prepare("SELECT id,username,full_name,avatar,favorite_os,interests FROM users WHERE (username LIKE ? OR full_name LIKE ? OR favorite_os LIKE ? OR interests LIKE ?) AND id != ? ORDER BY full_name LIMIT 20");
    $like = "%$q%";
    $stmt->execute([$like,$like,$like,$like,$me['id']]);
    $results = $stmt->fetchAll();
}
$pageTitle = 'Search';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>Search Members</h2>
  <form method="GET" class="search-form">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, username, OS, interests..." autofocus>
    <button type="submit">Search</button>
  </form>
  <?php if ($q && empty($results)): ?><p class="muted">No results for "<?= htmlspecialchars($q) ?>".</p><?php endif; ?>
  <?php foreach ($results as $u): ?>
  <div class="member-row">
    <img src="uploads/avatars/<?= htmlspecialchars($u['avatar']) ?>" class="avatar-md" alt="">
    <div class="member-info">
      <a href="profile.php?u=<?= htmlspecialchars($u['username']) ?>" class="member-name"><?= htmlspecialchars($u['full_name']) ?></a>
      <span class="muted">@<?= htmlspecialchars($u['username']) ?></span>
      <?php if ($u['favorite_os']): ?><span class="tag">🖥 <?= htmlspecialchars($u['favorite_os']) ?></span><?php endif; ?>
      <?php if ($u['interests']): ?><span class="tag">◈ <?= htmlspecialchars($u['interests']) ?></span><?php endif; ?>
    </div>
    <a href="friend_action.php?action=add&id=<?= $u['id'] ?>" class="btn-add">+ Add</a>
  </div>
  <?php endforeach; ?>
</div>
<?php include 'tpl_footer.php'; ?>
