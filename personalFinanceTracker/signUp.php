<?php
include("db.php");

function clean($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$message = "";
$goodMessage = "";
if (isset($_POST["signup"])) {
    $name = clean($_POST['fullname']);
    $email = clean($_POST['email']);
    $password = clean($_POST['password']);
    $confirm = clean($_POST['confirm']);

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    if (empty($email)) {
        $message = "Email account is required";
    } elseif (empty($name)) {
        $message = "Name is required";
    } elseif (empty($password)) {
        $message = "Password required";
    } elseif (mysqli_num_rows($result) > 0) {
        $message = "Email account already exists";
    } elseif ($password != $confirm) {
        $message = "Password does not match";
    } else {
        $name = ucwords($name);
        $hash = password_hash($password, PASSWORD_DEFAULT); // HASH MUNA
        $insert = "INSERT INTO users(email, password, name) VALUES ('$email', '$hash', '$name')"; // HASH ILALAGAY 
        if (mysqli_query($conn, $insert)) { // INSERT
            $goodMessage = "Account Created Successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Finance Tracker</title>
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
        .signup-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Side - Image Background */
        .signup-image {
            flex: 1.2;
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

        .signup-image h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .signup-image p {
            font-size: 1.2rem;
            max-width: 400px;
            color: var(--bg-color);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Right Side - Form */
        .signup-form-container {
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

        .input-group {
            margin-bottom: 1.2rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.4rem;
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
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: var(--dark-slate);
        }

        .terms-check input {
            margin-top: 0.2rem;
        }

        .terms-check a {
            color: var(--medium-green);
            text-decoration: none;
            font-weight: 600;
        }

        .terms-check a:hover {
            color: var(--deep-green);
            text-decoration: underline;
        }

        .btn-signup {
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

        .btn-signup:hover {
            background-color: var(--dark-slate);
        }

        .login-link {
            text-align: center;
            font-size: 0.9rem;
            color: var(--dark-slate);
        }

        .login-link a {
            color: var(--deep-green);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .signup-container {
                flex-direction: column;
            }

            .signup-image {
                flex: none;
                height: 25vh;
                padding: 1rem;
            }

            .signup-image h1 {
                font-size: 2rem;
            }

            .signup-image p {
                display: none;
            }

            .signup-form-container {
                align-items: flex-start;
                padding-top: 2rem;
                padding-bottom: 3rem;
            }
        }
    </style>
</head>

<body>

    <div class="signup-container">

        <!-- Left Banner Side -->
        <div class="signup-image">
            <h1>Join Us Today</h1>
            <p>Start your journey to better financial health and smarter money management.</p>
        </div>

        <!-- Right Form Side -->
        <div class="signup-form-container">
            <div class="form-wrapper">
                <h2>Create an Account</h2>
                <p>Sign up to start tracking your finances.</p>

                <!-- Dynamic Message Handlers -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (!empty($goodMessage)): ?>
                    <div class="alert alert-success"><?php echo $goodMessage; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="input-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" placeholder="John Doe" name="fullname" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" placeholder="you@example.com" name="email" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" placeholder="••••••••" name="password" required>
                    </div>

                    <div class="input-group">
                        <label for="confirm">Confirm Password</label>
                        <input type="password" id="confirm" placeholder="••••••••" name="confirm" required>
                    </div>

                    <div class="form-actions">
                        <label class="terms-check">
                            <input type="checkbox" name="terms" required>
                            <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-signup" name="signup">Sign Up</button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in here</a>
                </div>
            </div>
        </div>

    </div>

</body>

</html>