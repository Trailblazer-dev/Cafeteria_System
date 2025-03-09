<?php 
include 'includes/db.php';
session_start();
error_reporting(E_ALL); 
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = sanitize($_POST['items']); // Ensure this is an array

    if (empty($items)) {
        $_SESSION['error'] = "No items selected. Please select at least one item.";
        header("Location: student_order.php");
        exit;
    }

    // Calculate total cost
    $total_cost = 0;
    foreach ($items as $item_id) {
        $result = $conn->query("SELECT Price FROM Item_table WHERE Item_Id = " . intval($item_id));
        if ($result->num_rows === 1) {
            $item = $result->fetch_assoc();
            $total_cost += $item['Price'];
        }
    }

    $_SESSION['items'] = $items;
    $_SESSION['total_cost'] = $total_cost;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Order</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    
    <div class="max-w-4xl mx-auto">
        <!-- Display Student Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Student Details</h2>
            <p><strong>Registration Number:</strong> <?= htmlspecialchars($_SESSION['reg_no']) ?></p>
            <p><strong>First Name:</strong> <?= htmlspecialchars($_SESSION['firstname']) ?></p>
            <p><strong>Last Name:</strong> <?= htmlspecialchars($_SESSION['lastname']) ?></p>
            <p><strong>Phone Number:</strong> <?= htmlspecialchars($_SESSION['phone']) ?></p>
        </div>

        <!-- Order Confirmation -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Confirm Order</h2>
            <p class="text-xl font-bold">Total Cost: Ksh <?= number_format($_SESSION['total_cost'], 2) ?></p>
            <form method="post" action="payment.php">
                <button type="submit" class="w-full bg-green-500 text-white p-3 rounded mt-4">Proceed to Payment</button>
            </form>
            <form method="post" action="student_order.php">
                <button type="submit" class="w-full bg-red-500 text-white p-3 rounded mt-4">Go Back</button>
            </form>
        </div>
    </div>

</body>
</html>
