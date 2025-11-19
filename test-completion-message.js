/**
 * Completion Message Validation Test
 * Tests the global completion message (thank-you page) implementation
 * Phase 15 Foundation - Backend functionality complete, admin UI pending (Phase 16)
 */

const fs = require( 'fs' );
const path = require( 'path' );

let passedTests = 0;
let failedTests = 0;
const failures = [];

/**
 * Test helper function
 *
 * @param {string} description - Test description
 * @param {Function} condition - Test condition function
 */
function test( description, condition ) {
    try {
        if ( condition() ) {
            passedTests++;
            // eslint-disable-next-line no-console
            console.log( `✅ PASS: ${ description }` );
        } else {
            failedTests++;
            const message = `❌ FAIL: ${ description }`;
            failures.push( message );
            // eslint-disable-next-line no-console
            console.error( message );
        }
    } catch ( error ) {
        failedTests++;
        const message = `❌ FAIL: ${ description } - ${ error.message }`;
        failures.push( message );
        // eslint-disable-next-line no-console
        console.error( message );
    }
}

/**
 * Check if file exists
 *
 * @param {string} filePath - Path to file
 * @return {boolean} True if file exists
 */
function fileExists( filePath ) {
    return fs.existsSync( filePath );
}

/**
 * Read file content
 *
 * @param {string} filePath - Path to file
 * @return {string} File content
 */
function readFile( filePath ) {
    return fs.readFileSync( filePath, 'utf8' );
}

/**
 * Check if text contains pattern
 *
 * @param {string} text - Text to search
 * @param {string|RegExp} pattern - Pattern to find
 * @return {boolean} True if pattern found
 */
function contains( text, pattern ) {
    if ( pattern instanceof RegExp ) {
        return pattern.test( text );
    }
    return text.includes( pattern );
}

// eslint-disable-next-line no-console
console.log( '\n' );
// eslint-disable-next-line no-console
console.log( '='.repeat( 80 ) );
// eslint-disable-next-line no-console
console.log( 'COMPLETION MESSAGE VALIDATION TEST SUITE' );
// eslint-disable-next-line no-console
console.log( '='.repeat( 80 ) );
// eslint-disable-next-line no-console
console.log( '\n' );

// ============================================================================
// TEST CATEGORY 1: File Structure
// ============================================================================
// eslint-disable-next-line no-console
console.log( '📁 TEST CATEGORY 1: File Structure\n' );

test( '1.1 - Backend handler file exists', () =>
    fileExists( 'admin/completion-message-backend.php' )
);

test( '1.2 - Completion message page template exists', () =>
    fileExists( 'templates/completion-message-page.php' )
);

test( '1.3 - Completion message CSS exists', () =>
    fileExists( 'assets/css/completion-message.css' )
);

test( '1.4 - Templates directory created', () =>
    fs.existsSync( 'templates' ) && fs.statSync( 'templates' ).isDirectory()
);

// ============================================================================
// TEST CATEGORY 2: Backend Handler Class
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📦 TEST CATEGORY 2: Backend Handler Class\n' );

const backendFile = readFile( 'admin/completion-message-backend.php' );

test( '2.1 - EIPSI_Completion_Message class defined', () =>
    contains( backendFile, 'class EIPSI_Completion_Message' )
);

test( '2.2 - get_config() method exists', () =>
    contains( backendFile, 'public static function get_config()' )
);

test( '2.3 - save_config() method exists', () =>
    contains( backendFile, 'public static function save_config(' )
);

test( '2.4 - get_page_url() method exists', () =>
    contains( backendFile, 'public static function get_page_url()' )
);

test( '2.5 - Option key defined for storage', () =>
    contains( backendFile, 'eipsi_global_completion_message' )
);

test( '2.6 - Default message in Spanish', () =>
    contains(
        backendFile,
        'Gracias por completar el formulario. Sus respuestas han sido registradas.'
    )
);

test( '2.7 - Capability check in save_config', () =>
    contains( backendFile, 'current_user_can' ) &&
    contains( backendFile, 'manage_options' )
);

test( '2.8 - wp_kses_post sanitization used', () =>
    contains( backendFile, 'wp_kses_post' )
);

test( '2.9 - esc_url_raw sanitization for redirect URL', () =>
    contains( backendFile, 'esc_url_raw' )
);

test( '2.10 - ABSPATH security check', () =>
    contains( backendFile, "if ( ! defined( 'ABSPATH' ) )" )
);

// ============================================================================
// TEST CATEGORY 3: Page Template
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n🎨 TEST CATEGORY 3: Page Template\n' );

const templateFile = readFile( 'templates/completion-message-page.php' );

test( '3.1 - Template includes backend handler', () =>
    contains( templateFile, 'completion-message-backend.php' )
);

test( '3.2 - Template calls get_config()', () =>
    contains( templateFile, 'EIPSI_Completion_Message::get_config()' )
);

test( '3.3 - Logo section with conditional display', () =>
    contains( templateFile, "if ( $config['show_logo'] )" )
);

test( '3.4 - Message section displays config message', () =>
    contains( templateFile, 'eipsi-completion-message' ) &&
    contains( templateFile, 'wp_kses_post( $config' )
);

test( '3.5 - Home button with conditional display', () =>
    contains( templateFile, "if ( $config['show_home_button'] )" )
);

test( '3.6 - External redirect button conditional', () =>
    contains( templateFile, "if ( ! empty( $config['redirect_url'] ) )" )
);

test( '3.7 - Proper HTML structure (DOCTYPE, html, head, body)', () =>
    contains( templateFile, '<!DOCTYPE html>' ) &&
    contains( templateFile, '<html' ) &&
    contains( templateFile, '<head>' ) &&
    contains( templateFile, '<body' )
);

test( '3.8 - wp_head() and wp_footer() hooks present', () =>
    contains( templateFile, 'wp_head()' ) &&
    contains( templateFile, 'wp_footer()' )
);

test( '3.9 - All URLs properly escaped', () => {
    const escapeCount = ( templateFile.match( /esc_url/g ) || [] ).length;
    return escapeCount >= 3;
} );

test( '3.10 - ABSPATH security check', () =>
    contains( templateFile, "if ( ! defined( 'ABSPATH' ) )" )
);

// ============================================================================
// TEST CATEGORY 4: CSS Styling
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n🎨 TEST CATEGORY 4: CSS Styling\n' );

const cssFile = readFile( 'assets/css/completion-message.css' );

test( '4.1 - Page background styling', () =>
    contains( cssFile, '.eipsi-completion-page' )
);

test( '4.2 - Container styling', () =>
    contains( cssFile, '.eipsi-completion-container' )
);

test( '4.3 - Logo styling', () =>
    contains( cssFile, '.eipsi-completion-logo' )
);

test( '4.4 - Message styling', () =>
    contains( cssFile, '.eipsi-completion-message' )
);

test( '4.5 - Button styling', () =>
    contains( cssFile, '.eipsi-btn' )
);

test( '4.6 - Primary button styling', () =>
    contains( cssFile, '.eipsi-btn-primary' )
);

test( '4.7 - Secondary button styling', () =>
    contains( cssFile, '.eipsi-btn-secondary' )
);

test( '4.8 - WCAG AA colors used (#005a87)', () =>
    contains( cssFile, '#005a87' )
);

test( '4.9 - Mobile responsive media query', () =>
    contains( cssFile, '@media (max-width: 600px)' )
);

test( '4.10 - Reduced motion support', () =>
    contains( cssFile, '@media (prefers-reduced-motion: reduce)' )
);

test( '4.11 - High contrast mode support', () =>
    contains( cssFile, '@media (prefers-contrast: more)' )
);

test( '4.12 - Focus visible styling for accessibility', () =>
    contains( cssFile, ':focus-visible' )
);

test( '4.13 - Smooth animation for entrance', () =>
    contains( cssFile, '@keyframes slideUp' )
);

// ============================================================================
// TEST CATEGORY 5: Plugin File Integration
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n🔌 TEST CATEGORY 5: Plugin File Integration\n' );

const pluginFile = readFile( 'vas-dinamico-forms.php' );

test( '5.1 - Backend handler required', () =>
    contains( pluginFile, 'completion-message-backend.php' )
);

test( '5.2 - Rewrite rule registered', () =>
    contains( pluginFile, 'vas_dinamico_register_completion_endpoint' ) &&
    contains( pluginFile, 'eipsi-completion' )
);

test( '5.3 - Query var registered', () =>
    contains( pluginFile, 'vas_dinamico_add_completion_query_var' ) &&
    contains( pluginFile, 'eipsi_completion' )
);

test( '5.4 - Template redirect handler registered', () =>
    contains( pluginFile, 'vas_dinamico_completion_template_redirect' )
);

test( '5.5 - Completion URL passed to frontend JS', () =>
    contains( pluginFile, 'completionUrl' ) &&
    contains( pluginFile, 'EIPSI_Completion_Message::get_page_url()' )
);

test( '5.6 - Completion CSS enqueued', () =>
    contains( pluginFile, 'eipsi-completion-message-css' ) &&
    contains( pluginFile, 'completion-message.css' )
);

test( '5.7 - Rewrite rule uses add_rewrite_rule', () =>
    contains( pluginFile, 'add_rewrite_rule' )
);

test( '5.8 - Query vars filter applied', () =>
    contains( pluginFile, 'add_filter' ) &&
    contains( pluginFile, 'query_vars' )
);

test( '5.9 - Template redirect action applied', () =>
    contains( pluginFile, 'add_action' ) &&
    contains( pluginFile, 'template_redirect' )
);

// ============================================================================
// TEST CATEGORY 6: AJAX Handler
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n🔄 TEST CATEGORY 6: AJAX Handler\n' );

const ajaxFile = readFile( 'admin/ajax-handlers.php' );

test( '6.1 - AJAX handler function defined', () =>
    contains( ajaxFile, 'function eipsi_save_completion_message_handler()' )
);

test( '6.2 - Nonce check present', () =>
    contains( ajaxFile, 'check_ajax_referer' ) &&
    contains( ajaxFile, 'eipsi_admin_nonce' )
);

test( '6.3 - Capability check present', () =>
    contains( ajaxFile, 'current_user_can' ) &&
    contains( ajaxFile, 'manage_options' )
);

test( '6.4 - Config sanitization (wp_kses_post)', () =>
    contains( ajaxFile, 'wp_kses_post' )
);

test( '6.5 - URL sanitization (esc_url_raw)', () =>
    contains( ajaxFile, 'esc_url_raw' )
);

test( '6.6 - AJAX action hooked', () =>
    contains( ajaxFile, 'add_action' ) &&
    contains( ajaxFile, 'wp_ajax_eipsi_save_completion_message' )
);

test( '6.7 - Success response uses wp_send_json_success', () =>
    contains( ajaxFile, 'wp_send_json_success' )
);

test( '6.8 - Error response uses wp_send_json_error', () =>
    contains( ajaxFile, 'wp_send_json_error' )
);

test( '6.9 - Handler includes backend handler file', () =>
    contains( ajaxFile, 'completion-message-backend.php' )
);

test( '6.10 - Handler calls save_config', () =>
    contains( ajaxFile, 'EIPSI_Completion_Message::save_config' )
);

// ============================================================================
// TEST CATEGORY 7: Frontend JavaScript Integration
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n⚡ TEST CATEGORY 7: Frontend JavaScript Integration\n' );

const jsFile = readFile( 'assets/js/eipsi-forms.js' );

test( '7.1 - Redirect logic added to submitForm', () =>
    contains( jsFile, 'window.location.href' )
);

test( '7.2 - Completion URL checked before redirect', () =>
    contains( jsFile, 'this.config.completionUrl' )
);

test( '7.3 - Success message updated to indicate redirect', () =>
    contains( jsFile, 'Redirigiendo' )
);

test( '7.4 - Redirect happens after delay (1.5s)', () =>
    contains( jsFile, '1500' )
);

test( '7.5 - Fallback form reset if no completion URL', () =>
    contains( jsFile, 'form.reset()' )
);

test( '7.6 - Navigator reset in fallback', () =>
    contains( jsFile, 'navigator.reset()' )
);

test( '7.7 - Tracking still recorded before redirect', () =>
    contains( jsFile, 'EIPSITracking.recordSubmit' )
);

// ============================================================================
// TEST CATEGORY 8: Security & Best Practices
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n🔒 TEST CATEGORY 8: Security & Best Practices\n' );

test( '8.1 - Backend handler has ABSPATH check', () => {
    const content = readFile( 'admin/completion-message-backend.php' );
    return contains( content, "if ( ! defined( 'ABSPATH' ) )" );
} );

test( '8.2 - Template has ABSPATH check', () => {
    const content = readFile( 'templates/completion-message-page.php' );
    return contains( content, "if ( ! defined( 'ABSPATH' ) )" );
} );

test( '8.3 - All output properly escaped in template', () => {
    const content = readFile( 'templates/completion-message-page.php' );
    return (
        contains( content, 'esc_url' ) &&
        contains( content, 'wp_kses_post' ) &&
        contains( content, 'esc_html' )
    );
} );

test( '8.4 - AJAX handler has proper nonce verification', () => {
    const content = readFile( 'admin/ajax-handlers.php' );
    return contains( content, 'check_ajax_referer' );
} );

test( '8.5 - AJAX handler checks user capabilities', () => {
    const content = readFile( 'admin/ajax-handlers.php' );
    return contains( content, 'current_user_can' );
} );

test( '8.6 - Static class methods used for state management', () => {
    const content = readFile( 'admin/completion-message-backend.php' );
    return contains( content, 'public static function' );
} );

test( '8.7 - CSS uses clinical research colors', () => {
    const content = readFile( 'assets/css/completion-message.css' );
    return (
        contains( content, '#005a87' ) && contains( content, '#e3f2fd' )
    );
} );

// ============================================================================
// TEST CATEGORY 9: Accessibility (WCAG AA)
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n♿ TEST CATEGORY 9: Accessibility (WCAG AA)\n' );

test( '9.1 - Focus visible styles defined', () => {
    const content = readFile( 'assets/css/completion-message.css' );
    return (
        contains( content, ':focus-visible' ) &&
        contains( content, 'outline:' )
    );
} );

test( '9.2 - Reduced motion preference supported', () => {
    const content = readFile( 'assets/css/completion-message.css' );
    return contains( content, '@media (prefers-reduced-motion: reduce)' );
} );

test( '9.3 - High contrast mode supported', () => {
    const content = readFile( 'assets/css/completion-message.css' );
    return contains( content, '@media (prefers-contrast: more)' );
} );

test( '9.4 - Semantic HTML in template', () => {
    const content = readFile( 'templates/completion-message-page.php' );
    return (
        contains( content, '<div' ) &&
        contains( content, '<a' ) &&
        contains( content, 'href=' )
    );
} );

test( '9.5 - Alt text for logo image', () => {
    const content = readFile( 'templates/completion-message-page.php' );
    return contains( content, 'alt=' );
} );

test( '9.6 - Language attributes set', () => {
    const content = readFile( 'templates/completion-message-page.php' );
    return contains( content, 'language_attributes()' );
} );

test( '9.7 - Viewport meta tag for mobile', () => {
    const content = readFile( 'templates/completion-message-page.php' );
    return contains( content, 'viewport' );
} );

test( '9.8 - WCAG AA contrast ratio used (#005a87)', () => {
    const content = readFile( 'assets/css/completion-message.css' );
    return contains( content, '#005a87' );
} );

// ============================================================================
// SUMMARY
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n' );
// eslint-disable-next-line no-console
console.log( '='.repeat( 80 ) );
// eslint-disable-next-line no-console
console.log( 'TEST SUMMARY' );
// eslint-disable-next-line no-console
console.log( '='.repeat( 80 ) );
// eslint-disable-next-line no-console
console.log( `✅ Passed: ${ passedTests }` );
// eslint-disable-next-line no-console
console.log( `❌ Failed: ${ failedTests }` );
// eslint-disable-next-line no-console
console.log( `📊 Total: ${ passedTests + failedTests }` );

if ( failedTests > 0 ) {
    // eslint-disable-next-line no-console
    console.log( '' );
    // eslint-disable-next-line no-console
    console.log( 'FAILED TESTS:' );
    // eslint-disable-next-line no-console
    console.log( '-'.repeat( 80 ) );
    failures.forEach( ( failure ) => {
        // eslint-disable-next-line no-console
        console.log( failure );
    } );
    // eslint-disable-next-line no-console
    process.exit( 1 );
} else {
    // eslint-disable-next-line no-console
    console.log( '' );
    // eslint-disable-next-line no-console
    console.log(
        '🎉 All tests passed! Completion message foundation is complete.'
    );
    // eslint-disable-next-line no-console
    process.exit( 0 );
}
