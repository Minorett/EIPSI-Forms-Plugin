# Export URL Verification

## Expected URL Formats

### Without Form Filter (All Forms)

**CSV Export URL:**
```
/wp-admin/admin.php?page=eipsi-results-experience&action=export_csv
```

**Excel Export URL:**
```
/wp-admin/admin.php?page=eipsi-results-experience&action=export_excel
```

### With Form Filter (Specific Form ID)

**CSV Export URL:**
```
/wp-admin/admin.php?page=eipsi-results-experience&action=export_csv&form_id=PHQ-9-v2.1
```

**Excel Export URL:**
```
/wp-admin/admin.php?page=eipsi-results-experience&action=export_excel&form_id=PHQ-9-v2.1
```

---

## How the Handler Works

When user clicks export button, WordPress follows this flow:

1. **User Action:** User clicks "Download CSV" or "Download Excel" button
2. **Browser Request:** Browser navigates to the export URL
3. **WordPress Init:** `admin_init` action fires (hooked in `export.php`)
4. **Page Check:** Handler checks if `$_GET['page'] === 'eipsi-results-experience'` ✅
5. **Action Check:** Handler checks for `export_csv` or `export_excel` action ✅
6. **Permission Check:** Handler verifies `current_user_can('manage_options')` ✅
7. **Data Retrieval:** Handler fetches data from database (local or external)
8. **Form Filter:** If `form_id` parameter present, filters results ✅
9. **Export Generation:** Handler generates CSV or Excel file
10. **File Download:** Browser receives file with proper headers ✅

---

## Key Code Snippets

### Button Generation (submissions-tab.php:174-175)

```php
$export_params = $current_form ? ['form_id' => $current_form] : [];
$csv_url = add_query_arg(
    array_merge(['page' => 'eipsi-results-experience', 'action' => 'export_csv'], $export_params),
    admin_url('admin.php')
);
$excel_url = add_query_arg(
    array_merge(['page' => 'eipsi-results-experience', 'action' => 'export_excel'], $export_params),
    admin_url('admin.php')
);
```

### Handler Registration (export.php:680-684)

```php
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'eipsi-results-experience') {
        if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
            eipsi_export_to_excel();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            eipsi_export_to_csv();
        }
    }
    // ... longitudinal export handlers
});
```

---

## Error Handling Flow

### Normal Flow (Success)
```
User clicks button
    ↓
Browser navigates to export URL
    ↓
WordPress admin_init fires
    ↓
Handler catches the request
    ↓
Data retrieved and processed
    ↓
File generated with headers
    ↓
Browser downloads file
    ↓
User receives complete export
```

### Error Flow (Exception)
```
User clicks button
    ↓
Browser navigates to export URL
    ↓
WordPress admin_init fires
    ↓
Handler catches the request
    ↓
Exception thrown during processing
    ↓
Try-catch catches exception
    ↓
Error logged to error_log
    ↓
User-friendly error message displayed
    ↓
User sees clear feedback
```

---

## Testing Scenarios

### Scenario 1: Basic CSV Export
**Input:** User clicks "Download CSV" without form filter
**Expected:** All submissions exported to CSV
**URL:** `/wp-admin/admin.php?page=eipsi-results-experience&action=export_csv`

### Scenario 2: Filtered Excel Export
**Input:** User filters by form ID "PHQ-9-v2.1", clicks "Download Excel"
**Expected:** Only PHQ-9-v2.1 submissions exported to Excel
**URL:** `/wp-admin/admin.php?page=eipsi-results-experience&action=export_excel&form_id=PHQ-9-v2.1`

### Scenario 3: No Data Available
**Input:** User clicks export when no submissions exist
**Expected:** Error message "No data to export."
**Behavior:** wp_die() called with error message

### Scenario 4: Permission Denied
**Input:** Non-admin user clicks export
**Expected:** Error message about insufficient permissions
**Behavior:** current_user_can() check fails, wp_die() called

### Scenario 5: Database Error
**Input:** Database connection fails during export
**Expected:** Error message "An error occurred while exporting..."
**Behavior:** Try-catch catches exception, logs error, shows user-friendly message

---

## Debugging Tips

### If Export Not Working:

1. **Check URL Parameters:**
   - Verify URL contains `page=eipsi-results-experience`
   - Verify URL contains `action=export_csv` or `action=export_excel`

2. **Check Browser Console:**
   - Look for JavaScript errors
   - Check network tab for failed requests

3. **Check WordPress Error Log:**
   - Look for "EIPSI Forms Export Error" messages
   - Review detailed exception messages

4. **Verify Permissions:**
   - Ensure user has `manage_options` capability
   - Check user role is Administrator

5. **Test with Different Browser:**
   - Rule out browser-specific issues
   - Test in incognito/private mode

---

## Success Indicators

✅ Export buttons have correct URLs when inspected (right-click → Copy Link Address)
✅ Clicking export button triggers immediate download
✅ Downloaded file contains all expected data
✅ Form filter is respected when active
✅ No JavaScript errors in console
✅ No PHP errors in error log
✅ Export works with both local and external database
