<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
if (!$me['is_admin']) { echo "<p>Access denied.</p>"; exit; }
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['uid'] ?? 0);
    $pid = (int)($_POST['pid'] ?? 0);
    $rid = (int)($_POST['rid'] ?? 0);
    if ($action === 'make_admin' && $uid !== $me['id']) { $db->prepare("UPDATE users SET is_admin=1 WHERE id=?")->execute([$uid]); $success = "User #$uid promoted."; }
    if ($action === 'remove_admin' && $uid !== $me['id']) { $db->prepare("UPDATE users SET is_admin=0 WHERE id=?")->execute([$uid]); $success = "Admin removed from #$uid."; }
    if ($action === 'delete_user' && $uid !== $me['id']) {
        foreach (['friends','comments','messages','posts'] as $t) $db->prepare("DELETE FROM $t WHERE user_id=? OR ".($t==='friends'?'friend_id':($t==='messages'?'to_id':'user_id'))."=?")->execute([$uid,$uid]);
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]); $success = "User #$uid deleted.";
    }
    if ($action === 'delete_post' && $pid) { $db->prepare("DELETE FROM comments WHERE post_id=?")->execute([$pid]); $db->prepare("DELETE FROM posts WHERE id=?")->execute([$pid]); $success = "Post #$pid deleted."; }
    if ($action === 'close_report' && $rid) { $db->prepare("UPDATE reports SET status='closed' WHERE id=?")->execute([$rid]); $success = "Report #$rid closed."; }
}

$users = $db->query("SELECT id,username,full_name,email,is_admin,created_at FROM users ORDER BY id")->fetchAll();
$posts = $db->query("SELECT p.id,p.content,p.created_at,u.username FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 30")->fetchAll();
$reports = $db->query("SELECT r.*,u.username as reporter FROM reports r JOIN users u ON r.reporter_id=u.id WHERE r.status='pending' ORDER BY r.created_at DESC")->fetchAll();
$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'posts' => $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'friends' => $db->query("SELECT COUNT(*) FROM friends WHERE status='accepted'")->fetchColumn(),
    'messages' => $db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'reports' => $db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
];
$pageTitle = 'Admin Panel';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>Admin Panel</h2>
  <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
  <div class="admin-stats">
    <?php foreach (['users'=>'👤','posts'=>'📝','friends'=>'👥','messages'=>'✉','reports'=>'⚑'] as $k=>$icon): ?>
    <div class="stat-box"><span><?= $stats[$k] ?></span><?= $icon ?> <?= $k ?></div>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($reports)): ?>
  <h3>Pending Reports <span class="badge"><?= count($reports) ?></span></h3>
  <table class="admin-table">
    <tr><th>ID</th><th>Reporter</th><th>Type</th><th>Target</th><th>Reason</th><th>Date</th><th></th></tr>
    <?php foreach ($reports as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['reporter']) ?></td><td><?= $r['type'] ?></td><td>#<?= $r['target_id'] ?></td>
      <td><?= htmlspecialchars(substr($r['reason'],0,60)) ?></td><td><?= substr($r['created_at'],0,10) ?></td>
      <td><form method="POST" style="display:inline"><input type="hidden" name="rid" value="<?= $r['id'] ?>"><button name="action" value="close_report">Close</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <h3>Users</h3>
  <table class="admin-table">
    <tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Admin</th><th>Joined</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><a href="profile.php?u=<?= htmlspecialchars($u['username']) ?>"><?= htmlspecialchars($u['username']) ?></a></td>
      <td><?= htmlspecialchars($u['full_name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= $u['is_admin'] ? '★' : '' ?></td>
      <td><?= substr($u['created_at'],0,10) ?></td>
      <td><?php if ($u['id'] !== $me['id']): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="uid" value="<?= $u['id'] ?>">
          <button name="action" value="<?= $u['is_admin']?'remove_admin':'make_admin' ?>"><?= $u['is_admin']?'-admin':'+admin' ?></button>
          <button name="action" value="delete_user" onclick="return confirm('Delete user?')">Delete</button>
        </form>
      <?php else: ?><span class="muted">(you)</span><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h3>Recent Posts</h3>
  <table class="admin-table">
    <tr><th>ID</th><th>Author</th><th>Content</th><th>Date</th><th></th></tr>
    <?php foreach ($posts as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td><td><?= htmlspecialchars($p['username']) ?></td>
      <td><?= htmlspecialchars(substr($p['content'],0,80)) ?>…</td><td><?= substr($p['created_at'],0,16) ?></td>
      <td><form method="POST" style="display:inline"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button name="action" value="delete_post" onclick="return confirm('Delete post?')">Delete</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include 'tpl_footer.php'; ?>
