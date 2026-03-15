<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();

// New post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $privacy = in_array($_POST['privacy']??'', ['public','friends']) ? $_POST['privacy'] : 'public';
    $link = trim($_POST['link'] ?? '');
    $image = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['image']['size'] < 5*1024*1024) {
            $fname = $me['id'].'_'.time().'.'.$ext;
            move_uploaded_file($_FILES['image']['tmp_name'], POSTS_DIR.$fname);
            $image = $fname;
        }
    }
    if (strlen($content) > 0) {
        $db->prepare("INSERT INTO posts (user_id,content,image,link,privacy) VALUES (?,?,?,?,?)")
           ->execute([$me['id'], $content, $image, $link, $privacy]);
    }
    header('Location: home.php'); exit;
}

// Delete post
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $post = $db->prepare("SELECT * FROM posts WHERE id=?");
    $post->execute([$pid]);
    $p = $post->fetch();
    if ($p && ($p['user_id'] == $me['id'] || $me['is_admin'])) {
        if ($p['image']) @unlink(POSTS_DIR.$p['image']);
        $db->prepare("DELETE FROM comments WHERE post_id=?")->execute([$pid]);
        $db->prepare("DELETE FROM posts WHERE id=?")->execute([$pid]);
    }
    header('Location: home.php'); exit;
}

// Feed: own posts + friends' public/friends posts
$stmt = $db->prepare("
    SELECT p.*, u.username, u.full_name, u.avatar,
           (SELECT COUNT(*) FROM comments WHERE post_id=p.id) as comment_count
    FROM posts p JOIN users u ON p.user_id=u.id
    WHERE (
        p.user_id = ?
        OR (
            p.user_id IN (
                SELECT friend_id FROM friends WHERE user_id=? AND status='accepted'
                UNION SELECT user_id FROM friends WHERE friend_id=? AND status='accepted'
            )
            AND p.privacy IN ('public','friends')
        )
    )
    ORDER BY p.created_at DESC LIMIT 40
");
$stmt->execute([$me['id'], $me['id'], $me['id']]);
$posts = $stmt->fetchAll();

// Suggested friends
$stmt2 = $db->prepare("
    SELECT id,username,full_name,avatar,favorite_os FROM users
    WHERE id != ? AND id NOT IN (
        SELECT friend_id FROM friends WHERE user_id=?
        UNION SELECT user_id FROM friends WHERE friend_id=?
    )
    ORDER BY RANDOM() LIMIT 5
");
$stmt2->execute([$me['id'],$me['id'],$me['id']]);
$suggested = $stmt2->fetchAll();

$pageTitle = 'Home';
include 'tpl_header.php';
?>
<div class="layout">
  <aside class="sidebar-left">
    <div class="profile-mini">
      <img src="uploads/avatars/<?= htmlspecialchars($me['avatar']) ?>" class="avatar-md" alt="">
      <p class="pm-name"><a href="profile.php?u=<?= $me['username'] ?>"><?= htmlspecialchars($me['full_name']) ?></a></p>
      <p class="pm-user">@<?= htmlspecialchars($me['username']) ?></p>
      <?php if ($me['favorite_os']): ?>
      <p class="pm-os">🖥 <?= htmlspecialchars($me['favorite_os']) ?></p>
      <?php endif; ?>
    </div>
    <nav class="side-nav">
      <a href="home.php" class="active">◈ Feed</a>
      <a href="profile.php?u=<?= $me['username'] ?>">◈ My Profile</a>
      <a href="friends.php">◈ Friends</a>
      <a href="messages.php">◈ Messages</a>
      <a href="members.php">◈ Members</a>
      <a href="search.php">◈ Search</a>
      <a href="settings.php">◈ Settings</a>
      <?php if ($me['is_admin']): ?><a href="admin.php">◈ Admin Panel</a><?php endif; ?>
      <a href="logout.php">◈ Log Out</a>
    </nav>
  </aside>

  <main class="feed">
    <div class="post-form-wrap">
      <form method="POST" enctype="multipart/form-data" class="post-form">
        <div class="post-form-top">
          <img src="uploads/avatars/<?= htmlspecialchars($me['avatar']) ?>" class="avatar-sm" alt="">
          <textarea name="content" maxlength="2000" placeholder="What's on your mind, <?= htmlspecialchars($me['full_name']) ?>?" required></textarea>
        </div>
        <div class="post-form-bottom">
          <input type="text" name="link" placeholder="Link (optional)" class="post-link-input">
          <label class="post-image-label">📎 Image <input type="file" name="image" accept="image/*" style="display:none"></label>
          <select name="privacy" class="post-privacy">
            <option value="public">🌐 Public</option>
            <option value="friends">👥 Friends only</option>
          </select>
          <button type="submit">Post</button>
        </div>
      </form>
    </div>

    <?php if (empty($posts)): ?>
      <div class="empty-feed">
        <p>◉ No posts yet. Add some friends or write something!</p>
      </div>
    <?php endif; ?>

    <?php foreach ($posts as $p): ?>
    <article class="post-card">
      <div class="post-card-header">
        <img src="uploads/avatars/<?= htmlspecialchars($p['avatar']) ?>" class="avatar-sm" alt="">
        <div class="post-meta">
          <a href="profile.php?u=<?= htmlspecialchars($p['username']) ?>" class="post-author"><?= htmlspecialchars($p['full_name']) ?></a>
          <span class="post-time"><?= htmlspecialchars($p['created_at']) ?></span>
          <span class="post-privacy-badge"><?= $p['privacy'] === 'friends' ? '👥' : '🌐' ?></span>
        </div>
        <?php if ($p['user_id'] == $me['id'] || $me['is_admin']): ?>
          <a href="home.php?delete=<?= $p['id'] ?>" class="post-delete" onclick="return confirm('Delete this post?')">✕</a>
        <?php endif; ?>
      </div>
      <div class="post-card-body">
        <p class="post-content"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
        <?php if ($p['link']): ?>
          <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank" class="post-link">🔗 <?= htmlspecialchars($p['link']) ?></a>
        <?php endif; ?>
        <?php if ($p['image']): ?>
          <img src="uploads/posts/<?= htmlspecialchars($p['image']) ?>" class="post-image" alt="">
        <?php endif; ?>
      </div>
      <div class="post-card-footer">
        <a href="post.php?id=<?= $p['id'] ?>" class="post-comments-link">💬 <?= $p['comment_count'] ?> comment<?= $p['comment_count'] != 1 ? 's' : '' ?></a>
        <a href="report.php?type=post&id=<?= $p['id'] ?>" class="post-report">⚑ Report</a>
      </div>
    </article>
    <?php endforeach; ?>
  </main>

  <aside class="sidebar-right">
    <h3>People You May Know</h3>
    <?php foreach ($suggested as $s): ?>
    <div class="suggested-card">
      <img src="uploads/avatars/<?= htmlspecialchars($s['avatar']) ?>" class="avatar-sm" alt="">
      <div class="suggested-info">
        <a href="profile.php?u=<?= htmlspecialchars($s['username']) ?>"><?= htmlspecialchars($s['full_name']) ?></a>
        <?php if ($s['favorite_os']): ?><span class="muted">🖥 <?= htmlspecialchars($s['favorite_os']) ?></span><?php endif; ?>
        <a href="friend_action.php?action=add&id=<?= $s['id'] ?>" class="btn-add">+ Add</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($suggested)): ?><p class="muted">You know everyone here!</p><?php endif; ?>
  </aside>
</div>
<?php include 'tpl_footer.php'; ?>
