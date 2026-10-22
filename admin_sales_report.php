<?php
// =====================================================================
// admin_sales_report.php - Case Study 5(ii).
// Builds a daily sales report from the saved customer orders. Choose a date and tick
// which parts you want: sales by product, sales by category, popular option of best seller.
// =====================================================================
require_once 'db.php';

// ---- Step 1: read the chosen date (default today); reject anything that is not a real date ----
$date = $_GET['date'] ?? date('Y-m-d');
$dt   = DateTime::createFromFormat('Y-m-d', $date);
if (!$dt || $dt->format('Y-m-d') !== $date) { $date = date('Y-m-d'); $dt = new DateTime($date); }

// ---- Step 2: which button/boxes were used? ----
$submitted = isset($_GET['generate']);   // true after "Generate Report" is clicked
$wantProd  = isset($_GET['by_product']);
$wantCat   = isset($_GET['by_category']);
$wantPop   = isset($_GET['popular']);

// Report window = from 00:00 of the chosen day up to (not including) 00:00 of the next day
$start = $dt->format('Y-m-d 00:00:00');
$end   = (clone $dt)->modify('+1 day')->format('Y-m-d 00:00:00');

$errors = [];
$byProduct = $byCategory = [];
$popular = null;

// Must tick at least one report option
if ($submitted && !($wantProd || $wantCat || $wantPop)) {
    $errors[] = 'Please tick at least one report option.';
}

// ---- Step 3: query the database and prepare the numbers ----
if ($submitted && !$errors) {
    try {
        $pdo = get_db();

        // One query gives dollars + quantity for every product/option on the chosen day.
        // LEFT JOIN keeps products with no sales (shown as 0).
        $sql = 'SELECT p.product_id, p.name, o.option_name,
                       COALESCE(SUM(x.quantity), 0)   AS qty,
                       COALESCE(SUM(x.line_total), 0) AS dollars
                FROM products p
                JOIN product_options o ON o.product_id = p.product_id
                LEFT JOIN (SELECT oi.option_id, oi.quantity, oi.line_total
                           FROM order_items oi JOIN orders od ON od.order_id = oi.order_id
                           WHERE od.order_date >= ? AND od.order_date < ?) x ON x.option_id = o.option_id
                GROUP BY p.product_id, p.name, o.option_id, o.option_name
                ORDER BY p.product_id, o.price';
        $st = $pdo->prepare($sql);
        $st->execute([$start, $end]);
        $rows = $st->fetchAll();

        // Add the rows up two ways: per product, and per category (Null/Single/Double)
        $byProduct = [];                                   // name => [dollars, qty, options => [name => [qty,dollars]]]
        $byCategory = ['Null' => [0.0, 0], 'Single' => [0.0, 0], 'Double' => [0.0, 0]];
        foreach ($rows as $r) {
            $n = $r['name'];
            if (!isset($byProduct[$n])) $byProduct[$n] = ['dollars' => 0.0, 'qty' => 0, 'options' => []];
            $byProduct[$n]['dollars'] += (float)$r['dollars'];
            $byProduct[$n]['qty']     += (int)$r['qty'];
            $byProduct[$n]['options'][$r['option_name']] = ['qty' => (int)$r['qty'], 'dollars' => (float)$r['dollars']];
            $byCategory[$r['option_name']][0] += (float)$r['dollars'];
            $byCategory[$r['option_name']][1] += (int)$r['qty'];
        }

        // Best selling product = highest dollar sales; its popular option = highest quantity sold
        $best = null;
        foreach ($byProduct as $n => $d) {
            if ($d['qty'] > 0 && ($best === null || $d['dollars'] > $byProduct[$best]['dollars'])) $best = $n;
        }
        if ($best !== null) {
            $bestOpt = null; $bestQty = -1;
            foreach ($byProduct[$best]['options'] as $on => $d) {
                if ($d['qty'] > $bestQty) { $bestQty = $d['qty']; $bestOpt = $on; }
            }
            $popular = ['product' => $best, 'option' => $bestOpt];
        }
    } catch (Throwable $e) {
        $errors[] = 'Database error. Please check the connection settings in db.php.';
    }
}

// Formats a number as dollars, e.g. 152.5 -> $152.50
function money($v): string { return '$' . number_format((float)$v, 2); }

// ---- Step 4: draw the page ----
page_header('Daily Sales Report');
?>
		<style>
			.rpt {border-collapse: collapse; margin: 10px 0 20px 0; min-width: 400px;}
			.rpt th, .rpt td {border: 1px solid #7a6330; padding: 5px 10px; text-align: left;}
			.rpt th {background-color: #CDAB61;}
			.err {color: #a00000; font-weight: bold;}
			.detail {font-size: 90%; margin: -10px 0 15px 0;}
		</style>
		<h1> Click to generate daily sales report: </h1>

<?php /* Form: date + 3 checkboxes (all ticked by default on first visit). Uses GET so the report URL can be bookmarked */ ?>
		<form method="get" action="admin_sales_report.php">
			<p>Date: <input type="date" name="date" value="<?= h($date) ?>"></p>
			<p><label><input type="checkbox" name="by_product"  <?= (!$submitted || $wantProd) ? 'checked' : '' ?>> Total dollar and quantity sales by products</label></p>
			<p><label><input type="checkbox" name="by_category" <?= (!$submitted || $wantCat)  ? 'checked' : '' ?>> Total dollar and quantity sales by categories</label></p>
			<p><label><input type="checkbox" name="popular"     <?= (!$submitted || $wantPop)  ? 'checked' : '' ?>> Popular option of best selling product</label></p>
			<p><button type="submit" name="generate" value="1">Generate Report</button></p>
		</form>

<?php if ($errors): ?>
		<div class="err"><ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php /* Report output - only shown after a valid submit */ ?>
<?php if ($submitted && !$errors): ?>
		<h2> Report for <?= h($dt->format('d M Y')) ?> </h2>

<?php /* Table 1: total dollars and quantity per product */ ?>
<?php if ($wantProd): ?>
		<h3> Sales by product </h3>
		<table class="rpt">
			<tr><th>Product</th><th>Total Dollar Sales</th><th>Quantity Sales</th></tr>
<?php foreach ($byProduct as $n => $d): ?>
			<tr><td><?= h($n) ?></td><td><?= money($d['dollars']) ?></td><td><?= $d['qty'] ?></td></tr>
<?php endforeach; ?>
		</table>
<?php endif; ?>

<?php /* Table 2: totals per category, plus a breakdown line for each coffee with options */ ?>
<?php if ($wantCat): ?>
		<h3> Sales by categories </h3>
		<table class="rpt">
			<tr><th>Category</th><th>Total Dollar Sales</th><th>Quantity Sales</th></tr>
<?php foreach ($byCategory as $c => [$dollars, $qty]): ?>
			<tr><td><?= h($c) ?></td><td><?= money($dollars) ?></td><td><?= $qty ?></td></tr>
<?php endforeach; ?>
		</table>
		<div class="detail">
<?php foreach ($byProduct as $n => $d): if (count($d['options']) < 2) continue; ?>
			<?= h($n) ?> &ndash;
			<?php
			$bits = [];
			foreach ($d['options'] as $on => $od) $bits[] = $od['qty'] . ' ' . h($on) . ' (' . money($od['dollars']) . ')';
			echo implode(', ', $bits);
			?><br>
<?php endforeach; ?>
		</div>
<?php endif; ?>

<?php /* Result 3: option with highest quantity, of the product with highest dollar sales */ ?>
<?php if ($wantPop): ?>
		<h3> Popular option of best selling product </h3>
<?php if ($popular): ?>
		<p>Popular (highest quantity sold) option of the best selling (highest $) product is
			<b><?= h($popular['option']) ?></b> of <b><?= h($popular['product']) ?></b>.</p>
<?php else: ?>
		<p>No sales recorded on this date.</p>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php page_footer(); ?>
