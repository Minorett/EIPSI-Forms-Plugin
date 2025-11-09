# EIPSI Forms - Editor Smoke Test Checklist

**Version:** 2.2.0  
**Last Updated:** 2024  
**Purpose:** Comprehensive manual testing checklist for Gutenberg editor workflows

---

## Prerequisites

### Environment Setup
- [ ] WordPress 6.4+ running locally or staging
- [ ] EIPSI Forms plugin installed and activated
- [ ] Plugin built with latest changes: `npm run build`
- [ ] Browser DevTools open (F12) with Console tab visible
- [ ] Clear browser cache and localStorage

### Baseline Verification
- [ ] No console errors on dashboard load
- [ ] Plugin appears in Plugins list
- [ ] EIPSI Forms menu visible in admin sidebar
- [ ] Block editor loads without errors on new post/page

---

## Test Matrix

### 1. Block Insertion Workflow

#### 1.1 Form Container Block
- [ ] **Insert Form Container**
  - [ ] Click (+) block inserter
  - [ ] Search "Form Container" or "EIPSI"
  - [ ] Block appears in search results
  - [ ] Click to insert
  - [ ] Block renders in editor with placeholder
  - [ ] Inspector controls visible in right sidebar
  - [ ] No console errors during insertion

- [ ] **Configure Form Settings**
  - [ ] Set Form ID: `smoke-test-form-001`
  - [ ] Enable Multi-Page mode toggle
  - [ ] Verify toggle state persists after clicking elsewhere
  - [ ] Check "Enable Tracking" option
  - [ ] Verify tracking fields appear (Participant ID, Interaction)

#### 1.2 Page Blocks (Minimum 3 Pages)
- [ ] **Insert Page 1**
  - [ ] Click inside Form Container
  - [ ] Insert "Form Page" block
  - [ ] Set Page Title: "Demographics"
  - [ ] Set Page ID: `page-1-demographics`
  - [ ] Verify page renders with title
  - [ ] No console errors

- [ ] **Insert Page 2**
  - [ ] Insert second "Form Page" block
  - [ ] Set Page Title: "Clinical Assessment"
  - [ ] Set Page ID: `page-2-assessment`
  - [ ] Verify page numbering/order

- [ ] **Insert Page 3**
  - [ ] Insert third "Form Page" block
  - [ ] Set Page Title: "Follow-Up Questions"
  - [ ] Set Page ID: `page-3-followup`
  - [ ] Verify all 3 pages visible in editor

- [ ] **Insert Page 4 (Optional)**
  - [ ] Insert fourth "Form Page" block
  - [ ] Set Page Title: "Thank You"
  - [ ] Add paragraph block with completion message

#### 1.3 Field Blocks (Mix All Types)

**Page 1 Fields:**
- [ ] **Text Field**
  - [ ] Insert into Page 1
  - [ ] Label: "Full Name"
  - [ ] Field Name: `full_name`
  - [ ] Required: ✓
  - [ ] Placeholder: "Enter your full name"
  - [ ] Verify preview shows label and placeholder

- [ ] **Select Field**
  - [ ] Insert into Page 1
  - [ ] Label: "Age Group"
  - [ ] Field Name: `age_group`
  - [ ] Options (one per line):
    ```
    18-24
    25-34
    35-44
    45-54
    55-64
    65+
    ```
  - [ ] Required: ✓
  - [ ] Verify dropdown renders with options

**Page 2 Fields:**
- [ ] **Radio Field**
  - [ ] Insert into Page 2
  - [ ] Label: "Have you received therapy before?"
  - [ ] Field Name: `therapy_history`
  - [ ] Options:
    ```
    Yes
    No
    Prefer not to say
    ```
  - [ ] Required: ✓
  - [ ] Verify radio buttons render

- [ ] **Checkbox Field**
  - [ ] Insert into Page 2
  - [ ] Label: "Select symptoms experienced (check all that apply)"
  - [ ] Field Name: `symptoms`
  - [ ] Options:
    ```
    Anxiety
    Depression
    Sleep issues
    Stress
    Other
    ```
  - [ ] Required: ✗
  - [ ] Verify checkboxes render

- [ ] **Likert Scale Field**
  - [ ] Insert into Page 2
  - [ ] Label: "Overall mood in the past week"
  - [ ] Field Name: `mood_rating`
  - [ ] Scale Type: 1-5
  - [ ] Left Label: "Very Poor"
  - [ ] Right Label: "Excellent"
  - [ ] Required: ✓
  - [ ] Verify scale renders with labels

**Page 3 Fields:**
- [ ] **VAS Slider Field**
  - [ ] Insert into Page 3
  - [ ] Label: "Current stress level (0-100)"
  - [ ] Field Name: `stress_vas`
  - [ ] Min Value: 0
  - [ ] Max Value: 100
  - [ ] Default: 50
  - [ ] Left Label: "No stress"
  - [ ] Right Label: "Maximum stress"
  - [ ] Required: ✓
  - [ ] Verify slider renders and moves smoothly

- [ ] **Text Area Field**
  - [ ] Insert into Page 3
  - [ ] Label: "Additional comments or concerns"
  - [ ] Field Name: `comments`
  - [ ] Placeholder: "Please share any additional information..."
  - [ ] Rows: 5
  - [ ] Required: ✗
  - [ ] Verify textarea renders

---

### 2. Conditional Logic Configuration

#### 2.1 Basic Conditional Navigation (Page-Level)

**Test Case: Skip Page 3 if Age Group is "65+"**

- [ ] **Select Page 3 block**
- [ ] **Open Conditional Logic panel** in inspector
- [ ] **Enable Conditional Logic** toggle
- [ ] **Configure Rule 1:**
  - [ ] Condition Type: "Skip Page"
  - [ ] If Field: `age_group`
  - [ ] Operator: "equals"
  - [ ] Value: `65+`
  - [ ] Action: "Skip to Page 4" (or Submit)

- [ ] **Verify Configuration:**
  - [ ] Rule appears in the list
  - [ ] No duplicate value warnings
  - [ ] No empty field errors
  - [ ] Rule can be edited after saving
  - [ ] Rule can be deleted

**Test Case: Conditional Branch Based on Radio Button**

- [ ] **Select Page 2 block**
- [ ] **Enable Conditional Logic**
- [ ] **Configure Rule:**
  - [ ] If Field: `therapy_history`
  - [ ] Operator: "equals"
  - [ ] Value: `Yes`
  - [ ] Action: "Go to Page 3"
  - [ ] Else: "Submit form"

- [ ] **Verify Configuration:**
  - [ ] Rule displays correctly
  - [ ] Multiple rules can be added
  - [ ] Rules can be reordered (if applicable)
  - [ ] AND/OR logic visible (if multiple rules)

#### 2.2 Field-Level Conditional Display (if applicable)

- [ ] **Select a field block** (e.g., Text Area on Page 3)
- [ ] **Open Conditional Logic panel**
- [ ] **Configure Show/Hide Rule:**
  - [ ] Show this field if: `symptoms` (checkbox)
  - [ ] Operator: "contains"
  - [ ] Value: `Other`
- [ ] **Verify:**
  - [ ] Rule saves correctly
  - [ ] No console errors

#### 2.3 Validation & Error Handling

- [ ] **Test Duplicate Values:**
  - [ ] Create two rules with identical field/value combinations
  - [ ] Verify error message: "Duplicate condition value"
  - [ ] Verify rule highlighting or border color change

- [ ] **Test Empty Values:**
  - [ ] Leave condition value empty
  - [ ] Verify error message appears
  - [ ] Verify rule is marked as invalid

- [ ] **Test Invalid Field References:**
  - [ ] Select a field that doesn't exist in earlier pages
  - [ ] Verify warning or error (if validation exists)

---

### 3. Form Style Panel Modifications

#### 3.1 Theme Preset Selection

- [ ] **Select Form Container block**
- [ ] **Open "Form Styles" panel** in inspector
- [ ] **Apply Clinical Blue preset:**
  - [ ] Click "Clinical Blue" preset button
  - [ ] Verify checkmark appears on button
  - [ ] Verify preview updates instantly
  - [ ] Check CSS variables applied (inspect element)
  - [ ] Verify `--eipsi-color-primary: #005a87`

- [ ] **Apply Minimal White preset:**
  - [ ] Click "Minimal White" preset
  - [ ] Verify previous preset checkmark clears
  - [ ] Verify new preset checkmark appears
  - [ ] Verify background changes to white
  - [ ] Verify primary color changes to `#2c5aa0`

- [ ] **Apply Warm Neutral preset:**
  - [ ] Click "Warm Neutral" preset
  - [ ] Verify typography changes (serif headings)
  - [ ] Verify warm color palette applied

- [ ] **Apply High Contrast preset:**
  - [ ] Click "High Contrast" preset
  - [ ] Verify high contrast colors
  - [ ] Verify no shadows (accessibility)

#### 3.2 Custom Color Modifications

- [ ] **Modify Primary Color:**
  - [ ] Click on primary color swatch
  - [ ] Change color to `#8b0000` (dark red)
  - [ ] Verify preset checkmark clears (custom mode)
  - [ ] Verify preview updates immediately
  - [ ] Check contrast warning (if below WCAG AA)

- [ ] **Modify Background Color:**
  - [ ] Change background to `#f0f0f0`
  - [ ] Verify preview updates
  - [ ] Check contrast ratio displayed

- [ ] **Modify Button Colors:**
  - [ ] Change button background to custom color
  - [ ] Change button text color
  - [ ] Verify hover state preview (if available)

#### 3.3 Typography Modifications

- [ ] **Change Heading Font:**
  - [ ] Select "Georgia" or another serif font
  - [ ] Verify preview headings update

- [ ] **Change Body Font:**
  - [ ] Select "Arial" or another sans-serif font
  - [ ] Verify field labels update

- [ ] **Adjust Font Sizes:**
  - [ ] Increase base font size to 18px
  - [ ] Verify all text scales proportionally
  - [ ] Verify no layout breaks

#### 3.4 Spacing & Layout Modifications

- [ ] **Adjust Container Padding:**
  - [ ] Change to "xl" (2.5rem)
  - [ ] Verify form container grows
  - [ ] No overlap with editor controls

- [ ] **Adjust Field Gap:**
  - [ ] Change to "lg" (2rem)
  - [ ] Verify spacing between fields increases

- [ ] **Adjust Border Radius:**
  - [ ] Change to "lg" (12px)
  - [ ] Verify all inputs get rounded corners

#### 3.5 Advanced Customization

- [ ] **Shadow Effects:**
  - [ ] Change shadow to "lg"
  - [ ] Verify form container has depth

- [ ] **Focus Ring Customization:**
  - [ ] Modify focus ring color
  - [ ] Tab through fields in preview
  - [ ] Verify focus ring visible and styled

- [ ] **Transition Duration:**
  - [ ] Change to 0.5s
  - [ ] Hover over buttons in preview
  - [ ] Verify smooth transitions

#### 3.6 Style Panel Edge Cases

- [ ] **Preview Responsiveness:**
  - [ ] Apply complex styles
  - [ ] Switch editor view to mobile preview (if available)
  - [ ] Verify no layout breaks

- [ ] **Reset to Defaults:**
  - [ ] Click "Reset to Defaults" button
  - [ ] Verify all 52 tokens reset to DEFAULT_STYLE_CONFIG
  - [ ] Verify preview returns to default appearance

- [ ] **No Layout Shift:**
  - [ ] With complex multi-page form loaded
  - [ ] Change multiple style settings rapidly
  - [ ] Verify no block controls disappear or shift position
  - [ ] Verify List View remains accessible

---

### 4. Common Editor Workflows

#### 4.1 Block Duplication

- [ ] **Duplicate Page Block:**
  - [ ] Select Page 2
  - [ ] Click "..." options menu
  - [ ] Click "Duplicate"
  - [ ] Verify duplicate appears below original
  - [ ] Verify field blocks duplicated inside
  - [ ] Verify field names remain unique (or warn if not)

- [ ] **Duplicate Field Block:**
  - [ ] Select Text Field
  - [ ] Duplicate via options menu or Ctrl+Shift+D
  - [ ] Verify duplicate appears
  - [ ] Change field name to avoid conflicts

#### 4.2 Move Fields Between Pages

- [ ] **Drag and Drop:**
  - [ ] Select a field from Page 1
  - [ ] Drag to Page 2
  - [ ] Verify field moves successfully
  - [ ] Verify page re-renders correctly

- [ ] **Cut and Paste:**
  - [ ] Select field
  - [ ] Ctrl+X (cut)
  - [ ] Select different page
  - [ ] Ctrl+V (paste)
  - [ ] Verify field appears in new location

#### 4.3 Undo/Redo

- [ ] **Undo Block Insertion:**
  - [ ] Insert a new field
  - [ ] Press Ctrl+Z
  - [ ] Verify field removed
  - [ ] No console errors

- [ ] **Undo Style Changes:**
  - [ ] Change primary color
  - [ ] Press Ctrl+Z
  - [ ] Verify color reverts
  - [ ] Verify preview updates

- [ ] **Redo Actions:**
  - [ ] After undo, press Ctrl+Shift+Z
  - [ ] Verify action restored

- [ ] **Undo/Redo Multiple Steps:**
  - [ ] Perform 5-10 actions
  - [ ] Undo all
  - [ ] Redo all
  - [ ] Verify form state correct

#### 4.4 Copy/Paste Blocks

- [ ] **Copy Page Block:**
  - [ ] Select page
  - [ ] Ctrl+C
  - [ ] Click outside form
  - [ ] Ctrl+V
  - [ ] Verify page pastes with all nested fields

- [ ] **Copy Multiple Fields:**
  - [ ] Select multiple fields (Shift+Click or Ctrl+Click)
  - [ ] Copy
  - [ ] Paste into different page
  - [ ] Verify all fields paste correctly

#### 4.5 Editor View Modes

- [ ] **List View Navigation:**
  - [ ] Open List View (Ctrl+Shift+O or icon)
  - [ ] Verify form structure visible
  - [ ] Click on nested field in List View
  - [ ] Verify field selects in editor
  - [ ] Drag blocks in List View
  - [ ] Verify reorder works

- [ ] **Document Overview:**
  - [ ] Open Document Overview panel
  - [ ] Verify block count accurate
  - [ ] Verify outline shows form structure

- [ ] **Block Inserter Search:**
  - [ ] Open block inserter
  - [ ] Search "EIPSI" or "VAS" or "Likert"
  - [ ] Verify all custom blocks appear
  - [ ] Insert from search results

#### 4.6 Keyboard Navigation

- [ ] **Tab Through Blocks:**
  - [ ] Tab through inspector controls
  - [ ] Verify all inputs reachable
  - [ ] Verify visible focus indicators

- [ ] **Arrow Key Navigation:**
  - [ ] Use Up/Down arrows to navigate blocks
  - [ ] Verify selection changes

- [ ] **Block Shortcuts:**
  - [ ] Type `/` to open block inserter
  - [ ] Search for block name
  - [ ] Press Enter to insert

---

### 5. Console Error Monitoring

#### 5.1 Initial Load
- [ ] No errors on page editor load
- [ ] No React key warnings
- [ ] No deprecated API warnings

#### 5.2 Block Operations
- [ ] No errors during block insertion
- [ ] No errors during block deletion
- [ ] No errors during block duplication
- [ ] No errors during block move/drag

#### 5.3 Attribute Updates
- [ ] No errors when typing in text fields
- [ ] No errors when toggling checkboxes
- [ ] No errors when selecting dropdowns
- [ ] No errors when adding/removing options
- [ ] No errors when changing colors in style panel

#### 5.4 Conditional Logic
- [ ] No errors when enabling conditional logic
- [ ] No errors when adding rules
- [ ] No errors when deleting rules
- [ ] No errors when editing rule values

#### 5.5 Style Panel
- [ ] No errors when changing colors
- [ ] No errors when changing fonts
- [ ] No errors when changing spacing
- [ ] No errors when applying presets
- [ ] No errors when resetting to defaults

#### 5.6 Save Operations
- [ ] No errors during manual save (Ctrl+S)
- [ ] No errors during autosave
- [ ] No errors during publish/update

---

### 6. Persistence Verification

#### 6.1 Save and Reload
- [ ] **After all configurations above:**
  - [ ] Save post/page (Ctrl+S)
  - [ ] Wait for "Saved" indicator
  - [ ] Reload page (F5)
  - [ ] Wait for editor to fully load

#### 6.2 Verify Attributes Persist
- [ ] **Form Container:**
  - [ ] Form ID matches
  - [ ] Multi-page toggle state preserved
  - [ ] Tracking settings preserved
  - [ ] **styleConfig attribute present** (check in browser console):
    ```javascript
    wp.data.select('core/block-editor').getBlocks()[0].attributes.styleConfig
    ```

- [ ] **Page Blocks:**
  - [ ] All pages present (count matches)
  - [ ] Page titles preserved
  - [ ] Page IDs preserved
  - [ ] **conditionalLogic attribute present** (if configured)

- [ ] **Field Blocks:**
  - [ ] All fields present
  - [ ] Field names preserved
  - [ ] Field labels preserved
  - [ ] Options preserved (select/radio/checkbox)
  - [ ] Required state preserved
  - [ ] Placeholders preserved

- [ ] **Style Settings:**
  - [ ] Custom colors preserved
  - [ ] Custom typography preserved
  - [ ] Custom spacing preserved
  - [ ] Preset selection preserved (activePreset may reset, but config should remain)
  - [ ] Inline styles applied to form container

- [ ] **Conditional Logic:**
  - [ ] All rules preserved
  - [ ] Field references intact
  - [ ] Operators preserved
  - [ ] Actions preserved

#### 6.3 Preview Rendering After Reload
- [ ] Form container renders with styles
- [ ] All pages visible in editor
- [ ] All fields render correctly
- [ ] CSS variables applied (check inline styles)
- [ ] No visual regressions

---

### 7. Edge Cases & Stress Tests

#### 7.1 Large Form Complexity
- [ ] **Create form with 20+ fields**
  - [ ] Editor remains responsive
  - [ ] No lag when selecting blocks
  - [ ] Save completes in <5 seconds
  - [ ] No memory leaks (check DevTools Memory tab)

#### 7.2 Nested Block Limits
- [ ] **Insert 10 pages with 5 fields each**
  - [ ] All blocks render
  - [ ] List View remains navigable
  - [ ] Undo/redo history works

#### 7.3 Rapid Attribute Changes
- [ ] **Quickly change field names 10 times**
  - [ ] No debounce issues
  - [ ] No lost keystrokes
  - [ ] No console errors

#### 7.4 Style Panel Performance
- [ ] **Rapidly switch presets 10 times**
  - [ ] No lag
  - [ ] Preview updates smoothly
  - [ ] No broken layouts

#### 7.5 Conditional Logic Complexity
- [ ] **Add 10 conditional rules to single page**
  - [ ] All rules display
  - [ ] Panel scrollable
  - [ ] No performance degradation

---

### 8. Browser Compatibility (Optional but Recommended)

#### 8.1 Chrome/Chromium
- [ ] All tests above pass
- [ ] No browser-specific errors

#### 8.2 Firefox
- [ ] All tests above pass
- [ ] No browser-specific errors

#### 8.3 Safari (Mac only)
- [ ] All tests above pass
- [ ] No browser-specific errors

#### 8.4 Edge
- [ ] All tests above pass
- [ ] No browser-specific errors

---

## Test Report Template

### Test Session Information
- **Date:** [YYYY-MM-DD]
- **Tester:** [Name]
- **Environment:**
  - WordPress Version: [e.g., 6.4.2]
  - Plugin Version: [e.g., 2.2.0]
  - Browser: [e.g., Chrome 120.0]
  - OS: [e.g., macOS 14.0]

### Test Results Summary
- **Total Checks:** [e.g., 150]
- **Passed:** ✓ [e.g., 145]
- **Failed:** ✗ [e.g., 3]
- **Skipped:** ○ [e.g., 2]
- **Console Errors:** [e.g., 0]
- **Console Warnings:** [e.g., 2]

### Failed Tests
List any failed tests with:
1. Test name
2. Expected behavior
3. Actual behavior
4. Steps to reproduce
5. Screenshot/video (if applicable)
6. Console errors (if applicable)

### Console Errors/Warnings Log
Paste any critical console messages here.

### Defects Found
- **Issue 1:** [Description]
  - **Severity:** Critical / High / Medium / Low
  - **Steps to Reproduce:**
  - **Expected Result:**
  - **Actual Result:**
  - **Screenshot:**

### Recommendations
List any suggestions for improvements or additional tests needed.

---

## Acceptance Criteria (from Ticket)

✓ **Complex forms can be authored without encountering editor errors or broken controls.**
- [ ] All test scenarios passed without errors
- [ ] No console errors during entire session
- [ ] All inspector controls functional

✓ **All configured attributes persist after save/reload and render correctly in editor preview.**
- [ ] styleConfig attribute persists
- [ ] conditionalLogic attribute persists
- [ ] All field attributes persist
- [ ] Preview matches configuration

✓ **Smoke-test report enumerates scenarios covered and highlights any defects found.**
- [ ] This checklist completed
- [ ] Test report generated (see template above)
- [ ] Screenshots saved for reference
- [ ] Defects documented with reproduction steps

---

## Quick Reference: CSS Variable Verification

To verify CSS variables are properly applied in the editor, open DevTools Console and run:

```javascript
// Get form container element
const formContainer = document.querySelector('.eipsi-form-container-editor, .wp-block-vas-dinamico-form-container');

// Check inline styles
console.log('Inline Styles:', formContainer.getAttribute('style'));

// Check computed CSS variables
const styles = window.getComputedStyle(formContainer);
console.log('Primary Color:', styles.getPropertyValue('--eipsi-color-primary'));
console.log('Background:', styles.getPropertyValue('--eipsi-color-background'));
console.log('Font Family:', styles.getPropertyValue('--eipsi-font-family-body'));

// Check styleConfig attribute
const blocks = wp.data.select('core/block-editor').getBlocks();
const formBlock = blocks.find(b => b.name === 'vas-dinamico/form-container');
console.log('styleConfig:', formBlock?.attributes?.styleConfig);
```

---

## Automated Test Companion

This manual checklist complements the automated smoke test script:

```bash
# Run automated tests first
node test-editor-smoke.js

# Then perform manual verification for items marked [MANUAL]
# Review generated report: EDITOR_SMOKE_TEST_REPORT.md
```

---

**Note:** This is a living document. Update as new features are added or edge cases discovered.
