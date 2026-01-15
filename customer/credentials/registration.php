<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$connection = mysqli_connect("localhost", "root", "root", "petshop") or die("Connection failed");

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim(mysqli_real_escape_string($connection, $_POST["full_name"]));
    $email = trim(mysqli_real_escape_string($connection, $_POST["email"]));
    $phone = trim(mysqli_real_escape_string($connection, $_POST["phone_number"]));
    $password = trim(mysqli_real_escape_string($connection, $_POST["password"]));
    $confirm_password = trim(mysqli_real_escape_string($connection, $_POST["confirm_password"]));
    $terms = isset($_POST['terms']) ? true : false;

    if (!preg_match("/^[a-zA-Z ]{2,50}$/", $full_name))
        $errors['full_name'] = "Full name must contain only letters (2–50 chars).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors['email'] = "Invalid email format.";
    if (!preg_match("/^01[0-46-9]-?\d{7,8}$/", $phone))
        $errors['phone_number'] = "Invalid Malaysian phone number. Ex: 019-2832974";
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password))
        $errors['password'] = "Password must be 8+ chars with uppercase, lowercase, number & special char.";
    if ($password !== $confirm_password)
        $errors['confirm_password'] = "Passwords do not match.";
    if (!$terms)
        $errors['terms'] = "You must agree to the Terms & Conditions.";

    $check = mysqli_query($connection, "SELECT * FROM users WHERE email='$email' OR phone_number='$phone'");
    if (mysqli_num_rows($check) > 0)
        $errors['exists'] = "Email or phone number already registered.";

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into DB
        $sql = "INSERT INTO users (full_name, email, password, phone_number) 
            VALUES ('$full_name','$email','$hashed_password','$phone')";

        if (mysqli_query($connection, $sql)) {
            $user_id = mysqli_insert_id($connection);
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $full_name;
            $success = "Successfully registered! Redirecting...";
            header("refresh:2;url=../../system/mainpage.php");
            exit();
        } else {
            $errors['general'] = "Registration failed. Try again.";
        }
    }

}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register | Romeo Pet Shop</title>
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
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 20px;
        }

        .shop-title {
            text-align: center;
            margin-bottom: 25px;
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

        .register-container {
            background: rgba(255, 250, 243, 0.95);
            width: 620px;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
            text-align: center;
            margin-top: 20px;
        }

        .register-container h2 {
            font-size: 28px;
            color: #4b3621;
            margin-bottom: 25px;
            font-weight: 600;
        }

        form {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .column {
            flex: 1;
            min-width: 250px;
        }

        .input-group {
            text-align: left;
            margin-bottom: 15px;
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
            padding: 12px;
            font-size: 14px;
            border: 1px solid #d69d6a;
            border-radius: 10px;
            background: #fff;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #d17878;
            outline: none;
            box-shadow: 0 0 5px rgba(209, 120, 120, 0.5);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 8px;
        }

        .checkbox-group input[type=checkbox] {
            transform: scale(1.1);
            margin: 0;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #d17878;
            border: none;
            color: #fff;
            font-size: 15px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #b76161;
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

        .success-msg {
            color: #28a745;
            background: #e6ffed;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .login-link {
            font-size: 14px;
            color: #4b3621;
            margin-top: 15px;
        }

        .login-link a {
            color: #d17878;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            width: 100%;
            color: #160e02ff;
            font-size: 13px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
            margin-top: 20px;
        }

        @media(max-width:650px) {
            form {
                flex-direction: column;
            }

            .column {
                min-width: 100%;
            }

            .shop-title h1 {
                font-size: 40px;
            }
        }
    </style>
</head>

<body>

    <div class="shop-title">
        <h1>Romeo Pet Shop</h1>
    </div>

    <div class="register-container">
        <h2>Create an Account</h2>

        <?php if ($success): ?>
            <div class="success-msg"><?= $success ?></div><?php endif; ?>
        <?php if (isset($errors['exists'])): ?>
            <div class="error-msg"><?= $errors['exists'] ?></div><?php endif; ?>
        <?php if (isset($errors['general'])): ?>
            <div class="error-msg"><?= $errors['general'] ?></div><?php endif; ?>

        <form method="POST" action="registration.php">
            <!-- Left Column -->
            <div class="column">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="error-msg"><?= $errors['full_name'] ?></div><?php endif; ?>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-msg"><?= $errors['email'] ?></div><?php endif; ?>
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" required
                        value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
                    <?php if (isset($errors['phone_number'])): ?>
                        <div class="error-msg"><?= $errors['phone_number'] ?></div><?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="column">
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-msg"><?= $errors['password'] ?></div><?php endif; ?>
                </div>
                <div class="input-group">
                    <label>Re-enter Password</label>
                    <input type="password" name="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="error-msg"><?= $errors['confirm_password'] ?></div><?php endif; ?>
                </div><br>
                <div class="checkbox-group">
                    <input type="checkbox" name="terms" id="terms" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                    <label for="terms" style="margin:0; font-size:14px;">I agree to the <a
                            href="termsncondition.php">Terms & Conditions</a></label>
                </div>
                <?php if (isset($errors['terms'])): ?>
                    <div class="error-msg"><?= $errors['terms'] ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
    </div>

    <footer>© <?= date("Y") ?> Romeo Pet Shop. All Rights Reserved.</footer>

</body>

</html>