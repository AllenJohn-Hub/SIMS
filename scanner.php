<?php
// scanner.php - UI skeleton for scanner module (partial for AJAX load)
require_once 'config.php';
// Fetch all inventory items with category
$items = [];
$sql = "SELECT i.id, i.name, i.stock, i.price, c.category FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id ORDER BY i.name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<style>
    body { background: #f8f9fa; }
    .scanner-container { display: flex; height: 90vh; gap: 0; }
    .scanner-main { flex: 2; padding: 2rem; display: flex; flex-direction: column; }
    .scanner-preview {
        background: #cfd6dd;
        border-radius: 8px;
        border: 2px solid #b0b8c1;
        min-height: 300px;
        height: 325px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .scanner-preview .placeholder-icon {
        font-size: 3rem;
        color: #8a99a8;
    }
    .scanner-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
        background: #222;
        display: block;
    }
    .qty-box {
        background: #e9ecef;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        min-width: 120px;
        text-align: center;
    }
    .scanner-actions button {
        min-width: 90px;
        margin-right: 0.5rem;
    }
    .scanned-table {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        margin-top: 1.5rem;
        padding: 1rem;
        overflow: hidden;
        min-height: 310px;
    }
    .scanned-table table {
        table-layout: fixed;
        width: 100%;
        border-collapse: separate;
    }
    .scanned-table thead th {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 2;
    }
    .scanned-table thead th:nth-child(2),
    .scanned-table tbody td:nth-child(2) {
        width: 40%;
    }
    .scanned-table thead th:nth-child(3),
    .scanned-table tbody td:nth-child(3) {
        width: 35%;
    }
    .scanned-table thead th:nth-child(4),
    .scanned-table tbody td:nth-child(4) {
        width: 15%;
    }
    .scanned-table tbody {
        /* Remove previous display:block/max-height/overflow-y here to avoid conflicts */
        /* All handled inline for now */
    }
    .scanned-table thead, .scanned-table tbody tr {
        display: table;
        width: 100%;
        table-layout: fixed;
    }
    .scanned-table th, .scanned-table td {
        vertical-align: middle;
        padding: 0.5rem 0.5rem;
    }
    .sidebar-summary {
        flex: 1;
        background: #f4f6f8;
        border-left: 1px solid #dee2e6;
        padding: 0 1.5rem 2rem 1.5rem;
        display: flex;
        flex-direction: column;
        margin-top: 3.5rem;
        height: 100%;
        /* Add subtle shadow for card effect */
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .sidebar-summary .summary-list {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 2rem;
    }
    .summary-list-item {
        background: #fff;
        border: 1px solid #e0e3e7;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        transition: box-shadow 0.2s;
    }
    .summary-list-item:last-child {
        margin-bottom: 0;
    }
    .summary-list-item .placeholder-icon {
        margin-right: 0.75rem;
    }
    .summary-list-item strong {
        font-size: 1.05rem;
    }
    .summary-list-item .small {
        font-size: 0.95rem;
    }
    .sidebar-summary .mt-auto {
        border-top: 1.5px solid #e0e3e7;
        padding-top: 1.25rem;
        margin-top: 1.25rem;
        background: #f4f6f8;
    }
    .sidebar-summary .mb-2, .sidebar-summary .mb-3 {
        font-size: 1.08rem;
    }
    .sidebar-summary .finish-btn {
        width: 100%;
        font-size: 1.1rem;
        margin-top: 0.5rem;
    }
    .help-btn {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: #fff;
        border-radius: 50%;
        border: 2px solid #bbb;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #333;
        cursor: pointer;
    }
    .webcam-toggle-btn {
        position: absolute;
        top: 1.5rem;
        left: 1.5rem;
        z-index: 2;
    }
    .scanned-table tbody tr.selected {
        background: #e9ecef;
    }
    .scanned-table div[style*="overflow-y: auto"] {
        scrollbar-width: none;      /* Firefox */
        -ms-overflow-style: none;   /* IE 10+ */
    }
    .scanned-table div[style*="overflow-y: auto"]::-webkit-scrollbar {
        display: none;              /* Chrome, Safari, Opera */
    }
    #qr-reader {
        width: 320px;
        height: 320px;
        margin: 0 auto;
        background: #111;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 16px rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .qr-corners {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 10;
    }
    .qr-corner {
        position: absolute;
        width: 32px;
        height: 32px;
        border: 4px solid #e53935;
        border-radius: 6px;
    }
    .qr-corner.tl { top: 0; left: 0; border-right: none; border-bottom: none; }
    .qr-corner.tr { top: 0; right: 0; border-left: none; border-bottom: none; }
    .qr-corner.bl { bottom: 0; left: 0; border-right: none; border-top: none; }
    .qr-corner.br { bottom: 0; right: 0; border-left: none; border-top: none; }
    
    /* Discount input styling */
    .summary-list-item input[type="number"] {
        border: 1px solid #ced4da;
        border-radius: 3px;
        padding: 2px 4px;
        font-size: 11px;
        text-align: center;
    }
    .summary-list-item input[type="number"]:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    .summary-list-item .text-end {
        min-width: 60px;
    }
    
    /* Button states */
    .scanner-actions button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
<div class="container-fluid">
    <div class="scanner-container">
        <!-- Main Scanner Area -->
        <div class="scanner-main position-relative">
            <h3 class="mb-3">SCANNER</h3>
            <button class="help-btn" title="Help"><span>?</span></button>
            <button class="btn btn-dark webcam-toggle-btn" id="toggleWebcamBtn">Open Webcam</button>
            <div class="scanner-preview mb-3" id="scannerPreview">
                <!-- Webcam preview or placeholder will go here -->
                <span class="placeholder-icon" id="placeholderIcon">
                    
                </span>
                <div id="qr-reader" style="width: 100%; display: none;">
                    <div class="qr-corners">
                        <div class="qr-corner tl"></div>
                        <div class="qr-corner tr"></div>
                        <div class="qr-corner bl"></div>
                        <div class="qr-corner br"></div>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center mb-3">
                <input type="number" min="1" class="form-control" id="qtyInput" style="max-width:120px;" placeholder="Qty">
                <div class="ms-3 scanner-actions">
                    <button class="btn btn-dark" id="addBtn" title="Select an item first">Add</button>
                    <button class="btn btn-dark" id="cancelBtn">Cancel</button>
                </div>
            </div>
            <div class="scanned-table">
                <div class="mb-2">
                    <input type="text" class="form-control" id="itemSearchInput" placeholder="Search items...">
                </div>
                <div style="height: 400px; overflow-y: auto; width: 100%;">
                    <table class="table mb-0" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width:48px;"><input type="checkbox" id="masterCheckbox"></th>
                                <th style="width:40%">Item</th>
                                <th style="width:35%">Details</th>
                                <th style="width:15%">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="itemTableBody">
                            <?php if (empty($items)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>
                            <?php else: ?>
                            <?php foreach ($items as $item): ?>
                            <tr data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                <td style="width:48px;"><input type="checkbox" class="rowCheckbox"></td>
                                <td style="width:40%">
                                    <span class="placeholder-icon"></span>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </td>
                                <td style="width:35%"><?php echo htmlspecialchars($item['category']); ?></td>
                                <td style="width:15%"><?php echo htmlspecialchars($item['stock']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Sidebar Summary -->
        <div class="sidebar-summary d-flex flex-column">
            <div class="summary-list" id="summaryList">
                <!-- Dynamic summary items will be rendered here -->
            </div>
            <button class="btn btn-danger w-100 mb-3" id="voidBtn" style="display:none;">Void</button>
            <div class="mt-auto">
                <div class="mb-2">Total Qty: <span id="summaryTotalQty">0</span></div>
                <div class="mb-2">Subtotal: <span id="summarySubtotal">₱0.00</span></div>
                <div class="mb-2">
                    <label for="cartDiscountInput" class="form-label small mb-1">Cart Discount (%)</label>
                    <input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm" id="cartDiscountInput" placeholder="0.00" value="0">
                </div>
                <div class="mb-3">Total: <span id="summaryTotalPrice">₱0.00</span></div>
                <label for="cashTenderedInput" class="form-label">Cash Tendered</label>
                <input type="number" min="0" step="0.01" class="form-control" id="cashTenderedInput" placeholder="Enter cash amount">
                <button class="btn btn-dark finish-btn" id="finishBtn">Finish</button>
            </div>
        </div>
    </div>
</div>
<script>
window.inventoryItems = <?php echo json_encode($items); ?>;

// Webcam toggle logic
let webcamStream = null;
const toggleBtn = document.getElementById('toggleWebcamBtn');
const preview = document.getElementById('scannerPreview');
const placeholder = document.getElementById('placeholderIcon');
let videoElem = null;
let qrScanner = null;
let scanCooldown = false;

function onScanSuccess(decodedText, decodedResult) {
    if (scanCooldown) return; // Prevent multiple triggers
    scanCooldown = true;
    setTimeout(() => { scanCooldown = false; }, 1500); // 1.5s cooldown
    let id = null;
    try {
        // Try to parse as JSON
        const obj = JSON.parse(decodedText);
        if (obj.id) {
            id = obj.id;
        }
    } catch (e) {
        // Not JSON, use as is
        id = decodedText;
    }
    console.log('Scanned QR:', decodedText, 'Using id:', id);
    const item = window.inventoryItems.find(it => String(it.id) === String(id));
    console.log('Matched item:', item);
    if (!item) {
        alert("Item not found for QR: " + decodedText);
        return;
    }

    // Get qty from input
    let qtyToAdd = parseInt(qtyInput.value, 10);
    if (isNaN(qtyToAdd) || qtyToAdd < 1) qtyToAdd = 1;

    // Get current stock from the table (always up to date due to polling)
    let stock = 0;
    const row = document.querySelector('tr[data-id="' + item.id + '"]');
    if (row) {
        const tds = row.querySelectorAll('td');
        if (tds.length >= 4) {
            stock = parseInt(tds[3].textContent.trim(), 10) || 0;
        }
    }

    // Get current qty in cart
    const idx = storedItems.findIndex(it => it.id == item.id);
    const cartQty = idx !== -1 ? storedItems[idx].qty : 0;

    if (cartQty + qtyToAdd > stock) {
        document.getElementById('stockModalMsg').textContent = `Cannot add more. Quantity in cart exceeds available stock (${stock}).`;
        stockModal.show();
        return;
    }

    // Add or increment in sidebar
    if (idx !== -1) {
        storedItems[idx].qty += qtyToAdd;
    } else {
        storedItems.push({
            id: item.id,
            name: item.name,
            qty: qtyToAdd,
            price: parseFloat(item.price) || 0,
            discount: 0
        });
    }
    // Update DB, then UI
    updateTableStock(item.id, qtyToAdd);
    console.log('storedItems after scan:', storedItems);
    renderSummary();
    qtyInput.value = '1'; // Reset qty input after scan
}

function startQrScanner() {
    const qrReaderDiv = document.getElementById('qr-reader');
    if (!qrScanner) {
        qrScanner = new Html5Qrcode("qr-reader");
    }
    qrReaderDiv.style.display = '';
    qrScanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: 250
        },
        onScanSuccess
    ).catch(err => {
        alert("Unable to start QR scanner: " + err);
    });
}

function stopQrScanner() {
    const qrReaderDiv = document.getElementById('qr-reader');
    if (qrScanner) {
        qrScanner.stop().then(() => {
            qrReaderDiv.style.display = 'none';
        }).catch(err => {
            // Stop failed
        });
    } else {
        qrReaderDiv.style.display = 'none';
    }
}

function loadHtml5QrcodeScript(callback) {
    if (window.Html5Qrcode) {
        callback();
        return;
    }
    var script = document.createElement('script');
    script.src = "https://unpkg.com/html5-qrcode";
    script.onload = callback;
    document.head.appendChild(script);
}

function openWebcam() {
    // Hide placeholder icon
    if (placeholder) placeholder.style.display = 'none';
    toggleBtn.textContent = 'Close Webcam';
    // Dynamically load QR scanner library and start
    loadHtml5QrcodeScript(function() {
        startQrScanner();
    });
}

function closeWebcam() {
    // Show placeholder icon
    if (placeholder) placeholder.style.display = '';
    toggleBtn.textContent = 'Open Webcam';
    // Stop QR scanner
    stopQrScanner();
}

toggleBtn.addEventListener('click', function() {
    if (webcamStream) {
        closeWebcam();
    } else {
        openWebcam();
    }
});

// Item search filter
const itemSearchInput = document.getElementById('itemSearchInput');
const itemTableBody = document.getElementById('itemTableBody');
if (itemSearchInput && itemTableBody) {
    itemSearchInput.addEventListener('input', function() {
        const val = itemSearchInput.value.trim().toLowerCase();
        Array.from(itemTableBody.querySelectorAll('tr')).forEach(function(row) {
            // Always reset to visible first
            row.style.display = '';
            const text = row.textContent.toLowerCase();
            if (val !== '' && text.indexOf(val) === -1) {
                row.style.display = 'none';
            }
        });
    });
}

// Checkbox logic
const masterCheckbox = document.getElementById('masterCheckbox');
const rowCheckboxes = () => document.querySelectorAll('.rowCheckbox');
const itemTableBodyRows = () => document.querySelectorAll('#itemTableBody tr');

if (masterCheckbox) {
    masterCheckbox.addEventListener('change', function() {
        rowCheckboxes().forEach(cb => {
            cb.checked = masterCheckbox.checked;
            cb.closest('tr').classList.toggle('selected', cb.checked);
        });
    });
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('rowCheckbox')) {
        e.target.closest('tr').classList.toggle('selected', e.target.checked);
        // If any unchecked, uncheck master; if all checked, check master
        const all = rowCheckboxes();
        const checked = Array.from(all).filter(cb => cb.checked);
        if (checked.length === all.length) {
            masterCheckbox.checked = true;
        } else {
            masterCheckbox.checked = false;
        }
    }
});

// --- Sidebar summary logic ---
const summaryList = document.getElementById('summaryList');
const summaryTotalQty = document.getElementById('summaryTotalQty');
const summaryTotalPrice = document.getElementById('summaryTotalPrice');
const finishBtn = document.getElementById('finishBtn');
const qtyInput = document.getElementById('qtyInput');
const addBtn = document.getElementById('addBtn');
const cancelBtn = document.getElementById('cancelBtn');

// Store items as {id, name, qty, price, discount}
let storedItems = [];
let selectedRow = null;
let cartDiscount = 0;

// Helper: Render sidebar summary
function renderSummary() {
    summaryList.innerHTML = '';
    let totalQty = 0;
    let subtotal = 0;
    let totalDiscount = 0;
    
    if (storedItems.length === 0) {
        summaryList.innerHTML = '<div class="text-muted">No items added.</div>';
        document.getElementById('voidBtn').style.display = 'none';
    } else {
        document.getElementById('voidBtn').style.display = '';
        storedItems.forEach((item, idx) => {
            const itemSubtotal = item.qty * item.price;
            const itemDiscount = item.discount || 0;
            const itemDiscountAmount = (itemSubtotal * itemDiscount) / 100;
            const itemFinalPrice = itemSubtotal - itemDiscountAmount;
            
            totalQty += item.qty;
            subtotal += itemSubtotal;
            totalDiscount += itemDiscountAmount;
            
            const div = document.createElement('div');
            div.className = 'summary-list-item';
            div.innerHTML = `
                <input type="checkbox" class="summary-checkbox me-2" data-idx="${idx}">
                <span class="placeholder-icon"></span>
                <div class="flex-grow-1">
                    <div><strong>${item.name}</strong></div>
                    <div class="small text-muted">Qty: ${item.qty} | Price: ₱${parseFloat(item.price).toFixed(2)}</div>
                    <div class="small text-muted">
                        <input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm d-inline-block" 
                               style="width: 60px; height: 24px; font-size: 11px;" 
                               placeholder="0%" value="${item.discount || 0}" 
                               onchange="updateItemDiscount(${idx}, this.value)">
                        % discount
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">₱${itemFinalPrice.toFixed(2)}</div>
                </div>
            `;
            summaryList.appendChild(div);
        });
    }
    
    // Apply cart discount
    const cartDiscountAmount = (subtotal * cartDiscount) / 100;
    const finalTotal = subtotal - totalDiscount - cartDiscountAmount;
    
    summaryTotalQty.textContent = totalQty;
    document.getElementById('summarySubtotal').textContent = `₱${subtotal.toFixed(2)}`;
    summaryTotalPrice.textContent = `₱${finalTotal.toFixed(2)}`;
}

// Function to update individual item discount
function updateItemDiscount(itemIndex, discountValue) {
    const discount = parseFloat(discountValue) || 0;
    if (discount < 0 || discount > 100) {
        alert('Discount must be between 0 and 100%');
        return;
    }
    storedItems[itemIndex].discount = discount;
    renderSummary();
}

// --- Row selection and Qty input logic ---
function clearRowSelections() {
    document.querySelectorAll('.rowCheckbox').forEach(cb => {
        cb.checked = false;
        cb.closest('tr').classList.remove('selected');
    });
    selectedRow = null;
    qtyInput.value = '';
    qtyInput.disabled = true;
    addBtn.disabled = true;
    addBtn.title = 'Select an item first';
    // Always enable cash input
    const cashInput = document.getElementById('cashTenderedInput');
    if (cashInput) cashInput.disabled = false;
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('rowCheckbox')) {
        // Only allow one selection at a time
        document.querySelectorAll('.rowCheckbox').forEach(cb => {
            if (cb !== e.target) {
                cb.checked = false;
                cb.closest('tr').classList.remove('selected');
            }
        });
        if (e.target.checked) {
            selectedRow = e.target.closest('tr');
            qtyInput.disabled = false;
            qtyInput.value = '1';
            addBtn.disabled = false;
            addBtn.title = 'Add selected item to cart';
        } else {
            selectedRow = null;
            qtyInput.value = '';
            qtyInput.disabled = true;
            addBtn.disabled = true;
            addBtn.title = 'Select an item first';
        }
    }
});

qtyInput.addEventListener('input', function() {
    let val = parseInt(qtyInput.value, 10);
    if (isNaN(val) || val < 1) val = 1;
    qtyInput.value = val;
});

// Add Bootstrap modal for stock warning
const stockModalHtml = `
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title" id="stockModalLabel">Stock Limit Exceeded</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="stockModalMsg">You cannot add more than the available stock.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>`;
document.body.insertAdjacentHTML('beforeend', stockModalHtml);
const stockModal = new bootstrap.Modal(document.getElementById('stockModal'));

addBtn.addEventListener('click', function() {
    if (!selectedRow) return;
    const tds = selectedRow.querySelectorAll('td');
    const name = tds[1].textContent.trim();
    const details = tds[2].textContent.trim();
    const stock = parseInt(tds[3].textContent.trim(), 10) || 1;
    const id = selectedRow.getAttribute('data-id');
    
    // Get the actual price from inventory items
    const inventoryItem = window.inventoryItems.find(item => String(item.id) === String(id));
    const price = inventoryItem ? parseFloat(inventoryItem.price) || 0 : 0;
    
    let qty = parseInt(qtyInput.value, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    
    // Check if already in storedItems
    const idx = storedItems.findIndex(it => it.id === id);
    const cartQty = idx !== -1 ? storedItems[idx].qty : 0;
    if (cartQty + qty > stock) {
        document.getElementById('stockModalMsg').textContent = `Cannot add more. Quantity in cart exceeds available stock (${stock}).`;
        stockModal.show();
        return;
    }
    
    if (idx !== -1) {
        storedItems[idx].qty += qty;
    } else {
        storedItems.push({id, name, qty, price, discount: 0});
    }
    
    // Only update UI, not DB
    updateTableStock(id, qty);
    renderSummary();
    clearRowSelections();
});

cancelBtn.addEventListener('click', function() {
    clearRowSelections();
});

// Finish button clears the list
finishBtn.addEventListener('click', function() {
    // Update DB for all items in storedItems
    let itemsToUpdate = storedItems.slice();
    let updateCount = 0;
    if (itemsToUpdate.length === 0) {
        storedItems = [];
        cartDiscount = 0;
        if (cartDiscountInput) cartDiscountInput.value = '0';
        renderSummary();
        qtyInput.value = '1'; // Reset qty input after finish
        // Always enable cash input
        const cashInput = document.getElementById('cashTenderedInput');
        if (cashInput) cashInput.disabled = false;
        return;
    }
    // Get cash tendered
    const cashInput = document.getElementById('cashTenderedInput');
    const cashTendered = parseFloat(cashInput && cashInput.value ? cashInput.value : '0');
    
    // Calculate totals with discounts
    let subtotal = 0;
    let totalItemDiscount = 0;
    itemsToUpdate.forEach(item => { 
        const itemSubtotal = item.qty * item.price;
        const itemDiscount = item.discount || 0;
        const itemDiscountAmount = (itemSubtotal * itemDiscount) / 100;
        subtotal += itemSubtotal;
        totalItemDiscount += itemDiscountAmount;
    });
    
    const cartDiscountAmount = (subtotal * cartDiscount) / 100;
    const total = subtotal - totalItemDiscount - cartDiscountAmount;
    
    if (isNaN(cashTendered) || cashTendered < total) {
        cashModal.show();
        return;
    }
    // Insert receipt and receipt_items via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_receipt.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && resp.status === 'success') {
                    // Show receipt modal with improved formatting
                    document.getElementById('receiptModalBody').innerHTML = `
                        <div class="text-center mb-3">
                            <h4 class="mb-1">Thank you for your purchase!</h4>
                            <div class="text-muted">Receipt #${resp.receipt_id}</div>
                        </div>
                        <table class="table table-bordered mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsToUpdate.map(item => {
                                    const itemSubtotal = item.qty * item.price;
                                    const itemDiscount = item.discount || 0;
                                    const itemDiscountAmount = (itemSubtotal * itemDiscount) / 100;
                                    const itemFinalPrice = itemSubtotal - itemDiscountAmount;
                                    return `
                                        <tr>
                                            <td>${item.name}</td>
                                            <td class="text-end">${item.qty}</td>
                                            <td class="text-end">₱${parseFloat(item.price).toFixed(2)}</td>
                                            <td class="text-end">${itemDiscount > 0 ? `${itemDiscount}% (-₱${itemDiscountAmount.toFixed(2)})` : '-'}</td>
                                            <td class="text-end">₱${itemFinalPrice.toFixed(2)}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-end mb-2">
                            <div style="min-width:280px;">
                                <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                    <span>Subtotal:</span>
                                    <span>₱${subtotal.toFixed(2)}</span>
                                </div>
                                ${totalItemDiscount > 0 ? `
                                <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                    <span>Item Discounts:</span>
                                    <span class="text-danger">-₱${totalItemDiscount.toFixed(2)}</span>
                                </div>
                                ` : ''}
                                ${cartDiscountAmount > 0 ? `
                                <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                    <span>Cart Discount (${cartDiscount}%):</span>
                                    <span class="text-danger">-₱${cartDiscountAmount.toFixed(2)}</span>
                                </div>
                                ` : ''}
                                <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                    <span>Total:</span>
                                    <span><strong>₱${total.toFixed(2)}</strong></span>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                    <span>Cash Tendered:</span>
                                    <span>₱${cashTendered.toFixed(2)}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Change:</span>
                                    <span class="fw-bold text-success">₱${(cashTendered-total).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-center text-muted" style="font-size:13px;">${new Date().toLocaleString()}</div>
                    `;
                    receiptModal.show();
                    storedItems = [];
                    cartDiscount = 0;
                    if (cartDiscountInput) cartDiscountInput.value = '0';
                    renderSummary();
                    qtyInput.value = '1';
                    if (cashInput) {
                        cashInput.value = '';
                        cashInput.disabled = false;
                    }
                } else {
                    alert(resp.message || 'Failed to save receipt.');
                }
            } catch (e) {
                alert('Invalid server response');
            }
        }
    };
    xhr.send(JSON.stringify({
        items: itemsToUpdate,
        total: total,
        subtotal: subtotal,
        total_item_discount: totalItemDiscount,
        cart_discount: cartDiscount,
        cart_discount_amount: cartDiscountAmount,
        cash_tendered: cashTendered
    }));
});

// Void button logic
const voidBtn = document.getElementById('voidBtn');
voidBtn.addEventListener('click', function() {
    // Find checked summary items
    const checkedIdxs = Array.from(document.querySelectorAll('.summary-checkbox:checked')).map(cb => parseInt(cb.getAttribute('data-idx'), 10));
    if (checkedIdxs.length === 0) return;
    // Remove items in reverse order to avoid index shift
    checkedIdxs.sort((a,b) => b-a).forEach(idx => storedItems.splice(idx, 1));
    renderSummary();
    qtyInput.value = '1'; // Reset qty input after void
    // Always enable cash input
    const cashInput = document.getElementById('cashTenderedInput');
    if (cashInput) cashInput.disabled = false;
});

// Update stock in the main table
function updateTableStock(itemId, qtyAdded) {
    // Find the item in inventoryItems
    const itemData = window.inventoryItems.find(it => String(it.id) === String(itemId));
    if (!itemData) return;
    // Find the row in the table by matching name (since id is not in the table)
    const rows = document.querySelectorAll('#itemTableBody tr');
    rows.forEach(row => {
        const tds = row.querySelectorAll('td');
        if (tds.length < 4) return;
        const name = tds[1].textContent.trim();
        if (name === itemData.name) {
            let stockCell = tds[3];
            let currentStock = parseInt(stockCell.textContent.trim(), 10) || 0;
            let newStock = Math.max(currentStock - qtyAdded, 0);
            stockCell.textContent = newStock;
            // Optionally gray out row if out of stock
            if (newStock === 0) {
                row.classList.add('text-muted');
            }
        }
    });
}

// Helper: Update stock in DB via AJAX
function updateStockInDB(itemId, qty, onSuccess, onError) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_stock.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && resp.status === 'success') {
                    if (onSuccess) onSuccess();
                } else {
                    if (onError) onError(resp.message || 'Update failed');
                }
            } catch (e) {
                if (onError) onError('Invalid server response');
            }
        }
    };
    xhr.send('id=' + encodeURIComponent(itemId) + '&qty=' + encodeURIComponent(qty));
}

// Polling: update stock cells in the inventory table every 5 seconds
function pollInventoryStock() {
    fetch('get_inventory.php?t=' + Date.now())
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && Array.isArray(data.items)) {
                const rows = document.querySelectorAll('#itemTableBody tr');
                data.items.forEach(item => {
                    rows.forEach(row => {
                        if (row.getAttribute('data-id') === String(item.id)) {
                            const tds = row.querySelectorAll('td');
                            if (tds.length < 4) return;
                            tds[3].textContent = item.stock;
                            if (parseInt(item.stock, 10) === 0) {
                                row.classList.add('text-muted');
                            } else {
                                row.classList.remove('text-muted');
                            }
                        }
                    });
                });
            }
        })
        .catch(() => {});
    setTimeout(pollInventoryStock, 5000);
}

pollInventoryStock();

// Cart discount input event listener
const cartDiscountInput = document.getElementById('cartDiscountInput');
if (cartDiscountInput) {
    cartDiscountInput.addEventListener('input', function() {
        const discount = parseFloat(this.value) || 0;
        if (discount < 0 || discount > 100) {
            this.value = Math.max(0, Math.min(100, discount));
        }
        cartDiscount = parseFloat(this.value) || 0;
        renderSummary();
    });
}

// Initialize button states
addBtn.disabled = true;

// Initial render
renderSummary();

// Add Bootstrap modal for scanner help
const scannerHelpModalHtml = `
<div class="modal fade" id="scannerHelpModal" tabindex="-1" aria-labelledby="scannerHelpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info-subtle">
        <h5 class="modal-title" id="scannerHelpModalLabel">Scanner Help</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>To use the scanner:</p>
        <ul>
          <li>Click <strong>Open Webcam</strong> to activate the QR scanner.</li>
          <li>Point your camera at a QR code to scan an item.</li>
          <li>Select an item from the list and enter the quantity to add to cart.</li>
          <li>Click <strong>Add</strong> to add the item to your cart.</li>
          <li>Click <strong>Finish</strong> to complete the transaction.</li>
        </ul>
        <p>If you need further assistance, please contact support.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>`;
document.body.insertAdjacentHTML('beforeend', scannerHelpModalHtml);
const scannerHelpModal = new bootstrap.Modal(document.getElementById('scannerHelpModal'));

document.querySelector('.help-btn').addEventListener('click', function() {
    scannerHelpModal.show();
});

// Add Bootstrap modal for insufficient cash and receipt
const receiptModalHtml = `
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success-subtle">
        <h5 class="modal-title" id="receiptModalLabel">Receipt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="receiptModalBody">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="cashModal" tabindex="-1" aria-labelledby="cashModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger-subtle">
        <h5 class="modal-title" id="cashModalLabel">Insufficient Cash</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Cash tendered is less than the total amount due.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>`;
document.body.insertAdjacentHTML('beforeend', receiptModalHtml);
const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
const cashModal = new bootstrap.Modal(document.getElementById('cashModal'));
</script>
<script src="bootstrap5/js/bootstrap.bundle.min.js"></script> 