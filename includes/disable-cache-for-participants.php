<?php
/**
 * Disable cache for EIPSI participant pages
 * 
 * This ensures that participant dashboards and study pages are always
 * fresh and not served from cache, which is critical for:
 * - Auto-refresh functionality
 * - Real-time countdown updates
 * - Wave status changes
 * - T1-Anchor timing
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable cache for pages with EIPSI participant content
 */
add_action('template_redirect', 'eipsi_disable_cache_for_participants', 1);
function eipsi_disable_cache_for_participants() {
    global $post;
    
    // Only run on frontend
    if (is_admin()) {
        return;
    }
    
    // Check if we have a post
    if (!is_a($post, 'WP_Post')) {
        return;
    }
    
    // Check if current page has EIPSI participant shortcodes
    $has_eipsi_content = 
        has_shortcode($post->post_content, 'eipsi_longitudinal_study') ||
        has_shortcode($post->post_content, 'eipsi_participant_dashboard') ||
        has_shortcode($post->post_content, 'eipsi_form') ||
        has_shortcode($post->post_content, 'eipsi_survey_form');
    
    // Also check if URL contains participant-related keywords
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    $is_participant_page = 
        strpos($current_url, 'estudio-') !== false ||
        strpos($current_url, 'participante-') !== false ||
        strpos($current_url, 'dashboard') !== false;
    
    // If this is a participant page, disable all caching
    if ($has_eipsi_content || $is_participant_page) {
        
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[EIPSI Cache] Disabling cache for: ' . $current_url);
        }
        
        // Disable WP Fastest Cache
        if (function_exists('wpfc_exclude_current_page')) {
            wpfc_exclude_current_page();
        }
        
        // Disable W3 Total Cache
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        
        // Disable WP Super Cache
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        
        // Disable LiteSpeed Cache
        if (function_exists('litespeed_purge_all')) {
            do_action('litespeed_control_set_nocache', 'EIPSI participant page');
        }
        
        // Set HTTP headers to prevent caching
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            
            // Prevent proxy caching
            header('X-Accel-Expires: 0');
            
            // Add custom header for debugging
            header('X-EIPSI-Cache: DISABLED');
        }
        
        // Disable object caching for this request
        wp_suspend_cache_addition(true);
    }
}

/**
 * Add cache exclusion rules for WP Fastest Cache
 * (if the plugin provides a filter/action)
 */
add_filter('wpfc_exclude_current_page', 'eipsi_wpfc_exclude_rules', 10, 1);
function eipsi_wpfc_exclude_rules($exclude) {
    global $post;
    
    if (!is_a($post, 'WP_Post')) {
        return $exclude;
    }
    
    // Check for EIPSI shortcodes
    $has_eipsi_content = 
        has_shortcode($post->post_content, 'eipsi_longitudinal_study') ||
        has_shortcode($post->post_content, 'eipsi_participant_dashboard') ||
        has_shortcode($post->post_content, 'eipsi_form') ||
        has_shortcode($post->post_content, 'eipsi_survey_form');
    
    if ($has_eipsi_content) {
        return true; // Exclude from cache
    }
    
    return $exclude;
}

/**
 * Exclude EIPSI pages from LiteSpeed Cache
 */
add_filter('litespeed_cache_is_cacheable', 'eipsi_litespeed_exclude', 10, 1);
function eipsi_litespeed_exclude($cacheable) {
    global $post;
    
    if (!is_a($post, 'WP_Post')) {
        return $cacheable;
    }
    
    $has_eipsi_content = 
        has_shortcode($post->post_content, 'eipsi_longitudinal_study') ||
        has_shortcode($post->post_content, 'eipsi_participant_dashboard') ||
        has_shortcode($post->post_content, 'eipsi_form') ||
        has_shortcode($post->post_content, 'eipsi_survey_form');
    
    if ($has_eipsi_content) {
        return false; // Don't cache
    }
    
    return $cacheable;
}

/**
 * Add admin notice if cache plugin is detected
 */
add_action('admin_notices', 'eipsi_cache_plugin_notice');
function eipsi_cache_plugin_notice() {
    // Only show on EIPSI admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'eipsi') === false) {
        return;
    }
    
    // Detect active cache plugins
    $cache_plugins = array();
    
    if (is_plugin_active('wp-fastest-cache/wpFastestCache.php')) {
        $cache_plugins[] = 'WP Fastest Cache';
    }
    if (is_plugin_active('w3-total-cache/w3-total-cache.php')) {
        $cache_plugins[] = 'W3 Total Cache';
    }
    if (is_plugin_active('wp-super-cache/wp-cache.php')) {
        $cache_plugins[] = 'WP Super Cache';
    }
    if (is_plugin_active('litespeed-cache/litespeed-cache.php')) {
        $cache_plugins[] = 'LiteSpeed Cache';
    }
    
    if (empty($cache_plugins)) {
        return;
    }
    
    // Check if user has dismissed this notice
    $dismissed = get_user_meta(get_current_user_id(), 'eipsi_cache_notice_dismissed', true);
    if ($dismissed) {
        return;
    }
    
    ?>
    <div class="notice notice-warning is-dismissible eipsi-cache-notice">
        <p>
            <strong>⚠️ EIPSI Forms:</strong> 
            Se detectaron plugins de cache activos: <strong><?php echo implode(', ', $cache_plugins); ?></strong>
        </p>
        <p>
            Las páginas de participantes están automáticamente excluidas del cache para garantizar que el 
            <strong>auto-refresh</strong> y los <strong>countdowns</strong> funcionen correctamente.
        </p>
        <p>
            <a href="<?php echo esc_url(EIPSI_FORMS_PLUGIN_URL . 'CACHE-EXCLUSION-GUIDE.md'); ?>" target="_blank">
                📖 Ver guía de configuración de cache
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.eipsi-cache-notice').on('click', '.notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'eipsi_dismiss_cache_notice',
                nonce: '<?php echo wp_create_nonce('eipsi_dismiss_cache_notice'); ?>'
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler to dismiss cache notice
 */
add_action('wp_ajax_eipsi_dismiss_cache_notice', 'eipsi_ajax_dismiss_cache_notice');
function eipsi_ajax_dismiss_cache_notice() {
    check_ajax_referer('eipsi_dismiss_cache_notice', 'nonce');
    
    update_user_meta(get_current_user_id(), 'eipsi_cache_notice_dismissed', true);
    
    wp_send_json_success();
}
