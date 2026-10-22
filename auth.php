<?php
// =====================================================================
// auth.php - admin login helpers (session based).
// Loaded by db.php, so every PHP page already has the session started.
// =====================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// True when an admin has logged in during this session.
function is_admin(): bool {
    return !empty($_SESSION['admin_id']);
}

// Put this at the very top of any admin-only page (before any output).
// Visitors who are not logged in are sent to the login page.
function require_admin(): void {
    if (!is_admin()) {
        header('Location: login.php?next=' . urlencode(basename($_SERVER['PHP_SELF'])));
        exit;
    }
}
