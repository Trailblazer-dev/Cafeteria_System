<?php 
include 'includes/db.php';
start_session();
error_reporting(E_ALL); 
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cafeteria System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-center">Welcome to the Cafeteria System</h2>
            <div class="flex justify-around">
                <a href="student_login.php" class="w-1/3 bg-blue-500 text-white p-3 rounded-lg text-center hover:bg-blue-600 transition duration-300">Student Login</a>
                <a href="login.php" class="w-1/3 bg-green-500 text-white p-3 rounded-lg text-center hover:bg-green-600 transition duration-300">Staff Login</a>
            </div>
        </div>
    </div>
</body>
</html>
