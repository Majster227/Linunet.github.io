<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$db = getDB();
$me = currentUser();
$action = $_GET['action'] ?? '';
$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0 || $targetId === $me['id']) { header('Location: friends.php'); exit; }
switch ($action) {
    case 'add':
        $db->prepare("INSERT OR IGNORE INTO friends (user_id,friend_id,status) VALUES (?,?,'pending')")->execute([$me['id'],$targetId]);
        break;
    case 'accept':
        $db->prepare("UPDATE friends SET status='accepted' WHERE user_id=? AND friend_id=? AND status='pending'")->execute([$targetId,$me['id']]);
        break;
    case 'decline':
        $db->prepare("DELETE FROM friends WHERE user_id=? AND friend_id=? AND status='pending'")->execute([$targetId,$me['id']]);
        break;
    case 'cancel':
        $db->prepare("DELETE FROM friends WHERE user_id=? AND friend_id=? AND status='pending'")->execute([$me['id'],$targetId]);
        break;
    case 'remove':
        $db->prepare("DELETE FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)")->execute([$me['id'],$targetId,$targetId,$me['id']]);
        break;
}
header('Location: '.($_SERVER['HTTP_REFERER'] ?? 'friends.php'));
exit;
