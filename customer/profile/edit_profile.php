<?php
session_start();

$loggedIn = isset($_SESSION['user_id']);
if (!$loggedIn) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// DB Connection
$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

// Fetch user info
$sql = "SELECT full_name, email, phone_number, address, password FROM users WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- VALIDATIONS ---
    if (empty($full_name))
        $errors[] = "Full Name cannot be empty.";
    if (empty($email))
        $errors[] = "Email cannot be empty.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format.";
    if (empty($phone))
        $errors[] = "Phone number cannot be empty.";
    elseif (!preg_match("/^(01)[0-9]{8,9}$/", $phone))
        $errors[] = "Invalid Malaysian phone number (e.g., 0192839281).";


    // --- Check duplicates ---
    $dupStmt = $conn->prepare("SELECT user_id FROM users WHERE (email=? OR phone_number=?) AND user_id<>?");
    $dupStmt->bind_param("ssi", $email, $phone, $user_id);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();
    if ($dupResult->num_rows > 0)
        $errors[] = "Email or phone number is already used by another account.";
    $dupStmt->close();

    // --- PASSWORD LOGIC ---
    $passwordUpdate = false;
    if ($current_password) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (!$new_password && !$confirm_password) {
            $errors[] = "No new password entered. Please enter a new password to update.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $new_password)) {
            $errors[] = "New password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
        } else {
            $passwordUpdate = true;
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    } elseif ($new_password || $confirm_password) {
        $errors[] = "To change password, you must enter your current password.";
    }

    // --- CHECK IF ANYTHING CHANGED ---
    $nothingChanged = (
        $full_name === $user['full_name'] &&
        $email === $user['email'] &&
        $phone === $user['phone_number'] &&

        !$passwordUpdate
    );

    if (empty($errors) && $nothingChanged) {
        $success = "No changes were made.";
    }

    // --- UPDATE DB ONLY IF CHANGED ---
    if (empty($errors) && !$nothingChanged) {
        $updateFields = "full_name=?, email=?, phone_number=?";
        $params = [$full_name, $email, $phone,];
        $types = "sss";

        if ($passwordUpdate) {
            $updateFields .= ", password=?";
            $params[] = $hashed_password;
            $types .= "s";
        }

        $params[] = $user_id;
        $types .= "i";

        $sqlUpdate = "UPDATE users SET $updateFields WHERE user_id=?";
        $stmt = $conn->prepare($sqlUpdate);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        $success = "Profile updated successfully!";
        // refresh $user
        $stmt = $conn->prepare("SELECT full_name, email, phone_number,  password FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Profile - Romeo Pet Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('../../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
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
        }

        .profile-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #4b3621;
            font-size: 2rem;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .profile-form label {
            font-weight: 600;
            color: #4b3621;
        }

        .profile-form input {
            padding: 10px;
            border: 2px solid #4b3621;
            border-radius: 8px;
            background: #fff8f0;
        }

        .profile-form input:focus {
            outline: none;
            border-color: #d17878;
            box-shadow: 0 0 5px rgba(209, 120, 120, 0.5);
        }

        .btn-save {
            padding: 12px;
            background: #d17878;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-save:hover {
            background: #b76161;
        }

        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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

        #logoutModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        #logoutModal .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
        }

        #logoutModal .modal-content button {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        #logoutModal .btn-cancel {
            background: #ccc;
        }

        #logoutModal .btn-cancel:hover {
            background: #b3b3b3;
        }

        #logoutModal .btn-logout {
            background: #e74c3c;
            color: #fff;
        }

        #logoutModal .btn-logout:hover {
            background: #c0392b;
        }

        .message.error ul {
            list-style: none;
            /* removes the dot */
            padding-left: 0;
            /* removes extra indentation */
            margin: 0;
        }

        .message.error ul li {
            margin-left: 0;
        }

        .profile-form input {
            padding: 10px;
            border: 1px solid #c8b7a6;
            /* softer border */
            border-radius: 8px;
            background: #fff8f0;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .profile-form input:focus {
            outline: none;
            border-color: #d17878;
            /* soft accent on focus */
            box-shadow: 0 0 4px rgba(209, 120, 120, 0.3);
            /* subtle shadow */
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
            <h2>Edit Profile</h2>

            <?php if ($errors): ?>
                <div class="message error">
                    <ul><?php foreach ($errors as $e)
                        echo "<li>$e</li>"; ?></ul>
                </div>
            <?php elseif ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php endif; ?>

            <form method="post" class="profile-form" novalidate>
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>">

                <label>Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($user['email']); ?>">

                <label>Phone Number</label>
                <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']); ?>">



                <hr style="margin:10px 0; border-color:#e0d5c8;">

                <label>Current Password (required to change password)</label>
                <input type="password" name="current_password">

                <label>New Password</label>
                <input type="password" name="new_password">

                <label>Confirm New Password</label>
                <input type="password" name="confirm_password">

                <button type="submit" class="btn-save">Save Changes</button>
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

    <div id="logoutModal">
        <div class="modal-content">
            <h3>Log out</h3>
            <p>Are you sure you want to log out?</p>
            <div style="display:flex; justify-content:space-between; margin-top:15px;">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-logout" onclick="confirmLogout()">Log out</button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() { document.getElementById("logoutModal").style.display = "flex"; }
        function closeLogoutModal() { document.getElementById("logoutModal").style.display = "none"; }
        function confirmLogout() { window.location.href = "../credentials/logout.php"; }
    </script>

</body>

</html>