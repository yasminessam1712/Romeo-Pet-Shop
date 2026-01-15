<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);

/* ================= DATABASE ================= */
$conn = new mysqli("localhost", "root", "root", "petshop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$is_logged_in = isset($_SESSION['user_id']);

$category_filter = isset($_GET['category']) && is_numeric($_GET['category'])
    ? (int) $_GET['category']
    : null;

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

/* ================= QUERY ================= */
$sql = "
SELECT 
    p.id,
    p.name AS product_name,
    c.name AS category,
    p.type,
    p.description,
    p.price,
    p.image_path,
    p.units
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE 1=1
";

if ($category_filter) {
    $sql .= " AND p.category_id = $category_filter";
}

if ($search !== "") {
    $safe = $conn->real_escape_string($search);
    $sql .= " AND (
        p.name LIKE '%$safe%' 
        OR p.description LIKE '%$safe%' 
        OR p.type LIKE '%$safe%'
    )";
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>

<head>
    <title>All Cat Products - Romeo Pet Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center fixed;
            background-size: cover;
        }

        /* ================= NAV ================= */
        nav {
            background: #f8f1e4;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

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


        /* ================= CONTAINER ================= */
        .container {
            max-width: 1350px;
            margin: 60px auto;
            padding: 40px 35px;

            background: rgba(255, 250, 244, 0.9);
            backdrop-filter: blur(4px);

            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .container h2 {
            text-align: center;
            font-size: 2.5rem;
            color: #333;
        }

        .subtitle {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 30px;
            color: #666;
        }

        /* ================= FILTER ================= */
        .filter-form {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }

        .search-input,
        .filter-form select {
            padding: 10px 14px;
            font-size: 14px;
            border-radius: 8px;
            border: 2px solid #ccc;
        }

        .search-input {
            width: 260px;
        }

        .search-input:focus,
        .filter-form select:focus {
            outline: none;
            border-color: #d17878;
        }

        .search-btn {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            background: #d17878;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .search-btn:hover {
            background: #b85d5d;
        }

        /* ================= GRID ================= */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 350px);
            gap: 30px;
            justify-content: center;
        }

        /* ================= CARD ================= */
        .product-card {
            background: #fdfdfd;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            padding: 15px;
            display: flex;
            flex-direction: column;
        }

        .image-wrapper {
            height: 200px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            border-radius: 10px;
        }

        .image-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .card-content {
            background: #f5f5f5;
            border-radius: 10px;
            padding: 12px;
            margin-top: 10px;
            flex-grow: 1;
        }

        .product-name {
            font-weight: 700;
            font-size: 18px;
            color: #333;
        }

        .product-type {
            font-size: 13px;
            color: #777;
            margin-bottom: 6px;
        }

        .product-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }

        .product-price {
            margin-top: 8px;
            font-weight: bold;
        }

        /* ================= ACTIONS ================= */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .action-buttons a {
            padding: 8px 12px;
            background: #d17878;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .action-buttons a:hover {
            background: #b85d5d;
        }

        /* ================= FOOTER ================= */
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

    <div class="container">
        <h2>Romeo's Cat Products</h2>
        <p class="subtitle">Browse our quality products for your feline friend</p>

        <form method="get" class="filter-form">
            <input type="text" name="search" class="search-input" placeholder="Search products..."
                value="<?= htmlspecialchars($search) ?>">

            <select name="category">
                <option value="">All Categories</option>
                <?php
                $cats = $conn->query("SELECT id, name FROM categories");
                while ($c = $cats->fetch_assoc()):
                    ?>
                    <option value="<?= $c['id'] ?>" <?= ($category_filter == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button class="search-btn">Search</button>
        </form>

        <div class="products-grid">
            <?php if ($result->num_rows): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="image-wrapper">
                            <img src="../staff/system/<?= htmlspecialchars($row['image_path']) ?>">
                        </div>

                        <div class="card-content">
                            <div class="product-name"><?= htmlspecialchars($row['product_name']) ?></div>
                            <div class="product-type"><?= htmlspecialchars($row['category']) ?> •
                                <?= htmlspecialchars($row['type']) ?></div>
                            <div class="product-desc"><?= htmlspecialchars($row['description']) ?></div>
                            <div class="product-price">RM <?= number_format($row['price'], 2) ?></div>

                            <?php if ($row['units'] <= 0): ?>
                                <div style="color:red;font-weight:bold;">❌ Out of Stock</div>
                            <?php elseif ($row['units'] < 10): ?>
                                <div style="color:#c48a00;font-weight:bold;">⚠️ Only <?= $row['units'] ?> left</div>
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($row['units'] > 0): ?>
                                <a href="addcart.php?id=<?= $row['id'] ?>">Add to Cart</a>
                            <?php else: ?>
                                <a style="background:#999;pointer-events:none;">Unavailable</a>
                            <?php endif; ?>
                            <a href="viewproduct.php?id=<?= $row['id'] ?>">View Product</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
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