<?php
/**
 * Completion Message Page Template
 * Displayed after participant submits form successfully
 * 
 * @package VAS_Dinamico_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/admin/completion-message-backend.php';

$config = EIPSI_Completion_Message::get_config();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Thank You', 'vas-dinamico-forms' ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="eipsi-completion-page">
	<div class="eipsi-completion-container">
		
		<!-- Logo (if enabled) -->
		<?php if ( $config['show_logo'] ) : ?>
			<div class="eipsi-completion-logo">
				<?php
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
					if ( $logo_url ) {
						echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
					}
				} else {
					// Fallback to site name if no logo
					echo '<h1>' . esc_html( get_bloginfo( 'name' ) ) . '</h1>';
				}
				?>
			</div>
		<?php endif; ?>
		
		<!-- Message -->
		<div class="eipsi-completion-message">
			<?php echo wp_kses_post( $config['message'] ); ?>
		</div>
		
		<!-- Actions -->
		<div class="eipsi-completion-actions">
			
			<!-- Home Button (if enabled) -->
			<?php if ( $config['show_home_button'] ) : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="eipsi-btn eipsi-btn-primary">
					<?php esc_html_e( 'Back to Start', 'vas-dinamico-forms' ); ?>
				</a>
			<?php endif; ?>
			
			<!-- Redirect Button (if configured) -->
			<?php if ( ! empty( $config['redirect_url'] ) ) : ?>
				<a href="<?php echo esc_url( $config['redirect_url'] ); ?>" class="eipsi-btn eipsi-btn-secondary">
					<?php esc_html_e( 'Continue', 'vas-dinamico-forms' ); ?>
				</a>
			<?php endif; ?>
			
		</div>
		
	</div>
	
	<?php wp_footer(); ?>
</body>
</html>
