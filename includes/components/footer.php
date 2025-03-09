<?php
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}
?>
<!-- Footer -->
<footer class="bg-white border-t mt-12 py-6">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-600 text-sm">
                &copy; <?= date('Y') ?> Cafeteria Management System. All rights reserved.
            </div>
            <div class="mt-4 md:mt-0">
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-question-circle"></i> Help
                    </a>
                    <a href="#" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="#" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
