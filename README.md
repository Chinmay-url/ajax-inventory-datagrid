# AJAX Inventory Data Grid

A PHP-based inventory management system with AJAX-powered CRUD operations, inline editing, product search, pagination, and batch soft delete.

---

## Features

### Product Management
- Add & Edit Products
- Inline Cell Editing
- Batch Soft Delete
- Real-time Product Search
- Paginated Product Listing

### Performance
- Debounced Search
- Indexed Pagination
- Optimized SQL Queries
- AJAX-based Updates

### Security
- CSRF Protection
- Prepared Statements
- Server-side Validation
- HTML Escaping

---

## Tech Stack

| Layer | Technology |
|--------|------------|
| Backend | PHP 8 |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, Vanilla JavaScript |
| Security | CSRF, PDO |

---

## Project Structure

```text
├── config/
├── assets/
├── fetch_products.php
├── save_product.php
├── update_product.php
├── batch_delete.php
├── index.php
├── schema.sql
├── .env.example
└── .env
```

---

## Setup

1. Clone the repository

```bash
git clone <repository-url>
```

2. Create `.env`

```bash
cp .env.example .env
```

3. Import the database

```bash
mysql -u root < schema.sql
```

4. Start Apache & MySQL

5. Open

```
http://localhost/ajax-inventory-datagrid/
```

---

## Workflow

```
User Action
     │
     ▼
 AJAX Request
     │
     ▼
PHP API Endpoint
     │
     ▼
Database
     │
     ▼
JSON Response
     │
     ▼
UI Update
```

---

## API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| fetch_products.php | GET | Fetch Products |
| save_product.php | POST | Add / Update Product |
| update_product.php | PATCH | Inline Update |
| batch_delete.php | POST | Soft Delete Products |

---

## Security

- CSRF Protection
- Prepared Statements
- Input Validation
- Output Escaping
- Soft Delete Strategy

---

## Performance

- Indexed Search
- Debounced Requests
- Optimized Pagination
- AJAX Rendering

---

## License

MIT
