<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
if (!$is_logged_in) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details
$user_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Get order ID
$order_id = $_GET['order_id'] ?? 0;

// Fetch order items
$order_stmt = $conn->prepare("
    SELECT oi.quantity, p.name, oi.price 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

// Clear cart
$clear_cart_stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
$clear_cart_stmt->bind_param("i", $user_id);
$clear_cart_stmt->execute();
// Fetch the order total including delivery from orders table
$order_total_stmt = $conn->prepare("SELECT total_amount FROM orders WHERE order_id = ?");
$order_total_stmt->bind_param("i", $order_id);
$order_total_stmt->execute();
$order_total_result = $order_total_stmt->get_result();
$order_total_row = $order_total_result->fetch_assoc();
$total_with_delivery = $order_total_row['total_amount'] ?? 0;


$total_price = 0;
?>
<!DOCTYPE html>
<html>

<head>
    <title>Order Successful - Romeo Pet Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #4b3621;
        }

        /* NAVIGATION */
        nav {
            background-color: #f8f1e4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        nav .logo {
            font-size: 24px;
            font-weight: 600;
            color: #4b3621;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 25px;
        }

        nav ul li a {
            text-decoration: none;
            color: #4b3621;
            font-size: 16px;
            font-weight: bold;
        }

        nav ul li a:hover {
            color: #d17878;
        }

        nav ul li.dropdown {
            position: relative;
        }

        nav ul li .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #f8f1e4;
            top: 100%;
            left: 0;
            padding: 10px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            min-width: 160px;
        }

        nav ul li:hover .dropdown-menu {
            display: block;
        }

        nav ul li .dropdown-menu li {
            padding: 5px 20px;
        }

        .auth-links a {
            margin-left: 15px;
            color: #4b3621;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }

        .auth-links a:hover {
            color: #000;
        }

        /* THANK YOU BOX */
        .thankyou-box {
            max-width: 650px;
            margin: 80px auto;
            background: rgba(255, 250, 243, 0.95);
            backdrop-filter: blur(2px);
            padding: 40px 35px;
            text-align: center;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.25);
            color: #4b3621;
        }

        .thankyou-box h1 {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #d17878;
        }

        .thankyou-box p {
            font-size: 16px;
            margin-bottom: 25px;
        }

        .summary {
            text-align: left;
            margin-top: 25px;
        }

        .summary h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .summary ul {
            padding-left: 20px;
        }

        .summary li {
            margin-bottom: 8px;
            font-size: 15px;
        }

        .total {
            font-size: 18px;
            font-weight: 600;
            margin-top: 15px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 15px 10px 0;
            text-decoration: none;
            background-color: #d17878;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.2s;
        }

        .btn:hover {
            background-color: #b76161;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #4b3621;
        }

        .btn-secondary:hover {
            background-color: #3a2f26;
        }

        /* FOOTER */
        .footer {
            background-color: #4b3621;
            color: #f8f1e4;
            padding: 40px 20px;
            text-align: center;
            margin-top: 50px;
        }

        .footer-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            max-width: 1200px;
            margin: auto;
        }

        .footer-column {
            flex: 1 1 250px;
            margin: 20px;
        }

        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #d17878;
        }

        .footer-column a {
            color: #f8f1e4;
            text-decoration: none;
        }

        .footer-column a:hover {
            color: #d69d6a;
        }

        .footer-bottom {
            margin-top: 30px;
            font-size: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 15px;
            color: #ddd;
        }

        /* LOGOUT MODAL */
        .logout-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .logout-modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .logout-modal-content h3 {
            margin-bottom: 10px;
            font-size: 20px;
            color: #000;
        }

        .logout-modal-content p {
            color: #555;
            margin-bottom: 20px;
        }

        .logout-actions {
            display: flex;
            justify-content: space-between;
        }

        .btn-cancel {
            background: #ccc;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #b3b3b3;
        }

        .btn-logout:hover {
            background: #c0392b;
        }

        @media(max-width:700px) {
            .thankyou-box {
                margin: 50px 15px;
                padding: 30px 20px;
            }

            nav {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <!-- NAVIGATION -->
    <nav>
        <div class="logo">Romeo Pet Shop</div>
        <div style="display:flex; align-items:center;">
            <ul>
                <li><a href="mainpage.php">Home</a></li>
                <li><a href="catproducts.php">All Products</a></li>
            </ul>
            <div class="auth-links">
                <?php if ($is_logged_in): ?>
                    <a href="cart.php">Shopping Cart</a>
                    <a href="../customer/profile/profile.php">Profile</a>
                    <a href="order_history.php">Order</a>
                    <a href="#" onclick="openLogoutModal()">Logout</a>
                <?php else: ?>
                    <a href="../customer/credentials/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- THANK YOU BOX -->
    <div class="thankyou-box">
        <h1>Thank You, <?= htmlspecialchars($user['full_name']); ?>! </h1>
        <p>Your order has been successfully placed.</p>

        <div class="summary">
            <h3>Order Summary</h3>
            <ul>
                <?php while ($item = $order_result->fetch_assoc()):
                    $subtotal = $item['quantity'] * $item['price'];
                    $total_price += $subtotal;
                    ?>
                    <li><?= htmlspecialchars($item['name']); ?> (x<?= $item['quantity']; ?>) — RM
                        <?= number_format($subtotal, 2); ?></li>
                <?php endwhile; ?>
            </ul>
            <div class="total">Total (including delivery): RM <?= number_format($total_with_delivery, 2); ?></div><br>

        </div>

        <a class="btn" href="mainpage.php">Back to Home</a>
        <a class="btn btn-secondary" href="order_history.php">View All Your Orders</a>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>Romeo Pet Shop</h3>
                <p>Your one-stop destination for all your pet needs.<br>From nutritious food to toys, we’ve got
                    everything your cat will love!</p>
            </div>
            <div class="footer-column">
                <h3>Contact Us</h3>
                <p>📍 4, Jalan Diplomatik 2/1, Presint Diplomatik, 62050 Putrajaya, Wilayah Persekutuan Putrajaya</p>
                <p>📞 +60 192838456</p>
                <p>✉️ support@romeopetshop.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            © <?= date("Y") ?> Romeo Pet Shop. All Rights Reserved.
            <br>
            <a href="../staff/credentials/login_staff.php" style="color: #fff; text-decoration: underline;">Staff
                Login</a>
        </div>
    </footer>

    <!-- LOGOUT MODAL -->
    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>Log out</h3>
            <p>Are you sure you want to log out?</p>
            <div class="logout-actions">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-logout" onclick="confirmLogout()">Log out</button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() { document.getElementById("logoutModal").style.display = "flex"; }
        function closeLogoutModal() { document.getElementById("logoutModal").style.display = "none"; }
        function confirmLogout() { window.location.href = "../customer/credentials/logout.php"; }
    </script>

</body>

</html>
<?php $conn->close(); ?>