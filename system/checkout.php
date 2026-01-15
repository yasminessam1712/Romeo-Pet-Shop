<?php
session_start();

$loggedIn = isset($_SESSION['user_id']);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$is_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? null;

// Fetch user data
$stmt = $conn->prepare("SELECT full_name, phone_number, address FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get cart items
$stmt = $conn->prepare("SELECT sc.product_id, p.name, p.price, p.image_path, sc.quantity 
                        FROM shopping_cart sc 
                        JOIN products p ON sc.product_id = p.id 
                        WHERE sc.user_id = ?");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = [];
$total_amount = 0;

while ($item = $cart_result->fetch_assoc()) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total_amount += $item['subtotal'];
    $cart_items[] = $item;
}
$delivery_fee = 5; // Flat RM10 delivery



// Add delivery fee to total amount
$total_amount_with_delivery = $total_amount + $delivery_fee;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $card_number = $_POST['card_number'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    // Get last 4 digits only
    $card_last4 = substr(preg_replace('/\D/', '', $card_number), -4);

    // Save user details
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, address = ?, card_last4 = ?, card_expiry = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("sssssi", $full_name, $phone, $address, $card_last4, $expiry, $user_id);
    $stmt->execute();


    $tracking_id = $tracking_number;
    $shipping_address = $address;
    $payment_method = "Card";

    $stmt = $conn->prepare("
INSERT INTO orders 
(user_id, total_amount, tracking_id, shipping_address, payment_method, card_last4)
VALUES (?, ?, ?, ?, ?, ?)
");
    $stmt->bind_param(
        "idssss",
        $user_id,
        $total_amount_with_delivery, // this now exists
        $tracking_id,
        $shipping_address,
        $payment_method,
        $card_last4
    );

    $stmt->execute();
    $order_id = $stmt->insert_id;



    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    // 🔻 Deduct stock from products table
    $update_stock = $conn->prepare("UPDATE products SET units = units - ? WHERE id = ?");
    foreach ($cart_items as $item) {
        $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $update_stock->execute();
    }



    // Clear the cart
    $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    header("Location: thankyou.php?order_id=" . $order_id);
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>
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

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #4b3621;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        textarea {
            resize: vertical;
        }

        .cart-summary {
            background-color: #f8f1e4;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }

        .cart-summary ul {
            list-style: none;
            padding-left: 0;
        }

        .cart-summary li {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }

        .submit-btn {
            background-color: #4b3621;
            color: white;
            font-weight: bold;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #372719;
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

        .checkout-container {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            gap: 30px;
        }

        .cart-container {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            flex: 1;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
        }

        .cart-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .cart-item {
            display: flex;
            align-items: flex-start;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            gap: 15px;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .item-info h4 {
            margin: 0;
            font-size: 16px;
            color: #4b3621;
        }

        .item-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .cart-total {
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #4b3621;
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

        .error-msg {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #fff;
            font-size: 14px;
            color: #333;
            appearance: none;
            /* removes default arrow style on some browsers */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'><path fill='%23333' d='M7 10l5 5 5-5H7z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
            cursor: pointer;
        }

        select:focus {
            border-color: #4b3621;
            outline: none;
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
    <div class="checkout-container">
        <!-- LEFT SIDE: Cart Summary -->
        <div class="cart-container">
            <h2>Your Cart</h2>
            <?php if (!empty($cart_items)): ?>
                <ul class="cart-list">
                    <?php foreach ($cart_items as $item): ?>
                        <li class="cart-item">
                            <img src="../staff/system/<?= htmlspecialchars($item['image_path']) ?>" alt="Product Image"
                                onerror="this.onerror=null;this.src='../pictures/default_product.png';">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <p>Qty: <?= $item['quantity'] ?></p>
                                <p>Price: RM <?= number_format($item['price'], 2) ?></p>
                                <p><strong>Subtotal: RM <?= number_format($item['subtotal'], 2) ?></strong></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="cart-total">
                    <p>Order Total: RM <?= number_format($total_amount, 2) ?></p>
                    <p>Delivery Fee: RM <?= number_format($delivery_fee, 2) ?></p>
                    <p><strong>Total (including delivery): RM <?= number_format($total_amount_with_delivery, 2) ?></strong>
                    </p>
                </div>

            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>

        <!-- RIGHT SIDE: Checkout Form -->
        <div class="form-container">
            <h2>Checkout Information</h2>

            <form method="POST">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>

                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
                    required>

                <label for="street">Street Address</label>

                <input type="text" id="street" name="street" placeholder="Street" required>
                <label for="city">City / State</label>
                <select id="city" name="city" required>
                    <option value="">Select your city</option>
                    <option value="Kuala Lumpur">Kuala Lumpur</option>
                    <option value="Putrajaya">Putrajaya</option>
                    <option value="Selangor">Selangor</option>
                    <option value="Penang">Penang</option>
                    <option value="Johor">Johor</option>
                    <option value="Perak">Perak</option>
                    <option value="Negeri Sembilan">Negeri Sembilan</option>
                    <option value="Malacca">Malacca</option>
                    <option value="Pahang">Pahang</option>
                    <option value="Kelantan">Kelantan</option>
                    <option value="Terengganu">Terengganu</option>
                    <option value="Sarawak">Sarawak</option>
                    <option value="Sabah">Sabah</option>
                    <option value="Kedah">Kedah</option>
                </select>

                <input type="text" id="postcode" name="postcode" placeholder="Postcode" maxlength="5" required>

                <!-- hidden input for DB -->
                <input type="hidden" id="full_address" name="address">

                <!-- Error messages -->
                <div id="street_error" class="error-msg">Street must start with a number.</div>
                <div id="city_error" class="error-msg">Please select a city.</div>
                <div id="postcode_error" class="error-msg">Postcode must be 5 digits.</div>


                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" maxlength="19" placeholder="1234 5678 9012 3456"
                    required>
                <div id="card_number_error" class="error-msg">Card number must be 16 digits.</div>

                <label for="expiry">Expiry Date (MM/YY)</label>
                <input type="text" id="expiry" name="expiry" maxlength="5" placeholder="08/25" required>
                <div id="expiry_error" class="error-msg">Expiry must be valid and in the future.</div>

                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" maxlength="4" placeholder="123" required>
                <div id="cvv_error" class="error-msg">CVV must be 3 digits.</div>

                <button type="submit" class="submit-btn">Save and Proceed</button>
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
    <script>

        document.querySelector('form').addEventListener('submit', function (e) {
            e.preventDefault(); // prevent default submit

            let valid = true;

            // ===================== CARD VALIDATION =====================
            let cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
            let cvv = document.getElementById('cvv').value.trim();
            let expiry = document.getElementById('expiry').value.trim();

            // Card number: 16 digits
            if (!/^\d{16}$/.test(cardNumber)) {
                document.getElementById('card_number_error').style.display = 'block';
                valid = false;
            } else {
                document.getElementById('card_number_error').style.display = 'none';
            }

            // CVV: 3 digits
            if (!/^\d{3}$/.test(cvv)) {
                document.getElementById('cvv_error').style.display = 'block';
                valid = false;
            } else {
                document.getElementById('cvv_error').style.display = 'none';
            }

            // Expiry: MM/YY format & future date
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) {
                document.getElementById('expiry_error').style.display = 'block';
                valid = false;
            } else {
                let parts = expiry.split('/');
                let month = parseInt(parts[0], 10);
                let year = parseInt(parts[1], 10) + 2000;
                let expDate = new Date(year, month - 1, 1);
                let today = new Date();
                today.setDate(1);
                if (expDate < today) {
                    document.getElementById('expiry_error').style.display = 'block';
                    valid = false;
                } else {
                    document.getElementById('expiry_error').style.display = 'none';
                }
            }

            // ===================== ADDRESS VALIDATION =====================
            let street = document.getElementById('street').value.trim();
            let city = document.getElementById('city').value;
            let postcode = document.getElementById('postcode').value.trim();

            // Street: must start with number
            let streetRegex = /^[0-9]+[A-Za-z0-9\s\/]+$/;
            if (!streetRegex.test(street)) {
                document.getElementById('street_error').style.display = 'block';
                valid = false;
            } else {
                document.getElementById('street_error').style.display = 'none';
            }

            // City: must select
            if (!city) {
                document.getElementById('city_error').style.display = 'block';
                valid = false;
            } else {
                document.getElementById('city_error').style.display = 'none';
            }

            // Postcode: exactly 5 digits
            if (!/^\d{5}$/.test(postcode)) {
                document.getElementById('postcode_error').style.display = 'block';
                valid = false;
            } else {
                document.getElementById('postcode_error').style.display = 'none';
            }

            // Combine address if all valid
            if (valid) {
                document.getElementById('full_address').value = `${street}, ${city}, ${postcode}`;
                this.submit(); // submit form
            }
        });
    </script>





</body>

</html>