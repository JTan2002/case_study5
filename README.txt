JavaJam - Case Study 05 (base version: HTML, CSS, JS, PHP, MySQL)

SETUP
1. Start Apache + MySQL (XAMPP/WAMP/MAMP) and copy this folder into htdocs (or run: php -S localhost:8080).
2. Import schema.sql (phpMyAdmin > Import, or: mysql -u root < schema.sql).
   It recreates database "javajam" with the ORIGINAL prices
   (Just Java 2 | Cafe au Lait 2/3 | Iced Cappuccino 4.75/5.75)
   and quantity 1 ordered for each product/category. Re-import to reset at any time.
3. Edit db.php if your MySQL user/password differ from root / (empty).
4. Open http://localhost/casestudy_04/menu.php

PAGES
menu.php               Menu (prices from DB) + Check-Out button -> checkout.php saves the order
checkout.php           Validates order, prices looked up server-side, saves orders + order_items
admin_price_update.php 5(i)  Tick a blend, type new price(s), saved to DB and re-read for confirmation
admin_sales_report.php 5(ii) Daily report: by product, by category, popular option of best seller
db.php                 DB connection + shared helpers
