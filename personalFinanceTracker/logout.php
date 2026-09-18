<?php
include("db.php");

// 1. Erase the token from the database so it can't be used again
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    mysqli_query($conn, "UPDATE users SET remember_token = NULL WHERE email = '$email'");
}

// 2. Kill the cookie on their computer by setting its expiration date to the past
setcookie("remember_me", "", time() - 3600, "/");

// 3. Destroy the normal browser session
session_unset();
session_destroy();

// 4. Redirect to login
header("Location: login.php");
exit();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

</body>

</html>