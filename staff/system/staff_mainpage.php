<?php
session_start(); // Start session

$loggedIn = isset($_SESSION['user_id']);

$loggedIn = isset($_SESSION['user_id']);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../credentials/login_staff.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Romeo Pet Shop Staff</title>
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
            background: url('../../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
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
        <div class="logo">Romeo Pet Shop Staff</div>
        <div style="display: flex; align-items: center;">
            <ul>
                <li><a href="staff_mainpage.php">Home</a></li>
                <li><a href="staff_orders.php">Orders</a></li>
                <li><a href="staff_reports.php">Report</a></li>
                <li><a href="add_products.php">Products</a></li>
                <li><a href="#" onclick="openLogoutModal()">Logout</a></li>

            </ul>
    </nav>


    <div class="content">
        <div class="welcome-box">
            <h1>Welcome to<br>Romeo Pet Shop Staff Dashboard</h1>
            <p>Manage orders, track sales, update products, and generate reports efficiently—all in one place.</p>

            <div class="btn-group">
                <a href="add_products.php" class="btn btn-shop">Manage Products</a>
                <a href="staff_orders.php" class="btn btn-browse">Manage Orders</a>
            </div>
        </div>
    </div>

    <footer class="footer" style="background-color:#3b2e23; color:#f4ede3; padding:40px 20px; margin-top:60px;">

        <div class="footer-container"
            style="max-width:1200px; margin:auto; display:flex; justify-content:space-between; gap:40px; flex-wrap:wrap;">

            <!-- Left Column -->
            <div class="footer-column" style="flex:1 1 300px;">
                <h3 style="color:#e0a899; font-size:20px; margin-bottom:10px;">Romeo Pet Shop — Staff Portal</h3>
                <p style="font-size:14px; opacity:0.9; line-height:1.6;">
                    Internal dashboard for staff to manage inventory, orders, analytics and product listings.
                </p>
            </div>

            <!-- Right Column -->
            <div class="footer-column" style="flex:1 1 220px;">
                <h3 style="color:#e0a899; font-size:20px; margin-bottom:10px;">Support</h3>
                <p style="font-size:14px; opacity:0.9;">📍 4, Jalan Diplomatik 2/1, Presint Diplomatik, 62050 Putrajaya,
                    Wilayah Persekutuan Putrajaya</p>
                <p style="font-size:14px; opacity:0.9;">📞 +60 192838456</p>
                <p style="font-size:14px; opacity:0.9;">✉️ support@romeopetshop.com</p>
            </div>

        </div>

        <div class="footer-bottom"
            style="margin-top:30px; font-size:13px; text-align:center; border-top:1px solid rgba(255,255,255,0.15); padding-top:12px;">

            © <?= date("Y") ?> Romeo Pet Shop — Staff System. All Rights Reserved.
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
            window.location.href = "../credentials/logout_staff.php";
        }
    </script>

</body>

</html>