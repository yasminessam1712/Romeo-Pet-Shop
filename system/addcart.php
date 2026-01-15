<?php
session_start();

$loggedIn = isset($_SESSION['user_id']);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "root", "petshop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    echo "Invalid product ID.";
    exit;
}

$stmt = $conn->prepare("SELECT p.id, p.name AS product_name, c.name AS category, p.type, p.description, p.full_detail, p.price, p.image_path
                        FROM products p
                        JOIN categories c ON p.category_id = c.id
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "Product not found.";
    exit;
}

$is_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? null;
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add to Cart - Romeo Pet Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #fff9f0;
            color: #333;
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #4b3621;
        }

        /* NAVBAR */
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

        /* PRODUCT PAGE */
        .product-container {
            max-width: 1100px;
            margin: 60px auto;
            display: flex;
            gap: 40px;
            background-color: #fff;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        }

        .product-container img {
            width: 380px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .product-details {
            flex: 1;
            padding-right: 20px;
        }

        .product-details h2 {
            font-size: 28px;
            color: #4b3621;
            margin-bottom: 12px;
        }

        .product-details p {
            font-size: 16px;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .product-details strong {
            color: #4b3621;
        }

        input[type="number"] {
            width: 80px;
            padding: 7px;
            font-size: 16px;
            margin-right: 10px;
        }

        button {
            background-color: #d17878;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover {
            background-color: #c47800;
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

        /* MOBILE RESPONSIVE */
        @media (max-width: 900px) {
            .product-container {
                flex-direction: column;
                text-align: center;
            }

            .product-container img {
                width: 90%;
                margin: 0 auto 20px;
            }

            .auth-links a {
                margin-left: 10px;
            }
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
        <div style="display: flex; align-items: center;">
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

    <div class="product-container">
        <img src="../staff/system/<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image">

        <div class="product-details">
            <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>

            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?>
                (<?php echo htmlspecialchars($product['type']); ?>)</p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
            <p style="text-align: justify;">
                <strong><?php echo htmlspecialchars($product['full_detail'] ?? 'N/A'); ?></strong>
            </p>

            <p><strong>Price:</strong> RM <?php echo number_format($product['price'], 2); ?></p>

            <form method="post" action="confirm_add.php">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                <label for="quantity"><strong>Quantity:</strong></label>
                <input type="number" name="quantity" value="1" min="1" required>

                <button type="submit">Confirm Add to Cart</button>
            </form>
        </div>
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
                <p>📍 4, Jalan Diplomatik 2/1, Presint Diplomatik, 62050 Putrajaya, Wilayah Persekutuan Putrajaya</p>
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


    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart',
                text: 'Your item has been added to the cart!',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'stock'): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Not enough stock!',
                text: 'The quantity you requested is more than the available stock.',
                timer: 2500,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'exceed'): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Not enough stock!',
                text: 'You already have items in your cart. Adding more will exceed available stock.',
                timer: 2500,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>
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