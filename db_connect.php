<?php
$host = 'localhost';
$username = 'root'; // Default WAMP MySQL username
$password = ''; // Default WAMP MySQL password (empty by default)
$dbname = 'sky_mobile';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>