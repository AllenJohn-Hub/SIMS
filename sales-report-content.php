<?php
require_once 'config.php';
// Date range filter
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Summary stats
$summary = [
    'total_sales' => 0,
    'total_transactions' => 0,
    'total_items' => 0,
    'avg_sale' => 0,
    'best_seller' => ''
];

// Get summary
$sql = "
    SELECT 
        SUM(r.total_amount) as total_sales,
        COUNT(r.receipt_id) as total_transactions,
        SUM(ri.quantity) as total_items,
        AVG(r.total_amount) as avg_sale
    FROM receipt r
    LEFT JOIN receipt_items ri ON r.receipt_id = ri.receipt_id
    WHERE DATE(r.date_issued) BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$stmt->bind_result($summary['total_sales'], $summary['total_transactions'], $summary['total_items'], $summary['avg_sale']);
$stmt->fetch();
$stmt->close();

// Best seller
$best_seller = '';
$sql = "
    SELECT i.name, SUM(ri.quantity) as qty_sold
    FROM receipt_items ri
    JOIN inventory i ON ri.inventory_id = i.id
    JOIN receipt r ON ri.receipt_id = r.receipt_id
    WHERE DATE(r.date_issued) BETWEEN ? AND ?
    GROUP BY ri.inventory_id
    ORDER BY qty_sold DESC
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$stmt->bind_result($best_seller, $qty_sold);
if ($stmt->fetch()) {
    $summary['best_seller'] = "$best_seller ($qty_sold sold)";
}
$stmt->close();

// Sales breakdown
$sales = [];
$sql = "
    SELECT r.receipt_id, r.date_issued, r.total_amount, r.cash_tendered, r.change_due, COUNT(ri.item_id) as item_count
    FROM receipt r
    LEFT JOIN receipt_items ri ON r.receipt_id = ri.receipt_id
    WHERE DATE(r.date_issued) BETWEEN ? AND ?
    GROUP BY r.receipt_id
    ORDER BY r.date_issued DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
}
$stmt->close();

// Top products
$top_products = [];
$sql = "
    SELECT i.name, SUM(ri.quantity) as qty_sold, SUM(ri.quantity * ri.price_each) as total_sales
    FROM receipt_items ri
    JOIN inventory i ON ri.inventory_id = i.id
    JOIN receipt r ON ri.receipt_id = r.receipt_id
    WHERE DATE(r.date_issued) BETWEEN ? AND ?
    GROUP BY ri.inventory_id
    ORDER BY qty_sold DESC
    LIMIT 10
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $top_products[] = $row;
}
$stmt->close();

// Item sold list (all products sold in the date range)
$item_sold_list = [];
$sql = "
    SELECT i.name, SUM(ri.quantity) as qty_sold, SUM(ri.quantity * ri.price_each) as total_sales
    FROM receipt_items ri
    JOIN inventory i ON ri.inventory_id = i.id
    JOIN receipt r ON ri.receipt_id = r.receipt_id
    WHERE DATE(r.date_issued) BETWEEN ? AND ?
    GROUP BY ri.inventory_id
    ORDER BY i.name ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $item_sold_list[] = $row;
}
$stmt->close();
?>

<div class="report-card card shadow-sm p-4 mb-4 bg-white border-0">
    <h2 class="mb-4 fw-bold text-primary">Sales Report</h2>
    <form class="row g-3 mb-4 align-items-end" method="get">
        <div class="col-auto">
            <label for="start" class="form-label">From</label>
            <input type="date" class="form-control rounded-pill" id="start" name="start" value="<?=htmlspecialchars($start)?>">
        </div>
        <div class="col-auto">
            <label for="end" class="form-label">To</label>
            <input type="date" class="form-control rounded-pill" id="end" name="end" value="<?=htmlspecialchars($end)?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Filter</button>
        </div>
    </form>
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card text-center h-100 border-0 shadow-sm bg-light">
                <div class="card-body py-3">
                    <div class="h4 mb-1 text-success">₱<?=number_format($summary['total_sales'],2)?></div>
                    <div class="text-muted small">Total Sales</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100 border-0 shadow-sm bg-light">
                <div class="card-body py-3">
                    <div class="h4 mb-1 text-info"><?=$summary['total_transactions']?></div>
                    <div class="text-muted small">Transactions</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100 border-0 shadow-sm bg-light">
                <div class="card-body py-3">
                    <div class="h4 mb-1 text-warning"><?=$summary['total_items']?></div>
                    <div class="text-muted small">Items Sold</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100 border-0 shadow-sm bg-light">
                <div class="card-body py-3">
                    <div class="h6 mb-1 text-primary"><?=htmlspecialchars($summary['best_seller'])?></div>
                    <div class="text-muted small">Best Seller</div>
                </div>
            </div>
        </div>
    </div>
    <h4 class="mt-4 mb-2 fw-semibold text-secondary">Sales Breakdown</h4>
    <div class="table-responsive mb-4" style="max-height:400px;overflow-y:auto;">
        <table class="table table-bordered table-hover align-middle rounded shadow-sm">
            <thead class="table-primary">
                <tr>
                    <th>Date</th>
                    <th>Receipt #</th>
                    <th>Total</th>
                    <th>Cash</th>
                    <th>Change</th>
                    <th>Item Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                <tr><td colspan="6" class="text-center text-muted">No sales found for this period.</td></tr>
                <?php else: 
                $grand_total = 0;
                $grand_cash = 0;
                $grand_change = 0;
                $grand_items = 0;
                foreach($sales as $row): 
                    $grand_total += $row['total_amount'];
                    $grand_cash += $row['cash_tendered'];
                    $grand_change += $row['change_due'];
                    $grand_items += $row['item_count'];
                ?>
                <tr>
                    <td><?=htmlspecialchars($row['date_issued'])?></td>
                    <td><?=$row['receipt_id']?></td>
                    <td class="text-success">₱<?=number_format($row['total_amount'],2)?></td>
                    <td>₱<?=number_format($row['cash_tendered'],2)?></td>
                    <td>₱<?=number_format($row['change_due'],2)?></td>
                    <td><?=$row['item_count']?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="fw-bold text-end">Total</td>
                    <td></td>
                    <td class="fw-bold text-success">₱<?=number_format($grand_total,2)?></td>
                    <td class="fw-bold text-success">₱<?=number_format($grand_cash,2)?></td>
                    <td class="fw-bold text-success">₱<?=number_format($grand_change,2)?></td>
                    <td class="fw-bold text-success"><?=$grand_items?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Items Sold List Section -->
    <h4 class="mt-4 mb-2 fw-semibold text-secondary">Items Sold List</h4>
    <div class="table-responsive mb-4" style="max-height:400px;overflow-y:auto;">
        <table class="table table-bordered table-hover align-middle rounded shadow-sm">
            <thead class="table-info">
                <tr>
                    <th>Product</th>
                    <th>Qty Sold</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($item_sold_list)): ?>
                <tr><td colspan="3" class="text-center text-muted">No items sold for this period.</td></tr>
                <?php else: 
                $total_qty = 0;
                $total_sales = 0;
                foreach($item_sold_list as $row): 
                    $total_qty += $row['qty_sold'];
                    $total_sales += $row['total_sales'];
                ?>
                <tr>
                    <td><?=htmlspecialchars($row['name'])?></td>
                    <td><?=$row['qty_sold']?></td>
                    <td class="text-success">₱<?=number_format($row['total_sales'],2)?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="fw-bold text-end">Total</td>
                    <td class="fw-bold"><?=$total_qty?></td>
                    <td class="fw-bold text-success">₱<?=number_format($total_sales,2)?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-end mb-4">
        <button class="btn btn-success rounded-pill px-4" onclick="printSoldItemsList()">
            <i class="bi bi-printer me-2"></i>Print Sold Items List
        </button>
    </div>
    <h4 class="mt-4 mb-2 fw-semibold text-secondary">Top Products</h4>
    <div class="table-responsive mb-4" style="max-height:400px;overflow-y:auto;">
        <table class="table table-bordered table-hover align-middle rounded shadow-sm">
            <thead class="table-info">
                <tr>
                    <th>Product</th>
                    <th>Qty Sold</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_products)): ?>
                <tr><td colspan="3" class="text-center text-muted">No product sales for this period.</td></tr>
                <?php else: foreach($top_products as $row): ?>
                <tr>
                    <td><?=htmlspecialchars($row['name'])?></td>
                    <td><?=$row['qty_sold']?></td>
                    <td class="text-success">₱<?=number_format($row['total_sales'],2)?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-end">
        <button class="btn btn-success rounded-pill px-4" onclick="printClassicReport()">
            <i class="bi bi-printer me-2"></i>Print Report
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="get"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            fetch('sales-report-content.php?' + params)
                .then(res => res.text())
                .then(html => {
                    // Replace only the report content, not the whole page
                    // Find the closest parent with class 'right-content' or fallback to body
                    let wrapper = form.closest('.right-content') || document.body;
                    wrapper.innerHTML = html;
                });
        });
    }
});

// Place the printClassicReport script at the end for reliability
window.printClassicReport = function() {
  var reportDiv = document.getElementById('classic-print-report');
  var table = reportDiv.querySelector('table');
  var rows = table.querySelectorAll('tbody tr');
  var totals = [0, 0, 0, 0, 0]; // [Total, Cash, Change, Item Count, Row Count]
  rows.forEach(function(row) {
    var cells = row.querySelectorAll('td');
    if (cells.length === 6 && !row.textContent.includes('No sales found')) {
      // cells: [Date, Receipt #, Total, Cash, Change, Item Count]
      totals[0] += parseFloat(cells[2].textContent.replace(/[^\d.\-]/g, '')) || 0; // Total
      totals[1] += parseFloat(cells[3].textContent.replace(/[^\d.\-]/g, '')) || 0; // Cash
      totals[2] += parseFloat(cells[4].textContent.replace(/[^\d.\-]/g, '')) || 0; // Change
      totals[3] += parseInt(cells[5].textContent) || 0; // Item Count
      totals[4]++;
    }
  });
  var totalRow = `<tr>
    <td colspan="1" style="font-weight:bold;text-align:right;">Total</td>
    <td></td>
    <td style="font-weight:bold; color:green;">₱${totals[0].toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    <td style="font-weight:bold; color:green;">₱${totals[1].toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    <td style="font-weight:bold; color:green;">₱${totals[2].toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    <td style="font-weight:bold; color:green;">${totals[3]}</td>
  </tr>`;
  var tableHTML = table.outerHTML.replace('</tbody>', totalRow + '</tbody>');
  var reportHtml = reportDiv.innerHTML.replace(table.outerHTML, tableHTML);
  var win = window.open('', '', 'width=900,height=700');
  win.document.write(`
    <html>
      <head>
        <title>Sales Report</title>
        <style>
          @media print {
            @page { size: A4; margin: 12mm; }
            body { margin: 0; padding: 0; }
            #classic-print-report { display: block !important; width: 100% !important; margin: 0 !important; border: 2px solid #333; padding: 24px 32px 16px 32px; box-sizing: border-box !important; }
            #classic-print-report table { width: 100%; border-collapse: collapse; }
            #classic-print-report th, #classic-print-report td { border: 1px solid #333; padding: 4px 8px; text-align: center; }
          }
        </style>
      </head>
      <body onload="window.print();window.close();">
        <div id="classic-print-report">${reportHtml}</div>
      </body>
    </html>
  `);
  win.document.close();
}

window.printSoldItemsList = function() {
    // Clone the Items Sold List table for printing
    var soldListSection = document.createElement('div');
    var table = document.querySelectorAll('.table-responsive.mb-4')[1].querySelector('table');
    
    // Create a clone of the table to avoid modifying the original
    var tableClone = table.cloneNode(true);
    
    // Remove any existing total row from the clone
    var rows = tableClone.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (
            cells.length === 3 &&
            cells[0].textContent.trim().toLowerCase() === 'total'
        ) {
            row.parentNode.removeChild(row);
        }
    });
    
    // Now recalculate totals from the original table (not the clone)
    var totalQty = 0;
    var totalSales = 0;
    var originalRows = table.querySelectorAll('tbody tr');
    originalRows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length === 3 && !row.textContent.includes('No items sold') && cells[0].textContent.trim().toLowerCase() !== 'total') {
            totalQty += parseInt(cells[1].textContent) || 0;
            var salesText = cells[2].textContent.replace(/[^\d.]/g, '');
            totalSales += parseFloat(salesText) || 0;
        }
    });
    
    var totalRow = `<tr><td style="font-weight:bold;text-align:right;">Total</td><td style="font-weight:bold;">${totalQty}</td><td style="font-weight:bold; color:green;">₱${totalSales.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td></tr>`;
    var tableHTML = tableClone.outerHTML.replace('</tbody>', totalRow + '</tbody>');
    soldListSection.innerHTML = `
        <div style="font-weight:bold;font-size:1.5em;text-align:center;margin-bottom:8px;">Items Sold List</div>
        ` + document.querySelectorAll('h4.fw-semibold.text-secondary')[1].outerHTML + '<div class="table-responsive mb-4">' + tableHTML + '</div>';
    var win = window.open('', '', 'width=900,height=700');
    win.document.write(`
        <html>
          <head>
            <title>Items Sold List</title>
            <style>
              @media print {
                @page { size: A4; margin: 12mm; }
                body { margin: 0; padding: 0; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #333; padding: 4px 8px; text-align: center; }
              }
              body { font-family: Arial, sans-serif; font-size: 15px; margin: 0; padding: 24px; }
              table { width: 100%; border-collapse: collapse; margin: 0 auto 16px auto; font-size: 14px; }
              th, td { border: 1px solid #333; padding: 4px 8px; text-align: center; }
            </style>
          </head>
          <body onload="window.print();window.close();">
            ${soldListSection.innerHTML}
          </body>
        </html>
      `);
    win.document.close();
}
</script>

<style>
@media print {
  @page {
    size: A4;
    margin: 12mm;
  }
  html, body {
    height: 100% !important;
    min-height: 100% !important;
  }
  body {
    background: #fff !important;
    color: #000 !important;
    font-family: Arial, sans-serif !important;
    font-size: 15px !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    height: 100% !important;
    min-height: 100% !important;
  }
  /* Hide all navigation/sidebar/tabs and filter */
  form[method="get"], .btn, button, .print-btn, .left-sidebar, .sidebar-title, nav, header, footer, .row.mb-4.g-3, .table-responsive.mb-4, .text-end, .fw-bold.text-primary, .fw-semibold.text-secondary, .card, .card-body, .bg-light, .bg-white, .border-0, .rounded-pill, .shadow-sm,
  .nav, .nav-tabs, .tabs, .tab-content, .navbar, .sidebar, .right-sidebar, .main-header, .main-footer, .page-header, .page-footer, .tab-pane, .tab, .tabbar, .tab-navigation, .tab-links, .tab-header, .tab-menu, .tablist, .tab-panel, .tab-controls {
    display: none !important;
  }
  #classic-print-report .top-products-print {
    display: none !important;
  }
  #classic-print-report {
    display: block !important;
    margin: auto !important;
    width: 95% !important;
    max-width: 1100px !important;
    background: #fff !important;
    color: #000 !important;
    border: 2px solid #333 !important;
    padding: 24px 32px 16px 32px !important;
    page-break-inside: avoid;
    text-align: center !important;
    box-sizing: border-box !important;
  }
  #classic-print-report table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 auto 16px auto !important;
    font-size: 14px !important;
  }
  #classic-print-report th, #classic-print-report td {
    border: 1px solid #333 !important;
    padding: 4px 8px !important;
    text-align: center !important;
  }
  #classic-print-report .signature-row {
    display: flex;
    justify-content: space-between;
    gap: 80px;
    margin-top: 40px;
    font-size: 15px;
    width: 100%;
  }
  #classic-print-report .signature-row > div {
    text-align: left;
    flex: 1;
  }
}
@media screen {
  #classic-print-report {
    display: none;
  }
}
</style>

<!-- Classic print layout, hidden on screen, shown only when printing -->
<div id="classic-print-report" style="display:none;">
    <div style="margin-bottom:16px;">
        <div style="font-weight:bold;font-size:2em;text-align:center;margin-bottom:8px;">Sales Report</div>
        <div style="text-align:right;font-size:13px;">From: <?=htmlspecialchars($start)?> To: <?=htmlspecialchars($end)?></div>
    </div>
    <table>
        <thead>
            <tr style="background:#eee;">
                <th>Date</th>
                <th>Receipt #</th>
                <th>Total</th>
                <th>Cash</th>
                <th>Change</th>
                <th>Item Count</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
            <tr><td colspan="6" style="color:#888;">No sales found for this period.</td></tr>
            <?php else: foreach($sales as $row): ?>
            <tr>
                <td><?=htmlspecialchars($row['date_issued'])?></td>
                <td><?=$row['receipt_id']?></td>
                <td>₱<?=number_format($row['total_amount'],2)?></td>
                <td>₱<?=number_format($row['cash_tendered'],2)?></td>
                <td>₱<?=number_format($row['change_due'],2)?></td>
                <td><?=$row['item_count']?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>