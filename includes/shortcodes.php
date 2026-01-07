<?php
/**
 * EIPSI Forms Shortcodes
 * 
 * Official shortcode to insert form templates anywhere
 * 
 * @package VAS_Dinamico_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [eipsi_form id="123"]
 * 
 * Display a form template by ID
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function eipsi_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'eipsi_form');
    
    $template_id = absint($atts['id']);
    
    // Use shared render helper
    return eipsi_render_form_shortcode_markup($template_id);
}
add_shortcode('eipsi_form', 'eipsi_form_shortcode');

/**
 * Add helpful information to shortcode in admin
 * Shows available forms when editing posts/pages
 */
function eipsi_shortcode_help_metabox() {
    $screens = array('post', 'page');
    
    foreach ($screens as $screen) {
        add_meta_box(
            'eipsi_shortcode_help',
            __('Shortcode de Formularios EIPSI', 'eipsi-forms'),
            'eipsi_shortcode_help_callback',
            $screen,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'eipsi_shortcode_help_metabox');

/**
 * Render shortcode help metabox
 */
function eipsi_shortcode_help_callback($post) {
    ?>
    <div class="eipsi-shortcode-help">
        <p style="font-size: 13px; margin: 0 0 12px;">
            <?php esc_html_e('Insertá formularios usando este formato:', 'eipsi-forms'); ?>
        </p>
        
        <code style="display: block; padding: 8px; background: #f0f0f1; border-radius: 3px; margin-bottom: 12px;">
            [eipsi_form id="<strong style="color: #2271b1;">123</strong>"]
        </code>
        
        <?php
        // Get available form templates
        $templates = get_posts(array(
            'post_type' => 'eipsi_form_template',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        if (!empty($templates)) {
            ?>
            <p style="font-size: 13px; margin: 12px 0 8px; font-weight: 600;">
                <?php esc_html_e('Formularios disponibles:', 'eipsi-forms'); ?>
            </p>
            
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px;">
                <?php foreach ($templates as $template) : ?>
                    <div style="padding: 8px; border-bottom: 1px solid #f0f0f1; font-size: 12px;">
                        <div style="font-weight: 600; margin-bottom: 4px;">
                            <?php echo esc_html($template->post_title ?: __('(Sin título)', 'eipsi-forms')); ?>
                        </div>
                        <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 2px; font-size: 11px; cursor: pointer;" 
                              onclick="navigator.clipboard.writeText('[eipsi_form id=&quot;<?php echo esc_attr($template->ID); ?>&quot;]'); this.style.background='#00a32a'; this.style.color='white'; setTimeout(() => { this.style.background='#f0f0f1'; this.style.color=''; }, 1000);" 
                              title="<?php esc_attr_e('Clic para copiar', 'eipsi-forms'); ?>">
                            [eipsi_form id="<?php echo esc_attr($template->ID); ?>"]
                        </code>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p style="font-size: 11px; color: #666; margin: 8px 0 0;">
                <em><?php esc_html_e('💡 Clic en un shortcode para copiarlo', 'eipsi-forms'); ?></em>
            </p>
            <?php
        } else {
            ?>
            <p style="font-size: 13px; margin: 12px 0 0; padding: 8px; background: #f0f0f1; border-radius: 3px;">
                <?php esc_html_e('No hay formularios creados aún.', 'eipsi-forms'); ?>
                <br>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=eipsi_form_template')); ?>" style="font-size: 13px;">
                    <?php esc_html_e('+ Crear tu primer formulario', 'eipsi-forms'); ?>
                </a>
            </p>
            <?php
        }
        ?>
    </div>
    
    <style>
        .eipsi-shortcode-help code:hover {
            background: #e5e7eb !important;
        }
    </style>
    <?php
}

/**
 * Add shortcode column to pages/posts list
 * Shows if the page/post contains EIPSI form shortcodes
 */
function eipsi_add_shortcode_indicator_column($columns) {
    $columns['eipsi_forms'] = '<span class="dashicons dashicons-feedback" title="' . esc_attr__('Formularios EIPSI', 'eipsi-forms') . '"></span>';
    return $columns;
}
add_filter('manage_posts_columns', 'eipsi_add_shortcode_indicator_column');
add_filter('manage_pages_columns', 'eipsi_add_shortcode_indicator_column');

/**
 * Show shortcode indicator in column
 */
function eipsi_shortcode_indicator_column_content($column, $post_id) {
    if ($column === 'eipsi_forms') {
        $content = get_post_field('post_content', $post_id);
        $has_shortcode = has_shortcode($content, 'eipsi_form');
        $has_block = has_block('eipsi/form-block', $content) || 
                     has_block('eipsi/form-container', $content);
        
        if ($has_shortcode || $has_block) {
            echo '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;" title="' . esc_attr__('Contiene formularios EIPSI', 'eipsi-forms') . '"></span>';
        } else {
            echo '<span style="color: #ddd;">—</span>';
        }
    }
}
add_action('manage_posts_custom_column', 'eipsi_shortcode_indicator_column_content', 10, 2);
add_action('manage_pages_custom_column', 'eipsi_shortcode_indicator_column_content', 10, 2);
