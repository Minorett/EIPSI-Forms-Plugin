# Fix Implementation: Duplicate Function Redeclaration

## Problem
The functions `eipsi_participant_register_handler()`, `eipsi_participant_login_handler()`, `eipsi_participant_logout_handler()`, and `eipsi_participant_info_handler()` are defined in both:
- `/admin/ajax-handlers.php` (lines 2964-3137)
- `/admin/ajax-participant-handlers.php` (lines 30-298)

This causes a fatal PHP error: "Cannot redeclare function"

## Root Cause
The file `ajax-participant-handlers.php` was created in v1.5.5 to handle participant authentication, but the old implementations in `ajax-handlers.php` were not removed.

## Solution
Remove ALL duplicate code from `admin/ajax-handlers.php` (lines 2958-3137), including:
- The add_action hooks for the 4 handlers
- The 4 function definitions

Keep ONLY the rate limiting helper functions:
- `eipsi_check_login_rate_limit()`
- `eipsi_record_failed_login()`
- `eipsi_clear_login_rate_limit()`

## Why This Is Safe
1. The file `ajax-participant-handlers.php` is loaded in `eipsi-forms.php`
2. It contains complete implementations of all 4 handlers
3. It includes the necessary add_action hooks
4. The implementations in `ajax-participant-handlers.php` are more robust:
   - Better validation (e.g., password length check)
   - More detailed error messages in Spanish
   - Better nonce handling ('eipsi_participant_auth')
   - Uses wp_unslash() for proper input handling
   - Includes redirect URL calculation
   - Handles session creation failures gracefully

## Code to Remove (admin/ajax-handlers.php, lines 2958-3137)

```php
/**
 * AJAX Handler: Participant Registration
 *
 * Endpoint: eipsi_participant_register
 * Hooks: wp_ajax_nopriv_eipsi_participant_register, wp_ajax_eipsi_participant_register
 */
add_action('wp_ajax_nopriv_eipsi_participant_register', 'eipsi_participant_register_handler');
add_action('wp_ajax_eipsi_participant_register', 'eipsi_participant_register_handler');

function eipsi_participant_register_handler() {
    // ... entire function body ...
}

/**
 * AJAX Handler: Participant Login
 *
 * Endpoint: eipsi_participant_login
 * Hooks: wp_ajax_nopriv_eipsi_participant_login, wp_ajax_eipsi_participant_login
 */
add_action('wp_ajax_nopriv_eipsi_participant_login', 'eipsi_participant_login_handler');
add_action('wp_ajax_eipsi_participant_login', 'eipsi_participant_login_handler');

function eipsi_participant_login_handler() {
    // ... entire function body ...
}

/**
 * AJAX Handler: Participant Logout
 *
 * Endpoint: eipsi_participant_logout
 * Hooks: wp_ajax_nopriv_eipsi_participant_logout, wp_ajax_eipsi_participant_logout
 */
add_action('wp_ajax_nopriv_eipsi_participant_logout', 'eipsi_participant_logout_handler');
add_action('wp_ajax_eipsi_participant_logout', 'eipsi_participant_logout_handler');

function eipsi_participant_logout_handler() {
    // ... entire function body ...
}

/**
 * AJAX Handler: Get Current Participant Info
 *
 * Endpoint: eipsi_participant_info
 * Hooks: wp_ajax_nopriv_eipsi_participant_info, wp_ajax_eipsi_participant_info
 */
add_action('wp_ajax_nopriv_eipsi_participant_info', 'eipsi_participant_info_handler');
add_action('wp_ajax_eipsi_participant_info', 'eipsi_participant_info_handler');

function eipsi_participant_info_handler() {
    // ... entire function body ...
}
```

## Code to Replace With

```php
// =============================================================================
// PARTICIPANT AUTHENTICATION AJAX HANDLERS
// =============================================================================
// NOTE: These handlers have been moved to ajax-participant-handlers.php (v1.5.5+)
// The add_action hooks and function implementations are now in that file
// to avoid duplication and fatal errors due to function redeclaration.
//
// The following functions are now defined in:
// - admin/ajax-participant-handlers.php:
//   * eipsi_participant_register_handler()
//   * eipsi_participant_login_handler()
//   * eipsi_participant_logout_handler()
//   * eipsi_participant_info_handler()
//
// Rate limiting helper functions (kept here for potential future use):
// * eipsi_check_login_rate_limit()
// * eipsi_record_failed_login()
// * eipsi_clear_login_rate_limit()
// =============================================================================
```

## Verification Steps
1. After removing the code, run: `php -l admin/ajax-handlers.php` - should show "No syntax errors"
2. Check that the 4 functions are NOT in ajax-handlers.php: `grep -c "^function eipsi_participant_.*_handler" admin/ajax-handlers.php` - should return 0
3. Verify the functions exist in ajax-participant-handlers.php
4. Test plugin activation - should show no fatal errors
5. Test participant registration, login, logout, and info endpoints

## Additional Notes
- The rate limiting helper functions (`eipsi_check_login_rate_limit()`, `eipsi_record_failed_login()`, `eipsi_clear_login_rate_limit()`) are NOT duplicated, so they are kept in ajax-handlers.php
- These helper functions are currently NOT used by the new implementations in ajax-participant-handlers.php
- They are kept for potential future use or for other parts of the system that might need them
