<?php
/**
 * Common utility functions
 */
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

/**
 * Check if a feature is enabled
 * 
 * @param string $feature Feature name to check
 * @return bool True if feature is enabled
 */
function isFeatureEnabled($feature) {
    global $FEATURES;
    return isset($FEATURES[$feature]) && $FEATURES[$feature] === true;
}

/**
 * Check if a file exists in the project
 * 
 * @param string $filename Filename to check
 * @return bool True if file exists
 */
function fileExists($filename) {
    $fullPath = __DIR__ . '/../../' . $filename;
    return file_exists($fullPath);
}

/**
 * Print a page header with breadcrumb navigation
 * 
 * @param string $title Page title
 * @param string $currentPage Current page name for breadcrumb
 */
function printPageHeader($title, $currentPage) {
    ?>
    <!-- Breadcrumb navigation -->
    <div class="text-sm mb-6">
        <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a> &gt; <span class="text-gray-600"><?= $currentPage ?></span>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold mb-4"><?= $title ?></h2>
    <?php
}

/**
 * Display a session message (success or error) and clear it
 */
function displayMessages() {
    if (isset($_SESSION['message'])) {
        echo '<div id="successMessage" class="bg-green-100 text-green-700 p-4 rounded mb-6 flex justify-between items-center">';
        echo '<div>' . htmlspecialchars($_SESSION['message']) . '</div>';
        echo '<button onclick="this.parentElement.style.display=\'none\'" class="text-green-700 hover:text-green-900">×</button>';
        echo '</div>';
        unset($_SESSION['message']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div id="errorMessage" class="bg-red-100 text-red-700 p-4 rounded mb-6 flex justify-between items-center">';
        echo '<div>' . htmlspecialchars($_SESSION['error']) . '</div>';
        echo '<button onclick="this.parentElement.style.display=\'none\'" class="text-red-700 hover:text-red-900">×</button>';
        echo '</div>';
        unset($_SESSION['error']);
    }
}

/**
 * Auto-hide messages script
 * Include this at the end of your pages to auto-hide success/error messages
 */
function includeAutoHideScript() {
    ?>
    <script>
        // Auto-hide success and error messages after 5 seconds
        setTimeout(() => {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            if (successMessage) successMessage.style.display = 'none';
            if (errorMessage) errorMessage.style.display = 'none';
        }, 5000);
    </script>
    <?php
}
