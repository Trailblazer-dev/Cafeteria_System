<?php
/**
 * Generate Receipts Page
 * Allows staff to generate and print receipts for orders
 */
include 'includes/config.php';
include 'includes/db.php';
include 'includes/PermissionHandler.php';
include 'includes/functions.php';

session_start();

// Check login
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

// Check permission
$permHandler = new PermissionHandler($conn, $_SESSION['staff_id']);
if (!$permHandler->hasPermission('generate_receipts')) {
    header("Location: unauthorized.php");
    exit;
}

// Page setup
$pageTitle = "Generate Receipts";

// Use secure include mechanism 
// (ACCESS_ALLOWED is defined in config.php, so we don't need to redefine it)
include 'includes/components/header.php';
include 'includes/components/navigation.php';
?>

<div class="container mx-auto p-6">
    <?php printPageHeader("Generate Receipts", "Generate Receipts"); ?>
    
    <?php displayMessages(); ?>
    
    <!-- Receipt generation interface -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between mb-6">
            <h3 class="text-lg font-bold">Recent Orders</h3>
            <div>
                <input type="text" placeholder="Search orders..." class="border rounded p-2 text-sm">
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 ml-2">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">ORD-0001</td>
                        <td class="px-4 py-3">John Smith</td>
                        <td class="px-4 py-3">Oct 15, 10:30 am</td>
                        <td class="px-4 py-3">Ksh 450.00</td>
                        <td class="px-4 py-3">
                            <button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                                <i class="fas fa-receipt mr-1"></i> Generate
                            </button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">ORD-0002</td>
                        <td class="px-4 py-3">Sara Jones</td>
                        <td class="px-4 py-3">Oct 15, 11:15 am</td>
                        <td class="px-4 py-3">Ksh 325.00</td>
                        <td class="px-4 py-3">
                            <button class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                                <i class="fas fa-receipt mr-1"></i> Generate
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="flex justify-end">
        <a href="dashboard.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php
include 'includes/components/footer.php';
includeAutoHideScript();
?>
