<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "team_cafeteria";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prevent SQL injection
function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    if (is_numeric($data)) {
        return $data;
    }
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($data)));
}

// Start session if not already started
function start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}
?>
