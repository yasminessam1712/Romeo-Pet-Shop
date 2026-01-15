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

// Fetch categories
$sql = "SELECT id, name FROM categories ORDER BY id ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Categories - Romeo Pet Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            transition: background-color 0.4s ease;
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

        .container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 30px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .category-card {
            background: #fdf8f2;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            padding: 20px;
            text-decoration: none;
            color: #4a2d12;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .category-card img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .category-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .category-card p {
            font-size: 14px;
            color: #6f4e37;
            line-height: 1.4;
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

    <!-- Categories -->
    <?php
    // Connect to database
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "petshop";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch categories
    $sql = "SELECT * FROM categories";
    $result = $conn->query($sql);
    ?>

    <div class="container">
        <div class="categories-grid">
            <?php
            $cat_result = $conn->query("SELECT id, name FROM categories WHERE id = 1");

            if ($cat_result && $cat_result->num_rows > 0) {
                while ($cat_row = $cat_result->fetch_assoc()):
                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['id']) ? 'selected' : '';
                    ?>
                    <a href="catproducts.php?category=<?= $cat_row['id'] ?>" class="category-card <?= $selected ?>">

                        <img src="../pictures/cat_food.jpg" alt="<?= htmlspecialchars($cat_row['name']) ?>">
                        <h3><?= htmlspecialchars($cat_row['name']) ?></h3>
                        <p>Nutritious and delicious meals to keep your cat healthy and happy.</p>
                    </a>
                    <?php
                endwhile;
            } else {
                echo "No category found.";
            }
            ?>

            <?php
            $cat_result = $conn->query("SELECT id, name FROM categories WHERE id = 2");

            if ($cat_result && $cat_result->num_rows > 0) {
                while ($cat_row = $cat_result->fetch_assoc()):
                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['id']) ? 'selected' : '';
                    ?>
                    <a href="catproducts.php?category=<?= $cat_row['id'] ?>" class="category-card <?= $selected ?>">
                        <img src="../pictures/cat_toys.jpg" alt="<?= htmlspecialchars($cat_row['name']) ?>">
                        <h3><?= htmlspecialchars($cat_row['name']) ?></h3>
                        <p>Fun and engaging toys for endless hours of playtime.</p>
                    </a>
                    <?php
                endwhile;
            } else {
                echo "No category found.";
            }
            ?>

            <?php
            $cat_result = $conn->query("SELECT id, name FROM categories WHERE id = 3");

            if ($cat_result && $cat_result->num_rows > 0) {
                while ($cat_row = $cat_result->fetch_assoc()):
                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['id']) ? 'selected' : '';
                    ?>
                    <a href="catproducts.php?category=<?= $cat_row['id'] ?>" class="category-card <?= $selected ?>">
                        <img src="../pictures/accessories.jpg" alt="<?= htmlspecialchars($cat_row['name']) ?>">
                        <h3><?= htmlspecialchars($cat_row['name']) ?></h3>
                        <p>Stylish and functional items for your furry friend.</p>
                    </a>
                    <?php
                endwhile;
            } else {
                echo "No category found.";
            }
            ?>

            <?php
            $cat_result = $conn->query("SELECT id, name FROM categories WHERE id = 4");

            if ($cat_result && $cat_result->num_rows > 0) {
                while ($cat_row = $cat_result->fetch_assoc()):
                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['id']) ? 'selected' : '';
                    ?>
                    <a href="catproducts.php?category=<?= $cat_row['id'] ?>" class="category-card <?= $selected ?>">
                        <img src="../pictures/health.jpg" alt="<?= htmlspecialchars($cat_row['name']) ?>">
                        <h3><?= htmlspecialchars($cat_row['name']) ?></h3>
                        <p>Supplements, care items, and everything for a healthy cat.</p>
                    </a>
                    <?php
                endwhile;
            } else {
                echo "No category found.";
            }
            ?>

            <?php
            $cat_result = $conn->query("SELECT id, name FROM categories WHERE id = 5");

            if ($cat_result && $cat_result->num_rows > 0) {
                while ($cat_row = $cat_result->fetch_assoc()):
                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat_row['id']) ? 'selected' : '';
                    ?>
                    <a href="catproducts.php?category=<?= $cat_row['id'] ?>" class="category-card <?= $selected ?>">
                        <img src="../pictures/grooming.jpg" alt="<?= htmlspecialchars($cat_row['name']) ?>">
                        <h3><?= htmlspecialchars($cat_row['name']) ?></h3>
                        <p>Everything you need to keep your cat clean and well-groomed.</p>
                    </a>
                    <?php
                endwhile;
            } else {
                echo "No category found.";
            }
            ?>
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