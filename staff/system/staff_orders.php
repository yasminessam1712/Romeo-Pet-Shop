<?php
session_start();

// Block non-staff
if (!isset($_SESSION['user_id'])) {
    header("Location: ../credentials/login_staff.php");
    exit();
}

$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

// Add missing columns
$columns = [
    'tracking_id' => "VARCHAR(100) NULL",
    'status' => "VARCHAR(30) NOT NULL DEFAULT 'Packing'",
    'arrival_date' => "DATE NULL"
];
foreach ($columns as $col => $type) {
    $check = $conn->query("SHOW COLUMNS FROM orders LIKE '$col'");
    if ($check->num_rows == 0)
        $conn->query("ALTER TABLE orders ADD COLUMN $col $type");
}

/* Search Feature */
$search = "";
$where = "WHERE 1 ";

if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where .= " AND (o.order_id LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

if (!empty($_GET['status_filter'])) {
    $statusFilter = $conn->real_escape_string($_GET['status_filter']);
    $where .= " AND o.status = '$statusFilter' ";
}

// Fetch orders
$sql = "
SELECT 
    o.order_id,
    o.user_id,
    u.full_name,
    u.address,
    u.phone_number,
    u.email,
    o.created_at AS order_date,
    o.arrival_date,
    o.status,
    o.tracking_id,
    p.name AS product_name,
    p.image_path AS product_image,
    oi.quantity,
    oi.price
FROM orders o
JOIN users u ON o.user_id = u.user_id
JOIN order_items oi ON o.order_id = oi.order_id
JOIN products p ON oi.product_id = p.id
$where
ORDER BY o.order_id DESC
";

$result = $conn->query($sql);

// Organize items by order
$orders = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['order_id'];
    if (!isset($orders[$id])) {
        $orders[$id] = [
            "full_name" => $row['full_name'],
            "address" => $row['address'],
            "phone" => $row['phone_number'],
            "email" => $row['email'],
            "date" => $row['order_date'],
            "arrival_date" => $row['arrival_date'],
            "status" => $row['status'],
            "tracking_id" => $row['tracking_id'],
            "items" => []
        ];
    }
    $orders[$id]['items'][] = $row;
}

// Status colors
$statusColors = [
    'Packing' => '#6898ffff',          // yellow
    'Out for Delivery' => '#d78038ff',  // orange
    'Completed' => '#2e701fff',        // green
    'Cancelled' => '#c94a4a'         // red
];
function generateTrackingNumber($orderId)
{
    return 'RPS-' . date('Ymd') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
}


/* Update order */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $oid = (int) $_POST['order_id'];
    $arrival = $_POST['arrival_date'] ?: null;
    $status = $_POST['status'];

    // Get existing tracking (if any)
    $check = $conn->prepare("SELECT tracking_id FROM orders WHERE order_id = ?");
    $check->bind_param("i", $oid);
    $check->execute();
    $check->bind_result($existingTracking);
    $check->fetch();
    $check->close();

    $trackingToSave = $existingTracking;

    // AUTO-GENERATE tracking ONLY when status becomes Out for Delivery
    if ($status === 'Out for Delivery' && empty($existingTracking)) {
        $trackingToSave = generateTrackingNumber($oid);
    }

    // Update order
    $stmt = $conn->prepare("
        UPDATE orders 
        SET arrival_date = ?, status = ?, tracking_id = ?
        WHERE order_id = ?
    ");
    $stmt->bind_param("sssi", $arrival, $status, $trackingToSave, $oid);
    $stmt->execute();
    $stmt->close();

    header("Location: staff_orders.php");
    exit();
}

?>


<!DOCTYPE html>
<html>

<head>
    <title>Staff Orders - Romeo Pet Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('../../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            transition: background-color 0.4s ease;
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

        /* Container */
        .container {
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 24px;
            background: rgba(255, 250, 244, 0.9);
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-bottom: 20px;
            color: #4b3621;
        }

        /* Order Card */
        .order-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #eee1d6;
            padding: 18px;
            margin-bottom: 18px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 18px;
            font-weight: 700;
            color: #fff;
            font-size: 13px;
            display: inline-block;
            min-width: 120px;
            text-align: center;
            background: #d69d6a;
        }

        .items {
            margin-top: 14px;
            border-top: 1px dashed #eee1d6;
            padding-top: 12px;
        }

        .item-row {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f2ece6;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: #f5f1ee;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-name {
            font-weight: 600;
            color: #4b3621;
            font-size: 15px;
        }

        .item-qty {
            font-size: 13px;
            color: #7b6a58;
        }

        .item-price {
            margin-left: auto;
            font-weight: 700;
            color: #4b3621;
        }

        form {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        form input,
        form select {
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .btn-details {
            padding: 8px 12px;
            border-radius: 8px;
            background: #d17878;
            color: #fff;
            border: none;
            font-weight: 700;
            cursor: pointer;
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

        .order-card {
            position: relative;
        }

        .order-number-left {
            position: absolute;
            left: 12px;
            top: 18px;
            font-weight: 700;
            font-size: 16px;
            color: #4b3621;
        }

        @media (max-width:720px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
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


    <div class="container">

        <h1>All Customer Orders</h1>
        <p style="margin-bottom:20px; color:#4b3621; font-size:14px;">
            View all orders placed by customers below. You can search by Order ID or Customer Name, filter by status,
            and update the order's arrival date, status, or tracking ID as needed.
        </p>

        <!-- SEARCH BAR -->
        <form method="get" style="margin-bottom:20px; display:flex; gap:10px;">
            <input type="text" name="search" placeholder="Search Order ID / Customer Name"
                value="<?= htmlspecialchars($search) ?>"
                style="padding:10px; width:300px; border-radius:8px; border:1px solid #c4b9a8;">
            <button type="submit" class="btn-details">Search</button>
        </form>

        <!-- STATUS FILTER -->
        <form method="get" style="margin-bottom:20px; display:flex; gap:10px;">
            <select name="status_filter" style="padding:10px; border-radius:8px; border:1px solid #c4b9a8;">
                <option value="">All Status</option>
                <option value="Packing" <?= ($_GET['status_filter'] ?? '') == "Packing" ? "selected" : "" ?>>Packing</option>
                <option value="Out for Delivery" <?= ($_GET['status_filter'] ?? '') == "Out for Delivery" ? "selected" : "" ?>>
                    Out for Delivery</option>
                <option value="Completed" <?= ($_GET['status_filter'] ?? '') == "Completed" ? "selected" : "" ?>>Completed
                </option>
                <option value="Cancelled" <?= ($_GET['status_filter'] ?? '') == "Cancelled" ? "selected" : "" ?>>Cancelled
                </option>
            </select>
            <button type="submit" class="btn-details">Filter</button>
        </form>


        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>

            <?php $counter = 1;
            foreach ($orders as $id => $o): ?>

                <div class="order-card">
                    <div class="order-number-left">
                        <?= $counter ?>.
                    </div>

                    <?php
                    $total = 0;
                    foreach ($o['items'] as $i) {
                        $total += ($i['price'] * $i['quantity']);
                    }
                    ?>
                    <div style="text-align:right; font-size:17px; font-weight:700; margin-top:10px; color:#4b3621;">
                        Total Price: RM <?= number_format($total, 2) ?>
                    </div>

                    <div class="order-header">
                        <div>

                            <strong>Order ID: <?= $id ?></strong><br>
                            Customer: <?= htmlspecialchars($o['full_name']) ?><br>
                            Email: <?= htmlspecialchars($o['email']) ?><br>
                            Phone: <?= htmlspecialchars($o['phone']) ?><br>
                            Address: <?= htmlspecialchars($o['address']) ?><br><br>
                            <div style="font-size:13px; color:#6b5a4b;">
                                Ordered: <?= date('d M Y', strtotime($o['date'])) ?><br>
                                Arrival:
                                <?= $o['arrival_date'] ? date('d M Y', strtotime($o['arrival_date'])) : '<em>Not set</em>' ?><br>
                                Tracking: <?= $o['tracking_id'] ?: '<em>Not set</em>' ?>
                            </div>
                        </div>
                        <div class="status-badge"
                            style="background: <?= $statusColors[$o['status']] ?? '#ccc' ?>; margin-top: 10px;">
                            <?= ucfirst($o['status']) ?>
                        </div>
                    </div>


                    <div class="items">
                        <?php foreach ($o['items'] as $item): ?>
                            <div class="item-row">
                                <div class="item-img">
                                    <img src="<?= htmlspecialchars($item['product_image']) ?>">
                                </div>
                                <div>
                                    <div class="item-name"><?= $item['product_name'] ?></div>
                                    <div class="item-qty">Quantity: <?= $item['quantity'] ?></div>
                                </div>
                                <div class="item-price">
                                    RM <?= number_format($item['price'] * $item['quantity'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="post">
                        <input type="hidden" name="order_id" value="<?= $id ?>">

                        <label>
                            Arrival Date:
                            <input type="date" name="arrival_date"
                                value="<?= $o['arrival_date'] ? date('Y-m-d', strtotime($o['arrival_date'])) : '' ?>">


                            <label style="margin-left:40px;">
                                Status:
                                <select name="status">
                                    <?php foreach (['Packing', 'Out for Delivery', 'Completed', 'Cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $o['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>


                            <button
                                style="padding:8px 22px; margin-left:320px; border-radius:8px; background:#d17878; color:#fff; border:none; font-weight:700; cursor:pointer;">Update</button>
                    </form>

                </div>
                <?php $counter++; endforeach; ?>
        <?php endif; ?>



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
    <script>
        // Dynamically renumber orders
        function renumberOrders() {
            let count = 1;
            document.querySelectorAll('.order-card').forEach(card => {
                if (card.style.display !== 'none') {
                    const numberEl = card.querySelector('.order-number');
                    if (numberEl) {
                        numberEl.textContent = count + '.';
                    }
                    count++;
                }
            });
        }

        // Call this once on page load
        renumberOrders();
        function filterOrders() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.order-card').forEach(card => {
                const matches = card.querySelector('.order-header').innerText.toLowerCase().includes(searchText);
                card.style.display = matches ? 'block' : 'none';
            });
            renumberOrders();
        }

    </script>

</body>

</html>