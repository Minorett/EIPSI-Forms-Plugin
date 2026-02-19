# Fix: Duplicate Function `eipsi_ajax_save_cron_reminders_config`

## Issue
The function `eipsi_ajax_save_cron_reminders_config()` was defined in TWO files, causing a PHP fatal error:
```
PHP Fatal error: Cannot redeclare eipsi_ajax_save_cron_reminders_config() 
(previously declared in .../admin/ajax-handlers.php:3363)
```

## Root Cause
Both files were loaded by the main plugin file (`eipsi-forms.php`):
1. Line 97: `require_once ... 'admin/ajax-handlers.php';` (loaded FIRST)
2. Line 101: `require_once ... 'admin/cron-reminders-handler.php';` (loaded AFTER)

Since `ajax-handlers.php` defined the function first, when `cron-reminders-handler.php` tried to define it again, PHP threw a fatal error.

## Solution
Removed the duplicate function from `ajax-handlers.php` (lines 3356-3461).

**Decision Rationale:**
- The function logically belongs in `cron-reminders-handler.php` since it's specifically about cron reminders configuration
- This file is the designated location for cron-related AJAX handlers
- Keeping functionality in specialized files improves code organization

## Files Modified
- `admin/ajax-handlers.php` - Removed duplicate function (lines 3356-3461)

## Files Unchanged
- `admin/cron-reminders-handler.php` - Contains the canonical version of the function (lines 255-313)

## Verification
- ✅ Build successful (`npm run build`)
- ✅ Duplicate function removed from `ajax-handlers.php`
- ✅ Function still exists in `cron-reminders-handler.php`
- ✅ No function redeclaration possible

## Prevention Recommendations
1. **Use `function_exists()` guard** (optional):
   ```php
   if (!function_exists('eipsi_ajax_save_cron_reminders_config')) {
       function eipsi_ajax_save_cron_reminders_config() {
           // ... function code
       }
   }
   ```

2. **Code organization**: Keep related functionality in dedicated files (like we did here - cron functions in `cron-reminders-handler.php`)

3. **IDE/Editor warnings**: Most modern IDEs will warn about duplicate function definitions

4. **Static analysis**: Consider using PHPStan or Psalm for static code analysis

## Date
2025-02-19

## Version
v1.5.5
