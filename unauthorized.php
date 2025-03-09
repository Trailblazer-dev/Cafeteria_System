<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unauthorized Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container mx-auto p-6 max-w-md">
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <div class="text-red-500 text-5xl mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="text-2xl font-bold mb-4">Unauthorized Access</h2>
            <p class="mb-6">You don't have permission to access this page.</p>
            <div>
                <a href="dashboard.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300 inline-flex items-center">
                    <i class="fas fa-home mr-2"></i> Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
