<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$postId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT p.*,u.username,u.full_name,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=?");
$stmt->execute([$postId]);
$post = $stmt->fetch();
if (!$post) { echo "<p>Post not found.</p>"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $c = trim($_POST['comment']);
    if (strlen($c) > 0 && strlen($c) <= 1000) {
        $db->prepare("INSERT INTO comments (post_id,user_id,content) VALUES (?,?,?)")->execute([$postId,$me['id'],$c]);
    }
    header("Location: post.php?id=$postId"); exit;
}

if (isset($_GET['delcomment'])) {
    $cid = (int)$_GET['delcomment'];
    $c = $db->query("SELECT * FROM comments WHERE id=$cid")->fetch();
    if ($c && ($c['user_id'] == $me['id'] || $me['is_admin'])) {
        $db->prepare("DELETE FROM comments WHERE id=?")->execute([$cid]);
    }
    header("Location: post.php?id=$postId"); exit;
}

$comments = $db->prepare("SELECT c.*,u.username,u.full_name,u.avatar FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=? ORDER BY c.created_at ASC");
$comments->execute([$postId]);
$comments = $comments->fetchAll();

$pageTitle = 'Post';
include 'tpl_header.php';
?>
<div class="layout-single">
  <article class="post-card">
    <div class="post-card-header">
      <img src="uploads/avatars/<?= htmlspecialchars($post['avatar']) ?>" class="avatar-sm" alt="">
      <div class="post-meta">
        <a href="profile.php?u=<?= htmlspecialchars($post['username']) ?>" class="post-author"><?= htmlspecialchars($post['full_name']) ?></a>
        <span class="post-time"><?= htmlspecialchars($post['created_at']) ?></span>
      </div>
    </div>
    <p class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
    <?php if ($post['link']): ?><a href="<?= htmlspecialchars($post['link']) ?>" target="_blank" class="post-link">🔗 <?= htmlspecialchars($post['link']) ?></a><?php endif; ?>
    <?php if ($post['image']): ?><img src="uploads/posts/<?= htmlspecialchars($post['image']) ?>" class="post-image" alt=""><?php endif; ?>
  </article>

  <div class="comments-section">
    <h3>Comments (<?= count($comments) ?>)</h3>
    <?php if (empty($comments)): ?><p class="muted">No comments yet. Be first!</p><?php endif; ?>
    <?php foreach ($comments as $c): ?>
    <div class="comment-row">
      <img src="uploads/avatars/<?= htmlspecialchars($c['avatar']) ?>" class="avatar-xs" alt="">
      <div class="comment-body">
        <a href="profile.php?u=<?= htmlspecialchars($c['username']) ?>" class="comment-author"><?= htmlspecialchars($c['full_name']) ?></a>
        <span class="muted small"> · <?= htmlspecialchars($c['created_at']) ?></span>
        <?php if ($c['user_id'] == $me['id'] || $me['is_admin']): ?>
          <a href="post.php?id=<?= $postId ?>&delcomment=<?= $c['id'] ?>" class="comment-del" onclick="return confirm('Delete?')">✕</a>
        <?php endif; ?>
        <p><?= nl2br(htmlspecialchars($c['content'])) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
    <form method="POST" class="comment-form">
      <img src="uploads/avatars/<?= htmlspecialchars($me['avatar']) ?>" class="avatar-xs" alt="">
      <div class="comment-input-wrap">
        <textarea name="comment" maxlength="1000" placeholder="Write a comment..." required></textarea>
        <button type="submit">Comment</button>
      </div>
    </form>
  </div>
</div>
<?php include 'tpl_footer.php'; ?>
