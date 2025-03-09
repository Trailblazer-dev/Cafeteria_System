<?php
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

/**
 * Generates a stat card for the dashboard
 *
 * @param string $icon FontAwesome icon class name (without the fa- prefix)
 * @param string $color Color theme (blue, green, purple, orange, etc.)
 * @param string $label Card label
 * @param string $value Card value to display
 * @param string $subvalue Optional additional information below the value
 * @return void Outputs the HTML directly
 */
function generateStatCard($icon, $color, $label, $value, $subvalue = null) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-<?= $color ?>-500">
        <div class="flex items-center">
            <div class="rounded-full bg-<?= $color ?>-100 p-3 mr-4">
                <i class="fas fa-<?= $icon ?> text-<?= $color ?>-500 text-xl"></i>
            </div>
            <div>
                <div class="text-gray-500"><?= $label ?></div>
                <div class="text-2xl font-bold"><?= $value ?></div>
                <?php if ($subvalue): ?>
                <div class="text-sm"><?= $subvalue ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Generates an action card for the dashboard
 *
 * @param string $href Link URL (or null for disabled cards)
 * @param string $icon FontAwesome icon class name (without the fa- prefix)
 * @param string $color Color theme (blue, green, yellow, etc.)
 * @param string $title Card title
 * @param string $comingSoon Optional "Coming Soon" label text
 * @return void Outputs the HTML directly
 */
function generateActionCard($href, $icon, $color, $title, $comingSoon = null) {
    if ($href) {
        // Active, clickable card
        ?>
        <a href="<?= $href ?>" class="flex flex-col items-center bg-<?= $color ?>-50 hover:bg-<?= $color ?>-100 rounded-lg p-4 transition-all duration-300 transform hover:scale-105">
            <div class="rounded-full bg-<?= $color ?>-100 p-3 mb-2">
                <i class="fas fa-<?= $icon ?> text-<?= $color ?>-500 text-xl"></i>
            </div>
            <span class="text-center font-medium"><?= $title ?></span>
        </a>
        <?php
    } else {
        // Disabled "coming soon" card
        ?>
        <div class="flex flex-col items-center bg-<?= $color ?>-50 rounded-lg p-4 cursor-not-allowed opacity-70">
            <div class="rounded-full bg-<?= $color ?>-100 p-3 mb-2">
                <i class="fas fa-<?= $icon ?> text-<?= $color ?>-500 text-xl"></i>
            </div>
            <span class="text-center font-medium"><?= $title ?></span>
            <?php if ($comingSoon): ?>
            <span class="text-xs text-<?= $color ?>-500 mt-1"><?= $comingSoon ?></span>
            <?php endif; ?>
        </div>
        <?php
    }
}
