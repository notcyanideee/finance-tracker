<?php
include("db.php");

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$successMessage = "";
$errorMessage = "";

// --- 1. HANDLE PROFILE UPDATE ---
if (isset($_POST['action_update_profile'])) {
    $newName = mysqli_real_escape_string($conn, $_POST['full_name']);
    $newEmail = mysqli_real_escape_string($conn, $_POST['new_email']);
    $newName = ucwords($newName);

    $updateQuery = "UPDATE users SET name = '$newName', email = '$newEmail' WHERE email = '$email'";
    if (mysqli_query($conn, $updateQuery)) {
        $_SESSION['email'] = $newEmail; // Update session if email changed
        $email = $newEmail; // Update local tracking variable
        $successMessage = "Profile updated successfully!";
    } else {
        $errorMessage = "Error updating profile.";
    }
}

// --- 2. HANDLE PREFERENCES UPDATE ---
if (isset($_POST['action_update_prefs'])) {
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $dateFormat = mysqli_real_escape_string($conn, $_POST['date_format']);

    $updateQuery = "UPDATE users SET currency = '$currency', date_format = '$dateFormat' WHERE email = '$email'";
    if (mysqli_query($conn, $updateQuery)) {
        $successMessage = "App preferences saved successfully!";
    } else {
        $errorMessage = "Error updating preferences.";
    }
}

// --- 3. HANDLE NOTIFICATIONS UPDATE ---
if (isset($_POST['action_update_notifs'])) {
    $emailSummary = isset($_POST['notif_email_summary']) ? 1 : 0;
    $spendingAlert = isset($_POST['notif_spending_alert']) ? 1 : 0;
    $budgetReminder = isset($_POST['notif_budget_reminder']) ? 1 : 0;

    $updateQuery = "UPDATE users SET notif_email_summary = '$emailSummary', notif_spending_alert = '$spendingAlert', notif_budget_reminder = '$budgetReminder' WHERE email = '$email'";
    if (mysqli_query($conn, $updateQuery)) {
        $successMessage = "Notification settings updated! BUT NOT YET WORKING";
    } else {
        $errorMessage = "Error updating notifications.";
    }
}

// --- 4. HANDLE PASSWORD CHANGE ---
if (isset($_POST['action_update_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    // Get current hashed password from database
    $passQuery = "SELECT password FROM users WHERE email = '$email'";
    $passResult = mysqli_query($conn, $passQuery);
    $passRow = mysqli_fetch_assoc($passResult);

    if (password_verify($currentPassword, $passRow['password'])) {
        $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET password = '$newHashed' WHERE email = '$email'";
        mysqli_query($conn, $updateQuery);
        $successMessage = "Password changed successfully!";
    } else {
        $errorMessage = "Incorrect current password. Password not changed.";
    }
}

// --- 5. HANDLE ACCOUNT DELETION ---
if (isset($_POST['action_delete_account'])) {
    // 1. Delete all user transactions first to prevent floating records
    mysqli_query($conn, "DELETE FROM transactions WHERE email = '$email'");
    // 2. Delete the user account
    mysqli_query($conn, "DELETE FROM users WHERE email = '$email'");

    // 3. Log them out and redirect
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- FETCH CURRENT USER DATA ---
$query = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Set default fallback values in case the columns are brand new and empty
$userCurrency = $user['currency'] ?? 'USD';
$userDateFormat = $user['date_format'] ?? 'YYYY-MM-DD';
$notifEmail = isset($user['notif_email_summary']) ? $user['notif_email_summary'] : 1;
$notifSpend = isset($user['notif_spending_alert']) ? $user['notif_spending_alert'] : 1;
$notifBudget = isset($user['notif_budget_reminder']) ? $user['notif_budget_reminder'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Trackery</title>
    <style>
        :root {
            --dark-slate: #273338;
            --deep-green: #2B5748;
            --medium-green: #618764;
            --light-olive: #9CB080;
            --bg-color: #f4f7f6;
            --white: #ffffff;
            --danger: #e74c3c;
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

        /* Sidebar Styles (Matching Theme) */
        .sidebar {
            width: 250px;
            background-color: var(--dark-slate);
            color: var(--white);
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            margin-bottom: 2rem;
            text-align: center;
            color: var(--light-olive);
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 1rem;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: block;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background-color: var(--deep-green);
            color: var(--light-olive);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: var(--deep-green);
            font-size: 1.8rem;
        }

        /* Settings Grid Layout */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .settings-card {
            background-color: var(--white);
            padding: 1.5rem 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--deep-green);
        }

        .settings-card h3 {
            color: var(--deep-green);
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(156, 176, 128, 0.3);
            padding-bottom: 0.5rem;
        }

        /* Form Elements */
        .input-group {
            margin-bottom: 1.2rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark-slate);
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid var(--light-olive);
            border-radius: 6px;
            font-size: 0.95rem;
            color: var(--dark-slate);
            outline: none;
            background-color: var(--white);
            transition: border-color 0.3s;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: var(--deep-green);
        }

        .btn-save {
            padding: 0.8rem 1.5rem;
            background-color: var(--deep-green);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 0.5rem;
        }

        .btn-save:hover {
            background-color: var(--dark-slate);
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            opacity: 0.8;
            background-color: var(--danger);
        }

        /* Toggle Switch CSS */
        .toggle-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .toggle-label {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--dark-slate);
        }

        .toggle-desc {
            display: block;
            font-size: 0.8rem;
            font-weight: normal;
            color: #777;
            margin-top: 0.2rem;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--medium-green);
        }

        input:checked+.slider:before {
            transform: translateX(22px);
        }

        .sticky-nav-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background-color: var(--deep-green, #2b5748);
            /* Fallback to a deep green if your variable isn't globally active here */
            color: #ffffff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
            z-index: 999;
            /* Keeps it layered above your cards and content tables */
        }

        .sticky-nav-btn:hover {
            background-color: var(--dark-slate, #273338);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .sticky-nav-btn:active {
            transform: translateY(0);
        }

        /* Creates a sharp chevron arrow pointing left using CSS borders */
        .arrow-left {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-left: 3px solid #ffffff;
            border-bottom: 3px solid #ffffff;
            transform: rotate(134deg);
            margin-left: 2px;
            /* Slight centering visual optical adjustment */
        }

        /* Responsive Breakpoint */
        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>Finance Tracker</h2>
        <ul class="nav-links">
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="transactions.php">Transactions</a></li>
            <li><a href="categories.php">Categories</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php" class="active">Settings</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="go-back">

        <div class="page-header">
            <h1>Account Settings</h1>
        </div>

        <!-- Alert Messages (Shows when form submits) -->
        <?php if ($successMessage): ?>
            <div style="background-color: var(--light-olive); color: white; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div style="background-color: var(--danger); color: white; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">

            <!-- Profile Settings -->
            <div class="settings-card" style="border-top-color: var(--deep-green);">
                <h3>Profile Information</h3>
                <form action="settings.php" method="POST">
                    <div class="input-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" name="full_name" id="fullName" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="emailAddress">Email Address</label>
                        <input type="email" name="new_email" id="emailAddress" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <button type="submit" name="action_update_profile" class="btn-save">Update Profile</button>
                </form>
            </div>

            <!-- Preferences -->
            <div class="settings-card" style="border-top-color: var(--medium-green);">
                <h3>App Preferences</h3>
                <form action="settings.php" method="POST">
                    <div class="input-group">
                        <label for="currency">Default Currency</label>
                        <select name="currency" id="currency">
                            <option value="USD" <?php echo $userCurrency == 'USD' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                            <option value="EUR" <?php echo $userCurrency == 'EUR' ? 'selected' : ''; ?>>EUR (€) - Euro</option>
                            <option value="PHP" <?php echo $userCurrency == 'PHP' ? 'selected' : ''; ?>>PHP (₱) - Philippine Peso</option>
                            <option value="GBP" <?php echo $userCurrency == 'GBP' ? 'selected' : ''; ?>>GBP (£) - British Pound</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="dateFormat">Date Format</label>
                        <select name="date_format" id="dateFormat">
                            <option value="MM/DD/YYYY" <?php echo $userDateFormat == 'MM/DD/YYYY' ? 'selected' : ''; ?>>MM/DD/YYYY (e.g., 10/28/2023)</option>
                            <option value="DD/MM/YYYY" <?php echo $userDateFormat == 'DD/MM/YYYY' ? 'selected' : ''; ?>>DD/MM/YYYY (e.g., 28/10/2023)</option>
                            <option value="YYYY-MM-DD" <?php echo $userDateFormat == 'YYYY-MM-DD' ? 'selected' : ''; ?>>YYYY-MM-DD (e.g., 2023-10-28)</option>
                        </select>
                    </div>

                    <button type="submit" name="action_update_prefs" class="btn-save">Save Preferences</button>
                </form>
            </div>

            <!-- Notifications -->
            <div class="settings-card" style="border-top-color: var(--light-olive);">
                <h3>Notifications</h3>

                <!-- Added a form wrap around notifications so they can be saved -->
                <form action="settings.php" method="POST">
                    <div class="toggle-group">
                        <div class="toggle-label">
                            Email Summaries
                            <span class="toggle-desc">Receive a weekly summary of your spending.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_email_summary" value="1" <?php echo $notifEmail ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <div class="toggle-label">
                            Unusual Spending Alerts
                            <span class="toggle-desc">Get notified if a transaction is abnormally high.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_spending_alert" value="1" <?php echo $notifSpend ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <div class="toggle-label">
                            Budget Reminders
                            <span class="toggle-desc">Alerts when you are close to your category limits.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notif_budget_reminder" value="1" <?php echo $notifBudget ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Added a save button for notifications -->
                    <button type="submit" name="action_update_notifs" class="btn-save" style="margin-top: 1rem;">Save Notifications</button>
                </form>
            </div>

            <!-- Security & Danger Zone -->
            <div class="settings-card" style="border-top-color: var(--dark-slate);">
                <h3>Security</h3>
                <form action="settings.php" method="POST">
                    <div class="input-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" name="current_password" id="currentPassword" placeholder="Enter current password" required>
                    </div>
                    <div class="input-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" required>
                    </div>
                    <button type="submit" name="action_update_password" class="btn-save" style="margin-bottom: 2rem;">Change Password</button>
                </form>

                <h3 style="color: var(--danger); border-bottom-color: rgba(231, 76, 60, 0.3);">Danger Zone</h3>
                <p style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--dark-slate);">
                    Once you delete your account, there is no going back. Please be certain.
                </p>

                <!-- Delete form with an onSubmit JavaScript confirmation box -->
                <form action="settings.php" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? All your data will be permanently lost.');">
                    <button type="submit" name="action_delete_account" class="btn-save btn-danger">Delete Account</button>
                </form>
            </div>

        </div>

    </main>
    <a href="#go-back">
        <button class="sticky-nav-btn" title="Go Back">
            <span class="arrow-left"></span>
        </button>
    </a>
</body>


</html>