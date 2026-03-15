<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();

$myFriends = $db->prepare("SELECT u.* FROM users u WHERE u.id IN (SELECT friend_id FROM friends WHERE user_id=? AND status='accepted' UNION SELECT user_id FROM friends WHERE friend_id=? AND status='accepted') ORDER BY u.full_name");
$myFriends->execute([$me['id'],$me['id']]);
$myFriends = $myFriends->fetchAll();

$incoming = $db->prepare("SELECT u.*,f.created_at as req_at FROM friends f JOIN users u ON f.user_id=u.id WHERE f.friend_id=? AND f.status='pending' ORDER BY f.created_at DESC");
$incoming->execute([$me['id']]);
$incoming = $incoming->fetchAll();

$outgoing = $db->prepare("SELECT u.*,f.created_at as req_at FROM friends f JOIN users u ON f.friend_id=u.id WHERE f.user_id=? AND f.status='pending' ORDER BY f.created_at DESC");
$outgoing->execute([$me['id']]);
$outgoing = $outgoing->fetchAll();

$pageTitle = 'Friends';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>Friends</h2>

  <?php if (!empty($incoming)): ?>
  <div class="card mb">
    <h3>Friend Requests <span class="badge"><?= count($incoming) ?></span></h3>
    <?php foreach ($incoming as $u): ?>
    <div class="friend-row">
      <img src="uploads/avatars/<?= htmlspecialchars($u['avatar']) ?>" class="avatar-sm" alt="">
      <div class="friend-row-info">
        <a href="profile.php?u=<?= htmlspecialchars($u['username']) ?>"><?= htmlspecialchars($u['full_name']) ?></a>
        <span class="muted">@<?= htmlspecialchars($u['username']) ?></span>
      </div>
      <div class="friend-row-actions">
        <a href="friend_action.php?action=accept&id=<?= $u['id'] ?>" class="btn">Accept</a>
        <a href="friend_action.php?action=decline&id=<?= $u['id'] ?>" class="btn-sm">Decline</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card mb">
    <h3>My Friends <span class="count"><?= count($myFriends) ?></span></h3>
    <?php if (empty($myFriends)): ?>
      <p class="muted">No friends yet. <a href="members.php">Browse members</a></p>
    <?php endif; ?>
    <div class="friends-grid-big">
      <?php foreach ($myFriends as $f): ?>
      <div class="friend-card">
        <a href="profile.php?u=<?= htmlspecialchars($f['username']) ?>">
          <img src="uploads/avatars/<?= htmlspecialchars($f['avatar']) ?>" class="avatar-md" alt="">
          <p><?= htmlspecialchars($f['full_name']) ?></p>
        </a>
        <a href="friend_action.php?action=remove&id=<?= $f['id'] ?>" class="btn-sm muted">Remove</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (!empty($outgoing)): ?>
  <div class="card">
    <h3>Sent Requests</h3>
    <?php foreach ($outgoing as $u): ?>
    <div class="friend-row">
      <img src="uploads/avatars/<?= htmlspecialchars($u['avatar']) ?>" class="avatar-sm" alt="">
      <div class="friend-row-info">
        <a href="profile.php?u=<?= htmlspecialchars($u['username']) ?>"><?= htmlspecialchars($u['full_name']) ?></a>
      </div>
      <a href="friend_action.php?action=cancel&id=<?= $u['id'] ?>" class="btn-sm">Cancel</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include 'tpl_footer.php'; ?>
