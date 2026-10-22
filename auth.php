<?php
// =====================================================================
// auth.php - admin login helpers, shared by the admin pages.
// Usage at the very top of a protected page (before any HTML is printed):
//     require_once 'db.php';
//     require_once 'auth.php';
//     require_admin();
// =====================================================================

// ---- Admin account: change these ----
// The password is stored as a HASH, never as plain text.
// Default login:  username "admin"  /  password "Java#Admin2026"   (CHANGE IT - see README note)
// To make a new hash, run this in a terminal:
//     php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"
const ADMIN_USER      = 'admin';
const ADMIN_PASS_HASH = '$2b$12$4LgwPALWo0dOgIqjtIAugeDkZEEWJSQVwAwHnH80SKSjs79U24Xmq';
// -------------------------------------

// Starts the session once, with safer cookie settings.
function start_admin_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,                          // cookie ends when the browser closes
            'path'     => '/',
            'httponly' => true,                       // JavaScript cannot read the cookie
            'samesite' => 'Lax',                      // not sent on cross-site POSTs
            'secure'   => !empty($_SERVER['HTTPS']),  // https only when the site uses https
        ]);
        session_start();
    }
}

// True when the visitor has logged in as admin.
function is_admin(): bool {
    start_admin_session();
    return !empty($_SESSION['is_admin']);
}

// Put this at the top of any page only the admin may see.
// Not logged in -> sent to the login page, and brought back here afterwards.
function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['after_login'] = basename($_SERVER['SCRIPT_NAME']);
        header('Location: admin_login.php');
        exit;
    }
    // Stop the browser keeping a copy, so "Back" after logout can't show the report
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}

// One-time token that proves a form really came from our own page.
function csrf_token(): string {
    start_admin_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_ok(): bool {
    start_admin_session();
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}