<?php
session_start();

if (!isset($_SESSION['admin_branch2'])) {
    header("Location: login-branch2.php");
    exit();
}

// Database connection
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set branch_id (for branch 1 admin, hardcoded for now; use session after login implementation)
$branch_id = 1; // Change to $_SESSION['branch_id'] after login system is implemented
$_SESSION['branch_id'] = $branch_id;

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
        echo '<td>' . htmlspecialchars($row['product_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
        echo '<td>Rs' . number_format($row['sale_price'], 2) . '</td>';
        echo '<td>' . $row['stock'] . '</td>';
        $status_class = $row['stock'] == 0 ? 'status-out' : ($row['stock'] < 10 ? 'status-low' : 'status-in');
        echo '<td class="' . $status_class . '"><span class="pulse"></span>' . getStatus($row['stock']) . '</td>';
        // echo '<td>' . htmlspecialchars($row['barcode']) . '</td>';
        echo '<td>' . htmlspecialchars($row['color']) . '</td>';
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

// Stock status function
function getStatus($stock) {
    if ($stock == 0) return 'Out of Stock';
    if ($stock < 10) return 'Low Stock'; // Threshold as per design
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

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: var(--card-shadow);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            transition: background 0.3s ease;
        }

        .inventory-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .inventory-table tbody tr:hover {
            background: rgba(52, 152, 219, 0.1);
            transform: scale(1.01);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .status-in {
            color: var(--success);
            font-weight: bold;
        }

        .status-low {
            color: var(--warning);
            font-weight: bold;
        }

        .status-out {
            color: var(--accent);
            font-weight: bold;
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

        @media (max-width: 1024px) {
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
        <a href="inventoryb1_show.php" class="active"><i class="fas fa-boxes"></i> Inventory B1</a>
        <a href="report_b2.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="reload_management_b2.php"><i class="fas fa-sim-card"></i> Reload Management</a>
        <!--<a href="barcode_b2.php"><i class="fas fa-barcode"></i> Barcode</a>-->
        <a href="credit_customers_b2.php"><i class="fas fa-credit-card"></i> Credit Customers</a>
    </div>

    <div class="section">
        <div class="inventory-header">
            <h2><i class="fas fa-box"></i> Inventory Management</h2>
        </div>

        <div class="search-bar">
            <label><i class="fas fa-search"></i> Search Products</label>
            <i class="fas fa-barcode"></i>
            <input type="text" id="search-input" placeholder="Search by Product ID, Name, Barcode, or Color" value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div style="overflow-x: auto;">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Sale Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <!-- <th>Barcode</th> -->
                        <th>Color</th>
                        <th>Photo</th>
                        <th>Sold</th>
                        <th>Last Sold</th>
                    </tr>
                </thead>
                <tbody id="inventory-tbody">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td>Rs<?php echo number_format($row['sale_price'], 2); ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td class="<?php echo $row['stock'] == 0 ? 'status-out' : ($row['stock'] < 10 ? 'status-low' : 'status-in'); ?>">
                            <span class="pulse"></span><?php echo getStatus($row['stock']); ?>
                        </td>
                        <!-- <td><?php echo htmlspecialchars($row['barcode']); ?></td> -->
                        <td><?php echo htmlspecialchars($row['color']); ?></td>
                        <td><?php if ($row['photo']) { ?><img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" width="50"><?php } else { echo 'No Photo'; } ?></td>
                        <td><?php echo $row['sold']; ?></td>
                        <td><?php echo $row['last_sold'] ? date('Y-m-d H:i:s', strtotime($row['last_sold'])) : 'Never'; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
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
        
        // AJAX Search with debounce on input
        const searchInput = document.getElementById('search-input');
        let timeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchValue = this.value;
                fetch(`inventoryb1_show.php?ajax_search=1&search=${encodeURIComponent(searchValue)}`)
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
                fetch(`inventoryb1_show.php?ajax_search=1&search=${encodeURIComponent(searchValue)}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('inventory-tbody').innerHTML = data;
                    })
                    .catch(error => console.error('Error:', error));
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