<?php
/**
 * Completion Message Backend Handler
 * Manages storage and retrieval of global completion message settings
 * 
 * This provides the backend infrastructure for the completion message feature.
 * The admin UI to manage these settings will be added in Phase 16.
 * 
 * @package VAS_Dinamico_Forms
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
	 * @return array Configuration array with message, show_logo, show_home_button, redirect_url
	 */
	public static function get_config() {
		$defaults = array(
			'message'          => 'Gracias por completar el formulario. Sus respuestas han sido registradas.',
			'show_logo'        => true,
			'show_home_button' => true,
			'redirect_url'     => '',
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
			'message'          => wp_kses_post( $config['message'] ?? '' ),
			'show_logo'        => (bool) ( $config['show_logo'] ?? true ),
			'show_home_button' => (bool) ( $config['show_home_button'] ?? true ),
			'redirect_url'     => esc_url_raw( $config['redirect_url'] ?? '' ),
		);
		
		return update_option( self::$option_key, $sanitized );
	}
	
	/**
	 * Get the completion page URL
	 * 
	 * @return string URL to completion page
	 */
	public static function get_page_url() {
		return home_url( '/eipsi-completion/' );
	}
}
