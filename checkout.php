<?php
// =====================================================================
// checkout.php - saves a customer's order when "Check-Out" is clicked.
// Called by menu_calculation.js (not opened directly by the user).
// Receives JSON like: {"items":[{"option_id":2,"quantity":3}, ...]}
// Replies with JSON:  {"ok":true,"order_id":7,"total":"12.50"}  or  {"ok":false,"error":"..."}
// Prices are ALWAYS looked up in the database - the browser's prices are never trusted.
// =====================================================================
require_once 'db.php';
header('Content-Type: application/json');   // tell the browser the reply is JSON

// Helper: send an error reply and stop the script.
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// ---- Step 1: only accept POST requests ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required.', 405);

// ---- Step 2: read the JSON the browser sent and make sure it has items ----
$data  = json_decode(file_get_contents('php://input'), true);
$items = is_array($data) ? ($data['items'] ?? null) : null;
if (!is_array($items) || count($items) === 0) fail('Your order is empty.');

// ---- Step 3: validate each item (whole numbers, quantity 1-99) and merge duplicates ----
$wanted = [];   // option_id => total quantity
foreach ($items as $it) {
    $oid = filter_var($it['option_id'] ?? null, FILTER_VALIDATE_INT);
    $qty = filter_var($it['quantity']  ?? null, FILTER_VALIDATE_INT);
    if ($oid === false || $qty === false || $oid < 1 || $qty < 1 || $qty > 99) {
        fail('Invalid item in order.');
    }
    $wanted[$oid] = ($wanted[$oid] ?? 0) + $qty;
}

try {
    $pdo = get_db();
    // A transaction = all inserts succeed together, or none are saved
    $pdo->beginTransaction();

    // ---- Step 4: look up the real price of each chosen option and work out line totals ----
    $find = $pdo->prepare('SELECT product_id, price FROM product_options WHERE option_id = ?');
    $lines = [];       // rows to insert into order_items
    $total = 0.0;      // whole-order total
    foreach ($wanted as $oid => $qty) {
        $find->execute([$oid]);
        $opt = $find->fetch();
        if (!$opt) { $pdo->rollBack(); fail('Unknown menu option.'); }
        $line = round($qty * (float)$opt['price'], 2);
        $total += $line;
        $lines[] = [$opt['product_id'], $oid, $qty, $opt['price'], $line];
    }

    // ---- Step 5: save the order header (one row in "orders") ----
    $pdo->prepare('INSERT INTO orders (total) VALUES (?)')->execute([number_format($total, 2, '.', '')]);
    $orderId = (int)$pdo->lastInsertId();   // id MySQL just created

    // ---- Step 6: save each order line (rows in "order_items"), keeping the price at time of sale ----
    $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, option_id, quantity, unit_price, line_total)
                          VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($lines as $l) {
        $ins->execute([$orderId, $l[0], $l[1], $l[2], $l[3], number_format($l[4], 2, '.', '')]);
    }
    $pdo->commit();   // make everything permanent

    // ---- Step 7: tell the browser it worked ----
    echo json_encode(['ok' => true, 'order_id' => $orderId, 'total' => number_format($total, 2)]);
} catch (Throwable $e) {
    // Anything unexpected: undo partial inserts and send a generic error
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fail('Could not save your order. Please try again.', 500);
}
