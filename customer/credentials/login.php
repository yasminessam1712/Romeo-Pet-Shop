<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $connection = mysqli_connect("localhost", "root", "root", "petshop") or die("Connection failed");

    $email = mysqli_real_escape_string($connection, $_POST["email"]);
    $password = mysqli_real_escape_string($connection, $_POST["password"]);

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($connection, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        $login_error = "Email not found.";
    } else {
        $data = mysqli_fetch_assoc($result);

        // ✅ Check hashed password
        if (password_verify($password, $data['password'])) {
            $_SESSION["user_id"] = $data["user_id"];
            $_SESSION["username"] = $data["full_name"];
            header("Location: ../../system/mainpage.php");
            exit();
        } else {
            $login_error = "Incorrect password.";
        }
    }

    mysqli_close($connection);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login | Romeo Pet Shop</title>
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
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Shop title above login card */
        .shop-title {
            text-align: center;
            margin-bottom: 25px;
            animation: fadeIn 1s ease-in-out;
        }

        .shop-title h1 {
            font-size: 52px;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #190a01ff, #d17878);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.25);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Login card */
        .login-container {
            background: rgba(255, 250, 243, 0.95);
            width: 380px;
            padding: 45px 35px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
            text-align: center;
            animation: fadeIn 0.7s ease-in-out;
            position: relative;
            z-index: 1;
        }

        .login-container h2 {
            font-size: 28px;
            color: #4b3621;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .input-group {
            text-align: left;
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #4b3621;
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 14px;
            font-size: 14px;
            border: 1px solid #d69d6a;
            border-radius: 10px;
            background-color: #fff;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #d17878;
            box-shadow: 0 0 5px rgba(209, 120, 120, 0.5);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #d17878;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            background-color: #b76161;
            transform: translateY(-2px);
        }

        .error-msg {
            color: #d9534f;
            background: #ffe6e6;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .register-link {
            margin-top: 18px;
            font-size: 14px;
            color: #4b3621;
        }

        .register-link a {
            color: #d17878;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        footer {
            position: fixed;
            bottom: 12px;
            text-align: center;
            width: 100%;
            color: #160e02ff;
            font-size: 13px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        /* Loader overlay */
        #loginLoader {
            position: fixed;
            inset: 0;
            background-color: rgba(248, 241, 228, 0.95);
            /* soft cream */
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 9999;
            font-family: 'Poppins', sans-serif;
            color: #4b3621;
            text-align: center;
        }

        #loginLoader .loader {
            width: 70px;
            height: 70px;
            border: 6px solid #f8f1e4;
            border-top: 6px solid #d17878;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
            position: relative;
        }

        /* Add a cute paw overlay */
        #loginLoader .loader::after {
            content: "🐾";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 28px;
        }

        #loginLoader h2 {
            font-size: 20px;
            font-weight: 600;
            color: #d17878;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media(max-width:420px) {
            .login-container {
                width: 90%;
                padding: 35px 25px;
            }

            .shop-title h1 {
                font-size: 40px;
            }
        }
    </style>
</head>

<body>

    <!-- Shop Title -->
    <div class="shop-title">
        <h1>Romeo Pet Shop</h1>
    </div>

    <!-- Login Card -->
    <div class="login-container">
        <h2>Welcome Back</h2>

        <?php if (!empty($login_error)): ?>
            <div class="error-msg"><?php echo $login_error; ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="login.php">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="register-link">
            Don’t have an account? <a href="registration.php">Register here</a>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div id="loginLoader">
        <div class="loader"></div>
        <h2>Logging In...</h2>
    </div>

    <footer>
        © <?= date("Y") ?> Romeo Pet Shop. All Rights Reserved.
    </footer>

    <script>
        // Show loader when submitting the form

        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault(); // Stop immediate submission
            document.getElementById('loginLoader').style.display = 'flex';

            // Wait 2 seconds before submitting the form
            setTimeout(() => {
                e.target.submit(); // Submit the form after delay
            }, 2000); // 2000ms = 2 seconds
        });

    </script>
</body>

</html>