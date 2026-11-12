# Case 7 — Retail Store Entity Relationship Model

```mermaid
erDiagram
    CATEGORIES ||--o{ PRODUCTS : classifies
    PRODUCTS ||--o{ PRODUCT_SUPPLIERS : linked_by
    SUPPLIERS ||--o{ PRODUCT_SUPPLIERS : supplies
    SUPPLIERS ||--o{ PURCHASE_ORDERS : receives
    PURCHASE_ORDERS ||--|{ PURCHASE_ORDER_ITEMS : includes
    PRODUCTS ||--o{ PURCHASE_ORDER_ITEMS : purchased
    CUSTOMERS |o--o{ SALES : makes
    SALES ||--|{ SALE_ITEMS : contains
    PRODUCTS ||--o{ SALE_ITEMS : sold_in

    CATEGORIES {
        int category_id PK
        varchar category_name UK
    }
    PRODUCTS {
        int product_id PK
        varchar sku UK
        varchar product_name
        int category_id FK
        decimal selling_price
        int stock_quantity
        int reorder_level
    }
    SUPPLIERS {
        int supplier_id PK
        varchar supplier_name
        varchar contact_person
        varchar email
        varchar phone
        varchar address
    }
    PRODUCT_SUPPLIERS {
        int product_id PK,FK
        int supplier_id PK,FK
        decimal supply_price
    }
    CUSTOMERS {
        int customer_id PK
        varchar customer_name
        varchar email
        varchar phone
        varchar address
    }
    PURCHASE_ORDERS {
        int purchase_order_id PK
        int supplier_id FK
        datetime order_date
        enum status
        datetime received_at
    }
    PURCHASE_ORDER_ITEMS {
        int purchase_order_id PK,FK
        int product_id PK,FK
        int quantity
        decimal unit_cost
    }
    SALES {
        int sale_id PK
        int customer_id FK
        datetime sale_date
    }
    SALE_ITEMS {
        int sale_id PK,FK
        int product_id PK,FK
        int quantity
        decimal unit_price
    }
```

## Cardinality and design decisions

- One category has many products; each product belongs to one category.
- Suppliers and products are many-to-many through `product_suppliers`.
- A supplier has many purchase orders; each purchase order has one supplier and many line items.
- A product may appear on many purchase order lines and many sales lines.
- A customer may have many sales. A sale may have no customer record (walk-in customer).
- Each sale contains at least one product line.
- `sale_items.unit_price` and `purchase_order_items.unit_cost` store historical transaction prices, rather than using mutable current prices.
- `products.stock_quantity` stores the current on-hand balance, maintained through transaction-safe sales and purchase-order receipts.

## Relational schema (PK bold, FK in parentheses)

- CATEGORIES(**category_id**, category_name UNIQUE)
- PRODUCTS(**product_id**, sku UNIQUE, product_name, category_id (FK), selling_price, stock_quantity, reorder_level, created_at)
- SUPPLIERS(**supplier_id**, supplier_name, contact_person, email, phone, address)
- PRODUCT_SUPPLIERS(**product_id** (FK), **supplier_id** (FK), supply_price)
- PURCHASE_ORDERS(**purchase_order_id**, supplier_id (FK), order_date, status, received_at, notes)
- PURCHASE_ORDER_ITEMS(**purchase_order_id** (FK), **product_id** (FK), quantity, unit_cost)
- CUSTOMERS(**customer_id**, customer_name, email, phone, address)
- SALES(**sale_id**, customer_id (nullable FK), sale_date, notes)
- SALE_ITEMS(**sale_id** (FK), **product_id** (FK), quantity, unit_price)

All tables are InnoDB, with primary keys, foreign keys, and checks for price and quantity.
