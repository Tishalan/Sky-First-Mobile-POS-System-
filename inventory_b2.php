<?php
session_start();

// Check if logged in for branch 2
if (!isset($_SESSION['admin_branch2'])) {
    header("Location: login-branch2.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set branch_id for branch 2
$branch_id = 2;
$_SESSION['branch_id'] = $branch_id;

// Generate unique product ID for branch 2 (prefix PB)
function generateProductId($conn, $branch_id) {
    $prefix = 'PB'; // Always use PB for branch 2
    // Query the maximum product_id for branch 2 only
    $sql = "SELECT MAX(CAST(SUBSTRING(product_id, 3) AS UNSIGNED)) as max_id 
            FROM products 
            WHERE product_id LIKE '$prefix%' AND branch_id = $branch_id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $max_id = $row['max_id'];
    
    if ($max_id !== null) {
        $numeric_part = (int)$max_id + 1;
    } else {
        $numeric_part = 1; // Start with 1 if no products exist
    }
    
    return $prefix . str_pad($numeric_part, 5, "0", STR_PAD_LEFT);
}

// Handle add or update product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_product'])) {
    $errors = [];
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $original_price = floatval($_POST['original_price']);
    $sale_price = floatval($_POST['sale_price']);
    $stock = intval($_POST['stock']);
    // $barcode = mysqli_real_escape_string($conn, $_POST['barcode']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $photo = '';

    // Form validations
    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($category)) $errors[] = "Category is required.";
    if ($original_price <= 0) $errors[] = "Original price must be greater than 0.";
    if ($sale_price <= 0) $errors[] = "Sale price must be greater than 0.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";
    // if (empty($barcode)) $errors[] = "Barcode is required.";

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo = $target_file;
        } else {
            $errors[] = "Failed to upload photo.";
        }
    }

    if (empty($errors)) {
        // Check if product exists (for update)
        $check_sql = "SELECT * FROM products WHERE product_id = '$product_id' AND branch_id = $branch_id";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            // Update existing product
            $update_sql = "UPDATE products SET 
                name = '$name', 
                category = '$category', 
                original_price = $original_price, 
                sale_price = $sale_price, 
                stock = $stock, 
                -- barcode = '$barcode', 
                color = '$color'";
            if ($photo) {
                $update_sql .= ", photo = '$photo'";
            }
            $update_sql .= " WHERE product_id = '$product_id' AND branch_id = $branch_id";
            $conn->query($update_sql);
        } else {
            // Insert new product
            $insert_sql = "INSERT INTO products (product_id, branch_id, name, category, original_price, sale_price, stock, color, photo) 
                VALUES ('$product_id', $branch_id, '$name', '$category', $original_price, $sale_price, $stock, '$color', '$photo')";
            $conn->query($insert_sql);
        }
        header("Location: inventory_b2.php");
        exit();
    } else {
        echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
    }
}

// Handle delete products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_product'])) {
    if (isset($_POST['selected_products']) && is_array($_POST['selected_products']) && count($_POST['selected_products']) == 1) {
        $ids = mysqli_real_escape_string($conn, $_POST['selected_products'][0]);
        $delete_sql = "DELETE FROM products WHERE product_id = '$ids' AND branch_id = $branch_id";
        $conn->query($delete_sql);
        header("Location: inventory_b2.php");
        exit();
    } else {
        echo "<script>alert('Please select exactly one product to delete.');</script>";
    }
}

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle AJAX search
if (isset($_GET['ajax_search'])) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $sql = "SELECT * FROM products WHERE branch_id = $branch_id";
    if ($search) {
        $sql .= " AND (product_id LIKE '%$search%' OR name LIKE '%$search%' OR barcode LIKE '%$search%' OR color LIKE '%$search%')";
    }
    $result = $conn->query($sql);

    // Output only the tbody content
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td><input type="radio" name="selected_products[]" value="' . htmlspecialchars($row['product_id']) . '"></td>';
        echo '<td>' . htmlspecialchars($row['product_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
        echo '<td>Rs' . number_format($row['sale_price'], 2) . '</td>';
        echo '<td>' . $row['stock'] . '</td>';
        echo '<td class="' . ($row['stock'] == 0 ? 'status-out' : ($row['stock'] < 5 ? 'status-low' : 'status-in')) . '"><span class="pulse"></span>' . getStatus($row['stock']) . '</td>';
        echo '<td>' . htmlspecialchars($row['color']) . '</td>';
        echo '<td>Rs' . number_format($row['sale_price'] * $row['stock'], 2) . '</td>';
        if ($row['photo']) {
            echo '<td><img src="' . htmlspecialchars($row['photo']) . '" alt="Photo" width="50"></td>';
        } else {
            echo '<td>No Photo</td>';
        }
        echo '<td>' . $row['sold'] . '</td>';
        echo '<td>' . ($row['last_sold'] ? date('Y-m-d H:i:s', strtotime($row['last_sold'])) : 'Never') . '</td>';
        echo '</tr>';
    }
    $conn->close();
    exit();
}

// Handle search for full page load
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql = "SELECT * FROM products WHERE branch_id = $branch_id";
if ($search) {
    $sql .= " AND (product_id LIKE '%$search%' OR name LIKE '%$search%' OR barcode LIKE '%$search%' OR color LIKE '%$search%')";
}
$result = $conn->query($sql);

// Calculate total value for all products
$total_value = 0;
$result_for_total = $conn->query($sql);
while ($row = $result_for_total->fetch_assoc()) {
    $total_value += $row['sale_price'] * $row['stock'];
}

// Stock status function
function getStatus($stock) {
    if ($stock == 0) return 'Out of Stock';
    if ($stock < 5) return 'Low Stock'; // Threshold as per design
    return 'In Stock';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - SKY FIRST MOBILE Billing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #2ecc71;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --text: #333;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--secondary), var(--accent), var(--success));
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            animation: slideDown 0.7s ease;
        }

        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
            animation: shimmer 3s infinite;
        }

        .header-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-grow: 1;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            color: #3498db;
        }

        .header .date-time {
            font-size: 16px;
            text-align: center;
        }

        .header .date-time p {
            margin: 3px 0;
        }

        .header .logout-btn {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 4px;
            background: rgba(255,255,255,0.2);
            transition: var(--transition);
            z-index: 1;
            position: relative;
            overflow: hidden;
        }

        .header .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .header .logout-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .header .logout-btn:hover::after {
            left: 100%;
        }

        .logo-container {
            text-align: center;
            position: relative;
        }

        .logo-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 15px rgba(255,255,255,0.5);
            animation: logoPulse 2s infinite;
            transition: var(--transition);
        }

        .logo-container img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 25px rgba(255,255,255,0.7);
        }

        @keyframes logoPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(52, 152, 219, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0);
            }
        }

        .nav {
            margin: 25px 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            animation: fadeIn 1s ease;
        }

        .nav a {
            margin-right: 0;
            text-decoration: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav a:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 10px rgba(0,0,0,0.15);
        }

        .nav a:nth-child(2) {
            background: linear-gradient(135deg, var(--accent) 0%, var(--warning) 100%);
            box-shadow: 0 4px 6px rgba(231, 76, 60, 0.3);
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease;
            transition: var(--transition);
        }

        .section:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.15), 0 5px 15px rgba(0,0,0,0.07);
        }

        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }

        .inventory-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 28px;
            position: relative;
        }

        .inventory-header h2::after {
            content: '';
            position: absolute;
            bottom: -17px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--secondary);
        }

        .inventory-header a {
            text-decoration: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--success) 0%, #27ae60 100%);
            transition: var(--transition);
        }

        .inventory-header a:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(46, 204, 113, 0.4);
        }

        .search-bar {
            margin-bottom: 20px;
            position: relative;
        }

        .search-bar label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .search-bar input {
            width: 100%;
            padding: 12px 15px;
            padding-left: 45px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .search-bar input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 43px;
            color: #7f8c8d;
        }

        .add-product-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,Zero,0,0.08);
            display: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group select {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--Secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        .form-buttons button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }

        .form-buttons button[type="submit"] {
            background: linear-gradient(135deg, var(--success) 0%, #27ae60 100%);
            color: white;
        }

        .form-buttons button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(46, 204, 113, 0.4);
        }

        .form-buttons button[type="button"] {
            background: linear-gradient(135deg, var(--accent) 0%, #c0392b 100%);
            color: white;
        }

        .form-buttons button[type="button"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(231, 76, 60, 0.4);
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .inventory-table th,
        .inventory-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .inventory-table th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .inventory-table tr:hover {
            background: #f8f9fa;
        }

        .status-in {
            color: var(--success);
            font-weight: 600;
            position: relative;
        }

        .status-low {
            color: var(--warning);
            font-weight: 600;
            position: relative;
        }

        .status-out {
            color: var(--accent);
            font-weight: 600;
            position: relative;
        }

        .pulse {
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.4;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: translateY(-50%) scale(1);
                opacity: 0.4;
            }
            50% {
                transform: translateY(-50%) scale(1.5);
                opacity: 0.2;
            }
            100% {
                transform: translateY(-50%) scale(1);
                opacity: 0.4;
            }
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        .action-buttons button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }

        .action-buttons button[type="submit"] {
            background: linear-gradient(135deg, var(--accent) 0%, #c0392b 100%);
            color: white;
        }

        .action-buttons button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(231, 76, 60, 0.4);
        }

        .action-buttons button[id="edit-product"] {
            background: linear-gradient(135deg, var(--warning) 0%, #e67e22 100%);
            color: white;
        }

        .action-buttons button[id="edit-product"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(243, 156, 18, 0.4);
        }

        .floating-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            animation: slideInRight 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(30px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .inventory-table th,
            .inventory-table td {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .header .date-time {
                text-align: center;
            }

            .nav {
                flex-direction: column;
                align-items: center;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <img src="assets/images/logo.jpg" alt="SKY FIRST MOBILE Logo">
        </div>
        <div class="header-content">
            <h1><i class="fas fa-mobile-alt"></i> SKY FIRST MOBILE Billing</h1>
            <div class="date-time">
                <p id="current-date"><?php echo date('l, F j, Y'); ?></p>
                <p id="current-time"><?php echo date('g:i:s A'); ?></p>
            </div>
        </div>
        <form method="post">
            <button type="submit" name="logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>

    <div class="nav">
        <a href="billing_b2.php"><i class="fas fa-cash-register"></i> Billing</a>
        <a href="inventory_b2.php"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="inventoryb1_show.php"><i class="fas fa-boxes"></i> Inventory B1</a>
        <a href="report_b2.php" class="active"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="reload_management_b2.php"><i class="fas fa-sim-card"></i> Reload Management</a>
        <!--<a href="barcode_b2.php"><i class="fas fa-barcode"></i> Barcode</a>-->
        <a href="credit_customers_b2.php"><i class="fas fa-credit-card"></i> Credit Customers</a>
    </div>

    <div class="section">
        <div class="inventory-header">
            <h2><i class="fas fa-box"></i> Inventory Management</h2>
            <a href="#" id="add-new-product"><i class="fas fa-plus-circle"></i> Add New Product</a>
        </div>

        <div id="add-product-form" class="add-product-form">
            <form method="post" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product ID</label>
                        <input type="text" name="product_id" readonly value="<?php echo generateProductId($conn, $branch_id); ?>">
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" required>
                    </div>
                    <div class="form-group">
                        <label>Original Price</label>
                        <input type="number" name="original_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Sale Price</label>
                        <input type="number" name="sale_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" min="0" required>
                    </div>
                    <!-- <div class="form-group">
                        <label>Barcode</label>
                        <input type="text" name="barcode" id="barcode-input" required>
                    </div> -->
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color">
                    </div>
                    <div class="form-group">
                        <label>Photo</label>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="save_product"><i class="fas fa-save"></i> Save Product</button>
                    <button type="button" id="cancel"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>

        <div class="search-bar">
            <label><i class="fas fa-search"></i> Search Products</label>
            <i class="fas fa-barcode"></i>
            <input type="text" id="search-input" placeholder="Search by Product ID, Name, Barcode, or Color" value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div style="margin-bottom: 20px; font-weight: 600; color: var(--primary); font-size: 18px;">
            Total Value: Rs<?php echo number_format($total_value, 2); ?>
        </div>

        <form method="post" id="inventory-form">
            <div style="overflow-x: auto;">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Product ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Sale Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <!-- <th>Barcode</th> -->
                            <th>Color</th>
                            <th>Value</th>
                            <th>Photo</th>
                            <th>Sold</th>
                            <th>Last Sold</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-tbody">
                        <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><input type="radio" name="selected_products[]" value="<?php echo htmlspecialchars($row['product_id']); ?>"></td>
                            <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>Rs<?php echo number_format($row['sale_price'], 2); ?></td>
                            <td><?php echo $row['stock']; ?></td>
                            <td class="<?php echo $row['stock'] == 0 ? 'status-out' : ($row['stock'] < 5 ? 'status-low' : 'status-in'); ?>">
                                <span class="pulse"></span><?php echo getStatus($row['stock']); ?>
                            </td>
                            <!-- <td><?php echo htmlspecialchars($row['barcode']); ?></td> -->
                            <td><?php echo htmlspecialchars($row['color']); ?></td>
                            <td>Rs<?php echo number_format($row['sale_price'] * $row['stock'], 2); ?></td>
                            <td><?php if ($row['photo']) { ?><img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" width="50"><?php } else { echo 'No Photo'; } ?></td>
                            <td><?php echo $row['sold']; ?></td>
                            <td><?php echo $row['last_sold'] ? date('Y-m-d H:i:s', strtotime($row['last_sold'])) : 'Never'; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons">
                <!-- <button type="submit" name="remove_product"><i class="fas fa-trash-alt"></i> Remove Selected</button> -->
                <button type="button" id="edit-product"><i class="fas fa-edit"></i> Edit Selected</button>
            </div>
        </form>
    </div>
    
    <div class="floating-notification" id="notification">
        Product saved successfully!
    </div>

    <script>
        // Update time in real-time
        function updateClock() {
            const now = new Date();
            
            // Format date
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
            
            // Format time
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            const timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            document.getElementById('current-time').textContent = timeString;
            
            setTimeout(updateClock, 1000);
        }
        
        // Start the clock
        updateClock();
        
        // Show notification if product was saved
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_product']) && empty($errors)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        });
        <?php endif; ?>
        
        // Toggle add product form
        document.getElementById('add-new-product').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('add-product-form').style.display = 'block';
            const form = document.querySelector('#add-product-form form');
            form.reset();
            // Set the product ID directly from PHP-generated value
            document.querySelector('input[name="product_id"]').value = '<?php echo generateProductId($conn, $branch_id); ?>';
        });

        // Cancel form
        document.getElementById('cancel').addEventListener('click', function() {
            document.getElementById('add-product-form').style.display = 'none';
            document.querySelector('#add-product-form form').reset();
        });

        // AJAX Search with debounce on input
        const searchInput = document.getElementById('search-input');
        let timeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchValue = this.value;
                fetch(`inventory_b2.php?ajax_search=1&search=${encodeURIComponent(searchValue)}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('inventory-tbody').innerHTML = data;
                    })
                    .catch(error => console.error('Error:', error));
            }, 300);
        });

        // Also handle Enter key for immediate search
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(timeout);
                const searchValue = this.value;
                fetch(`inventory_b2.php?ajax_search=1&search=${encodeURIComponent(searchValue)}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('inventory-tbody').innerHTML = data;
                    })
                    .catch(error => console.error('Error:', error));
            }
        });

        // Edit product
        document.getElementById('edit-product').addEventListener('click', function() {
            const selected = document.querySelector('input[name="selected_products[]"]:checked');
            if (!selected) {
                alert('Please select a product to edit.');
                return;
            }
            const product_id = selected.value;
            fetch('get_product.php?product_id=' + encodeURIComponent(product_id))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    document.querySelector('input[name="product_id"]').value = data.product_id;
                    document.querySelector('input[name="name"]').value = data.name;
                    document.querySelector('input[name="category"]').value = data.category;
                    document.querySelector('input[name="original_price"]').value = data.original_price;
                    document.querySelector('input[name="sale_price"]').value = data.sale_price;
                    document.querySelector('input[name="stock"]').value = data.stock;
                    // document.querySelector('input[name="barcode"]').value = data.barcode;
                    document.querySelector('input[name="color"]').value = data.color;
                    document.getElementById('add-product-form').style.display = 'block';
                })
                .catch(error => alert('Error fetching product data: ' + error.message));
        });

        // Restrict to single selection
        document.querySelectorAll('input[name="selected_products[]"]').forEach(radio => {
            radio.addEventListener('click', function() {
                document.querySelectorAll('input[name="selected_products[]"]').forEach(other => {
                    if (other !== this) other.checked = false;
                });
            });
        });

        // Add confirmation for save/update
        const addForm = document.querySelector('#add-product-form form');
        addForm.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to save/update this product?')) {
                e.preventDefault();
            }
        });

        // Add confirmation for delete
        const deleteButton = document.querySelector('button[name="remove_product"]');
        deleteButton.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this product?')) {
                e.preventDefault();
            }
        });
        
        // Add animation to table rows when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            const tableRows = document.querySelectorAll('.inventory-table tr');
            tableRows.forEach(row => {
                row.style.opacity = 0;
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(row);
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>