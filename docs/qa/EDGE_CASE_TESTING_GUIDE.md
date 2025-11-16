# EIPSI Forms - Edge Case & Robustness Testing Guide (Phase 8)

**Version:** 1.0.0  
**Date:** January 2025  
**Status:** Active Testing  
**Estimated Duration:** 8-10 hours

---

## Table of Contents

1. [Overview](#overview)
2. [Environment Setup](#environment-setup)
3. [Testing Categories](#testing-categories)
   - [3.1 Validation & Error Handling](#31-validation--error-handling)
   - [3.2 Database Failures](#32-database-failures)
   - [3.3 Network Interruption](#33-network-interruption)
   - [3.4 Long Form Behavior](#34-long-form-behavior)
   - [3.5 Cross-Browser Compatibility](#35-cross-browser-compatibility)
   - [3.6 Security Hygiene](#36-security-hygiene)
4. [Data Collection & Artifacts](#data-collection--artifacts)
5. [Acceptance Criteria](#acceptance-criteria)

---

## 1. Overview

This guide provides comprehensive manual testing procedures to validate EIPSI Forms plugin robustness under adverse conditions. The goal is to ensure the system handles edge cases gracefully, maintains data integrity during failures, and provides excellent user experience across all browsers and devices.

### Objectives

- ✅ Validate error handling for all input types
- ✅ Confirm database fallback mechanisms work correctly
- ✅ Verify graceful handling of network interruptions
- ✅ Test performance with long multi-page forms
- ✅ Ensure cross-browser compatibility (Chrome, Firefox, Safari, Edge, iOS, Android)
- ✅ Validate security measures (nonces, sanitization, capability checks)

---

## 2. Environment Setup

### 2.1 Required Tools

- **Desktop Browsers:**
  - Chrome (latest version)
  - Firefox (latest version)
  - Safari (latest version, macOS)
  - Edge (latest version)

- **Mobile Devices/Emulators:**
  - iOS Safari (iPhone/iPad - physical or simulator)
  - Android Chrome (physical device or emulator)
  - Optional: BrowserStack or Sauce Labs for broader coverage

- **Developer Tools:**
  - Browser DevTools (Console, Network tab, Elements tab)
  - Network throttling capabilities
  - Offline mode toggle
  - HAR file export capability

### 2.2 Test Forms Setup

Create the following test forms in WordPress admin:

1. **Simple Form** (1 page, 5 fields) - for quick validation tests
2. **Medium Form** (3 pages, 15 fields) - for navigation tests
3. **Long Form** (10+ pages, 30+ fields) - for performance tests
4. **Validation Test Form** - specific field types:
   - Required text field
   - Required email field
   - Required number input
   - Required radio group
   - Required checkbox group
   - Required select dropdown
   - Required VAS slider
   - Optional fields mixed in

### 2.3 Database Configuration

- **Primary Test:** WordPress database mode (default)
- **Secondary Test:** External database mode (configure in admin)
- **Tertiary Test:** External DB with intentional failures

---

## 3. Testing Categories

### 3.1 Validation & Error Handling

**Estimated Duration:** 2 hours

#### Test 3.1.1: Required Field Validation

**Setup:**
- Use "Validation Test Form"
- All fields marked as required

**Steps:**
1. Load form
2. Click "Next" or "Submit" without filling any fields
3. Observe error messages

**Expected Results:**
- ✅ Inline error messages appear below each field
- ✅ Error messages use CSS class `.form-error`
- ✅ Error text: "Este campo es obligatorio."
- ✅ Form group has `.has-error` class
- ✅ Input has `aria-invalid="true"`
- ✅ Focus moves to first invalid field
- ✅ Global error message appears: "Por favor, completa todos los campos requeridos."

**Pass Criteria:**
- All checkboxes above must be satisfied

---

#### Test 3.1.2: Email Format Validation

**Steps:**
1. Enter invalid email formats in email field:
   - `notanemail`
   - `missing@domain`
   - `@nodomain.com`
   - `spaces in@email.com`
2. Try to advance/submit
3. Enter valid email: `user@example.com`
4. Verify error clears

**Expected Results:**
- ✅ Invalid formats trigger error: "Por favor, introduzca una dirección de correo electrónico válida."
- ✅ Error appears immediately on blur (if validateOnBlur enabled)
- ✅ Valid email clears error
- ✅ `aria-invalid` removed when valid

**Pass Criteria:**
- All invalid formats rejected, valid format accepted

---

#### Test 3.1.3: VAS Slider Touch Validation

**Steps:**
1. Load form with required VAS slider
2. Do NOT touch the slider
3. Try to advance/submit
4. Observe error
5. Touch slider (click or drag)
6. Try to advance/submit again

**Expected Results:**
- ✅ Error appears: "Por favor, interactúe con la escala para continuar."
- ✅ After touching, error clears
- ✅ `data-touched="false"` changes to `"true"` on interaction
- ✅ Keyboard arrow keys also count as "touched"

**Pass Criteria:**
- Slider must be interacted with before submission allowed

---

#### Test 3.1.4: Radio/Checkbox Group Validation

**Steps:**
1. Load form with required radio group
2. Do not select any option
3. Try to advance/submit
4. Observe error
5. Select an option
6. Verify error clears
7. Repeat for checkbox group

**Expected Results:**
- ✅ Error appears on radio group container
- ✅ All radio inputs get `.error` class
- ✅ All radio inputs get `aria-invalid="true"`
- ✅ Selecting option clears all errors
- ✅ Same behavior for checkbox groups

**Pass Criteria:**
- Group validation works correctly for both radio and checkbox

---

#### Test 3.1.5: Select Dropdown Validation

**Steps:**
1. Load form with required select dropdown
2. Leave on default/empty option
3. Try to advance/submit
4. Observe error
5. Select valid option
6. Verify error clears

**Expected Results:**
- ✅ Error appears for empty selection
- ✅ `aria-invalid="true"` set on select element
- ✅ Selecting valid option clears error

---

#### Test 3.1.6: Server-Side Sanitization

**Setup:**
- Use browser DevTools Console

**Steps:**
1. Open DevTools Console
2. Fill out form normally
3. Before submission, use Console to inject malicious values:
   ```javascript
   document.querySelector('input[name="name"]').value = '<script>alert("XSS")</script>';
   document.querySelector('input[name="email"]').value = '"><script>alert("XSS")</script>';
   document.querySelector('textarea').value = '<?php echo "Injection"; ?>';
   ```
4. Submit form
5. Check admin results page
6. View response details in modal

**Expected Results:**
- ✅ Script tags are stripped/escaped in database
- ✅ No JavaScript execution in admin view
- ✅ PHP code rendered as text, not executed
- ✅ `esc_html()` used in admin modal output

**Pass Criteria:**
- No XSS vulnerabilities, all malicious code neutralized

---

#### Test 3.1.7: Oversized Text Handling

**Steps:**
1. Fill text field with extremely long string (10,000+ characters)
2. Fill textarea with massive content
3. Submit form
4. Check admin view

**Expected Results:**
- ✅ Form submits without error
- ✅ Database stores full content (within MySQL limits)
- ✅ Admin view handles long text gracefully (no UI breaks)
- ✅ Export functions handle long text correctly

---

#### Test 3.1.8: ARIA Live Announcements

**Setup:**
- Enable screen reader (VoiceOver on Mac, NVDA on Windows)

**Steps:**
1. Fill form with errors
2. Submit form
3. Listen for announcements
4. Fill form correctly
5. Submit successfully
6. Listen for success announcements

**Expected Results:**
- ✅ Error messages announced with `role="alert"`
- ✅ Success messages announced with `role="status"` and `aria-live="polite"`
- ✅ Screen reader reads error text
- ✅ Focus management works with screen reader

---

#### Test 3.1.9: Focus Management on Error

**Steps:**
1. Create form with errors on page 2
2. Fill page 1 correctly, advance
3. Leave page 2 fields empty, try to advance
4. Observe focus behavior

**Expected Results:**
- ✅ Focus moves to first invalid field on page
- ✅ `focusFirstInvalidField()` executes
- ✅ Auto-scroll to error field (if `enableAutoScroll: true`)
- ✅ Focus is visible (outline/ring)

---

#### Test 3.1.10: Error Clearing on Correction

**Steps:**
1. Trigger validation error on text field
2. Start typing correction
3. Observe real-time error clearing (if `validateOnBlur` enabled)
4. Tab away from field
5. Verify error fully cleared

**Expected Results:**
- ✅ Error message disappears when field becomes valid
- ✅ `.has-error` class removed
- ✅ `aria-invalid` removed
- ✅ `.error` class removed from input

---

### 3.2 Database Failures

**Estimated Duration:** 2 hours

#### Test 3.2.1: External Database Connection Failure (During Submission)

**Setup:**
- Configure external database in admin
- Test connection (should succeed)
- Submit a test form (should succeed with external DB)

**Steps:**
1. **Simulate failure:** Stop external database service OR revoke user permissions
2. Fill out a form completely
3. Submit form
4. Observe response
5. Check WordPress database for fallback record
6. Check admin error logs (if WP_DEBUG enabled)

**Expected Results:**
- ✅ Form submission does NOT fail completely
- ✅ Success message displayed to user with warning
- ✅ Message includes: "Form was saved to local database (external database temporarily unavailable)."
- ✅ `fallback_used: true` in AJAX response
- ✅ Data saved to WordPress `wp_vas_form_results` table
- ✅ Error logged: "EIPSI Forms: External DB insert failed, falling back to WordPress DB"
- ✅ Admin can view response in Results page

**Pass Criteria:**
- Zero data loss, graceful fallback, user informed

**Capture:**
- Screenshot of success message with warning
- Console log showing AJAX response
- Screenshot of admin Results page with fallback record

---

#### Test 3.2.2: External Database Configuration Test

**Steps:**
1. Go to **EIPSI Forms → Configuration**
2. Enter invalid database credentials
3. Click "Test Connection"
4. Observe error message
5. Enter valid credentials
6. Click "Test Connection"
7. Observe success message
8. Click "Save Configuration"

**Expected Results:**
- ✅ Invalid credentials: Error message displayed with specific error
- ✅ Valid credentials: "Connection successful!" with record count
- ✅ Save button disabled until test succeeds
- ✅ `connectionTested` flag prevents save without test
- ✅ Success message auto-hides after 5 seconds
- ✅ Password field cleared after save

**Pass Criteria:**
- Test-before-save workflow prevents invalid configuration

---

#### Test 3.2.3: Export During Database Failure

**Setup:**
- Configure external database (working)
- Submit several test responses
- Stop external database

**Steps:**
1. Go to **EIPSI Forms → Form Responses**
2. Click "CSV Export" or "Excel Export"
3. Observe behavior

**Expected Results:**
- ✅ Export still works (falls back to WordPress DB)
- ✅ No fatal errors or white screen
- ✅ If no data available, friendly error message
- ✅ HAR file shows no 500 errors

**Pass Criteria:**
- Export degrades gracefully, no critical failures

---

#### Test 3.2.4: Database Status Indicator

**Steps:**
1. Go to **EIPSI Forms → Configuration**
2. With valid external DB: Observe status box
3. Disable external DB
4. Go to **Form Responses** page
5. Observe any status indicators

**Expected Results:**
- ✅ Configuration page shows: "Status: Connected" (or similar)
- ✅ Record count displayed
- ✅ Results page indicates data source (if implemented)

---

#### Test 3.2.5: Database Reconnection

**Steps:**
1. Simulate DB failure (stop service)
2. Submit form (should fallback to WordPress DB)
3. Restart external database service
4. Submit another form
5. Check destination

**Expected Results:**
- ✅ After DB recovery, submissions go to external DB again
- ✅ No manual intervention required
- ✅ System automatically detects reconnection

---

### 3.3 Network Interruption

**Estimated Duration:** 1.5 hours

#### Test 3.3.1: Offline Mode During Submission

**Setup:**
- Use Chrome DevTools → Network tab → Throttling → Offline

**Steps:**
1. Fill out form completely
2. **Before submitting:** Enable offline mode in DevTools
3. Click "Submit"
4. Observe behavior
5. Wait 5 seconds
6. Re-enable network
7. Click "Submit" again

**Expected Results:**
- ✅ First attempt: Error message appears
- ✅ Message: "Ocurrió un error. Por favor, inténtelo de nuevo."
- ✅ Submit button re-enabled after error
- ✅ Form data retained (not cleared)
- ✅ Loading state removed
- ✅ Second attempt (online): Submission succeeds

**Pass Criteria:**
- User informed of error, can retry, no data loss

**Capture:**
- Screenshot of error message during offline
- Network tab showing failed request
- Console log showing fetch error

---

#### Test 3.3.2: Slow Network (Throttling)

**Setup:**
- DevTools → Network → Slow 3G

**Steps:**
1. Fill out form
2. Submit form
3. Observe loading states

**Expected Results:**
- ✅ Submit button shows "Enviando..."
- ✅ Submit button disabled during submission
- ✅ `.form-loading` class added to container
- ✅ Navigation buttons disabled during submission
- ✅ Eventually submission completes successfully
- ✅ All states reset properly

**Pass Criteria:**
- Clear loading feedback, no UI glitches

---

#### Test 3.3.3: Double Submit Prevention

**Steps:**
1. Fill out form
2. Click "Submit" button
3. **Immediately** click "Submit" again (rapid double-click)
4. Observe behavior
5. Check admin Results page for duplicate entries

**Expected Results:**
- ✅ Only ONE submission occurs
- ✅ `form.dataset.submitting = 'true'` prevents second click
- ✅ Submit button disabled immediately on first click
- ✅ No duplicate records in database

**Pass Criteria:**
- Zero duplicate submissions

---

#### Test 3.3.4: Rapid Page Navigation Clicks

**Steps:**
1. Load multi-page form
2. Fill first page
3. Rapidly click "Next" button multiple times
4. Observe page transitions

**Expected Results:**
- ✅ Only one page transition occurs
- ✅ Button disabled checks prevent rapid firing
- ✅ No JavaScript errors in console

---

#### Test 3.3.5: Network Timeout Simulation

**Setup:**
- Use DevTools → Network → Custom → Set very high latency (e.g., 10000ms)

**Steps:**
1. Fill form
2. Submit
3. Wait for timeout
4. Observe error handling

**Expected Results:**
- ✅ Fetch eventually fails or times out
- ✅ `.catch()` block executes
- ✅ Error message displayed
- ✅ Form state resets properly

---

### 3.4 Long Form Behavior

**Estimated Duration:** 2 hours

#### Test 3.4.1: Create 10+ Page Form

**Setup:**
- Create new form with at least 10 pages
- Add 3-5 fields per page
- Mix field types (text, radio, VAS, select)
- Mark some fields as required

**Steps:**
1. Load form in browser
2. Fill out page 1
3. Click "Next"
4. Repeat for all 10+ pages
5. Monitor performance throughout

**Expected Results:**
- ✅ Page transitions are smooth (< 100ms)
- ✅ Progress indicator updates correctly (1/10, 2/10, etc.)
- ✅ Auto-scroll to top of form (if enabled)
- ✅ No memory leaks in DevTools Memory tab
- ✅ No lag or freezing

**Pass Criteria:**
- Excellent performance across all pages

---

#### Test 3.4.2: Scroll Performance & Sticky Elements

**Steps:**
1. On long form, scroll up and down on each page
2. Observe navigation buttons
3. Observe progress indicator

**Expected Results:**
- ✅ Sticky navigation (if implemented) stays visible
- ✅ Smooth scrolling with no jank
- ✅ CSS `position: sticky` works correctly
- ✅ Auto-scroll to form top on page change works

---

#### Test 3.4.3: Progress Indicator Accuracy

**Steps:**
1. Navigate through 10-page form
2. Check progress indicator on each page
3. Note current page / total pages display

**Expected Results:**
- ✅ Progress shows correct values (1/10, 2/10, etc.)
- ✅ If conditional logic skips pages, estimated total adjusts (e.g., "8*")
- ✅ `.current-page` and `.total-pages` updated
- ✅ Visually clear progress indication

---

#### Test 3.4.4: Backwards Navigation (If Enabled)

**Setup:**
- Enable `allowBackwardsNav` in form block

**Steps:**
1. Navigate forward to page 5
2. Click "Previous" button
3. Verify it returns to page 4
4. Continue clicking "Previous"
5. Verify history-based navigation

**Expected Results:**
- ✅ "Previous" button appears when `allowBackwardsNav: true`
- ✅ History tracked correctly in `navigator.history`
- ✅ Can navigate back through visited pages
- ✅ "Previous" button hidden on first page

---

#### Test 3.4.5: Conditional Navigation with Long Forms

**Setup:**
- Add conditional logic to jump from page 2 to page 8
- Set trigger on a radio button

**Steps:**
1. Navigate to page 2
2. Select radio option that triggers jump
3. Click "Next"
4. Observe jump to page 8

**Expected Results:**
- ✅ Skipped pages (3-7) marked in `skippedPages`
- ✅ Progress indicator shows adjusted estimate
- ✅ Backwards navigation skips skipped pages
- ✅ Branch jump tracked in analytics (if enabled)

---

#### Test 3.4.6: Form Reset After Submission

**Steps:**
1. Complete entire 10-page form
2. Submit successfully
3. Wait for success message (3 seconds)
4. Observe form state

**Expected Results:**
- ✅ Form resets to page 1
- ✅ All fields cleared
- ✅ Navigator history reset
- ✅ VAS sliders reset to `data-touched="false"`
- ✅ Progress shows 1/10
- ✅ User can start new submission

---

#### Test 3.4.7: Memory & Performance Monitoring

**Setup:**
- Chrome DevTools → Performance tab
- Chrome DevTools → Memory tab

**Steps:**
1. Open Performance tab
2. Start recording
3. Navigate through all 10 pages
4. Stop recording
5. Analyze flame chart
6. Take heap snapshot before and after

**Expected Results:**
- ✅ No long tasks (> 50ms)
- ✅ Smooth 60fps animations
- ✅ No memory leaks (heap size stable)
- ✅ RequestAnimationFrame used efficiently

**Capture:**
- Performance trace file
- Heap snapshot comparison

---

#### Test 3.4.8: Field Validation Across Pages

**Steps:**
1. Fill pages 1-5 correctly
2. On page 6, leave required field empty
3. Try to advance
4. Observe error
5. Correct error
6. Continue to page 10
7. Submit form

**Expected Results:**
- ✅ Validation only checks current page on "Next"
- ✅ Final validation checks all visited pages on "Submit"
- ✅ Skipped pages not validated
- ✅ Error messages appear on correct page

---

### 3.5 Cross-Browser Compatibility

**Estimated Duration:** 3 hours

#### Test 3.5.1: Chrome (Desktop)

**Version:** Latest stable

**Test Steps:**
1. Load form
2. Fill all field types
3. Test validation
4. Submit form
5. Check styling consistency

**Expected Results:**
- ✅ All features work perfectly
- ✅ CSS renders correctly
- ✅ JavaScript executes without errors
- ✅ DevTools Console: 0 errors

**Capture:**
- Screenshot of form
- Console log (no errors)
- Network tab HAR file

---

#### Test 3.5.2: Firefox (Desktop)

**Version:** Latest stable

**Test Steps:**
1. Same as Chrome test
2. Pay attention to:
   - Input styling (especially range sliders)
   - Flexbox/Grid layout
   - Font rendering

**Expected Results:**
- ✅ All features work correctly
- ✅ VAS sliders styled consistently
- ✅ No Firefox-specific bugs
- ✅ Console: 0 errors

**Capture:**
- Screenshot comparison with Chrome
- Note any visual differences

---

#### Test 3.5.3: Safari (Desktop - macOS)

**Version:** Latest stable

**Test Steps:**
1. Same as Chrome test
2. Pay attention to:
   - Date inputs (Safari handles differently)
   - Range slider appearance
   - CSS animations
   - Flexbox behavior

**Expected Results:**
- ✅ All features work correctly
- ✅ `-webkit-` prefixes handled if needed
- ✅ No Safari-specific errors
- ✅ Smooth animations

**Capture:**
- Screenshot comparison
- Note webkit-specific behaviors

---

#### Test 3.5.4: Edge (Desktop)

**Version:** Latest Chromium-based

**Test Steps:**
1. Same as Chrome test
2. Should behave identically (Chromium-based)

**Expected Results:**
- ✅ Same as Chrome results
- ✅ No Edge-specific issues

---

#### Test 3.5.5: Mobile Safari (iOS)

**Device:** iPhone (physical or Simulator)  
**Version:** Latest iOS

**Test Steps:**
1. Load form on mobile device
2. Test touch interactions on VAS sliders
3. Test form field focus (keyboard appears)
4. Test page navigation with touch
5. Test validation and submission
6. Rotate device (portrait/landscape)

**Expected Results:**
- ✅ Touch events work correctly (`pointerdown`)
- ✅ VAS sliders draggable with finger
- ✅ Keyboard doesn't obscure input fields
- ✅ Viewport meta tag prevents zoom issues
- ✅ Navigation buttons large enough for touch (44x44px minimum)
- ✅ Responsive layout works in both orientations
- ✅ No horizontal scrolling

**Capture:**
- Screenshots on iPhone
- Video of interaction
- Safari Web Inspector console log

---

#### Test 3.5.6: Android Chrome (Mobile)

**Device:** Android phone (physical or emulator)  
**Version:** Latest Chrome for Android

**Test Steps:**
1. Same as iOS test
2. Pay attention to:
   - Material Design input styling
   - Android keyboard behavior
   - Back button handling

**Expected Results:**
- ✅ All mobile features work
- ✅ Touch interactions smooth
- ✅ Responsive design adapts correctly
- ✅ No Android-specific bugs

**Capture:**
- Screenshots on Android
- Chrome DevTools remote debugging console log

---

#### Test 3.5.7: Tablet (iPad)

**Steps:**
1. Load form on iPad (or iPad simulator)
2. Test in portrait and landscape
3. Verify responsive breakpoints

**Expected Results:**
- ✅ Layout adapts to tablet size
- ✅ Not just "zoomed mobile" or "zoomed desktop"
- ✅ Touch targets appropriately sized

---

#### Test 3.5.8: Compatibility Matrix

Create spreadsheet with results:

| Browser/Device | Version | Form Load | Validation | Submission | Styling | Notes |
|----------------|---------|-----------|------------|------------|---------|-------|
| Chrome Desktop | 120.x   | ✅        | ✅         | ✅         | ✅      | Perfect |
| Firefox Desktop| 121.x   | ✅        | ✅         | ✅         | ✅      | Perfect |
| Safari Desktop | 17.x    | ✅        | ✅         | ✅         | ✅      | Perfect |
| Edge Desktop   | 120.x   | ✅        | ✅         | ✅         | ✅      | Perfect |
| iOS Safari     | 17.x    | ✅        | ✅         | ✅         | ✅      | Touch OK |
| Android Chrome | 120.x   | ✅        | ✅         | ✅         | ✅      | Touch OK |

**Pass Criteria:**
- All ✅ in matrix, or minor cosmetic differences documented

---

### 3.6 Security Hygiene

**Estimated Duration:** 1.5 hours

#### Test 3.6.1: Nonce Expiration

**Steps:**
1. Load form
2. Wait 24+ hours (or modify nonce timeout in WordPress)
3. Fill form
4. Submit form
5. Observe response

**Expected Results:**
- ✅ AJAX request fails
- ✅ `check_ajax_referer()` returns error
- ✅ HTTP 403 status code
- ✅ User-friendly error message (not raw WordPress error)

**Pass Criteria:**
- Expired nonce rejected, user informed

---

#### Test 3.6.2: Unauthorized AJAX Calls

**Setup:**
- Use browser DevTools Console
- User logged out (or non-admin user)

**Steps:**
1. Open Console
2. Attempt to call admin-only AJAX action:
   ```javascript
   fetch('/wp-admin/admin-ajax.php', {
     method: 'POST',
     body: new URLSearchParams({
       action: 'eipsi_get_response_details',
       id: 1,
       nonce: 'invalid_nonce'
     })
   }).then(r => r.json()).then(console.log);
   ```
3. Observe response

**Expected Results:**
- ✅ Request returns error
- ✅ HTTP 403 Forbidden
- ✅ `current_user_can('manage_options')` check blocks access
- ✅ Response: `{"success":false,"data":"Unauthorized"}`

**Pass Criteria:**
- Unauthorized access blocked at capability check

---

#### Test 3.6.3: SQL Injection Attempt

**Steps:**
1. Fill form with SQL injection attempts in text fields:
   ```
   '; DROP TABLE wp_users; --
   1' OR '1'='1
   " OR 1=1 --
   ```
2. Submit form
3. Check database
4. Verify site still functions

**Expected Results:**
- ✅ Malicious SQL not executed
- ✅ Database intact
- ✅ Values stored as text (sanitized)
- ✅ `$wpdb->prepare()` prevents injection

**Pass Criteria:**
- Zero SQL injection vulnerabilities

---

#### Test 3.6.4: XSS Prevention

**Steps:**
1. Already covered in Test 3.1.6
2. Verify admin modal escapes output

**Expected Results:**
- ✅ `esc_html()` used in admin views
- ✅ No JavaScript execution in modal

---

#### Test 3.6.5: CSRF Protection

**Steps:**
1. Create malicious HTML file:
   ```html
   <form action="https://yoursite.com/wp-admin/admin-ajax.php" method="POST">
     <input type="hidden" name="action" value="vas_dinamico_submit_form">
     <input type="hidden" name="form_id" value="test">
     <input type="hidden" name="nonce" value="fake_nonce">
   </form>
   <script>document.forms[0].submit();</script>
   ```
2. Open file in browser (different domain)
3. Observe if form submits

**Expected Results:**
- ✅ Submission fails
- ✅ Nonce verification prevents CSRF
- ✅ Cross-origin request blocked (or nonce invalid)

**Pass Criteria:**
- CSRF attack prevented

---

#### Test 3.6.6: Direct File Access Prevention

**Steps:**
1. Attempt to access PHP files directly via URL:
   - `https://yoursite.com/wp-content/plugins/vas-dinamico-forms/admin/ajax-handlers.php`
   - `https://yoursite.com/wp-content/plugins/vas-dinamico-forms/admin/database.php`
2. Observe response

**Expected Results:**
- ✅ Blank page or error
- ✅ `if (!defined('ABSPATH')) exit;` check triggers
- ✅ No PHP code execution

**Pass Criteria:**
- Direct access blocked for all PHP files

---

#### Test 3.6.7: Password Field Security

**Steps:**
1. Go to Configuration page
2. Enter database credentials including password
3. Save configuration
4. Check page source / DevTools

**Expected Results:**
- ✅ Password not visible in page source
- ✅ Password field cleared after save (security.js line ~XX)
- ✅ Password not logged in browser console

---

#### Test 3.6.8: Event Type Whitelist

**Setup:**
- Use DevTools Console

**Steps:**
1. Attempt to send invalid event type via tracking AJAX:
   ```javascript
   fetch('/wp-admin/admin-ajax.php', {
     method: 'POST',
     body: new URLSearchParams({
       action: 'eipsi_track_event',
       event_type: 'malicious_event',
       session_id: 'test',
       nonce: eipsiFormsConfig.trackingNonce
     })
   }).then(r => r.json()).then(console.log);
   ```
2. Observe response

**Expected Results:**
- ✅ Request rejected
- ✅ HTTP 400 Bad Request
- ✅ `allowed_events` whitelist prevents arbitrary events
- ✅ Response: `{"success":false,"data":"Invalid event type"}`

**Pass Criteria:**
- Only whitelisted events accepted

---

## 4. Data Collection & Artifacts

All test evidence should be stored in `/docs/qa/artifacts/phase8/` with the following structure:

```
/docs/qa/artifacts/phase8/
├── validation/
│   ├── required-field-error.png
│   ├── email-validation-error.png
│   ├── vas-slider-touch-error.png
│   └── xss-attempt-sanitized.png
├── database/
│   ├── external-db-fallback-success.png
│   ├── console-log-fallback.json
│   ├── admin-results-fallback-record.png
│   └── db-connection-test-invalid.png
├── network/
│   ├── offline-error-message.png
│   ├── network-tab-offline.har
│   ├── double-submit-prevention.mp4
│   └── slow-3g-loading-state.png
├── long-forms/
│   ├── 10-page-form-progress.png
│   ├── performance-trace.json
│   ├── heap-snapshot-before.heapsnapshot
│   ├── heap-snapshot-after.heapsnapshot
│   └── conditional-jump-page-2-to-8.mp4
├── browsers/
│   ├── chrome-desktop-screenshot.png
│   ├── firefox-desktop-screenshot.png
│   ├── safari-desktop-screenshot.png
│   ├── edge-desktop-screenshot.png
│   ├── ios-safari-portrait.png
│   ├── ios-safari-landscape.png
│   ├── android-chrome-screenshot.png
│   ├── compatibility-matrix.xlsx
│   └── console-logs/
│       ├── chrome.log
│       ├── firefox.log
│       ├── safari.log
│       └── mobile.log
└── security/
    ├── nonce-expiration-403.png
    ├── unauthorized-ajax-403.png
    ├── sql-injection-sanitized.png
    ├── csrf-prevention-failed.png
    └── direct-access-blocked.png
```

### Capture Tools

- **Screenshots:** Built-in OS tools, browser DevTools
- **HAR Files:** DevTools Network tab → Export HAR
- **Performance Traces:** DevTools Performance tab → Save profile
- **Heap Snapshots:** DevTools Memory tab → Take snapshot
- **Videos:** OBS Studio, QuickTime (macOS), or browser extensions
- **Logs:** Copy from DevTools Console, save as `.log` or `.json`

---

## 5. Acceptance Criteria

### 5.1 Automated Tests

- ✅ `node edge-case-validation.js` passes 100% (80+ tests)
- ✅ All categories show green checkmarks
- ✅ `docs/qa/edge-case-validation.json` generated with pass results

### 5.2 Manual Tests

- ✅ All 50+ manual test scenarios completed
- ✅ Pass rate ≥ 95% (minor cosmetic issues acceptable)
- ✅ Critical failures: 0
- ✅ Data loss incidents: 0
- ✅ Security vulnerabilities: 0

### 5.3 Browser Compatibility

- ✅ Compatibility matrix 100% green for required browsers:
  - Chrome (desktop)
  - Firefox (desktop)
  - Safari (desktop)
  - Edge (desktop)
  - iOS Safari (mobile)
  - Android Chrome (mobile)
- ✅ Minor visual differences documented and acceptable

### 5.4 Documentation

- ✅ `docs/qa/QA_PHASE8_RESULTS.md` completed with:
  - Executive summary
  - All test results documented
  - Screenshots and artifacts linked
  - Findings and recommendations
  - Mitigations for any failures
- ✅ Compatibility matrix finalized
- ✅ All artifacts organized in `/docs/qa/artifacts/phase8/`

### 5.5 Performance Benchmarks

- ✅ 10-page form loads in < 1 second
- ✅ Page transitions < 100ms
- ✅ No memory leaks (stable heap size)
- ✅ No JavaScript errors in any browser
- ✅ Lighthouse Performance score ≥ 90

### 5.6 Security Checklist

- ✅ All nonces verified
- ✅ All inputs sanitized
- ✅ All outputs escaped
- ✅ SQL injection prevented
- ✅ XSS prevented
- ✅ CSRF prevented
- ✅ Direct file access blocked
- ✅ Capability checks enforced
- ✅ Unauthorized AJAX blocked

---

## 6. Recommended Mitigations (If Issues Found)

Document any issues discovered and proposed fixes:

### Example Issue Template

**Issue ID:** EDGE-001  
**Category:** Validation  
**Severity:** Medium  
**Description:** Email validation allows `user@domain` without TLD  
**Expected:** Should require `.com`, `.org`, etc.  
**Actual:** Accepts `user@domain`  
**Steps to Reproduce:**  
1. Enter `test@example` in email field  
2. Submit form  
3. No error shown  

**Proposed Fix:**  
Update regex in `eipsi-forms.js` line 1399:  
```javascript
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
```

**Priority:** P2 (fix in next release)  
**Assigned To:** Developer  
**Status:** Open  

---

## 7. Sign-Off

Upon completion of all tests:

**QA Tester:**  
Name: ___________________________  
Date: ___________________________  
Signature: _______________________  

**Tech Lead:**  
Name: ___________________________  
Date: ___________________________  
Signature: _______________________  

**Approval Status:** ☐ Approved ☐ Approved with Minor Issues ☐ Rejected (Requires Fixes)

---

**End of Manual Testing Guide**
