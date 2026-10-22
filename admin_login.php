<?php
// =====================================================================
// admin_login.php - login page for the admin-only pages. Also handles log out.
// =====================================================================
require_once 'db.php';
require_once 'auth.php';
start_admin_session();

// Pages the login is allowed to send the admin back to (stops "redirect to anywhere" tricks)
$allowed = ['admin_sales_report.php', 'admin_price_update.php'];
$target  = $_SESSION['after_login'] ?? '';
if (!in_array($target, $allowed, true)) $target = 'admin_sales_report.php';

$error  = '';
$notice = isset($_GET['out']) ? 'You have been logged out.' : '';

// ---- Log out (a POST from the button on the admin pages) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    if (csrf_ok()) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $c = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
        }
        session_destroy();
    }
    header('Location: admin_login.php?out=1');
    exit;
}

// ---- Already logged in? Go straight to the page ----
if (is_admin()) {
    header('Location: ' . $target);
    exit;
}

// ---- Log in ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $now   = time();
    // Keep only the failed attempts from the last 5 minutes
    $fails = array_values(array_filter($_SESSION['fails'] ?? [], fn($t) => $t > $now - 300));

    if (!csrf_ok()) {
        $error = 'Your session expired. Try again.';
    } elseif (count($fails) >= 5) {
        $error = 'Too many failed attempts. Wait a few minutes and try again.';
    } else {
        $u = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
        $p = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

        // Check both every time, so the response time does not reveal which one was wrong
        $okUser = hash_equals(ADMIN_USER, $u);
        $okPass = password_verify($p, ADMIN_PASS_HASH);

        if ($okUser && $okPass) {
            session_regenerate_id(true);          // new session id after login (stops session fixation)
            $_SESSION['is_admin'] = true;
            unset($_SESSION['fails'], $_SESSION['after_login']);
            header('Location: ' . $target);
            exit;
        }
        $fails[] = $now;
        usleep(400000);                           // small delay makes guessing slower
        $error = 'Wrong username or password.';
    }
    $_SESSION['fails'] = $fails;
}

page_header('Admin Login');
?>
<style>
  .login {max-width: 380px; margin: 20px auto; color: #3b2f12;}
  .login h1 {font-size: 1.5em; margin: 0 0 6px 0;}
  .login .intro {color: #6b5a2a; font-size: 90%; margin: 0 0 16px 0;}
  .login form {background: #fffdf3; border: 1px solid #d9c98a; border-radius: 10px; padding: 16px 20px;}
  .login label {display: block; font-size: 90%; margin: 10px 0 3px 0;}
  .login input[type=text], .login input[type=password] {width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #b99a4a; border-radius: 6px; background: #fff; font-size: 100%;}
  .login input:focus {outline: 0; border-color: #7a6330; box-shadow: 0 0 0 2px #f3e3a6;}
  .login .btn {margin-top: 16px; width: 100%; background: #7a6330; color: #fffdf3; border: 0; border-radius: 6px; padding: 9px 20px; font-size: 100%; cursor: pointer;}
  .login .btn:hover {background: #5a4818;}
  .login .err {background: #fdecec; border: 1px solid #e0a0a0; color: #a00000; font-weight: bold; border-radius: 8px; padding: 8px 14px; margin: 0 0 12px 0;}
  .login .ok  {background: #eef7e6; border: 1px solid #9cc77d; color: #2a6a1f; border-radius: 8px; padding: 8px 14px; margin: 0 0 12px 0;}
</style>

<div class="login">
  <h1> Admin login </h1>
  <p class="intro">This page is for JavaJam staff only. Log in to continue.</p>

  <?php if ($notice): ?><div class="ok"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="err"><?= h($error) ?></div><?php endif; ?>

  <form method="post" action="admin_login.php" autocomplete="on">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" autofocus required autocomplete="username">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <button type="submit" name="login" value="1" class="btn">Log in</button>
  </form>
</div>

<?php page_footer(); ?>