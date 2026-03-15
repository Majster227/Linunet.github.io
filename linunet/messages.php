<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();

$toId = (int)($_GET['to'] ?? 0);

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && $toId > 0) {
    $c = trim($_POST['content']);
    if (strlen($c) > 0 && strlen($c) <= 2000) {
        $db->prepare("INSERT INTO messages (from_id,to_id,content) VALUES (?,?,?)")->execute([$me['id'],$toId,$c]);
    }
    header("Location: messages.php?to=$toId"); exit;
}

// Mark as read
if ($toId > 0) {
    $db->prepare("UPDATE messages SET is_read=1 WHERE to_id=? AND from_id=?")->execute([$me['id'],$toId]);
}

// Conversation list
$convos = $db->prepare("
    SELECT u.id,u.username,u.full_name,u.avatar,
           MAX(m.created_at) as last_at,
           SUM(CASE WHEN m.to_id=? AND m.is_read=0 THEN 1 ELSE 0 END) as unread
    FROM messages m
    JOIN users u ON (CASE WHEN m.from_id=? THEN m.to_id ELSE m.from_id END)=u.id
    WHERE m.from_id=? OR m.to_id=?
    GROUP BY u.id ORDER BY last_at DESC
");
$convos->execute([$me['id'],$me['id'],$me['id'],$me['id']]);
$convos = $convos->fetchAll();

// Active conversation
$partner = null;
$thread = [];
if ($toId > 0) {
    $ps = $db->prepare("SELECT * FROM users WHERE id=?");
    $ps->execute([$toId]);
    $partner = $ps->fetch();
    $ts = $db->prepare("SELECT m.*,u.username,u.full_name,u.avatar FROM messages m JOIN users u ON m.from_id=u.id WHERE (m.from_id=? AND m.to_id=?) OR (m.from_id=? AND m.to_id=?) ORDER BY m.created_at ASC");
    $ts->execute([$me['id'],$toId,$toId,$me['id']]);
    $thread = $ts->fetchAll();
}

$pageTitle = 'Messages';
include 'tpl_header.php';
?>
<div class="messages-layout">
  <div class="messages-sidebar">
    <h3>Messages</h3>
    <?php if (empty($convos)): ?><p class="muted">No conversations yet.</p><?php endif; ?>
    <?php foreach ($convos as $c): ?>
    <a href="messages.php?to=<?= $c['id'] ?>" class="convo-row <?= $toId == $c['id'] ? 'active' : '' ?>">
      <img src="uploads/avatars/<?= htmlspecialchars($c['avatar']) ?>" class="avatar-sm" alt="">
      <div class="convo-info">
        <span class="convo-name"><?= htmlspecialchars($c['full_name']) ?></span>
        <span class="convo-time muted small"><?= htmlspecialchars(substr($c['last_at'],0,16)) ?></span>
      </div>
      <?php if ($c['unread'] > 0): ?><span class="badge"><?= $c['unread'] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="messages-main">
    <?php if ($partner): ?>
      <div class="messages-header">
        <img src="uploads/avatars/<?= htmlspecialchars($partner['avatar']) ?>" class="avatar-sm" alt="">
        <a href="profile.php?u=<?= htmlspecialchars($partner['username']) ?>"><?= htmlspecialchars($partner['full_name']) ?></a>
      </div>
      <div class="messages-thread" id="thread">
        <?php if (empty($thread)): ?><p class="muted">Start the conversation!</p><?php endif; ?>
        <?php foreach ($thread as $msg): ?>
        <div class="msg-row <?= $msg['from_id'] == $me['id'] ? 'msg-mine' : 'msg-theirs' ?>">
          <div class="msg-bubble">
            <p><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
            <span class="msg-time"><?= htmlspecialchars(substr($msg['created_at'],11,5)) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <form method="POST" class="messages-form">
        <textarea name="content" placeholder="Write a message..." required maxlength="2000"></textarea>
        <button type="submit">Send →</button>
      </form>
      <script>document.getElementById('thread').scrollTop = 9999999;</script>
    <?php else: ?>
      <div class="messages-empty">
        <p>◉ Select a conversation or <a href="members.php">find someone to message</a>.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include 'tpl_footer.php'; ?>
