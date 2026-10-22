<?php
// =====================================================================
// login.php - admin login form. On success the admin is sent to the page
// they were trying to open (Price Update or Sales Report).
// =====================================================================
require_once 'db.php';   // also loads auth.php (starts the session)

// Already logged in? Go straight to the admin area.
if (is_admin()) {
    header('Location: admin_price_update.php');
    exit;
}

// Only these pages may be used as the "go back to" target (prevents open redirects).
$allowed = ['admin_price_update.php', 'admin_sales_report.php'];
$next = $_POST['next'] ?? $_GET['next'] ?? 'admin_price_update.php';
if (!in_array($next, $allowed, true)) $next = 'admin_price_update.php';

// CSRF token: proves the form was submitted from this site.
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Session expired. Please try again.';
    } else {
        try {
            $st = get_db()->prepare('SELECT admin_id, password_hash FROM admins WHERE username = ?');
            $st->execute([$user]);
            $row = $st->fetch();

            if ($row && password_verify($pass, $row['password_hash'])) {
                session_regenerate_id(true);            // new session id after login
                $_SESSION['admin_id'] = (int)$row['admin_id'];
                header('Location: ' . $next);
                exit;
            }
            $error = 'Invalid username or password.';
        } catch (Throwable $e) {
            $error = 'Login is unavailable. Run setup_admin.php once and check db.php.';
        }
    }
}

page_header('Login');
?>
<style>
.login_table td {padding: 6px; text-align: left;}
.err {color: #a00000; font-weight: bold;}
</style>

<h1> Admin Login </h1>

<?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>

<form method="post" action="login.php">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
<input type="hidden" name="next" value="<?= h($next) ?>">
<table class="login_table">
<tr><td>Username:</td><td><input type="text" name="username" required autofocus></td></tr>
<tr><td>Password:</td><td><input type="password" name="password" required></td></tr>
</table>
<button type="submit">Log In</button>
</form>

<?php page_footer(); ?>
