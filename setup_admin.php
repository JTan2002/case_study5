<?php
// =====================================================================
// setup_admin.php - RUN ONCE in the browser, then DELETE THIS FILE.
// Creates the "admins" table and the first admin account.
// Change the username/password below BEFORE running it.
// =====================================================================
require_once 'db.php';

const NEW_ADMIN_USER = 'admin';
const NEW_ADMIN_PASS = 'ChangeMe123!';   // <-- change this

try {
    $pdo = get_db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL
    )');

    if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        echo 'An admin already exists. Delete setup_admin.php.';
        exit;
    }
    $st = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $st->execute([NEW_ADMIN_USER, password_hash(NEW_ADMIN_PASS, PASSWORD_DEFAULT)]);
    echo 'Admin "' . h(NEW_ADMIN_USER) . '" created. Now DELETE setup_admin.php.';
} catch (Throwable $e) {
    echo 'Setup failed: ' . h($e->getMessage());
}
