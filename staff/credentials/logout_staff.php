<?php
session_start();
session_destroy(); // Destroy session
?>
<!DOCTYPE html>
<html>

<head>
    <title>Logging Out - Romeo Pet Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background: url('../../pictures/cat_wallpaper_2.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Poppins', sans-serif;
            color: #4b3621;
        }


        .loader-container {
            background: rgba(255, 250, 243, 0.95);
            padding: 40px 35px;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
            text-align: center;
            animation: fadeIn 0.7s ease-in-out;
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

        .loader {
            border: 6px solid #f8f1e4;
            border-top: 6px solid #d17878;
            border-radius: 50%;
            margin-left: 140px;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #190a01ff, #d17878);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.25);
        }

        p {
            font-size: 16px;
            color: #4b3621;
        }

        @media(max-width:420px) {
            .loader-container {
                padding: 30px 25px;
            }

            h1 {
                font-size: 24px;
            }

            .loader {
                width: 50px;
                height: 50px;
                border-width: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="loader-container">
        <div class="loader"></div>
        <h1>Logging Out...</h1>
        <p>Thank you for helping Romeo Pet Shop! 🐾</p>
    </div>

    <script>
        // Redirect after 2 seconds
        setTimeout(function () {
            window.location.href = "login_staff.php";
        }, 2000); // adjust time (ms) here
    </script>
</body>

</html>