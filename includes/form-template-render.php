<?php
/**
 * Shared rendering helpers for form templates
 *
 * @package EIPSI_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render helper: build HTML attributes string
 *
 * @param array $attributes Key => value map
 * @return string
 */
function eipsi_build_html_attributes($attributes = array()) {
    $pairs = array();

    foreach ($attributes as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $pairs[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
    }

    return implode(' ', $pairs);
}

/**
 * Render helper: consistent notice UI for editors/front-end
 *
 * @param string $message Message to display
 * @param string $type    info|warning|error
 * @return string
 */
function eipsi_render_form_notice($message, $type = 'info') {
    $colors = array(
        'info' => array('#e0f2fe', '#0369a1', '#38bdf8'),
        'warning' => array('#fef3c7', '#92400e', '#fbbf24'),
        'error' => array('#fee2e2', '#b91c1c', '#f87171'),
    );

    $palette = isset($colors[$type]) ? $colors[$type] : $colors['info'];

    return sprintf(
        '<div class="eipsi-form-notice eipsi-form-notice-%1$s" style="margin: 20px 0; padding: 16px 18px; border: 2px solid %3$s; border-radius: 8px; background: %2$s; color: %4$s; font-size: 14px; line-height: 1.5;">
            <strong style="display: block; margin-bottom: 6px; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">%5$s</strong>
            <span>%6$s</span>
        </div>',
        esc_attr($type),
        esc_attr($palette[0]),
        esc_attr($palette[2]),
        esc_attr($palette[1]),
        esc_html__('EIPSI Forms', 'eipsi-forms'),
        wp_kses_post($message)
    );
}

/**
 * Fetch a form template post
 *
 * @param int $template_id
 * @return WP_Post|WP_Error
 */
function eipsi_get_form_template($template_id) {
    if (!$template_id) {
        return new WP_Error('eipsi_missing_form', __('Por favor, seleccioná un formulario válido.', 'eipsi-forms'));
    }

    $template = get_post($template_id);

    if (!$template || $template->post_type !== 'eipsi_form_template' || $template->post_status === 'trash') {
        return new WP_Error('eipsi_form_not_found', __('El formulario seleccionado no existe o fue eliminado.', 'eipsi-forms'));
    }

    return $template;
}

/**
 * Render a form template and wrap it for block/shortcode usage
 *
 * @param int    $template_id Template post ID
 * @param string $context     block|shortcode
 * @return string HTML markup
 */
function eipsi_render_form_template_markup($template_id, $context = 'block') {
    $template = eipsi_get_form_template($template_id);

    if (is_wp_error($template)) {
        return eipsi_render_form_notice($template->get_error_message(), 'error');
    }

    // Ensure frontend assets are loaded
    eipsi_forms_enqueue_frontend_assets();

    // Render Gutenberg blocks contained in the template
    $content = do_blocks($template->post_content);

    $wrapper_attributes = eipsi_build_html_attributes(array(
        'class' => 'eipsi-form-template-wrapper',
        'data-template-id' => $template_id,
        'data-render-source' => $context,
    ));

    return sprintf('<div %s>%s</div>', $wrapper_attributes, $content);
}

/**
 * Shortcode dispatcher (shared helper)
 *
 * @param int $template_id
 * @return string
 */
function eipsi_render_form_shortcode_markup($template_id) {
    if (!$template_id) {
        return eipsi_render_form_notice(
            __('Recordá pasar el atributo id: [eipsi_form id="123"].', 'eipsi-forms'),
            'warning'
        );
    }

    return eipsi_render_form_template_markup($template_id, 'shortcode');
}
