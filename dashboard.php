<?php
/**
 * Dashboard Page
 * Main interface for the Cafeteria Management System
 */
// First include config to define ACCESS_ALLOWED
include 'includes/config.php';

// Then include the rest of the files
include 'includes/db.php';
include 'includes/PermissionHandler.php';
include 'includes/functions.php';
include 'includes/services/StatsService.php';
include 'includes/services/OrderService.php';
include 'includes/components/cards.php';

session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize services
$statsService = new StatsService($conn);
$orderService = new OrderService($conn);
$permHandler = new PermissionHandler($conn, $_SESSION['staff_id']);

// Get statistics
$stats = $statsService->getDashboardStats();
$cafeteria_count = $stats['cafeteria_count'];
$menu_count = $stats['menu_count'];
$staff_count = $stats['staff_count'];

// Get recent orders
$recent_orders = $orderService->getRecentOrders(5);

// Get cafeteria for current staff
$staff_cafeteria = $statsService->getStaffCafeteria($_SESSION['staff_id']);

// Set page title for header
$pageTitle = "Dashboard";

// For debugging - log permissions for this user
if (ENVIRONMENT === 'development') {
    error_log("User ID: " . $_SESSION['staff_id'] . " Role: " . $_SESSION['role']);
    $user_permissions = $permHandler->getUserPermissions($_SESSION['staff_id']);
    error_log("User permissions: " . json_encode($user_permissions));
}

include 'includes/components/header.php';
include 'includes/components/navigation.php';
?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-2">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    <p class="text-gray-600 mb-8">
        <?php if ($staff_cafeteria !== "N/A"): ?>
            You are assigned to <span class="font-semibold"><?= htmlspecialchars($staff_cafeteria) ?></span> cafeteria.
        <?php endif; ?>
    </p>
    
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php 
        generateStatCard('utensils', 'blue', 'Cafeterias', $cafeteria_count);
        generateStatCard('hamburger', 'green', 'Menu Items', $menu_count);
        generateStatCard('users', 'purple', 'Staff', $staff_count);
        generateStatCard('clock', 'orange', 'Current Time', date('h:i A'), date('l, F j, Y'));
        ?>
    </div>
    
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Quick Actions -->
        <div class="lg:w-1/2">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php if ($_SESSION['role'] === 'R001'): ?>
                        <?php generateActionCard('setup_role_permissions.php', 'user-lock', 'pink', 'Role Permissions'); ?>
                    <?php endif; ?>
                    
                    <?php if ($permHandler->hasPermission('manage_cafeterias')): ?>
                        <?php generateActionCard('manage_cafeterias.php', 'store', 'blue', 'Manage Cafeterias'); ?>
                    <?php endif; ?>
                    
                    <?php if ($permHandler->hasPermission('manage_menu')): ?>
                        <?php generateActionCard('manage_menu_items.php', 'hamburger', 'green', 'Menu Management'); ?>
                    <?php endif; ?>
                    
                    <?php if ($permHandler->hasPermission('manage_roles')): ?>
                        <?php generateActionCard('manage_staff_roles.php', 'user-tag', 'yellow', 'Staff Roles'); ?>
                    <?php endif; ?>
                    
                    <?php if ($permHandler->hasPermission('view_reports')): ?>
                        <?php generateActionCard(null, 'chart-pie', 'red', 'Reports', 'Coming Soon'); ?>
                    <?php endif; ?>
                    
                    <?php 
                    // Process orders link - make sure we use a valid link for staff
                    if ($permHandler->hasPermission('process_orders')): 
                    ?>
                        <?php generateActionCard('process_orders.php', 'tasks', 'yellow', 'Manage Orders'); ?>
                    <?php endif; ?>
                    
                    <?php if ($permHandler->hasPermission('manage_inventory')): ?>
                        <?php generateActionCard(null, 'box', 'purple', 'Inventory', 'Coming Soon'); ?>
                    <?php endif; ?>
                    
                    <?php 
                    // Generate receipts link - make sure we use a valid link for staff
                    if ($permHandler->hasPermission('generate_receipts')): 
                    ?>
                        <?php generateActionCard('generate_receipts.php', 'receipt', 'green', 'Receipts'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="lg:w-1/2">
            <h2 class="text-xl font-bold mb-4">Recent Orders</h2>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (!empty($recent_orders)): ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?= htmlspecialchars($order['order_id']) ?></td>
                                    <td class="px-4 py-3">
                                        <?= htmlspecialchars($order['fistname']) ?> <?= htmlspecialchars($order['lastname']) ?>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars(date('M j, g:i a', strtotime($order['order_date']))) ?></td>
                                    <td class="px-4 py-3">Ksh <?= htmlspecialchars(number_format($order['total_cost'], 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Disabled "View All" link -->
                    <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 text-right">
                        <span class="text-gray-400 text-sm cursor-not-allowed">
                            View All Orders <i class="fas fa-chevron-right ml-1"></i>
                            <span class="text-xs">(Coming Soon)</span>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        <p>No recent orders available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer with auto-hide script for messages
include 'includes/components/footer.php';
?>
