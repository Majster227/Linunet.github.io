#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';
$username = $argv[1] ?? '';
if (!$username) { echo "Usage: php make_admin.php <username>\n"; exit(1); }
$db = getDB();
$db->prepare("UPDATE users SET is_admin=1 WHERE username=?")->execute([$username]);
echo $db->query("SELECT changes()")->fetchColumn() > 0
    ? "✓ '$username' is now an admin.\n"
    : "✗ User '$username' not found.\n";
