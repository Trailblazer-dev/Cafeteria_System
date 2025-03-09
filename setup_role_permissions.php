<?php
/**
 * Role Permissions Setup
 * Allows configuration of permissions for each role
 */
include 'includes/config.php';
include 'includes/db.php';
include 'includes/PermissionHandler.php';
include 'includes/functions.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'R001') {
    header("Location: unauthorized.php");
    exit;
}

// Initialize permission handler
$permHandler = new PermissionHandler($conn, $_SESSION['staff_id']);

// Process form submissions for permissions setup
$success_messages = [];
$error_messages = [];
$debug_info = []; // Store debug information to show conditionally

// Check database schema
$tableSchema = [
    'Role_Table' => [
        'exists' => false,
        'columns' => []
    ],
    'permissions' => [
        'exists' => false,
        'columns' => []
    ]
];

// Check if Role_Table exists and get its columns
$result = $conn->query("SHOW TABLES LIKE 'Role_Table'");
if ($result && $result->num_rows > 0) {
    $tableSchema['Role_Table']['exists'] = true;
    $columns = $conn->query("DESCRIBE Role_Table");
    while ($col = $columns->fetch_assoc()) {
        $tableSchema['Role_Table']['columns'][$col['Field']] = $col['Type'];
    }
}

// Check if permissions table exists
$result = $conn->query("SHOW TABLES LIKE 'permissions'");
if ($result && $result->num_rows > 0) {
    $tableSchema['permissions']['exists'] = true;
    $permissionTableName = 'permissions';
    
    // Get columns for the permission table
    $columns = $conn->query("DESCRIBE permissions");
    if ($columns) {
        while ($col = $columns->fetch_assoc()) {
            $tableSchema['permissions']['columns'][$col['Field']] = $col['Type'];
        }
    }
}

// Handle setup of admin permissions
if (isset($_POST['setup_admin'])) {
    try {
        // Setup admin permissions
        $permHandler->setupAdminPermissions();
        $success_messages[] = "Admin permissions configured successfully.";
        
        // Collect debug info
        $debug_info['admin_setup'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $_SESSION['username']
        ];
    } catch (Exception $e) {
        $error_messages[] = "Error setting up admin permissions: " . $e->getMessage();
    }
}

// Get all roles for display - handle different column names
$roles = [];
try {
    // First, get the actual column names from Role_Table
    $result = $conn->query("DESCRIBE Role_Table");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Check if the table has role_name and role_description fields
    $hasRoleName = in_array('role_name', $columns);
    $hasRoleDescription = in_array('role_description', $columns);
    
    // Use Role_Name and Role_Description if role_name and role_description don't exist
    if (!$hasRoleName && in_array('Role_Name', $columns)) {
        $hasRoleName = true;
        $roleName = 'Role_Name';
    } else {
        $roleName = 'role_name';
    }
    
    if (!$hasRoleDescription && in_array('Role_Description', $columns)) {
        $hasRoleDescription = true;
        $roleDesc = 'Role_Description';
    } else {
        $roleDesc = 'role_description';
    }
    
    // Build the query based on available columns
    $query = "SELECT Role_Id";
    if ($hasRoleName) {
        $query .= ", $roleName";
    }
    if ($hasRoleDescription) {
        $query .= ", $roleDesc";
    }
    $query .= " FROM Role_Table ORDER BY Role_Id";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $roleId = $row['Role_Id'];
            $roles[$roleId] = [
                'Role_Id' => $roleId
            ];
            
            if ($hasRoleName) {
                $roles[$roleId]['role_name'] = $row[$roleName];
            } else {
                $roles[$roleId]['role_name'] = $roleId; // Use ID as name if name field doesn't exist
            }
            
            if ($hasRoleDescription) {
                $roles[$roleId]['role_description'] = $row[$roleDesc];
            } else {
                $roles[$roleId]['role_description'] = '';
            }
            
            // Get permissions for this role
            $role_perms = $permHandler->getRolePermissions($roleId);
            $roles[$roleId]['permissions'] = $role_perms;
        }
    }
} catch (Exception $e) {
    $error_messages[] = "Error fetching roles: " . $e->getMessage();
    $debug_info['role_error'] = $e->getMessage();
}

// Get all available permissions
$all_permissions = [];
try {
    // Use the correct table name 'permissions'
    $result = $conn->query("SELECT permission_id, permission_name FROM permissions ORDER BY permission_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $all_permissions[$row['permission_id']] = $row['permission_name'];
        }
    } else {
        $error_messages[] = "Query failed: " . $conn->error;
    }
} catch (Exception $e) {
    $error_messages[] = "Error fetching permissions: " . $e->getMessage();
    $debug_info['permissions_error'] = $e->getMessage();
}

// Debug info about schema
$debug_info['schema'] = $tableSchema;
$debug_info['columns'] = $columns ?? [];

// Page title for header
$pageTitle = "Role Permissions Setup";

include 'includes/components/header.php';
include 'includes/components/navigation.php';
?>

<div class="container mx-auto p-6">
    <?php printPageHeader("Role Permission Setup", "Role Permission Setup"); ?>
    
    <?php if (!empty($success_messages)): ?>
        <div id="successMessages" class="bg-green-100 text-green-700 p-4 rounded mb-6">
            <?php foreach ($success_messages as $message): ?>
                <div class="mb-1"><?= $message ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_messages)): ?>
        <div id="errorMessages" class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <?php foreach ($error_messages as $message): ?>
                <div class="mb-1"><?= $message ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Database Schema Info -->
    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded mb-6">
        <h3 class="text-lg font-semibold text-yellow-800">Database Schema Information</h3>
        <ul class="mt-2 space-y-1">
            <li><strong>Role Table:</strong> <?= $tableSchema['Role_Table']['exists'] ? '✓ Exists' : '✗ Missing' ?></li>
            <li><strong>Permission Table:</strong> <?= $tableSchema['permissions']['exists'] ? '✓ Exists' : '✗ Missing' ?></li>
        </ul>
        
        <?php if (!$tableSchema['permissions']['exists']): ?>
        <div class="mt-3">
            <form method="post">
                <button type="submit" name="create_tables" class="bg-blue-500 text-white px-4 py-2 rounded">
                    Create Permission Tables
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Role setup info -->
    <div class="bg-blue-50 border border-blue-200 p-4 rounded mb-6">
        <p class="text-blue-800 mb-2">
            This utility has set up permissions for all roles based on best practices for cafeteria operations.
            You can further customize these permissions in the Manage Security Permissions area.
        </p>
    </div>
    
    <!-- Roles and Permissions Summary Table -->
    <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
        <h3 class="text-lg font-bold p-4 bg-gray-50 border-b">Role Permission Summary</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($roles as $role_id => $role): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($role_id) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($role['role_name']) ?></td>
                            <td class="px-6 py-4">
                                <?php if (!empty($role['permissions'])): ?>
                                    <?php foreach ($role['permissions'] as $perm): ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1 mb-1">
                                            <?= htmlspecialchars(is_array($perm) ? $perm['permission_name'] : $perm) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-gray-500">Limited access (basic dashboard only)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4">
        <a href="dashboard.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
            <i class="fas fa-arrow-left mr-2"></i> Return to Dashboard
        </a>
        
        <a href="view_staff_permissions.php" class="bg-purple-500 text-white px-6 py-2 rounded-lg hover:bg-purple-600 transition duration-300">
            <i class="fas fa-id-card-alt mr-2"></i> View Staff Permissions
        </a>
        
        <!-- Setup Admin Permissions Button -->
        <form method="post" class="inline">
            <button type="submit" name="setup_admin" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition duration-300">
                <i class="fas fa-user-shield mr-2"></i> Setup Admin Permissions
            </button>
        </form>
        
        <?php if (SHOW_DEBUG): // Only show in development mode ?>
        <button 
            id="showDebugBtn" 
            class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-300"
            onclick="document.getElementById('debugInfo').classList.toggle('hidden')">
            <i class="fas fa-bug mr-2"></i> Show Debug Info
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (SHOW_DEBUG || !empty($error_messages)): // Show in development mode or if there are errors ?>
    <!-- Debug Information (Hidden by Default) -->
    <div id="debugInfo" class="<?= empty($error_messages) ? 'hidden' : '' ?> mt-8 bg-gray-100 p-4 rounded-lg border border-gray-300">
        <h3 class="text-lg font-bold mb-4">Debug Information</h3>
        <pre class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto"><?= htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT)) ?></pre>
        
        <div class="mt-4">
            <h4 class="font-bold mb-2">Database Diagnostics:</h4>
            <ul class="list-disc ml-6">
                <li>Roles table: <?= count($roles) ?> entries</li>
                <li>Permissions table: <?= count($all_permissions) ?> entries</li>
                <li>Permission table name: <?= $permissionTableName ?></li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
include 'includes/components/footer.php';
includeAutoHideScript();
?>
