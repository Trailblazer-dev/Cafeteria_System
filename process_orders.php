<?php
/**
 * Process Orders Page
 * Allows staff to process and manage customer orders
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
if (!$permHandler->hasPermission('process_orders')) {
    header("Location: unauthorized.php");
    exit;
}

// Get some sample orders for demonstration
$demoOrders = [
    [
        'id' => 'ORD-1001',
        'student' => 'John Smith',
        'time' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
        'items' => 'Burger (2), Fries, Soda',
        'total' => 850.00,
        'status' => 'pending'
    ],
    [
        'id' => 'ORD-1002',
        'student' => 'Mary Johnson',
        'time' => date('Y-m-d H:i:s', strtotime('-25 minutes')),
        'items' => 'Chicken Sandwich, Salad, Water',
        'total' => 650.00,
        'status' => 'in-progress'
    ],
    [
        'id' => 'ORD-1003',
        'student' => 'David Williams',
        'time' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
        'items' => 'Pizza Slice (3), Soda (2)',
        'total' => 950.00,
        'status' => 'in-progress'
    ],
    [
        'id' => 'ORD-1004',
        'student' => 'Sarah Brown',
        'time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'items' => 'Pasta, Garlic Bread, Juice',
        'total' => 750.00,
        'status' => 'completed'
    ],
    [
        'id' => 'ORD-1005',
        'student' => 'Michael Lee',
        'time' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'items' => 'Rice & Beans, Grilled Chicken, Water',
        'total' => 800.00,
        'status' => 'completed'
    ]
];

// Set active tab based on query parameter or default to 'pending'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// Page setup
$pageTitle = "Process Orders";

// Use secure include mechanism
include 'includes/components/header.php';
include 'includes/components/navigation.php';
?>

<div class="container mx-auto p-6">
    <?php printPageHeader("Process Orders", "Process Orders"); ?>
    
    <?php displayMessages(); ?>
    
    <!-- Under Development Notice -->
    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
        <div class="flex items-center mb-4">
            <div class="text-yellow-500 mr-3">
                <i class="fas fa-tools text-3xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-yellow-800">Under Development</h3>
                <p class="text-yellow-700">The order processing interface is currently in development. Basic functionality is available for demonstration.</p>
            </div>
        </div>
    </div>
    
    <!-- Order Processing Interface -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <!-- Tabs for different order states -->
        <div class="bg-gray-100 px-4 border-b">
            <nav class="flex space-x-2 overflow-x-auto" aria-label="Order Tabs">
                <a href="?tab=pending" class="<?= $activeTab == 'pending' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-3 font-medium text-sm inline-flex items-center">
                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-red-500 text-white text-xs mr-2">7</span>
                    Pending Orders
                </a>
                <a href="?tab=in-progress" class="<?= $activeTab == 'in-progress' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-3 font-medium text-sm inline-flex items-center">
                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-yellow-500 text-white text-xs mr-2">2</span>
                    In Progress
                </a>
                <a href="?tab=completed" class="<?= $activeTab == 'completed' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-3 font-medium text-sm inline-flex items-center">
                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-green-500 text-white text-xs mr-2">15</span>
                    Completed
                </a>
                <a href="?tab=all" class="<?= $activeTab == 'all' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-3 font-medium text-sm">
                    All Orders
                </a>
            </nav>
        </div>
        
        <!-- Search and Filter Bar -->
        <div class="p-4 border-b flex flex-wrap gap-2 items-center justify-between">
            <div class="flex items-center">
                <input type="text" placeholder="Search orders..." class="border rounded p-2 text-sm">
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 ml-2">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-sm text-gray-600">Filter by:</label>
                <select class="border rounded p-2 text-sm">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this-week">This Week</option>
                    <option value="this-month">This Month</option>
                </select>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $filteredOrders = $demoOrders;
                    if ($activeTab !== 'all') {
                        $filteredOrders = array_filter($demoOrders, function($order) use ($activeTab) {
                            return $order['status'] === $activeTab;
                        });
                    }
                    
                    if (count($filteredOrders) > 0):
                        foreach($filteredOrders as $order): 
                            $statusClass = '';
                            $statusLabel = '';
                            
                            switch($order['status']) {
                                case 'pending':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusLabel = 'Pending';
                                    break;
                                case 'in-progress':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    $statusLabel = 'In Progress';
                                    break;
                                case 'completed':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusLabel = 'Completed';
                                    break;
                            }
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($order['id']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($order['student']) ?></td>
                            <td class="px-4 py-3 text-sm">
                                <?= date('g:i A', strtotime($order['time'])) ?><br>
                                <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($order['time'])) ?></span>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($order['items']) ?></td>
                            <td class="px-4 py-3">KSh <?= number_format($order['total'], 2) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-1 rounded-full text-xs <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 flex space-x-1">
                                <button class="bg-blue-500 text-white rounded p-1 hover:bg-blue-600" title="View Order Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($order['status'] === 'pending'): ?>
                                <button class="bg-yellow-500 text-white rounded p-1 hover:bg-yellow-600" title="Mark as In Progress">
                                    <i class="fas fa-hourglass-start"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'in-progress'): ?>
                                <button class="bg-green-500 text-white rounded p-1 hover:bg-green-600" title="Mark as Completed">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button class="bg-indigo-500 text-white rounded p-1 hover:bg-indigo-600" title="Print Order">
                                    <i class="fas fa-print"></i>
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                No orders found in this category.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6 flex items-center justify-between">
            <div class="hidden sm:block">
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">24</span> orders
                </p>
            </div>
            <div class="flex-1 flex justify-end sm:justify-between">
                <div>
                    <select class="border rounded px-2 py-1 text-sm">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                    </select>
                </div>
                <div class="flex">
                    <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Fulfillment Stats -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-bold mb-4">Order Fulfillment Statistics</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-sm text-blue-600 mb-1">Average Preparation Time</div>
                <div class="text-2xl font-bold">12 mins</div>
                <div class="text-xs text-gray-500">Today</div>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-sm text-green-600 mb-1">Orders Completed</div>
                <div class="text-2xl font-bold">15 / 24</div>
                <div class="text-xs text-gray-500">Today</div>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-sm text-yellow-600 mb-1">Current Wait Time</div>
                <div class="text-2xl font-bold">15 mins</div>
                <div class="text-xs text-gray-500">For new orders</div>
            </div>
        </div>
        
        <div class="w-full h-40 bg-gray-100 rounded flex items-center justify-center text-gray-400">
            <i class="fas fa-chart-line mr-2"></i> Order Volume Chart (Coming Soon)
        </div>
    </div>
    
    <div class="flex justify-end">
        <a href="dashboard.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Order Details Modal (Hidden by Default) -->
<div id="orderDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">
        <div class="border-b p-4 flex justify-between items-center">
            <h3 class="text-lg font-bold">Order Details: ORD-1001</h3>
            <button onclick="document.getElementById('orderDetailsModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <div class="text-sm text-gray-500">Student</div>
                    <div class="font-medium">John Smith</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Order Time</div>
                    <div class="font-medium">March 8, 2025 at 3:45 PM</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Status</div>
                    <div><span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Pending</span></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="font-medium">KSh 850.00</div>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h4 class="font-medium mb-2">Order Items</h4>
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left text-xs uppercase text-gray-500 pb-2">Item</th>
                            <th class="text-right text-xs uppercase text-gray-500 pb-2">Qty</th>
                            <th class="text-right text-xs uppercase text-gray-500 pb-2">Price</th>
                            <th class="text-right text-xs uppercase text-gray-500 pb-2">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="py-2">Burger</td>
                            <td class="py-2 text-right">2</td>
                            <td class="py-2 text-right">KSh 300.00</td>
                            <td class="py-2 text-right">KSh 600.00</td>
                        </tr>
                        <tr>
                            <td class="py-2">Fries</td>
                            <td class="py-2 text-right">1</td>
                            <td class="py-2 text-right">KSh 150.00</td>
                            <td class="py-2 text-right">KSh 150.00</td>
                        </tr>
                        <tr>
                            <td class="py-2">Soda</td>
                            <td class="py-2 text-right">1</td>
                            <td class="py-2 text-right">KSh 100.00</td>
                            <td class="py-2 text-right">KSh 100.00</td>
                        </tr>
                        <tr class="border-t">
                            <td colspan="3" class="py-2 text-right font-bold">Total:</td>
                            <td class="py-2 text-right font-bold">KSh 850.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="border-t pt-4 mt-4">
                <h4 class="font-medium mb-2">Estimated Preparation Time</h4>
                <div class="flex items-center mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 45%"></div>
                    </div>
                    <span>5 mins</span>
                </div>
            </div>
        </div>
        <div class="border-t p-4 flex justify-end space-x-2">
            <button onclick="document.getElementById('orderDetailsModal').classList.add('hidden')" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-100">
                Close
            </button>
            <button class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                <i class="fas fa-hourglass-start mr-1"></i> Start Preparation
            </button>
        </div>
    </div>
</div>

<script>
    // Function to show order details modal
    function showOrderDetails(orderId) {
        document.getElementById('orderDetailsModal').classList.remove('hidden');
    }
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers to view buttons
        document.querySelectorAll('button[title="View Order Details"]').forEach(btn => {
            btn.addEventListener('click', function() {
                showOrderDetails('ORD-1001'); // In a real app, this would be dynamic
            });
        });
    });
</script>

<?php
include 'includes/components/footer.php';
includeAutoHideScript();
?>
