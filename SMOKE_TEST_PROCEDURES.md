# EIPSI Forms - Smoke Test Procedures

## Purpose
Verify that the packaged plugin installs and functions correctly on a clean WordPress site before distribution.

---

## Prerequisites

### Test Environment
- ✅ Clean WordPress installation (5.8 or higher)
- ✅ PHP 7.4 or higher
- ✅ Gutenberg editor enabled
- ✅ No conflicting plugins active
- ✅ Standard WordPress theme (e.g., Twenty Twenty-Four)

### Test Package
- ✅ Distribution zip file: `eipsi-forms-X.X.X.zip`
- ✅ Release metadata file: `release-metadata-X.X.X.json`
- ✅ Checksums verified

---

## Smoke Test Checklist

### 1. Installation Test (5 minutes)

#### 1.1 Upload Plugin
- [ ] Log in to WordPress admin
- [ ] Navigate to Plugins → Add New → Upload Plugin
- [ ] Select the distribution zip file
- [ ] Click "Install Now"
- [ ] **Expected:** Installation completes without errors

#### 1.2 Activate Plugin
- [ ] Click "Activate Plugin"
- [ ] **Expected:** Plugin activates successfully
- [ ] **Expected:** No PHP errors or warnings displayed
- [ ] **Expected:** Admin menu shows "VAS Forms" entry

#### 1.3 Database Verification
- [ ] Check that database tables were created:
  - `wp_vas_form_results`
  - `wp_vas_form_events`
- [ ] **Command:** Run via phpMyAdmin or WP-CLI:
  ```bash
  wp db query "SHOW TABLES LIKE 'wp_vas_form%';"
  ```
- [ ] **Expected:** 2 tables listed

---

### 2. Block Editor Integration Test (10 minutes)

#### 2.1 Block Category
- [ ] Create new page/post
- [ ] Open block inserter (+ icon)
- [ ] Search for "EIPSI"
- [ ] **Expected:** "EIPSI Forms" category appears
- [ ] **Expected:** All blocks listed:
  - EIPSI Form Container
  - EIPSI Página
  - EIPSI Campo Texto
  - EIPSI Campo Textarea
  - EIPSI Campo Descripción
  - EIPSI Campo Select
  - EIPSI Campo Radio
  - EIPSI Campo Multiple
  - EIPSI Campo Likert
  - EIPSI VAS Slider

#### 2.2 Form Creation
- [ ] Add "EIPSI Form Container" block
- [ ] **Expected:** Form container appears with placeholder
- [ ] **Expected:** Inspector panel shows customization options
- [ ] Add several field blocks inside container
- [ ] **Expected:** Fields render correctly in editor
- [ ] **Expected:** No console errors (open browser DevTools)

#### 2.3 Block Customization Panel
- [ ] Select the Form Container block
- [ ] Open Block Inspector (right sidebar)
- [ ] **Expected:** See customization panels:
  - 🎨 Theme Presets
  - 🎨 Colors
  - ✍️ Typography
  - 📐 Spacing & Layout
  - 🔲 Borders & Radius
  - ✨ Shadows & Effects
  - ⚡ Hover & Interaction
- [ ] Apply a theme preset (e.g., "Clinical Blue")
- [ ] **Expected:** Visual changes apply immediately in editor
- [ ] Modify a color value
- [ ] **Expected:** Color picker works, changes reflected

---

### 3. Frontend Rendering Test (10 minutes)

#### 3.1 Basic Form Display
- [ ] Publish the page with the form
- [ ] Visit the published page (logged out)
- [ ] **Expected:** Form renders correctly
- [ ] **Expected:** No layout issues or broken styles
- [ ] **Expected:** No console errors (F12 → Console tab)

#### 3.2 Responsive Design
- [ ] Test on different screen sizes:
  - [ ] Desktop (1280px+)
  - [ ] Tablet (768px)
  - [ ] Mobile (375px)
  - [ ] Small mobile (320px)
- [ ] **Expected:** Form adapts to screen size
- [ ] **Expected:** No horizontal scrolling
- [ ] **Expected:** Touch targets adequate (44x44px minimum)

#### 3.3 Form Styling
- [ ] Verify applied theme preset is visible on frontend
- [ ] Check that custom colors match editor preview
- [ ] Verify all field types render properly:
  - [ ] Text inputs
  - [ ] Textarea
  - [ ] Select dropdowns
  - [ ] Radio buttons
  - [ ] Checkboxes
  - [ ] Likert scale
  - [ ] VAS slider

---

### 4. Form Submission Test (10 minutes)

#### 4.1 Fill and Submit
- [ ] Fill out all form fields
- [ ] Submit the form
- [ ] **Expected:** Form submits via AJAX
- [ ] **Expected:** Success message appears
- [ ] **Expected:** No page reload
- [ ] **Expected:** No console errors

#### 4.2 Data Capture
- [ ] Check that response was saved:
  - Navigate to WordPress admin → VAS Forms
- [ ] **Expected:** Form responses table appears
- [ ] **Expected:** Submitted response is listed
- [ ] Click "View" on the response
- [ ] **Expected:** All field values captured correctly
- [ ] **Expected:** Metadata captured:
  - [ ] Timestamp
  - [ ] IP address (if enabled)
  - [ ] Device type
  - [ ] Browser
  - [ ] OS
  - [ ] Screen width
  - [ ] Duration

---

### 5. Admin Functionality Test (10 minutes)

#### 5.1 Results Dashboard
- [ ] Navigate to VAS Forms in admin menu
- [ ] **Expected:** Results table displays
- [ ] **Expected:** Pagination works (if multiple responses)
- [ ] **Expected:** Sorting works (click column headers)
- [ ] Click "View" on a response
- [ ] **Expected:** Full response details displayed

#### 5.2 Export Functionality
- [ ] Click "Export Excel" button
- [ ] **Expected:** Excel file downloads
- [ ] Open the Excel file
- [ ] **Expected:** All responses exported correctly
- [ ] **Expected:** Proper formatting and column headers
- [ ] Try CSV export if available
- [ ] **Expected:** CSV file downloads and opens correctly

#### 5.3 Response Management
- [ ] Try editing a response (if feature available)
- [ ] **Expected:** Edit form works
- [ ] Try deleting a response
- [ ] **Expected:** Confirmation prompt appears
- [ ] Confirm deletion
- [ ] **Expected:** Response removed from list

---

### 6. Multi-Page Form Test (15 minutes)

#### 6.1 Create Multi-Page Form
- [ ] Create new page
- [ ] Add EIPSI Form Container
- [ ] Add multiple EIPSI Página blocks inside
- [ ] Add fields inside each page block
- [ ] Publish page

#### 6.2 Frontend Navigation
- [ ] Visit the published multi-page form
- [ ] **Expected:** Only first page visible
- [ ] **Expected:** "Next" button appears
- [ ] Click "Next"
- [ ] **Expected:** Second page appears
- [ ] **Expected:** "Previous" button appears
- [ ] Test backward navigation
- [ ] **Expected:** Returns to previous page
- [ ] Navigate to final page
- [ ] **Expected:** "Submit" button appears

#### 6.3 Conditional Logic (Optional)
- [ ] If time permits, test conditional branching:
  - Add conditional logic to a select/radio field
  - Set rules to skip pages based on response
  - Test on frontend
  - **Expected:** Form branches correctly

---

### 7. Compatibility Test (10 minutes)

#### 7.1 Browser Testing
Test on multiple browsers:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest) - if available
- [ ] Edge (latest)
- [ ] **Expected:** Consistent behavior across browsers
- [ ] **Expected:** No browser-specific errors

#### 7.2 Theme Compatibility
- [ ] Activate different WordPress theme
- [ ] Visit form page
- [ ] **Expected:** Form still renders correctly
- [ ] **Expected:** Styles not conflicting with theme

#### 7.3 Plugin Conflicts
- [ ] Activate common plugins:
  - [ ] Contact Form 7 or WPForms
  - [ ] Yoast SEO
  - [ ] WooCommerce (if testing e-commerce compatibility)
- [ ] **Expected:** No conflicts or errors
- [ ] **Expected:** Form still functions normally

---

## Expected Outcomes

### ✅ PASS Criteria
- All installation steps complete without errors
- All blocks appear in editor and function correctly
- Forms render properly on frontend at all breakpoints
- Form submissions save correctly to database
- Admin dashboard displays and exports data
- No PHP errors, warnings, or notices
- No JavaScript console errors
- Cross-browser compatibility confirmed

### ❌ FAIL Criteria
- Installation fails or shows errors
- Blocks missing or not registering
- Forms don't render or have broken styles
- Submissions don't save or show errors
- Admin dashboard shows errors
- PHP errors or warnings appear
- JavaScript console shows errors
- Browser incompatibilities detected

---

## Issue Reporting Template

If issues are found during testing, document them using this template:

```markdown
### Issue #X: [Brief Description]

**Severity:** Critical / High / Medium / Low
**Category:** Installation / Editor / Frontend / Admin / Export

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
[What should happen]

**Actual Behavior:**
[What actually happens]

**Environment:**
- WordPress Version: X.X.X
- PHP Version: X.X.X
- Browser: Name + Version
- Theme: Theme Name

**Screenshots/Logs:**
[Attach screenshots or error logs]

**Console Errors:**
[Copy any JavaScript console errors]
```

---

## Verification Evidence

### Required Documentation

#### 1. Installation Screenshots
- [ ] Plugin upload screen
- [ ] Activation success message
- [ ] Admin menu with "VAS Forms" entry

#### 2. Block Editor Screenshots
- [ ] Block inserter showing EIPSI Forms category
- [ ] Form container in editor
- [ ] Customization panel open

#### 3. Frontend Screenshots
- [ ] Form on desktop (1280px)
- [ ] Form on tablet (768px)
- [ ] Form on mobile (375px)

#### 4. Submission Test Evidence
- [ ] Submission success message
- [ ] Admin results table with test response
- [ ] Response detail view
- [ ] Exported Excel file

#### 5. Console Logs
- [ ] Browser DevTools console (no errors)
- [ ] Network tab showing successful AJAX requests

---

## Quick Test Script

For rapid testing, run this condensed version (20 minutes):

1. **Install & Activate** (3 min)
   - Upload zip → Activate → Check admin menu

2. **Create Simple Form** (5 min)
   - Add Form Container → Add 3 fields → Apply preset → Publish

3. **Frontend Test** (5 min)
   - View page → Fill form → Submit → Verify success

4. **Admin Check** (3 min)
   - Check results table → View response → Export Excel

5. **Responsive Check** (4 min)
   - Resize browser to mobile → Verify layout

---

## Post-Test Checklist

- [ ] All tests passed
- [ ] Screenshots captured
- [ ] Console logs reviewed (no errors)
- [ ] Issue report created (if problems found)
- [ ] Test results documented
- [ ] Package approved for release / rejected for fixes

---

## Test Results Template

```markdown
# EIPSI Forms v1.X.X - Smoke Test Results

**Tester:** [Name]
**Date:** [YYYY-MM-DD]
**Environment:**
- WordPress: X.X.X
- PHP: X.X.X
- Server: Local/Staging/Production
- Browser: Name + Version

## Test Summary

| Test Category | Status | Notes |
|---------------|--------|-------|
| Installation | ✅ PASS | No errors |
| Block Editor | ✅ PASS | All blocks functional |
| Frontend Rendering | ✅ PASS | Responsive design works |
| Form Submission | ✅ PASS | Data saved correctly |
| Admin Dashboard | ✅ PASS | Export successful |
| Multi-Page Forms | ✅ PASS | Navigation smooth |
| Compatibility | ✅ PASS | No conflicts |

## Overall Status: ✅ APPROVED FOR RELEASE

**Confidence Level:** High / Medium / Low

**Recommendation:** 
[Approve for distribution / Require fixes before release]

**Critical Issues Found:** 
[None / List issues]

**Notes:**
[Any additional observations]
```

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-10  
**Related Files:** 
- `build-release.sh`
- `DISTRIBUTION_CHECKLIST.md`
- `RELEASE_PACKAGE_DOCUMENTATION.md`
