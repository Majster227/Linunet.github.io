<?php
// Start session exactly once, here, for the whole app
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_FILE', __DIR__ . '/data/linunet.db');
define('UPLOADS_DIR', __DIR__ . '/uploads/avatars/');
define('POSTS_DIR', __DIR__ . '/uploads/posts/');
define('SITE_NAME', 'Linunet');
define('SITE_TAGLINE', 'The network that connects people who think different.');
define('ADMIN_EMAIL', 'admin@linunet.local');

function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        foreach ([__DIR__.'/data', UPLOADS_DIR, POSTS_DIR] as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDB($db);
    }
    return $db;
}

function initDB(PDO $db): void {
    $db->exec("PRAGMA journal_mode=WAL;");
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        full_name TEXT NOT NULL,
        bio TEXT DEFAULT '',
        avatar TEXT DEFAULT 'default.png',
        favorite_os TEXT DEFAULT '',
        interests TEXT DEFAULT '',
        birthdate TEXT DEFAULT '',
        post_privacy TEXT DEFAULT 'public',
        is_admin INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        image TEXT DEFAULT '',
        link TEXT DEFAULT '',
        privacy TEXT DEFAULT 'public',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS friends (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        friend_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, friend_id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_id INTEGER NOT NULL,
        to_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_id) REFERENCES users(id),
        FOREIGN KEY (to_id) REFERENCES users(id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reporter_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        target_id INTEGER NOT NULL,
        reason TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed admin account
    $check = $db->query("SELECT id FROM users WHERE username='admin'")->fetch();
    if (!$check) {
        $hash = password_hash('Linunet2024!', PASSWORD_BCRYPT);
        $db->prepare("INSERT INTO users (username,email,password_hash,full_name,bio,is_admin) VALUES (?,?,?,?,?,1)")
           ->execute(['admin','admin@linunet.local',$hash,'Administrator','Linunet system administrator.']);
    }
}
