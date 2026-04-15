<?php
header('Content-Type: application/json; charset=utf-8');

// Database Connection Settings
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "furryneeds_db";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

// Step 1: Accept 2 Parameters (Requirement: 2 parameters)
$cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : null;
$max_price = isset($_GET['price']) ? $_GET['price'] : 99999;

if ($cat_id === null) {
    echo json_encode(["message" => "Error: Please provide a category ID"]);
    exit;
}

// Step 2: Query the Database
$sql = "SELECT * FROM Product WHERE CategoryID = $cat_id AND Price <= $max_price";
$result = $conn->query($sql);

$response = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        
        // Step 3: Multi-status Logic (Requirement: not just Y/N)
        $qty = $row['StockQuantity'];
        if ($qty == 0) {
            $availability = "Out of Stock";
        } elseif ($qty <= 10) {
            $availability = "Low Stock - Order Soon!";
        } else {
            $availability = "In Stock";
        }

        $response[] = [
            "product_name" => $row['ProductName'],
            "price" => $row['Price'],
            "status" => $availability
        ];
    }
} else {
    $response = ["message" => "No products found in this price range."];
}

// Step 4: Return JSON Response
echo json_encode($response);

$conn->close();
?>
