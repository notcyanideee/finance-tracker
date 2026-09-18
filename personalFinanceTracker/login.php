<?php
require_once("db.php");

function clean($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$message = "";
if (isset($_POST["login"])) {
    $email = clean($_POST['email']);
    $password = clean($_POST['password']);

    $sql = "SELECT * FROM users WHERE email='$email'"; // CHECK FOR USER
    $result = mysqli_query($conn, $sql);

    if (empty($email) && empty($password)) {
        $message = "Email and Password required";
    } elseif (empty($email)) {
        $message = "Email is required";
    } elseif (empty($password)) {
        $message = "Password required";
    } elseif (mysqli_num_rows($result) == 0) {
        $message = "Account does not exist";
    } else {
        $row = mysqli_fetch_assoc($result); // FETCH PARA KUNIN AND MA COMPARE
        if (password_verify($password, $row['password'])) {
            $_SESSION['email'] = $row['email'];

            // Check if "Remember Me" was ticked
            if (isset($_POST['remember'])) {
                // 1. Generate a secure, unique random token
                $token = bin2hex(random_bytes(32));

                // 2. Save this token to the database for this specific user
                $userEmail = $row['email'];
                mysqli_query($conn, "UPDATE users SET remember_token = '$token' WHERE email = '$userEmail'") or die("Database Error: " . mysqli_error($conn));

                // 3. Drop the cookie on their computer (lasts for 30 days)
                setcookie("remember_me", $token, time() + (60 * 60 * 24 * 30), "/", "", false, true);
            }

            header("Location: index.php");
            exit();
        } else {
            $message = "Incorrect password";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finance Tracker</title>
    <style>
        :root {
            --dark-slate: #273338;
            --deep-green: #2B5748;
            --medium-green: #618764;
            --light-olive: #9CB080;
            --bg-color: #f4f7f6;
            --white: #ffffff;
            --danger: #e74c3c;
            --danger-bg: #fce8e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--dark-slate);
            display: flex;
            min-height: 100vh;
        }

        /* Split Screen Layout */
        .login-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* Left Side - Image Background */
        .login-image {
            flex: 1.2;
            /* Using a high-quality Unsplash image that matches your green palette */
            background: linear-gradient(to right, rgba(43, 87, 72, 0.4), rgba(39, 51, 56, 0.6)),
                url('https://images.unsplash.com/photo-1513836279014-a89f7a76ae86?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }

        .login-image h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-image p {
            font-size: 1.2rem;
            max-width: 400px;
            color: var(--bg-color);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Right Side - Form */
        .login-form-container {
            flex: 1;
            background-color: var(--white);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .form-wrapper h2 {
            color: var(--dark-slate);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .form-wrapper p {
            color: var(--medium-green);
            margin-bottom: 2rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-slate);
            font-size: 0.9rem;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--light-olive);
            border-radius: 6px;
            font-size: 1rem;
            color: var(--dark-slate);
            outline: none;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: var(--deep-green);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-slate);
        }

        .forgot-password {
            color: var(--medium-green);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-password:hover {
            color: var(--deep-green);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background-color: var(--deep-green);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 1.5rem;
        }

        .btn-login:hover {
            background-color: var(--dark-slate);
        }

        .signup-link {
            text-align: center;
            font-size: 0.9rem;
            color: var(--dark-slate);
        }

        .signup-link a {
            color: var(--deep-green);
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        /* Alert Styling */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .alert-danger {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #facdca;
        }

        .alert-success {
            background-color: rgba(97, 135, 100, 0.15);
            color: var(--deep-green);
            border: 1px solid var(--light-olive);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-image {
                flex: none;
                height: 30vh;
                padding: 1rem;
            }

            .login-image h1 {
                font-size: 2rem;
            }

            .login-image p {
                display: none;
                /* Hide subtitle on small screens */
            }

            .login-form-container {
                height: 70vh;
                align-items: flex-start;
                padding-top: 3rem;
            }
        }
    </style>
</head>


<body>

    <div class="login-container">

        <!-- Left Banner Side -->
        <div class="login-image">
            <h1>Finance Tracker</h1>
            <p>Take control of your money, track your expenses, and reach your financial goals.</p>
        </div>

        <!-- Right Form Side -->
        <div class="login-form-container">
            <div class="form-wrapper">
                <h2>Welcome Back</h2>
                <p>Please enter your details to sign in.</p>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (!empty($goodMessage)): ?>
                    <div class="alert alert-success"><?php echo $goodMessage; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" placeholder="you@example.com" name="email" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" placeholder="••••••••" name="password" required>
                    </div>

                    <div class="form-actions">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            Remember me
                        </label>
                        <a href="none.php" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-login" name="login">Sign In</button>
                </form>

                <div class="signup-link">
                    Don't have an account? <a href="signUp.php">Sign up here</a>
                </div>
            </div>
        </div>

    </div>

</body>

</html>