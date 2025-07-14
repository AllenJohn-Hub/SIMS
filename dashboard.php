<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: index.php");
    exit;
}

// Include database connection
require_once('config.php');

// Fetch dashboard statistics
$stats = [];

// Total Products
$sql_total_products = "SELECT COUNT(*) as total FROM inventory";
$result = $conn->query($sql_total_products);
$stats['total_products'] = $result->fetch_assoc()['total'];

// Total Value of Inventory
$sql_total_value = "SELECT SUM(stock * price) as total_value FROM inventory";
$result = $conn->query($sql_total_value);
$stats['total_value'] = $result->fetch_assoc()['total_value'] ?? 0;

// Low Stock Items (less than 10 items)
$sql_low_stock = "SELECT COUNT(*) as total FROM inventory WHERE stock < 10";
$result = $conn->query($sql_low_stock);
$stats['low_stock'] = $result->fetch_assoc()['total'];

// Out of Stock Items
$sql_out_of_stock = "SELECT COUNT(*) as total FROM inventory WHERE stock = 0";
$result = $conn->query($sql_out_of_stock);
$stats['out_of_stock'] = $result->fetch_assoc()['total'];

// Categories count
$sql_categories = "SELECT c.category, COUNT(*) as count FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id GROUP BY c.category ORDER BY count DESC";
$result = $conn->query($sql_categories);
$categories_data = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories_data[] = $row;
    }
}

// Recent items added (last 5)
$sql_recent = "SELECT i.name, c.category, i.stock, i.price, i.date_added FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id ORDER BY i.date_added DESC LIMIT 5";
$result = $conn->query($sql_recent);
$recent_items = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['is_low_stock'] = ($row['stock'] < 10 && $row['stock'] > 0);
        $row['is_out_of_stock'] = ($row['stock'] == 0);
        $recent_items[] = $row;
    }
}

// Top selling items (based on stock movement - for demo, using items with highest value)
$sql_top_items = "SELECT i.name, c.category, i.stock, i.price, (i.stock * i.price) as total_value FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id ORDER BY total_value DESC LIMIT 5";
$result = $conn->query($sql_top_items);
$top_items = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['is_low_stock'] = ($row['stock'] < 10 && $row['stock'] > 0);
        $row['is_out_of_stock'] = ($row['stock'] == 0);
        $top_items[] = $row;
    }
}

$conn->close();
?>

<!-- Embedded CSS Styling -->
<style>
body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', sans-serif;
}
.stat-card,
.chart-card,
.activity-card,
.quick-actions-card {
    background-color: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease-in-out;
    height: 100%;
    padding: 1rem;
}
.stat-card:hover,
.chart-card:hover,
.activity-card:hover,
.quick-actions-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.stat-card-icon i,
.quick-action-icon i {
    font-size: 1.75rem;
}
.stat-card-number,
.top-item-value .value-amount {
    font-size: 1.5rem;
    font-weight: bold;
}
.stat-card-label,
.chart-card-title,
.quick-actions-title,
.activity-card-title {
    font-size: 0.95rem;
    font-weight: 600;
}
.chart-card-body {
    min-height: 250px;
}
.activity-item,
.top-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}
.activity-item:last-child,
.top-item:last-child {
    border-bottom: none;
}
.quick-action-item {
    cursor: pointer;
    display: flex;
    padding: 0.75rem;
    border-radius: 0.75rem;
    transition: background 0.3s;
    align-items: center;
}
.quick-action-item:hover {
    background-color: #f1f1f1;
}
.quick-action-icon {
    width: 36px;
    height: 36px;
    background-color: #e7e7e7;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 10px;
}
.view-all-link {
    font-size: 0.9rem;
    text-decoration: none;
}
.current-time {
    font-size: 0.9rem;
    color: #555;
}
.category-badge {
    background-color: #e9ecef;
    padding: 2px 6px;
    border-radius: 0.5rem;
    font-size: 0.8rem;
}
</style>

<div class="container-fluid p-4">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-header">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h1>
                <p class="welcome-subtitle">Here's what's happening with your inventory today</p>
                <div class="current-time d-flex align-items-center">
                    <i class="bi bi-clock me-2"></i>
                    <span id="current-time" class="fw-semibold"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-icon"><i class="bi bi-box-seam"></i></div>
                <div class="stat-card-content">
                    <h3 class="stat-card-number"><?php echo number_format($stats['total_products']); ?></h3>
                    <p class="stat-card-label">Total Products</p>
                    <div class="stat-card-trend"><i class="bi bi-arrow-up"></i> <span>Active inventory items</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-success">
                <div class="stat-card-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-card-content">
                    <h3 class="stat-card-number">₱<?php echo number_format($stats['total_value'], 2); ?></h3>
                    <p class="stat-card-label">Total Inventory Value</p>
                    <div class="stat-card-trend"><i class="bi bi-graph-up"></i> <span>Current stock value</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="stat-card-content">
                    <h3 class="stat-card-number"><?php echo number_format($stats['low_stock']); ?></h3>
                    <p class="stat-card-label">Low Stock Items</p>
                    <div class="stat-card-trend"><i class="bi bi-arrow-down"></i> <span>Need restocking</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-danger">
                <div class="stat-card-icon"><i class="bi bi-x-circle"></i></div>
                <div class="stat-card-content">
                    <h3 class="stat-card-number"><?php echo number_format($stats['out_of_stock']); ?></h3>
                    <p class="stat-card-label">Out of Stock</p>
                    <div class="stat-card-trend"><i class="bi bi-exclamation"></i> <span>Requires attention</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart and Actions -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="chart-card-title"><i class="bi bi-pie-chart me-2"></i> Category Distribution</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshChart()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="chart-card-body">
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="quick-actions-card">
                <h5 class="quick-actions-title mb-3"><i class="bi bi-lightning me-2"></i> Quick Actions</h5>
                <div class="quick-action-item" onclick="loadContent('inventory.php')">
                    <div class="quick-action-icon"><i class="bi bi-plus-circle"></i></div>
                    <div class="quick-action-content">
                        <h6>Add New Item</h6>
                        <p>Add a new product to inventory</p>
                    </div>
                </div>
                <div class="quick-action-item" onclick="loadContent('qr-print-content.php')">
                    <div class="quick-action-icon"><i class="bi bi-printer"></i></div>
                    <div class="quick-action-content">
                        <h6>Print QR Codes</h6>
                        <p>Generate and print QR codes</p>
                    </div>
                </div>
                <div class="quick-action-item" onclick="loadContent('scan-content.php')">
                    <div class="quick-action-icon"><i class="bi bi-qr-code-scan"></i></div>
                    <div class="quick-action-content">
                        <h6>Scan QR Code</h6>
                        <p>Scan and view item details</p>
                    </div>
                </div>
                <div class="quick-action-item" onclick="loadContent('sales-report-content.php')">
                    <div class="quick-action-icon"><i class="bi bi-graph-up"></i></div>
                    <div class="quick-action-content">
                        <h6>View Reports</h6>
                        <p>Check sales and analytics</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent + Top Items -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="activity-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="activity-card-title"><i class="bi bi-clock-history me-2"></i> Recently Added Items</h5>
                    <a href="#" onclick="loadContent('inventory.php')" class="view-all-link">View All</a>
                </div>
                <div class="activity-card-body">
                    <?php if (empty($recent_items)): ?>
                        <div class="empty-state"><i class="bi bi-inbox"></i><p>No recent items added</p></div>
                    <?php else: ?>
                        <?php foreach ($recent_items as $item): ?>
                            <div class="activity-item d-flex">
                                <div class="me-3"><i class="bi bi-box-seam"></i></div>
                                <div>
                                    <h6><?php echo htmlspecialchars($item['name']); ?>
                                        <?php if ($item['is_out_of_stock']): ?>
                                            <span class="badge bg-danger ms-2">Out of Stock</span>
                                        <?php elseif ($item['is_low_stock']): ?>
                                            <span class="badge bg-warning text-dark ms-2">Low Stock</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1">
                                        <span class="category-badge"><?php echo htmlspecialchars($item['category']); ?></span>
                                        <span class="ms-2">Stock: <?php echo $item['stock']; ?></span>
                                        <span class="ms-2">₱<?php echo number_format($item['price'], 2); ?></span>
                                    </p>
                                    <small><?php echo date('M j, Y', strtotime($item['date_added'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="activity-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="activity-card-title"><i class="bi bi-trophy me-2"></i> Top Items by Value</h5>
                    <a href="#" onclick="loadContent('inventory.php')" class="view-all-link">View All</a>
                </div>
                <div class="activity-card-body">
                    <?php if (empty($top_items)): ?>
                        <div class="empty-state"><i class="bi bi-trophy"></i><p>No items to display</p></div>
                    <?php else: ?>
                        <?php foreach ($top_items as $index => $item): ?>
                            <div class="top-item d-flex align-items-center">
                                <div class="me-3"><span class="fw-bold"><?php echo $index + 1; ?>.</span></div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?>
                                        <?php if ($item['is_out_of_stock']): ?>
                                            <span class="badge bg-danger ms-2">Out of Stock</span>
                                        <?php elseif ($item['is_low_stock']): ?>
                                            <span class="badge bg-warning text-dark ms-2">Low Stock</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small>Stock: <?php echo $item['stock']; ?> | ₱<?php echo number_format($item['price'], 2); ?></small>
                                </div>
                                <div class="top-item-value"><strong>₱<?php echo number_format($item['total_value'], 2); ?></strong></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script>
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const timeElem = document.getElementById('current-time');
    if (timeElem) {
        timeElem.textContent = timeString;
    }
}
setInterval(updateTime, 1000);
updateTime();

window.categoryData = <?php echo json_encode($categories_data); ?>;
const ctx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: window.categoryData.map(item => item.category),
        datasets: [{
            data: window.categoryData.map(item => item.count),
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        },
        cutout: '60%'
    }
});
function refreshChart() {
    location.reload();
}
</script>
