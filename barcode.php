<?php
session_start();

// Check if logged in for branch 1 (Main Branch)
if (!isset($_SESSION['admin_main'])) {
    header("Location: login-branch1.php");
    exit();
}



$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set branch_id (for branch 1 admin)
$branch_id = 1; // Change to $_SESSION['branch_id'] after login system is implemented
$_SESSION['branch_id'] = $branch_id;

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Get products from database
$products = [];
$sql = "SELECT * FROM products WHERE branch_id = $branch_id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Generator - SKY FIRST MOBILE Billing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
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

        .barcode-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
            max-width: 400px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary) 0%, #2980b9 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(52, 152, 219, 0.4);
        }

        .barcode-preview {
            width: 300px;
            height: 100px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Libre Barcode 128', cursive;
            font-size: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .barcode-preview.active {
            border-color: var(--success);
            box-shadow: 0 0 10px rgba(46, 204, 113, 0.3);
        }

        .pulse {
            animation: pulse 0.5s ease;
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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

        @media print {
            .header, .nav, .btn {
                display: none !important;
            }
            
            .container {
                padding: 0;
                margin: 0;
            }
            
            .section {
                box-shadow: none;
                border: none;
            }
            
            .barcode-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                width: 100%;
                max-width: 794px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .barcode-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin: 14px 0;
                page-break-inside: avoid;
            }
            
            .barcode {
                font-family: 'Libre Barcode 128';
                font-size: 60px;
                text-align: center;
                height: 60px;
                line-height: 60px;
            }
            
            .barcode-number {
                font-family: 'Arial', sans-serif;
                font-size: 12px;
                text-align: center;
                margin-top: 10px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .nav {
                flex-direction: column;
                align-items: center;
            }
            
            .barcode-preview {
                width: 250px;
                height: 80px;
                font-size: 50px;
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
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="reload_management.php"><i class="fas fa-sim-card"></i> Reload Management</a>
            <a href="barcode.php" class="active"><i class="fas fa-chart-line"></i> Barcode</a>
            <a href="credit_payments.php"><i class="fas fa-credit-card"></i> Credit Payments</a>
    </div>
    
    <div class="section">
        <div class="barcode-container">
            <div class="form-group">
                <label for="barcode-product"><i class="fas fa-box"></i> Select Product</label>
                <select class="form-control" id="barcode-product">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['barcode']); ?>" 
                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-color="<?php echo htmlspecialchars($product['color']); ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['color']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="barcode-quantity"><i class="fas fa-hashtag"></i> Number of Barcodes (Max 20)</label>
                <input type="number" class="form-control" id="barcode-quantity" min="1" max="20" value="1" placeholder="Enter number of barcodes">
            </div>
            
            <div class="barcode-preview" id="barcode-preview">Select a product to preview barcode</div>
            
            <div class="form-group">
                <label for="barcode-number"><i class="fas fa-barcode"></i> Barcode Number</label>
                <input type="text" class="form-control" id="barcode-number" readonly placeholder="Barcode will appear here">
            </div>
            
            <button class="btn btn-primary" id="print-barcode">
                <i class="fas fa-print"></i> Print Barcodes
            </button>
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

        // Barcode Functions
        document.getElementById('barcode-product').addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const barcode = e.target.value;
            const productName = selectedOption.getAttribute('data-name');
            const color = selectedOption.getAttribute('data-color');
            
            document.getElementById('barcode-number').value = barcode;
            
            const preview = document.getElementById('barcode-preview');
            if (barcode) {
                preview.textContent = barcode;
                preview.classList.add('active', 'pulse');
                preview.title = `${productName} (${color})`;
                preview.style.fontFamily = "'Libre Barcode 128', cursive";
                preview.style.fontSize = '60px';
            } else {
                preview.textContent = 'Select a product to preview barcode';
                preview.classList.remove('active');
                preview.style.fontFamily = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
                preview.style.fontSize = '16px';
            }
            
            setTimeout(() => {
                preview.classList.remove('pulse');
            }, 500);
        });

        document.getElementById('print-barcode').addEventListener('click', () => {
            const barcode = document.getElementById('barcode-number').value;
            const quantity = parseInt(document.getElementById('barcode-quantity').value) || 1;
            const selectedOption = document.getElementById('barcode-product').options[document.getElementById('barcode-product').selectedIndex];
            const productName = selectedOption.getAttribute('data-name');
            const color = selectedOption.getAttribute('data-color');
            
            if (!barcode || barcode === 'Select a product to preview barcode') {
                alert('Please select a product!');
                return;
            }
            
            if (quantity < 1 || isNaN(quantity)) {
                alert('Please enter a valid number of barcodes (minimum 1)!');
                document.getElementById('barcode-quantity').style.borderColor = 'var(--accent)';
                setTimeout(() => document.getElementById('barcode-quantity').style.borderColor = '', 1000);
                return;
            }
            
            if (quantity > 20) {
                alert('Maximum 20 barcodes per page! Printing 20 barcodes.');
            }
            
            const numBarcodes = Math.min(quantity, 20);
            const printWindow = window.open('', '_blank', 'height=1123, width=794');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Barcodes - ${productName}</title>
                    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 20px;
                        }
                        .barcode-grid {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            gap: 20px;
                            width: 100%;
                            max-width: 794px;
                            margin: 0 auto;
                        }
                        .barcode-item {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            margin: 14px 0;
                            page-break-inside: avoid;
                            border: 1px solid #ddd;
                            padding: 15px;
                            border-radius: 8px;
                        }
                        .barcode {
                            font-family: "Libre Barcode 128";
                            font-size: 60px;
                            text-align: center;
                            height: 60px;
                            line-height: 60px;
                            margin: 10px 0;
                        }
                        .product-info {
                            font-size: 12px;
                            text-align: center;
                            margin-top: 5px;
                            color: #666;
                        }
                        .barcode-number {
                            font-family: Arial, sans-serif;
                            font-size: 10px;
                            text-align: center;
                            margin-top: 5px;
                            color: #333;
                            font-weight: bold;
                        }
                        @media print {
                            .barcode-item {
                                border: none;
                                padding: 10px;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="barcode-grid">
            `);
            
            for (let i = 0; i < numBarcodes; i++) {
                printWindow.document.write(`
                    <div class="barcode-item">
                        <div class="barcode">${barcode}</div>
                        <div class="product-info">${productName} (${color})</div>
                        <div class="barcode-number">${barcode}</div>
                    </div>
                `);
            }
            
            printWindow.document.write(`
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            // Wait for content to load before printing
            printWindow.onload = function() {
                printWindow.print();
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            };
        });

        // Add animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-group, .barcode-preview, .btn');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Initial setup for preview
            const preview = document.getElementById('barcode-preview');
            preview.style.fontFamily = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
            preview.style.fontSize = '16px';
        });

        // Add input validation
        document.getElementById('barcode-quantity').addEventListener('input', function() {
            if (this.value < 1) {
                this.value = 1;
            }
            if (this.value > 20) {
                this.value = 20;
            }
        });
    </script>
</body>
</html>