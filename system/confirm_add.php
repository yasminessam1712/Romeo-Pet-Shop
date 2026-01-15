<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "root", "petshop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$quantity = $_POST['quantity'];

// ✅ Get product stock
$stmt = $conn->prepare("SELECT units FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$stock = $product['units'];

// ✅ Get existing cart quantity for this product
$cart_stmt = $conn->prepare("
    SELECT quantity 
    FROM shopping_cart 
    WHERE user_id = ? AND product_id = ?
");
$cart_stmt->bind_param("ii", $user_id, $product_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_item = $cart_result->fetch_assoc();

$current_cart_qty = $cart_item ? $cart_item['quantity'] : 0;

// ⚠️ Calculate final quantity after adding more
$final_quantity = $current_cart_qty + $quantity;

// ❌ Prevent exceeding stock
if ($final_quantity > $stock) {
    header("Location: addcart.php?id=$product_id&error=exceed");
    exit();
}

// ✅ Add/update cart because total is valid
$insert = $conn->prepare("
    INSERT INTO shopping_cart (user_id, product_id, quantity)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = ?
");
$insert->bind_param("iiii", $user_id, $product_id, $final_quantity, $final_quantity);
$insert->execute();

header("Location: addcart.php?id=$product_id&added=1");
exit();
?>

