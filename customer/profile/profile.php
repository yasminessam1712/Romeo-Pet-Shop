<?php
session_start();

$loggedIn = isset($_SESSION['user_id']);
// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Connect DB
$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user info
$sql = "SELECT full_name, email, phone_number, address, card_last4, card_expiry, created_at 
        FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Profile - Romeo Pet Shop</title>
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
            min-height: 100vh;
            color: var(--brown);
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 70px);
            padding: 20px;
        }

        .profile-box {
            background-color: #fffaf3;
            padding: 40px 30px;
            border-radius: 16px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .profile-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }

        .profile-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #4b3621;
            font-size: 2rem;
            letter-spacing: 0.5px;
        }

        .profile-info {
            margin-bottom: 18px;
            font-size: 16px;
            color: #5c4b3a;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e0d4c3;
            padding-bottom: 6px;
        }

        .profile-info strong {
            color: #4b3621;
            font-weight: 600;
            width: 130px;
        }

        .btn-edit {
            display: block;
            margin: 25px auto 0 auto;
            padding: 12px 25px;
            background: #d17878;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: background 0.3s ease;
        }

        .btn-edit:hover {
            background: #b76161;
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
                <li><a href="../../system/mainpage.php">Home</a></li>
                <li><a href="../../system/catproducts.php">All Products</a></li>

            </ul>


            <div class="auth-links">
                <?php if ($loggedIn): ?>
                    <a href="../../system/cart.php">Shopping Cart</a>
                    <a href="profile.php">Profile</a>
                    <a href="../../system/order_history.php">Order</a>
                    <a href="#" onclick="openLogoutModal()">Logout</a>
                <?php else: ?>
                    <a href="../credentials/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="content">
        <div class="profile-box">
            <h2>My Profile</h2>
            <div class="profile-info"><strong>Name:</strong> <?= htmlspecialchars($user['full_name']); ?></div>
            <div class="profile-info"><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></div>
            <div class="profile-info"><strong>Phone:</strong> <?= htmlspecialchars($user['phone_number']); ?></div>

            <div class="profile-info"><strong>Member Since:</strong>
                <?= htmlspecialchars(date("F j, Y", strtotime($user['created_at']))); ?></div>

            <a href="../profile/edit_profile.php" class="btn-edit">Edit Profile</a>
        </div>
    </div>
    <link rel="stylesheet" href="footer.css">

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
            window.location.href = "../credentials/logout.php";
        }
    </script>

</body>

</html>