<?php
include 'includes/db.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'R001') {
    header("Location: unauthorized.php");
    exit;
}

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";
$error = "";

// Function to execute SQL and handle errors
function executeSQL($conn, $sql, $description) {
    try {
        if ($conn->query($sql)) {
            return "<span class='text-green-600'>✓ $description successful</span><br>";
        } else {
            return "<span class='text-red-600'>✗ $description failed: " . $conn->error . "</span><br>";
        }
    } catch (Exception $e) {
        return "<span class='text-red-600'>✗ $description failed: " . $e->getMessage() . "</span><br>";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_tables'])) {
            // Create permissions table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS permissions (
                permission_id INT AUTO_INCREMENT PRIMARY KEY,
                permission_name VARCHAR(50) NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL
            )";
            $message .= executeSQL($conn, $sql, "Creating permissions table");
            
            // Create role_permissions table without foreign keys
            $sql = "CREATE TABLE IF NOT EXISTS role_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                Role_Id VARCHAR(10) NOT NULL,
                permission_id INT NOT NULL,
                UNIQUE KEY unique_role_permission (Role_Id, permission_id)
            )";
            $message .= executeSQL($conn, $sql, "Creating role_permissions table");
            
        } else if (isset($_POST['drop_tables'])) {
            // Drop tables in the correct order to avoid foreign key constraint issues
            $sql = "DROP TABLE IF EXISTS role_permissions";
            $message .= executeSQL($conn, $sql, "Dropping role_permissions table");
            
            $sql = "DROP TABLE IF EXISTS permissions";
            $message .= executeSQL($conn, $sql, "Dropping permissions table");
            
        } else if (isset($_POST['populate_permissions'])) {
            // Insert base permissions
            $default_permissions = [
                ['manage_cafeterias', 'Can add, edit, and delete cafeterias'],
                ['manage_menu', 'Can add, edit, and delete menu items'],
                ['manage_staff', 'Can add, edit, and delete staff members'],
                ['manage_roles', 'Can add, edit, and delete roles'],
                ['process_orders', 'Can process and fulfill customer orders'],
                ['view_reports', 'Can view sales and other reports'],
                ['generate_receipts', 'Can generate and print receipts'],
                ['manage_inventory', 'Can manage cafeteria inventory'],
                ['admin_dashboard', 'Can access the admin dashboard']
            ];
            
            foreach ($default_permissions as $perm) {
                $stmt = $conn->prepare("INSERT IGNORE INTO permissions (permission_name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $perm[0], $perm[1]);
                if ($stmt->execute()) {
                    $message .= "<span class='text-green-600'>✓ Added permission: {$perm[0]}</span><br>";
                } else {
                    $message .= "<span class='text-red-600'>✗ Failed to add permission {$perm[0]}: {$stmt->error}</span><br>";
                }
                $stmt->close();
            }
            
        } else if (isset($_POST['setup_admin'])) {
            // Set up admin permissions (R001)
            $conn->query("DELETE FROM role_permissions WHERE Role_Id = 'R001'");
            
            $result = $conn->query("SELECT permission_id FROM permissions");
            while ($row = $result->fetch_assoc()) {
                $permission_id = $row['permission_id'];
                $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (Role_Id, permission_id) VALUES ('R001', ?)");
                $stmt->bind_param("i", $permission_id);
                if ($stmt->execute()) {
                    $message .= "<span class='text-green-600'>✓ Added permission #{$permission_id} to admin</span><br>";
                } else {
                    $message .= "<span class='text-red-600'>✗ Failed to add permission #{$permission_id} to admin: {$stmt->error}</span><br>";
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get table information for display
$tables = [];
try {
    // Check if permissions table exists
    $result = $conn->query("SHOW TABLES LIKE 'permissions'");
    $tables['permissions_exists'] = ($result->num_rows > 0);
    
    // Check if role_permissions table exists
    $result = $conn->query("SHOW TABLES LIKE 'role_permissions'");
    $tables['role_permissions_exists'] = ($result->num_rows > 0);
    
    // Count permissions
    $tables['permissions_count'] = 0;
    if ($tables['permissions_exists']) {
        $result = $conn->query("SELECT COUNT(*) as count FROM permissions");
        $row = $result->fetch_assoc();
        $tables['permissions_count'] = $row['count'];
    }
    
    // Count role permissions
    $tables['role_permissions_count'] = 0;
    if ($tables['role_permissions_exists']) {
        $result = $conn->query("SELECT COUNT(*) as count FROM role_permissions");
        $row = $result->fetch_assoc();
        $tables['role_permissions_count'] = $row['count'];
    }
    
    // Get Role_Table structure
    $tables['role_table_structure'] = [];
    $result = $conn->query("DESCRIBE Role_Table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tables['role_table_structure'][] = $row;
        }
    }
    
} catch (Exception $e) {
    $error = "Error getting table info: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Database Tables</title>
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
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Fix Database Tables</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Database Table Repair Tool</h2>
            
            <?php if (!empty($message)): ?>
                <div class="bg-gray-100 p-4 rounded mb-6">
                    <h3 class="font-bold mb-2">Operation Results:</h3>
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-yellow-50 border border-yellow-300 rounded p-4 mb-6">
                <p class="text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Warning:</strong> This tool is designed to help fix issues with permission tables. 
                    Use with caution as it may modify database structure.
                </p>
            </div>
            
            <!-- Table Information -->
            <div class="mb-6">
                <h3 class="text-xl font-bold mb-2">Table Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded border">
                        <h4 class="font-bold">permissions table</h4>
                        <p>Status: 
                            <?php if ($tables['permissions_exists']): ?>
                                <span class="text-green-600">Exists</span>
                            <?php else: ?>
                                <span class="text-red-600">Does not exist</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($tables['permissions_exists']): ?>
                            <p>Entries: <?= $tables['permissions_count'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded border">
                        <h4 class="font-bold">role_permissions table</h4>
                        <p>Status: 
                            <?php if ($tables['role_permissions_exists']): ?>
                                <span class="text-green-600">Exists</span>
                            <?php else: ?>
                                <span class="text-red-600">Does not exist</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($tables['role_permissions_exists']): ?>
                            <p>Entries: <?= $tables['role_permissions_count'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Role_Table Structure -->
                <div class="mt-4">
                    <h4 class="font-bold mb-2">Role_Table Structure</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border-b">Field</th>
                                    <th class="py-2 px-4 border-b">Type</th>
                                    <th class="py-2 px-4 border-b">Null</th>
                                    <th class="py-2 px-4 border-b">Key</th>
                                    <th class="py-2 px-4 border-b">Default</th>
                                    <th class="py-2 px-4 border-b">Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tables['role_table_structure'])): ?>
                                    <?php foreach ($tables['role_table_structure'] as $column): ?>
                                        <tr>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Field']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Type']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Null']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Key']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Default']) ?></td>
                                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($column['Extra']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-2 px-4 text-center">No structure information available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Create Tables -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h3 class="text-lg font-bold mb-3">Create Tables</h3>
                    <p class="mb-3">Creates the required permission tables without foreign key constraints.</p>
                    <form method="post" action="">
                        <button type="submit" name="create_tables" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
                            <i class="fas fa-plus-circle mr-2"></i> Create Tables
                        </button>
                    </form>
                </div>
                
                <!-- Drop Tables -->
                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                    <h3 class="text-lg font-bold mb-3">Drop Tables</h3>
                    <p class="mb-3">Deletes both tables to start fresh. <strong>Warning:</strong> This will delete all permission data.</p>
                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to drop these tables? This will delete ALL permission data.')">
                        <button type="submit" name="drop_tables" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-300">
                            <i class="fas fa-trash-alt mr-2"></i> Drop Tables
                        </button>
                    </form>
                </div>
                
                <!-- Populate Permissions -->
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h3 class="text-lg font-bold mb-3">Populate Permissions</h3>
                    <p class="mb-3">Adds the default permissions to the permissions table.</p>
                    <form method="post" action="">
                        <button type="submit" name="populate_permissions" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-300">
                            <i class="fas fa-database mr-2"></i> Populate Permissions
                        </button>
                    </form>
                </div>
                
                <!-- Setup Admin Permissions -->
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h3 class="text-lg font-bold mb-3">Setup Admin Permissions</h3>
                    <p class="mb-3">Grants all permissions to the Administrator role (R001).</p>
                    <form method="post" action="">
                        <button type="submit" name="setup_admin" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition duration-300">
                            <i class="fas fa-user-shield mr-2"></i> Setup Admin Permissions
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="dashboard.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
