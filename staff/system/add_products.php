<?php
session_start(); // Start session

$loggedIn = isset($_SESSION['user_id']);

$loggedIn = isset($_SESSION['user_id']);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../credentials/login_staff.php");
    exit();
}

// DB config
$host = 'localhost';
$user = 'root';
$password = 'root';
$dbname = 'petshop';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error)
    die('Connection failed: ' . $conn->connect_error);

// Handle AJAX (update units / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];
    if ($action === 'update_units') {
        $id = (int) ($_POST['id'] ?? 0);
        $units = (int) ($_POST['units'] ?? 0);
        $stmt = $conn->prepare("UPDATE products SET units=? WHERE id=?");
        $stmt->bind_param("ii", $units, $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode($ok ? ['status' => 'success', 'message' => 'Units updated.', 'units' => $units] : ['status' => 'error', 'message' => 'Failed to update units.']);
        exit;
    }
    if ($action === 'delete_product') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT image_path FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($img);
        $stmt->fetch();
        $stmt->close();
        $stmt2 = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt2->bind_param("i", $id);
        $ok = $stmt2->execute();
        $stmt2->close();
        if ($ok && !empty($img) && file_exists($img))
            @unlink($img);
        echo json_encode($ok ? ['status' => 'success', 'message' => 'Product deleted.'] : ['status' => 'error', 'message' => 'Failed to delete product.']);
        exit;
    }
}

// Handle add product
// Handle add product
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {

    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $cat_id = (int) ($_POST['category'] ?? 0);
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    $desc = $conn->real_escape_string($_POST['description'] ?? '');
    $detail = $conn->real_escape_string($_POST['fulldetail'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $units = (int) ($_POST['units'] ?? 0);

    // ============================
    // PREVENT DUPLICATE PRODUCT NAME
    // ============================
    $check = $conn->prepare("
        SELECT id FROM products
        WHERE LOWER(name) = LOWER(?)
        LIMIT 1
    ");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "error:Product name already exists.";
    }
    $check->close();

    // ❗ STOP if duplicate found
    if ($message !== "") {
        // do nothing, message will be shown
    }
    // ============================
    // CONTINUE ONLY IF NO ERROR
    // ============================
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            if (!is_dir('uploads'))
                mkdir('uploads', 0755, true);
            $new_name = uniqid('img_', true) . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("
                    INSERT INTO products
                    (name, category_id, type, description, full_detail, price, image_path, units)
                    VALUES (?,?,?,?,?,?,?,?)
                ");
                $stmt->bind_param("sssssdsi", $name, $cat_id, $type, $desc, $detail, $price, $upload_path, $units);

                $message = $stmt->execute()
                    ? "success:Product added successfully!"
                    : "error:Error saving product.";

                if ($message !== "success:Product added successfully!" && file_exists($upload_path)) {
                    @unlink($upload_path);
                }
                $stmt->close();
            } else {
                $message = "error:Failed to move uploaded file.";
            }
        } else {
            $message = "error:Invalid file type. Only JPG, PNG, WEBP allowed.";
        }
    } else if ($message === "") {
        $message = "error:Image upload failed or no image provided.";
    }
}


// Fetch categories & products
$categories_res = $conn->query("SELECT id,name FROM categories ORDER BY name ASC");
$products_res = $conn->query("
    SELECT p.id,p.name,p.type,p.description,p.price,p.image_path,p.units,c.name AS category_name,p.category_id
    FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Products | Romeo Pet Shop Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #d17878;
            --accent-dark: #b76161;
            --tan: #f8f1e4;
            --brown: #4b3621
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif
        }

        body {
            background: url('../../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: var(--brown)
        }

        nav {
            background-color: var(--tan);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08)
        }

        nav .logo {
            font-size: 22px;
            font-weight: 600;
            color: var(--brown)
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 20px
        }

        nav ul li a {
            color: var(--brown);
            text-decoration: none;
            font-weight: 600
        }

        nav ul li a:hover {
            color: var(--accent)
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: rgba(255, 250, 243, 0.95);
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            padding: 24px;
            display: flex;
            gap: 32px
        }

        .left {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08)
        }

        .right {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08)
        }

        h2 {
            margin-bottom: 16px;
            color: var(--brown)
        }

        .form-row {
            margin-bottom: 12px
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--brown)
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #d6a97f;
            font-size: 14px;
            background: #fff
        }

        textarea {
            min-height: 80px;
            resize: vertical
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            background: var(--accent);
            color: #fff;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            margin-top: 8px;
            transition: background .18s ease, transform .08s
        }

        .btn:hover {
            background: var(--accent-dark);
            transform: translateY(-1px)
        }

        .preview {
            display: block;
            margin-top: 10px;
            width: 150px;
            /* fixed width */
            height: 150px;
            /* fixed height, square */
            object-fit: cover;
            /* keeps aspect ratio, crops excess */
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 16px
        }

        .card {
            background: #fdfaf5;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06)
        }

        .card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px
        }

        .card .title {
            font-weight: 700;
            color: var(--brown);
            font-size: 15px
        }

        .card .meta {
            font-size: 13px;
            color: #6b5346
        }

        .card .price {
            font-weight: 700;
            color: var(--accent);
            font-size: 14px
        }

        .card .units {
            font-weight: 600;
            color: #333
        }

        .card .card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto
        }

        .btn-small {
            padding: 6px 10px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer
        }

        .btn-edit {
            background: var(--tan);
            color: var(--brown)
        }

        .btn-edit:hover {
            background: #efe6da
        }

        .btn-danger {
            background: #ffd6d6;
            color: #a33;
            border-radius: 8px
        }

        .btn-danger:hover {
            background: #ffc6c6
        }

        .search-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px
        }

        .search-bar input {
            flex: 1 1 150px;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #d6a97f
        }

        .search-bar select {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #d6a97f
        }

        .toast {
            position: fixed;
            right: 20px;
            top: 20px;
            z-index: 3000;
            padding: 12px 16px;
            border-radius: 8px;
            color: #fff;
            display: none;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18)
        }

        .toast.success {
            background: #2fa24a
        }

        .toast.error {
            background: #d9534f
        }

        @media(max-width:980px) {
            .container {
                flex-direction: column
            }
        }

        /* Popup overlay */
        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 5000;
            animation: fadeIn 0.2s ease;
        }

        /* Popup box */
        .popup-box {
            background: #fff8f2;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            text-align: center;
            animation: popIn 0.25s ease;
        }

        .popup-box h3 {
            color: #b76161;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .popup-box p {
            font-size: 14px;
            color: #6b5346;
            margin-bottom: 18px;
        }

        /* Buttons */
        .popup-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .popup-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .popup-btn.cancel {
            background: #e9e1d8;
            color: #4b3621;
        }

        .popup-btn.cancel:hover {
            background: #dcd1c6;
        }

        .popup-btn.delete {
            background: #d17878;
            color: white;
        }

        .popup-btn.delete:hover {
            background: #b76161;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes popIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
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

        /* ===== FORM ALIGNMENT FIX ===== */
        .form-row {
            width: 100%;
            margin-bottom: 14px;
        }

        .form-row input,
        .form-row select,
        .form-row textarea {
            width: 100%;
            height: 44px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d6a97f;
            font-size: 14px;
        }

        .form-row textarea {
            height: 88px;
            resize: none;
        }

        .price-wrapper {
            position: relative;
            width: 100%;
        }

        .price-wrapper span {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #555;
        }

        .price-wrapper input {
            padding-left: 42px;
        }

        .char-counter {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 4px;
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


    <div class="container">

        <!-- LEFT: Add Product -->

        <div class="left">
            <h2>Add New Product</h2>

            <p style="font-size:14px; color:#6b5346; margin-bottom:16px;">
                Add new products to the shop with details like name, category, type, description, price, units, and
                image.
            </p>


            <?php if (!empty($message)):
                $parts = explode(':', $message, 2);
                $type = $parts[0] ?? 'info';
                $msgtext = $parts[1] ?? $message;
                ?>
                <div id="serverMsg" data-type="<?= htmlspecialchars($type) ?>" data-msg="<?= htmlspecialchars($msgtext) ?>">
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-row">
                    <label>Product Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-row">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        $categories_res->data_seek(0);
                        while ($cat = $categories_res->fetch_assoc()):
                            ?>
                            <option value="<?= (int) $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <?php
                        $types = $conn->query("
                    SELECT DISTINCT type
                    FROM products
                    WHERE type IS NOT NULL AND type != ''
                    ORDER BY type ASC
                ");
                        while ($t = $types->fetch_assoc()):
                            ?>
                            <option value="<?= htmlspecialchars($t['type']) ?>">
                                <?= htmlspecialchars($t['type']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label>Description</label>
                    <textarea name="description" id="description" minlength="20" maxlength="255" required></textarea>
                    <div class="char-counter" id="descCount">0 / 255 characters</div>
                </div>
                <div class="form-row">
                    <label>Full Detail</label>
                    <textarea name="fulldetail" id="fulldetail" minlength="40" maxlength="255" required></textarea>
                    <div class="char-counter" id="detailCount">0 / 255 characters</div>
                </div>

                <div class="form-row">
                    <label>Price</label>
                    <div class="price-wrapper">
                        <span>RM</span>
                        <input type="number" name="price" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="form-row">
                    <label>Units</label>
                    <input type="number" name="units" min="0" value="0">
                </div>

                <div class="form-row">
                    <label>Upload Image</label>
                    <input type="file" name="image" id="imageInput" accept="image/*" required>
                    <img id="livePreview" class="preview" style="display:none;">

                </div>

                <button class="btn" type="submit">Add Product</button>
            </form>
        </div>


        <!-- RIGHT: Manage Products -->
        <div class="right">
            <h2>Manage Products</h2>
            <p style="font-size:14px; color:#6b5346; margin-bottom:16px;">
                View, edit stock units, or delete products. Filter products by name, type, or category.
            </p>


            <div class="search-bar">
                <input type="text" id="searchName" placeholder="Search by name/type">
                <select id="searchCategory">
                    <option value="">All Categories</option>
                    <?php $categories_res->data_seek(0);
                    while ($cat = $categories_res->fetch_assoc()): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button class="btn" id="btnSearch">Search</button>
                <button class="btn btn-edit" id="btnReset">Reset</button>
            </div>
            <div class="cards" id="cardsContainer">
                <?php while ($row = $products_res->fetch_assoc()): ?>
                    <div class="card fade-in" data-id="<?= (int) $row['id'] ?>"
                        data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>"
                        data-type="<?= htmlspecialchars(strtolower($row['type'])) ?>"
                        data-category="<?= (int) $row['category_id'] ?>">
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Product">
                        <div class="title"><?= htmlspecialchars($row['name']) ?></div>
                        <div class="meta"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?> •
                            <?= htmlspecialchars($row['type']) ?></div>
                        <div class="price">RM <?= number_format($row['price'], 2) ?></div>
                        <div class="units">Units: <span class="units-value"><?= (int) $row['units'] ?></span></div>
                        <div class="card-actions">
                            <button class="btn-small btn-edit" data-action="edit-units" data-id="<?= (int) $row['id'] ?>">Edit
                                Units</button>
                            <button class="btn-small btn-danger" data-action="delete"
                                data-id="<?= (int) $row['id'] ?>">Delete</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>

    <div id="toast" class="toast"></div>
    <!-- CUSTOM DELETE POPUP -->
    <div id="deletePopup" class="popup-overlay">
        <div class="popup-box">
            <h3>Delete Product?</h3>
            <p>This action cannot be undone.</p>

            <div class="popup-buttons">
                <button id="cancelDelete" class="popup-btn cancel">Cancel</button>
                <button id="confirmDelete" class="popup-btn delete">Delete</button>
            </div>
        </div>
    </div>
    <!-- EDIT UNITS POPUP -->
    <div id="unitsPopup" class="popup-overlay">
        <div class="popup-box">
            <h3>Edit Units</h3>
            <p>Update product stock amount.</p>

            <input id="unitsInput" type="number"
                style="width:100%; padding:10px; border-radius:8px; border:1px solid #d6a97f; margin-bottom:15px;">

            <div class="popup-buttons">
                <button id="cancelUnits" class="popup-btn cancel">Cancel</button>
                <button id="confirmUnits" class="popup-btn delete">Update</button>
            </div>
        </div>
    </div>

    <script>

        const imageInput = document.getElementById('imageInput'); const livePreview = document.getElementById('livePreview');
        imageInput.addEventListener('change', () => { const file = imageInput.files[0]; if (!file) { livePreview.style.display = 'none'; return; } livePreview.src = URL.createObjectURL(file); livePreview.style.display = 'block'; });

        // Server message toast
        const serverMsgDiv = document.getElementById('serverMsg'); const toast = document.getElementById('toast');
        if (serverMsgDiv) {
            const t = serverMsgDiv.dataset.type; const m = serverMsgDiv.dataset.msg; showToast(m, t === 'success' ? 'success' : 'error'); if (t === 'success') setTimeout(() => window.location.href = 'add_products.php', 3000);
        }
        function showToast(msg, type = 'success') { toast.innerText = msg; toast.className = 'toast ' + (type === 'success' ? 'success' : 'error'); toast.style.display = 'block'; setTimeout(() => toast.style.opacity = '1', 10); setTimeout(() => toast.style.display = 'none', 3500); }

        // Search/filter
        document.getElementById('btnSearch').addEventListener('click', () => {
            const nameVal = document.getElementById('searchName').value.toLowerCase();
            const catVal = document.getElementById('searchCategory').value;
            document.querySelectorAll('#cardsContainer .card').forEach(c => {
                const name = c.dataset.name, type = c.dataset.type, cat = c.dataset.category;
                c.style.display = (name.includes(nameVal) || type.includes(nameVal)) && (!catVal || catVal === cat) ? 'flex' : 'none';
            });
        });
        document.getElementById('btnReset').addEventListener('click', () => {
            document.getElementById('searchName').value = ''; document.getElementById('searchCategory').value = '';
            document.querySelectorAll('#cardsContainer .card').forEach(c => c.style.display = 'flex');
        });
        // =========================
        // EDIT UNITS POPUP SYSTEM
        // =========================
        let editUnitsId = null;
        const unitsPopup = document.getElementById("unitsPopup");
        const unitsInput = document.getElementById("unitsInput");
        const confirmUnitsBtn = document.getElementById("confirmUnits");
        const cancelUnitsBtn = document.getElementById("cancelUnits");

        // Open popup
        document.querySelectorAll("[data-action='edit-units']").forEach(btn => {
            btn.addEventListener("click", () => {
                editUnitsId = btn.dataset.id;

                const card = btn.closest(".card");
                const currentUnits = card.querySelector(".units-value").innerText;

                unitsInput.value = currentUnits;
                unitsPopup.style.display = "flex";
            });
        });

        // Cancel
        cancelUnitsBtn.addEventListener("click", () => {
            unitsPopup.style.display = "none";
            editUnitsId = null;
        });

        // Confirm update
        confirmUnitsBtn.addEventListener("click", () => {
            const newUnits = unitsInput.value;

            const formData = new FormData();
            formData.append("action", "update_units");
            formData.append("id", editUnitsId);
            formData.append("units", newUnits);

            fetch("", { method: "POST", body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        document
                            .querySelector(`.card[data-id="${editUnitsId}"] .units-value`)
                            .innerText = newUnits;

                        showToast("Units updated!", "success");
                    } else {
                        showToast("Failed to update units", "error");
                    }

                    unitsPopup.style.display = "none";
                    editUnitsId = null;
                })
                .catch(() => {
                    showToast("Error updating units", "error");
                    unitsPopup.style.display = "none";
                });
        });


        // =========================
        // DELETE PRODUCT
        // =========================

        let deleteId = null;
        const popup = document.getElementById("deletePopup");
        const confirmDeleteBtn = document.getElementById("confirmDelete");
        const cancelDeleteBtn = document.getElementById("cancelDelete");

        // Open popup
        document.querySelectorAll("[data-action='delete']").forEach(btn => {
            btn.addEventListener("click", () => {
                deleteId = btn.dataset.id;
                popup.style.display = "flex"; // show popup
            });
        });

        // Close popup
        cancelDeleteBtn.addEventListener("click", () => {
            popup.style.display = "none";
            deleteId = null;
        });

        // Confirm delete
        confirmDeleteBtn.addEventListener("click", () => {
            if (!deleteId) return;

            const formData = new FormData();
            formData.append("action", "delete_product");
            formData.append("id", deleteId);

            fetch("", { method: "POST", body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        document.querySelector(`.card[data-id="${deleteId}"]`).remove();
                        showToast("Product deleted!", "success");
                    } else {
                        showToast("Failed to delete product", "error");
                    }

                    popup.style.display = "none";
                    deleteId = null;
                })
                .catch(() => {
                    showToast("Error deleting product!", "error");
                    popup.style.display = "none";
                });
        });


    </script>
    <script>
        /* =========================
           DESCRIPTION CHAR COUNTER
           ========================= */
        document.addEventListener("DOMContentLoaded", () => {
            const desc = document.getElementById("description");
            const counter = document.getElementById("descCount");

            if (!desc || !counter) return;

            const updateCount = () => {
                counter.textContent = `${desc.value.length} / 255 characters`;
            };

            // Initial load (refresh / validation error)
            updateCount();

            // Live typing
            desc.addEventListener("input", updateCount);
        });
    </script>
    <script>
        /* =========================
           DESCRIPTION CHAR COUNTER
           ========================= */
        document.addEventListener("DOMContentLoaded", () => {
            const desc = document.getElementById("fulldetail");
            const counter = document.getElementById("detailCount");

            if (!desc || !counter) return;

            const updateCount = () => {
                counter.textContent = `${desc.value.length} / 255 characters`;
            };

            // Initial load (refresh / validation error)
            updateCount();

            // Live typing
            desc.addEventListener("input", updateCount);
        });
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
<?php $conn->close(); ?>