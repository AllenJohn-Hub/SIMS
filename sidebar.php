<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTW Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/inventory.css">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard-chart.js"></script>
    <style>
        .left-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1030;
        }
        .right-content {
            margin-left: 250px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100 align-items-stretch">
            <!-- Left Sidebar -->
            <div class="col-md-3 left-sidebar min-vh-100">
                <div class="p-3">
                    <div class="sidebar-title">RTW TABS CONTAINER</div>
                    <h1 class="logo-text text-white mb-4">RTW</h1>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-content-url="dashboard.php"><i class="bi bi-grid-1x2 me-2"></i> <span>Dashboard</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-content-url="inventory.php"><i class="bi bi-box-seam me-2"></i> <span>Inventory</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-content-url="sales-report-content.php"><i class="bi bi-graph-up me-2"></i> <span>Sales Report</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-content-url="trends-content.php"><i class="bi bi-bar-chart-line me-2"></i> <span>Trends</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-content-url="qr-print-content.php"><i class="bi bi-printer me-2"></i> <span>QR Print</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-content-url="scanner.php"><i class="bi bi-qr-code-scan me-2"></i> <span>Scan</span></a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link" href="#" data-content-url="users-content.php"><i class="bi bi-person me-2"></i> <span>Users</span></a>
                        </li>
                         <li class="nav-item mt-auto">
                            <a class="nav-link" href="#" data-content-url="logout.php"><i class="bi bi-box-arrow-left me-2"></i> <span>Logout</span></a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Content Area -->
            <div class="col-md-9 right-content">
                <!-- Content will be loaded here by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.left-sidebar .nav-link');
            const rightContentArea = document.querySelector('.right-content');

            // Function to load content
            function loadContent(url) {
                if (url) {
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.text(); // Expecting HTML content, not JSON
                        })
                        .then(html => {
                            rightContentArea.innerHTML = html;
                            // Initialize dashboard chart if dashboard.php is loaded
                            if (url === 'dashboard.php' && typeof window.initDashboardChart === 'function') {
                                setTimeout(() => window.initDashboardChart(), 0);
                            }
                            // Load inventory filter script if inventory.php is loaded
                            if (url === 'inventory.php') {
                                const script = document.createElement('script');
                                script.src = 'js/inventory-filter.js';
                                script.onload = function() {
                                    if (window.initInventoryFilter) window.initInventoryFilter();
                                };
                                document.body.appendChild(script);
                            }
                            // After loading content, initialize modal and set up form submission
                            const addItemModalElement = document.getElementById('addItemModal');
                            let addItemModal = null;
                            if (addItemModalElement) {
                                addItemModal = new bootstrap.Modal(addItemModalElement);
                            }

                            const addItemForm = document.getElementById('addItemForm');
                            if (addItemForm) {
                                addItemForm.addEventListener('submit', function(event) {
                                    event.preventDefault(); // Prevent default form submission
                                    event.stopPropagation(); // Prevent event bubbling

                                    const form = event.target;
                                    const formData = new FormData(form);

                                    // Use the form's action attribute as the URL
                                    const formActionUrl = form.getAttribute('action');
                                    if (!formActionUrl) {
                                        console.error('Form action URL is missing');
                                        // Optionally show an error message to the user
                                        return;
                                    }

                                    // Resolve the form action URL to an absolute URL
                                    const resolvedFormActionUrl = new URL(formActionUrl, window.location.href).href;

                                    // Disable submit button and show loading state if available
                                    const submitButton = form.querySelector('button[type="submit"]');
                                    let originalButtonText = '';
                                    if (submitButton) {
                                        originalButtonText = submitButton.innerHTML;
                                        submitButton.disabled = true;
                                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
                                    }

                                    console.log('Fetching URL:', resolvedFormActionUrl, 'with method:', form.method);

                                    fetch(resolvedFormActionUrl, {
                                        method: form.method,
                                        body: formData
                                    })
                                    .then(response => {
                                        console.log('Received response with status:', response.status);
                                        if (!response.ok) {
                                            // Attempt to read response even if not OK for error details
                                            return response.text().then(text => { throw new Error(`HTTP error! status: ${response.status}, Response: ${text}`); });
                                        }
                                        return response.text();
                                    })
                                    .then(text => {
                                        console.log('Raw response text:', text); // Debug log
                                        let data;
                                        try {
                                            data = JSON.parse(text);
                                        } catch (e) {
                                            console.error('JSON parsing error:', e);
                                            console.error('Problematic response text:', text); // Log the bad text again
                                            throw new Error('Failed to parse server response as JSON.');
                                        }

                                        // Check for server-side status in the JSON response
                                        if (data.status === 'success') {
                                            console.log('Server response status: success', data);
                                            // Close the modal
                                            const modalElement = rightContentArea.querySelector('#addItemModal');
                                            const modal = bootstrap.Modal.getInstance(modalElement);
                                            if (modal) {
                                                modal.hide();
                                            }
                                            // Clear the form
                                            form.reset();
                                            // Reload just the inventory module (right panel)
                                            loadContent('inventory.php');
                                            // Attach sales report filter handler if present
                                            if (typeof attachSalesReportFilterAJAX === 'function') {
                                                attachSalesReportFilterAJAX();
                                            }
                                            return;
                                        } else {
                                            // Handle server-side error status
                                            console.error('Server response status: error', data);
                                            throw new Error(data.message || 'An unexpected error occurred on the server.');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Fetch error:', error);
                                        // Show a generic error for network or fetch issues, or re-thrown server errors
                                        const errorDiv = document.createElement('div');
                                        errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                                        errorDiv.innerHTML = `
                                            Error: ${error.message}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        `;
                                        // Prepend to the main content area within the loaded inventory.php content
                                        const inventoryContainer = rightContentArea.querySelector('.inventory-container');
                                        if (inventoryContainer) {
                                            inventoryContainer.prepend(errorDiv);
                                            setTimeout(() => errorDiv.remove(), 5000);
                                        } else {
                                            // Fallback if inventory container not found
                                            rightContentArea.prepend(errorDiv);
                                             setTimeout(() => errorDiv.remove(), 5000);
                                        }
                                    })
                                    .finally(() => {
                                        // Re-enable the submit button and restore text
                                        if (submitButton) {
                                            submitButton.disabled = false;
                                            submitButton.innerHTML = originalButtonText || 'Add Item'; // Restore original text or default
                                        }
                                    });
                                });
                            }

                            // Existing script execution logic (will be replaced)
                            rightContentArea.querySelectorAll('script').forEach(originalScript => {
                                // Only execute inline scripts (those without a src attribute)
                                if (!originalScript.src) {
                                    const newScript = document.createElement('script');

                                    // Copy attributes (though for inline scripts there might not be many useful ones besides type)
                                    Array.from(originalScript.attributes).forEach(attr => {
                                        newScript.setAttribute(attr.name, attr.value);
                                    });

                                    // Copy inline content
                                    newScript.appendChild(document.createTextNode(originalScript.innerHTML));

                                    // Append the new script to the body to execute it
                                    // Appending to body is often simpler for execution in this dynamic context.
                                    document.body.appendChild(newScript);
                                }
                            });

                        })
                        .catch(error => {
                            console.error('Error loading content:', error);
                            rightContentArea.innerHTML = '<p>Error loading content.</p>';
                        });
                } else {
                    rightContentArea.innerHTML = ''; // Clear content if no URL
                }
            }

            // Add click event listeners to sidebar links
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default link behavior

                    // Remove active class from all links
                    sidebarLinks.forEach(navLink => navLink.classList.remove('active'));

                    // Add active class to the clicked link
                    this.classList.add('active');

                    // Load content based on data-content-url attribute
                    const contentUrl = this.getAttribute('data-content-url');
                    loadContent(contentUrl);
                });
            });

            // Load default content (e.g., Dashboard) on page load
            const initialLink = document.querySelector('.left-sidebar .nav-link.active');
            if (initialLink) {
                loadContent(initialLink.getAttribute('data-content-url'));
            } else {
                 // If no active link, load content from the first link or a default
                 const firstLink = document.querySelector('.left-sidebar .nav-link');
                 if(firstLink) {
                     firstLink.classList.add('active');
                     loadContent(firstLink.getAttribute('data-content-url'));
                 }
            }

            // Add a global event listener for the custom event 'reloadInventoryModule'
            window.addEventListener('reloadInventoryModule', function() {
                loadContent('inventory.php');
            });
        });
    </script>
    <script>
    // Persistent AJAX filter for sales report
    function attachSalesReportFilterAJAX() {
        const form = document.querySelector('.right-content form[method="get"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams(formData).toString();
                fetch('sales-report-content.php?' + params)
                    .then(res => res.text())
                    .then(html => {
                        let wrapper = form.closest('.right-content') || document.body;
                        wrapper.innerHTML = html;
                        // Re-attach the handler after content reload
                        attachSalesReportFilterAJAX();
                    });
            }, { once: true }); // Only once, will re-attach after reload
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        attachSalesReportFilterAJAX();
        document.addEventListener('ajaxContentLoaded', attachSalesReportFilterAJAX);
    });
    </script>
    <script>
    // Robust event delegation for sales report filter
    // This will not affect other modules

    document.addEventListener('DOMContentLoaded', function() {
        const rightContent = document.querySelector('.right-content');
        if (rightContent) {
            rightContent.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.matches('form[method="get"]')) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData).toString();
                    fetch('sales-report-content.php?' + params)
                        .then(res => res.text())
                        .then(html => {
                            rightContent.innerHTML = html;
                        });
                }
            });
        }
    });
    </script>
</body>
</html> 