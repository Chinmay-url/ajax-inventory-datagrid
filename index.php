<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/csrf.php';

$csrfToken = Csrf::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<title>DataGrid | Product Inventory</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <header class="header">
        <div class="header__title">
            <span class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                </svg>
            </span>
            <div>
                <h1>Product Inventory</h1>
                <p class="header__sub">AJAX data grid &middot; inline editing &middot; indexed pagination</p>
            </div>
        </div>
        <div class="header__stats">
            <span id="totalCount" class="total-count">0 items</span>
            <span id="rangeInfo" class="range-info">Showing &mdash;</span>
        </div>
    </header>

    <div class="toolbar">
        <div class="search-wrap">
            <svg class="search-wrap__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="search" id="searchInput" class="search-input" placeholder="Search by SKU or product name..." aria-label="Search products" autocomplete="off">
            <button type="button" id="searchClear" class="search-clear" aria-label="Clear search" hidden>&times;</button>
        </div>

        <div class="toolbar__actions">
            <button type="button" id="addProductBtn" class="btn btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Add Product
            </button>

            <label class="limit-label" for="limitSelect">
                Rows
                <select id="limitSelect">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </label>

            <button type="button" id="refreshBtn" class="btn btn--ghost btn--icon" title="Refresh data" aria-label="Refresh data">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                    <path d="M21 3v6h-6"></path>
                </svg>
            </button>

            <button type="button" id="deleteSelectedBtn" class="btn btn--danger" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
                Delete Selected (<span id="deleteCount">0</span>)
            </button>
        </div>
    </div>

    <div id="selectionBar" class="selection-bar" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
        <span id="selectionText">0 rows selected</span>
        <button type="button" id="clearSelectionBtn" class="btn btn--ghost btn--sm">Clear</button>
    </div>

    <div class="table-wrapper">
        <table class="grid-table">
            <thead>
                <tr>
                    <th class="col-checkbox">
                        <input type="checkbox" id="selectAll" aria-label="Select all rows on this page">
                    </th>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="gridBody">
                <tr><td colspan="7" class="loading-row">Loading products...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <button id="prevPage" class="btn btn--ghost" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Prev
        </button>
        <span id="pageInfo" class="page-info" role="status">Page 1</span>
        <button id="nextPage" class="btn btn--ghost">
            Next
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>

    <p class="hint">
        Tip: double-click any value (name, price, stock, status) to edit inline &middot; <kbd>Enter</kbd> saves &middot; <kbd>Esc</kbd> cancels &middot; use the pencil button to edit a whole row
    </p>
</div>

<div id="productModal" class="modal" hidden>
    <div class="modal__backdrop" data-close-modal></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal__header">
            <h2 id="modalTitle">Add Product</h2>
            <button type="button" id="modalCloseBtn" class="modal__close" aria-label="Close dialog">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <form id="productForm">
            <input type="hidden" id="productId">
            <div class="form-grid">
                <div class="form-field form-field--full">
                    <label for="skuInput">SKU</label>
                    <input type="text" id="skuInput" maxlength="50" placeholder="SKU-000001" required>
                </div>
                <div class="form-field form-field--full">
                    <label for="nameInput">Product name</label>
                    <input type="text" id="nameInput" maxlength="200" placeholder="Product Item 2001" required>
                </div>
                <div class="form-field">
                    <label for="priceInput">Price ($)</label>
                    <input type="number" id="priceInput" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="form-field">
                    <label for="stockInput">Stock</label>
                    <input type="number" id="stockInput" step="1" min="0" placeholder="0" required>
                </div>
                <div class="form-field form-field--full">
                    <label for="statusInput">Status</label>
                    <select id="statusInput">
                        <option value="IN_STOCK">IN STOCK</option>
                        <option value="LOW_STOCK">LOW STOCK</option>
                        <option value="OUT_OF_STOCK">OUT OF STOCK</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" id="modalCancelBtn" class="btn btn--ghost">Cancel</button>
                <button type="submit" id="modalSaveBtn" class="btn btn--primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="toast" role="status" aria-live="polite">
    <svg class="toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span id="toastMessage"></span>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
