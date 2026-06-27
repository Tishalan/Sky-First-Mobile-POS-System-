<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$branch_id = 1; // Match with inventory.php
$sql = "SELECT MAX(product_id) as max_id FROM products WHERE branch_id = $branch_id";
$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed']);
    exit();
}

$row = $result->fetch_assoc();
$max_id = $row['max_id'];
if ($max_id && preg_match('/^PM(\d+)$/', $max_id, $matches)) {
    $numeric_part = (int)$matches[1] + 1;
    $new_id = "PM" . str_pad($numeric_part, 5, "0", STR_PAD_LEFT);
} else {
    $new_id = "PM00001";
}

// Clear output buffer to prevent stray output
ob_clean();
header('Content-Type: application/json');
echo json_encode(['product_id' => $new_id]);

$conn->close();
?>