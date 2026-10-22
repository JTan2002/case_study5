<?php
require_once 'db.php';
$ins = get_db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?,?,?)');
$ins->execute(['admin', password_hash('ChangeMe123!', PASSWORD_DEFAULT), 'admin']);
$ins->execute(['customer', password_hash('Customer123!', PASSWORD_DEFAULT), 'user']);
echo 'Users created. Delete this file now.';