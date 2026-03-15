<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$username = $_GET['u'] ?? $me['username'];
$stmt = $db->prepare("SELECT * FROM users WHERE username=?");
$stmt->execute([$username]);
$profile = $stmt->fetch();
if (!$profile) { echo "<p>User not found.</p>"; exit; }
$isMe = ($profile['id'] === $me['id']);

// Delete post
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $p = $db->prepare("SELECT * FROM posts WHERE id=?")->execute([$pid]) ? $db->query("SELECT * FROM posts WHERE id=$pid")->fetch() : null;
    if ($p && ($p['user_id'] == $me['id'] || $me['is_admin'])) {
        if ($p['image']) @unlink(POSTS_DIR.$p['image']);
        $db->prepare("DELETE FROM comments WHERE post_id=?")->execute([$pid]);
        $db->prepare("DELETE FROM posts WHERE id=?")->execute([$pid]);
    }
    header("Location: profile.php?u=$username"); exit;
}

$friendStatus = null;
if (!$isMe) {
    $fs = $db->prepare("SELECT *, CASE WHEN user_id=? THEN 'sent' ELSE 'received' END as dir FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)");
    $fs->execute([$me['id'],$me['id'],$profile['id'],$profile['id'],$me['id']]);
    $friendStatus = $fs->fetch();
}

$isFriend = $friendStatus && $friendStatus['status'] === 'accepted';

// Posts: own = all, friends = public+friends, others = public only
if ($isMe) {
    $pstmt = $db->prepare("SELECT p.*,(SELECT COUNT(*) FROM comments WHERE post_id=p.id) as cc FROM posts p WHERE p.user_id=? ORDER BY p.created_at DESC LIMIT 20");
    $pstmt->execute([$profile['id']]);
} elseif ($isFriend) {
    $pstmt = $db->prepare("SELECT p.*,(SELECT COUNT(*) FROM comments WHERE post_id=p.id) as cc FROM posts p WHERE p.user_id=? AND p.privacy IN ('public','friends') ORDER BY p.created_at DESC LIMIT 20");
    $pstmt->execute([$profile['id']]);
} else {
    $pstmt = $db->prepare("SELECT p.*,(SELECT COUNT(*) FROM comments WHERE post_id=p.id) as cc FROM posts p WHERE p.user_id=? AND p.privacy='public' ORDER BY p.created_at DESC LIMIT 20");
    $pstmt->execute([$profile['id']]);
}
$posts = $pstmt->fetchAll();

$fstmt = $db->prepare("SELECT u.id,u.username,u.full_name,u.avatar FROM users u WHERE u.id IN (SELECT friend_id FROM friends WHERE user_id=? AND status='accepted' UNION SELECT user_id FROM friends WHERE friend_id=? AND status='accepted') LIMIT 12");
$fstmt->execute([$profile['id'],$profile['id']]);
$friends = $fstmt->fetchAll();

$pageTitle = htmlspecialchars($profile['full_name']);
include 'tpl_header.php';
?>
<div class="profile-page">
  <div class="profile-cover"></div>
  <div class="profile-header-box">
    <img src="uploads/avatars/<?= htmlspecialchars($profile['avatar']) ?>" class="avatar-xl" alt="">
    <div class="profile-header-info">
      <h1><?= htmlspecialchars($profile['full_name']) ?></h1>
      <p class="profile-username">@<?= htmlspecialchars($profile['username']) ?></p>
      <?php if ($profile['bio']): ?><p class="profile-bio"><?= nl2br(htmlspecialchars($profile['bio'])) ?></p><?php endif; ?>
      <div class="profile-fields">
        <?php if ($profile['favorite_os']): ?><span>🖥 <?= htmlspecialchars($profile['favorite_os']) ?></span><?php endif; ?>
        <?php if ($profile['interests']): ?><span>◈ <?= htmlspecialchars($profile['interests']) ?></span><?php endif; ?>
        <?php if ($profile['birthdate']): ?><span>📅 <?= htmlspecialchars($profile['birthdate']) ?></span><?php endif; ?>
        <span>🗓 Joined <?= htmlspecialchars(substr($profile['created_at'],0,10)) ?></span>
      </div>
      <div class="profile-actions">
        <?php if ($isMe): ?>
          <a href="settings.php" class="btn">Edit Profile</a>
        <?php elseif (!$friendStatus): ?>
          <a href="friend_action.php?action=add&id=<?= $profile['id'] ?>" class="btn">+ Add Friend</a>
          <a href="messages.php?to=<?= $profile['id'] ?>" class="btn-sec">✉ Message</a>
          <a href="report.php?type=user&id=<?= $profile['id'] ?>" class="btn-sm muted">⚑ Report</a>
        <?php elseif ($friendStatus['status'] === 'accepted'): ?>
          <span class="badge-friend">✓ Friends</span>
          <a href="messages.php?to=<?= $profile['id'] ?>" class="btn-sec">✉ Message</a>
          <a href="friend_action.php?action=remove&id=<?= $profile['id'] ?>" class="btn-sm muted">Remove</a>
        <?php elseif ($friendStatus['dir'] === 'sent'): ?>
          <span class="muted">Request sent</span>
          <a href="friend_action.php?action=cancel&id=<?= $profile['id'] ?>" class="btn-sm">Cancel</a>
        <?php else: ?>
          <a href="friend_action.php?action=accept&id=<?= $profile['id'] ?>" class="btn">✓ Accept</a>
          <a href="friend_action.php?action=decline&id=<?= $profile['id'] ?>" class="btn-sm">Decline</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="profile-body">
    <div class="profile-sidebar">
      <div class="profile-widget">
        <h3>Friends <span class="count"><?= count($friends) ?></span></h3>
        <div class="friends-grid">
          <?php foreach ($friends as $f): ?>
          <a href="profile.php?u=<?= htmlspecialchars($f['username']) ?>" class="friend-tile">
            <img src="uploads/avatars/<?= htmlspecialchars($f['avatar']) ?>" class="avatar-sm" alt="">
            <span><?= htmlspecialchars($f['full_name']) ?></span>
          </a>
          <?php endforeach; ?>
          <?php if (empty($friends)): ?><p class="muted">No friends yet.</p><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="profile-posts">
      <?php if ($isMe): ?>
      <div class="post-form-wrap">
        <form method="POST" action="home.php" enctype="multipart/form-data" class="post-form">
          <div class="post-form-top">
            <textarea name="content" maxlength="2000" placeholder="Write something..." required></textarea>
          </div>
          <div class="post-form-bottom">
            <select name="privacy" class="post-privacy">
              <option value="public">🌐 Public</option>
              <option value="friends">👥 Friends only</option>
            </select>
            <button type="submit">Post</button>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <?php foreach ($posts as $p): ?>
      <article class="post-card">
        <div class="post-card-header">
          <div class="post-meta">
            <span class="post-time"><?= htmlspecialchars($p['created_at']) ?></span>
            <span class="post-privacy-badge"><?= $p['privacy'] === 'friends' ? '👥' : '🌐' ?></span>
          </div>
          <?php if ($isMe || $me['is_admin']): ?>
            <a href="profile.php?u=<?= $username ?>&delete=<?= $p['id'] ?>" class="post-delete" onclick="return confirm('Delete?')">✕</a>
          <?php endif; ?>
        </div>
        <p class="post-content"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
        <?php if ($p['link']): ?><a href="<?= htmlspecialchars($p['link']) ?>" target="_blank" class="post-link">🔗 <?= htmlspecialchars($p['link']) ?></a><?php endif; ?>
        <?php if ($p['image']): ?><img src="uploads/posts/<?= htmlspecialchars($p['image']) ?>" class="post-image" alt=""><?php endif; ?>
        <div class="post-card-footer">
          <a href="post.php?id=<?= $p['id'] ?>" class="post-comments-link">💬 <?= $p['cc'] ?> comment<?= $p['cc'] != 1 ? 's' : '' ?></a>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (empty($posts)): ?><p class="muted">No posts yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php include 'tpl_footer.php'; ?>
