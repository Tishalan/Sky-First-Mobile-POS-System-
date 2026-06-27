<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sky_first_mobile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);
$sql = "SELECT * FROM products WHERE product_id = '$product_id'";
$result = $conn->query($sql);
$product = $result->fetch_assoc();
echo json_encode($product);
$conn->close();
?>