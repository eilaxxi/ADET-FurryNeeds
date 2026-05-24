<?php
$conn = new mysqli("localhost", "root", "", "furryneeds_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_name = $_POST['product_name'];

$imageName = $_FILES['image']['name'];
$tempName = $_FILES['image']['tmp_name'];

$folder = "photo/" . $imageName;

move_uploaded_file($tempName, $folder);

$sql = "INSERT INTO products (product_name, image)
        VALUES ('$product_name', '$folder')";

if ($conn->query($sql) === TRUE) {
    echo "Product saved!";
} else {
    echo "Error: " . $conn->error;
}
?>