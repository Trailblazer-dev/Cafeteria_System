<?php
include 'includes/db.php';
start_session();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    // Query staff table for username
    $stmt = $conn->prepare("SELECT staffId, username, password_hash, Role_Id FROM staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $staff = $result->fetch_assoc();
        // Verify the password
        if(password_verify($password, $staff['password_hash'])) {
            // Save user details in session
            $_SESSION['staff_id'] = $staff['staffId'];
            $_SESSION['username'] = $staff['username'];
            $_SESSION['role'] = $staff['Role_Id']; // e.g. 'R001' for admin
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>

<!-- HTML for the login form -->
<!DOCTYPE html>
<html>
<head>
    <title>Admin/Staff Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4 text-center">Admin/Staff Login</h2>
        <?php if(isset($error)): ?>
            <div class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-semibold">Username</label>
                <input type="text" name="username" id="username" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-semibold">Password</label>
                <input type="password" name="password" id="password" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-green-500 text-white p-3 rounded-lg hover:bg-green-600 transition duration-300">Login</button>
        </form>
        <!-- Added Back Button -->
        <div class="absolute top-4 left-4">
            <a href="index.php" class="flex items-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-chevron-left mr-1"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
