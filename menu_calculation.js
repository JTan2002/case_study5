// =====================================================================
// menu_calculation.js - runs in the browser on menu.php.
// Does two things: (1) live price calculation as the user types,
//                  (2) sends the order to checkout.php when "Check-Out" is clicked.
// Unit prices come from the data-price attributes that PHP wrote from the database,
// so a price changed on the admin page appears here automatically.
// =====================================================================

// Quantity must be empty or 1-2 digits (no letters, no decimals)
var QTY_PATTERN = /^\s*\d{0,2}\s*$/;

// Finds which option (Single/Double/fixed) is chosen in one product row.
// Single-option products use a hidden input; others use radio buttons.
function selectedOption(row) {
	return row.querySelector('input.opt[type="hidden"], input.opt:checked');
}

// Works out the cost of ONE product row and writes it into that row's price cell.
// Returns {total, qty, valid} so callers can add up the totals.
function updateRow(row) {
	var qtyBox = row.querySelector('.qty');
	var cell   = row.querySelector('.subtotal');

	// Invalid quantity -> show "Error"
	if (!QTY_PATTERN.test(qtyBox.value)) {
		cell.textContent = 'Error';
		return {total: 0, qty: 0, valid: false};
	}
	var qty = Number(qtyBox.value) || 0;                       // empty box counts as 0
	var opt = selectedOption(row);
	var total = qty * Number(opt.getAttribute('data-price'));  // quantity x unit price
	cell.textContent = '$' + total.toFixed(2);
	return {total: total, qty: qty, valid: true};
}

// Recalculates every row and the grand total at the bottom of the table.
function calculatePrice() {
	var rows = document.querySelectorAll('#menu_table .product_row');
	var grand = 0, allValid = true;
	for (var i = 0; i < rows.length; i++) {
		var r = updateRow(rows[i]);
		grand += r.total;
		if (!r.valid) allValid = false;
	}
	document.getElementById('maintotal').textContent = allValid ? '$' + grand.toFixed(2) : 'Error';
}

// Shows a green (ok) or red (error) message under the Check-Out button.
function showMessage(text, ok) {
	var box = document.getElementById('checkout_msg');
	box.textContent = text;
	box.className = ok ? 'msg_ok' : 'msg_err';
}

// Runs when the Check-Out button is clicked.
function checkout() {
	var rows = document.querySelectorAll('#menu_table .product_row');
	var items = [];   // list of {option_id, quantity} to send

	// Step 1: collect the rows that have a quantity above 0
	for (var i = 0; i < rows.length; i++) {
		var qtyBox = rows[i].querySelector('.qty');
		if (!QTY_PATTERN.test(qtyBox.value)) {
			showMessage('Please correct the quantities marked "Error".', false);
			return;
		}
		var qty = Number(qtyBox.value) || 0;
		if (qty > 0) {
			items.push({option_id: Number(selectedOption(rows[i]).value), quantity: qty});
		}
	}
	// Step 2: refuse an empty order
	if (items.length === 0) {
		showMessage('Your order is empty. Enter a quantity for at least one coffee.', false);
		return;
	}

	var btn = document.getElementById('checkout_btn');
	btn.disabled = true;   // stop double-clicks while saving

	// Step 3: send the order to checkout.php as JSON
	fetch('checkout.php', {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify({items: items})
	})
	.then(function (res) { return res.json(); })
	.then(function (data) {
		if (data.ok) {
			// Success: thank the customer and clear the quantity boxes
			showMessage('Thank you! Order #' + data.order_id + ' saved. Total charged: $' + data.total, true);
			var boxes = document.querySelectorAll('#menu_table .qty');
			for (var i = 0; i < boxes.length; i++) boxes[i].value = '';
			calculatePrice();
		} else {
			showMessage(data.error || 'Check-out failed.', false);
		}
	})
	.catch(function () { showMessage('Could not reach the server. Please try again.', false); })
	.then(function () { btn.disabled = false; });   // re-enable the button either way
}

// Set up the page once it has fully loaded.
window.addEventListener('load', function () {
	var table = document.getElementById('menu_table');
	if (!table) return;                        // menu failed to load from the database
	table.addEventListener('input',  calculatePrice);   // recalculate when a quantity is typed
	table.addEventListener('change', calculatePrice);   // ...or a Single/Double radio is changed
	document.getElementById('checkout_btn').addEventListener('click', checkout);
	calculatePrice();                          // show $0.00 values at start
});
