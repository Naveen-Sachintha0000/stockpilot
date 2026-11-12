# StockPilot — Retail Store Management (PHP + MySQL)

This **clean rebuild** uses database **`retail_store_portal`**, deliberately different from the earlier `retail_store` and `retailstore` databases to avoid foreign key errors caused by old tables.

## What is included

- Responsive HTML/CSS dashboard and forms
- Product entry/editing and categories
- Supplier and customer entry/editing
- Create purchase orders and receive deliveries (stock increases only once)
- Create customer sales, including multi-item baskets (stock decreases transactionally)
- Sales report by product with optional date range, and low-stock report
- MySQL schema, sample data, ER diagram and a setup diagnostics page

## XAMPP installation (Windows)

1. Extract this ZIP into `C:\xampp\htdocs\` so the folder is `C:\xampp\htdocs\retail_store_portal\`.
2. Start **Apache** and **MySQL** from XAMPP Control Panel.
3. Open **http://localhost/phpmyadmin/**.
4. Click **Import** on the phpMyAdmin home page (server-level Import), select `C:\xampp\htdocs\retail_store_portal\database.sql`, and click **Go**. **Import the included SQL file, not the file from your previous C# or PHP project.** The SQL script creates the `retail_store_portal` database and all nine tables.
5. Check **http://localhost/retail_store_portal/diagnostics.php**. The page should say that all nine tables were found.
6. Open **http://localhost/retail_store_portal/**.

**MySQL connection**: edit `config.php` if your MySQL user/password/port is not the XAMPP default (`127.0.0.1:3306`, user `root`, blank password).

### Important: What happens to previous data?

`database.sql` resets the nine tables in **`retail_store_portal` only** and inserts sample data. It **does not delete** your old `retail_store` or `retailstore` databases. Importing the SQL repeatedly resets demo data in `retail_store_portal`, so back it up before reimporting if you have entered your own records.

### Troubleshooting

- **Unknown database**: import `database.sql` at the phpMyAdmin home/server-level Import page.
- **Missing tables**: check `/diagnostics.php`, then reimport the included SQL file.
- **Access denied**: correct credentials in `config.php`.
- **Apache/MySQL not starting**: check XAMPP status and potential 80/3306 port conflicts.
- **404**: verify the extracted folder is exactly `retail_store_portal`, not a nested directory.
- **mb_strlen undefined**: enable the `mbstring` extension in your PHP installation (XAMPP usually has it enabled).

## Database entities and relationships

See `docs/ER_Diagram.md`. Tables: `categories`, `products`, `suppliers`, `product_suppliers`, `customers`, `purchase_orders`, `purchase_order_items`, `sales`, `sale_items`.

## Application workflow

1. Add or edit products (SKU, category, price, initial stock, reorder threshold).
2. Register a supplier and a customer.
3. Place a purchase order, then click **Mark received** to add incoming stock.
4. Create a sale. Stock sufficiency is checked inside a database transaction before items are saved or stock reduced.
5. View **Reports** for sales performance, and products at/below reorder level.

## Tools and verification

This project requires PHP with `pdo_mysql` and MySQL/MariaDB. Prepared statements are used for parameterized inputs, HTML is escaped, forms use CSRF tokens, and stock changes use transactions. PHP syntax checks are done for all PHP files. A real MySQL/XAMPP deployment check is still required on your machine; no MySQL server is running in the artifact build environment.

## Launch URL

`http://localhost/retail_store_portal/`
