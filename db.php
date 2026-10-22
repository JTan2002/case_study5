<?php
// =====================================================================
// db.php - shared by every PHP page.
// Holds: (1) the database connection, (2) small helper functions,
//        (3) the page header/footer so admin pages match the site design.
// Other files load it with:  require_once 'db.php';
// =====================================================================

// ---- Database login details: change these to match your MySQL / XAMPP / WAMP setup ----
const DB_HOST = 'localhost';   // where MySQL runs
const DB_NAME = 'javajam';     // database created by schema.sql
const DB_USER = 'root';        // XAMPP default user
const DB_PASS = '';            // XAMPP default password is empty
// -----------------------------------------------------------------------------------------

// Opens the connection ONCE and re-uses it on later calls ("static" keeps the value).
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // DSN = text that tells PDO which database server/name to use
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw errors so try/catch can handle them
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows come back as ['column' => value]
            PDO::ATTR_EMULATE_PREPARES   => false,                  // use real prepared statements (safer)
        ]);
    }
    return $pdo;
}

// Makes text safe to print inside HTML (stops "<script>" etc. from running). Use on any DB/user text.
function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Reads every product and its options (Single/Double/...) from the database.
// Result shape:
//   [ product_id => ['product_id'=>..,'name'=>..,'description'=>..,'options'=>[ {option rows} ]] ]
function get_menu(): array {
    // JOIN = combine each product with its rows in product_options
    $sql = 'SELECT p.product_id, p.name, p.description,
                   o.option_id, o.option_name, o.option_label, o.price
            FROM products p
            JOIN product_options o ON o.product_id = p.product_id
            ORDER BY p.product_id, o.price';
    $menu = [];
    foreach (get_db()->query($sql) as $r) {
        $pid = (int)$r['product_id'];
        // First time we see this product: create its entry
        if (!isset($menu[$pid])) {
            $menu[$pid] = [
                'product_id'  => $pid,
                'name'        => $r['name'],
                'description' => $r['description'],
                'options'     => [],
            ];
        }
        // Every row is one option of that product: add it to the list
        $menu[$pid]['options'][] = [
            'option_id'    => (int)$r['option_id'],
            'option_name'  => $r['option_name'],
            'option_label' => $r['option_label'],
            'price'        => $r['price'],
        ];
    }
    return $menu;
}

// Prints the top of a page (logo, left navigation, opening of the right column).
// Used by the two admin pages so they look like the other pages.
function page_header(string $title): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title> JavaJam - <?= h($title) ?> </title>
	<meta charset="utf-8">
	<link rel="stylesheet" href="site.css">
</head>
<body>
<div id="wrapper">
	<header></header>
	<div id="leftcolumn">
		<nav>
			<a href="index.html">Home</a>
			<a href="menu.php">Menu</a>
			<a href="music.html">Music</a>
			<a href="jobs.html">Jobs</a>
			<a href="admin_price_update.php">Price Update</a>
			<a href="admin_sales_report.php">Sales Report</a>
		</nav>
	</div>
	<div id="rightcolumn">
<?php }

// Prints the bottom of a page (footer + closing tags).
function page_footer(): void { ?>
	</div>
	<footer>
	<p> Copyright &copy; 2014 JavaJam Coffee House<br>
	<a href="mailto:stevenloekita@harsono.com">stevenloekita@harsono.com </a></p>
	</footer>
</div>
</body>
</html>
<?php }
