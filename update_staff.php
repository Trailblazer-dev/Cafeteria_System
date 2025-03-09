<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['staff_id']) || empty($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once 'includes/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    
    // Basic validation
    $errors = [];
    if (empty($staff_id)) {
        $errors[] = "Staff ID is required";
    }
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($role)) {
        $errors[] = "Role is required";
    }
    
    // If no errors, update the database
    if (empty($errors)) {
        $sql = "UPDATE staff SET name = ?, email = ?, role = ? WHERE staffId = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sssi", $name, $email, $role, $staff_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Staff updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating staff: " . $conn->error;
            }
            
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
    
    // Redirect back to manage_cafeterias.php
    header("Location: manage_cafeterias.php");
    exit;
}

// If it's not a POST request, redirect to manage_cafeterias.php
header("Location: manage_cafeterias.php");
exit;
?>
