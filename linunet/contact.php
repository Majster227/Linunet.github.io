<?php
require_once 'config.php';
require_once 'auth.php';
$me = currentUser();
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($msg) < 10) {
        $error = 'Please fill in all fields correctly.';
    } else {
        // In a real deployment you'd send an email here
        // For now we just save to a file
        $entry = date('Y-m-d H:i:s')." | $name | $email\n$msg\n---\n";
        file_put_contents(__DIR__.'/data/contact.txt', $entry, FILE_APPEND);
        $success = 'Message sent! We will get back to you soon.';
    }
}
$pageTitle = 'Contact';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>Contact</h2>
  <?php if ($success): ?><p class="success"><?= $success ?></p>
  <?php else: ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <div class="card">
    <form method="POST">
      <label>Name</label>
      <input type="text" name="name" required maxlength="60" value="<?= $me ? htmlspecialchars($me['full_name']) : '' ?>">
      <label>Email</label>
      <input type="email" name="email" required value="<?= $me ? htmlspecialchars($me['email']) : '' ?>">
      <label>Message</label>
      <textarea name="message" required maxlength="2000" rows="6"></textarea>
      <button type="submit">Send Message</button>
    </form>
  </div>
  <?php endif; ?>
</div>
<?php include 'tpl_footer.php'; ?>
