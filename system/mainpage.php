<?php
session_start(); // Start session

$loggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Romeo Pet Shop</title>
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

        .content {
            height: calc(100% - 70px);
            display: flex;
            align-items: center;
            padding: 0 60px;
        }

        .welcome-box {
            background-color: #fffaf3;
            padding: 40px 50px;
            border-radius: 12px;
            max-width: 600px;
            transition: background-color 0.3s ease;
        }

        .welcome-box h1 {
            font-size: 38px;
            margin-bottom: 20px;
            color: #4b3621;
        }

        .welcome-box p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #5c4b3a;
        }

        .btn-group {
            display: flex;
            gap: 20px;
        }

        .btn {
            text-decoration: none;
            padding: 12px 22px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-shop {
            background-color: #d17878;
            color: white;
        }

        .btn-shop:hover {
            background-color: #b76161;
        }

        .btn-browse {
            background-color: #d69d6a;
            color: white;
        }

        .btn-browse:hover {
            background-color: #b88456;
        }

        body.dark-mode {
            background-color: #1c1c1c;
        }

        body.dark-mode nav {
            background-color: #2b2b2b;
        }

        body.dark-mode nav .logo,
        body.dark-mode nav ul li a,
        body.dark-mode .auth-links a {
            color: #f1f1f1;
        }

        body.dark-mode .welcome-box {
            background-color: #2b2b2b;
        }

        body.dark-mode .welcome-box h1,
        body.dark-mode .welcome-box p {
            color: #e0e0e0;
        }

        body.dark-mode .btn-shop {
            background-color: #c76f6f;
        }

        body.dark-mode .btn-shop:hover {
            background-color: #a85a5a;
        }

        body.dark-mode .btn-browse {
            background-color: #c58a5f;
        }

        body.dark-mode .btn-browse:hover {
            background-color: #a4714d;
        }

        .toggle-dark:hover {
            background: #d4c5b0;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
                justify-content: center;
            }

            .welcome-box {
                max-width: 100%;
                text-align: center;
            }

            .btn-group {
                flex-direction: column;
                gap: 12px;
                align-items: center;
            }
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

    <div class="content">
        <div class="welcome-box">
            <h1>Welcome to<br>Romeo Pet Shop</h1>
            <p>Your one-stop destination for all things feline. From nutritious food to exciting toys, we’ve got
                everything your cat will love!</p>
            <div class="btn-group">
                <a href="catproducts.php" class="btn btn-shop">Shop Cat Products</a>
                <a href="allproducts.php" class="btn btn-browse">Browse Category</a>
            </div>
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