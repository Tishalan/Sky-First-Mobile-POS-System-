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

$branch_id = 2; // Branch 2

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle adding provider
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_provider'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $sql = "INSERT INTO reload_providers (name, branch_id) VALUES ('$name', $branch_id)";
    $conn->query($sql);
}

// Handle adding purchase
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_purchase'])) {
    $provider_id = intval($_POST['provider_id']);
    $amount = floatval($_POST['amount']);
    $cost = floatval($_POST['cost']);
    $balance = $amount;
    $sql = "INSERT INTO reload_purchases (provider_id, amount, cost, balance) VALUES ($provider_id, $amount, $cost, $balance)";
    $conn->query($sql);
}

// Handle delete purchase
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_purchase'])) {
    $purchase_id = intval($_POST['purchase_id']);
    $sql = "DELETE FROM reload_purchases WHERE id = $purchase_id";
    $conn->query($sql);
}

// Delete purchases with zero balance
$sql = "DELETE FROM reload_purchases WHERE balance = 0";
$conn->query($sql);

// Fetch providers
$providers_sql = "SELECT * FROM reload_providers WHERE branch_id = $branch_id";
$providers_result = $conn->query($providers_sql);

// Fetch purchases and calculate total balance
$providers = [];
if ($providers_result->num_rows > 0) {
    while ($prov = $providers_result->fetch_assoc()) {
        $prov_id = $prov['id'];
        
        // Calculate total balance for this provider
        $balance_sql = "SELECT SUM(balance) as total_balance FROM reload_purchases WHERE provider_id = $prov_id";
        $balance_result = $conn->query($balance_sql);
        $total_balance = $balance_result->fetch_assoc()['total_balance'] ?? 0;
        $prov['total_balance'] = $total_balance;
        
        // Store provider with total balance
        $providers[$prov_id] = $prov;
        
        // Fetch purchases for this provider
        $purchases_sql = "SELECT * FROM reload_purchases WHERE provider_id = $prov_id ORDER BY purchase_date DESC";
        $purchases[$prov_id] = $conn->query($purchases_sql);
    }
    // Reset pointer for later use
    $providers_result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reload Management - SKY FIRST MOBILE Branch 2</title>
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
            border: none;
            cursor: pointer;
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
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .section:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.15), 0 5px 15px rgba(0,0,0,0.07);
        }

        .section h2 {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
            color: var(--primary);
            font-size: 28px;
            position: relative;
        }

        .section h2::after {
            content: '';
            position: absolute;
            bottom: -17px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--secondary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--success) 0%, #27ae60 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(46, 204, 113, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--accent) 0%, #c0392b 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(231, 76, 60, 0.4);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: var(--card-shadow);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        .report-table th,
        .report-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            transition: background 0.3s ease;
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

        .pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            margin-right: 8px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
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
            .report-table th,
            .report-table td {
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
        <a href="billing_b2.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'billing_b2.php' ? 'active' : ''; ?>"><i class="fas fa-cash-register"></i> Billing</a>
        <a href="inventory_b2.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory_b2.php' ? 'active' : ''; ?>"><i class="fas fa-boxes"></i> Inventory</a>
        <a href="inventoryb1_show.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventoryb1_show.php' ? 'active' : ''; ?>"><i class="fas fa-boxes"></i> Inventory B1</a>
        <a href="report_b2.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'report_b2.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="reload_management_b2.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reload_management_b2.php' ? 'active' : ''; ?>"><i class="fas fa-sim-card"></i> Reload Management</a>
        <!--<a href="barcode_b2.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'barcode_b2.php' ? 'active' : ''; ?>"><i class="fas fa-barcode"></i> Barcode</a>-->
        <a href="credit_customers_b2.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'barcode_b2.php' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Credit Customers</a>
    </div>
    
    <div class="section">
        <h2><i class="fas fa-users"></i> Manage Providers</h2>
        <form method="post">
            <div class="form-group">
                <label>Provider Name</label>
                <input type="text" name="name" required>
            </div>
            <button type="submit" name="add_provider" class="btn btn-primary"><i class="fas fa-plus"></i> Add Provider</button>
        </form>
        <table class="report-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Total Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Reset the pointer and loop through providers
                $providers_result->data_seek(0);
                while ($prov = $providers_result->fetch_assoc()): 
                    // Get the total balance from our pre-calculated array
                    $total_balance = isset($providers[$prov['id']]) ? $providers[$prov['id']]['total_balance'] : 0;
                ?>
                <tr>
                    <td><?php echo $prov['id']; ?></td>
                    <td><?php echo htmlspecialchars($prov['name']); ?></td>
                    <td>Rs<?php echo number_format($total_balance, 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <h2><i class="fas fa-shopping-bag"></i> Manage Purchases</h2>
        <form method="post">
            <div class="form-group">
                <label>Provider</label>
                <select name="provider_id" required>
                    <?php 
                    $providers_result->data_seek(0);
                    while ($prov = $providers_result->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Amount (Credit Bought)</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div class="form-group">
                <label>Cost (Paid)</label>
                <input type="number" step="0.01" name="cost" required>
            </div>
            <button type="submit" name="add_purchase" class="btn btn-primary"><i class="fas fa-plus"></i> Add Purchase</button>
        </form>
        
        <?php 
        // Reset pointer and loop through providers to show purchases
        $providers_result->data_seek(0);
        while ($prov = $providers_result->fetch_assoc()): 
            $prov_id = $prov['id'];
            if (isset($purchases[$prov_id])): 
        ?>
        <h3 style="margin-top: 30px; color: var(--primary);">Purchases for <?php echo htmlspecialchars($prov['name']); ?></h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Cost</th>
                    <th>Discount %</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $purchases_result = $purchases[$prov_id];
                while ($purch = $purchases_result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $purch['purchase_date']; ?></td>
                    <td>Rs<?php echo number_format($purch['amount'], 2); ?></td>
                    <td>Rs<?php echo number_format($purch['cost'], 2); ?></td>
                    <td><?php echo number_format((($purch['amount'] - $purch['cost']) / $purch['amount']) * 100, 2); ?>%</td>
                    <td>Rs<?php echo number_format($purch['balance'], 2); ?></td>
                    <td>
                        <form method="post" onsubmit="return confirmDelete(<?php echo $purch['id']; ?>)">
                            <input type="hidden" name="purchase_id" value="<?php echo $purch['id']; ?>">
                            <button type="submit" name="delete_purchase" class="btn btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php 
            endif;
        endwhile; 
        ?>
    </div>
    
    <div class="floating-notification" id="notification">
        Operation completed successfully!
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
        
        // Show notification if operation was successful
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['add_provider']) || isset($_POST['add_purchase']) || isset($_POST['delete_purchase']))): ?>
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

        // Delete confirmation
        function confirmDelete(purchaseId) {
            return confirm('Are you sure you want to delete this purchase? This action cannot be undone.');
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>