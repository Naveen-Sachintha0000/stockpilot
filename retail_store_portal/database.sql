SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_order_items;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS product_suppliers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE categories (
 category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE suppliers (
 supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 supplier_name VARCHAR(150) NOT NULL,
 contact_person VARCHAR(100),
 email VARCHAR(150),
 phone VARCHAR(30),
 address VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE customers (
 customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_name VARCHAR(150) NOT NULL,
 email VARCHAR(150),
 phone VARCHAR(30),
 address VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE products (
 product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sku VARCHAR(40) NOT NULL UNIQUE,
 product_name VARCHAR(150) NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 selling_price DECIMAL(12,2) NOT NULL,
 stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 reorder_level INT UNSIGNED NOT NULL DEFAULT 5,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(category_id),
 CONSTRAINT chk_product_price CHECK (selling_price >= 0)
) ENGINE=InnoDB;

CREATE TABLE product_suppliers (
 product_id INT UNSIGNED NOT NULL,
 supplier_id INT UNSIGNED NOT NULL,
 supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 PRIMARY KEY (product_id,supplier_id),
 CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(product_id),
 CONSTRAINT fk_ps_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
 CONSTRAINT chk_supply_price CHECK (supply_price >= 0)
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
 purchase_order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 supplier_id INT UNSIGNED NOT NULL,
 order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 status ENUM('Pending','Received') NOT NULL DEFAULT 'Pending',
 received_at DATETIME NULL,
 notes VARCHAR(255),
 CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
) ENGINE=InnoDB;

CREATE TABLE purchase_order_items (
 purchase_order_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 unit_cost DECIMAL(12,2) NOT NULL,
 PRIMARY KEY (purchase_order_id, product_id),
 CONSTRAINT fk_poi_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(purchase_order_id),
 CONSTRAINT fk_poi_product FOREIGN KEY (product_id) REFERENCES products(product_id),
 CONSTRAINT chk_poi_qty CHECK (quantity > 0),
 CONSTRAINT chk_poi_cost CHECK (unit_cost >= 0)
) ENGINE=InnoDB;

CREATE TABLE sales (
 sale_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NULL,
 sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 notes VARCHAR(255),
 CONSTRAINT fk_sale_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
 sale_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 PRIMARY KEY (sale_id, product_id),
 CONSTRAINT fk_si_sale FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
 CONSTRAINT fk_si_product FOREIGN KEY (product_id) REFERENCES products(product_id),
 CONSTRAINT chk_si_qty CHECK (quantity > 0),
 CONSTRAINT chk_si_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_po_date ON purchase_orders(order_date);
CREATE INDEX idx_products_name ON products(product_name);

INSERT INTO categories VALUES (1,'Electronics'),(2,'Groceries'),(3,'Stationery'),(4,'Home & Living');
INSERT INTO suppliers VALUES
 (1,'TechSource Lanka','Amal Fernando','orders@techsource.example','0771234567','Colombo'),
 (2,'Island Wholesale','Nadeesha Perera','sales@islandwholesale.example','0712345678','Negombo'),
 (3,'Office Essentials','Ruwan Silva','hello@officeessentials.example','0763456789','Kandy');
INSERT INTO customers VALUES
 (1,'Kasun Perera','kasun@example.com','0775551111','Colombo'),
 (2,'Nimali Silva','nimali@example.com','0715552222','Gampaha'),
 (3,'Ayesha Fernando','ayesha@example.com','0755553333','Negombo'),
 (4,'Dilan Jayasinghe','dilan@example.com','0725554444','Kandy');
INSERT INTO products(product_id,sku,product_name,category_id,selling_price,stock_quantity,reorder_level) VALUES
 (1,'ELEC-001','Wireless Mouse',1,3200.00,4,8),
 (2,'ELEC-002','USB-C Cable',1,1500.00,24,10),
 (3,'ELEC-003','Bluetooth Speaker',1,8500.00,12,5),
 (4,'GROC-001','Rice 1kg',2,420.00,42,15),
 (5,'GROC-002','Milk Powder 400g',2,1250.00,3,10),
 (6,'STAT-001','A5 Notebook',3,350.00,35,12),
 (7,'STAT-002','Blue Ballpoint Pen',3,120.00,80,20),
 (8,'HOME-001','Ceramic Mug',4,1750.00,6,8);
INSERT INTO product_suppliers VALUES
 (1,1,2300),(2,1,980),(3,1,6300),(4,2,310),(5,2,960),(6,3,220),(7,3,60),(8,2,1100);
INSERT INTO purchase_orders(purchase_order_id,supplier_id,order_date,status,received_at,notes) VALUES
 (1,1,'2026-10-15 09:30:00','Received','2026-10-18 11:00:00','October electronics replenishment'),
 (2,2,'2026-10-22 14:00:00','Received','2026-10-24 10:15:00','Groceries delivery'),
 (3,3,'2026-11-01 10:20:00','Pending',NULL,'Stationery restock');
INSERT INTO purchase_order_items VALUES
 (1,1,15,2300),(1,2,30,980),(1,3,15,6300),
 (2,4,50,310),(2,5,15,960),(2,8,10,1100),
 (3,6,50,220),(3,7,100,60);
INSERT INTO sales(sale_id,customer_id,sale_date,notes) VALUES
 (1,1,'2026-10-26 10:10:00','In-store purchase'),
 (2,2,'2026-10-27 15:30:00','In-store purchase'),
 (3,3,'2026-11-01 12:05:00','Afternoon purchase'),
 (4,NULL,'2026-11-02 16:45:00','Walk-in customer');
INSERT INTO sale_items VALUES
 (1,1,2,3200),(1,2,1,1500),
 (2,4,3,420),(2,5,2,1250),
 (3,3,1,8500),(3,6,4,350),
 (4,7,5,120),(4,8,2,1750);
-- Stock quantities represent CURRENT on-hand balances after the sample orders and sales.
