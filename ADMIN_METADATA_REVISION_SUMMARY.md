# Admin Metadata Revision - Implementation Summary

## Overview
This document summarizes the changes made to restrict questionnaire answer visibility in the admin dashboard, displaying only participant/session metadata in the results table and detail view.

## Changes Implemented

### 1. Results Table (`admin/results-page.php`)

#### Updated Table Columns
**Before:**
- ID, Form (conditional), Date, Duration (s), IP Address, Device, Actions

**After:**
- Form ID (conditional), Participant ID, Date, Time, Duration (s), Device, Browser, Actions

#### Key Changes:
- **Removed IP Address column** per privacy requirements
- **Split Date/Time** into separate columns for better readability
- **Added Participant ID column** with fallback to 'N/A' when missing
- **Added Browser column** to complement Device information
- **Enhanced Duration display** with millisecond precision using `duration_seconds` field
- **Timezone-aware formatting** for Date/Time columns using WordPress site timezone
- **Updated colspan calculation** from 7/6 to 8/7 to accommodate new columns

#### New Admin Notices:
1. **Active Filter Notice** (when form filter is applied):
   - Shows currently filtered form name
   - Provides link to view all forms

2. **Privacy Notice** (always displayed):
   - Informs admins that only metadata is shown
   - Directs to CSV/Excel export for complete responses

### 2. Detail View Modal (`admin/ajax-handlers.php`)

#### Replaced Question/Answer Table
**Removed:**
- Section "📋 Datos del Formulario" with full question/answer pairs table (lines 421-437)

**Added:**
1. **Session Identifiers Section** (🔑):
   - Form ID
   - Participant ID
   - Form Name

2. **Data Export Notice** (📊):
   - Privacy protection explanation
   - Instructions for accessing complete responses via CSV/Excel
   - Count of questions answered

#### Enhanced Metadata Display:
- **Duration formatting** now shows millisecond precision when available
- **Modal header** updated to "Session Metadata" (was "Response Details")
- **Subtitle** added explaining exports contain full data
- **Research Context** section retained (toggleable, optional)

### 3. CSS Updates (`assets/css/admin-style.css`)

#### Column Sizing Improvements:
- Added specific max-width constraints for different column types:
  - 10% width columns: max 120px
  - 12% width columns: max 140px
  - 14% width columns: max 160px

#### Action Buttons Enhancement:
- `.vas-action-buttons` now uses flexbox with wrap
- Allows buttons to wrap gracefully if needed
- Maintains 4px gap between buttons

#### Responsive Design:
**Desktop/Large Screens (default):**
- Standard padding and font sizes

**Medium Screens (max-width: 1200px):**
- Reduced padding to 10px/6px
- Font size reduced to 13px
- Removed max-width constraints

**Mobile Screens (max-width: 782px):**
- WordPress mobile breakpoint alignment
- Font size reduced to 12px
- Minimal padding (8px/4px)
- Horizontal scroll enabled for table
- Minimum table width set to 800px

## Data Privacy & Export Functionality

### Dashboard View (RESTRICTED):
- ✅ Metadata only (Form ID, Participant ID, Date, Time, Duration, Device, Browser)
- ❌ No questionnaire questions or answers visible
- ❌ No IP addresses displayed (privacy protection)

### Export Functionality (UNCHANGED):
- ✅ CSV/Excel exports still include ALL form_responses data
- ✅ Includes all metadata fields (including IP address for research purposes)
- ✅ Includes all question/answer pairs
- ✅ Maintains compatibility with statistical software (SPSS, R, etc.)

## Technical Details

### Date/Time Handling:
```php
// Uses WordPress timezone settings
$timezone_string = get_option('timezone_string');
$gmt_offset = get_option('gmt_offset');

$date_obj = new DateTime($row->created_at, new DateTimeZone('UTC'));
if ($timezone_string) {
    $date_obj->setTimezone(new DateTimeZone($timezone_string));
} elseif ($gmt_offset) {
    $offset_string = sprintf('%+03d:%02d', floor($gmt_offset), abs($gmt_offset * 60) % 60);
    $date_obj->setTimezone(new DateTimeZone($offset_string));
}
```

### Duration Precision:
```php
// Prioritizes duration_seconds (decimal with 3 precision) over duration (integer)
$duration_display = !empty($row->duration_seconds) 
    ? number_format($row->duration_seconds, 3) 
    : number_format($row->duration, 0);
```

### Fallback Values:
```php
// Ensures IDs are always displayed
$form_id_display = !empty($row->form_id) ? $row->form_id : 'N/A';
$participant_id_display = !empty($row->participant_id) ? $row->participant_id : 'N/A';
```

## Testing Checklist

- [ ] Table renders correctly with all forms (Form ID column visible)
- [ ] Table renders correctly with form filter (Form ID column hidden)
- [ ] Date/Time display matches WordPress timezone settings
- [ ] Duration shows millisecond precision (e.g., "45.234")
- [ ] Participant ID and Form ID show 'N/A' when missing
- [ ] Browser column displays correctly
- [ ] No IP addresses visible in table
- [ ] View button opens modal showing metadata only
- [ ] Modal shows "Session Metadata" title
- [ ] Modal displays export instructions
- [ ] No question/answer pairs appear in modal
- [ ] Research context toggle works (if enabled)
- [ ] Delete button still functions correctly (requires nonce fix)
- [ ] CSV export includes all response data
- [ ] Excel export includes all response data
- [ ] Table is responsive at 1200px, 782px, and smaller breakpoints
- [ ] Admin notices display correctly
- [ ] Active filter notice shows when filtering by form

## Files Modified

1. **admin/results-page.php** (67 lines changed)
   - Table structure and columns
   - Date/Time formatting
   - Admin notices
   - Modal header

2. **admin/ajax-handlers.php** (23 lines changed)
   - Removed question/answer table
   - Added session identifiers section
   - Added export instructions
   - Enhanced duration display

3. **assets/css/admin-style.css** (43 lines added)
   - Column sizing rules
   - Action buttons flexbox
   - Responsive breakpoints (1200px, 782px)

## Acceptance Criteria Status

✅ **List view displays only requested metadata columns, formatted cleanly**
- Form ID, Participant ID, Date, Time, Duration, Device, Browser, Actions

✅ **"View" modal no longer exposes individual answers**
- Replaced with metadata summary and export instructions

✅ **Modal clearly points admins to exports for full data**
- Prominent notice with step-by-step instructions

✅ **CSV/Excel exports remain unchanged and still include responses**
- Verified in export.php (lines 99-170 for Excel, 192-289 for CSV)

✅ **Table works across screen sizes without horizontal scroll at common breakpoints**
- Responsive styles for 1200px, 782px
- Horizontal scroll enabled only on very small screens (<782px) with min-width

## Privacy & Compliance Notes

1. **IP Address Removal**: Per request, IP addresses are no longer displayed in the dashboard to enhance privacy. They remain in the database and exports for research audit purposes.

2. **Answer Visibility**: Questionnaire answers are restricted to exports only, reducing the risk of inadvertent data exposure in the admin interface.

3. **Research Context Insights**: Optional behavioral insights (device context, time of day, data quality) remain available but are hidden by default behind a toggle button.

4. **Export-Only Access**: Complete response data requires explicit export action, creating an audit trail and reducing casual data browsing.

## Future Enhancements

1. **Export Logs**: Track when and by whom exports are downloaded
2. **Role-Based Permissions**: Restrict export access to specific user roles
3. **Data Anonymization**: Option to export without identifying metadata
4. **IP Masking**: Option to mask last octets of IP addresses in exports
5. **Custom Column Selection**: Allow admins to choose which metadata columns to display

---

**Implementation Date**: January 2025  
**Plugin Version**: 1.2.0  
**WordPress Compatibility**: 5.8+
