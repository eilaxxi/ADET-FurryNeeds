<?php
header("Content-Type: application/json");
require_once "db.php";

$action = $_GET["action"] ?? "";

if ($action === "product") {
    $id = isset($_GET["id"]) ? intval($_GET["id"]) : 1;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.description,
                p.price,
                p.stock_quantity,
                p.image,
                p.ingredients,
                c.category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            echo json_encode([
                "success" => true,
                "product" => $product
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Product not found"
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Query failed: " . $e->getMessage()
        ]);
    }

    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Invalid action"
]);
?>