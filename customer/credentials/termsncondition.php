<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Terms & Conditions | Romeo Pet Shop</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 30px 20px;
        }

        .container {
            background: rgba(255, 250, 243, 0.95);
            width: 90%;
            max-width: 900px;
            padding: 30px 40px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
            overflow-y: auto;
            max-height: 80vh;
        }

        h1 {
            text-align: center;
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #190a01ff, #d17878);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.25);
        }

        h2 {
            font-size: 22px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #4b3621;
        }

        p,
        li {
            font-size: 14px;
            color: #4b3621;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        ul {
            margin-left: 20px;
            margin-bottom: 10px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: #d17878;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            margin-left: 300px;
        }

        .back-btn:hover {
            background: #b76161;
            transform: translateY(-2px);
        }

        footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #160e02ff;
        }

        @media(max-width:600px) {
            h1 {
                font-size: 32px;
            }

            .container {
                padding: 20px;
                max-height: 90vh;
            }
        }
    </style>
</head>

<body>

    <h1>Romeo Pet Shop</h1>
    <div class="container">
        <h2>Terms & Conditions</h2>
        <p>Effective Date: December 10, 2025</p>
        <p>Welcome to <strong>Romeo Pet Shop</strong>. By accessing or using our website, products, or services, you
            agree to comply with these Terms & Conditions. Please read them carefully.</p>

        <h2>1. Acceptance of Terms</h2>
        <p>By using this website, you acknowledge that you have read, understood, and agree to be bound by these Terms &
            Conditions and our Privacy Policy. If you do not agree, you must not use our services.</p>

        <h2>2. Products and Services</h2>
        <ul>
            <li>Romeo Pet Shop sells pet supplies, accessories, and related products.</li>
            <li>All product descriptions, images, and prices are provided as accurately as possible.</li>
            <li>We reserve the right to modify, discontinue, or remove any product at any time.</li>
        </ul>

        <h2>3. Account Registration</h2>
        <ul>
            <li>Users must provide accurate and complete information during registration.</li>
            <li>You are responsible for maintaining the confidentiality of your account and password.</li>
            <li>Any activity under your account is your responsibility.</li>
        </ul>

        <h2>4. Payment and Orders</h2>
        <ul>
            <li>Payments are processed securely through our selected payment gateways.</li>
            <li>All prices are in Malaysian Ringgit (MYR) and are subject to change without notice.</li>
            <li>Orders are accepted subject to product availability.</li>
        </ul>

        <h2>5. Shipping and Delivery</h2>
        <ul>
            <li>Shipping times may vary depending on location and product availability.</li>
            <li>Romeo Pet Shop is not responsible for delays caused by shipping providers or other external factors.
            </li>
        </ul>

        <h2>6. Returns and Refunds</h2>
        <ul>
            <li>Returns are accepted according to our Return Policy.</li>
            <li>Products must be returned in original condition, with packaging intact.</li>
            <li>Refunds are processed after the returned product is inspected.</li>
        </ul>

        <h2>7. User Conduct</h2>
        <ul>
            <li>You agree not to use the website for illegal activities or to post harmful content.</li>
            <li>You are responsible for your interactions with other users.</li>
        </ul>

        <a href="registration.php" class="back-btn">Back to Registration</a>
    </div>

    <footer>© <?= date("Y") ?> Romeo Pet Shop. All Rights Reserved.</footer>

</body>

</html>