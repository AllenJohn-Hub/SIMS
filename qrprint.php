<?php
// Database connection (reuse logic from inventory.php)
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "sims";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Database connection error. Please try again later.");
}

// Filtering logic
$filter_name = isset($_GET['filter_name']) ? trim($_GET['filter_name']) : '';
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$where = "WHERE qr_code IS NOT NULL AND LENGTH(qr_code) > 0";
if ($filter_name !== '') {
    $safe_name = $conn->real_escape_string($filter_name);
    $where .= " AND name LIKE '%$safe_name%'";
}
if ($filter_date !== '') {
    $safe_date = $conn->real_escape_string($filter_date);
    $where .= " AND DATE(date_added) = '$safe_date'";
}
$sql = "SELECT name, stock, qr_code, price, category_id FROM inventory $where";
$result = $conn->query($sql);
$items = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
// Fetch category names for all category_ids
$category_names = [];
if (!empty($items)) {
    $cat_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
    if (!$cat_conn->connect_error) {
        $cat_ids = array_unique(array_column($items, 'category_id'));
        $cat_ids_str = implode(',', array_map('intval', $cat_ids));
        $cat_result = $cat_conn->query("SELECT category_id, category FROM category WHERE category_id IN ($cat_ids_str)");
        if ($cat_result && $cat_result->num_rows > 0) {
            while ($cat_row = $cat_result->fetch_assoc()) {
                $category_names[$cat_row['category_id']] = $cat_row['category'];
            }
        }
        $cat_conn->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print QR Codes</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; }
        .main-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 30px 0 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-left: 80px;
        }
        .print-header { text-align: center; margin: 20px 0 30px 0; width: 100%; }
        .filter-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 24px;
            gap: 10px;
        }
        .filter-input {
            padding: 8px 14px;
            font-size: 16px;
            border: 1px solid #bbb;
            border-radius: 5px;
            min-width: 220px;
        }
        .filter-btn {
            padding: 8px 20px;
            font-size: 16px;
            background: linear-gradient(90deg, #007bff 60%, #00c6ff 100%);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .filter-btn:hover { background: #0056b3; }
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 28px;
            justify-items: center;
            align-items: start;
            margin: 0 auto;
            max-width: 1200px;
        }
        .qr-item {
            background: #fff;
            border: 1.5px solid #e0e0e0;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 18px 12px 14px 12px;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 160px;
            min-height: 200px;
            page-break-inside: avoid;
            transition: box-shadow 0.2s, border 0.2s;
        }
        .qr-item:hover {
            box-shadow: 0 8px 24px rgba(0,123,255,0.12);
            border: 1.5px solid #007bff;
        }
        .qr-img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            border: 1px solid #eee;
            background: #fff;
            margin-bottom: 12px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .qr-label-input, .qr-qty-input {
            font-size: 15px;
            color: #222;
            font-weight: 600;
            text-align: center;
            margin-top: 4px;
            margin-bottom: 2px;
            border: 1px solid #bbb;
            border-radius: 4px;
            padding: 4px 8px;
            width: 90%;
            background: #f6faff;
            transition: border 0.2s;
        }
        .qr-label-input:focus, .qr-qty-input:focus {
            border: 1.5px solid #007bff;
            outline: none;
            background: #fff;
        }
        .qr-label-print, .qr-qty-print { display: none; }
        @media print {
            .print-header, .print-btn, .filter-form, .qr-label-input, .qr-qty-input { display: none !important; }
            .qr-label-print { display: block !important; }
            .qr-qty-print { display: none !important; }
            body { margin: 0; background: #fff; }
            .qr-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 24px 18px !important;
                max-width: 1000px !important;
                margin: 0 auto !important;
                justify-items: center !important;
                align-items: start !important;
                padding: 0 !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100vw !important;
                width: 100vw !important;
                display: block !important;
            }
            .qr-item {
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                border: 1.5px solid #e0e0e0;
                border-radius: 14px;
                min-width: 0;
                min-height: 0;
                padding: 18px 10px 12px 10px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                background: #fafbfc;
                margin-bottom: 0;
                page-break-inside: avoid;
            }
            .qr-img {
                width: 110px;
                height: 110px;
                object-fit: contain;
                border: 1px solid #eee;
                background: #fff;
                margin-bottom: 10px;
                border-radius: 6px;
                box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            }
            .left-sidebar, .sidebar, .sidebar-container { display: none !important; }
            #print-date {
                display: block !important;
                position: fixed;
                bottom: 20px;
                left: 0;
                width: 100vw;
                text-align: center;
                font-size: 16px;
                color: #333;
            }
        }
        .print-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 30px;
            font-size: 18px;
            background: linear-gradient(90deg, #007bff 60%, #00c6ff 100%);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="print-header">
            <h1>Inventory QR Codes</h1>
            <form class="filter-form" method="get" action="">
                <input class="filter-input" type="text" name="filter_name" placeholder="Filter by item name..." value="<?php echo htmlspecialchars($filter_name); ?>">
                <input class="filter-input" type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                <button class="filter-btn" type="submit">Filter</button>
                <?php if ($filter_name !== '' || $filter_date !== ''): ?>
                    <a href="qrprint.php" class="filter-btn" style="background:#6c757d; margin-left:5px; text-decoration:none;">Clear</a>
                <?php endif; ?>
            </form>
            <button class="print-btn" onclick="printQRCodes()">Print</button>
        </div>
        <div class="qr-grid" id="qrGrid">
            <?php if (empty($items)): ?>
                <div style="grid-column: 1 / -1; text-align:center; color:#888; font-size:18px;">No items found.</div>
            <?php else: ?>
                <?php foreach ($items as $idx => $item): ?>
                    <?php 
                        $qty = max(1, (int)$item['stock']);
                        $qrImageData = base64_encode($item['qr_code']);
                        $price = isset($item['price']) ? number_format($item['price'], 2) : '';
                        $category = isset($category_names[$item['category_id']]) ? $category_names[$item['category_id']] : '';
                        for ($i = 0; $i < $qty; $i++):
                    ?>
                    <div class="qr-item" data-idx="<?php echo $idx; ?>">
                        <img class="qr-img" src="data:image/png;base64,<?php echo $qrImageData; ?>" alt="QR Code">
                        <input class="qr-label-input" type="text" value="<?php echo htmlspecialchars($item['name']); ?>" onchange="updateLabel(<?php echo $idx; ?>, this.value)">
                        <input class="qr-qty-input" type="number" min="1" value="<?php echo $qty; ?>" onchange="updateQty(<?php echo $idx; ?>, this.value)">
                        <div class="qr-label-print"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="qr-qty-print"><?php echo $qty; ?></div>
                        <div class="qr-meta" style="margin-top:6px; font-size:15px; color:#444; text-align:center;">
                            <div style="font-weight:bold; font-size:16px; margin-bottom:2px;">₱<?php echo $price; ?></div>
                            <div><?php echo htmlspecialchars($category); ?></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Store initial data for live editing
    let qrItems = <?php echo json_encode(array_map(function($item) {
        return [
            'name' => $item['name'],
            'qty' => max(1, (int)$item['stock']),
            'qr' => base64_encode($item['qr_code'])
        ];
    }, $items)); ?>;

    function updateLabel(idx, value) {
        qrItems[idx].name = value;
        renderGrid();
    }
    function updateQty(idx, value) {
        qrItems[idx].qty = Math.max(1, parseInt(value) || 1);
        renderGrid();
    }
    function renderGrid() {
        const grid = document.getElementById('qrGrid');
        if (!qrItems.length) {
            grid.innerHTML = '<div style="grid-column: 1 / -1; text-align:center; color:#888; font-size:18px;">No items found.</div>';
            return;
        }
        let html = '';
        qrItems.forEach((item, idx) => {
            for (let i = 0; i < item.qty; i++) {
                html += `<div class="qr-item" data-idx="${idx}">
                    <img class="qr-img" src="data:image/png;base64,${item.qr}" alt="QR Code">
                    <input class="qr-label-input" type="text" value="${item.name}" onchange="updateLabel(${idx}, this.value)">
                    <input class="qr-qty-input" type="number" min="1" value="${item.qty}" onchange="updateQty(${idx}, this.value)">
                    <div class="qr-label-print">${item.name}</div>
                    <div class="qr-qty-print">${item.qty}</div>
                </div>`;
            }
        });
        grid.innerHTML = html;
    }
    // Only show editable fields on screen, not in print
    function printQRCodes() {
        document.body.classList.add('printing');
        window.print();
        setTimeout(() => document.body.classList.remove('printing'), 1000);
    }
    </script>
    <div id="print-date" style="display:none; text-align:center; margin-top:30px; font-size:16px;"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var printDate = new Date();
        var dateString = printDate.getFullYear() + '-' +
            String(printDate.getMonth() + 1).padStart(2, '0') + '-' +
            String(printDate.getDate()).padStart(2, '0') + ' ' +
            String(printDate.getHours()).padStart(2, '0') + ':' +
            String(printDate.getMinutes()).padStart(2, '0');
        document.getElementById('print-date').textContent = 'Date printed: ' + dateString;
    });
    </script>
</body>
</html>
