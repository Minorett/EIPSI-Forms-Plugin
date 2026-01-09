<?php
/**
 * Completion Message Backend Handler
 * Manages storage and retrieval of global completion message settings
 * 
 * This provides the backend infrastructure for the completion message feature.
 * The admin UI to manage these settings will be added in Phase 16.
 * 
 * @package EIPSI_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EIPSI_Completion_Message {
    
    /**
     * Option key for storing completion message configuration
     */
    private static $option_key = 'eipsi_global_completion_message';
    
    /**
     * Get completion message configuration
     * 
     * @return array Configuration array with title, message, show_logo, show_home_button, button_text, button_action, show_animation
     */
    public static function get_config() {
        $defaults = array(
            'title'            => '¡Gracias por completar el formulario!',
            'message'          => 'Sus respuestas han sido registradas correctamente.',
            'show_logo'        => true,
            'show_home_button' => true,
            'button_text'      => 'Comenzar de nuevo',
            'button_action'    => 'reload',
            'show_animation'   => false,
        );
        
        $saved = get_option( self::$option_key, array() );
        return wp_parse_args( $saved, $defaults );
    }
    
    /**
     * Save completion message configuration
     * 
     * @param array $config Configuration to save
     * @return bool True on success, false on failure
     */
    public static function save_config( $config ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        
        $sanitized = array(
            'title'            => sanitize_text_field( $config['title'] ?? '¡Gracias por completar el formulario!' ),
            'message'          => wp_kses_post( $config['message'] ?? '' ),
            'show_logo'        => (bool) ( $config['show_logo'] ?? true ),
            'show_home_button' => (bool) ( $config['show_home_button'] ?? true ),
            'button_text'      => sanitize_text_field( $config['button_text'] ?? 'Comenzar de nuevo' ),
            'button_action'    => in_array( $config['button_action'] ?? 'reload', array( 'reload', 'close', 'none' ) ) ? $config['button_action'] : 'reload',
            'show_animation'   => (bool) ( $config['show_animation'] ?? false ),
        );
        
        return update_option( self::$option_key, $sanitized );
    }
}
