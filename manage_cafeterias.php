<?php
include 'includes/db.php';
start_session();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'R001') {
    header("Location: unauthorized.php");
    exit;
}

// Function to generate the next Cafeteria_Id
function generateCafeteriaId($conn) {
    $result = $conn->query("SELECT MAX(Cafeteria_Id) AS max_id FROM Cafeteria");
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return null;
    }
    $row = $result->fetch_assoc();
    $last_id = $row['max_id'];
    $new_id_num = (int)substr($last_id, 1) + 1;
    return 'C' . str_pad($new_id_num, 3, '0', STR_PAD_LEFT);
}

// Handle form submissions for adding, updating, and deleting cafeterias
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cafeteria'])) {
        $cafeteria_id = generateCafeteriaId($conn);
        if ($cafeteria_id === null) {
            $_SESSION['error'] = "Failed to generate Cafeteria ID. Please try again.";
        } else {
            $name = sanitize($_POST['name']);
            $location = sanitize($_POST['location']);
            $stmt = $conn->prepare("INSERT INTO Cafeteria (Cafeteria_Id, Name, Location) VALUES (?, ?, ?)");
            if (!$stmt) {
                error_log("Prepare statement failed: " . $conn->error);
                $_SESSION['error'] = "Failed to add cafeteria. Please try again.";
            } else {
                $stmt->bind_param("sss", $cafeteria_id, $name, $location);
                if (!$stmt->execute()) {
                    error_log("Execute statement failed: " . $stmt->error);
                    $_SESSION['error'] = "Failed to add cafeteria. Please try again.";
                } else {
                    $_SESSION['message'] = "Cafeteria added successfully!";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['update_cafeteria'])) {
        $cafeteria_id = sanitize($_POST['cafeteria_id']);
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $stmt = $conn->prepare("UPDATE Cafeteria SET Name = ?, Location = ? WHERE Cafeteria_Id = ?");
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            $_SESSION['error'] = "Failed to update cafeteria. Please try again.";
        } else {
            $stmt->bind_param("sss", $name, $location, $cafeteria_id);
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                $_SESSION['error'] = "Failed to update cafeteria. Please try again.";
            } else {
                $_SESSION['message'] = "Cafeteria updated successfully!";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_cafeteria'])) {
        $cafeteria_id = sanitize($_POST['cafeteria_id']);
        
        // Begin transaction for deletion
        $conn->begin_transaction();

        try {
            // First get all staff IDs associated with this cafeteria
            $stmt = $conn->prepare("SELECT staffId FROM staff WHERE Cafeteria_Id = ?");
            if (!$stmt) {
                throw new Exception("Prepare statement failed (get staff): " . $conn->error);
            }
            $stmt->bind_param("s", $cafeteria_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed (get staff): " . $stmt->error);
            }
            $result = $stmt->get_result();
            $staff_ids = [];
            while ($row = $result->fetch_assoc()) {
                $staff_ids[] = $row['staffId'];
            }
            $stmt->close();
            
            // If there are staff members, delete their work schedules first
            if (!empty($staff_ids)) {
                // Create a string of placeholders for the IN clause
                $placeholders = implode(',', array_fill(0, count($staff_ids), '?'));
                
                $stmt = $conn->prepare("DELETE FROM Work_schedule_table WHERE staffId IN ($placeholders)");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed (delete work schedules): " . $conn->error);
                }
                
                // Bind all staff IDs
                $types = str_repeat('i', count($staff_ids));
                $stmt->bind_param($types, ...$staff_ids);
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute statement failed (delete work schedules): " . $stmt->error);
                }
                $stmt->close();
            }

            // Delete related items
            $stmt = $conn->prepare("DELETE FROM Item_table WHERE Cafeteria_Id = ?");
            if (!$stmt) {
                throw new Exception("Prepare statement failed (delete items): " . $conn->error);
            }
            $stmt->bind_param("s", $cafeteria_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed (delete items): " . $stmt->error);
            }
            $stmt->close();

            // Delete related staff
            $stmt = $conn->prepare("DELETE FROM staff WHERE Cafeteria_Id = ?");
            if (!$stmt) {
                throw new Exception("Prepare statement failed (delete staff): " . $conn->error);
            }
            $stmt->bind_param("s", $cafeteria_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed (delete staff): " . $stmt->error);
            }
            $stmt->close();

            // Delete the cafeteria
            $stmt = $conn->prepare("DELETE FROM Cafeteria WHERE Cafeteria_Id = ?");
            if (!$stmt) {
                throw new Exception("Prepare statement failed (delete cafeteria): " . $conn->error);
            }
            $stmt->bind_param("s", $cafeteria_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed (delete cafeteria): " . $stmt->error);
            }
            $stmt->close();

            // Commit the transaction if all deletes succeed
            $conn->commit();
            $_SESSION['message'] = "Cafeteria deleted successfully!";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            error_log("Deletion error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete cafeteria. Please try again. " . $e->getMessage();
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE Name LIKE '%$search%' OR Location LIKE '%$search%' OR Cafeteria_Id LIKE '%$search%'";
}

// Fetch all cafeterias with optional search
$result = $conn->query("SELECT * FROM Cafeteria" . $search_condition);
if (!$result) {
    error_log("Query failed: " . $conn->error);
    $_SESSION['error'] = "Failed to fetch cafeterias. Please try again.";
} else {
    $cafeterias = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Cafeterias</title>
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
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Manage Cafeterias</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Manage Cafeterias</h2>
            
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
                <!-- Add Cafeteria Form -->
                <div class="md:w-1/3">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="text-xl font-bold mb-4">Add New Cafeteria</h3>
                        <form method="post" action="" id="addForm">
                            <div class="mb-4">
                                <label for="name" class="block text-gray-700 font-semibold mb-2">Cafeteria Name</label>
                                <input type="text" name="name" id="name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div class="mb-4">
                                <label for="location" class="block text-gray-700 font-semibold mb-2">Location</label>
                                <input type="text" name="location" id="location" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <button type="submit" name="add_cafeteria" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center justify-center">
                                <i class="fas fa-plus-circle mr-2"></i> Add Cafeteria
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of Cafeterias -->
                <div class="mt-6 md:mt-0 md:w-2/3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">Existing Cafeterias</h3>
                        <!-- Search Form -->
                        <form method="get" action="" class="flex">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search cafeterias..." class="p-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-blue-500 text-white px-4 rounded-r-lg hover:bg-blue-600 transition duration-300">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="manage_cafeterias.php" class="bg-gray-300 text-gray-700 px-3 ml-2 rounded-lg flex items-center hover:bg-gray-400 transition duration-300">
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
                                    <th class="p-3 text-left">Name</th>
                                    <th class="p-3 text-left">Location</th>
                                    <th class="p-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($cafeterias) && count($cafeterias) > 0): ?>
                                    <?php foreach ($cafeterias as $cafeteria): ?>
                                        <tr class="border-t hover:bg-gray-50">
                                            <td class="p-3"><?= htmlspecialchars($cafeteria['Cafeteria_Id']) ?></td>
                                            <td class="p-3"><?= htmlspecialchars($cafeteria['Name']) ?></td>
                                            <td class="p-3"><?= htmlspecialchars($cafeteria['Location']) ?></td>
                                            <td class="p-3">
                                                <!-- Edit Button - Shows Modal -->
                                                <button onclick="showEditModal('<?= htmlspecialchars($cafeteria['Cafeteria_Id']) ?>', '<?= htmlspecialchars($cafeteria['Name']) ?>', '<?= htmlspecialchars($cafeteria['Location']) ?>')" class="bg-yellow-500 text-white px-3 py-1 rounded-lg hover:bg-yellow-600 transition duration-300 mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                
                                                <!-- Delete Button - With Confirmation -->
                                                <button onclick="confirmDelete('<?= htmlspecialchars($cafeteria['Cafeteria_Id']) ?>', '<?= htmlspecialchars($cafeteria['Name']) ?>')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition duration-300">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-5 text-center text-gray-500">
                                            <?php if (!empty($search)): ?>
                                                No cafeterias found matching "<?= htmlspecialchars($search) ?>".
                                            <?php else: ?>
                                                No cafeterias found. Add one to get started!
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
                <h3 class="text-xl font-bold">Edit Cafeteria</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="cafeteria_id" id="edit_id">
                <div class="mb-4">
                    <label for="edit_name" class="block text-gray-700 font-semibold mb-2">Cafeteria Name</label>
                    <input type="text" name="name" id="edit_name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit_location" class="block text-gray-700 font-semibold mb-2">Location</label>
                    <input type="text" name="location" id="edit_location" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="flex space-x-4">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="w-1/2 bg-gray-300 text-gray-800 p-3 rounded-lg hover:bg-gray-400 transition duration-300">Cancel</button>
                    <button type="submit" name="update_cafeteria" class="w-1/2 bg-yellow-500 text-white p-3 rounded-lg hover:bg-yellow-600 transition duration-300">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form (hidden) -->
    <form id="deleteForm" method="post" action="" class="hidden">
        <input type="hidden" name="cafeteria_id" id="delete_id">
        <input type="hidden" name="delete_cafeteria" value="1">
    </form>

    <script>
        // Show edit modal with cafeteria data
        function showEditModal(id, name, location) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_location').value = location;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        // Confirm delete before submission
        function confirmDelete(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '" and all associated data? This action cannot be undone.')) {
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
