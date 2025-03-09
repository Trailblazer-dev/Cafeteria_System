<?php
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}
?>
<!-- Navigation Bar -->
<nav class="bg-blue-600 text-white p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
        <div class="font-bold text-xl">Cafeteria Management System</div>
        <div class="space-x-4">
            <a href="dashboard.php" class="hover:underline"><i class="fas fa-home"></i> Dashboard</a>
            <span class="text-sm">|</span>
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
            <a href="logout.php" class="hover:underline"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>
