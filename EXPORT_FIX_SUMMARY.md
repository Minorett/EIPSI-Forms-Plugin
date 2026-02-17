# EIPSI Forms - Export Buttons Fix Summary

**Date:** 2025-02-17
**Version:** v1.5.5
**Issue:** Download CSV and Excel buttons not working in 📊 Submissions section

---

## Problem Identified

The Download CSV and Download Excel buttons in the Submissions section were not working due to two issues:

1. **Page Slug Mismatch**: The export handler in `export.php` was checking for `$_GET['page'] === 'eipsi-results'` but the actual page slug is `'eipsi-results-experience'`

2. **Missing Page Parameter**: The export buttons in `submissions-tab.php` were not including the `page` parameter in the URL, and were not providing a base URL to `add_query_arg()`

---

## Changes Made

### 1. Fixed Export Handler Page Check (`admin/export.php`)

**Location:** Line 668

**Before:**
```php
if (isset($_GET['page']) && $_GET['page'] === 'eipsi-results') {
```

**After:**
```php
if (isset($_GET['page']) && $_GET['page'] === 'eipsi-results-experience') {
```

**Rationale:** Updated the page check to match the actual page slug defined in `menu.php`

---

### 2. Fixed Export Button URLs (`admin/tabs/submissions-tab.php`)

**Location:** Lines 174-175

**Before:**
```php
$export_params = $current_form ? ['form_id' => $current_form] : [];
$csv_url = add_query_arg(array_merge(['action' => 'export_csv'], $export_params));
$excel_url = add_query_arg(array_merge(['action' => 'export_excel'], $export_params));
```

**After:**
```php
$export_params = $current_form ? ['form_id' => $current_form] : [];
$csv_url = add_query_arg(array_merge(['page' => 'eipsi-results-experience', 'action' => 'export_csv'], $export_params), admin_url('admin.php'));
$excel_url = add_query_arg(array_merge(['page' => 'eipsi-results-experience', 'action' => 'export_excel'], $export_params), admin_url('admin.php'));
```

**Rationale:**
- Added `page => 'eipsi-results-experience'` to the query parameters
- Added `admin_url('admin.php')` as the base URL for `add_query_arg()`
- This ensures the buttons generate proper URLs like:
  - `/wp-admin/admin.php?page=eipsi-results-experience&action=export_csv`
  - `/wp-admin/admin.php?page=eipsi-results-experience&action=export_excel`

---

### 3. Added Error Handling to Excel Export (`admin/export.php`)

**Location:** Lines 62-369

**Changes:**
- Wrapped entire `eipsi_export_to_excel()` function in `try-catch` block
- Added error logging to `error_log` for debugging
- Provides user-friendly error message via `wp_die()` on failure

**Before:**
```php
function eipsi_export_to_excel() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
    }
    // ... rest of function
    exit;
}
```

**After:**
```php
function eipsi_export_to_excel() {
    try {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
        }
        // ... rest of function
        exit;
    } catch (Exception $e) {
        error_log('EIPSI Forms Export Error (Excel): ' . $e->getMessage());
        wp_die(__('An error occurred while exporting to Excel. Please try again or contact support if the problem persists.', 'eipsi-forms'));
    }
}
```

---

### 4. Added Error Handling to CSV Export (`admin/export.php`)

**Location:** Lines 371-678

**Changes:**
- Wrapped entire `eipsi_export_to_csv()` function in `try-catch` block
- Added error logging to `error_log` for debugging
- Added safe cleanup of file handle in case of error
- Provides user-friendly error message via `wp_die()` on failure

**Before:**
```php
function eipsi_export_to_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
    }
    // ... rest of function
    fclose($output);
    exit;
}
```

**After:**
```php
function eipsi_export_to_csv() {
    try {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
        }
        // ... rest of function
        fclose($output);
        exit;
    } catch (Exception $e) {
        error_log('EIPSI Forms Export Error (CSV): ' . $e->getMessage());
        if (isset($output) && is_resource($output)) {
            fclose($output);
        }
        wp_die(__('An error occurred while exporting to CSV. Please try again or contact support if the problem persists.', 'eipsi-forms'));
    }
}
```

---

## Testing Checklist

- ✅ Export buttons now include correct page parameter
- ✅ Export handler checks for correct page slug
- ✅ URLs are properly formatted with base URL
- ✅ Error handling prevents silent failures
- ✅ Error messages provide clear feedback to users
- ✅ Errors are logged to WordPress error log for debugging
- ✅ Form filter (`form_id`) is properly passed in export URLs
- ✅ Backward compatibility with existing external database setup

---

## Expected Behavior

### Before Fix
- Clicking "Download CSV" button → 404 or no response
- Clicking "Download Excel" button → 404 or no response
- No error feedback shown to user

### After Fix
- Clicking "Download CSV" button → CSV file downloads with all submission data
- Clicking "Download Excel" button → Excel (.xlsx) file downloads with all submission data
- Form filter is respected (only filtered data exported)
- If export fails, user sees clear error message
- Errors are logged to WordPress error log for debugging

---

## Files Modified

1. `/home/engine/project/admin/export.php`
   - Fixed page slug check in export handler
   - Added try-catch error handling to `eipsi_export_to_excel()`
   - Added try-catch error handling to `eipsi_export_to_csv()`

2. `/home/engine/project/admin/tabs/submissions-tab.php`
   - Fixed export button URL generation to include page parameter
   - Added base URL to `add_query_arg()` calls

---

## No Breaking Changes

- All existing functionality preserved
- Backward compatible with external database setup
- No changes to database schema
- No changes to export data format
- No changes to privacy settings

---

## Security Considerations

- ✅ All existing security checks maintained
- ✅ `current_user_can('manage_options')` still enforced
- ✅ Input validation for `form_id` filter preserved
- ✅ SQL injection protection maintained (prepared statements)
- ✅ External database connection security preserved

---

## Performance Impact

- No performance impact
- Same query execution as before
- Minimal overhead from try-catch blocks (only on error)
- No additional database queries

---

## Future Improvements (Optional)

1. Add loading indicators on export buttons
2. Add progress bar for large exports
3. Add export history/log for tracking
4. Add email notification when export completes (for very large datasets)
5. Add export scheduling feature
6. Add export template customization

---

## Notes

- Export functionality supports both local and external database configurations
- Privacy settings (IP, device, browser, OS) are respected in exports
- Form filtering works correctly
- Page timings and field timings are included when available
- Randomization data is included when applicable
