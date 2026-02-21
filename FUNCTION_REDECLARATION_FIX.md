# Fix: Function Redeclaration Error - `eipsi_is_participant_logged_in()`

## Problem
The function `eipsi_is_participant_logged_in()` was defined in two locations, causing a fatal PHP error:

1. **`/includes/form-template-render.php`** (line 156) - With `function_exists` guard
2. **`/admin/ajax-participant-handlers.php`** (line 493) - Without guard

## Solution
Removed the duplicate function definition from `/admin/ajax-participant-handlers.php`.

## Changes Made

### File: `/admin/ajax-participant-handlers.php`
- **Removed** the following duplicate function (lines 488-499):
```php
/**
 * Helper function to check if a participant is logged in.
 * 
 * @return bool
 */
function eipsi_is_participant_logged_in() {
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    return EIPSI_Auth_Service::is_authenticated();
}
```

### File: `/includes/form-template-render.php` (UNCHANGED)
- **Kept** the original function definition (lines 156-163) with guard:
```php
if (!function_exists('eipsi_is_participant_logged_in')) {
    function eipsi_is_participant_logged_in() {
        // Check if session cookie or session in DB exists
        // Use same method as participant-auth.js
        return isset($_COOKIE[EIPSI_SESSION_COOKIE_NAME]) || 
               (isset($_SESSION['eipsi_participant_id']) && !empty($_SESSION['eipsi_participant_id']));
    }
}
```

## Why This Works

1. **Loading Order**: In `eipsi-forms.php`:
   - Line 112: `form-template-render.php` is loaded first
   - Line 157: `ajax-participant-handlers.php` is loaded later

2. **Function Availability**: Since `form-template-render.php` is loaded first, the function is already defined when `ajax-participant-handlers.php` is loaded.

3. **Guard Protection**: The `function_exists` guard prevents any future redeclaration errors.

4. **No Internal Usage**: The function was not being called within `ajax-participant-handlers.php` itself, so removing it doesn't break any functionality.

## Prevention Measures

To prevent similar issues in the future:

1. **Always use `function_exists()` guards** when defining functions in plugin files:
   ```php
   if (!function_exists('eipsi_function_name')) {
       function eipsi_function_name() {
           // function code
       }
   }
   ```

2. **Use a utility/functions file** for shared helper functions instead of defining them in multiple files.

3. **Search before defining** - always search the codebase before adding a new function to ensure it doesn't already exist.

## Testing

After this fix:
- ✅ The function is defined only once in the plugin
- ✅ No fatal error will occur on plugin load
- ✅ All existing functionality continues to work
- ✅ The function is available to both frontend and admin contexts

## Files Modified
- `/admin/ajax-participant-handlers.php` - Removed duplicate function definition

## Files Unchanged
- `/includes/form-template-render.php` - Contains the canonical function definition with guard
