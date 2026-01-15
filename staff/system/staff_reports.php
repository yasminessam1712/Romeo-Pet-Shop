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

// --- Filters ---
$month = isset($_GET['month']) ? (int) $_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');

// --- Total Sales ---
$totalSalesSql = "SELECT SUM(oi.price * oi.quantity) AS total_sales FROM order_items oi
JOIN orders o ON oi.order_id=o.order_id
WHERE MONTH(o.created_at)=$month AND YEAR(o.created_at)=$year";
$totalSalesResult = $conn->query($totalSalesSql);
$totalSales = $totalSalesResult->fetch_assoc()['total_sales'] ?? 0;

// --- Orders by Status ---
$statusSql = "SELECT status, COUNT(*) AS count_orders FROM orders GROUP BY status";
$statusResult = $conn->query($statusSql);
$ordersByStatus = [];
while ($row = $statusResult->fetch_assoc()) {
    $ordersByStatus[$row['status']] = $row['count_orders'];
}

// --- Top Products ---
$topProdSql = "
SELECT p.name, SUM(oi.quantity) AS total_qty, SUM(oi.price * oi.quantity) AS total_sales
FROM order_items oi
JOIN products p ON oi.product_id = p.id
GROUP BY p.id
ORDER BY total_qty DESC
LIMIT 50
";
$topProdResult = $conn->query($topProdSql);
$topProducts = [];
while ($row = $topProdResult->fetch_assoc()) {
    $topProducts[] = $row;
}

// --- Units Available ---
$unitsLeft = [];
$unitsSql = "SELECT name, units FROM products ORDER BY name ASC";
$resUnits = $conn->query($unitsSql);
while ($row = $resUnits->fetch_assoc()) {
    $unitsLeft[] = $row;
}

// --- Download as CSV ---
if (isset($_GET['download'])) {
    $filename = "report_{$month}_{$year}.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Top Products', 'Total Quantity Sold', 'Total Sales (RM)']);
    foreach ($topProducts as $p) {
        fputcsv($out, [$p['name'], $p['total_qty'], number_format($p['total_sales'], 2)]);
    }
    fclose($out);
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Sales Report - Romeo Pet Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: #4b3621;
            margin-bottom: 20px;
        }

        .card {
            padding: 20px;
            border-radius: 12px;
            background: #f5f1ee;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .card h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: #4b3621;
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            align-items: center;
        }

        .filters select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            background: #d17878;
            color: #fff;
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn:hover {
            background: #b05c5c;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        table th,
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background: #4b3621;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
        }

        table tr:hover {
            background: #f2e9e1;
        }

        .search-input {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 250px;
        }

        .description {
            font-size: 13px;
            color: #555;
            margin-bottom: 8px;
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

        .page-description {
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
            /* space between description and first card */
            line-height: 1.5;
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

        @media (max-width:720px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        @media(max-width:768px) {
            .filters {
                flex-direction: column;
                align-items: flex-start;
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
        <h1>Sales Reports (<?= date("F", mktime(0, 0, 0, $month, 1)) ?> <?= $year ?>)</h1>
        <p class="page-description">
            Overview of sales, order status, top-selling products, and inventory for the selected month and year.
            (Delivery fee not inlcuded)
        </p>



        <!-- Total Sales -->
        <div class="card">
            <form method="get" class="filters">
                <label>Month:
                    <select name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Year:
                    <select name="year">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <button class="btn" type="submit">Filter</button>

            </form>
            <h2>Total Sales</h2>
            <p class="description">Total revenue generated from orders for the selected month and year.</p>
            <p style="font-size:20px; font-weight:bold;">RM <?= number_format($totalSales, 2) ?></p>
        </div>

        <!-- Orders by Status -->
        <div class="card">
            <h2>Orders by Status</h2>
            <p class="description">Overview of the number of orders in each status (Packing, Out for Delivery,
                Completed, Cancelled).</p>
            <input type="text" id="searchStatus" class="search-input" placeholder="Search status...">
            <table id="tableStatus">
                <tr>
                    <th>Status</th>
                    <th>Number of Orders</th>
                </tr>
                <?php foreach ($ordersByStatus as $status => $count): ?>
                    <tr>
                        <td><?= htmlspecialchars($status) ?></td>
                        <td><?= $count ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Top Selling Products -->
        <div class="card">
            <h2>Top Selling Products</h2>
            <p class="description">List of products with the highest quantity sold during the selected month and year.
            </p>
            <input type="text" id="searchProducts" class="search-input" placeholder="Search product...">
            <table id="tableProducts">
                <tr>
                    <th>Product</th>
                    <th>Total Quantity Sold</th>
                    <th>Total Sales (RM)</th>
                </tr>
                <?php foreach ($topProducts as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= $p['total_qty'] ?></td>
                        <td><?= number_format($p['total_sales'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Units Available -->
        <div class="card">
            <h2>Units Available</h2>
            <p class="description">Current stock quantity of all products in inventory.</p>
            <input type="text" id="searchUnits" class="search-input" placeholder="Search product...">
            <table id="tableUnits">
                <tr>
                    <th>Product</th>
                    <th>Units Left</th>
                </tr>
                <?php foreach ($unitsLeft as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= $u['units'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>

    <script>
        // Simple table search function
        function setupSearch(inputId, tableId) {
            document.getElementById(inputId).addEventListener('keyup', function () {
                let filter = this.value.toUpperCase();
                let rows = document.getElementById(tableId).getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    let td = rows[i].getElementsByTagName('td')[0];
                    rows[i].style.display = td && td.textContent.toUpperCase().includes(filter) ? '' : 'none';
                }
            });
        }

        setupSearch('searchStatus', 'tableStatus');
        setupSearch('searchProducts', 'tableProducts');
        setupSearch('searchUnits', 'tableUnits');
    </script>
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