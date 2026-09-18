<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$conn = mysqli_connect("localhost", "root", "", "tracker");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!$conn) {
    die("Connection Failed");
}

// --- AUTOLOGIN CHECK ---
// Put this here so every page automatically checks for the cookie!
if (!isset($_SESSION['email']) && isset($_COOKIE['remember_me'])) {
    $cookieToken = mysqli_real_escape_string($conn, $_COOKIE['remember_me']);
    $checkQuery = "SELECT email FROM users WHERE remember_token = '$cookieToken'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        $foundUser = mysqli_fetch_assoc($checkResult);
        $_SESSION['email'] = $foundUser['email']; // Log them back in!
    } else {
        setcookie("remember_me", "", time() - 3600, "/"); // Delete invalid cookie
    }
}
