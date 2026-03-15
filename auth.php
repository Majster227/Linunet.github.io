<?php
require_once 'config.php';

// Upewnij się że sesja jest zawsze wystartowana
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION = [];
        session_destroy();
        return null;
    }
    return $user;
}

function loginUser(string $login, string $password): bool|string {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return 'Invalid username or password.';
    }
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    return true;
}

function registerUser(string $username, string $email, string $password, string $fullName): bool|string {
    $db = getDB();
    if (strlen($username) < 3 || strlen($username) > 30)
        return 'Username must be 3–30 characters.';
    if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $username))
        return 'Username may only contain letters, digits, _ and .';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return 'Invalid email address.';
    if (strlen($password) < 6)
        return 'Password must be at least 6 characters.';
    if (strlen($fullName) < 2)
        return 'Please enter your full name.';
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) return 'Username or email already taken.';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)")
       ->execute([$username, $email, $hash, $fullName]);
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['user_id']  = (int)$db->lastInsertId();
    $_SESSION['username'] = $username;
    return true;
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

function unreadMessages(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE to_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
