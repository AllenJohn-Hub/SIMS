<?php
require_once('config.php');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sims";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Calculate total items in system
$sql_items = "SELECT COUNT(*) as total FROM inventory";
$result_items = $conn->query($sql_items);
$row_items = $result_items->fetch_assoc();
$total_items = $row_items['total'];

// Calculate total QR codes generated
$sql_qr = "SELECT COUNT(*) as total FROM inventory WHERE qr_code IS NOT NULL";
$result_qr = $conn->query($sql_qr);
$row_qr = $result_qr->fetch_assoc();
$total_qr_codes = $row_qr['total'];

// Get last generated QR code date
$sql_last = "SELECT date_added FROM inventory WHERE qr_code IS NOT NULL ORDER BY date_added DESC LIMIT 1";
$result_last = $conn->query($sql_last);
$last_generated = "Never";
if ($result_last->num_rows > 0) {
    $row_last = $result_last->fetch_assoc();
    $last_generated = date("Y-m-d H:i:s", strtotime($row_last['date_added']));
}
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm main-qr-card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-qr-code me-2"></i>QR Code Generation</h5>
                    <span class="badge bg-light text-primary">Inventory System</span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <div class="alert alert-primary bg-light border-primary">
                                <div class="d-flex">
                                    <i class="bi bi-info-circle-fill me-3 fs-4 text-primary"></i>
                                    <div>
                                        <h6 class="alert-heading fw-bold">How to generate QR codes</h6>
                                        <p class="mb-0">Click the button to generate printable QR codes for all inventory items. Each item will have QR codes generated based on its current stock quantity.</p>
                                        <hr class="my-2">
                                        <small class="text-muted">Tip: Use your browser's print function (Ctrl+P) to print or save as PDF.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end text-center mt-3 mt-md-0">
                            <a href="qrprint.php" target="_blank" class="btn btn-primary btn-lg px-4 py-3">
                                <i class="bi bi-qr-code-scan me-2"></i>Generate & Print QR Codes
                            </a>
                        </div>
                    </div>
                    
                    <!-- Additional helpful information -->
                    <div class="qr-stats-row mt-4">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Inventory Items</h6>
                                <small class="text-muted"><?php echo $total_items; ?> items in system</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-grow-1 justify-content-center">
                            <div class="stat-icon"><i class="bi bi-upc-scan"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">QR Codes</h6>
                                <small class="text-muted"><?php echo $total_qr_codes; ?> codes generated</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-grow-1 justify-content-end">
                            <div class="stat-icon"><i class="bi bi-printer"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Last Generated</h6>
                                <small class="text-muted"><?php echo $last_generated; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 0.5rem;
    overflow: hidden;
}

.card-header {
    padding: 1.25rem 1.5rem;
    width: 100%;
}

.card-body {
    padding: 2rem;
    
}

/* Make the main QR card fill the main content area */
.main-qr-card {
    width: 128%;
    max-width: 100%;
    height: 200%;
    margin: 40px 0 0 0;
    box-shadow: 0 2px 32px rgba(13, 110, 253, 0.13);
    border-radius: 1.2rem;
    padding: 2.5rem 3rem 2.5rem 3rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    height: 100%;
}

.btn-primary {
    background-color: #0d6efd;
    border: none;
    box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary:hover {
    background-color: #0b5ed7;
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(13, 110, 253, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.alert {
    border-radius: 0.5rem;
    padding: 1.25rem;
    
}

.bg-light {
    background-color: #f8f9fa !important;
}

.shadow-sm {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}

/* Animation for the button */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.btn-primary:hover {
    animation: pulse 1.5s infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
}

.qr-stats-row {
    width: 100%;
    background: #f8f9fa;
    border-radius: 1rem;
    margin-top: 0;
    padding: 1.5rem 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    box-sizing: border-box;
}
.qr-stats-row .stat-icon {
    background: #0d6efd;
    color: #fff;
    border-radius: 8px;
    padding: 12px;
    margin-right: 18px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.qr-stats-row h6 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 2px;
}
.qr-stats-row small {
    color: #6c757d;
}
@media (max-width: 900px) {
    .qr-stats-row {
        flex-direction: column;
        gap: 18px;
        padding: 1.2rem 0.5rem;
    }
    .qr-stats-row .stat-icon {
        margin-right: 12px;
        font-size: 1.5rem;
    }
}
</style>