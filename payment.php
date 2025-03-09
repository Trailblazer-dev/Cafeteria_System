<?php 
include 'includes/db.php';
session_start();
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Check if student is logged in
if (!isset($_SESSION['reg_no'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: student_login.php");
    exit;
}

// Check if items are selected
if (!isset($_SESSION['items']) || empty($_SESSION['items'])) {
    $_SESSION['error'] = "No items selected. Please add items to your order.";
    header("Location: student_order.php");
    exit;
}

// Clear previous error message
if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['payment_method'])) {
        $_SESSION['error'] = "Please select a payment method.";
        header("Location: payment.php");
        exit;
    }
    
    $payment_method = sanitize($_POST['payment_method']);
    $reg_no = $_SESSION['reg_no'];
    $items = $_SESSION['items'];
    $total_cost = $_SESSION['total_cost'];

    // Ensure payment_method is an integer
    $payment_method = intval($payment_method);

    // Check if payment_method exists in paymentMethod table
    $stmt = $conn->prepare("SELECT methodId FROM paymentMethod WHERE methodId = ?");
    $stmt->bind_param("i", $payment_method);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $_SESSION['error'] = "Invalid payment method selected.";
        header("Location: payment.php");
        exit;
    }
    $stmt->close();

    // Begin transaction
    $conn->begin_transaction();

    try {
        // 1. Create Order with a prepared statement
        $stmt = $conn->prepare("INSERT INTO orders (reg_no, order_date, total_cost) VALUES (?, NOW(), ?)");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("sd", $reg_no, $total_cost);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $order_id = $conn->insert_id;
        $stmt->close();

        // Get the last payment_id and increment it by 1
        $result = $conn->query("SELECT MAX(payment_id) AS max_id FROM payment");
        $row = $result->fetch_assoc();
        $last_payment_id = $row['max_id'];
        $new_payment_id = 'P' . str_pad((int)substr($last_payment_id ?: 'P000', 1) + 1, 3, '0', STR_PAD_LEFT);

        // 2. Insert Payment Method into payment table
        $stmt3 = $conn->prepare("INSERT INTO payment (payment_id, order_id, amount, date, methodId) VALUES (?, ?, ?, NOW(), ?)");
        if (!$stmt3) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt3->bind_param("sids", $new_payment_id, $order_id, $total_cost, $payment_method);
        if (!$stmt3->execute()) {
            throw new Exception("Execute statement failed: " . $stmt3->error);
        }
        $stmt3->close();

        // Get the last details_id and increment it by 1
        $result = $conn->query("SELECT MAX(details_id) AS max_id FROM order_details");
        $row = $result->fetch_assoc();
        $details_id = ($row['max_id'] ?? 0) + 1;

        // 3. Insert Order Details using a prepared statement
        $stmt2 = $conn->prepare("INSERT INTO order_details (details_id, order_id, Item_Id, quantity) VALUES (?, ?, ?, ?)");
        if (!$stmt2) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        foreach ($items as $item_id) {
            $quantity = 1; // or fetch quantity from input if available
            $stmt2->bind_param("iiii", $details_id, $order_id, $item_id, $quantity);
            if (!$stmt2->execute()) {
                throw new Exception("Execute statement failed: " . $stmt2->error);
            }
            $details_id++; // Increment details_id for the next item
        }
        $stmt2->close();

        // Commit the transaction
        $conn->commit();

        // Clear any previous error (if needed)
        unset($_SESSION['error']);

        // Store order information for receipt
        $_SESSION['order_id'] = $order_id;
        $_SESSION['payment_id'] = $new_payment_id;
        $_SESSION['payment_date'] = date('Y-m-d H:i:s');

        // Optionally, you can set a success message
        $_SESSION['message'] = "Order submitted successfully!";

        // Output a confirmation page that auto-redirects after 30 seconds
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Order Submitted</title>
            <!-- Auto-redirect after 30 seconds to student_login.php -->
            <meta http-equiv="refresh" content="30;url=student_login.php">
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        </head>
        <body class="bg-gray-100 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 text-center">
                    <div class="text-green-500 mb-4">
                        <i class="fas fa-check-circle text-6xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Order Submitted Successfully!</h2>
                    <p class="mb-2">Order ID: <span class="font-semibold"><?= $order_id ?></span></p>
                    <p class="mb-2">Payment Reference: <span class="font-semibold"><?= $new_payment_id ?></span></p>
                    <p class="mb-4">Amount: <span class="font-semibold">Ksh <?= number_format($total_cost, 2) ?></span></p>
                    
                    <div class="mt-8">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="receipt-progress bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-gray-600 mt-2">Generating receipt... Please wait</p>
                        <p class="text-gray-500 mt-8">You will be redirected in 30 seconds to the login page for the next student.</p>
                    </div>
                    
                    <div class="mt-8">
                        <a href="student_login.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            <i class="fas fa-arrow-left mr-2"></i> Return to Login
                        </a>
                    </div>
                </div>
            </div>
            
            <script>
                // Animate the progress bar
                let width = 0;
                const interval = setInterval(() => {
                    width += 3.33; // Will reach 100% in 30 seconds
                    if (width > 100) width = 100;
                    document.querySelector('.receipt-progress').style.width = width + '%';
                }, 1000);
            </script>
        </body>
        </html>
        <?php
        exit;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Order creation failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to submit order. Please try again. Error: " . $e->getMessage();
        header("Location: payment.php");
        exit;
    }
}

// Get payment methods
$payment_methods = [];
$result = $conn->query("SELECT methodId, method FROM paymentMethod");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payment_methods[] = $row;
    }
} else {
    $_SESSION['error'] = "Failed to load payment methods.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Method</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    
    <div class="max-w-4xl mx-auto">
        <!-- Navigation breadcrumb -->
        <div class="flex items-center mb-6 text-sm">
            <a href="index.php" class="text-blue-600 hover:underline">Home</a>
            <span class="mx-2">/</span>
            <a href="student_login.php" class="text-blue-600 hover:underline">Student Login</a>
            <span class="mx-2">/</span>
            <a href="student_order.php" class="text-blue-600 hover:underline">Order</a>
            <span class="mx-2">/</span>
            <a href="confirm_order.php" class="text-blue-600 hover:underline">Confirm</a>
            <span class="mx-2">/</span>
            <span class="text-gray-600">Payment</span>
        </div>
        
        <!-- Error message display -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-6 flex justify-between items-center">
                <div><?= htmlspecialchars($_SESSION['error']) ?></div>
                <button onclick="this.parentElement.style.display='none'" class="text-red-700 hover:text-red-900">×</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    
        <!-- Display Student Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Student Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="mb-2"><strong>Registration Number:</strong> <?= htmlspecialchars($_SESSION['reg_no']) ?></p>
                    <p><strong>Phone Number:</strong> <?= htmlspecialchars($_SESSION['phone']) ?></p>
                </div>
                <div>
                    <p class="mb-2"><strong>First Name:</strong> <?= htmlspecialchars($_SESSION['firstname']) ?></p>
                    <p><strong>Last Name:</strong> <?= htmlspecialchars($_SESSION['lastname']) ?></p>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Order Summary</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">Item</th>
                            <th class="py-2 px-4 border-b text-right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($_SESSION['items'] as $item_id): 
                            $stmt = $conn->prepare("SELECT Name, Price FROM Item_table WHERE Item_Id = ?");
                            $stmt->bind_param("i", $item_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $item = $result->fetch_assoc();
                            $stmt->close();
                        ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($item['Name']) ?></td>
                                <td class="py-2 px-4 border-b text-right">Ksh <?= number_format($item['Price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-2 px-4 border-b">Total</td>
                            <td class="py-2 px-4 border-b text-right">Ksh <?= number_format($_SESSION['total_cost'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Select Payment Method</h2>
            
            <?php if (empty($payment_methods)): ?>
                <div class="bg-yellow-100 text-yellow-700 p-4 rounded mb-4">
                    No payment methods available. Please contact an administrator.
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <?php foreach ($payment_methods as $method): ?>
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-blue-50 transition duration-200 payment-option">
                                <input type="radio" name="payment_method" value="<?= htmlspecialchars($method['methodId']) ?>" class="mr-3 h-5 w-5" required>
                                <span class="text-gray-700 font-medium"><?= htmlspecialchars($method['method']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between">
                        <a href="confirm_order.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition duration-300">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                        <button type="submit" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition duration-300">
                            <i class="fas fa-credit-card mr-2"></i> Confirm and Pay
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhance radio button selection with visual feedback
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Reset all options
                document.querySelectorAll('.payment-option').forEach(el => {
                    el.classList.remove('bg-blue-50', 'border-blue-500');
                    el.classList.add('border-gray-200');
                });
                
                // Highlight selected option
                this.classList.add('bg-blue-50', 'border-blue-500');
                this.classList.remove('border-gray-200');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });
    </script>
</body>
</html>
