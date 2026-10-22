<?php
// =====================================================================
// admin_price_update.php - Case Study 5(i).
// Flow: tick a coffee -> type its new price(s) -> click Update -> prices are saved
// to the database -> prices are read back and shown on this same page for confirmation.
// The same file shows the form (GET) and handles the submit (POST).
// =====================================================================
require_once 'db.php';

$messages = [];   // success lines, e.g. "Just Java: $2.00 -> $2.50"
$errors   = [];   // problems to show in red

try {
    $pdo = get_db();

    // ---- PART A: runs only when the form was submitted ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Which blends were ticked, and what prices were typed
        $selected = isset($_POST['select']) && is_array($_POST['select']) ? array_keys($_POST['select']) : [];
        $prices   = isset($_POST['price'])  && is_array($_POST['price'])  ? $_POST['price'] : [];

        if (count($selected) === 0) {
            $errors[] = 'Please tick at least one coffee blend to update.';
        } else {
            // Load the options that belong to the TICKED products only, so nothing else can be changed.
            // (A user cannot update a product they did not tick, even by editing the form.)
            $in  = implode(',', array_fill(0, count($selected), '?'));
            $st  = $pdo->prepare("SELECT o.option_id, o.option_label, o.price, p.name
                                  FROM product_options o JOIN products p ON p.product_id = o.product_id
                                  WHERE o.product_id IN ($in)");
            $st->execute(array_map('intval', $selected));
            $valid = [];
            foreach ($st as $r) $valid[(int)$r['option_id']] = $r;

            // Check each typed price: blank = keep old price; otherwise must look like 2 or 2.50 and be > 0
            $updates = [];
            foreach ($valid as $oid => $r) {
                $raw = isset($prices[$oid]) ? trim((string)$prices[$oid]) : '';
                if ($raw === '') continue;                         // left blank = keep current price
                if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $raw) || (float)$raw <= 0) {
                    $errors[] = h($r['name'] . ' (' . $r['option_label'] . ')') . ': "' . h($raw) . '" is not a valid price.';
                    continue;
                }
                $updates[$oid] = [number_format((float)$raw, 2, '.', ''), $r];
            }

            // Ticked a blend but typed nothing?
            if (!$errors && count($updates) === 0) {
                $errors[] = 'Please enter a new price for at least one ticked blend.';
            }

            // ---- Save to the database (all changes together, or none) ----
            if (!$errors) {
                $pdo->beginTransaction();
                $up = $pdo->prepare('UPDATE product_options SET price = ? WHERE option_id = ?');
                foreach ($updates as $oid => [$newPrice, $r]) {
                    $up->execute([$newPrice, $oid]);
                    $messages[] = h($r['name'] . ' (' . $r['option_label'] . ')') . ': $' .
                                  number_format((float)$r['price'], 2) . ' &rarr; $' . number_format((float)$newPrice, 2);
                }
                $pdo->commit();
            }
        }
    }

    // ---- PART B: always read the prices from the database to display them ----
    $menu = get_menu();   // always read back from the DB, so the page shows the saved prices
} catch (Throwable $e) {
    // Any database problem: undo partial changes and show a friendly message
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $menu = [];
    $errors[] = 'Database error. Please check the connection settings in db.php.';
}

// ---- PART C: draw the page (header, messages, form) ----
page_header('Price Update');
?>
		<style>
			.admin_table {border-collapse: collapse; width: 100%;}
			.admin_table td, .admin_table th {padding: 6px; text-align: left; vertical-align: top;}
			.admin_table tr:nth-of-type(odd) {background-color: #CDAB61;}
			.new_price {display: none; margin-top: 4px;}
			.new_price.on {display: block;}
			.new_price input {width: 70px;}
			.ok  {color: #2a6a1f; font-weight: bold;}
			.err {color: #a00000; font-weight: bold;}
		</style>
		<h1> Click to update product prices: </h1>

<?php /* Green box listing what was changed (only after a successful update) */ ?>
<?php if ($messages): ?>
		<div class="ok">Prices updated successfully:
			<ul><?php foreach ($messages as $m): ?><li><?= $m ?></li><?php endforeach; ?></ul>
		</div>
<?php endif; ?>
<?php /* Red box listing any problems */ ?>
<?php if ($errors): ?>
		<div class="err"><ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

		<form method="post" action="admin_price_update.php">
		<table class="admin_table">
<?php /* One row per coffee: checkbox | name | description + CURRENT prices + (hidden) new-price boxes */ ?>
<?php foreach ($menu as $p): $pid = $p['product_id']; ?>
			<tr>
				<td width="30"><input type="checkbox" name="select[<?= $pid ?>]" value="1" class="pick" data-target="np_<?= $pid ?>"></td>
				<td width="120"><b><?= h($p['name']) ?></b></td>
				<td>
					<?= h($p['description']) ?><br>
					<?php
					$parts = [];
					foreach ($p['options'] as $o) $parts[] = h($o['option_label']) . ' $' . number_format((float)$o['price'], 2);
					echo implode(' &nbsp; ', $parts);
					?>
					<div class="new_price" id="np_<?= $pid ?>">
						New price:
						<?php foreach ($p['options'] as $o): ?>
							<label><?= h($o['option_label']) ?> $
							<input type="text" name="price[<?= $o['option_id'] ?>]" size="5" maxlength="7" disabled></label>&nbsp;
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
<?php endforeach; ?>
		</table>
		<p><button type="submit">Update Prices</button></p>
		</form>

		<?php /* Small script: ticking a checkbox reveals that coffee's price boxes */ ?>
		<script>
			// Show (and enable) the price inputs only for ticked blends; disabled inputs are not submitted.
			document.querySelectorAll('.pick').forEach(function (cb) {
				cb.addEventListener('change', function () {
					var box = document.getElementById(cb.dataset.target);
					box.classList.toggle('on', cb.checked);
					box.querySelectorAll('input').forEach(function (i) { i.disabled = !cb.checked; });
				});
			});
		</script>
<?php page_footer(); ?>
