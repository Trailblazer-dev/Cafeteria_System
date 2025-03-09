<?php
include 'includes/db.php';
include 'includes/PermissionHandler.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'R001') {
    header("Location: unauthorized.php");
    exit;
}

// Initialize permission handler
$permHandler = new PermissionHandler($conn);

// Ensure permission tables exist
try {
    // Check if permission tables exist and create them if needed
    $tablesExist = $permHandler::ensureTablesExist($conn);
    
    // Get diagnostic information about permissions
    $diagnostics = [];
    
    // Check if permissions table has entries
    $permCount = $conn->query("SELECT COUNT(*) as count FROM permissions");
    if ($permCount && $row = $permCount->fetch_assoc()) {
        $diagnostics['permissions_count'] = $row['count'];
    } else {
        $diagnostics['permissions_count'] = 'Unable to count permissions';
    }
    
    // Check if role_permissions table has entries
    $rolePermCount = $conn->query("SELECT COUNT(*) as count FROM role_permissions");
    if ($rolePermCount && $row = $rolePermCount->fetch_assoc()) {
        $diagnostics['role_permissions_count'] = $row['count'];
    } else {
        $diagnostics['role_permissions_count'] = 'Unable to count role permissions';
    }
    
    // Fetch all staff with their roles - FIXED QUERY
    $staff_query = "SELECT s.staffId, 
                   s.firstName,
                   s.lastName, 
                   s.username, 
                   s.Role_Id, 
                   r.Role AS role_name,
                   c.Name AS cafeteria_name
                   FROM staff s 
                   LEFT JOIN Role_Table r ON s.Role_Id = r.Role_Id 
                   LEFT JOIN Cafeteria c ON s.Cafeteria_Id = c.Cafeteria_Id
                   ORDER BY s.Role_Id, s.firstName";
                   
    $staff_result = $conn->query($staff_query);
    
    if (!$staff_result) {
        throw new Exception("Error fetching staff: " . $conn->error);
    }
    
    $staff_list = [];
    while ($staff = $staff_result->fetch_assoc()) {
        // Create full name by combining firstName and lastName
        $staff['full_name'] = $staff['firstName'] . ' ' . $staff['lastName'];
        $staff_list[] = $staff;
    }
    
    // Get all roles and their permissions
    $roles_query = "SELECT * FROM Role_Table ORDER BY Role_Id";
    $roles_result = $conn->query($roles_query);
    
    if (!$roles_result) {
        throw new Exception("Error fetching roles: " . $conn->error);
    }
    
    $roles = [];
    while ($role = $roles_result->fetch_assoc()) {
        $role_id = $role['Role_Id'];
        $role['permissions'] = $permHandler->getRolePermissions($role_id);
        $roles[$role_id] = $role;
    }
    
    // Get staff table structure for diagnostics
    $staff_structure = [];
    $result = $conn->query("DESCRIBE staff");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $staff_structure[] = $row;
        }
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Check if we need to run permission setup
$needsSetup = false;
if (isset($diagnostics) && 
    ($diagnostics['permissions_count'] == 0 || 
     $diagnostics['role_permissions_count'] == 0)) {
    $needsSetup = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Permissions</title>
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
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Staff Permissions</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Staff Permissions</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
                
                <?php if (isset($staff_structure) && count($staff_structure) > 0): ?>
                    <div class="bg-gray-100 p-4 rounded mb-6">
                        <h3 class="font-bold mb-2">Staff Table Structure:</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 border-b">Field</th>
                                        <th class="py-2 px-4 border-b">Type</th>
                                        <th class="py-2 px-4 border-b">Null</th>
                                        <th class="py-2 px-4 border-b">Key</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_structure as $column): ?>
                                        <tr>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Field']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Type']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Null']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Key']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($needsSetup): ?>
                <div class="bg-yellow-100 border border-yellow-300 rounded p-4 mb-6">
                    <p class="text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Permissions setup needed!</strong> The system has detected that permissions are not properly set up.
                    </p>
                    <div class="mt-3">
                        <a href="setup_role_permissions.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-300">
                            <i class="fas fa-cog mr-2"></i> Run Permissions Setup
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- System Diagnostics -->
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-3">System Diagnostics</h3>
                <div class="bg-gray-50 rounded border p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="font-semibold">Permissions Table:</span> 
                            <?php if (isset($diagnostics['permissions_count'])): ?>
                                <?php if ($diagnostics['permissions_count'] > 0): ?>
                                    <span class="text-green-600"><?= $diagnostics['permissions_count'] ?> entries ✓</span>
                                <?php else: ?>
                                    <span class="text-red-600">0 entries ✗</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-red-600">Table not found ✗</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="font-semibold">Role Permissions Table:</span> 
                            <?php if (isset($diagnostics['role_permissions_count'])): ?>
                                <?php if ($diagnostics['role_permissions_count'] > 0): ?>
                                    <span class="text-green-600"><?= $diagnostics['role_permissions_count'] ?> entries ✓</span>
                                <?php else: ?>
                                    <span class="text-red-600">0 entries ✗</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-red-600">Table not found ✗</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs for Staff and Roles -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px" id="tabs" role="tablist">
                        <li class="mr-2" role="presentation">
                            <button class="inline-block py-2 px-4 font-semibold rounded-t-lg border-b-2 border-blue-600 text-blue-600 active" 
                                id="staff-tab" data-target="staff-tab-content" type="button" role="tab">
                                Staff Members
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block py-2 px-4 font-semibold rounded-t-lg border-b-2 border-transparent hover:border-gray-300" 
                                id="roles-tab" data-target="roles-tab-content" type="button" role="tab">
                                Roles & Permissions
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Staff Tab Content -->
                <div id="staff-tab-content" class="pt-4" role="tabpanel">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">ID</th>
                                    <th class="py-2 px-4 border-b text-left">Name</th>
                                    <th class="py-2 px-4 border-b text-left">Username</th>
                                    <th class="py-2 px-4 border-b text-left">Cafeteria</th>
                                    <th class="py-2 px-4 border-b text-left">Role</th>
                                    <th class="py-2 px-4 border-b text-left">Permissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($staff_list) && count($staff_list) > 0): ?>
                                    <?php foreach ($staff_list as $staff): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-2 px-4"><?= htmlspecialchars($staff['staffId']) ?></td>
                                            <td class="py-2 px-4"><?= htmlspecialchars($staff['full_name']) ?></td>
                                            <td class="py-2 px-4"><?= htmlspecialchars($staff['username']) ?></td>
                                            <td class="py-2 px-4"><?= htmlspecialchars($staff['cafeteria_name'] ?? 'N/A') ?></td>
                                            <td class="py-2 px-4">
                                                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                                    <?= htmlspecialchars($staff['Role_Id']) ?> - <?= htmlspecialchars($staff['role_name']) ?>
                                                </span>
                                            </td>
                                            <td class="py-2 px-4">
                                                <?php 
                                                if (isset($roles[$staff['Role_Id']]['permissions']) && 
                                                    count($roles[$staff['Role_Id']]['permissions']) > 0): 
                                                ?>
                                                    <?php foreach ($roles[$staff['Role_Id']]['permissions'] as $perm): ?>
                                                        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs mr-1 mb-1">
                                                            <?= htmlspecialchars($perm['permission_name']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-500">Limited access (basic dashboard only)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-4 px-4 text-center text-gray-500">No staff members found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Roles Tab Content -->
                <div id="roles-tab-content" class="pt-4 hidden" role="tabpanel">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">Role ID</th>
                                    <th class="py-2 px-4 border-b text-left">Role Name</th>
                                    <th class="py-2 px-4 border-b text-left">Permissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($roles) && count($roles) > 0): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-2 px-4"><?= htmlspecialchars($role['Role_Id']) ?></td>
                                            <td class="py-2 px-4"><?= htmlspecialchars($role['Role']) ?></td>
                                            <td class="py-2 px-4">
                                                <?php if (isset($role['permissions']) && count($role['permissions']) > 0): ?>
                                                    <?php foreach ($role['permissions'] as $perm): ?>
                                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1 mb-1">
                                                            <?= htmlspecialchars($perm['permission_name']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-500">Limited access (basic dashboard only)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="py-4 px-4 text-center text-gray-500">No roles found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex space-x-4">
                <a href="dashboard.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
                <a href="setup_role_permissions.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
                    <i class="fas fa-sync-alt mr-2"></i> Reset Permissions
                </a>
                <a href="manage_security_permissions.php" class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition duration-300">
                    <i class="fas fa-edit mr-2"></i> Customize Permissions
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        document.querySelectorAll('#tabs button').forEach(button => {
            button.addEventListener('click', () => {
                // Hide all tab contents
                document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                    panel.classList.add('hidden');
                });
                
                // Show the selected tab content
                document.getElementById(button.dataset.target).classList.remove('hidden');
                
                // Set active state on the clicked tab
                document.querySelectorAll('#tabs button').forEach(btn => {
                    btn.classList.remove('text-blue-600', 'border-blue-600');
                    btn.classList.add('border-transparent', 'hover:border-gray-300');
                });
                
                button.classList.add('text-blue-600', 'border-blue-600');
                button.classList.remove('border-transparent', 'hover:border-gray-300');
            });
        });
    </script>
</body>
</html>
