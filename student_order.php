<?php 
include 'includes/db.php';
start_session();
error_reporting(E_ALL); 
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Place Order</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function calculateTotal() {
            let checkboxes = document.querySelectorAll('input[name="items[]"]:checked');
            let total = 0;
            checkboxes.forEach((checkbox) => {
                total += parseFloat(checkbox.dataset.price);
            });
            document.getElementById('totalCost').innerText = 'Total Cost: Ksh ' + total.toFixed(2);
        }
    </script>
</head>
<body class="bg-gray-100 p-8">
    
    <div class="max-w-4xl mx-auto">
        <!-- Display Success or Error Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Display Student Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Student Details</h2>
            <p><strong>Registration Number:</strong> <?= htmlspecialchars($_SESSION['reg_no']) ?></p>
            <p><strong>First Name:</strong> <?= htmlspecialchars($_SESSION['firstname']) ?></p>
            <p><strong>Last Name:</strong> <?= htmlspecialchars($_SESSION['lastname']) ?></p>
            <p><strong>Phone Number:</strong> <?= htmlspecialchars($_SESSION['phone']) ?></p>
        </div>

        <!-- Items Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Select Items</h2>
            <form method="post" action="confirm_order.php">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php
                    $items = $conn->query("SELECT * FROM Item_table");
                    
                    if ($items->num_rows > 0) : 
                        while($item = $items->fetch_assoc()) : ?>
                            <label class="border p-4 rounded-lg hover:border-blue-500 transition-colors">
                                <input 
                                    type="checkbox" 
                                    name="items[]" 
                                    value="<?= htmlspecialchars($item['Item_Id']) ?>" 
                                    data-price="<?= htmlspecialchars($item['Price']) ?>"
                                    class="mr-2"
                                    onchange="calculateTotal()"
                                >
                                <?= htmlspecialchars($item['Name']) ?> 
                                (Ksh <?= number_format($item['Price'], 2) ?>)
                            </label>
                        <?php endwhile;
                    else : ?>
                        <div class="text-red-500 p-3 bg-red-50 rounded-lg">
                            No food items found in the database!
                        </div>
                    <?php endif; ?>
                </div>
                <div id="totalCost" class="mt-4 text-xl font-bold">Total Cost: Ksh 0.00</div>
                <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded mt-4">Proceed to Confirmation</button>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Recent Orders</h2>
            
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left">Order ID</th>
                        <th class="p-3 text-left">Student</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orders = $conn->query("
                        SELECT o.order_id, 
                               s.fistname, 
                               s.lastname, 
                               SUM(i.Price * od.quantity) AS total,
                               o.order_date
                        FROM orders o
                        JOIN order_details od ON o.order_id = od.order_id
                        JOIN Item_table i ON od.Item_Id = i.Item_Id
                        JOIN student_table s ON o.reg_no = s.reg_no
                        GROUP BY o.order_id
                        ORDER BY o.order_date DESC
                        LIMIT 5
                    ");
                    
                    if ($orders->num_rows > 0) :
                        while($order = $orders->fetch_assoc()) : ?>
                            <tr class="border-t">
                                <td class="p-3"><?= htmlspecialchars($order['order_id']) ?></td>
                                <td class="p-3">
                                    <?= htmlspecialchars($order['fistname']) ?>
                                    <?= htmlspecialchars($order['lastname']) ?>
                                </td>
                                <td class="p-3">Ksh <?= number_format($order['total'], 2) ?></td>
                                <td class="p-3"><?= htmlspecialchars($order['order_date']) ?></td>
                            </tr>
                        <?php endwhile;
                    else : ?>
                        <tr>
                            <td colspan="4" class="p-3 text-center text-gray-500">
                                No orders found!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
