<?php
include 'includes/db.php';
include 'includes/PermissionHandler.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

// Create permission handler
$permHandler = new PermissionHandler($conn, $_SESSION['staff_id']);

try {
    // Ensure permission tables exist - wrap in try/catch for better error handling
    $tablesCreated = PermissionHandler::ensureTablesExist($conn);
    if ($tablesCreated) {
        $_SESSION['message'] = "Permission tables were created successfully.";
    }
    
    // Check if user has permission to manage security
    if (!$permHandler->hasPermission('manage_roles') && $_SESSION['role'] !== 'R001') {
        header("Location: unauthorized.php");
        exit;
    }
    
    // Handle form submissions for updating permissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_permissions'])) {
            $role_id = sanitize($_POST['role_id']);
            $permissions = isset($_POST['permissions']) ? sanitize($_POST['permissions']) : [];
            
            // Update permissions
            if ($permHandler->updateRolePermissions($role_id, $permissions)) {
                $_SESSION['message'] = "Permissions updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update permissions. Please try again.";
            }
        }
    }
    
    // Fetch all roles
    $roles = [];
    $roles_result = $conn->query("SELECT * FROM Role_Table ORDER BY Role_Id");
    if (!$roles_result) {
        throw new Exception("Failed to fetch roles: " . $conn->error);
    } else {
        $roles = $roles_result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get selected role's permissions
    $selected_role_id = isset($_GET['role_id']) ? sanitize($_GET['role_id']) : (isset($roles[0]) ? $roles[0]['Role_Id'] : '');
    
    // Get all permissions and role permissions
    $all_permissions = $permHandler->getAllPermissions();
    $role_permissions = [];
    
    if ($selected_role_id) {
        $role_perms = $permHandler->getRolePermissions($selected_role_id);
        foreach ($role_perms as $perm) {
            $role_permissions[] = $perm['permission_id'];
        }
    }
    
} catch (Exception $e) {
    // Log the error and show a friendly message
    error_log("Error in manage_security_permissions.php: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Security Permissions</title>
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
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                <a href="logout.php" class="hover:underline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Breadcrumb navigation -->
        <div class="text-sm mb-6">
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Manage Security Permissions</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Manage Security Permissions</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div id="successMessage" class="bg-green-100 text-green-700 p-4 rounded mb-6 flex justify-between items-center">
                    <div><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <button onclick="document.getElementById('successMessage').style.display='none'" class="text-green-700 hover:text-green-900">×</button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div id="errorMessage" class="bg-red-100 text-red-700 p-4 rounded mb-6 flex justify-between items-center">
                    <div><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <button onclick="document.getElementById('errorMessage').style.display='none'" class="text-red-700 hover:text-red-900">×</button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="bg-yellow-50 border border-yellow-300 rounded p-4 mb-6">
                <p class="text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Assign permissions to different roles in the system. The Admin role (R001) always has all permissions.
                </p>
            </div>

            <!-- Role Selection -->
            <?php if (!empty($roles)): ?>
            <div class="mb-8">
                <form method="get" action="" class="flex flex-wrap items-center gap-4">
                    <label for="role_id" class="font-semibold">Select Role:</label>
                    <select name="role_id" id="role_id" class="p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                        <?php foreach($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['Role_Id']) ?>" <?= $role['Role_Id'] === $selected_role_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['Role_Id']) ?> - <?= htmlspecialchars($role['Role']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <!-- Permissions Form -->
            <?php if ($selected_role_id): ?>
                <form method="post" action="">
                    <input type="hidden" name="role_id" value="<?= htmlspecialchars($selected_role_id) ?>">
                    
                    <h3 class="text-lg font-bold mb-4">Permissions for <?= htmlspecialchars($selected_role_id) ?></h3>
                    
                    <?php if ($selected_role_id === 'R001'): ?>
                        <div class="bg-blue-50 border border-blue-300 rounded p-4 mb-6">
                            <p class="text-blue-800">
                                <i class="fas fa-lock mr-2"></i>
                                The Admin role automatically has all permissions and cannot be modified.
                            </p>
                        </div>
                        
                        <!-- Display admin permissions as read-only -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            <?php foreach ($all_permissions as $permission): ?>
                                <div class="bg-gray-50 p-3 rounded border flex items-center">
                                    <label class="flex items-center w-full">
                                        <input 
                                            type="checkbox" 
                                            class="mr-3 h-5 w-5"
                                            checked
                                            disabled
                                        >
                                        <span><?= htmlspecialchars($permission['permission_name']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!empty($all_permissions)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            <?php foreach ($all_permissions as $permission): ?>
                                <div class="bg-gray-50 p-3 rounded border flex items-center">
                                    <label class="flex items-center cursor-pointer w-full">
                                        <input 
                                            type="checkbox" 
                                            name="permissions[]" 
                                            value="<?= htmlspecialchars($permission['permission_id']) ?>" 
                                            class="mr-3 h-5 w-5"
                                            <?= in_array($permission['permission_id'], $role_permissions) ? 'checked' : '' ?>
                                        >
                                        <span><?= htmlspecialchars($permission['permission_name']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_permissions" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
                                <i class="fas fa-save mr-2"></i> Save Permissions
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="bg-red-100 border border-red-300 rounded p-4">
                            <p class="text-red-800">No permissions found. Please check database configuration.</p>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
            <?php else: ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <p>No roles found in the system. Please create roles first.</p>
                    <p class="mt-2">
                        <a href="manage_staff_roles.php" class="underline">Go to Manage Staff Roles</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success and error messages after 5 seconds
        setTimeout(() => {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            if (successMessage) successMessage.style.display = 'none';
            if (errorMessage) errorMessage.style.display = 'none';
        }, 5000);
    </script>
</body>
</html>
