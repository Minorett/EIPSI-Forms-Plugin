# EIPSI Forms - Admin Workflows Manual Testing Guide (Phase 7)

**Version:** 1.0.0  
**Date:** January 2025  
**Status:** QA Phase 7 - Admin Interface  

## Overview

This guide provides step-by-step instructions for manually testing all admin-side functionality in the EIPSI Forms plugin. These tests complement the automated validation script and require a live WordPress installation.

---

## Prerequisites

### Environment Setup

1. **WordPress Installation**
   - WordPress 6.0+ installed
   - Admin user with `manage_options` capability
   - PHP 7.4+ and MySQL 5.7+

2. **Plugin Activation**
   - EIPSI Forms plugin activated
   - Run `npm run build` to compile Gutenberg blocks (if applicable)
   - Verify admin menu appears under "EIPSI Forms"

3. **Sample Data**
   - Create at least 3 different forms with varying numbers of pages and fields
   - Submit 10+ responses across different forms
   - Include forms with conditional logic and VAS sliders

4. **Browser Setup**
   - Chrome or Firefox (latest version)
   - DevTools ready for inspecting console and network traffic
   - Browser zoom at 100%

---

## Test Suite

### 1. Gutenberg Block Editor (30 minutes)

#### 1.1 Form Container Block Insertion

**Steps:**
1. Navigate to **Pages → Add New** or **Posts → Add New**
2. Click the **+** button to add a block
3. Search for "EIPSI Form Container"
4. Insert the block

**Expected Results:**
- ✅ Block appears in block inserter
- ✅ Block is inserted without console errors
- ✅ Block shows placeholder with "Form Settings" panel

**Screenshot:** `phase7/block-insertion.png`

---

#### 1.2 Inspector Controls - Basic Settings

**Steps:**
1. With Form Container selected, open the right sidebar (Inspector)
2. In "Form Settings" panel:
   - Enter Form ID: `test-form-001`
   - Change Submit Button Label: `Complete Survey`
   - Add Description: `This is a test form for QA validation`
3. Check the "Allow Backwards Navigation" toggle

**Expected Results:**
- ✅ All fields accept input without errors
- ✅ Changes persist when block is deselected and reselected
- ✅ Form ID appears in block preview
- ✅ allowBackwardsNav attribute is stored

**Screenshot:** `phase7/inspector-basic-settings.png`

---

#### 1.3 FormStylePanel - Preset Application

**Steps:**
1. Scroll down in Inspector to "Form Styling" panel
2. Click on each preset button:
   - Clinical Blue (default)
   - Warm Earth
   - Cool Mint
   - Soft Lavender
   - High Contrast
   - Dark EIPSI
3. Observe color changes in block preview

**Expected Results:**
- ✅ Each preset applies immediately
- ✅ Block preview updates with new colors
- ✅ Active preset is highlighted
- ✅ No console errors during preset application

**Screenshot:** `phase7/preset-application.png` (show 3 different presets)

---

#### 1.4 FormStylePanel - Custom Colors

**Steps:**
1. In "Form Styling" panel, expand "Colors" section
2. Change the following:
   - Primary Button Background: `#d32f2f` (red)
   - Primary Button Hover: `#b71c1c` (darker red)
   - Background Subtle: `#fff3e0` (light orange)
3. Check contrast rating indicators

**Expected Results:**
- ✅ Color pickers open and close properly
- ✅ Colors update in real-time in preview
- ✅ Contrast ratings show (WCAG AA/AAA or warnings)
- ✅ Active preset indicator clears on manual change

**Screenshot:** `phase7/custom-colors.png`

---

#### 1.5 FormStylePanel - Typography & Spacing

**Steps:**
1. Expand "Typography" section:
   - Change Base Font Size: `18px`
   - Change Line Height: `1.8`
2. Expand "Spacing" section:
   - Change Form Padding: `40px`
   - Change Field Spacing: `24px`
3. Save the post/page

**Expected Results:**
- ✅ Range sliders respond smoothly
- ✅ Values display in labels (e.g., "16px", "1.6")
- ✅ Preview updates with new spacing
- ✅ Post saves without errors

**Screenshot:** `phase7/typography-spacing.png`

---

#### 1.6 Form Structure - Pages and Fields

**Steps:**
1. Inside Form Container, click **+** button
2. Add "Form Page" block (from "EIPSI Forms" category)
3. Inside Form Page, add:
   - Text Field (with label "Full Name")
   - Email Field (with label "Email Address")
   - Textarea (with label "Comments")
   - Radio Group (with 3 options)
   - Likert Scale (5-point scale)
   - VAS Slider (0-100 range)
4. Add a second Form Page with 2-3 more fields
5. Save and preview the page

**Expected Results:**
- ✅ All blocks insert without errors
- ✅ Block hierarchy shows: Container → Page → Fields
- ✅ Editor preview renders fields properly
- ✅ Frontend preview shows multi-page form with navigation
- ✅ CSS variables from styleConfig apply on frontend

**Screenshot:** `phase7/form-structure.png` (editor view)  
**Screenshot:** `phase7/form-preview.png` (frontend view)

---

#### 1.7 Block Validation

**Steps:**
1. Save the post/page with the form
2. Reload the editor
3. Check for block validation errors (yellow/red banners)
4. Inspect browser console for warnings

**Expected Results:**
- ✅ No validation errors on reload
- ✅ All attributes preserved correctly
- ✅ styleConfig serializes and deserializes properly
- ✅ No "This block contains unexpected or invalid content" messages

**Screenshot:** `phase7/block-validation.png` (show no errors)

---

### 2. Results Page (25 minutes)

#### 2.1 Navigate to Results Page

**Steps:**
1. Go to WordPress admin dashboard
2. Click **EIPSI Forms → Form Responses**
3. Observe the page layout

**Expected Results:**
- ✅ Page loads without errors
- ✅ Privacy notice about metadata-only view is visible
- ✅ Filter dropdown contains all form names
- ✅ Export buttons (CSV, Excel) are visible
- ✅ Table shows 8 columns when "All Forms" selected

**Screenshot:** `phase7/results-page-all.png`

---

#### 2.2 Form Filtering

**Steps:**
1. Select a specific form from "Filter by Form" dropdown
2. Observe URL change (e.g., `?form_filter=test-form-001`)
3. Check table columns

**Expected Results:**
- ✅ Table filters to show only responses from selected form
- ✅ Active Filter notice appears above table
- ✅ "View All Forms" link is present
- ✅ Form ID column disappears (7 columns total now)
- ✅ Export buttons update to include form filter

**Screenshot:** `phase7/results-filtered.png`

---

#### 2.3 View Response Modal (AJAX)

**Steps:**
1. Click the **👁️ (eye icon)** button on any response row
2. Wait for modal to load
3. Inspect network tab for AJAX request to `eipsi_get_response_details`
4. Review modal content

**Expected Results:**
- ✅ Modal opens immediately with "Loading..." message
- ✅ AJAX request completes successfully (200 status)
- ✅ Modal displays:
   - Session metadata (Form ID, Participant ID, timestamps)
   - Device information (browser, OS, screen width)
   - Duration (in seconds with 3 decimal places)
   - Research context section (if implemented)
- ✅ No console errors

**Screenshot:** `phase7/view-modal.png`  
**HAR Export:** `phase7/ajax-get-response-details.har`

---

#### 2.4 Research Context Toggle (If Implemented)

**Steps:**
1. In the View Response modal, locate "🧠 Show Research Context" button
2. Click the button
3. Click again to hide

**Expected Results:**
- ✅ Section expands/collapses smoothly
- ✅ Button text changes between "Show" and "Hide"
- ✅ Button color changes on toggle
- ✅ No console errors

**Screenshot:** `phase7/research-context-toggle.png`

---

#### 2.5 Close Modal

**Steps:**
1. Click the **×** (close button) in modal
2. Click outside the modal (on gray overlay)
3. Press **Escape** key (if supported)

**Expected Results:**
- ✅ Modal closes on all three methods
- ✅ Modal content clears properly
- ✅ Page underneath remains functional

---

#### 2.6 Delete Response with Nonce

**Steps:**
1. Click the **🗑️ (trash icon)** button on any response row
2. Observe confirmation dialog
3. Click **OK** to confirm
4. Inspect URL for nonce parameter (e.g., `_wpnonce=abc123...`)

**Expected Results:**
- ✅ Confirmation dialog appears with proper message
- ✅ URL includes nonce with pattern `delete_response_{ID}`
- ✅ Page reloads with success notice: "Response deleted successfully."
- ✅ Response is removed from table
- ✅ Database record is deleted (verify in phpMyAdmin if needed)

**Screenshot:** `phase7/delete-confirmation.png`  
**Screenshot:** `phase7/delete-success.png`

---

#### 2.7 Delete Error States

**Steps:**
1. Manually modify a delete URL to have invalid nonce
2. Access the URL directly
3. Observe error message

**Expected Results:**
- ✅ Error notice appears: "Security check failed. Please refresh the page and try again."
- ✅ No response is deleted
- ✅ Error parameter in URL: `?error=nonce`

**Screenshot:** `phase7/delete-error-nonce.png`

---

#### 2.8 Date/Time Formatting

**Steps:**
1. Note WordPress timezone setting (**Settings → General**)
2. Compare timestamps in results table vs database (UTC)
3. Check for consistency across multiple responses

**Expected Results:**
- ✅ Dates formatted as `Y-m-d` (e.g., `2025-01-15`)
- ✅ Times formatted as `H:i:s` (e.g., `14:23:45`)
- ✅ Times reflect WordPress timezone (not UTC)
- ✅ Duration shows 3 decimal places (e.g., `127.456`)

**Screenshot:** `phase7/datetime-formatting.png`

---

#### 2.9 Empty States

**Steps:**
1. Filter by a form with no responses
2. Delete all responses from a form
3. Observe empty table message

**Expected Results:**
- ✅ Table shows centered message: "No responses found."
- ✅ Message spans correct colspan (8 or 7)
- ✅ No JavaScript errors
- ✅ Export buttons remain visible (but trigger "No data to export" on click)

**Screenshot:** `phase7/empty-table.png`

---

### 3. Configuration Panel (20 minutes)

#### 3.1 Navigate to Configuration

**Steps:**
1. Go to **EIPSI Forms → Configuration**
2. Observe page layout

**Expected Results:**
- ✅ Page loads without errors
- ✅ Database indicator banner shows current storage location
- ✅ Badge displays "WordPress Database" (if no external DB)
- ✅ Connection form is visible with 4 fields
- ✅ Test Connection button is enabled
- ✅ Save Configuration button is **disabled** by default

**Screenshot:** `phase7/config-initial-state.png`

---

#### 3.2 Test Connection - Valid Credentials

**Steps:**
1. Enter valid database credentials:
   - Host: `localhost` (or actual DB host)
   - Username: `test_user`
   - Password: `test_password`
   - Database Name: `research_db`
2. Click **Test Connection**
3. Wait for AJAX response

**Expected Results:**
- ✅ Button shows loading state (disabled + "eipsi-loading" class)
- ✅ AJAX request to `eipsi_test_db_connection` completes
- ✅ Success message appears in message container
- ✅ Status box updates to show "Connected" with record count
- ✅ Save Configuration button becomes **enabled**
- ✅ No console errors

**Screenshot:** `phase7/test-connection-success.png`  
**HAR Export:** `phase7/ajax-test-connection.har`

---

#### 3.3 Test Connection - Invalid Credentials

**Steps:**
1. Enter invalid credentials (wrong password)
2. Click **Test Connection**

**Expected Results:**
- ✅ Error message appears: "Connection failed: Access denied..."
- ✅ Status box shows "Disconnected"
- ✅ Save Configuration button remains **disabled**
- ✅ Error details include MySQL error code (if available)

**Screenshot:** `phase7/test-connection-error.png`

---

#### 3.4 Save Configuration

**Steps:**
1. Enter valid credentials
2. Test connection successfully
3. Click **Save Configuration**
4. Observe page reload or message

**Expected Results:**
- ✅ Success message: "Configuration saved successfully"
- ✅ Password field clears (security feature)
- ✅ Status box updates with external database info
- ✅ Disable External Database button appears
- ✅ Database indicator banner changes to "External Database" badge
- ✅ Credentials saved in `wp_options` (encrypted)

**Screenshot:** `phase7/save-config-success.png`

---

#### 3.5 Save Configuration - Test First Enforcement

**Steps:**
1. Enter valid credentials
2. **Do NOT** click Test Connection
3. Click Save Configuration directly

**Expected Results:**
- ✅ Warning message appears: "Please test the connection before saving"
- ✅ Configuration is **not** saved
- ✅ Save button remains disabled (if logic enforces this)

**Screenshot:** `phase7/save-without-test.png`

---

#### 3.6 Input Change Resets Test State

**Steps:**
1. Test connection successfully (Save button enabled)
2. Change any input field (e.g., modify database name)
3. Observe Save button state

**Expected Results:**
- ✅ Save button becomes **disabled** again
- ✅ `connectionTested` flag reset to false
- ✅ User must retest before saving

**Screenshot:** `phase7/input-change-resets.png`

---

#### 3.7 Disable External Database

**Steps:**
1. With external database configured, locate "Disable External Database" button
2. Click the button
3. Confirm in dialog prompt

**Expected Results:**
- ✅ Confirmation dialog: "Are you sure you want to switch back to WordPress database?"
- ✅ AJAX request to `eipsi_disable_external_db`
- ✅ Success message: "External database disabled"
- ✅ Status box shows "Disconnected"
- ✅ Database indicator banner changes back to "WordPress Database"
- ✅ Disable button disappears
- ✅ Save button disabled again

**Screenshot:** `phase7/disable-external-db.png`

---

#### 3.8 Status Box - Record Count

**Steps:**
1. With external database connected, note record count in status box
2. Submit a new form response
3. Reload configuration page

**Expected Results:**
- ✅ Record count increments by 1
- ✅ "Last Updated" timestamp updates (if available)

**Screenshot:** `phase7/record-count-update.png`

---

#### 3.9 Fallback Mode Indicator (Error Scenario)

**Steps:**
1. Configure external database
2. Simulate connection failure:
   - Stop MySQL service temporarily, OR
   - Change external DB password in MySQL
3. Submit a form response (should fallback to WordPress DB)
4. Reload configuration page

**Expected Results:**
- ✅ Yellow warning box appears: "Fallback Mode Active"
- ✅ Error message displayed (e.g., "Access denied for user...")
- ✅ Error code shown (e.g., `1045`)
- ✅ Timestamp of last error displayed
- ✅ Form submission still succeeds (saved to WordPress DB)

**Screenshot:** `phase7/fallback-mode.png`

---

#### 3.10 Responsive Behavior

**Steps:**
1. Resize browser window to tablet width (768px)
2. Resize to mobile width (375px)
3. Check form layout and button placement

**Expected Results:**
- ✅ Form fields stack vertically on small screens
- ✅ Buttons remain accessible (not cut off)
- ✅ Status box remains readable
- ✅ Text wraps properly

**Screenshot:** `phase7/config-responsive.png`

---

### 4. Export Functionality (15 minutes)

#### 4.1 CSV Export - All Forms

**Steps:**
1. Go to **EIPSI Forms → Form Responses**
2. Ensure "All Forms" is selected in filter
3. Click **📥 Download CSV**
4. Open downloaded CSV file

**Expected Results:**
- ✅ File downloads with name: `form-responses-YYYY-MM-DD-HH-MM-SS.csv`
- ✅ File opens in Excel/Numbers/Google Sheets without encoding errors
- ✅ Headers: `Form ID, Participant ID, Form Name, Date, Time, Duration(s), Start Time (UTC), End Time (UTC), IP Address, Device, Browser, OS, [Dynamic Question Columns]`
- ✅ All responses included (multiple forms)
- ✅ Timestamps formatted as ISO 8601 (e.g., `2025-01-15T14:23:45.123Z`)
- ✅ Special characters display correctly (UTF-8)

**Screenshot:** `phase7/csv-all-forms.png` (show first 10 rows in spreadsheet)

---

#### 4.2 CSV Export - Filtered by Form

**Steps:**
1. Filter by a specific form (e.g., `intake-form`)
2. Click **📥 Download CSV**
3. Open downloaded CSV file

**Expected Results:**
- ✅ File name includes form slug: `form-responses-intake-form-YYYY-MM-DD-HH-MM-SS.csv`
- ✅ Only responses from selected form included
- ✅ Question columns match selected form's fields

**Screenshot:** `phase7/csv-filtered.png`

---

#### 4.3 Excel Export - All Forms

**Steps:**
1. With "All Forms" selected, click **📊 Download Excel**
2. Open downloaded `.xlsx` file

**Expected Results:**
- ✅ File downloads with name: `form-responses-YYYY-MM-DD-HH-MM-SS.xlsx`
- ✅ Opens in Excel/Numbers without errors
- ✅ Same columns as CSV export
- ✅ Data formatted cleanly (no JSON artifacts in cells)
- ✅ Numeric values (duration, timestamps) display correctly

**Screenshot:** `phase7/excel-all-forms.png`

---

#### 4.4 Excel Export - Filtered by Form

**Steps:**
1. Filter by a specific form
2. Click **📊 Download Excel**
3. Open downloaded `.xlsx` file

**Expected Results:**
- ✅ File name includes form slug
- ✅ Only responses from selected form included
- ✅ Dynamic question columns match form structure

**Screenshot:** `phase7/excel-filtered.png`

---

#### 4.5 Export - Stable ID Generation

**Steps:**
1. Export responses (CSV or Excel)
2. Locate `Form ID` and `Participant ID` columns
3. Verify patterns:
   - Form ID: `ABC-123456` (initials + 6-char hash)
   - Participant ID: `FP-12345678` (fingerprint) or `FP-SESS-123456` (session fallback)
4. Export same data again after 1 minute

**Expected Results:**
- ✅ Form IDs are consistent across exports
- ✅ Participant IDs are consistent for same email/name
- ✅ IDs match database columns (`form_id`, `participant_id`)
- ✅ No IDs show as "N/A" (unless legacy data)

**Screenshot:** `phase7/stable-ids.png`

---

#### 4.6 Export - Dynamic Question Columns

**Steps:**
1. Create two forms with different fields:
   - Form A: Name, Email, Age, Comments
   - Form B: Name, Company, Phone, Preferences
2. Submit responses to both
3. Export with "All Forms" selected

**Expected Results:**
- ✅ Export contains union of all questions: `Name, Email, Age, Comments, Company, Phone, Preferences`
- ✅ Blank cells for questions not in a form's response
- ✅ No duplicate column headers

**Screenshot:** `phase7/dynamic-columns.png`

---

#### 4.7 Export - Internal Fields Excluded

**Steps:**
1. Inspect exported CSV/Excel
2. Check for presence of internal fields:
   - `action`, `nonce`, `form_action`, `current_page`, `eipsi_nonce`

**Expected Results:**
- ✅ Internal fields are **not** present in exports
- ✅ Only user-entered question/answers and metadata columns appear

**Screenshot:** `phase7/no-internal-fields.png`

---

#### 4.8 Export - Empty Data

**Steps:**
1. Delete all responses from a form
2. Try to export that form's data

**Expected Results:**
- ✅ Error page appears: "No data to export."
- ✅ No file downloads
- ✅ User remains on results page (or sees wp_die message)

**Screenshot:** `phase7/export-no-data.png`

---

#### 4.9 Export - Permission Check

**Steps:**
1. Log in as a user **without** `manage_options` capability (e.g., Editor role)
2. Try to access export URL directly: `?page=vas-dinamico-results&action=export_csv`

**Expected Results:**
- ✅ Access denied message: "You do not have sufficient permissions to perform this action."
- ✅ No file downloads

**Screenshot:** `phase7/export-permission-denied.png`

---

### 5. Admin Assets (10 minutes)

#### 5.1 Admin CSS Loading

**Steps:**
1. Open **EIPSI Forms → Form Responses** page
2. Inspect page source (View → Developer → View Source)
3. Search for `admin-style.css`

**Expected Results:**
- ✅ `admin-style.css` is enqueued in `<head>`
- ✅ Styles apply to results table (e.g., `.wp-list-table` overrides)
- ✅ No CSS loading errors in console

**Screenshot:** `phase7/admin-css-loaded.png`

---

#### 5.2 Configuration Panel CSS

**Steps:**
1. Open **EIPSI Forms → Configuration** page
2. Inspect page source
3. Search for `configuration-panel.css`

**Expected Results:**
- ✅ `configuration-panel.css` is enqueued
- ✅ Custom styles apply (status indicators, form layout)
- ✅ No styling regressions (buttons, inputs render correctly)

**Screenshot:** `phase7/config-css-loaded.png`

---

#### 5.3 Configuration Panel JavaScript

**Steps:**
1. On Configuration page, open browser console
2. Check for `EIPSIConfig` object: `console.log(EIPSIConfig)`
3. Check for localized strings: `console.log(eipsiConfigL10n)`

**Expected Results:**
- ✅ `EIPSIConfig` object exists with methods (`testConnection`, `saveConfiguration`, `disableExternalDB`)
- ✅ `eipsiConfigL10n` contains translated strings
- ✅ No JavaScript errors on page load

**Screenshot:** `phase7/config-js-loaded.png`

---

#### 5.4 AJAX URL Availability

**Steps:**
1. In browser console, check: `console.log(ajaxurl)`

**Expected Results:**
- ✅ `ajaxurl` is defined (WordPress global)
- ✅ Points to `wp-admin/admin-ajax.php`

---

#### 5.5 Responsive CSS - Mobile View

**Steps:**
1. Open Configuration page
2. Open DevTools → Toggle device toolbar (Ctrl+Shift+M)
3. Select iPhone 12 Pro (390x844)

**Expected Results:**
- ✅ Form fields stack vertically
- ✅ Buttons remain accessible (not truncated)
- ✅ Text remains readable (no tiny fonts)
- ✅ Status box adapts to width

**Screenshot:** `phase7/config-mobile.png`

---

### 6. AJAX Handlers (15 minutes)

#### 6.1 Form Submission AJAX

**Steps:**
1. On frontend, fill out a form
2. Open DevTools → Network tab
3. Submit the form
4. Locate AJAX request to `vas_dinamico_submit_form`

**Expected Results:**
- ✅ POST request to `admin-ajax.php` with action `vas_dinamico_submit_form`
- ✅ Response status: 200
- ✅ Response body: `{"success":true,"data":{...}}`
- ✅ No console errors

**HAR Export:** `phase7/ajax-form-submit.har`

---

#### 6.2 Event Tracking AJAX

**Steps:**
1. On frontend, navigate through a multi-page form
2. In Network tab, filter by `eipsi_track_event`
3. Observe tracking events: `view`, `start`, `page_change`, `submit`

**Expected Results:**
- ✅ Each event sends POST request to `eipsi_track_event`
- ✅ Responses: `{"success":true,"data":{...}}`
- ✅ Session ID consistent across events
- ✅ No failed requests (or graceful silent failures)

**HAR Export:** `phase7/ajax-tracking.har`

---

#### 6.3 Get Response Details AJAX

**Steps:**
1. On Results page, click **👁️** button
2. In Network tab, locate `eipsi_get_response_details` request
3. Inspect request payload and response

**Expected Results:**
- ✅ Request includes: `action`, `id`, `nonce`
- ✅ Response structure:
   ```json
   {
     "success": true,
     "data": "<html of modal content>"
   }
   ```
- ✅ Response time < 500ms

**HAR Export:** `phase7/ajax-get-response-details.har`

---

#### 6.4 Test DB Connection AJAX

**Steps:**
1. On Configuration page, enter credentials
2. Click Test Connection
3. In Network tab, locate `eipsi_test_db_connection` request

**Expected Results:**
- ✅ Request includes: `action`, `nonce`, `host`, `user`, `password`, `db_name`
- ✅ Response on success:
   ```json
   {
     "success": true,
     "data": {
       "message": "Connection successful!",
       "db_name": "research_db",
       "record_count": 42
     }
   }
   ```
- ✅ Response on failure:
   ```json
   {
     "success": false,
     "data": {
       "message": "Connection failed: Access denied..."
     }
   }
   ```

**HAR Export:** `phase7/ajax-test-connection.har`

---

#### 6.5 Save DB Config AJAX

**Steps:**
1. After successful test, click Save Configuration
2. Locate `eipsi_save_db_config` in Network tab

**Expected Results:**
- ✅ Request includes credentials
- ✅ Response:
   ```json
   {
     "success": true,
     "data": {
       "message": "Configuration saved successfully",
       "status": { /* updated status */ }
     }
   }
   ```
- ✅ No password visible in response

**HAR Export:** `phase7/ajax-save-config.har`

---

#### 6.6 Disable External DB AJAX

**Steps:**
1. With external DB configured, click Disable button
2. Locate `eipsi_disable_external_db` in Network tab

**Expected Results:**
- ✅ Request includes: `action`, `nonce`
- ✅ Response:
   ```json
   {
     "success": true,
     "data": {
       "message": "External database disabled successfully"
     }
   }
   ```

**HAR Export:** `phase7/ajax-disable-db.har`

---

#### 6.7 Nonce Verification

**Steps:**
1. Intercept any AJAX request (e.g., Test Connection)
2. Modify or remove the `nonce` parameter
3. Resend the request

**Expected Results:**
- ✅ Response: 403 or 400 status (or WordPress nonce failure message)
- ✅ Action is **not** performed
- ✅ Error message: "Security check failed" or similar

**Screenshot:** `phase7/nonce-failure.png`

---

#### 6.8 AJAX Error Handling

**Steps:**
1. Simulate network failure:
   - Open DevTools → Network → Throttling → Offline
2. Click Test Connection or Save Configuration

**Expected Results:**
- ✅ Error message appears: "Connection error. Please try again."
- ✅ Button returns to enabled state after error
- ✅ No JavaScript exceptions thrown

**Screenshot:** `phase7/ajax-error-handling.png`

---

### 7. Security & Edge Cases (10 minutes)

#### 7.1 ABSPATH Check

**Steps:**
1. Try to access PHP files directly in browser:
   - `https://yoursite.com/wp-content/plugins/eipsi-forms/admin/ajax-handlers.php`
   - `https://yoursite.com/wp-content/plugins/eipsi-forms/admin/configuration.php`

**Expected Results:**
- ✅ Blank page (no output) or error message (if ABSPATH undefined)
- ✅ No PHP warnings or sensitive data exposed

**Screenshot:** `phase7/abspath-check.png`

---

#### 7.2 SQL Injection Prevention

**Steps:**
1. In results page filter, try to inject SQL:
   - Select form, modify URL to: `?form_filter=' OR '1'='1`
2. Observe results

**Expected Results:**
- ✅ No extra responses shown (query properly escaped)
- ✅ No SQL errors in database logs
- ✅ `$wpdb->prepare()` prevents injection

---

#### 7.3 XSS Prevention

**Steps:**
1. Submit a form with XSS payload in a field:
   - Name: `<script>alert('XSS')</script>`
2. View response in Results page modal

**Expected Results:**
- ✅ Script does **not** execute
- ✅ Output is HTML-escaped: `&lt;script&gt;alert('XSS')&lt;/script&gt;`
- ✅ No alert popup appears

**Screenshot:** `phase7/xss-prevention.png`

---

#### 7.4 Permission Checks

**Steps:**
1. Log in as Editor (no `manage_options`)
2. Try to access:
   - Results page: `/wp-admin/admin.php?page=vas-dinamico-results`
   - Configuration page: `/wp-admin/admin.php?page=vas-dinamico-configuration`

**Expected Results:**
- ✅ Access denied message or redirect
- ✅ No data visible

---

#### 7.5 Large Dataset Handling

**Steps:**
1. Create 1,000+ responses (use script or bulk insert)
2. Load Results page
3. Trigger CSV export

**Expected Results:**
- ✅ Results page loads within 5 seconds (may need pagination if not implemented)
- ✅ Export completes without timeout (PHP `max_execution_time` may need adjustment)
- ✅ File size reasonable (<10MB for 1,000 responses)

---

## Data Collection

### Screenshots

Save all screenshots to: `/docs/qa/artifacts/phase7/`

Naming convention: `{test-section}-{description}.png`

Example:
- `block-insertion.png`
- `preset-application.png`
- `results-filtered.png`
- `ajax-test-connection.png`

### HAR Files

Export network logs for AJAX interactions:

1. In DevTools → Network tab
2. Right-click on any request → "Save all as HAR with content"
3. Save to `/docs/qa/artifacts/phase7/`

Files to export:
- `ajax-form-submit.har`
- `ajax-tracking.har`
- `ajax-get-response-details.har`
- `ajax-test-connection.har`
- `ajax-save-config.har`
- `ajax-disable-db.har`

### Console Logs

If errors occur, export console output:

1. Right-click in Console tab → Save as...
2. Save to `/docs/qa/artifacts/phase7/console-errors.txt`

---

## Issue Reporting

For each issue discovered, document:

1. **Test Section**: (e.g., "Results Page - 2.3")
2. **Severity**: Critical / High / Medium / Low
3. **Description**: Clear explanation of the issue
4. **Steps to Reproduce**: Numbered list
5. **Expected Behavior**: What should happen
6. **Actual Behavior**: What actually happens
7. **Browser/Environment**: Chrome 120, WordPress 6.4, PHP 8.1
8. **Screenshot/Log**: Reference file in artifacts folder

Example:
```markdown
### Issue #7-001: Modal does not close on Escape key

**Test Section:** Results Page - 2.5  
**Severity:** Low  
**Description:** Pressing Escape key does not close the View Response modal.  
**Steps to Reproduce:**
1. Open Results page
2. Click eye icon to open modal
3. Press Escape key

**Expected Behavior:** Modal should close  
**Actual Behavior:** Modal remains open  
**Browser:** Chrome 120.0.6099.109  
**Screenshot:** `phase7/modal-escape-bug.png`  
```

---

## Acceptance Criteria

✅ All test sections completed (7 sections)  
✅ At least 50 individual test cases executed  
✅ All critical paths pass (block editor, results, export, configuration)  
✅ No security vulnerabilities found  
✅ No console errors on happy paths  
✅ All screenshots captured  
✅ HAR files exported for AJAX interactions  
✅ Issues documented with severity ratings  
✅ Summary document created (`QA_PHASE7_RESULTS.md`)

---

## Next Steps

After completing manual testing:

1. Run automated validation: `node admin-workflows-validation.js`
2. Compile results into `QA_PHASE7_RESULTS.md`
3. Create GitHub issues for any bugs found (label: `qa-phase7`)
4. Update memory with learnings and common patterns
5. Prepare for deployment to staging environment

---

**End of Manual Testing Guide**
