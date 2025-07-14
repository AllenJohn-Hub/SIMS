<?php
// --- OUTPUT BUFFERING TO PREVENT ACCIDENTAL OUTPUT ---
if (!headers_sent()) ob_start();
// Force error logging and prevent display in output - MUST BE AT THE VERY TOP
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
// Set error log file if not already configured
// ini_set('error_log', '/path/to/your/php_error.log'); // Uncomment and set a path if needed

error_log("Debug: inventory.php script started."); // Debug log 1

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Database Connection (REPLACE WITH YOUR ACTUAL CREDENTIALS) ---
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "sims";

// Create connection (used for both AJAX and non-AJAX paths)
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Check connection and handle potential database errors at the start
if ($conn->connect_error) {
    error_log("Database Connection failed: " . $conn->connect_error);
    // Respond appropriately based on request type
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection error during AJAX form submission.'
        ]);
        exit;
    } else {
        die("Database connection error. Please try again later.");
    }
}

error_log("Debug: Database connection successful."); // Debug log after connection

// Check if qr_code column exists, if not add it (ensure this runs AFTER initial connection check)
// This will run on normal page load if the column is missing
if (isset($conn) && $conn && $conn->ping()) { // Only check if connection is valid
    $checkColumn = $conn->query("SHOW COLUMNS FROM inventory LIKE 'qr_code'");
    if ($checkColumn) { // Check if query was successful
        if ($checkColumn->num_rows == 0) {
            error_log("Debug: QR code column does not exist. Attempting to add it.");
            $addColumn = $conn->query("ALTER TABLE inventory ADD COLUMN qr_code MEDIUMBLOB AFTER price");
            if (!$addColumn) {
                error_log("Failed to add qr_code column: " . $conn->error);
            } else {
                error_log("Debug: Successfully added qr_code column.");
            }
        }
    } else {
        error_log("Database Query failed during column check: " . $conn->error);
    }
}

// Regenerate QR codes if needed (run this after column check, only on non-AJAX page load)
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    if (isset($conn) && $conn && $conn->ping()) { // Only run if connection is valid
        $checkQR = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE qr_code IS NULL OR LENGTH(qr_code) = 0");
        if ($checkQR) { // Check if query was successful
            $row = $checkQR->fetch_assoc();
            if ($row['count'] > 0) {
                error_log("Debug: Found items without QR codes on page load. Attempting regeneration.");
                // Get all items that need QR codes
                $items = $conn->query("SELECT i.id, i.name, i.category_id, i.stock, i.price, c.category FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id WHERE i.qr_code IS NULL OR LENGTH(i.qr_code) = 0");
                if ($items) { // Check if query was successful
                    while ($item = $items->fetch_assoc()) {
                        regenerateQRCode($conn, $item);
                    }
                    $items->free(); // Free result set
                } else {
                     error_log("Database Query failed during QR code fetch for regeneration: " . $conn->error);
                }
            }
             $checkQR->free(); // Free result set
        } else {
            error_log("Database Query failed during QR code count check for regeneration: " . $conn->error);
        }
    }
}

// Add this after the database connection check and before the AJAX handling block
function regenerateQRCode($conn, $item) {
    // Generate QR code data
    $qrData = json_encode([
        'id' => $item['id'],
        'name' => $item['name'],
        'category' => $item['category'],
        'stock' => $item['stock'],
        'price' => $item['price'],
        'timestamp' => time()
    ]);

    // Generate QR code using API
    $qrSize = 300;
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$qrSize}x{$qrSize}&data=" . urlencode($qrData);
    error_log("QR URL: $qrUrl");
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $qrUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute cURL request
    $qrImage = curl_exec($ch);
    error_log("QR API response (first 100 chars): " . substr($qrImage, 0, 100));
    
    // Debug: Log first bytes of response
    $firstBytes = substr($qrImage, 0, 8);
    $hex = strtoupper(bin2hex($firstBytes));
    error_log("QR code first bytes for " . $item['name'] . ": " . $hex);
    // PNG signature: 89504E470D0A1A0A
    if ($hex !== '89504E470D0A1A0A') {
        error_log("QR code API did not return a valid PNG for " . $item['name'] . ". Response: " . substr($qrImage, 0, 100));
        throw new Exception("QR code API did not return a valid PNG image.");
    }
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        throw new Exception("Failed to generate QR code: " . curl_error($ch));
    }
    
    // Check HTTP response code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        curl_close($ch);
        throw new Exception("Failed to generate QR code: HTTP error {$httpCode}.");
    }
    
    curl_close($ch);
    
    // Verify the image data (basic check)
    if (!$qrImage || strlen($qrImage) < 100) {
        throw new Exception("Invalid or empty QR code image data received");
    }
    
    // Update the database
    $stmt = $conn->prepare("UPDATE inventory SET qr_code = ? WHERE id = ?");
    if (!$stmt) {
        error_log("Failed to prepare update statement: " . $conn->error);
        return false;
    }
    
    // Use 's' for BLOB binding
    if (!$stmt->bind_param("si", $qrImage, $item['id'])) {
        error_log("Failed to bind parameters for update: " . $stmt->error);
        return false;
    }

    if (!$stmt->execute()) {
        error_log("Failed to execute update: " . $stmt->error);
        return false;
    }
    
    $stmt->close();
    error_log("Successfully regenerated QR code for item {$item['id']}");
    return true;
}

// Fetch categories from the category table
$categories = [];
$cat_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if (!$cat_conn->connect_error) {
    $cat_result = $cat_conn->query("SELECT category_id, category FROM category ORDER BY category ASC");
    if ($cat_result && $cat_result->num_rows > 0) {
        while ($cat_row = $cat_result->fetch_assoc()) {
            $categories[] = $cat_row;
        }
    }
    $cat_conn->close();
}

// --- AJAX Category Management (MUST BE BEFORE ANY HTML OR OUTPUT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'get_categories') {
        header('Content-Type: application/json');
        $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
        $cat_list = [];
        while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
        echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        exit;
    }
    if ($_POST['action'] === 'add_category') {
        header('Content-Type: application/json');
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Category name required.']);
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO category (category) VALUES (?)');
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
            $cat_list = [];
            while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
            echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add category.']);
        }
        exit;
    }
    if ($_POST['action'] === 'edit_category') {
        header('Content-Type: application/json');
        $id = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        if ($id <= 0 || $name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE category SET category=? WHERE category_id=?');
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) {
            $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
            $cat_list = [];
            while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
            echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to edit category.']);
        }
        exit;
    }
    if ($_POST['action'] === 'delete_category') {
        header('Content-Type: application/json');
        $id = intval($_POST['category_id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid category ID.']);
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM category WHERE category_id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $cats = $conn->query('SELECT category_id, category FROM category ORDER BY category ASC');
            $cat_list = [];
            while ($row = $cats->fetch_assoc()) $cat_list[] = $row;
            echo json_encode(['status' => 'success', 'categories' => $cat_list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete category.']);
        }
        exit;
    }
}

// --- AJAX Request Handling ---
// Process POST requests specifically for the add_item form submission via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_item') {
    // Clear any previous output
    if (ob_get_level()) ob_end_clean();
    
    // Set JSON header
    header('Content-Type: application/json');

    try {
        // Get form data
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $stock = $_POST['stock'] ?? 0;
        $price = $_POST['price'] ?? 0.00;
        // Fetch category name for QR code
        $category_name = '';
        if (!empty($category_id)) {
            $cat_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
            if (!$cat_conn->connect_error) {
                $cat_result = $cat_conn->query("SELECT category FROM category WHERE category_id = " . intval($category_id) . " LIMIT 1");
                if ($cat_result && $cat_result->num_rows > 0) {
                    $cat_row = $cat_result->fetch_assoc();
                    $category_name = $cat_row['category'];
                }
                $cat_conn->close();
            }
        }
        error_log('DEBUG: category_id=' . $category_id . ', category_name=' . $category_name);
        // Validate input (basic validation)
        if (empty($name)) {
            throw new Exception("Name is required.");
        }
        if (empty($category_id)) {
            throw new Exception("Category is required.");
        }
        if (!is_numeric($stock) || $stock < 0) {
            throw new Exception("Stock must be a non-negative number.");
        }
        if (!is_numeric($price) || $price < 0) {
            throw new Exception("Price must be a non-negative number.");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Generate QR code data
            $qrData = json_encode([
                'id' => $item['id'],
                'name' => $name,
                'category' => $category_name,
                'stock' => $stock,
                'price' => $price,
                'timestamp' => time()
            ]);

            // Generate QR code using API
            $qrSize = 300;
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$qrSize}x{$qrSize}&data=" . urlencode($qrData);
            
            // Initialize cURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $qrUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Execute cURL request
            $qrImage = curl_exec($ch);
            
            // Debug: Log first bytes of response
            $firstBytes = substr($qrImage, 0, 8);
            $hex = strtoupper(bin2hex($firstBytes));
            error_log("QR code first bytes for $name: $hex");
            // PNG signature: 89504E470D0A1A0A
            if ($hex !== '89504E470D0A1A0A') {
                error_log("QR code API did not return a valid PNG for $name. Response: " . substr($qrImage, 0, 100));
                throw new Exception("QR code API did not return a valid PNG image.");
            }
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                throw new Exception("Failed to generate QR code: " . curl_error($ch));
            }
            
            // Check HTTP response code
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                curl_close($ch);
                throw new Exception("Failed to generate QR code: HTTP error {$httpCode}.");
            }
            
            curl_close($ch);
            
            // Verify the image data (basic check)
            if (!$qrImage || strlen($qrImage) < 100) {
                throw new Exception("Invalid or empty QR code image data received");
            }

            // Store QR code directly in database
            // Assuming 'qr_code' column is of type MEDIUMBLOB
            $stmt = $conn->prepare("INSERT INTO inventory (name, category_id, stock, price, qr_code) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare SQL statement: " . $conn->error);
            }

            $null = NULL;
            $stmt->bind_param("siids", $name, $category_id, $stock, $price, $null); // Use 'b' for BLOB
            $stmt->send_long_data(4, $qrImage); // 4 is the zero-based index of the qr_code parameter

            // Execute the statement
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute SQL statement: " . $stmt->error);
            }

            // Get the ID of the newly inserted item
            $newItemId = $conn->insert_id;

            // Fetch the newly added item's data (without QR code BLOB for the response)
            $fetchNewItemSql = "SELECT id, name, category_id, stock, price FROM inventory WHERE id = ?";
            $fetchNewItemStmt = $conn->prepare($fetchNewItemSql);
            
            if (!$fetchNewItemStmt) {
                throw new Exception("Failed to prepare fetch statement: " . $conn->error);
            }

            if (!$fetchNewItemStmt->bind_param("i", $newItemId)) {
                throw new Exception("Failed to bind fetch parameters: " . $fetchNewItemStmt->error);
            }

            if (!$fetchNewItemStmt->execute()) {
                throw new Exception("Failed to execute fetch: " . $fetchNewItemStmt->error);
            }

            $newItemResult = $fetchNewItemStmt->get_result();
            $newItemData = $newItemResult->fetch_assoc();
            
            if (!$newItemData) {
                 // Log this unexpected case
                 error_log("Warning: Failed to fetch inserted item data for ID: " . $newItemId);
                 // Still return success if insert worked, but item data is missing
                 $response = [
                     'status' => 'success',
                     'message' => 'Item added successfully, but data fetch failed.'
                 ];
            } else {
                 $response = [
                     'status' => 'success',
                     'message' => 'Item added successfully!',
                     'item' => $newItemData
                 ];
            }

            // Commit the transaction
            $conn->commit();

            // Close statements
            if (isset($stmt) && $stmt) $stmt->close();
            if (isset($fetchNewItemStmt) && $fetchNewItemStmt) $fetchNewItemStmt->close();
            
            // Return success response
            echo json_encode($response);

        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
             // Close statements if they were successfully prepared
            if (isset($stmt) && $stmt) $stmt->close();
            if (isset($fetchNewItemStmt) && $fetchNewItemStmt) $fetchNewItemStmt->close();
            // Throw the exception to be caught by the outer handler
            throw $e;
        }

    } catch (Exception $e) {
        // Log the error server-side
        error_log("AJAX Form Submission Error: " . $e->getMessage());
        // Return error response to the client
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage() // Send the specific error message back
        ]);
    } finally {
         // Ensure the main connection is closed ONLY if it was successfully opened and is still active
         if (isset($conn) && $conn && $conn->ping()) {
             $conn->close();
         }
    }
    
    // IMPORTANT: Unconditionally exit after handling the AJAX form POST request
    exit; 
}

// Handle edit item AJAX request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_item') {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $stock = $_POST['stock'] ?? 0;
        $price = $_POST['price'] ?? 0.00;
        // Fetch category name for QR code
        $category_name = '';
        if (!empty($category_id)) {
            $cat_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
            if (!$cat_conn->connect_error) {
                $cat_result = $cat_conn->query("SELECT category FROM category WHERE category_id = " . intval($category_id) . " LIMIT 1");
                if ($cat_result && $cat_result->num_rows > 0) {
                    $cat_row = $cat_result->fetch_assoc();
                    $category_name = $cat_row['category'];
                }
                $cat_conn->close();
            }
        }
        if (empty($id) || empty($name) || empty($category_id) || !is_numeric($stock) || !is_numeric($price)) {
            throw new Exception("Invalid input data.");
        }
        // Reconnect if needed
        if (!isset($conn) || !$conn || !$conn->ping()) {
            $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
            if ($conn->connect_error) {
                throw new Exception("Database connection error.");
            }
        }
        $stmt = $conn->prepare("UPDATE inventory SET name=?, category_id=?, stock=?, price=?, qr_code=?, date_updated=NOW() WHERE id=?");
        if (!$stmt) throw new Exception("Failed to prepare update statement: " . $conn->error);
        $null = NULL;
        $stmt->bind_param("siidsi", $name, $category_id, $stock, $price, $null, $id); // Use 'b' for BLOB
        $stmt->send_long_data(4, $qrImage);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute update: " . $stmt->error);
        }
        $stmt->close();
        // Fetch updated item for response
        $fetchSql = "SELECT id, name, category_id, stock, price FROM inventory WHERE id=?";
        $fetchStmt = $conn->prepare($fetchSql);
        $fetchStmt->bind_param("i", $id);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();
        $updatedItem = $result->fetch_assoc();
        $fetchStmt->close();
        echo json_encode([
            'status' => 'success',
            'message' => 'Item updated successfully!',
            'item' => $updatedItem
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    } finally {
        if (isset($conn) && $conn && $conn->ping()) {
            $conn->close();
        }
    }
    exit;
}

// --- Code below this line runs for GET requests or non-AJAX POSTs that are NOT the add_item form submission ---

// Check if user is NOT logged in before attempting to render the page content that requires login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
     // If not logged in, the redirect at the top should have already happened, but as a safeguard:
     header("Location: index.php");
     exit;
}

// Re-establish database connection for GET request/page rendering (if not already open and valid)
// This is likely not needed if the connection was kept open, but adding for safety.
if (!isset($conn) || !$conn || !$conn->ping()) {
   $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
   // Handle connection error for page load if it occurs here
   if ($conn->connect_error) {
       error_log("Database Connection failed during page load rendering: " . $conn->connect_error);
       $inventory_items = []; 
       $error_message = "Could not load inventory data due to a database error. Please try again later.";
       // Don't die, continue to render the page with the error message
   }
}

// Fetch inventory data for rendering the HTML page ONLY if connection is good
$inventory_items = []; // Initialize array
$error_message = ''; // Initialize error message

if (isset($conn) && $conn && $conn->ping()) {
    $sql = "SELECT i.id, i.name, i.category_id, c.category, i.stock, i.price, i.qr_code FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id";
    $result = $conn->query($sql);

    if ($result) { // Check if query was successful
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $inventory_items[] = $row;
            }
        }
        $result->free(); // Free result set
    } else {
        error_log("Database Query failed during page load: " . $conn->error);
        $error_message = "Could not load inventory data due to a database query error. Please try again later.";
    }
    // Keep the connection open if needed by other parts of sidebar.php, otherwise close.
    // Given this file is loaded via AJAX into sidebar.php, it might be safer to close it.
     if (isset($conn) && $conn && $conn->ping()) {
         $conn->close();
     }
}

// The rest of the file is for rendering the HTML page
?>

<div class="container-fluid p-4">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <h2>INVENTORY</h2>
        </div>
    </div>

    <div class="inventory-container mt-3">
        <!-- Main Inventory Content -->
        <div class="inventory-main-content">
            <!-- Table Container with Scroll -->
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>QR Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory_items)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No inventory items found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventory_items as $item): ?>
                                <tr data-category-id="<?php echo $item['category_id']; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td data-category-id="<?php echo $item['category_id']; ?>"><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo htmlspecialchars($item['stock']); ?></td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <?php 
                                        // Get QR code from database
                                        // Check if qr_code is not empty and is a string/blob
                                        if (!empty($item['qr_code']) && is_string($item['qr_code'])): 
                                            $qrImageData = base64_encode($item['qr_code']);
                                            if (strlen($item['qr_code']) < 100) {
                                                error_log('Display: QR code BLOB too small for item ' . $item['id']);
                                            }
                                        ?>
                                            <img src="data:image/png;base64,<?php echo $qrImageData; ?>" 
                                                 alt="QR Code for <?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="qr-code-thumbnail"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#qrModal<?php echo $item['id']; ?>"
                                                 onerror="console.error('Failed to load QR code for item <?php echo $item['id']; ?>')">
                                            
                                            <!-- QR Code Modal -->
                                            <div class="modal fade" id="qrModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">QR Code - <?php echo htmlspecialchars($item['name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-center">
                                                            <img src="data:image/png;base64,<?php echo $qrImageData; ?>" 
                                                                 alt="QR Code" 
                                                                 class="img-fluid"
                                                                 onerror="console.error('Failed to load QR code in modal for item <?php echo $item['id']; ?>')">
                                                            <div class="mt-2">
                                                                <small class="text-muted">Scan to view item details</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <a href="data:image/png;base64,<?php echo $qrImageData; ?>" 
                                                               download="qr_<?php echo $item['id']; ?>.png" 
                                                               class="btn btn-primary">Download</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php error_log('Display: No QR code for item ' . $item['id']); ?>
                                            <span class="text-muted">No QR code</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary">Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Filter/Action Panel -->
        <div class="inventory-right-panel d-flex flex-column" style="height:100%">
             <div class="search-bar mb-3">
                <input type="text" class="form-control" placeholder="Search...">
            </div>
            <h5>Categories</h5>
            <ul class="list-unstyled category-list">
                <li class="category-item active" data-category-id="all">All</li>
                <?php foreach ($categories as $cat): ?>
                    <li class="category-item" data-category-id="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category']); ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="category-demo.php" target="_blank" class="btn btn-outline-secondary btn-sm w-100 mb-3" onclick="window.open('category-demo.php', 'CategoryDemo', 'width=600,height=600,scrollbars=yes'); return false;">Manage Categories</a>
            <div class="mt-auto">
                <h5 class="mb-3">Actions</h5>
                <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#addItemModal">Add New Item</button>
            </div>
        </div>
    </div>

    <style>
        .inventory-container {
            display: flex;
            gap: 20px;
            height: calc(100vh - 200px); /* Adjust based on your header height */
        }

        .inventory-main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .table-container {
            flex: 1;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            table-layout: fixed; /* Add this line for fixed layout */
        }

        .inventory-table thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 1;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
        }

        .inventory-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .inventory-right-panel {
            width: 250px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .list-item {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .qr-code-thumbnail {
            width: 50px;
            height: 50px;
            cursor: pointer;
            transition: transform 0.2s;
            object-fit: cover;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 2px;
            background-color: white;
        }

        .qr-code-thumbnail:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);;
        }

        .modal-body img {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            background-color: white;
        }

        .text-muted {
            font-size: 0.875rem;
        }

        .category-list {
            padding-left: 0;
            margin-bottom: 1rem;
        }
        .category-item {
            display: inline-block;
            margin: 4px 6px 4px 0;
            padding: 6px 16px;
            background: #f1f1f1;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            font-size: 0.97rem;
            transition: background 0.2s, color 0.2s, border 0.2s;
        }
        .category-item:hover {
            background: #e0e0e0;
            color: #4a3b3a;
            border-color: #bbaaaa;
        }
        .category-item.active {
            background: #4a3b3a;
            color: #fff;
            border-color: #4a3b3a;
        }
        .add-item-btn-wrapper {
            margin-top: auto;
        }
    </style>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add New Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addItemForm" action="inventory.php" method="post">
                        <input type="hidden" name="action" value="add_item">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editItemForm" action="inventory.php" method="post">
                        <input type="hidden" name="action" value="edit_item">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-category_id" class="form-label">Category</label>
                            <select class="form-select" id="edit-category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="edit-stock" name="stock" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="edit-price" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="js/category-manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('openCategoryDemoBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            window.open('category-demo.php', 'CategoryDemo', 'width=600,height=600,scrollbars=yes');
        });
    }
});
</script>
</body>
</html>