<?php
session_start();

// Check if logged in for branch 1 (Main Branch)
if (!isset($_SESSION['admin_main'])) {
    header("Location: login-branch1.php");
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

$branch_id = 1; // Hardcoded for now, update to $_SESSION['branch_id'] later
$_SESSION['branch_id'] = $branch_id;

// Generate unique bill number
function generateBillNo($conn, $branch_id) {
    $prefix = "PS-B" . $branch_id . "-";
    $sql = "SELECT MAX(bill_no) as max_bill FROM bills WHERE bill_no LIKE '$prefix%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $max_bill = $row['max_bill'];
    if ($max_bill && preg_match('/^PS-B' . $branch_id . '-(\d+)$/', $max_bill, $matches)) {
        $numeric_part = (int)$matches[1] + 1;
        return $prefix . str_pad($numeric_part, 4, "0", STR_PAD_LEFT);
    }
    return $prefix . "0001";
}

$bill_no = generateBillNo($conn, $branch_id);

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$products_sql = "SELECT * FROM products WHERE branch_id = $branch_id AND stock > 0";
if ($search) {
    $products_sql .= " AND (name LIKE '%$search%' OR barcode = '$search')";
}
$products_result = $conn->query($products_sql);

// Handle cart (stored in session) - BRANCH SPECIFIC
$cart_session_key = 'cart_branch_' . $branch_id;
if (!isset($_SESSION[$cart_session_key])) {
    $_SESSION[$cart_session_key] = [];
}

// Add product to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount']);
    $imei = mysqli_real_escape_string($conn, $_POST['imei']);
    $product_sql = "SELECT * FROM products WHERE product_id = '$product_id' AND branch_id = $branch_id";
    $product_result = $conn->query($product_sql);
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        $existing_quantity = isset($_SESSION[$cart_session_key][$product_id]) ? $_SESSION[$cart_session_key][$product_id]['quantity'] : 0;
        $total_quantity = $existing_quantity + $quantity;
        if ($total_quantity <= $product['stock']) {
            $new_subtotal = ($product['sale_price'] * $total_quantity);
            if (isset($_SESSION[$cart_session_key][$product_id])) {
                $_SESSION[$cart_session_key][$product_id]['quantity'] = $total_quantity;
                $_SESSION[$cart_session_key][$product_id]['discount'] = $discount;
                $_SESSION[$cart_session_key][$product_id]['subtotal'] = $new_subtotal;
                $_SESSION[$cart_session_key][$product_id]['imei'] = $imei;
            } else {
                $_SESSION[$cart_session_key][$product_id] = [
                    'name' => $product['name'],
                    'color' => $product['color'],
                    'price' => $product['sale_price'],
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'subtotal' => ($product['sale_price'] * $quantity),
                    'photo' => $product['photo'],
                    'imei' => $imei
                ];
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => "Insufficient stock for {$product['name']}. Available: {$product['stock']}"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Product not found."]);
    }
    exit();
}

// Update cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount']);
    $imei = mysqli_real_escape_string($conn, $_POST['imei']);
    $product_sql = "SELECT stock, sale_price FROM products WHERE product_id = '$product_id' AND branch_id = $branch_id";
    $product_result = $conn->query($product_sql);
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        if ($quantity <= $product['stock']) {
            if ($quantity > 0) {
                $_SESSION[$cart_session_key][$product_id]['quantity'] = $quantity;
                $_SESSION[$cart_session_key][$product_id]['discount'] = $discount;
                $_SESSION[$cart_session_key][$product_id]['subtotal'] = ($product['sale_price'] * $quantity);
                $_SESSION[$cart_session_key][$product_id]['imei'] = $imei;
            } else {
                unset($_SESSION[$cart_session_key][$product_id]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => "Insufficient stock for {$_SESSION[$cart_session_key][$product_id]['name']}. Available: {$product['stock']}"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Product not found."]);
    }
    exit();
}

// Function to process reload sale
function process_reload_sale($conn, $provider_id, $amount) {
    $remaining = $amount;
    $total_cost = 0.00;
    $purchases_sql = "SELECT * FROM reload_purchases WHERE provider_id = $provider_id AND balance > 0 ORDER BY purchase_date ASC";
    $purchases_result = $conn->query($purchases_sql);
    if ($purchases_result->num_rows == 0) {
        throw new Exception("No available balance for this provider.");
    }
    while ($purchase = $purchases_result->fetch_assoc()) {
        if ($remaining <= 0) break;
        $deduct = min($remaining, (float)$purchase['balance']);
        $cost_per_unit = (float)$purchase['cost'] / (float)$purchase['amount'];
        $total_cost += $deduct * $cost_per_unit;
        $new_balance = (float)$purchase['balance'] - $deduct;
        $update_sql = "UPDATE reload_purchases SET balance = $new_balance WHERE id = " . $purchase['id'];
        $conn->query($update_sql);
        $remaining -= $deduct;
    }
    if ($remaining > 0) {
        throw new Exception("Insufficient reload balance for provider. Available balance insufficient for " . number_format($amount, 2));
    }
    $profit = $amount - $total_cost;
    return $profit;
}

// Complete sale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_sale'])) {
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $paid_amount = floatval($_POST['paid_amount']);
    $phone_repairing = isset($_POST['phone_repairing']) ? floatval($_POST['phone_repairing']) : 0;
    $reload = isset($_POST['reload']) ? floatval($_POST['reload']) : 0;
    $reload_provider = isset($_POST['reload_provider']) ? intval($_POST['reload_provider']) : 0;
    $customer_name = isset($_POST['customer_name']) ? mysqli_real_escape_string($conn, $_POST['customer_name']) : null;
    $phone_no = isset($_POST['phone_no']) ? mysqli_real_escape_string($conn, $_POST['phone_no']) : null;
    $nic_no = isset($_POST['nic_no']) ? mysqli_real_escape_string($conn, $_POST['nic_no']) : null;
    $reload_profit = 0.00;
    $subtotal = $phone_repairing + $reload;
    $total_discount = 0;

    // Validation checks
    $errors = [];
    if (empty($_SESSION[$cart_session_key]) && $phone_repairing <= 0 && $reload <= 0) {
        $errors[] = "Cart is empty and no additional services selected.";
    }
    if ($reload > 0 && $reload_provider == 0) {
        $errors[] = "Please select a reload provider.";
    }
    if ($payment_method == "") {
        $errors[] = "Please select a payment method.";
    }
    if ($payment_method == "Credit" && (empty($customer_name) || empty($phone_no) || empty($nic_no))) {
        $errors[] = "Customer Name, Phone Number, and NIC Number are required for Credit payment.";
    }

    foreach ($_SESSION[$cart_session_key] as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
        $total_discount += $item['discount'];
    }
    $total = $subtotal - $total_discount;

    if ($total <= 0) {
        $errors[] = "Total amount must be greater than zero.";
    }

    if (!empty($errors)) {
        $error_message = implode("\\n", $errors);
        echo "<script>alert('$error_message');</script>";
    } else {
        if ($reload > 0) {
            try {
                $reload_profit = process_reload_sale($conn, $reload_provider, $reload);
            } catch (Exception $e) {
                echo "<script>alert('" . addslashes($e->getMessage()) . "');</script>";
                exit();
            }
        }
        
        // Calculate balance
        $balance = $paid_amount - $total;
        
        $bill_sql = "INSERT INTO bills (bill_no, branch_id, date, payment_method, subtotal, total_discount, total, paid_amount, balance, phone_repairing, reload, reload_profit, customer_name, phone_no, nic_no)
                     VALUES ('$bill_no', $branch_id, NOW(), '$payment_method', $subtotal, $total_discount, $total, $paid_amount, $balance, $phone_repairing, $reload, $reload_profit, " . ($customer_name ? "'$customer_name'" : "NULL") . ", " . ($phone_no ? "'$phone_no'" : "NULL") . ", " . ($nic_no ? "'$nic_no'" : "NULL") . ")";
        if ($conn->query($bill_sql)) {
            foreach ($_SESSION[$cart_session_key] as $product_id => $item) {
                $item_sql = "INSERT INTO bill_items (bill_no, product_id, quantity, price, discount, subtotal, imei)
                             VALUES ('$bill_no', '$product_id', {$item['quantity']}, {$item['price']}, {$item['discount']}, {$item['subtotal']}, '{$item['imei']}')";
                $conn->query($item_sql);
                $update_stock_sql = "UPDATE products SET stock = stock - {$item['quantity']}, sold = sold + {$item['quantity']}, last_sold = NOW()
                                     WHERE product_id = '$product_id' AND branch_id = $branch_id";
                $conn->query($update_stock_sql);
            }
            // Insert into bill_summary table
            $product_name = null;
            $imei = null;
            $quantity = null;
            if (!empty($_SESSION[$cart_session_key])) {
                $first_item = reset($_SESSION[$cart_session_key]);
                $product_name = mysqli_real_escape_string($conn, $first_item['name']);
                $imei = mysqli_real_escape_string($conn, $first_item['imei']);
                $quantity = $first_item['quantity'];
            } elseif ($phone_repairing > 0) {
                $product_name = 'Phone Repairing';
                $quantity = 1;
            } elseif ($reload > 0) {
                $product_name = 'Reload';
                $quantity = 1;
            }
            $bill_summary_sql = "INSERT INTO bill_summary (bill_no, date, customer_name, phone_no, nic_no, total_amount, balance, product_name, imei, quantity)
                                 VALUES ('$bill_no', NOW(), " . ($customer_name ? "'$customer_name'" : "NULL") . ", " . ($phone_no ? "'$phone_no'" : "NULL") . ", " . ($nic_no ? "'$nic_no'" : "NULL") . ", $total, $balance, " . ($product_name ? "'$product_name'" : "NULL") . ", " . ($imei ? "'$imei'" : "NULL") . ", " . ($quantity ? $quantity : "NULL") . ")";
            if (!$conn->query($bill_summary_sql)) {
                echo "<script>alert('Error inserting into bill_summary: " . addslashes($conn->error) . "');</script>";
            }
                
            $_SESSION[$cart_session_key] = [];
            echo "<script>alert('Sale completed successfully!'); window.location.href='billing.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error completing sale: " . addslashes($conn->error) . "');</script>";
        }
    }
}

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch providers for reload
$providers_sql = "SELECT * FROM reload_providers WHERE branch_id = $branch_id";
$providers_result = $conn->query($providers_sql);

// Compute today's total sales and total profit for both branches
$current_date = date('Y-m-d');
$total_sales = 0;
$total_profit = 0;
$total_discount = 0;
$total_reload_profit = 0;
$where_clause = "WHERE date BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59' AND branch_id IN (1, 2)";
$bills_sql = "SELECT bill_no, date, payment_method, paid_amount, total, phone_repairing, reload, reload_profit FROM bills $where_clause ORDER BY date ASC";
$bills_result = $conn->query($bills_sql);

while ($bill = $bills_result->fetch_assoc()) {
    $bill_no = $bill['bill_no'];
    $payment_method = $bill['payment_method'];
    $paid_amount = floatval($bill['paid_amount']);
    $total_bill = floatval($bill['total']);

    // Calculate the effective amount contributing to sales and profit
    $effective_amount = ($payment_method == 'Credit') ? $paid_amount : $total_bill;

    // Non-product items: Phone Repairing
    if ($bill['phone_repairing'] > 0) {
        $repair_amount = ($payment_method == 'Credit') ? min($bill['phone_repairing'], $paid_amount) : $bill['phone_repairing'];
        $profit = 0; // No profit for phone repairing
        $total_profit += $profit;
        $total_sales += $repair_amount;
        $total_discount += 0;
        if ($payment_method == 'Credit') {
            $paid_amount -= $repair_amount;
        }
    }

    // Non-product items: Reload
    if ($bill['reload'] > 0) {
        $reload_amount = ($payment_method == 'Credit') ? min($bill['reload'], $paid_amount) : $bill['reload'];
        $profit = ($payment_method == 'Credit') ? ($reload_amount / $bill['reload']) * $bill['reload_profit'] : $bill['reload_profit'];
        $total_profit += $profit;
        $total_sales += $reload_amount;
        $total_discount += 0;
        $total_reload_profit += $profit;
        if ($payment_method == 'Credit') {
            $paid_amount -= $reload_amount;
        }
    }

    // Product items
    $items_sql = "SELECT bi.*, p.name, p.original_price, p.color FROM bill_items bi JOIN products p ON bi.product_id = p.product_id WHERE bi.bill_no = '$bill_no'";
    $items_result = $conn->query($items_sql);
    while ($item = $items_result->fetch_assoc()) {
        $item_sale_price = $item['price'] - ($item['discount'] / $item['quantity']);
$item_total = $item_sale_price * $item['quantity'];
$effective_item_amount = ($payment_method == 'Credit') ? min($item_total, $paid_amount) : $item_total;

// New unified profit calculation: effective revenue - prorated cost
$prorate_factor = ($item_total > 0) ? ($effective_item_amount / $item_total) : 0;
$profit = $effective_item_amount - ($item['original_price'] * $item['quantity'] * $prorate_factor);

// Prorate discount for consistency
$effective_discount = $item['discount'] * $prorate_factor;

$total_profit += $profit;
$total_sales += $effective_item_amount;
$total_discount += $effective_discount;
if ($payment_method == 'Credit') {
    $paid_amount -= $effective_item_amount;
}
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKY FIRST MOBILE Billing</title>
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
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }

        .nav {
            margin: 25px 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            animation: fadeIn 1s ease;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav a {
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

        .nav a.active {
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

        .billing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }

        .billing-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 28px;
            position: relative;
        }

        .billing-header h2::after {
            content: '';
            position: absolute;
            bottom: -17px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--secondary);
        }

        .bill-no {
            font-size: 16px;
            font-weight: 600;
            color: var(--secondary);
            background: rgba(52, 152, 219, 0.1);
            padding: 8px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-container {
            display: flex;
            gap: 15px;
            margin-left: 10px;
        }

        .stat-item {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            background: rgba(46, 204, 113, 0.1);
            padding: 8px 15px;
            border-radius: 8px;
            transition: var(--transition);
            animation: pulseStats 2s infinite;
        }

        @keyframes pulseStats {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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

        .billing-container {
            display: flex;
            gap: 25px;
        }

        .left-column {
            flex: 3;
        }

        .right-column {
            flex: 2;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            max-height: 60vh;
            overflow-y: auto;
            padding: 5px;
        }

        .product-item {
            background-color: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            animation: fadeIn 0.5s ease;
        }

        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
            border-color: var(--secondary);
        }

        .product-item img {
            max-height: 70px;
            margin-bottom: 10px;
            border-radius: 8px;
            object-fit: cover;
            transition: var(--transition);
        }

        .product-item:hover img {
            transform: scale(1.05);
        }

        .product-item div {
            margin: 3px 0;
        }

        .product-item div:first-of-type {
            font-weight: 600;
            color: var(--dark);
        }

        .product-item div:nth-of-type(2) {
            color: var(--secondary);
            font-weight: 700;
        }

        .product-item div:nth-of-type(3) {
            color: #7f8c8d;
            font-size: 14px;
        }

        .product-item div:nth-of-type(4) {
            color: var(--accent);
            font-size: 14px;
        }

        .cart-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            animation: fadeInRight 0.8s ease;
        }

        .cart-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 18px;
            position: relative;
            overflow: hidden;
        }

        .cart-header::after {
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

        .cart-content {
            max-height: 40vh;
            overflow-y: auto;
            padding: 10px;
        }

        .cart-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .quantity-controls button {
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition);
        }

        .quantity-controls button:hover {
            background: #2980b9;
        }

        .discount-line,
        .imei-line,
        .subtotal-line {
            font-size: 14px;
            margin: 5px 0;
        }

        .discount-line input,
        .imei-line input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .trash {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
        }

        .trash:hover {
            background: #c0392b;
        }

        .additional-service {
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .additional-service label {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .additional-service input[type="number"],
        .additional-service select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 150px;
        }

        .additional-service select {
            width: 200px;
        }

        .totals {
            margin: 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .totals .subtotal,
        .totals .total-discount,
        .totals .total {
            font-weight: 700;
        }

        .payment-method,
        .customer-details,
        .paid-amount {
            margin: 15px 0;
        }

        .payment-method label,
        .customer-details label,
        .paid-amount label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .payment-method select,
        .customer-details input,
        .paid-amount input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .customer-details {
            display: none;
        }

        .balance {
            font-size: 16px;
            font-weight: 600;
            margin: 15px 0;
        }

        .positive-balance {
            color: var(--success);
        }

        .negative-balance {
            color: var(--accent);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: var(--warning);
            color: white;
        }

        .btn-print:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-complete {
            background: var(--success);
            color: white;
        }

        .btn-complete:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .bill-preview {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-top: 20px;
        }

        .notification {
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
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeInRight {
            from { transform: translateX(30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; visibility: hidden; }
        }

        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }

        @media (max-width: 768px) {
            .billing-container {
                flex-direction: column;
            }

            .nav {
                flex-direction: column;
                align-items: center;
            }

            .stats-container {
                flex-direction: column;
                gap: 10px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .additional-service select {
                width: 100%;
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
        <div class="nav-links">
            <a href="billing.php" class="active"><i class="fas fa-cash-register"></i> Billing</a>
            <a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
            <a href="inventoryb2_show.php"><i class="fas fa-boxes"></i> Inventory B2</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="reload_management.php"><i class="fas fa-sim-card"></i> Reload Management</a>
            <!--<a href="barcode.php"><i class="fas fa-chart-line"></i> Barcode</a>-->
            <a href="credit_payments.php"><i class="fas fa-credit-card"></i> Credit Payments</a>
        </div>
    </div>

    <div class="section">
        <div class="billing-header">
            <h2><i class="fas fa-cash-register"></i> Billing</h2>
            <div class="stats-container">
                <div class="stat-item">Today's Total Sales: Rs<?php echo number_format($total_sales, 2); ?></div>
                <div class="stat-item">Today's Total Profit: Rs<?php echo number_format($total_profit, 2); ?></div>
            </div>
            <div class="bill-no"><i class="fas fa-receipt"></i> Bill No: <?php echo htmlspecialchars($bill_no); ?></div>
            
        </div>

        <div class="search-bar">
            <label><i class="fas fa-search"></i> Search Product</label>
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or barcode...">
        </div>

        <div class="billing-container">
            <div class="left-column">
                <div class="products">
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <div class="product-item" onclick="showIMEIPrompt('<?php echo htmlspecialchars($product['product_id']); ?>', 1, 0)">
                            <?php if ($product['photo']): ?>
                                <img src="<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php endif; ?>
                            <div><?php echo htmlspecialchars($product['name']); ?></div>
                            <div>Rs<?php echo number_format($product['sale_price'], 2); ?></div>
                            <div>Stock: <?php echo $product['stock']; ?></div>
                            <div>Color: <?php echo htmlspecialchars($product['color'] ?? 'N/A'); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="right-column">
                <div class="cart-container">
                    <div class="cart-header">
                        <i class="fas fa-shopping-cart"></i> Cart <span class="cart-pulse"></span>
                    </div>

                    <div class="cart-content">
                        <?php 
                        $subtotal = 0;
                        $total_discount = 0;
                        if (!empty($_SESSION[$cart_session_key])):
                            foreach ($_SESSION[$cart_session_key] as $product_id => $item):
                                $subtotal += ($item['price'] * $item['quantity']);
                                $total_discount += $item['discount'];
                        ?>
                                <div class="cart-item">
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']) . ' (' . htmlspecialchars($item['color']) . ')'; ?></div>
                                        <div class="quantity-controls">
                                            <button class="minus" onclick="updateCart('<?php echo htmlspecialchars($product_id); ?>', <?php echo $item['quantity'] - 1; ?>, <?php echo $item['discount']; ?>, '<?php echo htmlspecialchars($item['imei']); ?>')">-</button>
                                            <span><?php echo $item['quantity']; ?></span>
                                            <button class="plus" onclick="updateCart('<?php echo htmlspecialchars($product_id); ?>', <?php echo $item['quantity'] + 1; ?>, <?php echo $item['discount']; ?>, '<?php echo htmlspecialchars($item['imei']); ?>')">+</button>
                                        </div>
                                        <div class="discount-line">
                                            <span>Rs</span>
                                            <input type="number" step="0.01" min="0" value="<?php echo number_format($item['discount'], 2); ?>" onchange="updateCart('<?php echo htmlspecialchars($product_id); ?>', <?php echo $item['quantity']; ?>, parseFloat(this.value), '<?php echo htmlspecialchars($item['imei']); ?>')">
                                            <span>Discount</span>
                                        </div>
                                        <div class="imei-line">
                                            <span>IMEI:</span>
                                            <input type="text" value="<?php echo htmlspecialchars($item['imei']); ?>" onchange="updateCart('<?php echo htmlspecialchars($product_id); ?>', <?php echo $item['quantity']; ?>, <?php echo $item['discount']; ?>, this.value)">
                                        </div>
                                        <div class="subtotal-line">x Rs<?php echo number_format($item['price'], 2); ?> = Rs<?php echo number_format($item['subtotal'], 2); ?></div>
                                    </div>
                                    <button class="trash" onclick="updateCart('<?php echo htmlspecialchars($product_id); ?>', 0, 0, '')"><i class="fas fa-trash"></i></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div style="padding: 15px;">
                        <form id="totals-form" method="post" onsubmit="return validateForm()">
                            <div class="additional-service">
                                <label>
                                    <input type="checkbox" id="phone-repairing-checkbox" onchange="toggleInput('phone-repairing')">
                                    <i class="fas fa-tools"></i> Phone Repairing
                                </label>
                                <input type="number" name="phone_repairing" value="0" step="0.01" min="0" id="phone-repairing" disabled>
                            </div>
                            <div class="additional-service">
                                <label>
                                    <input type="checkbox" id="reload-checkbox" onchange="toggleReloadInputs()">
                                    <i class="fas fa-sim-card"></i> Reload
                                </label>
                                <input type="number" name="reload" value="0" step="0.01" min="0" id="reload" disabled>
                                <select name="reload_provider" id="reload_provider" disabled>
                                    <option value="">Select Provider</option>
                                    <?php while ($prov = $providers_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($prov['id']); ?>"><?php echo htmlspecialchars($prov['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="totals">
                                <div>Subtotal: <span class="subtotal">Rs<?php echo number_format($subtotal, 2); ?></span></div>
                                <div>Total Discount: <span class="total-discount">-Rs<?php echo number_format($total_discount, 2); ?></span></div>
                                <div>Total: <span class="total">Rs<?php echo number_format($subtotal - $total_discount, 2); ?></span></div>
                            </div>

                            <div class="payment-method">
                                <label><i class="fas fa-credit-card"></i> Payment Method</label>
                                <select name="payment_method" id="payment-method" onchange="toggleCustomerDetails()" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Credit">Credit</option>
                                </select>
                            </div>

                            <div class="customer-details" id="customer-details">
                                <label><i class="fas fa-user"></i> Customer Name</label>
                                <input type="text" name="customer_name" id="customer-name" placeholder="Enter customer name">
                                <label><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="text" name="phone_no" id="phone-no" placeholder="Enter phone number">
                                <label><i class="fas fa-id-card"></i> NIC Number</label>
                                <input type="text" name="nic_no" id="nic-no" placeholder="Enter NIC number">
                            </div>

                            <div class="paid-amount">
                                <label><i class="fas fa-money-bill-wave"></i> Paid Amount</label>
                                <input type="number" name="paid_amount" value="0" step="0.01" min="0" id="paid-amount" required>
                            </div>

                            <div id="balance" class="balance">Balance: Rs<?php echo number_format(0 - ($subtotal - $total_discount), 2); ?></div>

                            <div class="action-buttons">
                                <button type="button" class="btn btn-print" onclick="printBill()"><i class="fas fa-print"></i> Print Bill</button>
                                <button type="submit" name="complete_sale" class="btn btn-complete"><i class="fas fa-check-circle"></i> Complete Sale</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="bill-preview">
            <!-- Bill content will be generated by bill_print_b1.php -->
        </div>
    </div>

    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i> Product added to cart!
    </div>

    <script>
        // Update time in real-time
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;
            document.getElementById('current-time').textContent = timeString;
            setTimeout(updateClock, 1000);
        }

        updateClock();

        function showIMEIPrompt(product_id, quantity, discount) {
            const imei = prompt("Enter IMEI number for the product:");
            if (imei !== null) {
                addToCart(product_id, quantity, discount, imei);
            }
        }

        function addToCart(product_id, quantity, discount, imei) {
            fetch('billing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${encodeURIComponent(product_id)}&quantity=${quantity}&discount=${discount}&imei=${encodeURIComponent(imei)}&add_to_cart=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.getElementById('notification');
                    notification.style.display = 'block';
                    setTimeout(() => notification.style.display = 'none', 3000);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart.');
            });
        }

        function updateCart(product_id, quantity, discount, imei) {
            fetch('billing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${encodeURIComponent(product_id)}&quantity=${quantity}&discount=${discount}&imei=${encodeURIComponent(imei)}&update_cart=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating cart.');
            });
        }

        function toggleCustomerDetails() {
            const paymentMethod = document.getElementById('payment-method').value;
            const customerDetails = document.getElementById('customer-details');
            if (paymentMethod === 'Credit') {
                customerDetails.style.display = 'block';
            } else {
                customerDetails.style.display = 'none';
                document.getElementById('customer-name').value = '';
                document.getElementById('phone-no').value = '';
                document.getElementById('nic-no').value = '';
            }
        }

        function validateForm() {
            const paymentMethod = document.getElementById('payment-method').value;
            const paidAmount = parseFloat(document.getElementById('paid-amount').value) || 0;
            const reloadCheckbox = document.getElementById('reload-checkbox').checked;
            const reloadAmount = parseFloat(document.getElementById('reload').value) || 0;
            const reloadProvider = document.getElementById('reload_provider').value;
            const phoneRepairingCheckbox = document.getElementById('phone-repairing-checkbox').checked;
            const phoneRepairingAmount = parseFloat(document.getElementById('phone-repairing').value) || 0;
            const cartItems = <?php echo json_encode(!empty($_SESSION[$cart_session_key])); ?>;
            const customerName = document.getElementById('customer-name').value;
            const phoneNo = document.getElementById('phone-no').value;
            const nicNo = document.getElementById('nic-no').value;
            
            let errors = [];
            
            if (!cartItems && !phoneRepairingCheckbox && !reloadCheckbox) {
                errors.push("Cart is empty and no additional services selected.");
            }
            if (reloadCheckbox && reloadAmount > 0 && !reloadProvider) {
                errors.push("Please select a reload provider.");
            }
            if (!paymentMethod) {
                errors.push("Please select a payment method.");
            }
            
            if (paymentMethod === 'Credit' && (!customerName || !phoneNo || !nicNo)) {
                errors.push("Customer Name, Phone Number, and NIC Number are required for Credit payment.");
            }

            if (errors.length > 0) {
                alert(errors.join("\n"));
                return false;
            }
            return true;
        }

        document.getElementById('search-input').addEventListener('input', function() {
            window.location.href = '?search=' + encodeURIComponent(this.value);
        });

        document.getElementById('search-input').addEventListener('focus', function() {
            this.select();
        });

        function toggleInput(inputId) {
            const input = document.getElementById(inputId);
            const checkbox = document.getElementById(inputId + '-checkbox');
            input.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                input.value = '0';
                updateTotal();
            }
        }

        function toggleReloadInputs() {
            const checkbox = document.getElementById('reload-checkbox');
            const input = document.getElementById('reload');
            const select = document.getElementById('reload_provider');
            input.disabled = !checkbox.checked;
            select.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                input.value = '0';
                select.value = '';
                updateTotal();
            }
        }

        const productNet = <?php echo !empty($_SESSION[$cart_session_key]) ? $subtotal : 0; ?>;
        const totalDiscountValue = <?php echo !empty($_SESSION[$cart_session_key]) ? $total_discount : 0; ?>;

        function updateTotal() {
            const phone = document.getElementById('phone-repairing-checkbox').checked ? parseFloat(document.getElementById('phone-repairing').value) || 0 : 0;
            const reload = document.getElementById('reload-checkbox').checked ? parseFloat(document.getElementById('reload').value) || 0 : 0;
            const totalValue = productNet + phone + reload - totalDiscountValue;
            if (document.querySelector('.subtotal')) {
                document.querySelector('.subtotal').textContent = `Rs${(productNet + phone + reload).toFixed(2)}`;
            }
            if (document.querySelector('.total')) {
                document.querySelector('.total').textContent = `Rs${totalValue.toFixed(2)}`;
            }
            const paid = parseFloat(document.getElementById('paid-amount').value) || 0;
            const balanceValue = paid - totalValue;
            const balanceElement = document.getElementById('balance');
            if (balanceElement) {
                balanceElement.textContent = `Balance: Rs${balanceValue.toFixed(2)}`;
                balanceElement.className = balanceValue < 0 ? 'balance negative-balance' : 'balance positive-balance';
            }
        }

        if (document.getElementById('phone-repairing')) {
            document.getElementById('phone-repairing').addEventListener('input', updateTotal);
            document.getElementById('reload').addEventListener('input', updateTotal);
            document.getElementById('paid-amount').addEventListener('input', updateTotal);
            window.addEventListener('load', updateTotal);
        }

        function printBill() {
            const phone = document.getElementById('phone-repairing-checkbox').checked ? parseFloat(document.getElementById('phone-repairing').value) || 0 : 0;
            const reload = document.getElementById('reload-checkbox').checked ? parseFloat(document.getElementById('reload').value) || 0 : 0;
            const paymentMethod = document.getElementById('payment-method').value;
            const paid = parseFloat(document.getElementById('paid-amount').value) || 0;
            
            // Get customer details
            const customerName = document.getElementById('customer-name').value;
            const phoneNo = document.getElementById('phone-no').value;
            const nicNo = document.getElementById('nic-no').value;
            
            // Validate payment method
            if(paymentMethod == ""){
                alert("Please select Payment Method");
                return;
            }
            
            // Create a form to submit data to bill_print_b1.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'bill_print_b1.php';
            form.target = '_blank'; // Opens in new tab
            
            // Add bill data as hidden inputs
            const billNo = '<?php echo htmlspecialchars($bill_no); ?>';
            
            addHiddenField(form, 'bill_no', billNo);
            addHiddenField(form, 'payment_method', paymentMethod);
            addHiddenField(form, 'paid_amount', paid);
            addHiddenField(form, 'phone_repairing', phone);
            addHiddenField(form, 'reload', reload);
            addHiddenField(form, 'customer_name', customerName);
            addHiddenField(form, 'phone_no', phoneNo);
            addHiddenField(form, 'nic_no', nicNo);
            
            // Submit form to open bill print page
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function addHiddenField(form, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            const productItems = document.querySelectorAll('.product-item');
            productItems.forEach(item => {
                item.style.opacity = 0;
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(item);
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>