<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/credentials/login.php");
    exit();
}
$loggedIn = isset($_SESSION['user_id']);


$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $product_id = $_POST['product_id'];
    $quantity = max(1, intval($_POST['quantity']));
    $stmt = $conn->prepare("UPDATE shopping_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $product_id);
    $stmt->execute();
}

// Handle item removal
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
}

// Fetch cart items with product info
$stmt = $conn->prepare("SELECT sc.product_id, sc.quantity, p.name AS product_name, p.price, p.image_path 
                        FROM shopping_cart sc 
                        JOIN products p ON sc.product_id = p.id 
                        WHERE sc.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Shopping Cart - Romeo Pet Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #4b3621;
        }

        /* NAV */
        nav {
            background-color: #f8f1e4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }

        nav .logo {
            font-size: 24px;
            font-weight: 600;
            color: #4b3621;
            display: flex;
            align-items: center;
        }

        nav .logo span {
            font-size: 28px;
            margin-right: 10px;
            animation: pawBounce 1.2s infinite alternate;
        }

        @keyframes pawBounce {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-5px);
            }
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
            transition: color 0.3s;
            font-weight: bold;
        }

        nav ul li a:hover {
            color: #d17878;
        }

        /* Dropdown setup */
        nav ul li.dropdown {
            position: relative;
        }

        nav ul li .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #f8f1e4;
            padding: 10px 0;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            min-width: 160px;
            z-index: 100;
        }

        nav ul li .dropdown-menu li {
            display: block;
            padding: 5px 20px;
        }

        nav ul li .dropdown-menu li a {
            display: block;
            color: #4b3621;
            text-decoration: none;
        }

        nav ul li .dropdown-menu li a:hover {
            background-color: #e7dbca;
            color: #d17878;
        }

        /* Show dropdown on hover */
        nav ul li.dropdown:hover .dropdown-menu {
            display: block;
        }

        .auth-links a {
            margin-left: 15px;
            color: #4b3621;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }

        .auth-links a:hover {
            color: #d17878;
        }

        /* Page container */
        .container {
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 24px;
            background: rgba(255, 250, 244, 0.9);
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-bottom: 20px;
            font-size: 26px;
            color: #4b3621;
        }

        /* Cart item */
        .cart-item {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
            padding: 14px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #eee1d6;
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-details h4 {
            font-size: 16px;
            font-weight: 600;
            color: #4b3621;
            margin-bottom: 6px;
        }

        .item-details p {
            font-size: 14px;
            color: #7b6a58;
            margin-bottom: 4px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        @media(min-width:600px) {
            .actions {
                flex-direction: row;
                align-items: center;
            }
        }

        input[type=number] {
            width: 60px;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        /* Buttons */
        .update-btn {
            background: #6c9a8b;
            border: none;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .update-btn:hover {
            background: #5a8578;
        }

        .remove-btn {
            background: transparent;
            color: #d17878;
            border: 1px solid #d17878;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.25s ease;
        }

        .remove-btn:hover {
            background: #d17878;
            color: #fff;
        }

        .checkout-container {
            text-align: right;
            margin-top: 20px;
        }

        .checkout-btn {
            background: #d17878;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .checkout-btn:hover {
            background: #c94a4a;
        }

        /* Footer */
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

        .footer-column p,
        .footer-column a {
            font-size: 15px;
            color: #f8f1e4;
            text-decoration: none;
            line-height: 1.8;
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

        .page-desc {
            font-size: 15px;
            color: #7b6a58;
            margin-bottom: 30px;
            line-height: 1.6;
        }

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
    </style>
</head>

<body>

    <nav>
        <div class="logo">Romeo Pet Shop</div>
        <div style="display:flex; align-items:center;">
            <ul>
                <li><a href="mainpage.php">Home</a></li>
                <li><a href="catproducts.php">All Products</a></li>
            </ul>
            <div class="auth-links">
                <?php if ($loggedIn): ?>
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

    <div class="container">
        <h2>Your Shopping Cart</h2>
        <p class="page-desc">
            Review the items in your cart before proceeding to checkout.
            You can update quantities or remove items anytime.
        </p>


        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="cart-item">
                    <img src="../staff/system/<?php echo htmlspecialchars($row['image_path']); ?>" alt="Product Image">
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                        <p>Price: RM <?php echo number_format($row['price'], 2); ?></p>
                        <p>Total: RM <?php echo number_format($row['price'] * $row['quantity'], 2); ?></p>
                    </div>
                    <div class="actions">
                        <form method="post" style="display:flex; gap:10px;">
                            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" min="1">
                            <button type="submit" name="update" class="update-btn">Update</button>
                            <a href="cart.php?remove=<?php echo $row['product_id']; ?>" class="remove-btn">Remove</a>
                        </form>

                    </div>
                </div>
            <?php endwhile; ?>

            <div class="checkout-container">
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <p style="text-align:center; font-size:18px; padding:30px 0;">Your cart is empty.</p>
        <?php endif; ?>
    </div>


    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>Romeo Pet Shop</h3>
                <p>Your one-stop destination for all your pet needs.
                    <br>From nutritious food to toys, we’ve got <br>everything your cat will love!
                </p>
            </div>

            <div class="footer-column">
                <h3>Contact Us</h3>
                <p>📍 19 Jalan Cendana P14/7, Malaysia</p>
                <p>📞 +60 192838456</p>
                <p>✉️ support@romeopetshop.com</p>
            </div>


        </div>

        <div class="footer-bottom">
            © <?= date("Y") ?> Romeo Pet Shop. All Rights Reserved.
            <br>
            <a href="../staff/credentials/login_staff.php" style="color: #fff; text-decoration: underline;">
                Staff Login
            </a>
        </div>
    </footer>
    <script>
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This item will be removed from your cart!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });
    </script>
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
        function openLogoutModal() {
            document.getElementById("logoutModal").style.display = "flex";
        }

        function closeLogoutModal() {
            document.getElementById("logoutModal").style.display = "none";
        }

        function confirmLogout() {
            window.location.href = "../customer/credentials/logout.php";
        }
    </script>
</body>

</html>