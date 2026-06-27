<?php
session_start();

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

$branch_id = 2; // Hardcoded for now, update to $_SESSION['branch_id'] later
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

// Calculate total sales for the current day
$current_date = date('Y-m-d');
$total_sales_sql = "SELECT SUM(paid_amount) as total_sales FROM bills WHERE branch_id = $branch_id AND DATE(date) = '$current_date'";
$total_sales_result = $conn->query($total_sales_sql);
$total_sales = $total_sales_result->fetch_assoc()['total_sales'] ?? 0;

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$products_sql = "SELECT * FROM products WHERE branch_id = $branch_id AND stock > 0";
if ($search) {
    $products_sql .= " AND (name LIKE '%$search%' OR barcode = '$search')";
}
$products_result = $conn->query($products_sql);

// Fetch providers for reload selection
$providers_sql = "SELECT rp.id, rp.name, SUM(rpu.balance) as total_balance 
                 FROM reload_providers rp 
                 LEFT JOIN reload_purchases rpu ON rp.id = rpu.provider_id 
                 WHERE rp.branch_id = $branch_id 
                 GROUP BY rp.id 
                 HAVING total_balance > 0";
$providers_result = $conn->query($providers_sql);

// Handle cart (stored in session) - BRANCH SPECIFIC
$cart_session_key = 'cart_branch_' . $branch_id;
if (!isset($_SESSION[$cart_session_key])) {
    $_SESSION[$cart_session_key] = [];
}

// Remove realme (white) from cart if it exists
foreach ($_SESSION[$cart_session_key] as $product_id => $item) {
    if (strtolower($item['name']) === 'realme' && strtolower($item['color']) === 'white') {
        unset($_SESSION[$cart_session_key][$product_id]);
    }
}

// Add product to cart from inventory_b2.php selection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount']);
    $imei = mysqli_real_escape_string($conn, $_POST['imei']);
    $product_sql = "SELECT * FROM products WHERE product_id = '$product_id' AND branch_id = $branch_id";
    $product_result = $conn->query($product_sql);
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        // Prevent adding realme (white) to cart
        if (strtolower($product['name']) === 'realme' && strtolower($product['color']) === 'white') {
            echo json_encode(['success' => false, 'message' => "Realme (white) cannot be added to the cart."]);
            exit();
        }
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
        } else {
            echo json_encode(['success' => false, 'message' => "Insufficient stock for {$product['name']}. Available: {$product['stock']}"]);
            exit();
        }
    }
    echo json_encode(['success' => true]);
    exit();
}

// Update cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount']);
    $imei = mysqli_real_escape_string($conn, $_POST['imei']);
    $product_sql = "SELECT stock, sale_price, name, color FROM products WHERE product_id = '$product_id' AND branch_id = $branch_id";
    $product_result = $conn->query($product_sql);
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        // Prevent updating realme (white) in cart
        if (strtolower($product['name']) === 'realme' && strtolower($product['color']) === 'white') {
            echo json_encode(['success' => false, 'message' => "Realme (white) cannot be updated in the cart."]);
            exit();
        }
        if ($quantity <= $product['stock']) {
            if ($quantity > 0) {
                $_SESSION[$cart_session_key][$product_id]['quantity'] = $quantity;
                $_SESSION[$cart_session_key][$product_id]['discount'] = $discount;
                $_SESSION[$cart_session_key][$product_id]['subtotal'] = ($product['sale_price'] * $quantity);
                $_SESSION[$cart_session_key][$product_id]['imei'] = $imei;
            } else {
                unset($_SESSION[$cart_session_key][$product_id]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Insufficient stock for {$_SESSION[$cart_session_key][$product_id]['name']}. Available: {$product['stock']}"]);
            exit();
        }
    }
    echo json_encode(['success' => true]);
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
    $errors = [];
    
    $payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : '';
    $paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
    $phone_repairing = isset($_POST['phone_repairing']) ? floatval($_POST['phone_repairing']) : 0;
    $reload = isset($_POST['reload']) ? floatval($_POST['reload']) : 0;
    $provider_id = isset($_POST['provider_id']) ? intval($_POST['provider_id']) : 0;
    $customer_name = isset($_POST['customer_name']) ? mysqli_real_escape_string($conn, $_POST['customer_name']) : '';
    $phone_no = isset($_POST['phone_no']) ? mysqli_real_escape_string($conn, $_POST['phone_no']) : '';
    $nic_no = isset($_POST['nic_no']) ? mysqli_real_escape_string($conn, $_POST['nic_no']) : '';

    if (empty($_SESSION[$cart_session_key]) && $phone_repairing <= 0 && $reload <= 0) {
        $errors[] = "Cart is empty and no additional services selected.";
    }
    if ($reload > 0 && $provider_id <= 0) {
        $errors[] = "Please select a reload provider.";
    }
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method.";
    }
    // if ($paid_amount <= 0) {
    //     $errors[] = "Paid amount must be greater than zero.";
    // }
    if ($payment_method == 'Credit' && (empty($customer_name) || empty($phone_no) || empty($nic_no))) {
        $errors[] = "Customer Name, Phone Number, and NIC Number are required for Credit payment.";
    }

    if (!empty($errors)) {
        $error_message = implode("\\n", $errors);
        echo "<script>alert('$error_message');</script>";
    } else {
        $reload_profit = 0;
        if ($reload > 0) {
            try {
                $reload_profit = process_reload_sale($conn, $provider_id, $reload);
            } catch (Exception $e) {
                echo "<script>alert('" . addslashes($e->getMessage()) . "');</script>";
                exit();
            }
        }

        $subtotal = $phone_repairing + $reload;
        $total_discount = 0;
        foreach ($_SESSION[$cart_session_key] as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
            $total_discount += $item['discount'];
        }
        $total = $subtotal - $total_discount;
        $balance = $paid_amount - $total;

        if ($total >= 0) {
            $bill_sql = "INSERT INTO bills (bill_no, branch_id, date, payment_method, subtotal, total_discount, total, paid_amount, balance, phone_repairing, reload, reload_profit, customer_name, phone_no, nic_no)
                         VALUES ('$bill_no', $branch_id, NOW(), '$payment_method', $subtotal, $total_discount, $total, $paid_amount, $balance, $phone_repairing, $reload, $reload_profit, '$customer_name', '$phone_no', '$nic_no')";
            if ($conn->query($bill_sql)) {
                foreach ($_SESSION[$cart_session_key] as $product_id => $item) {
                    $imei = mysqli_real_escape_string($conn, $item['imei']);
                    $item_sql = "INSERT INTO bill_items (bill_no, product_id, quantity, price, discount, subtotal, imei)
                                 VALUES ('$bill_no', '$product_id', {$item['quantity']}, {$item['price']}, {$item['discount']}, {$item['subtotal']}, '$imei')";
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
                echo "<script>alert('Sale completed successfully! Customer Name: $customer_name, Phone: $phone_no, NIC: $nic_no'); window.location.href='billing_b2.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error completing sale: " . addslashes($conn->error) . "');</script>";
            }
        } else {
            echo "<script>alert('Invalid total amount.');</script>";
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

        .nav a:nth-child(1) {
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
        }

        .total-sales {
            margin-left: 15px;
            color: var(--success);
            background: rgba(46, 159, 204, 0.1);
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite, glow 1.5s infinite alternate;
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 5px rgba(46, 204, 113, 0.5);
            }
            100% {
                box-shadow: 0 0 20px rgba(46, 204, 113, 0.8);
            }
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
            padding: 15px;
            max-height: 50vh;
            overflow-y: auto;
        }

        .cart-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            animation: slideInRight 0.5s ease;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            margin: 8px 0;
        }

        .quantity-controls button {
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-controls button.minus {
            background-color: var(--accent);
        }

        .quantity-controls button.minus:hover {
            background-color: #c0392b;
        }

        .quantity-controls span {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 6px 12px;
            margin: 0 8px;
            min-width: 40px;
            text-align: center;
            border-radius: 5px;
            font-weight: 600;
        }

        .quantity-controls button.plus {
            background-color: var(--success);
        }

        .quantity-controls button.plus:hover {
            background-color: #27ae60;
        }

        .discount-line {
            display: flex;
            align-items: center;
            margin: 8px 0;
            gap: 8px;
        }

        .discount-line span {
            color: #7f8c8d;
        }

        .discount-line input {
            width: 70px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            transition: var(--transition);
        }

        .discount-line input:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .imei-line {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }

        .imei-line input {
            width: 100px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .trash {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .trash:hover {
            background-color: #c0392b;
        }

        .subtotal-line {
            font-size: 14px;
            color: var(--secondary);
            font-weight: 600;
        }

        .additional-service {
            margin: 15px 0;
        }

        .additional-service label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .additional-service input[type="checkbox"] {
            margin-right: 8px;
            vertical-align: middle;
        }

        .additional-service input[type="number"],
        .additional-service select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .additional-service input[type="number"]:focus,
        .additional-service select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .additional-service input[type="number"][disabled],
        .additional-service select[disabled] {
            background: #f0f0f0;
            border-color: #ddd;
            cursor: not-allowed;
        }

        .totals {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
        }

        .totals div:hover {
            transform: translateX(5px);
        }

        .totals .subtotal {
            color: var(--primary);
            animation: scaleIn 0.5s ease;
        }

        .totals .total-discount {
            color: var(--accent);
            animation: scaleIn 0.5s ease;
        }

        .totals .total {
            color: var(--success);
            font-size: 18px;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0.95);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .payment-method {
            margin: 15px 0;
        }

        .payment-method label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .payment-method select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            animation: fadeIn 0.5s ease;
        }

        .payment-method select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .paid-amount {
            margin: 15px 0;
        }

        .paid-amount label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .paid-amount input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            animation: fadeIn 0.5s ease;
        }

        .paid-amount input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .balance {
            font-size: 16px;
            font-weight: 600;
            padding: 10px;
            border-radius: 8px;
            margin: 15px 0;
            transition: var(--transition);
            animation: fadeIn 0.5s ease;
        }

        .balance.positive-balance {
            color: var(--success);
            background: rgba(46, 204, 113, 0.1);
        }

        .balance.negative-balance {
            color: var(--accent);
            background: rgba(231, 76, 60, 0.1);
        }

        .balance:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .action-buttons .btn-print {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            color: white;
            animation: pulse 2s infinite;
        }

        .action-buttons .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .action-buttons .btn-complete {
            background: linear-gradient(135deg, var(--success) 0%, #27ae60 100%);
            color: white;
            animation: pulse 2s infinite;
        }

        .action-buttons .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .customer-details {
            margin: 15px 0;
            display: none;
        }

        .customer-details input[type="text"] {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .customer-details input[type="text"]:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
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
        <a href="billing_b2.php" class="active"><i class="fas fa-cash-register"></i> Billing</a>
        <a href="inventory_b2.php"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="inventoryb1_show.php"><i class="fas fa-boxes"></i> Inventory B1</a>
        <a href="report_b2.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="reload_management_b2.php"><i class="fas fa-sim-card"></i> Reload Management</a>
        <!--<a href="barcode_b2.php"><i class="fas fa-barcode"></i> Barcode</a>-->
        <a href="credit_customers_b2.php"><i class="fas fa-credit-card"></i> Credit Customers</a>
    </div>
    
    <div class="section">
        <div class="billing-header">
            <h2><i class="fas fa-receipt"></i> Create New Bill</h2>
            <div class="total-sales">Today's Total Sales: Rs<?php echo number_format($total_sales, 2); ?></div>
            <div class="bill-no">Bill No: <?php echo $bill_no; ?> | <?php echo date('m/d/Y'); ?> 
            </div>
        </div>
        
    
        
        <div class="billing-container">
            <div class="left-column">
                <div class="search-bar">
                    <label><i class="fas fa-search"></i> Scan Barcode or Search Product</label>
                    <i class="fas fa-barcode"></i>
                    <input type="text" id="search-input" placeholder="Scan barcode or type product name/barcode" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="products">
                    <?php if ($products_result->num_rows > 0): ?>
                        <?php while ($row = $products_result->fetch_assoc()) { ?>
                            <?php if (!(strtolower($row['name']) === 'realme' && strtolower($row['color']) === 'white')) { ?>
                                <div class="product-item" onclick="showIMEIPrompt('<?php echo $row['product_id']; ?>', 1, 0)">
                                    <img src="<?php echo htmlspecialchars($row['photo'] ? $row['photo'] : 'placeholder-icon.png'); ?>" alt="Product Photo">
                                    <div><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div>Rs<?php echo number_format($row['sale_price'], 2); ?></div>
                                    <div>Stock: <?php echo $row['stock']; ?></div>
                                    <div>Color: <?php echo htmlspecialchars($row['color']); ?></div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 30px; color: #7f8c8d;">
                            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h3>No products found</h3>
                            <p>Try a different search term or check inventory</p>
                        </div>
                    <?php endif; ?>
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
                            foreach ($_SESSION[$cart_session_key] as $product_id => $item) {
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
                            <?php } ?>
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
                                <select name="provider_id" id="reload_provider" disabled>
                                    <option value="">Select Provider</option>
                                    <?php while ($prov = $providers_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($prov['id']); ?>">
                                            <?php echo htmlspecialchars($prov['name']) . ' (Rs' . number_format($prov['total_balance'], 2) . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="number" step="0.01" name="reload" id="reload" value="0" min="0" disabled>
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
            fetch('billing_b2.php', {
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
            fetch('billing_b2.php', {
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
            // if (paidAmount <= 0) {
            //     errors.push("Paid amount must be greater than zero.");
            // }
            if (paymentMethod === 'Credit' && (!customerName || !phoneNo || !nicNo)) {
                errors.push("Customer Name, Phone Number, and NIC Number are required for Credit payment.");
            }

            if (errors.length > 0) {
                alert(errors.join("\n"));
                return false;
            }
            return true;
        }

        let searchTimeout;
document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    
    // Only search if user types at least 2 characters or clears the input
    if (this.value.length >= 2 || this.value.length === 0) {
        searchTimeout = setTimeout(() => {
            window.location.href = '?search=' + encodeURIComponent(this.value);
        }, 300); // 300ms delay
    }
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
            const providerSelect = document.getElementById('reload_provider');
            const providerName = providerSelect.options[providerSelect.selectedIndex]?.text.split(' (')[0] || '';
            const paymentMethod = document.getElementById('payment-method').value;
            const paid = parseFloat(document.getElementById('paid-amount').value) || 0;
            const productNet = <?php echo !empty($_SESSION[$cart_session_key]) ? $subtotal : 0; ?>;
            const totDiscount = <?php echo !empty($_SESSION[$cart_session_key]) ? $total_discount : 0; ?>;
            const subTotal = productNet + phone + reload;
            const tot = subTotal - totDiscount;
            const balance = paid - tot;

            if (!paymentMethod) {
                alert("Please select Payment Method");
                return;
            }

            let content = `
                <div style="text-align: center; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4;">
                    <img src="assets/images/logo.jpg" alt="Logo" style="width: 100px; margin-bottom: 10px; border: 2px solid #3498db; border-radius: 8px;">
                    <h2 style="color: blue; font-size: 25px; margin: 2px 0;">SKY FIRST MOBILE</h2>
                    <p style="margin: 2px 0;">Whole & Retail Dealers in Mobile Phone,</p>
                    <p style="margin: 2px 0;">Phone Accessories & Mobile Repairing</p>
                    <p style="margin: 2px 0;">Address: No 3 kasthuriyar Road Junction Semmatheru Jaffna</p>
                    <p style="margin: 2px 0;">Phone: 0741077750  0777377750  0703077750</p>
                    <p style="margin: 2px 0;">Bill No: <?php echo $bill_no; ?></p>
                    <p style="margin: 2px 0;">Date: ${new Date().toLocaleString()}</p>
                    <p style="margin: 2px 0;">Payment Method: ${paymentMethod}</p>
                </div>
                <div style="font-size: 12px; margin-top: 20px;">
            `;

            <?php foreach ($_SESSION[$cart_session_key] as $product_id => $item) { ?>
                content += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>Product: <?php echo htmlspecialchars($item['name']) . ' (' . htmlspecialchars($item['color']) . ')'; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>IMEI: <?php echo htmlspecialchars($item['imei']); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>Qty: <?php echo $item['quantity']; ?></span>
                        <span>Price: Rs<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        <span>Discount: Rs<?php echo number_format($item['discount'], 2); ?></span>
                    </div>
                `;
            <?php } ?>

            if (phone > 0) {
                content += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>Product: Phone Repairing</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>Qty: 1</span>
                        <span>Price: Rs${phone.toFixed(2)}</span>
                        <span>Discount: Rs0.00</span>
                    </div>
                `;
            }

            if (reload > 0) {
                content += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>Product: Reload (${providerName})</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span>Qty: 1</span>
                        <span>Price: Rs${reload.toFixed(2)}</span>
                        <span>Discount: Rs0.00</span>
                    </div>
                `;
            }

            content += `
                    <hr style="border: 1px solid #ddd; margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; margin-top: 25px;">
                        <span>Subtotal:</span>
                        <span>Rs${subTotal.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <span>Total Discount:</span>
                        <span>-Rs${totDiscount.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <span>Total:</span>
                        <span>Rs${tot.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <span>Paid:</span>
                        <span>Rs${paid.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <span>Balance:</span>
                        <span>Rs${balance.toFixed(2)}</span>
                    </div>
                </div>
                <hr style="border: 1px solid #ddd; margin: 10px 0;">
                <p style="text-align: center; font-size: 12px; margin-top: 20px;">Thank you, Visit Again!</p>
                <div style="display: flex; justify-content: center; align-items: center; margin-top: 10px;  padding: 10px;">
                    <span style="font-size: 12px; margin-right: 10px; color: black;">Develop By:</span>
                    <div style="text-align: center;">
                        <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                            <span style="color: red;">S</span>
                            <span style="color: black;">K</span>
                            <span style="color: black;">Y-</span>
                            <span style="color: red;">T</span>
                            <span style="color: black;">E</span>
                            <span style="color: black;">C</span>
                        </div>
                        <p style="font-size: 10px; margin-top: 0; font-weight: bold; color: black;">+94 75 090 6065</p>
                    </div>
                </div>
            `;

            const printWindow = window.open('', '', 'height=600, width=800');
            printWindow.document.write('<html><head><title>Bill</title><style>body{font-family:Arial,sans-serif;}</style></head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
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