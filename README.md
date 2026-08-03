# High-Performance AJAX Data Grid

A zero-dependency PHP + vanilla JS product inventory manager with dynamic inline editing,
debounced search, indexed pagination, and batch soft-delete. Ships with a clean, professional
UI generated from the [UI UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill)
design system (Minimalism & Swiss Style, industrial slate + stock green palette).

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white) ![Vanilla JS](https://img.shields.io/badge/JS-Vanilla-F7DF1E?logo=javascript&logoColor=black)

## Features

- **Inline editing** — double-click any value (name, price, stock, status) to edit it in place.
  `Enter` saves, `Esc` cancels, values are re-validated and normalized server-side.
- **Add / edit products** — the **Add Product** button opens a modal form; each row's pencil
  button opens the same form pre-filled to update all fields (including SKU) at once.
- **Debounced search** — searches SKU or name with a leading-wildcard-free `LIKE`, keeping the
  indexes usable even at 2,000+ rows.
- **Indexed pagination** — offset/limit pagination over a composite `(is_deleted, id)` index,
  with page-size selector (25 / 50 / 100 / 200) and prev/next controls.
- **Batch soft delete** — check rows (or select-all) and remove them in a single transactional
  update; rows are flagged `is_deleted = 1`, never physically removed.
- **CSRF protection** — every mutation is protected by a session-bound, per-request header token.
- **Server-side validation** — strict per-column whitelists and type checks; column names are
  never built from raw client input.
- **Secure by default** — PDO prepared statements, `strict_types`, HTML-escaped output on both
  server and client, JSON API responses.

## Tech Stack

| Layer    | Technology                              |
| -------- | --------------------------------------- |
| Backend  | PHP 8+ (PDO, `declare(strict_types=1)`) |
| Database | MySQL 8 / MariaDB (InnoDB, utf8mb4)     |
| Frontend | Vanilla JavaScript (ES6+, `fetch`)      |
| Styling  | Hand-written CSS (design-system tokens) |

## Project Structure

```
├── config/
│   ├── env.php           # Minimal .env loader (no Composer required)
│   ├── database.php      # PDO singleton (credentials read from .env)
│   ├── response.php      # Standard JSON response helper
│   └── csrf.php          # CSRF token generation + validation
├── fetch_products.php    # GET  — paginated/searchable product list
├── save_product.php      # POST — create or update a full product
├── update_product.php    # PATCH — inline field update (whitelisted)
├── batch_delete.php      # POST — soft-delete many products at once
├── index.php             # Single-page UI (loads CSRF meta tag)
├── assets/
│   ├── css/style.css     # Design-system stylesheet
│   └── js/app.js         # Grid logic: fetch, render, edit, delete
├── schema.sql            # DB schema + 2,000-row seed
├── .env.example          # Committed template (copy to .env)
└── .env                  # Local credentials — NOT committed
```

## Installation (XAMPP)

1. **Clone the repo**

   ```bash
   git clone https://github.com/<your-username>/ajax-inventory-datagrid.git
   ```

2. **Copy into the web root**

   ```
   C:\xampp\htdocs\ajax-inventory-datagrid
   ```

3. **Create the database and seed 2,000 demo products**

   ```bash
   mysql -u root < schema.sql
   ```

   Or run it inside phpMyAdmin → **Import** → `schema.sql`.

4. **Configure environment variables**

   No credentials are hardcoded in the source. Copy the template and fill in
   your own database settings:

   ```bash
   cp .env.example .env
   ```

   ```
   DB_HOST=127.0.0.1
   DB_NAME=datagrid_db
   DB_USER=root
   DB_PASS=your_password_here
   DB_CHARSET=utf8mb4
   ```

   > `.env` is git-ignored, so your credentials never reach the repository.
   > On Windows: `copy .env.example .env`

5. **Start Apache + MySQL** from the XAMPP Control Panel.

6. **Open the app**

   ```
   http://localhost/ajax-inventory-datagrid/
   ```

### Database configuration

All database credentials live in `.env` (see `config/env.php` for the loader and
`config/database.php` for usage). Every setting falls back to sensible XAMPP
defaults if the `.env` file is missing, so the app still boots before you
configure it.

## API Reference

### `GET fetch_products.php`

Query params: `search`, `page` (min 1), `limit` (10–200, default 50).

```json
{
  "success": true,
  "status": 200,
  "message": "Products fetched successfully.",
  "data": {
    "products": [{ "id": 1, "sku": "SKU-000001", "name": "Product Item 1", "price": "12.50", "stock": 34, "status": "IN_STOCK" }],
    "pagination": { "page": 1, "limit": 50, "total": 2000, "total_pages": 40 }
  }
}
```

### `PATCH update_product.php`

Requires `X-CSRF-TOKEN` header. Body: `{ "id": 1, "field": "price", "value": 19.99 }`.

Allowed fields: `name`, `price`, `stock`, `status` (status must be
`IN_STOCK | LOW_STOCK | OUT_OF_STOCK`).

### `POST save_product.php`

Requires `X-CSRF-TOKEN` header. Creates a product when `id` is omitted/null, otherwise
updates the product with that id.

```json
{
  "id": null,
  "sku": "SKU-002001",
  "name": "Product Item 2001",
  "price": 42.99,
  "stock": 12,
  "status": "IN_STOCK"
}
```

- `id` — omit or `null` to insert; pass a number to update that row.
- `sku` — required, 1–50 chars, unique among non-deleted products (returns `409` on clash).
- `name` — required, 1–200 chars.
- `price` — non-negative number; `stock` — non-negative integer.
- `status` — one of `IN_STOCK | LOW_STOCK | OUT_OF_STOCK`.

### `POST batch_delete.php`

Requires `X-CSRF-TOKEN` header. Body: `{ "ids": [1, 2, 3] }`. Soft-deletes rows in a
transaction.

## Security Notes

- All SQL uses prepared statements; only whitelisted column names are ever interpolated.
- Every write request is gated by a per-session CSRF token sent via the `X-CSRF-TOKEN` header.
- Output is HTML-escaped on the client (`escapeHtml`) and on the server before persistence.
- `is_deleted` soft-delete keeps history intact and the DELETE endpoints are idempotent.

## Performance Notes

- `idx_active_id (is_deleted, id)` matches the `WHERE is_deleted = 0 ORDER BY id ASC`
  pagination path.
- `idx_sku` and `idx_name` support the search, which is kept index-friendly by avoiding a
  leading `%` wildcard.
- `PDO::ATTR_EMULATE_PREPARES = false` sends real prepared statements and lets integer
  `LIMIT`/`OFFSET` binds stay typed.
- A 300 ms debounce prevents a request per keystroke.

## UI Design System

The interface is styled according to a design system generated by the
[UI UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) skill:

- **Style:** Minimalism & Swiss Style — spacious, high-contrast, grid-based.
- **Palette:** Industrial slate (`#334155`) + stock green accent (`#059669`) on `#F8FAFC`.
- **Typography:** [Fira Sans](https://fonts.google.com/specimen/Fira+Sans) for UI,
  [Fira Code](https://fonts.google.com/specimen/Fira+Code) for tabular data.
- **Interactions:** 200 ms hover transitions, keyboard-visible focus rings, skeleton loading,
  empty/error states, inline SVG icons (no emoji), and `prefers-reduced-motion` support.

## License

MIT — see [LICENSE](LICENSE).
