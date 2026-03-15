<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$type = in_array($_GET['type']??'', ['post','user']) ? $_GET['type'] : 'post';
$targetId = (int)($_GET['id'] ?? 0);
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    if (strlen($reason) < 5) { $error = 'Please describe the reason.'; }
    else {
        $db->prepare("INSERT INTO reports (reporter_id,type,target_id,reason) VALUES (?,?,?,?)")->execute([$me['id'],$type,$targetId,$reason]);
        $success = 'Report submitted. Thank you.';
    }
}
$pageTitle = 'Report';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>Report Content</h2>
  <?php if ($success): ?><p class="success"><?= $success ?></p>
  <?php else: ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <div class="card">
    <p class="muted">Reporting <?= $type ?> #<?= $targetId ?></p>
    <form method="POST">
      <label>Reason</label>
      <textarea name="reason" required maxlength="500" placeholder="Describe why you're reporting this..."></textarea>
      <button type="submit">Submit Report</button>
    </form>
  </div>
  <?php endif; ?>
</div>
<?php include 'tpl_footer.php'; ?>
