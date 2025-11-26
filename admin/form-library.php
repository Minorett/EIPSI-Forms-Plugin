<?php
/**
 * Form Library Management
 * 
 * Handles the Form Library admin page where clinicians can:
 * - View all saved form templates
 * - Copy shortcodes for reuse
 * - Create, edit, and delete form templates
 * 
 * @package VAS_Dinamico_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Type for Form Templates
 */
function eipsi_register_form_template_cpt() {
    $labels = array(
        'name'                  => __('Form Templates', 'vas-dinamico-forms'),
        'singular_name'         => __('Form Template', 'vas-dinamico-forms'),
        'menu_name'             => __('Form Library', 'vas-dinamico-forms'),
        'add_new'               => __('Add New', 'vas-dinamico-forms'),
        'add_new_item'          => __('Add New Form Template', 'vas-dinamico-forms'),
        'edit_item'             => __('Edit Form Template', 'vas-dinamico-forms'),
        'new_item'              => __('New Form Template', 'vas-dinamico-forms'),
        'view_item'             => __('View Form Template', 'vas-dinamico-forms'),
        'search_items'          => __('Search Form Templates', 'vas-dinamico-forms'),
        'not_found'             => __('No form templates found', 'vas-dinamico-forms'),
        'not_found_in_trash'    => __('No form templates found in trash', 'vas-dinamico-forms'),
        'all_items'             => __('All Forms', 'vas-dinamico-forms'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => false, // We'll add it manually to EIPSI Forms menu
        'show_in_rest'        => true, // Enable Gutenberg
        'capability_type'     => 'post',
        'capabilities'        => array(
            'edit_post'          => 'manage_options',
            'edit_posts'         => 'manage_options',
            'edit_others_posts'  => 'manage_options',
            'publish_posts'      => 'manage_options',
            'read_post'          => 'manage_options',
            'read_private_posts' => 'manage_options',
            'delete_post'        => 'manage_options',
        ),
        'hierarchical'        => false,
        'supports'            => array('title', 'editor', 'custom-fields'),
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
        'menu_icon'           => 'dashicons-feedback',
    );

    register_post_type('eipsi_form_template', $args);
}
add_action('init', 'eipsi_register_form_template_cpt');

/**
 * Add Form Library submenu to EIPSI Forms menu
 */
function eipsi_add_form_library_menu() {
    add_submenu_page(
        'vas-dinamico-results',
        __('Form Library', 'vas-dinamico-forms'),
        __('Form Library', 'vas-dinamico-forms'),
        'manage_options',
        'edit.php?post_type=eipsi_form_template'
    );
}
add_action('admin_menu', 'eipsi_add_form_library_menu', 11);

/**
 * Customize columns in Form Library list table
 */
function eipsi_form_library_columns($columns) {
    $new_columns = array(
        'cb'                => $columns['cb'],
        'title'             => __('Form Name', 'vas-dinamico-forms'),
        'shortcode'         => __('Shortcode', 'vas-dinamico-forms'),
        'last_response'     => __('Last Response', 'vas-dinamico-forms'),
        'total_responses'   => __('Total Responses', 'vas-dinamico-forms'),
        'date'              => __('Created', 'vas-dinamico-forms'),
    );
    
    return $new_columns;
}
add_filter('manage_eipsi_form_template_posts_columns', 'eipsi_form_library_columns');

/**
 * Populate custom columns in Form Library list table
 */
function eipsi_form_library_column_content($column, $post_id) {
    switch ($column) {
        case 'shortcode':
            $shortcode = '[eipsi_form id="' . $post_id . '"]';
            echo '<code class="eipsi-shortcode-display">' . esc_html($shortcode) . '</code>';
            echo '<button type="button" class="button button-small eipsi-copy-shortcode" data-shortcode="' . esc_attr($shortcode) . '" style="margin-left: 8px;">';
            echo '<span class="dashicons dashicons-clipboard" style="vertical-align: middle; margin-top: 3px;"></span>';
            echo '</button>';
            break;
            
        case 'last_response':
            global $wpdb;
            $table_name = $wpdb->prefix . 'vas_form_results';
            
            // Get form_name from post meta (stored when form is used)
            $form_name = get_post_meta($post_id, '_eipsi_form_name', true);
            
            if (!$form_name) {
                echo '<span style="color: #999;">—</span>';
                break;
            }
            
            $last_response = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(submitted_at) FROM {$table_name} WHERE form_name = %s",
                $form_name
            ));
            
            if ($last_response) {
                $time_diff = human_time_diff(strtotime($last_response), current_time('timestamp'));
                echo '<span title="' . esc_attr($last_response) . '">' . sprintf(__('%s ago', 'vas-dinamico-forms'), $time_diff) . '</span>';
            } else {
                echo '<span style="color: #999;">' . __('Never', 'vas-dinamico-forms') . '</span>';
            }
            break;
            
        case 'total_responses':
            global $wpdb;
            $table_name = $wpdb->prefix . 'vas_form_results';
            
            $form_name = get_post_meta($post_id, '_eipsi_form_name', true);
            
            if (!$form_name) {
                echo '<span style="color: #999;">0</span>';
                break;
            }
            
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE form_name = %s",
                $form_name
            ));
            
            echo '<strong>' . number_format_i18n((int)$count) . '</strong>';
            break;
    }
}
add_action('manage_eipsi_form_template_posts_custom_column', 'eipsi_form_library_column_content', 10, 2);

/**
 * Make shortcode and responses columns sortable
 */
function eipsi_form_library_sortable_columns($columns) {
    $columns['total_responses'] = 'total_responses';
    $columns['last_response'] = 'last_response';
    return $columns;
}
add_filter('manage_edit-eipsi_form_template_sortable_columns', 'eipsi_form_library_sortable_columns');

/**
 * Add admin CSS for Form Library
 */
function eipsi_form_library_admin_styles() {
    $screen = get_current_screen();
    
    if (!$screen || $screen->post_type !== 'eipsi_form_template') {
        return;
    }
    ?>
    <style>
        .eipsi-shortcode-display {
            background: #f0f0f1;
            padding: 4px 8px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
            color: #2271b1;
        }
        
        .eipsi-copy-shortcode {
            vertical-align: middle;
            cursor: pointer;
            padding: 2px 8px;
            height: 24px;
        }
        
        .eipsi-copy-shortcode:hover {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }
        
        .eipsi-copy-shortcode .dashicons {
            font-size: 16px;
        }
        
        .column-shortcode {
            width: 280px;
        }
        
        .column-last_response {
            width: 120px;
        }
        
        .column-total_responses {
            width: 100px;
            text-align: center;
        }
        
        /* Success message after copy */
        .eipsi-copy-success {
            position: fixed;
            top: 32px;
            right: 20px;
            background: #00a32a;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 999999;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
    <?php
}
add_action('admin_head', 'eipsi_form_library_admin_styles');

/**
 * Add admin JS for copying shortcodes
 */
function eipsi_form_library_admin_scripts() {
    $screen = get_current_screen();
    
    if (!$screen || $screen->post_type !== 'eipsi_form_template') {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Copy shortcode to clipboard
        $(document).on('click', '.eipsi-copy-shortcode', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const shortcode = button.data('shortcode');
            
            // Create temporary textarea to copy from
            const temp = $('<textarea>');
            $('body').append(temp);
            temp.val(shortcode).select();
            
            try {
                document.execCommand('copy');
                
                // Show success message
                const message = $('<div class="eipsi-copy-success">✓ Shortcode copiado</div>');
                $('body').append(message);
                
                setTimeout(() => {
                    message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
                
            } catch (err) {
                alert('Error al copiar. Por favor, copie manualmente: ' + shortcode);
            }
            
            temp.remove();
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'eipsi_form_library_admin_scripts');

/**
 * Add clinical templates section at the top of Form Library
 */
function eipsi_form_library_clinical_templates_section() {
    $screen = get_current_screen();
    
    if (!$screen || $screen->post_type !== 'eipsi_form_template' || $screen->base !== 'edit') {
        return;
    }
    
    $templates = eipsi_get_clinical_templates();
    ?>
    <div class="eipsi-clinical-templates-section">
        <div class="eipsi-clinical-templates-header">
            <h2><?php _e('Plantillas oficiales EIPSI', 'vas-dinamico-forms'); ?></h2>
            <p><?php _e('Escalas clínicas validadas listas para usar. Hacé clic en "Crear formulario" para generar una copia editable en tu librería.', 'vas-dinamico-forms'); ?></p>
        </div>
        
        <div class="eipsi-clinical-templates-grid">
            <?php foreach ($templates as $template_id => $template_data): ?>
                <div class="eipsi-clinical-template-card">
                    <div class="eipsi-template-icon"><?php echo esc_html($template_data['icon']); ?></div>
                    <h3 class="eipsi-template-name"><?php echo esc_html($template_data['name']); ?></h3>
                    <p class="eipsi-template-full-name"><?php echo esc_html($template_data['full_name']); ?></p>
                    <p class="eipsi-template-description"><?php echo esc_html($template_data['description']); ?></p>
                    <div class="eipsi-template-meta">
                        <small><?php echo esc_html($template_data['author']); ?></small>
                    </div>
                    <button type="button" 
                            class="button button-primary button-large eipsi-create-from-template" 
                            data-template-id="<?php echo esc_attr($template_id); ?>"
                            data-template-name="<?php echo esc_attr($template_data['name']); ?>">
                        <?php _e('Crear formulario', 'vas-dinamico-forms'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
        .eipsi-clinical-templates-section {
            background: white;
            padding: 24px;
            margin: 20px 0;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .eipsi-clinical-templates-header {
            margin-bottom: 24px;
        }
        
        .eipsi-clinical-templates-header h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .eipsi-clinical-templates-header p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        
        .eipsi-clinical-templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .eipsi-clinical-template-card {
            background: #f9f9f9;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .eipsi-clinical-template-card:hover {
            border-color: #2271b1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .eipsi-template-icon {
            font-size: 48px;
            line-height: 1;
            margin-bottom: 12px;
        }
        
        .eipsi-template-name {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 4px 0;
            color: #1d2327;
        }
        
        .eipsi-template-full-name {
            font-size: 13px;
            color: #646970;
            margin: 0 0 12px 0;
            font-weight: 500;
        }
        
        .eipsi-template-description {
            font-size: 13px;
            color: #50575e;
            margin: 0 0 12px 0;
            line-height: 1.5;
            flex-grow: 1;
        }
        
        .eipsi-template-meta {
            margin-bottom: 16px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
            width: 100%;
        }
        
        .eipsi-template-meta small {
            color: #646970;
            font-size: 12px;
        }
        
        .eipsi-create-from-template {
            width: 100%;
            padding: 8px 16px;
            height: auto;
        }
        
        .eipsi-create-from-template:hover {
            background: #135e96;
            border-color: #135e96;
        }
    </style>
    <?php
}
add_action('admin_notices', 'eipsi_form_library_clinical_templates_section');

/**
 * Add helpful notice at the top of Form Library
 */
function eipsi_form_library_admin_notice() {
    $screen = get_current_screen();
    
    if (!$screen || ($screen->post_type !== 'eipsi_form_template' && $screen->base !== 'post')) {
        return;
    }
    
    if ($screen->base === 'edit') {
        ?>
        <div class="notice notice-info" style="margin-top: 20px;">
            <p>
                <strong><?php _e('¿Cómo usar la librería de formularios?', 'vas-dinamico-forms'); ?></strong><br>
                1. Hacé clic en <strong>"Añadir nuevo"</strong> para crear un formulario reutilizable.<br>
                2. Usá el bloque <strong>"EIPSI Form Container"</strong> para armar tu formulario con páginas y campos.<br>
                3. Una vez guardado, copiá el <strong>shortcode</strong> o usá el bloque <strong>"Formulario EIPSI"</strong> para insertarlo en cualquier página.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'eipsi_form_library_admin_notice');

/**
 * Extract form_name from form-container block when saving a template
 * This allows us to track responses for this form
 */
function eipsi_extract_form_name_on_save($post_id, $post, $update) {
    // Only for our CPT
    if ($post->post_type !== 'eipsi_form_template') {
        return;
    }
    
    // Avoid infinite loops
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Parse blocks to find form-container
    $blocks = parse_blocks($post->post_content);
    $form_name = '';
    
    foreach ($blocks as $block) {
        if ($block['blockName'] === 'vas-dinamico/form-container') {
            $form_name = isset($block['attrs']['formId']) ? $block['attrs']['formId'] : '';
            break;
        }
    }
    
    // Save form_name as post meta for easy querying
    if ($form_name) {
        update_post_meta($post_id, '_eipsi_form_name', sanitize_text_field($form_name));
    } else {
        delete_post_meta($post_id, '_eipsi_form_name');
    }
}
add_action('save_post', 'eipsi_extract_form_name_on_save', 10, 3);

/**
 * Limit which blocks can be inserted based on post type context
 * 
 * This ensures that:
 * - Form Container + campos solo aparecen en la Form Library (CPT eipsi_form_template)
 * - El bloque de inserción (Formulario EIPSI) no se ofrece dentro del editor interno
 * - Sitios con bloques antiguos no se rompen: si ya existen, se mantienen permitidos
 * 
 * @param array|bool $allowed_block_types Array of allowed block types, or true to allow all.
 * @param object     $editor_context The current editor context.
 * @return array|bool Modified array of allowed block types.
 */
function eipsi_limit_blocks_by_context($allowed_block_types, $editor_context) {
    if (!isset($editor_context->post)) {
        return $allowed_block_types;
    }
    
    $post = $editor_context->post;
    $post_type = $post->post_type;
    $post_content = isset($post->post_content) ? $post->post_content : '';
    
    $form_building_blocks = array(
        'vas-dinamico/form-container',
        'vas-dinamico/pagina',
        'vas-dinamico/campo-texto',
        'vas-dinamico/campo-textarea',
        'vas-dinamico/campo-descripcion',
        'vas-dinamico/campo-select',
        'vas-dinamico/campo-radio',
        'vas-dinamico/campo-multiple',
        'vas-dinamico/campo-likert',
        'vas-dinamico/vas-slider',
    );
    $form_embed_block = 'vas-dinamico/form-block';
    
    if ($allowed_block_types === true || !is_array($allowed_block_types)) {
        $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $allowed_block_types = array_keys($registered_blocks);
    }
    
    // Dentro de la Form Library (CPT) ocultamos el bloque público
    if ($post_type === 'eipsi_form_template') {
        // Solo quitamos el bloque embed si no existe ya en el contenido (compatibilidad)
        if (!has_block($form_embed_block, $post_content)) {
            $allowed_block_types = array_diff($allowed_block_types, array($form_embed_block));
        }
        
        return array_values($allowed_block_types);
    }
    
    // Fuera de la Form Library ocultamos el Container + campos a menos que ya existan
    $blocks_to_hide = array();
    foreach ($form_building_blocks as $block_name) {
        if (!has_block($block_name, $post_content)) {
            $blocks_to_hide[] = $block_name;
        }
    }
    
    if (!empty($blocks_to_hide)) {
        $allowed_block_types = array_diff($allowed_block_types, $blocks_to_hide);
    }
    
    return array_values($allowed_block_types);
}
add_filter('allowed_block_types_all', 'eipsi_limit_blocks_by_context', 10, 2);
