<?php
/**
 * Privacy & Metadata Tab
 * Configure per-form metadata capture settings
 * Includes privacy-dashboard.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include privacy dashboard
include dirname(dirname(__FILE__)) . '/privacy-dashboard.php';

// Render the privacy dashboard
render_privacy_dashboard();
