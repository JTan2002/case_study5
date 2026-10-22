<?php
require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = get_db()->prepare('SELECT user_id, username, password_hash, role FROM users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();

    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);             // prevents session fixation
        $_SESSION['user_id']  = (int)$u['user_id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['role']     = $u['role'];

        // Only allow redirecting to the two admin pages (no open redirect)
        $allowed = ['admin_price_update.php', 'admin_sales_report.php'];
        $next = $_GET['next'] ?? '';
        header('Location: ' . (in_array($next, $allowed, true) ? $next : 'menu.php'));
        exit;
    }
    $error = 'Invalid username or password.';
}

page_header('Login');
?>
<h1>Login</h1>
<?php if ($error): ?><p style="color:#a00000;font-weight:bold"><?= h($error) ?></p><?php endif; ?>
<form method="post">
  <p><label>Username: <input type="text" name="username" required></label></p>
  <p><label>Password: <input type="password" name="password" required></label></p>
  <p><button type="submit">Log in</button></p>
</form>
<?php page_footer(); ?>