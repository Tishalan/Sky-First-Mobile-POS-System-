<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get bill data from session or URL parameters
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;
$cart_session_key = 'cart_branch_' . $branch_id;

// Get bill details from POST or SESSION
$bill_no = isset($_POST['bill_no']) ? $_POST['bill_no'] : (isset($_GET['bill_no']) ? $_GET['bill_no'] : '');
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : (isset($_GET['payment_method']) ? $_GET['payment_method'] : '');
$paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : (isset($_GET['paid_amount']) ? floatval($_GET['paid_amount']) : 0);
$phone_repairing = isset($_POST['phone_repairing']) ? floatval($_POST['phone_repairing']) : (isset($_GET['phone_repairing']) ? floatval($_GET['phone_repairing']) : 0);
$reload = isset($_POST['reload']) ? floatval($_POST['reload']) : (isset($_GET['reload']) ? floatval($_GET['reload']) : 0);
$customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : (isset($_GET['customer_name']) ? $_GET['customer_name'] : '');
$phone_no = isset($_POST['phone_no']) ? $_POST['phone_no'] : (isset($_GET['phone_no']) ? $_GET['phone_no'] : '');
$nic_no = isset($_POST['nic_no']) ? $_POST['nic_no'] : (isset($_GET['nic_no']) ? $_GET['nic_no'] : '');

// Get cart items from session
$cart_items = isset($_SESSION[$cart_session_key]) ? $_SESSION[$cart_session_key] : [];

// Calculate totals
$subtotal = 0;
$total_discount = 0;
foreach ($cart_items as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
    $total_discount += $item['discount'];
}
$subtotal += $phone_repairing + $reload;
$total = $subtotal - $total_discount;
$balance = $paid_amount - $total;

// Function to format currency
function formatMoney($amount) {
    return 'Rs ' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #<?php echo htmlspecialchars($bill_no); ?> - SKY FIRST MOBILE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .bill-container {
            max-width: 210mm; /* A5 width */
            width: 100%;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .bill-content {
            padding: 20px;
            background: white;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
                display: block;
            }
            
            .bill-container {
                max-width: 100%;
                box-shadow: none;
                border-radius: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                size: A5;
                margin: 10mm;
            }
        }
        
        /* Header Section */
        .bill-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .shop-name {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .shop-details {
            color: #4b5563;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .shop-details p {
            margin: 3px 0;
        }
        
        /* Bill Info Grid */
        .bill-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .info-item {
            font-size: 12px;
        }
        
        .info-label {
            color: #64748b;
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
        }
        
        .info-value {
            color: #0f172a;
            font-weight: 700;
            font-size: 13px;
        }
        
        .payment-badge {
            background: <?php echo $payment_method === 'Credit' ? '#fbbf24' : '#10b981'; ?>;
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Customer Details */
        .customer-section {
            background: #f0f9ff;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }
        
        .customer-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .customer-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            font-size: 12px;
        }
        
        .customer-detail-item span:first-child {
            color: #64748b;
            font-weight: 600;
        }
        
        .customer-detail-item span:last-child {
            color: #0f172a;
            font-weight: 500;
            margin-left: 5px;
        }
        
        /* Products Table */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .products-table th {
            background: #1e293b;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
        }
        
        .products-table th:first-child {
            border-radius: 8px 0 0 0;
        }
        
        .products-table th:last-child {
            border-radius: 0 8px 0 0;
        }
        
        .products-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        .product-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 3px;
        }
        
        .product-details {
            font-size: 10px;
            color: #64748b;
        }
        
        .product-details span {
            display: inline-block;
            margin-right: 8px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Summary Section */
        .summary-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
        }
        
        .summary-row.total {
            border-top: 2px dashed #94a3b8;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 14px;
            font-weight: 700;
        }
        
        .summary-label {
            color: #475569;
        }
        
        .summary-value {
            font-weight: 600;
            color: #0f172a;
        }
        
        .discount-value {
            color: #dc2626;
        }
        
        .balance-positive {
            color: #059669;
        }
        
        .balance-negative {
            color: #dc2626;
        }
        
        .total-amount {
            color: #4f46e5;
            font-size: 16px;
        }
        
        /* Footer */
        .bill-footer {
            text-align: center;
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .thank-you {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .footer-text {
            font-size: 10px;
            color: #94a3b8;
            margin: 3px 0;
        }
        
        .developer-credit {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 11px;
            margin: 10px 0;
        }
        
        .developer-credit span {
            margin-right: 5px;
        }
        
        .sky-logo {
            font-weight: 800;
        }
        
        .sky-logo span {
            color: #ff6b6b;
        }
        
        .terms {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
        }
        
        /* Print Button */
        .print-btn {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .print-container {
            text-align: center;
        }
        
        /* Page Break Prevention */
        .keep-together {
            page-break-inside: avoid;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .bill-info-grid,
            .customer-details-grid {
                grid-template-columns: 1fr;
            }
            
            .products-table {
                font-size: 10px;
            }
            
            .products-table th,
            .products-table td {
                padding: 6px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <div class="bill-content">
            <!-- Header -->
            <div class="bill-header keep-together">
                <div class="shop-name">SKY FIRST MOBILE</div>
                <div class="shop-details">
                    <p>Whole & Retail Dealers in Mobile Phone, Phone Accessories & Mobile Repairing</p>
                    <p>No.42 Kathi Aboopakkar Road, Jaffna</p>
                    <p>📞 0741077750 | 0777377750 | 0703077750</p>
                </div>
            </div>
            
            <!-- Bill Info -->
            <div class="bill-info-grid keep-together">
                <div class="info-item">
                    <span class="info-label">Bill No</span>
                    <span class="info-value"><?php echo htmlspecialchars($bill_no); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date & Time</span>
                    <span class="info-value"><?php echo date('d-m-Y h:i A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <span class="payment-badge"><?php echo htmlspecialchars($payment_method); ?></span>
                </div>
            </div>
            
            <!-- Customer Details (if credit payment) -->
            <?php if ($payment_method === 'Credit' && !empty($customer_name)): ?>
            <div class="customer-section keep-together">
                <div class="customer-title">
                    <span>👤</span> Customer Details
                </div>
                <div class="customer-details-grid">
                    <div class="customer-detail-item">
                        <span>Name:</span>
                        <span><?php echo htmlspecialchars($customer_name); ?></span>
                    </div>
                    <div class="customer-detail-item">
                        <span>Phone:</span>
                        <span><?php echo htmlspecialchars($phone_no) ?: 'N/A'; ?></span>
                    </div>
                    <div class="customer-detail-item">
                        <span>NIC:</span>
                        <span><?php echo htmlspecialchars($nic_no) ?: 'N/A'; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Products Table -->
            <table class="products-table keep-together">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cart_items)): ?>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-details">
                                    <span>Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                    <span>IMEI: <?php echo htmlspecialchars($item['imei']); ?></span>
                                </div>
                            </td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-right"><?php echo formatMoney($item['price']); ?></td>
                            <td class="text-right discount-value">-<?php echo formatMoney($item['discount']); ?></td>
                            <td class="text-right"><?php echo formatMoney($item['subtotal'] - $item['discount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($phone_repairing > 0): ?>
                    <tr>
                        <td>
                            <div class="product-name">Phone Repairing Service</div>
                            <div class="product-details">Service Charge</div>
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right"><?php echo formatMoney($phone_repairing); ?></td>
                        <td class="text-right discount-value">-<?php echo formatMoney(0); ?></td>
                        <td class="text-right"><?php echo formatMoney($phone_repairing); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($reload > 0): ?>
                    <tr>
                        <td>
                            <div class="product-name">Mobile Reload</div>
                            <div class="product-details">Prepaid/Top-up</div>
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right"><?php echo formatMoney($reload); ?></td>
                        <td class="text-right discount-value">-<?php echo formatMoney(0); ?></td>
                        <td class="text-right"><?php echo formatMoney($reload); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Summary -->
            <div class="summary-section keep-together">
                <div class="summary-row">
                    <span class="summary-label">Subtotal:</span>
                    <span class="summary-value"><?php echo formatMoney($subtotal); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Discount:</span>
                    <span class="summary-value discount-value">-<?php echo formatMoney($total_discount); ?></span>
                </div>
                <div class="summary-row total">
                    <span class="summary-label">Total Amount:</span>
                    <span class="total-amount"><?php echo formatMoney($total); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Amount Paid:</span>
                    <span class="summary-value"><?php echo formatMoney($paid_amount); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Balance:</span>
                    <span class="summary-value <?php echo $balance < 0 ? 'balance-negative' : 'balance-positive'; ?>">
                        <?php echo formatMoney($balance); ?>
                        <?php echo $balance < 0 ? ' (Due)' : ''; ?>
                    </span>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bill-footer keep-together">
                <div class="thank-you">Thank you for choosing SKY FIRST MOBILE!</div>
                <div class="footer-text">Your satisfaction is our priority</div>
                
                <div class="developer-credit">
                    <span>Powered by</span>
                    <span class="sky-logo"><span>S</span>KY-<span>T</span>EC</span>
                </div>
                <div class="footer-text">+94 75 090 6065</div>
                
                <div class="terms">
                    <p>* Items once sold cannot be returned or exchanged</p>
                    <p>* This is a computer generated invoice</p>
                </div>
            </div>
        </div>
        
        <!-- Print Button (only visible on screen) -->
        <div class="print-container no-print">
            <button class="print-btn" onclick="window.print()">
                <span>🖨️</span> Print Bill
            </button>
            <button class="print-btn" onclick="window.close()" style="background: #6b7280; margin-left: 10px;">
                <span>✖️</span> Close
            </button>
        </div>
    </div>
    
    <script>
        // Auto print when page loads (optional - uncomment if needed)
        // window.onload = function() {
        //     window.print();
        // };
        
        // Prevent page break inside tables
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tr');
            rows.forEach(row => {
                row.style.pageBreakInside = 'avoid';
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>