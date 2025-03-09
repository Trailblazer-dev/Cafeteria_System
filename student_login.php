<?php
include 'includes/db.php';
session_start(); // Changed from start_session() to standard session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If a user is already in session, clear it for a new student login
if (isset($_SESSION['reg_no'])) {
    // Keep the success message if it exists
    $success_message = $_SESSION['message'] ?? null;
    
    // Clear session data but keep important system variables
    $staff_id = $_SESSION['staff_id'] ?? null;
    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;
    
    // Clear all session variables
    session_unset();
    
    // Restore staff login data if it existed
    if ($staff_id) $_SESSION['staff_id'] = $staff_id;
    if ($username) $_SESSION['username'] = $username;
    if ($role) $_SESSION['role'] = $role;
    
    // Restore success message if it existed
    if ($success_message) $_SESSION['message'] = $success_message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and fetch student details
    $reg_no = sanitize($_POST['reg_no']);

    $stmt = $conn->prepare("SELECT reg_no, fistname, lastname, phone FROM student_table WHERE reg_no = ?");
    $stmt->bind_param("s", $reg_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $_SESSION['reg_no'] = $student['reg_no'];
        $_SESSION['firstname'] = $student['fistname'];
        $_SESSION['lastname'] = $student['lastname'];
        $_SESSION['phone'] = $student['phone'];
        header("Location: student_order.php");
        exit;
    } else {
        $error = "Student not found. Please check your registration number and try again.";
    }
    $stmt->close();
}

// Check for recent student registration - this would be added if there was a registration page
$recent_registrations = false;
$recent_students = [];
$result = $conn->query("SELECT reg_no, fistname, lastname FROM student_table ORDER BY reg_no DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    $recent_registrations = true;
    while ($row = $result->fetch_assoc()) {
        $recent_students[] = $row;
    }
}
?>

<!-- HTML for the student login form -->
<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <div class="font-bold text-xl">Cafeteria Management System</div>
            <a href="index.php" class="hover:underline"><i class="fas fa-home"></i> Home</a>
        </div>
    </nav>
    
    <div class="container mx-auto p-6 flex-grow flex items-center justify-center">
        <div class="w-full max-w-md">
            <!-- Success message -->
            <?php if(isset($_SESSION['message'])): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6 flex justify-between items-center">
                    <div><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-700 hover:text-green-900">×</button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Student Login</h2>
                
                <?php if(isset($error)): ?>
                    <div class="bg-red-100 text-red-700 p-4 rounded mb-6 flex justify-between items-center">
                        <div><?= htmlspecialchars($error) ?></div>
                        <button onclick="this.parentElement.style.display='none'" class="text-red-700 hover:text-red-900">×</button>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" class="space-y-6">
                    <div>
                        <label for="reg_no" class="block text-gray-700 font-semibold mb-2">Registration Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="reg_no" 
                                id="reg_no" 
                                class="w-full pl-10 p-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                placeholder="Enter your registration number"
                                required
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-500 text-white p-4 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i> Continue
                    </button>
                    
                    <?php if ($recent_registrations): ?>
                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-gray-500 mb-2">Recent Students:</h3>
                            <div class="space-y-1">
                                <?php foreach ($recent_students as $student): ?>
                                    <button 
                                        type="button"
                                        onclick="document.getElementById('reg_no').value='<?= htmlspecialchars($student['reg_no']) ?>'"
                                        class="text-left text-blue-500 hover:text-blue-700 text-sm block w-full hover:bg-gray-50 p-2 rounded-md transition">
                                        <?= htmlspecialchars($student['reg_no']) ?> - <?= htmlspecialchars($student['fistname']) ?> <?= htmlspecialchars($student['lastname']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-blue-500 hover:underline text-sm">
                        <i class="fas fa-user-shield mr-1"></i> Staff Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="mt-auto bg-white border-t py-4">
        <div class="container mx-auto px-6 text-center text-gray-600 text-sm">
            &copy; <?= date('Y') ?> Cafeteria Management System. All rights reserved.
        </div>
    </footer>
</body>
</html>
