<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../customer/credentials/login.php");
    exit();
}

$loggedIn = true;
$user_id = $_SESSION['user_id'];

// DB connection
$conn = new mysqli('localhost', 'root', 'root', 'petshop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Handle cancel order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancelOrderId = (int) $_POST['cancel_order_id'];

    // Only allow cancelling if status is Packing AND belongs to user
    $check = $conn->prepare("
        SELECT status 
        FROM orders 
        WHERE order_id = ? AND user_id = ?
        LIMIT 1
    ");
    $check->bind_param("ii", $cancelOrderId, $user_id);
    $check->execute();
    $check->bind_result($currentStatus);
    $check->fetch();
    $check->close();

    if (strtolower($currentStatus) === 'packing') {
        $update = $conn->prepare("
            UPDATE orders 
            SET status = 'Cancelled' 
            WHERE order_id = ?
        ");
        $update->bind_param("i", $cancelOrderId);
        $update->execute();
        $update->close();
    }

    // refresh page
    header("Location: order_history.php");
    exit();
}

// Add tracking_id only if missing
$col = $conn->query("SHOW COLUMNS FROM orders LIKE 'tracking_id'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN tracking_id VARCHAR(100) NULL");
}

// Add status only if missing
$col = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Packing'");
}

// Add arrival_date column if missing
$col = $conn->query("SHOW COLUMNS FROM orders LIKE 'arrival_date'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN arrival_date DATETIME NULL");
}
// Add delivery_fee column if missing
$col = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_fee'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 5.00"); // default RM5
}


// Fill missing arrival_date with order_date + 3 days
$conn->query("
    UPDATE orders
    SET arrival_date = DATE_ADD(created_at, INTERVAL 3 DAY)
    WHERE arrival_date IS NULL
");


// Fetch orders with status and tracking_id; order by order_id ASC
$sql = "
    SELECT 
        o.order_id, 
        o.created_at AS order_date,
        o.arrival_date,
        o.status,
        o.tracking_id,
        o.shipping_address,
        o.delivery_fee,
        p.name AS product_name,
        p.image_path AS product_image,
        oi.quantity,
        oi.price
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.order_id DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id = $row['order_id'];

    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            "date" => $row["order_date"],
            "arrival_date" => $row["arrival_date"] ?? date('Y-m-d H:i:s', strtotime($row['order_date'] . ' +3 days')),
            "status" => $row["status"] ?? 'Packing',
            "tracking_id" => $row["tracking_id"],
            "shipping_address" => $row["shipping_address"] ?? 'Not provided',
            "delivery_fee" => $row["delivery_fee"] ?? 5.00,
            "items" => []
        ];
    }




    $orders[$order_id]["items"][] = [
        "product_name" => $row["product_name"],
        "product_image" => $row["product_image"],
        "quantity" => $row["quantity"],
        "price" => $row["price"]
    ];
}


?>

<!DOCTYPE html>
<html>

<head>
    <title>My Orders - Romeo Pet Shop</title>
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
            background: url('../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #4b3621;
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

        /* Page container */
        .container {
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 24px;
            background: rgba(255, 250, 244, 0.9);
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .page-title h1 {
            font-size: 26px;
            color: #4b3621;
        }

        .orders-list {
            margin-top: 10px;
        }

        /* Order card */
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

        .order-left {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .order-id {
            font-weight: 700;
            color: #4b3621;
            font-size: 16px;
        }

        .order-date {
            font-size: 13px;
            color: #7b6a58;
        }

        .tracking {
            font-size: 13px;
            color: #4b3621;
            margin-top: 4px;
        }

        /* Status badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 18px;
            font-weight: 700;
            color: #fff;
            font-size: 13px;
            display: inline-block;
            min-width: 120px;
            text-align: center;
        }

        /* Progress bar container */
        .progress-wrap {
            margin-top: 12px;
        }

        .progress {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }

        .step {
            flex: 1;
            text-align: center;
            font-size: 12px;
            color: #6a5b49;
            position: relative;
        }

        .step .dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin: 0 auto 6px;
            background: #e6e0d9;
            border: 3px solid #e6e0d9;
            transition: all .25s;
        }

        .step.active .dot {
            background: #fff;
            border-color: #d17878;
            box-shadow: 0 0 0 6px rgba(209, 120, 120, 0.12);
        }

        .step.completed .dot {
            background: #6bbf59;
            border-color: #6bbf59;
            box-shadow: none;
        }

        /* connecting line */
        .step:before {
            content: "";
            position: absolute;
            height: 4px;
            left: 50%;
            right: -50%;
            top: 11px;
            background: #e7dbca;
            z-index: -1;
        }

        .step:last-child:before {
            display: none;
        }

        /* items list */
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

        .controls {
            margin-top: 12px;
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            border: none;
        }

        .btn-details {
            background: #d17878;
            color: #fff;
        }

        .btn-track {
            background: #d69d6a;
            color: #fff;
        }

        /* modal */
        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1000;
        }

        .modal.open {
            display: flex;
        }

        .modal-card {
            width: 92%;
            max-width: 720px;
            background: #fffaf3;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-height: 85vh;
            overflow: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }

        /* responsive */
        @media (max-width:720px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .step:before {
                display: none;
            }

            .progress {
                flex-direction: column;
                gap: 6px;
                align-items: flex-start;
            }
        }

        /* footer */
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

        .btn-cancel {
            background: #c94a4a;
            color: #fff;
        }

        .btn-cancel:hover {
            background: #a93b3b;
        }

        .step.cancelled .dot {
            background: #c94a4a;
            border-color: #c94a4a;
        }

        .modal {
            pointer-events: auto;
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
            background: #3f3e3eff;
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

    <!-- NAV -->

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

    <!-- MAIN CONTAINER -->
    <div class="container">

        <div class="page-title">
            <div>
                <h1>Your Orders</h1>
                <p style="font-size:14px; color:#7b6a58; margin-top:6px;">
                    Track your purchases, check delivery progress, and manage your orders here.<br><br>
                </p>
                <p style="font-size:13px; color:#7b6a58; margin-top:4px;">
                    You can only cancel orders and request a refund if the order status is still
                    <strong>Packing</strong>.
                    <br>Orders that are <strong>Out for Delivery</strong> or <strong>Completed</strong> cannot be
                    cancelled.
                </p>
            </div>

        </div>

        <div
            style="margin:16px 0; display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-end; align-items:center;">
            <input type="text" id="searchInput" placeholder="Search by Product or Order ID" style="
            padding:8px 12px;
            border-radius:8px;
            border:1px solid #e0d5c8;
            font-family:Poppins;
            font-weight:600;
            color:#4b3621;
            min-width:200px;
        ">

            <select id="statusFilter" style="
            padding:8px 12px;
            border-radius:8px;
            border:1px solid #e0d5c8;
            font-family:Poppins;
            font-weight:600;
            color:#4b3621;
        ">
                <option value="all">All Orders</option>
                <option value="packing">Packing</option>
                <option value="out for delivery">Out for Delivery</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>


        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <p style="text-align:center; color:#4b3621; font-size:18px; padding:30px 0;">
                    You have not made any orders yet.
                </p>
            <?php else: ?>
                <?php
                $counter = 1; // start numbering
                foreach ($orders as $order_id => $order):
                    $status = strtolower($order['status'] ?? 'packing');
                    $tracking = $order['tracking_id'] ?? null;

                    // Determine badge color
                    $badgeColor = '#d69d6a'; // default packing
                    if ($status === 'packing')
                        $badgeColor = '#d69d6a';
                    if ($status === 'out for delivery' || $status === 'out_for_delivery')
                        $badgeColor = '#d17878';
                    if ($status === 'completed')
                        $badgeColor = '#6bbf59';
                    if ($status === 'cancelled')
                        $badgeColor = '#c94a4a';

                    // progress state
                    $isPacking = in_array($status, ['packing']);
                    $isOut = in_array($status, ['out for delivery', 'out_for_delivery']);
                    $isCompleted = ($status === 'completed');
                    $isCancelled = ($status === 'cancelled');
                    ?>
                    <div class="order-card" id="order-card-<?= htmlspecialchars($order_id) ?>"
                        data-status="<?= htmlspecialchars($status) ?>">

                        <div class="order-header">
                            <div class="order-left">
                                <div class="order-number"
                                    style="margin-right:12px; font-weight:500; font-size:14px; color:#4b3621;">
                                    <?= $counter ?>.

                                </div>
                                <div>
                                    <div class="order-date">
                                        <strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?><br>
                                        Ordered: <?= date('d M Y, h:i A', strtotime($order['date'])) ?><br>
                                        Estimate Arrival:
                                        <?= $order['arrival_date'] ? date('d M Y', strtotime($order['arrival_date'])) : '<em>Not assigned</em>' ?>
                                    </div>



                                    <?php if ($order['shipping_address']): ?>
                                        <div class="tracking" style="margin-top:4px;">
                                            <strong>Shipping Address:</strong>
                                            <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($status, ['out for delivery', 'completed']) && $tracking): ?>
                                        <div class="tracking">Tracking: <?= htmlspecialchars($tracking) ?></div>
                                    <?php elseif ($status === 'packing'): ?>
                                        <div class="tracking"><em>Tracking will be available once shipped</em></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div>
                                <div class="status-badge" style="background: <?= $badgeColor ?>;">
                                    <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                </div>
                            </div>
                        </div>
                        <!-- progress -->
                        <div class="progress-wrap">
                            <div class="progress" aria-hidden="true">
                                <?php
                                // Step classes
                                $step1 = $isPacking ? 'active' : ($isOut || $isCompleted ? 'completed' : '');
                                $step2 = $isOut ? 'active' : ($isCompleted ? 'completed' : '');
                                $step3 = $isCompleted ? 'completed' : '';
                                if ($isCancelled) {
                                    $step1 = $step2 = $step3 = 'cancelled';
                                }
                                ?>
                                <div class="step <?= $step1 ?>">
                                    <div class="dot"></div>
                                    <div>Packing</div>
                                </div>

                                <div class="step <?= $step2 ?>">
                                    <div class="dot"></div>
                                    <div>Out for Delivery</div>
                                </div>

                                <div class="step <?= $step3 ?>">
                                    <div class="dot"></div>
                                    <div>Completed</div>
                                </div>
                            </div>
                        </div>

                        <!-- items list -->
                        <div class="items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item-row">
                                    <div class="item-img">
                                        <img src="../staff/system/<?php echo htmlspecialchars($item['product_image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    </div>

                                    <div>
                                        <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <div class="item-qty">Quantity: <?= (int) $item['quantity'] ?></div>
                                    </div>

                                    <div class="item-price">RM <?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>

                            <?php
                            // Calculate totals **once per order**, after listing all items
                            $itemsTotal = 0;
                            foreach ($order['items'] as $item) {
                                $itemsTotal += $item['price'] * $item['quantity'];
                            }
                            $totalPrice = $itemsTotal + $order['delivery_fee'];
                            ?>
                            <div style="margin-top:8px; font-weight:700; text-align:right;">
                                Items Total: RM <?= number_format($itemsTotal, 2) ?><br>
                                Delivery: RM <?= number_format($order['delivery_fee'], 2) ?><br>
                                <span style="font-size:16px;">Grand Total: RM <?= number_format($totalPrice, 2) ?></span>
                            </div>



                            <div class="controls">
                                <button class="btn btn-details" data-order="<?= htmlspecialchars($order_id) ?>">
                                    View Details
                                </button>

                                <?php if (in_array($status, ['out for delivery', 'completed']) && $tracking): ?>
                                    <button class="btn btn-track" onclick="copyTracking('<?= htmlspecialchars($tracking) ?>')">
                                        Copy Tracking
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-track" disabled style="opacity:0.5; cursor:not-allowed;">
                                        Tracking Not Available
                                    </button>
                                <?php endif; ?>


                                <?php if ($status === 'packing'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="cancel_order_id" value="<?= (int) $order_id ?>">
                                        <button type="button" class="btn btn-cancel"
                                            onclick="openCancelModal(<?= (int) $order_id ?>)">
                                            Cancel Order
                                        </button>


                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="modal" aria-hidden="true">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <strong>Cancel Order</strong>
                <button class="modal-close" onclick="closeCancelModal()">✕</button>
            </div>

            <p style="font-size:15px; color:#5b4a3a; margin-bottom:20px;">
                Are you sure you want to cancel this order?<br>
                <strong>This action cannot be undone.</strong>
            </p>

            <form method="post" id="cancelForm" style="display:flex; gap:12px; justify-content:flex-end;">
                <input type="hidden" name="cancel_order_id" id="cancelOrderId">

                <button type="button" class="btn" style="background:#ccc;" onclick="closeCancelModal()">
                    No, Keep Order
                </button>

                <button type="submit" class="btn btn-cancel">
                    Yes, Cancel
                </button>
            </form>
        </div>
    </div>


    <!-- Modal (single modal reused for all orders) -->
    <div id="orderModal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-card" role="document">
            <div class="modal-header">
                <div>
                    <strong id="modalOrderId">Order #</strong>
                    <div id="modalOrderDate" style="font-size:13px; color:#7b6a58;"></div>
                    <div id="modalTracking" style="font-size:13px; color:#7b6a58; margin-top:6px;"></div>
                </div>
                <button class="modal-close" aria-label="Close" onclick="closeModal()">✕</button>
            </div>

            <div id="modalContent">
                <!-- Filled by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Footer -->
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

    <script>
        // Prepare order data on the page for modal usage (JSON encoded)
        const ORDERS = <?= json_encode($orders, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        // open modal and populate
        document.querySelectorAll('.btn-details').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const orderId = btn.getAttribute('data-order');
                openModal(orderId);
            });
        });

        function openModal(orderId) {
            const modal = document.getElementById('orderModal');
            const data = ORDERS[orderId];
            if (!data) return;

            document.getElementById('modalOrderId').textContent = 'Order #' + orderId;
            document.getElementById('modalOrderDate').textContent = new Date(data.date).toLocaleString();
            if (
                (data.status === 'out for delivery' || data.status === 'completed')
                && data.tracking_id
            ) {
                document.getElementById('modalTracking').innerHTML =
                    'Tracking: ' + escapeHtml(data.tracking_id);
            } else {
                document.getElementById('modalTracking').innerHTML =
                    '<em>Tracking will be available once shipped</em>';
            }

            // build items table
            let html = '<div style="margin-top:8px;">';
            html += '<table style="width:100%; border-collapse:collapse;">';
            html += '<thead><tr style="text-align:left;"><th>Product</th><th>Qty</th><th style="text-align:right;">Price</th></tr></thead><tbody>';
            let totalItems = 0;
            data.items.forEach(it => {
                const price = parseFloat(it.price) * parseInt(it.quantity);
                totalItems += price;
                html += '<tr style="border-bottom:1px solid #efe7dd;">';
                html += '<td style="padding:10px 8px;">' + escapeHtml(it.product_name) + '</td>';
                html += '<td style="padding:10px 8px;">' + parseInt(it.quantity) + '</td>';
                html += '<td style="padding:10px 8px; text-align:right;">RM ' + price.toFixed(2) + '</td>';
                html += '</tr>';
            });
            html += '<tr><td colspan="2" style="padding:10px 8px; font-weight:700;">Items Total</td><td style="padding:10px 8px; text-align:right; font-weight:700;">RM ' + totalItems.toFixed(2) + '</td></tr>';
            html += '<tr><td colspan="2" style="padding:10px 8px; font-weight:700;">Delivery</td><td style="padding:10px 8px; text-align:right; font-weight:700;">RM ' + parseFloat(data.delivery_fee).toFixed(2) + '</td></tr>';
            html += '<tr><td colspan="2" style="padding:10px 8px; font-weight:700;">Grand Total</td><td style="padding:10px 8px; text-align:right; font-weight:700;">RM ' + (totalItems + parseFloat(data.delivery_fee)).toFixed(2) + '</td></tr>';

            document.getElementById('modalContent').innerHTML = html;

            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            const modal = document.getElementById('orderModal');
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }

        // Utility to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/[&<>"']/g, function (m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
            });
        }

        // copy tracking id (will copy empty string if none)
        function copyTracking(tracking) {
            if (!tracking) {
                alert('Tracking ID not assigned yet.');
                return;
            }
            navigator.clipboard?.writeText(tracking).then(() => {
                alert('Tracking ID copied to clipboard.');
            }).catch(() => {
                // fallback
                const ta = document.createElement('textarea');
                ta.value = tracking;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                alert('Tracking ID copied to clipboard.');
            });
        }

        // close modal when clicking outside card
        document.getElementById('orderModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        // keyboard ESC to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
        function openCancelModal(orderId) {
            document.getElementById('cancelOrderId').value = orderId;
            const modal = document.getElementById('cancelModal');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelModal');
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }
        document.getElementById('cancelModal').addEventListener('click', function (e) {
            if (e.target === this) closeCancelModal();
        });
        // DROPDOWN STATUS FILTER
        const statusFilter = document.getElementById('statusFilter');
        const searchInput = document.getElementById('searchInput');

        function filterOrders() {
            const selectedStatus = statusFilter.value.toLowerCase();
            const searchText = searchInput.value.toLowerCase();

            document.querySelectorAll('.order-card').forEach(card => {
                const status = card.dataset.status.toLowerCase();
                const orderId = card.id.replace('order-card-', '');
                const itemsText = Array.from(card.querySelectorAll('.item-name'))
                    .map(el => el.textContent.toLowerCase())
                    .join(' ');

                // Check if matches status AND search
                const matchesStatus = (selectedStatus === 'all' || status === selectedStatus);
                const matchesSearch = (orderId.includes(searchText) || itemsText.includes(searchText));

                if (matchesStatus && matchesSearch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Event listeners
        statusFilter.addEventListener('change', filterOrders);
        searchInput.addEventListener('input', filterOrders);

        function renumberOrders() {
            let count = 1;
            document.querySelectorAll('.order-card').forEach(card => {
                if (card.style.display !== 'none') {
                    const numberEl = card.querySelector('.order-number');
                    if (numberEl) {
                        numberEl.textContent = count + '.';
                        count++;
                    }
                }
            });
        }

        // Call renumberOrders after filtering
        function filterOrders() {
            const selectedStatus = statusFilter.value.toLowerCase();
            const searchText = searchInput.value.toLowerCase();

            document.querySelectorAll('.order-card').forEach(card => {
                const status = card.dataset.status.toLowerCase();
                const orderId = card.id.replace('order-card-', '');
                const itemsText = Array.from(card.querySelectorAll('.item-name'))
                    .map(el => el.textContent.toLowerCase())
                    .join(' ');

                const matchesStatus = (selectedStatus === 'all' || status === selectedStatus);
                const matchesSearch = (orderId.includes(searchText) || itemsText.includes(searchText));

                card.style.display = (matchesStatus && matchesSearch) ? 'block' : 'none';
            });

            renumberOrders(); // update numbers
        }

        // Initial numbering
        renumberOrders();



    </script>
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