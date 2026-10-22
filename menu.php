<?php
// =====================================================================
// menu.php - the customer Menu page (replaces menu.html).
// Reads coffees and prices from the database, shows a quantity box per coffee,
// calculates totals live (menu_calculation.js) and has a Check-Out button.
// =====================================================================
require_once 'db.php';          // database connection + helper functions
$error = null;                  // holds a message if the database cannot be read
$menu  = [];                    // list of products and their options
try {
    $menu = get_menu();   // prices come from the database (see admin_price_update.php)
} catch (Throwable $e) {
    $error = 'The menu is temporarily unavailable. Please check the database connection in db.php.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title> JavaJam - Menu </title>
	<meta charset="utf-8">
	<link rel="stylesheet" href="site.css">

	<style>
		tr:nth-of-type(odd) {background-color:#CDAB61;}
		.td_text {
					padding : 5px 5px 5px 5px;
					text-align: left;
		}
		td 	{
				padding : 0;
				text-align: center;
			}
		th {padding : 5px 5px 5px 5px;}
		.price {
				padding: 5px 5px 5px 5px;
				text-align: center;
				}
		#checkout_area {text-align: center; margin-top: 15px;}
		#checkout_btn  {padding: 6px 20px; font-size: 100%;}
		#checkout_msg  {margin-top: 10px; font-weight: bold;}
		.msg_ok  {color: #2a6a1f;}
		.msg_err {color: #a00000;}
	</style>
	<script type="text/javascript" src="menu_calculation.js"></script>
</head>
<body>

<div id="wrapper">
	<header>
	</header>
	<div id="leftcolumn">
		<nav>
			<a href="index.html">Home</a>
			<a href="menu.php">Menu</a>
			<a href="music.html">Music</a>
			<a href="jobs.html">Jobs</a>
			<a href="login.php">Login</a>
		</nav>
	</div>
	<div id="rightcolumn">
		<h1> Coffee at JavaJam </h1>
<?php /* If the database failed, show the error instead of the menu */ ?>
<?php if ($error): ?>
		<p class="msg_err"><?= h($error) ?></p>
<?php else: ?>
		<table id="menu_table" width="600px" align="center" border="1">
			<tr>
				<th colspan="2"></th>
				<th>Q</th>
				<th width="70px">Price</th>
			</tr>
<?php /* One table row per coffee. data-product-id lets the JavaScript identify the row */ ?>
<?php foreach ($menu as $p): $pid = $p['product_id']; $opts = $p['options']; ?>
			<tr class="product_row" data-product-id="<?= $pid ?>">
				<td class="td_text"> <?= h($p['name']) ?> </td>
				<td class="td_text"> <?= h($p['description']) ?> <br>
<?php /* Only one option (Just Java): show its price as text + a hidden input holding its id and price */ ?>
<?php if (count($opts) === 1): $o = $opts[0]; ?>
					<?= h($o['option_label']) ?> $<?= number_format((float)$o['price'], 2) ?>
					<input type="hidden" class="opt" name="opt_<?= $pid ?>" value="<?= $o['option_id'] ?>" data-price="<?= h($o['price']) ?>">
<?php /* Several options (Single/Double): one radio button each; the first is pre-selected */ ?>
<?php else: foreach ($opts as $i => $o): ?>
					<label><input type="radio" class="opt" name="opt_<?= $pid ?>" value="<?= $o['option_id'] ?>" data-price="<?= h($o['price']) ?>"<?= $i === 0 ? ' checked' : '' ?>>
					<?= h($o['option_label']) ?> $<?= number_format((float)$o['price'], 2) ?></label>
<?php endforeach; endif; ?>
				</td>
				<td><input type="text" class="qty" size="1" maxlength="2"></td>
				<td class="price subtotal"></td>
			</tr>
<?php endforeach; ?>
<?php /* Last row: grand total, filled in by JavaScript */ ?>
			<tr>
				<th colspan="2"></th>
				<th><big>Total</big></th>
				<th class="price" id="maintotal"></th>
			</tr>
		</table>

<?php /* Check-Out button + area where success/error message appears */ ?>
		<div id="checkout_area">
			<button type="button" id="checkout_btn">Check-Out</button>
			<div id="checkout_msg"></div>
		</div>
<?php endif; ?>
	</div>
	<footer>
	<p> Copyright &copy; 2014 JavaJam Coffee House<br>
	<a href="mailto:stevenloekita@harsono.com">stevenloekita@harsono.com </a></p>
	</footer>
</div>

</body>
</html>
