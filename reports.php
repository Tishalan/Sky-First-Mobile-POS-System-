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

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle report generation
$report_type = isset($_POST['report_type']) ? $_POST['report_type'] : '';
$branch = isset($_POST['branch']) ? mysqli_real_escape_string($conn, $_POST['branch']) : '1';
$from_date = isset($_POST['from']) ? mysqli_real_escape_string($conn, $_POST['from']) : '';
$to_date = isset($_POST['to']) ? mysqli_real_escape_string($conn, $_POST['to']) : '';
$results = [];
$total_profit = 0;
$total_sales = 0;
$total_discount = 0;
$total_reload_profit = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])) {
    if ($from_date) {
        // Base where clause
        $where_clause = "WHERE 1=1";
        if ($branch != 'all') {
            $where_clause .= " AND branch_id = '$branch'";
        } else {
            $where_clause .= " AND branch_id IN (1, 2)"; // Include both branches
        }
        // If only from_date is provided, use it for a single day; otherwise, use date range
        if ($to_date) {
            $where_clause .= " AND date BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'";
        } else {
            $where_clause .= " AND DATE(date) = '$from_date'";
        }
        $bills_sql = "SELECT bill_no, date, payment_method, paid_amount, total, phone_repairing, reload, reload_profit FROM bills $where_clause ORDER BY date ASC";
        $bills_result = $conn->query($bills_sql);

        while ($bill = $bills_result->fetch_assoc()) {
            $bill_no = $bill['bill_no'];
            $date = date('Y-m-d', strtotime($bill['date']));
            $time = date('H:i:s', strtotime($bill['date']));
            $payment_method = $bill['payment_method'];
            $paid_amount = floatval($bill['paid_amount']);
            $total_bill = floatval($bill['total']);
            $credit_status = ($payment_method == 'Credit' && $paid_amount < $total_bill) ? 'Partial' : 'Fully Paid';

            // Calculate the effective amount contributing to sales and profit
            $effective_amount = ($payment_method == 'Credit') ? $paid_amount : $total_bill;

            // Non-product items: Phone Repairing
            if ($bill['phone_repairing'] > 0) {
                $repair_amount = ($payment_method == 'Credit') ? min($bill['phone_repairing'], $paid_amount) : $bill['phone_repairing'];
                $profit = 0; // No profit for phone repairing
                $total_profit += $profit;
                $total_sales += $repair_amount;
                $total_discount += 0;
                $results[] = [
                    'date' => $date,
                    'time' => $time,
                    'bill_no' => $bill_no,
                    'product' => 'Phone Repairing',
                    'imei' => '',
                    'quantity' => 1,
                    'sale_price' => $repair_amount,
                    'original_price' => 0,
                    'profit' => $profit,
                    'discount' => 0,
                    'credit_status' => $credit_status
                ];
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
                $results[] = [
                    'date' => $date,
                    'time' => $time,
                    'bill_no' => $bill_no,
                    'product' => 'Reload',
                    'imei' => '',
                    'quantity' => 1,
                    'sale_price' => $reload_amount,
                    'original_price' => $reload_amount - $profit,
                    'profit' => $profit,
                    'discount' => 0,
                    'credit_status' => $credit_status
                ];
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
    $results[] = [
        'date' => $date,
        'time' => $time,
        'bill_no' => $bill_no,
        'product' => $item['name'] . ' (' . $item['color'] . ')',
        'imei' => $item['imei'],
        'quantity' => $item['quantity'],
        'sale_price' => ($payment_method == 'Credit') ? ($effective_item_amount / $item['quantity']) : $item_sale_price,
        'original_price' => $item['original_price'],
        'profit' => $profit,
        'discount' => $effective_discount,
        'credit_status' => $credit_status
    ];
    if ($payment_method == 'Credit') {
        $paid_amount -= $effective_item_amount;
    }
}
        }

        // Group for monthly if selected
        if ($report_type == 'Monthly Sales') {
            $grouped_results = [];
            foreach ($results as $result) {
                $month = date('Y-m', strtotime($result['date']));
                if (!isset($grouped_results[$month])) {
                    $grouped_results[$month] = ['items' => [], 'total_profit' => 0, 'total_sales' => 0, 'total_discount' => 0, 'total_reload_profit' => 0];
                }
                $grouped_results[$month]['items'][] = $result;
                $grouped_results[$month]['total_profit'] += $result['profit'];
                $grouped_results[$month]['total_sales'] += $result['sale_price'] * $result['quantity'];
                $grouped_results[$month]['total_discount'] += $result['discount'];
                if ($result['product'] == 'Reload') {
                    $grouped_results[$month]['total_reload_profit'] += $result['profit'];
                }
            }
            $results = $grouped_results;
        }
    } else {
        echo "<script>alert('Please select the from date.');</script>";
    }
}

// Handle exports
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export_excel'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="sales_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date/Month', 'Bill No', 'Product', 'IMEI', 'Quantity', 'Discount', 'Sale Price', 'Original Price', 'Profit', 'Credit Status']);

    if ($report_type == 'Daily Sales') {
        foreach ($results as $row) {
            fputcsv($output, [
                $row['date'] . ' ' . $row['time'],
                $row['bill_no'],
                $row['product'],
                $row['imei'],
                $row['quantity'],
                number_format($row['discount'], 2),
                number_format($row['sale_price'], 2),
                number_format($row['original_price'], 2),
                number_format($row['profit'], 2),
                $row['credit_status']
            ]);
        }
    } else {
        foreach ($results as $month => $group) {
            foreach ($group['items'] as $row) {
                fputcsv($output, [
                    $month,
                    $row['bill_no'],
                    $row['product'],
                    $row['imei'],
                    $row['quantity'],
                    number_format($row['discount'], 2),
                    number_format($row['sale_price'], 2),
                    number_format($row['original_price'], 2),
                    number_format($row['profit'], 2),
                    $row['credit_status']
                ]);
            }
        }
    }
    fputcsv($output, ['', 'Total Sales', '', '', '', number_format($total_discount, 2), number_format($total_sales, 2), '', number_format($total_profit, 2), '']);
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SKY FIRST MOBILE Billing</title>
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

        .section h2 {
            margin-top: 0;
            color: var(--primary);
            font-size: 28px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
            margin-bottom: 25px;
            position: relative;
        }

        .section h2::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--secondary);
        }

        .form-container {
            margin: 25px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-container label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-container select,
        .form-container input[type="date"] {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
        }

        .form-container select:focus,
        .form-container input[type="date"]:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
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

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            margin: 0 0 10px;
            color: var(--primary);
            font-size: 18px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: var(--secondary);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: var(--card-shadow);
            border-radius: 8px;
            overflow: hidden;
        }

        .report-table th,
        .report-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .report-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .report-table tbody tr:hover {
            background: rgba(52, 152, 219, 0.1);
            transform: scale(1.01);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .report-table .monthly-total {
            background: #f8f9fa;
            font-weight: bold;
        }

        .export-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: flex-end;
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

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
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

        @keyframes slideInRight {
            from {
                transform: translateX(100px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
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
            
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
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
        <a href="billing.php"><i class="fas fa-cash-register"></i> Billing</a>
        <a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="inventoryb2_show.php"><i class="fas fa-boxes"></i> Inventory B2</a>
        <a href="reports.php" class="active"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="reload_management.php"><i class="fas fa-sim-card"></i> Reload Management</a>
        <!--<a href="barcode.php"><i class="fas fa-chart-line"></i> Barcode</a>-->
        <a href="credit_payments.php"><i class="fas fa-credit-card"></i> Credit Payments</a>
    </div>
    
    <div class="section">
        <h2><i class="fas fa-chart-pie"></i> Sales Reports</h2>
        
        <form method="post" class="form-container">
            <div class="form-group">
                <label><i class="fas fa-chart-bar"></i> Report Type</label>
                <select name="report_type" required>
                    <option value="Daily Sales" <?php echo $report_type == 'Daily Sales' ? 'selected' : ''; ?>>Daily Sales</option>
                    <option value="Monthly Sales" <?php echo $report_type == 'Monthly Sales' ? 'selected' : ''; ?>>Monthly Sales</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-building"></i> Branch</label>
                <select name="branch" required>
                    <option value="all" <?php echo $branch == 'all' ? 'selected' : ''; ?>>All Branches</option>
                    <option value="1" <?php echo $branch == '1' ? 'selected' : ''; ?>>Branch 1</option>
                    <option value="2" <?php echo $branch == '2' ? 'selected' : ''; ?>>Branch 2</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-start"></i> From</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($from_date); ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-end"></i> To</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($to_date); ?>">
            </div>
            <div class="form-group">
                <button type="submit" name="generate_report" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Generate Report
                </button>
            </div>
        </form>

        <?php if (!empty($results)) { ?>
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Sales Items</h3>
                    <div class="value">
                        <?php 
                        $total_items = 0;
                        if ($report_type == 'Daily Sales') {
                            $total_items = count($results);
                        } else {
                            foreach ($results as $group) {
                                $total_items += count($group['items']);
                            }
                        }
                        echo $total_items;
                        ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <div class="value">Rs<?php echo number_format($total_sales, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Profit</h3>
                    <div class="value">Rs<?php echo number_format($total_profit, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Discount</h3>
                    <div class="value">Rs<?php echo number_format($total_discount, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Report Period</h3>
                    <div class="value"><?php echo $from_date . ($to_date ? ' to ' . $to_date : ''); ?></div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date/Month</th>
                            <th>Bill No</th>
                            <th>Product</th>
                            <th>IMEI</th>
                            <th>Quantity</th>
                            <th>Discount</th>
                            <th>Sale Price</th>
                            <th>Original Price</th>
                            <th>Profit</th>
                            <th>Credit Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($report_type == 'Daily Sales') {
                            foreach ($results as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['date'] . ' ' . $row['time']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['bill_no']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['product']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['imei'] ?? '') . "</td>";
                                echo "<td>" . $row['quantity'] . "</td>";
                                echo "<td>Rs" . number_format($row['discount'], 2) . "</td>";
                                echo "<td>Rs" . number_format($row['sale_price'], 2) . "</td>";
                                echo "<td>Rs" . number_format($row['original_price'], 2) . "</td>";
                                echo "<td>Rs" . number_format($row['profit'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars($row['credit_status']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($results as $month => $group) {
                                foreach ($group['items'] as $row) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($month) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['bill_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['product']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['imei']) . "</td>";
                                    echo "<td>" . $row['quantity'] . "</td>";
                                    echo "<td>Rs" . number_format($row['discount'], 2) . "</td>";
                                    echo "<td>Rs" . number_format($row['sale_price'], 2) . "</td>";
                                    echo "<td>Rs" . number_format($row['original_price'], 2) . "</td>";
                                    echo "<td>Rs" . number_format($row['profit'], 2) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['credit_status']) . "</td>";
                                    echo "</tr>";
                                }
                                echo "<tr class='monthly-total'>";
                                echo "<td colspan='5'><strong>Monthly Total for " . $month . "</strong></td>";
                                echo "<td><strong>Rs" . number_format($group['total_discount'], 2) . "</strong></td>";
                                echo "<td><strong>Rs" . number_format($group['total_sales'], 2) . "</strong></td>";
                                echo "<td></td>";
                                echo "<td><strong>Rs" . number_format($group['total_profit'], 2) . "</strong></td>";
                                echo "<td></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6"><strong>Total Sales</strong></td>
                            <td><strong>Rs<?php echo number_format($total_sales, 2); ?></strong></td>
                            <td></td>
                            <td><strong>Rs<?php echo number_format($total_profit, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="export-buttons">
                <form method="post">
                    <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
                    <input type="hidden" name="branch" value="<?php echo htmlspecialchars($branch); ?>">
                    <input type="hidden" name="from" value="<?php echo htmlspecialchars($from_date); ?>">
                    <input type="hidden" name="to" value="<?php echo htmlspecialchars($to_date); ?>">
                    <button type="submit" name="export_excel" class="btn btn-warning">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </form>
            </div>
        <?php } else if ($_SERVER["REQUEST_METHOD"] == "POST") { ?>
            <div style="text-align: center; padding: 30px; color: #7f8c8d;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h3>No data found for the selected period</h3>
                <p>Try selecting a different date range</p>
            </div>
        <?php } else { ?>
            <div style="text-align: center; padding: 30px; color: #7f8c8d;">
                <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h3>Generate a sales report</h3>
                <p>Select report type and date range to generate a report</p>
            </div>
        <?php } ?>
    </div>
    
    <div class="floating-notification" id="notification">
        Report generated successfully!
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
        
        // Show notification if report was generated
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report']) && !empty($from_date)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        });
        <?php endif; ?>
        
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
            
            const tableRows = document.querySelectorAll('.report-table tr');
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