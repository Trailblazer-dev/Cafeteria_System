<?php
include 'includes/db.php';
session_start(); // Changed from start_session() to standard session_start()

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'R001') {
    header("Location: unauthorized.php");
    exit;
}

// Function to generate the next Role_Id
function generateRoleId($conn) {
    $result = $conn->query("SELECT MAX(Role_Id) AS max_id FROM Role_Table"); // Fixed table name
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return null;
    }
    $row = $result->fetch_assoc();
    $last_id = $row['max_id'];
    // Check if there are any existing roles
    if ($last_id) {
        $new_id_num = (int)substr($last_id, 1) + 1;
    } else {
        $new_id_num = 1; // Start with R001 if no roles exist yet
    }
    return 'R' . str_pad($new_id_num, 3, '0', STR_PAD_LEFT);
}

// Handle form submissions for adding, updating, and deleting roles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_role'])) {
        $role_id = generateRoleId($conn);
        if ($role_id === null) {
            $_SESSION['error'] = "Failed to generate Role ID. Please try again.";
        } else {
            $role_name = sanitize($_POST['role_name']);
            // Note: No description field in the database, just using role name
            $stmt = $conn->prepare("INSERT INTO Role_Table (Role_Id, Role) VALUES (?, ?)");
            if (!$stmt) {
                error_log("Prepare statement failed: " . $conn->error);
                $_SESSION['error'] = "Failed to add role. Please try again.";
            } else {
                $stmt->bind_param("ss", $role_id, $role_name);
                if (!$stmt->execute()) {
                    error_log("Execute statement failed: " . $stmt->error);
                    $_SESSION['error'] = "Failed to add role. Please try again.";
                } else {
                    $_SESSION['message'] = "Role added successfully!";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['update_role'])) {
        $role_id = sanitize($_POST['role_id']);
        $role_name = sanitize($_POST['role_name']);
        $stmt = $conn->prepare("UPDATE Role_Table SET Role = ? WHERE Role_Id = ?");
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            $_SESSION['error'] = "Failed to update role. Please try again.";
        } else {
            $stmt->bind_param("ss", $role_name, $role_id);
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                $_SESSION['error'] = "Failed to update role. Please try again.";
            } else {
                $_SESSION['message'] = "Role updated successfully!";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_role'])) {
        $role_id = sanitize($_POST['role_id']);
        
        // First check if role is in use by any staff
        $stmt = $conn->prepare("SELECT COUNT(*) AS staff_count FROM staff WHERE Role_Id = ?");
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            $_SESSION['error'] = "Failed to check if role is in use.";
        } else {
            $stmt->bind_param("s", $role_id);
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                $_SESSION['error'] = "Failed to check if role is in use.";
            } else {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row['staff_count'] > 0) {
                    $_SESSION['error'] = "Cannot delete role. It is currently assigned to {$row['staff_count']} staff member(s).";
                } else {
                    // Safe to delete the role
                    $stmt->close();
                    
                    // Delete the role
                    $stmt = $conn->prepare("DELETE FROM Role_Table WHERE Role_Id = ?"); // Fixed table name
                    if (!$stmt) {
                        error_log("Prepare statement failed: " . $conn->error);
                        $_SESSION['error'] = "Failed to delete role. Please try again.";
                    } else {
                        $stmt->bind_param("s", $role_id);
                        if (!$stmt->execute()) {
                            error_log("Execute statement failed: " . $stmt->error);
                            $_SESSION['error'] = "Failed to delete role. Please try again.";
                        } else {
                            $_SESSION['message'] = "Role deleted successfully!";
                        }
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE Role LIKE '%$search%' OR Role_Id LIKE '%$search%'";
}

// Fetch all roles with optional search
$result = $conn->query("SELECT * FROM Role_Table" . $search_condition . " ORDER BY Role_Id"); // Fixed table name
if (!$result) {
    error_log("Query failed: " . $conn->error);
    $_SESSION['error'] = "Failed to fetch roles. Please try again.";
} else {
    $roles = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Staff Roles</title>
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
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Manage Staff Roles</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Manage Staff Roles</h2>
            
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

            <div class="md:flex md:space-x-6">
                <!-- Add Role Form -->
                <div class="md:w-1/3">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="text-xl font-bold mb-4">Add New Role</h3>
                        <form method="post" action="" id="addForm">
                            <div class="mb-4">
                                <label for="role_name" class="block text-gray-700 font-semibold mb-2">Role Name</label>
                                <input type="text" name="role_name" id="role_name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <!-- Removed description field as it doesn't exist in the database -->
                            <button type="submit" name="add_role" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center justify-center">
                                <i class="fas fa-plus-circle mr-2"></i> Add Role
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of Roles -->
                <div class="mt-6 md:mt-0 md:w-2/3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">Existing Roles</h3>
                        <!-- Search Form -->
                        <form method="get" action="" class="flex">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search roles..." class="p-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-blue-500 text-white px-4 rounded-r-lg hover:bg-blue-600 transition duration-300">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="manage_staff_roles.php" class="bg-gray-300 text-gray-700 px-3 ml-2 rounded-lg flex items-center hover:bg-gray-400 transition duration-300">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full bg-white rounded-lg shadow-md">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-3 text-left">ID</th>
                                    <th class="p-3 text-left">Role Name</th>
                                    <th class="p-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($roles) && count($roles) > 0): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <tr class="border-t hover:bg-gray-50">
                                            <td class="p-3"><?= htmlspecialchars($role['Role_Id']) ?></td>
                                            <td class="p-3"><?= htmlspecialchars($role['Role']) ?></td>
                                            <td class="p-3">
                                                <!-- Edit Button - Shows Modal -->
                                                <button onclick="showEditModal('<?= htmlspecialchars($role['Role_Id']) ?>', '<?= htmlspecialchars(addslashes($role['Role'])) ?>')" class="bg-yellow-500 text-white px-3 py-1 rounded-lg hover:bg-yellow-600 transition duration-300 mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                
                                                <!-- Delete Button - With Confirmation -->
                                                <button onclick="confirmDelete('<?= htmlspecialchars($role['Role_Id']) ?>', '<?= htmlspecialchars(addslashes($role['Role'])) ?>')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition duration-300">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="p-5 text-center text-gray-500">
                                            <?php if (!empty($search)): ?>
                                                No roles found matching "<?= htmlspecialchars($search) ?>".
                                            <?php else: ?>
                                                No roles found. Add one to get started!
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Edit Role</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="role_id" id="edit_id">
                <div class="mb-4">
                    <label for="edit_role_name" class="block text-gray-700 font-semibold mb-2">Role Name</label>
                    <input type="text" name="role_name" id="edit_role_name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <!-- Removed description field -->
                <div class="flex space-x-4">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="w-1/2 bg-gray-300 text-gray-800 p-3 rounded-lg hover:bg-gray-400 transition duration-300">Cancel</button>
                    <button type="submit" name="update_role" class="w-1/2 bg-yellow-500 text-white p-3 rounded-lg hover:bg-yellow-600 transition duration-300">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form (hidden) -->
    <form id="deleteForm" method="post" action="" class="hidden">
        <input type="hidden" name="role_id" id="delete_id">
        <input type="hidden" name="delete_role" value="1">
    </form>

    <script>
        // Show edit modal with role data
        function showEditModal(id, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_role_name').value = name;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        // Confirm delete before submission
        function confirmDelete(id, name) {
            if (confirm('Are you sure you want to delete role "' + name + '"?\nThis action cannot be undone.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

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
