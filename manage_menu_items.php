<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'R001') {
    header("Location: unauthorized.php");
    exit;
}

// Function to generate the next Item_Id
function generateItemId($conn) {
    $result = $conn->query("SELECT MAX(Item_Id) AS max_id FROM Item_table");
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return null;
    }
    $row = $result->fetch_assoc();
    $last_id = $row['max_id'];
    $new_id = intval($last_id) + 1;
    return $new_id;
}

// Handle form submissions for adding, updating, and deleting menu items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        $item_id = generateItemId($conn);
        if ($item_id === null) {
            $_SESSION['error'] = "Failed to generate Item ID. Please try again.";
        } else {
            $name = sanitize($_POST['name']);
            $price = sanitize($_POST['price']);
            $availability = isset($_POST['availability']) ? 1 : 0;
            $cafeteria_id = sanitize($_POST['cafeteria_id']);
            
            $stmt = $conn->prepare("INSERT INTO Item_table (Item_Id, Name, Price, Availability, Cafeteria_Id) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                error_log("Prepare statement failed: " . $conn->error);
                $_SESSION['error'] = "Failed to add menu item. Please try again.";
            } else {
                $stmt->bind_param("isdis", $item_id, $name, $price, $availability, $cafeteria_id);
                if (!$stmt->execute()) {
                    error_log("Execute statement failed: " . $stmt->error);
                    $_SESSION['error'] = "Failed to add menu item. Please try again.";
                } else {
                    $_SESSION['message'] = "Menu item added successfully!";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['update_item'])) {
        $item_id = sanitize($_POST['item_id']);
        $name = sanitize($_POST['name']);
        $price = sanitize($_POST['price']);
        $availability = isset($_POST['availability']) ? 1 : 0;
        $cafeteria_id = sanitize($_POST['cafeteria_id']);
        
        $stmt = $conn->prepare("UPDATE Item_table SET Name = ?, Price = ?, Availability = ?, Cafeteria_Id = ? WHERE Item_Id = ?");
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            $_SESSION['error'] = "Failed to update menu item. Please try again.";
        } else {
            $stmt->bind_param("sdisi", $name, $price, $availability, $cafeteria_id, $item_id);
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                $_SESSION['error'] = "Failed to update menu item. Please try again.";
            } else {
                $_SESSION['message'] = "Menu item updated successfully!";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_item'])) {
        $item_id = sanitize($_POST['item_id']);
        
        // Check if the item is referenced in any order_details
        $stmt = $conn->prepare("SELECT COUNT(*) AS order_count FROM order_details WHERE Item_Id = ?");
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            $_SESSION['error'] = "Failed to check if menu item is in use.";
        } else {
            $stmt->bind_param("i", $item_id);
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                $_SESSION['error'] = "Failed to check if menu item is in use.";
            } else {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row['order_count'] > 0) {
                    $_SESSION['error'] = "Cannot delete menu item. It is referenced in {$row['order_count']} order(s).";
                } else {
                    // Safe to delete the menu item
                    $stmt->close();
                    
                    // Delete the menu item
                    $stmt = $conn->prepare("DELETE FROM Item_table WHERE Item_Id = ?");
                    if (!$stmt) {
                        error_log("Prepare statement failed: " . $conn->error);
                        $_SESSION['error'] = "Failed to delete menu item. Please try again.";
                    } else {
                        $stmt->bind_param("i", $item_id);
                        if (!$stmt->execute()) {
                            error_log("Execute statement failed: " . $stmt->error);
                            $_SESSION['error'] = "Failed to delete menu item. Please try again.";
                        } else {
                            $_SESSION['message'] = "Menu item deleted successfully!";
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
    $search_condition = " WHERE Name LIKE '%$search%' OR Item_Id LIKE '%$search%' OR Cafeteria_Id LIKE '%$search%'";
}

// Fetch all menu items with optional search
$result = $conn->query("SELECT i.*, c.Name as CafeteriaName 
                        FROM Item_table i 
                        LEFT JOIN Cafeteria c ON i.Cafeteria_Id = c.Cafeteria_Id" . 
                        $search_condition . 
                        " ORDER BY i.Item_Id");
if (!$result) {
    error_log("Query failed: " . $conn->error);
    $_SESSION['error'] = "Failed to fetch menu items. Please try again.";
} else {
    $items = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all cafeterias for dropdown
$cafeterias_result = $conn->query("SELECT Cafeteria_Id, Name FROM Cafeteria ORDER BY Name");
if (!$cafeterias_result) {
    error_log("Query failed: " . $conn->error);
    $_SESSION['error'] = "Failed to fetch cafeterias. Please try again.";
} else {
    $cafeterias = $cafeterias_result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Menu Items</title>
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
            <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600">Manage Menu Items</span>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Manage Menu Items</h2>
            
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
                <!-- Add Menu Item Form -->
                <div class="md:w-1/3">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="text-xl font-bold mb-4">Add New Menu Item</h3>
                        <form method="post" action="" id="addForm">
                            <div class="mb-4">
                                <label for="name" class="block text-gray-700 font-semibold mb-2">Item Name</label>
                                <input type="text" name="name" id="name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div class="mb-4">
                                <label for="price" class="block text-gray-700 font-semibold mb-2">Price</label>
                                <input type="number" name="price" id="price" step="0.01" min="0" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div class="mb-4">
                                <label for="cafeteria_id" class="block text-gray-700 font-semibold mb-2">Cafeteria</label>
                                <select name="cafeteria_id" id="cafeteria_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select Cafeteria</option>
                                    <?php foreach($cafeterias as $cafeteria): ?>
                                        <option value="<?= htmlspecialchars($cafeteria['Cafeteria_Id']) ?>">
                                            <?= htmlspecialchars($cafeteria['Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="availability" id="availability" class="mr-2" checked>
                                    <span class="text-gray-700 font-semibold">Available</span>
                                </label>
                            </div>
                            <button type="submit" name="add_item" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center justify-center">
                                <i class="fas fa-plus-circle mr-2"></i> Add Menu Item
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of Menu Items -->
                <div class="mt-6 md:mt-0 md:w-2/3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">Existing Menu Items</h3>
                        <!-- Search Form -->
                        <form method="get" action="" class="flex">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search menu items..." class="p-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-blue-500 text-white px-4 rounded-r-lg hover:bg-blue-600 transition duration-300">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="manage_menu_items.php" class="bg-gray-300 text-gray-700 px-3 ml-2 rounded-lg flex items-center hover:bg-gray-400 transition duration-300">
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
                                    <th class="p-3 text-left">Price</th>
                                    <th class="p-3 text-left">Availability</th>
                                    <th class="p-3 text-left">Cafeteria</th>
                                    <th class="p-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($items) && count($items) > 0): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr class="border-t hover:bg-gray-50">
                                            <td class="p-3"><?= htmlspecialchars($item['Item_Id']) ?></td>
                                            <td class="p-3"><?= htmlspecialchars($item['Name']) ?></td>
                                            <td class="p-3"><?= htmlspecialchars($item['Price']) ?></td>
                                            <td class="p-3">
                                                <?php if ($item['Availability'] == 1): ?>
                                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded">Available</span>
                                                <?php else: ?>
                                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded">Unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3"><?= htmlspecialchars($item['CafeteriaName']) ?></td>
                                            <td class="p-3">
                                                <!-- Edit Button - Shows Modal -->
                                                <button onclick="showEditModal(
                                                    '<?= htmlspecialchars($item['Item_Id']) ?>',
                                                    '<?= htmlspecialchars(addslashes($item['Name'])) ?>',
                                                    '<?= htmlspecialchars($item['Price']) ?>',
                                                    <?= $item['Availability'] ?>,
                                                    '<?= htmlspecialchars($item['Cafeteria_Id']) ?>'
                                                )" class="bg-yellow-500 text-white px-3 py-1 rounded-lg hover:bg-yellow-600 transition duration-300 mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                
                                                <!-- Delete Button - With Confirmation -->
                                                <button onclick="confirmDelete('<?= htmlspecialchars($item['Item_Id']) ?>', '<?= htmlspecialchars(addslashes($item['Name'])) ?>')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition duration-300">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="p-5 text-center text-gray-500">
                                            <?php if (!empty($search)): ?>
                                                No menu items found matching "<?= htmlspecialchars($search) ?>".
                                            <?php else: ?>
                                                No menu items found. Add one to get started!
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
                <h3 class="text-xl font-bold">Edit Menu Item</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="item_id" id="edit_id">
                <div class="mb-4">
                    <label for="edit_name" class="block text-gray-700 font-semibold mb-2">Item Name</label>
                    <input type="text" name="name" id="edit_name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit_price" class="block text-gray-700 font-semibold mb-2">Price</label>
                    <input type="number" name="price" id="edit_price" step="0.01" min="0" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit_cafeteria_id" class="block text-gray-700 font-semibold mb-2">Cafeteria</label>
                    <select name="cafeteria_id" id="edit_cafeteria_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Cafeteria</option>
                        <?php foreach($cafeterias as $cafeteria): ?>
                            <option value="<?= htmlspecialchars($cafeteria['Cafeteria_Id']) ?>">
                                <?= htmlspecialchars($cafeteria['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="availability" id="edit_availability" class="mr-2">
                        <span class="text-gray-700 font-semibold">Available</span>
                    </label>
                </div>
                <div class="flex space-x-4">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="w-1/2 bg-gray-300 text-gray-800 p-3 rounded-lg hover:bg-gray-400 transition duration-300">Cancel</button>
                    <button type="submit" name="update_item" class="w-1/2 bg-yellow-500 text-white p-3 rounded-lg hover:bg-yellow-600 transition duration-300">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form (hidden) -->
    <form id="deleteForm" method="post" action="" class="hidden">
        <input type="hidden" name="item_id" id="delete_id">
        <input type="hidden" name="delete_item" value="1">
    </form>

    <script>
        // Show edit modal with menu item data
        function showEditModal(id, name, price, availability, cafeteria_id) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_availability').checked = availability === 1;
            document.getElementById('edit_cafeteria_id').value = cafeteria_id;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        // Confirm delete before submission
        function confirmDelete(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"?\nThis action cannot be undone.')) {
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
