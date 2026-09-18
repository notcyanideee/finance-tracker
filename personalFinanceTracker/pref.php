<?php
require_once "db.php";
$email = $_SESSION['email'];

// 1. Fetch user preferences
$prefQuery = "SELECT currency, date_format FROM users WHERE email = '$email'";
$prefResult = mysqli_query($conn, $prefQuery);
$userPrefs = mysqli_fetch_assoc($prefResult);

// 2. Map the 3-letter currency code to an actual symbol
$currencySymbols = [
    'USD' => '$',
    'EUR' => '€',
    'PHP' => '₱',
    'GBP' => '£'
];
$sym = $currencySymbols[$userPrefs['currency']] ?? '$'; // Defaults to $ if empty

// 3. Translate the dropdown values into actual PHP date() format strings
$dateFormatMap = [
    'MM/DD/YYYY' => 'm/d/Y',
    'DD/MM/YYYY' => 'd/m/Y',
    'YYYY-MM-DD' => 'Y-m-d'
];
$dateFormat = $dateFormatMap[$userPrefs['date_format']] ?? 'M d, Y';
