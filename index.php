<?php
// Start a session (if not already started)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$error_message = ""; // Initialize an empty error message

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password from the form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // --- Database Connection (REPLACE WITH YOUR ACTUAL CREDENTIALS) ---
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "sims";

    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Log the error instead of displaying it directly in a production environment
        error_log("Database Connection failed: " . $conn->connect_error);
        $error_message = "Database connection error. Please try again later.";
    } else {
        // Prepare a SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, is_active FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // User found, verify password
            $account = $result->fetch_assoc();

            // --- Password Verification (Plain text comparison) ---
            if ($password === $account['password']) {
                // Password is correct, check if account is active
                if ($account['is_active']) {
                    // Login successful
                    $_SESSION['loggedin'] = TRUE;
                    $_SESSION['id'] = $account['id'];
                    $_SESSION['username'] = $account['username'];

                    // Redirect to sidebar.php
                    header("Location: sidebar.php");
                    exit(); // Stop script execution after redirect
                } else {
                    $error_message = "Your account is inactive. Please contact the administrator.";
                }
            } else {
                // Invalid password
                $error_message = "Invalid username or password.";
            }
        } else {
            // No user found with that username
            $error_message = "Invalid username or password.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTW Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <div class="col-md-6 d-flex flex-column align-items-center justify-content-center left-panel">
                <div class="text-center mb-4 login-title">RTW LOGIN</div>
                <div class="logo-section text-center">
                    <div class="logo-text">RTW</div>
                    <img src="images/image.png" alt="RTW Logo" class="logo-image img-fluid mb-3">
                    <div class="logo-subtitle">SALES AND INVENTORY</div>
                </div>
            </div>
            <div class="col-md-6 d-flex flex-column align-items-center justify-content-center right-panel">
                <div class="card w-75 p-4 login-form">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">RTW Login</h2>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form action="index.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">USERNAME</label>
                                <input type="text" class="form-control" id="username" name="username">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">PASSWORD</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <span class="input-group-text password-toggle"><!-- Placeholder for eye icon --></span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                            <div class="text-end">
                                <a href="#" class="forgot-password">Forget password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
