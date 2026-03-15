<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;
$total = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pages = ceil($total / $perPage);
$members = $db->query("SELECT id,username,full_name,avatar,favorite_os,interests,created_at FROM users ORDER BY created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
$pageTitle = 'Members';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>All Members <span class="count"><?= number_format($total) ?></span></h2>
  <div class="members-grid">
    <?php foreach ($members as $u): ?>
    <div class="member-card">
      <a href="profile.php?u=<?= htmlspecialchars($u['username']) ?>">
        <img src="uploads/avatars/<?= htmlspecialchars($u['avatar']) ?>" class="avatar-lg" alt="">
        <p class="member-card-name"><?= htmlspecialchars($u['full_name']) ?></p>
        <p class="muted small">@<?= htmlspecialchars($u['username']) ?></p>
      </a>
      <?php if ($u['favorite_os']): ?><span class="tag">🖥 <?= htmlspecialchars($u['favorite_os']) ?></span><?php endif; ?>
      <a href="friend_action.php?action=add&id=<?= $u['id'] ?>" class="btn-add mt">+ Add</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
      <a href="?p=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php include 'tpl_footer.php'; ?>
