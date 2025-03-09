<?php
include 'includes/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

// Check permissions
$hasPermission = false;

// Admin role always has permission
if (isset($_SESSION['role']) && $_SESSION['role'] === 'R001') {
    $hasPermission = true;
} else {
    // Check if role_permissions table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'role_permissions'");
    if ($tableExists->num_rows > 0) {
        // Check for manage_menu_items permission
        $roleId = $_SESSION['role'];
        $permStmt = $conn->prepare("SELECT 1 FROM role_permissions WHERE Role_Id = ? AND permission_name = 'manage_menu_items'");
        if ($permStmt) {
            $permStmt->bind_param("s", $roleId);
            $permStmt->execute();
            $result = $permStmt->get_result();
            $hasPermission = ($result->num_rows > 0);
            $permStmt->close();
        }
    } else {
        // If permissions table doesn't exist, default to no permission for non-admins
        $hasPermission = false;
    }
}

if (!$hasPermission) {
    header("Location: unauthorized.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission here
    // This will be implemented later
}

// Get all items from the database
$items_query = "SELECT * FROM Item_table ORDER BY Item_Id";
$items_result = $conn->query($items_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Menu Items</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <div class="font-bold text-xl">Cafeteria Management System</div>
            <div class="space-x-4">
                <a href="dashboard.php" class="hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                <span class="text-sm">|</span>
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="hover:underline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Breadcrumb navigation -->
        <div class="text-sm mb-6">
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Update Menu Items</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Update Menu Items</h2>
            
            <p class="mb-6">This feature is under development. Please check back later.</p>
            
            <div class="flex justify-end">
                <a href="dashboard.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
