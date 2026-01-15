<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
// Database connection
$host = 'localhost';
$user = 'root';
$password = 'root';
$dbname = 'petshop';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$is_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? null;

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT p.id, p.name AS product_name, c.name AS category, p.type, p.description, p.full_detail, p.price, p.image_path
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = $product_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Product Detail</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            margin: 0;
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #4b3621;
        }

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

        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 30px;
            background: #fdf8f2;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-detail {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .product-detail img {
            max-width: 400px;
            width: 100%;
            border-radius: 10px;
            object-fit: contain;
        }

        .product-info {
            flex: 1;
        }

        .product-info h2 {
            font-size: 28px;
            color: #4a2d12;
            margin-bottom: 10px;
        }

        .product-info .type {
            font-size: 16px;
            color: #777;
            margin-bottom: 15px;
        }

        .product-info .price {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            margin-bottom: 20px;
        }

        .product-info .short-desc {
            font-size: 15px;
            margin-bottom: 10px;
        }

        .product-info .full-detail {
            font-size: 18px;
            color: #333;
            margin-bottom: 25px;
            line-height: 2.0;
            text-align: justify;
        }

        .btn-cart {
            padding: 12px 20px;
            font-size: 16px;
            background-color: #d17878;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
            margin-left: 170px;
        }

        .btn-cart:hover {
            background-color: #b86565;
        }

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

    <!-- Navigation bar -->
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

    <!-- Product Details -->
    <div class="container">
        <div class="product-detail">
            <img src="../staff/system/<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image">
            <div class="product-info">
                <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                <div class="type">
                    <?php echo htmlspecialchars($product['category']) . " - " . htmlspecialchars($product['type']); ?>
                </div>
                <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                <div class="short-desc"><?php echo htmlspecialchars($product['description']); ?></div>
                <div class="full-detail"><?php echo nl2br(htmlspecialchars($product['full_detail'])); ?></div>

                <?php if ($is_logged_in): ?>
                    <a class="btn-cart" href="addcart.php?id=<?php echo $product['id']; ?>">Add to Cart</a>
                <?php else: ?>
                    <a class="btn-cart" href="../customer/credentials/login.php">Login to Add to Cart</a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // === RELATED PRODUCTS SECTION START ===
        $category_id_sql = "SELECT category_id FROM products WHERE id = $product_id";
        $category_result = $conn->query($category_id_sql);
        $category_id = $category_result->fetch_assoc()['category_id'];

        $related_sql = "SELECT id, name, price, image_path 
                    FROM products 
                    WHERE category_id = $category_id AND id != $product_id 
                    LIMIT 4";
        $related_result = $conn->query($related_sql);

        if ($related_result->num_rows > 0):
            ?>
            <h3 style="margin-top:40px; font-size:22px; color:#4a2d12;">Related Products</h3>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php while ($rel = $related_result->fetch_assoc()): ?>
                    <div
                        style="background: #fff; border-radius: 10px; padding: 15px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <a href="viewproduct.php?id=<?php echo $rel['id']; ?>">
                            <img src="../staff/system/<?php echo htmlspecialchars($rel['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($rel['name']); ?>"
                                style="width:100%; height:180px; object-fit:contain; border-radius: 8px;">
                        </a>
                        <h4 style="font-size:18px; color:#4b3621; margin:10px 0;"><?php echo htmlspecialchars($rel['name']); ?>
                        </h4>
                        <p style="font-weight:bold; color:#000;">RM <?php echo number_format($rel['price'], 2); ?></p>
                        <a href="viewproduct.php?id=<?php echo $rel['id']; ?>"
                            style="display:inline-block; padding:10px 15px; background:#d17878; color:white; border-radius:6px; text-decoration:none;">
                            View Product
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        <!-- === RELATED PRODUCTS SECTION END === -->

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
<?php $conn->close(); ?>