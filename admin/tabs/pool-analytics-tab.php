<?php
/**
 * Pool Analytics Tab
 * Tab content for pool analytics dashboard.
 * Wraps functionality from longitudinal-pool-dashboard.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the longitudinal-pool-dashboard to access the function
if (file_exists(dirname(__FILE__) . '/../longitudinal-pool-dashboard.php')) {
    require_once dirname(__FILE__) . '/../longitudinal-pool-dashboard.php';
}

/**
 * Render the Pool Analytics tab content.
 */
function eipsi_render_pool_analytics_tab() {
    // Check if function exists, otherwise call it directly
    if (function_exists('eipsi_display_pool_dashboard_page')) {
        eipsi_display_pool_dashboard_page();
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Pool Analytics', 'eipsi-forms') . '</h1>';
        echo '<div class="notice notice-error">';
        echo '<p>' . esc_html__('Error: Pool Analytics functionality is not available.', 'eipsi-forms') . '</p>';
        echo '</div>';
        echo '</div>';
    }
}
