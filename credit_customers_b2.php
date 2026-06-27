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

// Calculate total credit bills count from bill_summary
$total_credits_sql = "SELECT COUNT(*) as total_credits FROM bill_summary WHERE EXISTS (SELECT 1 FROM bills WHERE bills.bill_no = bill_summary.bill_no AND bills.branch_id = $branch_id AND bills.payment_method = 'Credit')";
$total_credits_result = $conn->query($total_credits_sql);
$total_credits = $total_credits_result->fetch_assoc()['total_credits'] ?? 0;

// Calculate total credit amount from bill_summary
$total_credit_amount_sql = "SELECT SUM(balance) as total_credit_amount FROM bill_summary WHERE EXISTS (SELECT 1 FROM bills WHERE bills.bill_no = bill_summary.bill_no AND bills.branch_id = $branch_id AND bills.payment_method = 'Credit')";
$total_credit_amount_result = $conn->query($total_credit_amount_sql);
$total_credit_amount = $total_credit_amount_result->fetch_assoc()['total_credit_amount'] ?? 0;

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$credit_bills_sql = "SELECT bs.*, b.payment_method FROM bill_summary bs JOIN bills b ON bs.bill_no = b.bill_no WHERE b.branch_id = $branch_id AND b.payment_method = 'Credit'";
if ($search) {
    $credit_bills_sql .= " AND (bs.customer_name LIKE '%$search%' OR bs.phone_no LIKE '%$search%' OR bs.nic_no LIKE '%$search%' OR bs.bill_no LIKE '%$search%')";
}
$credit_bills_sql .= " ORDER BY bs.date DESC";
$credit_bills_result = $conn->query($credit_bills_sql);

// Handle update payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_payment'])) {
    $bill_no = mysqli_real_escape_string($conn, $_POST['bill_no']);
    $payment_amount = floatval($_POST['payment_amount']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Verify this is a credit bill and get current balance
        $verify_sql = "SELECT b.*, bs.balance as summary_balance FROM bills b JOIN bill_summary bs ON b.bill_no = bs.bill_no WHERE b.bill_no = '$bill_no' AND b.payment_method = 'Credit' AND b.branch_id = $branch_id";
        $verify_result = $conn->query($verify_sql);
        
        if ($verify_result->num_rows == 0) {
            throw new Exception("This bill is not a credit bill or doesn't exist.");
        }
        
        $bill_data = $verify_result->fetch_assoc();
        $current_balance = floatval($bill_data['summary_balance']);
        
        // Validate payment amount
        if ($payment_amount <= 0) {
            throw new Exception("Payment amount must be greater than zero.");
        }
        if ($payment_amount > abs($current_balance)) {
            throw new Exception("Payment amount cannot exceed the outstanding balance of Rs" . number_format(abs($current_balance), 2));
        }
        
        // Update balances
        $new_balance = $current_balance + $payment_amount; // Since balance is negative, adding payment reduces it
        $new_paid_amount = floatval($bill_data['paid_amount']) + $payment_amount;
        
        // Update bills table
        $update_bills_sql = "UPDATE bills SET paid_amount = $new_paid_amount, balance = $new_balance WHERE bill_no = '$bill_no'";
        if (!$conn->query($update_bills_sql)) {
            throw new Exception("Error updating bills: " . $conn->error);
        }
        
        // Update bill_summary table
        $update_summary_sql = "UPDATE bill_summary SET balance = $new_balance WHERE bill_no = '$bill_no'";
        if (!$conn->query($update_summary_sql)) {
            throw new Exception("Error updating bill summary: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo "<script>alert('Payment updated successfully! New balance: Rs" . number_format($new_balance, 2) . "'); window.location.href='credit_customers_b2.php?search=" . urlencode($search) . "';</script>";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "<script>alert('Error updating payment: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Handle delete credit bill - FIXED: Do not restore stock
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_bill'])) {
    $bill_no = mysqli_real_escape_string($conn, $_POST['bill_no']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Only delete from bill_summary - DO NOT RESTORE STOCK
        $delete_summary_sql = "DELETE FROM bill_summary WHERE bill_no = '$bill_no'";
        $conn->query($delete_summary_sql);
        
        // Commit transaction
        $conn->commit();
        
        echo "<script>alert('Credit bill deleted successfully! Stock remains unchanged as this was a valid sale.'); window.location.href='credit_customers_b2.php?search=" . urlencode($search) . "';</script>";
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "<script>alert('Error deleting credit bill: " . addslashes($e->getMessage()) . "');</script>";
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
    <title>SKY FIRST MOBILE - Credit Customers</title>
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

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }

        .section-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 28px;
            position: relative;
        }

        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: -17px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--secondary);
        }

        .total-credits, .total-credit-amount {
            color: var(--accent);
            background: rgba(231, 76, 60, 0.1);
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
                box-shadow: 0 0 5px rgba(231, 76, 60, 0.5);
            }
            100% {
                box-shadow: 0 0 20px rgba(231, 76, 60, 0.8);
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

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .credit-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .credit-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .credit-table tr {
            transition: var(--transition);
            animation: fadeIn 0.5s ease;
        }

        .credit-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .credit-table tr:hover {
            background-color: #e8f4fc;
            transform: translateX(5px);
        }

        .credit-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .bill-no {
            font-weight: 600;
            color: var(--secondary);
        }

        .customer-name {
            font-weight: 600;
            color: var(--dark);
        }

        .phone-no {
            color: var(--secondary);
        }

        .nic-no {
            color: #7f8c8d;
        }

        .total-amount {
            font-weight: 600;
            color: var(--success);
        }

        .balance {
            font-weight: 600;
            color: var(--accent);
        }

        .action-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            margin-right: 5px;
        }

        .action-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .delete-btn {
            background: linear-gradient(135deg, var(--accent) 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .no-records i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            width: 90%;
            max-width: 400px;
            position: relative;
            animation: fadeInUp 0.3s ease;
        }

        .modal-content h3 {
            margin-top: 0;
            color: var(--primary);
        }

        .modal-content .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text);
        }

        .modal-content .form-group {
            margin-bottom: 15px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .modal-content input:focus {
            border-color: var(--secondary);
            outline: none;
        }

        .modal-content button {
            width: 100%;
            padding: 12px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .modal-content button:hover {
            background: #2980b9;
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
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
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
        <a href="report_b2.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="reload_management_b2.php"><i class="fas fa-sim-card"></i> Reload Management</a>
        <!--<a href="barcode_b2.php"><i class="fas fa-barcode"></i> Barcode</a>-->
        <a href="credit_customers_b2.php" class="active"><i class="fas fa-credit-card"></i> Credit Customers</a>
    </div>
    
    <div class="section">
        <div class="section-header">
            <h2><i class="fas fa-users"></i> Credit Bill Management</h2>
            <div>
                <div class="total-credits">Total Credit Bills: <?php echo $total_credits; ?></div>
                <div class="total-credit-amount">Total Credit Amount: Rs<?php echo number_format($total_credit_amount, 2); ?></div>
            </div>
        </div>
        
        <div class="search-bar">
            <label><i class="fas fa-search"></i> Search Credit Bills</label>
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Search by Customer Name, Phone Number, NIC Number, or Bill No" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <div class="table-container">
            <?php if ($credit_bills_result->num_rows > 0): ?>
                <table class="credit-table">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Product</th>
                            <th>IMEI</th>
                            <th>Quantity</th>
                            <th>Date</th>
                            <th>Customer Name</th>
                            <th>Phone Number</th>
                            <th>NIC Number</th>
                            <th>Total Amount</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $credit_bills_result->fetch_assoc()) { ?>
                            <tr>
                                <td class="bill-no"><?php echo htmlspecialchars($row['bill_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['product_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['imei'] ?? ''); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['date'])); ?></td>
                                <td class="customer-name"><?php echo htmlspecialchars($row['customer_name'] ?? ''); ?></td>
                                <td class="phone-no"><?php echo htmlspecialchars($row['phone_no'] ?? ''); ?></td>
                                <td class="nic-no"><?php echo htmlspecialchars($row['nic_no'] ?? ''); ?></td>
                                <td class="total-amount">Rs<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td class="balance">Rs<?php echo number_format($row['balance'], 2); ?></td>
                                <td>
                                    <button class="action-btn" onclick="showPaymentModal('<?php echo htmlspecialchars($row['bill_no']); ?>', <?php echo abs($row['balance']); ?>)">
                                        <i class="fas fa-money-bill-wave"></i> Pay
                                    </button>
                                    <form method="post" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($row['bill_no']); ?>')">
                                        <input type="hidden" name="bill_no" value="<?php echo htmlspecialchars($row['bill_no']); ?>">
                                        <button type="submit" name="delete_bill" class="delete-btn">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">
                    <i class="fas fa-search"></i>
                    <h3>No credit bills found</h3>
                    <p>No credit bills match your search criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePaymentModal()">&times;</span>
            <h3>Update Credit Payment</h3>
            <form method="post" onsubmit="return validatePaymentForm()">
                <div class="form-group">
                    <label for="modal-bill-no">Bill No</label>
                    <input type="text" id="modal-bill-no" name="bill_no" readonly>
                </div>
                <div class="form-group">
                    <label for="modal-balance">Current Balance</label>
                    <input type="text" id="modal-balance" readonly>
                </div>
                <div class="form-group">
                    <label for="payment-amount">Payment Amount (Rs)</label>
                    <input type="number" id="payment-amount" name="payment_amount" step="0.01" min="0" required>
                </div>
                <button type="submit" name="update_payment">Update Payment</button>
            </form>
        </div>
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

        // Search functionality
        document.getElementById('search-input').addEventListener('input', function() {
            window.location.href = '?search=' + encodeURIComponent(this.value);
        });

        document.getElementById('search-input').addEventListener('focus', function() {
            this.select();
        });

        // Confirm delete function - Updated message
        function confirmDelete(billNo) {
            return confirm(`Are you sure you want to delete credit bill ${billNo}? This action cannot be undone. Note: Product stock will NOT be restored as this was a valid sale.`);
        }

        // Modal functions
        function showPaymentModal(billNo, balance) {
            const modal = document.getElementById('paymentModal');
            document.getElementById('modal-bill-no').value = billNo;
            document.getElementById('modal-balance').value = 'Rs' + balance.toFixed(2);
            document.getElementById('payment-amount').setAttribute('max', balance.toFixed(2));
            modal.style.display = 'flex';
        }

        function closePaymentModal() {
            const modal = document.getElementById('paymentModal');
            modal.style.display = 'none';
            document.getElementById('payment-amount').value = '';
        }

        function validatePaymentForm() {
            const paymentAmount = parseFloat(document.getElementById('payment-amount').value);
            const maxAmount = parseFloat(document.getElementById('payment-amount').getAttribute('max'));
            if (paymentAmount <= 0) {
                alert('Payment amount must be greater than zero.');
                return false;
            }
            if (paymentAmount > maxAmount) {
                alert('Payment amount cannot exceed the outstanding balance of Rs' + maxAmount.toFixed(2));
                return false;
            }
            return true;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                closePaymentModal();
            }
        }

        // Add animations to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('.credit-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>