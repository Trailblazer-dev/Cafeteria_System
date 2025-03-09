<?php
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

// Get page title from calling file or use default
$pageTitle = $pageTitle ?? 'Cafeteria Management System';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $pageTitle ?> - Cafeteria Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
