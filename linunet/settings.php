<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $fn = trim($_POST['full_name'] ?? '');
        $bio = substr(trim($_POST['bio'] ?? ''), 0, 500);
        $os = substr(trim($_POST['favorite_os'] ?? ''), 0, 60);
        $interests = substr(trim($_POST['interests'] ?? ''), 0, 200);
        $bd = trim($_POST['birthdate'] ?? '');
        $privacy = in_array($_POST['post_privacy']??'', ['public','friends']) ? $_POST['post_privacy'] : 'public';
        if (strlen($fn) < 2) { $error = 'Name too short.'; }
        else {
            $db->prepare("UPDATE users SET full_name=?,bio=?,favorite_os=?,interests=?,birthdate=?,post_privacy=? WHERE id=?")
               ->execute([$fn,$bio,$os,$interests,$bd,$privacy,$me['id']]);
            $success = 'Profile updated.';
        }
    }

    if ($action === 'avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { $error = 'Invalid image format.'; }
            elseif ($_FILES['avatar']['size'] > 2*1024*1024) { $error = 'File too large (max 2MB).'; }
            else {
                $newName = $me['id'].'_'.time().'.'.$ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOADS_DIR.$newName);
                if ($me['avatar'] !== 'default.png' && file_exists(UPLOADS_DIR.$me['avatar'])) @unlink(UPLOADS_DIR.$me['avatar']);
                $db->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([$newName,$me['id']]);
                $success = 'Avatar updated.';
            }
        }
    }

    if ($action === 'account') {
        $email = trim($_POST['email'] ?? '');
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $con = $_POST['confirm_password'] ?? '';
        if (!password_verify($cur, $me['password_hash'])) { $error = 'Current password is incorrect.'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email.'; }
        elseif ($new && strlen($new) < 6) { $error = 'New password too short.'; }
        elseif ($new && $new !== $con) { $error = 'Passwords do not match.'; }
        else {
            $hash = $new ? password_hash($new, PASSWORD_BCRYPT) : $me['password_hash'];
            $db->prepare("UPDATE users SET email=?,password_hash=? WHERE id=?")->execute([$email,$hash,$me['id']]);
            $success = 'Account updated.';
        }
    }

    if ($action === 'delete_account') {
        $pw = $_POST['confirm_pw'] ?? '';
        if (!password_verify($pw, $me['password_hash'])) { $error = 'Wrong password.'; }
        else {
            $db->prepare("DELETE FROM friends WHERE user_id=? OR friend_id=?")->execute([$me['id'],$me['id']]);
            $db->prepare("DELETE FROM comments WHERE user_id=?")->execute([$me['id']]);
            $db->prepare("DELETE FROM messages WHERE from_id=? OR to_id=?")->execute([$me['id'],$me['id']]);
            $db->prepare("DELETE FROM posts WHERE user_id=?")->execute([$me['id']]);
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$me['id']]);
            session_destroy();
            header('Location: index.php'); exit;
        }
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$me['id']]); $me = $stmt->fetch();
}

$pageTitle = 'Settings';
include 'tpl_header.php';
?>
<div class="layout-single">
  <h2>Settings</h2>
  <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <div class="card mb">
    <h3>Avatar</h3>
    <img src="uploads/avatars/<?= htmlspecialchars($me['avatar']) ?>" class="avatar-xl" alt="">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="avatar">
      <label>Choose image (jpg/png/gif, max 2MB)</label>
      <input type="file" name="avatar" accept="image/*">
      <button type="submit">Update Avatar</button>
    </form>
  </div>

  <div class="card mb">
    <h3>Profile Info</h3>
    <form method="POST">
      <input type="hidden" name="action" value="profile">
      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($me['full_name']) ?>" required maxlength="60">
      <label>Bio</label>
      <textarea name="bio" maxlength="500"><?= htmlspecialchars($me['bio']) ?></textarea>
      <label>Favorite OS</label>
      <input type="text" name="favorite_os" value="<?= htmlspecialchars($me['favorite_os']) ?>" maxlength="60" placeholder="e.g. Linux Mint, Arch, Debian...">
      <label>Interests</label>
      <input type="text" name="interests" value="<?= htmlspecialchars($me['interests']) ?>" maxlength="200" placeholder="e.g. programming, music, gaming...">
      <label>Date of Birth</label>
      <input type="date" name="birthdate" value="<?= htmlspecialchars($me['birthdate']) ?>">
      <label>Default post privacy</label>
      <select name="post_privacy">
        <option value="public" <?= $me['post_privacy']==='public'?'selected':'' ?>>🌐 Public</option>
        <option value="friends" <?= $me['post_privacy']==='friends'?'selected':'' ?>>👥 Friends only</option>
      </select>
      <button type="submit">Save Profile</button>
    </form>
  </div>

  <div class="card mb">
    <h3>Account</h3>
    <form method="POST">
      <input type="hidden" name="action" value="account">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($me['email']) ?>" required>
      <label>Current Password (required)</label>
      <input type="password" name="current_password" required>
      <label>New Password (leave blank to keep current)</label>
      <input type="password" name="new_password">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password">
      <button type="submit">Update Account</button>
    </form>
  </div>

  <div class="card card-danger">
    <h3>Delete Account</h3>
    <p class="muted">This is permanent. All your posts and data will be deleted.</p>
    <form method="POST" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">
      <input type="hidden" name="action" value="delete_account">
      <label>Enter your password to confirm</label>
      <input type="password" name="confirm_pw" required>
      <button type="submit" class="btn-danger">Delete My Account</button>
    </form>
  </div>
</div>
<?php include 'tpl_footer.php'; ?>
