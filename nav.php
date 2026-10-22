<?php
// nav.php - the left navigation, shared by every PHP page.
// Admin links only appear after logging in.
require_once __DIR__ . '/auth.php';
?>
<nav>
<a href="index.html">Home</a>
<a href="menu.php">Menu</a>
<a href="music.html">Music</a>
<a href="jobs.html">Jobs</a>
<?php if (is_admin()): ?>
<a href="admin_price_update.php">Price Update</a>
<a href="admin_sales_report.php">Sales Report</a>
<a href="logout.php">Logout</a>
<?php else: ?>
<a href="login.php">Login</a>
<?php endif; ?>
</nav>
