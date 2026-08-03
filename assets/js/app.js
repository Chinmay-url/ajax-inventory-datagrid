(() => {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const searchInput       = document.getElementById('searchInput');
    const searchClear       = document.getElementById('searchClear');
    const gridBody          = document.getElementById('gridBody');
    const totalCount        = document.getElementById('totalCount');
    const rangeInfo         = document.getElementById('rangeInfo');
    const selectAll         = document.getElementById('selectAll');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const deleteCount       = document.getElementById('deleteCount');
    const selectionBar      = document.getElementById('selectionBar');
    const selectionText     = document.getElementById('selectionText');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const refreshBtn        = document.getElementById('refreshBtn');
    const limitSelect       = document.getElementById('limitSelect');
    const prevPageBtn       = document.getElementById('prevPage');
    const nextPageBtn       = document.getElementById('nextPage');
    const pageInfo          = document.getElementById('pageInfo');
    const toast             = document.getElementById('toast');
    const toastMessage      = document.getElementById('toastMessage');
    const toastIcon         = document.querySelector('.toast__icon');

    const addProductBtn     = document.getElementById('addProductBtn');
    const productModal      = document.getElementById('productModal');
    const modalTitle        = document.getElementById('modalTitle');
    const productForm       = document.getElementById('productForm');
    const productIdInput    = document.getElementById('productId');
    const skuInput          = document.getElementById('skuInput');
    const nameInput         = document.getElementById('nameInput');
    const priceInput        = document.getElementById('priceInput');
    const stockInput        = document.getElementById('stockInput');
    const statusInput       = document.getElementById('statusInput');
    const modalSaveBtn      = document.getElementById('modalSaveBtn');
    const modalCancelBtn    = document.getElementById('modalCancelBtn');
    const modalCloseBtn     = document.getElementById('modalCloseBtn');

    const ICONS = {
        success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        error:   '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
    };

    let state = {
        search: '',
        page: 1,
        limit: 50,
        totalPages: 1,
        total: 0,
    };

    const selectedIds = new Set();
    let debounceTimer = null;
    let toastTimer = null;
    let editingId = null;
    let lastFocused = null;

    function showToast(message, type = 'success') {
        toastMessage.textContent = message;
        toastIcon.innerHTML = ICONS[type] || ICONS.success;
        toast.className = `toast toast--${type} toast--visible`;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('toast--visible');
        }, 3000);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function statusOptions(current) {
        return ['IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK'].map(s =>
            `<option value="${s}" ${s === current ? 'selected' : ''}>${s.replace('_', ' ')}</option>`
        ).join('');
    }

    function renderSkeleton() {
        gridBody.innerHTML = Array.from({ length: state.limit > 10 ? 10 : state.limit }, () => `
            <tr class="skeleton-row">
                <td><span class="sk" style="width:16px;height:16px"></span></td>
                <td><span class="sk sk--narrow"></span></td>
                <td><span class="sk sk--wide"></span></td>
                <td><span class="sk sk--narrow"></span></td>
                <td><span class="sk sk--narrow"></span></td>
                <td><span class="sk sk--narrow"></span></td>
                <td><span class="sk" style="width:28px"></span></td>
            </tr>
        `).join('');
    }

    function renderEmpty() {
        gridBody.innerHTML = `
            <tr>
                <td colspan="7" class="state-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <p>No products found</p>
                    <small>${state.search ? 'Try a different search term.' : 'There are no products in the inventory.'}</small>
                </td>
            </tr>`;
    }

    function renderRows(products) {
        if (!products.length) {
            renderEmpty();
            return;
        }

        gridBody.innerHTML = products.map(p => `
            <tr data-id="${p.id}">
                <td class="col-checkbox"><input type="checkbox" class="row-checkbox" data-id="${p.id}" aria-label="Select product ${p.sku}" ${selectedIds.has(String(p.id)) ? 'checked' : ''}></td>
                <td data-field="sku">${escapeHtml(p.sku)}</td>
                <td class="editable-cell" data-field="name" tabindex="0" role="button" title="Double-click to edit">${escapeHtml(p.name)}</td>
                <td class="editable-cell" data-field="price" tabindex="0" role="button" title="Double-click to edit">$${parseFloat(p.price).toFixed(2)}</td>
                <td class="editable-cell" data-field="stock" tabindex="0" role="button" title="Double-click to edit">${p.stock}</td>
                <td class="editable-cell" data-field="status" tabindex="0" role="button" title="Double-click to edit">
                    <span class="status-pill status-pill--${p.status}">${p.status.replace('_', ' ')}</span>
                </td>
                <td class="col-actions">
                    <button type="button" class="btn btn--icon btn--ghost row-edit" data-id="${p.id}" title="Edit product" aria-label="Edit product ${escapeHtml(p.sku)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `).join('');

        selectAll.checked = gridBody.querySelectorAll('.row-checkbox:checked').length > 0;
        attachRowEvents();
    }

    function updatePaginationMeta() {
        const { total, page, limit, totalPages } = state;
        totalCount.textContent = `${total.toLocaleString()} item${total === 1 ? '' : 's'}`;

        const start = total === 0 ? 0 : (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);
        rangeInfo.textContent = total === 0 ? 'Showing 0' : `Showing ${start.toLocaleString()}\u2013${end.toLocaleString()}`;

        pageInfo.textContent = `Page ${page} of ${totalPages}`;
        prevPageBtn.disabled = page <= 1;
        nextPageBtn.disabled = page >= totalPages;
    }

    async function fetchProducts() {
        renderSkeleton();

        try {
            const params = new URLSearchParams({
                search: state.search,
                page: state.page,
                limit: state.limit,
            });

            const res = await fetch(`fetch_products.php?${params.toString()}`);
            const payload = await res.json();

            if (!payload.success) {
                showToast(payload.message, 'error');
                return;
            }

            renderRows(payload.data.products);

            const pagination = payload.data.pagination;
            state.totalPages = pagination.total_pages || 1;
            state.total = pagination.total;

            updatePaginationMeta();
        } catch (err) {
            console.error(err);
            gridBody.innerHTML = `
                <tr>
                    <td colspan="7" class="state-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <p>Failed to load products</p>
                        <small>Check that the server is reachable.</small>
                    </td>
                </tr>`;
            showToast('Failed to load products.', 'error');
        }
    }

    async function updateField(id, field, value) {
        try {
            const res = await fetch('update_product.php', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ id, field, value }),
            });
            const payload = await res.json();

            if (!payload.success) {
                showToast(payload.message, 'error');
                return null;
            }

            showToast('Updated successfully.', 'success');
            return payload.data.value;
        } catch (err) {
            console.error(err);
            showToast('Network error while updating.', 'error');
            return null;
        }
    }

    function renderCellValue(cell, field, serverValue) {
        const value = serverValue !== null && serverValue !== undefined ? serverValue : cell.dataset.pending;

        if (field === 'price') {
            cell.textContent = `$${parseFloat(value).toFixed(2)}`;
        } else if (field === 'status') {
            cell.innerHTML = `<span class="status-pill status-pill--${value}">${value.replace('_', ' ')}</span>`;
        } else if (field === 'stock') {
            cell.textContent = String(value);
        } else {
            cell.textContent = String(value);
        }
    }

    async function deleteSelected() {
        if (!selectedIds.size) return;

        const ids = Array.from(selectedIds).map(Number);
        deleteSelectedBtn.disabled = true;

        try {
            const res = await fetch('batch_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ ids }),
            });
            const payload = await res.json();

            if (!payload.success) {
                showToast(payload.message, 'error');
                return;
            }

            showToast(payload.message, 'success');
            selectedIds.clear();
            updateSelectionUI();
            await reloadAfterDelete();
        } catch (err) {
            console.error(err);
            showToast('Network error during delete.', 'error');
        } finally {
            deleteSelectedBtn.disabled = selectedIds.size === 0;
        }
    }

    async function reloadAfterDelete() {
        await fetchProducts();
        const stillHasRows = !!gridBody.querySelector('.editable-cell');
        if (!stillHasRows && state.page > 1) {
            state.page--;
            await fetchProducts();
        }
    }

    function updateSelectionUI() {
        const count = selectedIds.size;
        deleteCount.textContent = count;
        deleteSelectedBtn.disabled = count === 0;
        selectionBar.hidden = count === 0;
        selectionText.textContent = `${count} row${count === 1 ? '' : 's'} selected`;

        if (gridBody.querySelectorAll('.row-checkbox').length) {
            const visibleIds = Array.from(gridBody.querySelectorAll('.row-checkbox'));
            selectAll.checked = visibleIds.length > 0 && visibleIds.every(cb => cb.checked);
        } else {
            selectAll.checked = false;
        }
    }

    function attachRowEvents() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const id = cb.dataset.id;
                if (cb.checked) selectedIds.add(id); else selectedIds.delete(id);
                updateSelectionUI();
            });
        });

        document.querySelectorAll('.editable-cell').forEach(cell => {
            cell.addEventListener('dblclick', () => startEdit(cell));
            cell.addEventListener('keydown', (e) => {
                if ((e.key === 'Enter' || e.key === ' ') && !cell.querySelector('input, select')) {
                    e.preventDefault();
                    startEdit(cell);
                }
            });
        });

        document.querySelectorAll('.row-edit').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(btn.dataset.id));
        });
    }

    function startEdit(cell) {
        if (cell.querySelector('input, select')) return;

        const field = cell.dataset.field;
        const row = cell.closest('tr');
        const id = row.dataset.id;

        let currentValue;
        if (field === 'price') {
            currentValue = cell.textContent.replace('$', '').trim();
        } else if (field === 'status') {
            currentValue = cell.querySelector('.status-pill').className.split('--')[1];
        } else {
            currentValue = cell.textContent.trim();
        }

        const original = cell.innerHTML;

        let inputHtml;
        if (field === 'status') {
            inputHtml = `<select aria-label="Status">${statusOptions(currentValue)}</select>`;
        } else if (field === 'price') {
            inputHtml = `<input type="number" step="0.01" min="0" value="${currentValue}" aria-label="Price">`;
        } else if (field === 'stock') {
            inputHtml = `<input type="number" step="1" min="0" value="${currentValue}" aria-label="Stock">`;
        } else {
            inputHtml = `<input type="text" value="${escapeHtml(currentValue)}" aria-label="Name">`;
        }

        cell.innerHTML = inputHtml;
        const input = cell.querySelector('input, select');
        input.focus();
        if (input.select) input.select();

        const commit = async () => {
            const newValue = input.value;
            const serverValue = await updateField(id, field, newValue);

            if (serverValue !== null) {
                renderCellValue(cell, field, serverValue);
            } else {
                cell.innerHTML = original;
            }
        };

        input.addEventListener('blur', commit, { once: true });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') input.blur();
            if (e.key === 'Escape') {
                cell.innerHTML = original;
            }
        });
    }

    // ========== Add / Edit modal ==========

    function openModal(id = null) {
        editingId = id;
        productForm.reset();

        if (id === null) {
            modalTitle.textContent = 'Add Product';
            modalSaveBtn.textContent = 'Add Product';
            productIdInput.value = '';
        } else {
            modalTitle.textContent = 'Edit Product';
            modalSaveBtn.textContent = 'Save Changes';
            productIdInput.value = String(id);
        }

        lastFocused = document.activeElement;
        productModal.hidden = false;
        document.body.classList.add('modal-open');
        requestAnimationFrame(() => skuInput.focus());
    }

    function closeModal() {
        productModal.hidden = true;
        document.body.classList.remove('modal-open');
        editingId = null;
        if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    function openEditModal(id) {
        const row = gridBody.querySelector(`tr[data-id="${id}"]`);
        if (!row) return;

        const cell = selector => row.querySelector(selector);
        const sku = cell('td[data-field="sku"]').textContent.trim();
        const name = cell('.editable-cell[data-field="name"]').textContent.trim();
        const price = cell('.editable-cell[data-field="price"]').textContent.replace('$', '').trim();
        const stock = cell('.editable-cell[data-field="stock"]').textContent.trim();
        const status = cell('.editable-cell[data-field="status"] .status-pill').className.split('--')[1];

        openModal(id);
        skuInput.value = sku;
        nameInput.value = name;
        priceInput.value = price;
        stockInput.value = stock;
        statusInput.value = status;
    }

    function highlightRow(id) {
        const row = gridBody.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            row.classList.add('row--flash');
            setTimeout(() => row.classList.remove('row--flash'), 1600);
        }
    }

    async function saveProduct() {
        const payload = {
            id: editingId,
            sku: skuInput.value.trim(),
            name: nameInput.value.trim(),
            price: priceInput.value,
            stock: stockInput.value,
            status: statusInput.value,
        };

        modalSaveBtn.disabled = true;
        modalSaveBtn.textContent = 'Saving...';

        try {
            const res = await fetch('save_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (!data.success) {
                showToast(data.message, 'error');
                return;
            }

            showToast(data.message, 'success');
            closeModal();

            if (editingId === null) {
                // Newest products have the highest id, so they land on the last page.
                await fetchProducts();
                if (state.page !== state.totalPages) {
                    state.page = state.totalPages;
                    await fetchProducts();
                }
                highlightRow(data.data.id);
            } else {
                await fetchProducts();
            }
        } catch (err) {
            console.error(err);
            showToast('Network error while saving.', 'error');
        } finally {
            modalSaveBtn.disabled = false;
            modalSaveBtn.textContent = editingId === null ? 'Add Product' : 'Save Changes';
        }
    }

    addProductBtn.addEventListener('click', () => openModal());

    productForm.addEventListener('submit', (e) => {
        e.preventDefault();
        saveProduct();
    });

    modalCancelBtn.addEventListener('click', closeModal);
    modalCloseBtn.addEventListener('click', closeModal);
    document.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !productModal.hidden) {
            e.preventDefault();
            closeModal();
        }
    });

    // Debounced search — waits 300ms after the last keystroke before firing.
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        searchClear.hidden = searchInput.value.trim() === '';
        debounceTimer = setTimeout(() => {
            state.search = searchInput.value.trim();
            state.page = 1;
            fetchProducts();
        }, 300);
    });

    searchClear.addEventListener('click', () => {
        searchInput.value = '';
        searchClear.hidden = true;
        state.search = '';
        state.page = 1;
        fetchProducts();
        searchInput.focus();
    });

    limitSelect.addEventListener('change', () => {
        state.limit = parseInt(limitSelect.value, 10);
        state.page = 1;
        fetchProducts();
    });

    refreshBtn.addEventListener('click', () => {
        fetchProducts();
        showToast('Data refreshed.', 'success');
    });

    selectAll.addEventListener('change', () => {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = selectAll.checked;
            const id = cb.dataset.id;
            if (selectAll.checked) selectedIds.add(id); else selectedIds.delete(id);
        });
        updateSelectionUI();
    });

    clearSelectionBtn.addEventListener('click', () => {
        selectedIds.clear();
        document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = false; });
        updateSelectionUI();
    });

    deleteSelectedBtn.addEventListener('click', deleteSelected);

    prevPageBtn.addEventListener('click', () => {
        if (state.page > 1) { state.page--; fetchProducts(); }
    });
    nextPageBtn.addEventListener('click', () => {
        if (state.page < state.totalPages) { state.page++; fetchProducts(); }
    });

    updateSelectionUI();
    fetchProducts();
})();
