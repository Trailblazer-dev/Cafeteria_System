<?php
include 'includes/db.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use the sanitize() function to clean input data
    $reg_no = $_SESSION['reg_no'];
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

        // Get the last details_id and increment it by 1
        $result = $conn->query("SELECT MAX(details_id) AS max_id FROM order_details");
        $row = $result->fetch_assoc();
        $details_id = $row['max_id'] + 1;

        // 2. Insert Order Details using a prepared statement
        $stmt2 = $conn->prepare("INSERT INTO order_details (details_id, order_id, Item_Id, quantity) VALUES (?, ?, ?, ?)");
        if (!$stmt2) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        foreach ($items as $item_id) {
            $quantity = 1; // or fetch quantity from input if available
            $stmt2->bind_param("iiis", $details_id, $order_id, $item_id, $quantity);
            if (!$stmt2->execute()) {
                throw new Exception("Execute statement failed: " . $stmt2->error);
            }
            $details_id++; // Increment details_id for the next item
        }
        $stmt2->close();

        // Commit the transaction
        $conn->commit();
        $_SESSION['message'] = "Order submitted successfully!";
        header("Location: student_order.php");
        exit;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Order creation failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to submit order. Please try again. Error: " . $e->getMessage();
        header("Location: student_order.php");
        exit;
    }
}
?>
