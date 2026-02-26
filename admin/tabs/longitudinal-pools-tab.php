<?php
/**
 * Longitudinal Pools Tab
 * Tab content for managing longitudinal randomization pools.
 * Wraps functionality from randomization-pools-page.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the randomization-pools-page to access the function
if (file_exists(dirname(__FILE__) . '/../randomization-pools-page.php')) {
    require_once dirname(__FILE__) . '/../randomization-pools-page.php';
}

/**
 * Render the Longitudinal Pools tab content.
 */
function eipsi_render_longitudinal_pools_tab() {
    // Check if function exists, otherwise call it directly
    if (function_exists('eipsi_display_longitudinal_pools_page')) {
        eipsi_display_longitudinal_pools_page();
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Longitudinal Pools', 'eipsi-forms') . '</h1>';
        echo '<div class="notice notice-error">';
        echo '<p>' . esc_html__('Error: Longitudinal Pools functionality is not available.', 'eipsi-forms') . '</p>';
        echo '</div>';
        echo '</div>';
    }
}
