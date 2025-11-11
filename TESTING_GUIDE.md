# EIPSI Forms - Testing Guide
## Bug Fixes: Radio Fields, Navigation, VAS Labels, and Post-Submission UX

This guide provides step-by-step testing procedures for all fixes implemented in this release.

---

## Pre-Testing Setup

1. **Update WordPress Installation**
   - Ensure WordPress is up to date
   - Activate the EIPSI Forms plugin

2. **Clear Cache**
   - Clear browser cache (hard refresh: Ctrl+Shift+R or Cmd+Shift+R)
   - Clear WordPress cache (if using caching plugin)

3. **Test Environment**
   - Desktop browser (Chrome, Firefox, Safari, or Edge)
   - Mobile device OR browser DevTools responsive mode
   - Screen sizes to test: 320px, 375px, 768px, 1024px, 1280px

---

## Test 1: Radio Fields - Deselection Behavior

### Objective
Verify that all radio groups work independently and allow deselection.

### Steps

1. **Create Test Form**
   - Create new page/post in WordPress
   - Add "EIPSI Form Container" block
   - Set Form ID: `test-radio-fields`
   - Add 3 "Campo Radio" (Radio Field) blocks:
     - Field 1: "Gender" with options: Male, Female, Other
     - Field 2: "Experience" with options: Beginner, Intermediate, Advanced
     - Field 3: "Preference" with options: Yes, No, Maybe

2. **Test Radio Group 1 (Gender)**
   - Click "Male" → Verify it gets selected
   - Click "Female" → Verify "Male" deselects and "Female" selects
   - Click "Female" again → Verify "Female" deselects (none selected)
   - ✅ Expected: Radio deselects when clicking the same option

3. **Test Radio Group 2 (Experience)**
   - Click "Beginner" → Verify it gets selected
   - Click "Advanced" → Verify "Beginner" deselects and "Advanced" selects
   - Click "Advanced" again → Verify "Advanced" deselects
   - ✅ Expected: Independent from Group 1

4. **Test Radio Group 3 (Preference)**
   - Click "Yes" → Verify it gets selected
   - Click "No" → Verify "Yes" deselects and "No" selects
   - Click "No" again → Verify "No" deselects
   - ✅ Expected: Independent from Groups 1 and 2

5. **Test Validation**
   - Mark all fields as required
   - Try to submit without selecting any radio
   - ✅ Expected: Validation error appears
   - Select one option in each group
   - ✅ Expected: Form submits successfully

### Pass Criteria
- ✅ All radio groups work independently
- ✅ Clicking selected radio deselects it
- ✅ Validation runs after deselection
- ✅ No JavaScript errors in console

---

## Test 2: Navigation Buttons - Visibility Logic

### Objective
Verify correct button visibility on each page.

### Steps

1. **Create Multi-Page Form**
   - Create new page/post
   - Add "EIPSI Form Container" block
   - Set Form ID: `test-navigation`
   - Add 3 "Form Page" blocks
   - Add at least one field to each page (text field is fine)

2. **Test Page 1**
   - View form on frontend
   - ✅ Expected: Only "Siguiente" (Next) button visible
   - ❌ Not Expected: "Anterior" (Previous) button should NOT be visible
   - Take screenshot for documentation

3. **Test Page 2**
   - Click "Siguiente"
   - ✅ Expected: Both "Anterior" and "Siguiente" visible
   - Click "Anterior" to go back to page 1
   - ✅ Expected: Only "Siguiente" visible again

4. **Test Last Page**
   - Navigate to Page 3 (last page)
   - ✅ Expected: "Anterior" and "Enviar" (Submit) visible
   - ❌ Not Expected: "Siguiente" should NOT be visible
   - Take screenshot for documentation

5. **Test "Allow Backwards Navigation" Toggle**
   - Go back to WordPress editor
   - Select Form Container block
   - Find "Navigation Settings" panel in right sidebar
   - Toggle OFF "Allow backwards navigation"
   - Save and view frontend
   - Navigate through all pages
   - ✅ Expected: "Anterior" button NEVER appears on any page
   - Toggle ON again
   - ✅ Expected: "Anterior" button appears as normal (pages 2+)

### Pass Criteria
- ✅ Page 1: Only "Siguiente" shows
- ✅ Page 2-N: "Anterior" and "Siguiente" show
- ✅ Last page: "Anterior" and "Enviar" show
- ✅ Toggle OFF: "Anterior" never shows
- ✅ Toggle ON: "Anterior" shows normally

---

## Test 3: VAS Slider - Label Styling

### Objective
Verify that Label Style and Label Alignment controls work correctly.

### Steps

1. **Create VAS Slider Form**
   - Create new page/post
   - Add "EIPSI Form Container" block
   - Set Form ID: `test-vas-labels`
   - Add "VAS Slider" block
   - Set label: "Pain Level"
   - Set left label: "No Pain"
   - Set right label: "Worst Pain"

2. **Test Label Style: Simple (Default)**
   - In block settings, find "Label Style"
   - Select "Simple" or ensure it's default
   - Save and view frontend
   - ✅ Expected: Labels are transparent, minimal styling
   - Take screenshot

3. **Test Label Style: Squares**
   - Back to editor, select "Squares"
   - Save and view frontend
   - ✅ Expected: Labels have EIPSI blue background (#005a87)
   - ✅ Expected: Labels appear as badges/pills
   - ✅ Expected: White text on blue background
   - Take screenshot

4. **Test Label Style: Buttons**
   - Back to editor, select "Buttons"
   - Save and view frontend
   - ✅ Expected: Labels have blue border (#005a87)
   - ✅ Expected: White/light background
   - ✅ Expected: Blue text
   - ✅ Expected: Hover effect (background becomes blue)
   - Take screenshot

5. **Test Label Alignment: Justified (Default)**
   - In block settings, find "Label Alignment"
   - Ensure "Justified" is selected
   - Save and view frontend
   - ✅ Expected: Labels spread edge-to-edge (full width)
   - Measure with browser DevTools
   - Take screenshot

6. **Test Label Alignment: Centered**
   - Back to editor, select "Centered"
   - Save and view frontend
   - ✅ Expected: Labels are centered with spacing
   - ✅ Expected: Not edge-to-edge
   - ✅ Expected: Gap between labels and edges
   - Take screenshot

7. **Test Multi-Label VAS**
   - Edit VAS block
   - In "Labels" field, enter: `None,Mild,Moderate,Severe,Extreme`
   - Test all 3 label styles (Simple, Squares, Buttons)
   - Test both alignments (Justified, Centered)
   - ✅ Expected: All styles work with multiple labels

8. **Test Responsive Behavior**
   - Resize browser to 320px, 375px, 768px
   - ✅ Expected: Labels remain readable
   - ✅ Expected: No horizontal scrolling
   - ✅ Expected: Styles maintained at all sizes

### Pass Criteria
- ✅ Simple style: Transparent, minimal
- ✅ Squares style: Blue background badges
- ✅ Buttons style: Blue bordered buttons
- ✅ Justified: Edge-to-edge labels
- ✅ Centered: Centered with spacing
- ✅ Multi-labels work with all styles
- ✅ Responsive at 320px, 375px, 768px

---

## Test 4: Post-Submission UX - Enhanced Flow

### Objective
Verify the improved submission flow with proper timing and animations.

### Steps

1. **Create Complete Form**
   - Create new page/post
   - Add "EIPSI Form Container" block
   - Set Form ID: `test-submission-ux`
   - Add 2 pages with required fields
   - Ensure backend form submission is working

2. **Test Successful Submission**
   - Fill out all required fields
   - Click "Enviar" (Submit)
   
3. **Verify Immediate Response (0 seconds)**
   - ✅ Expected: Button text changes to "Enviando..."
   - ✅ Expected: Button is disabled (greyed out)
   - ✅ Expected: Form shows loading state

4. **Verify Success Message (after ~1 second)**
   - ✅ Expected: Green success message appears at TOP of form
   - ✅ Expected: Message says "¡Formulario enviado correctamente!"
   - ✅ Expected: Message includes subtitle "Gracias por completar el formulario"
   - ✅ Expected: Submit button re-enabled with original text
   - ✅ Expected: Submit button disabled again immediately
   - Take screenshot with stopwatch showing ~1s elapsed

5. **Verify Form Reset (after 3 seconds total)**
   - Start timer when message appears
   - Wait 3 seconds from submission
   - ✅ Expected: Form fields reset to default values
   - ✅ Expected: Form returns to Page 1
   - ✅ Expected: VAS sliders reset to initial value
   - ✅ Expected: Radio buttons deselected
   - ✅ Expected: Text fields cleared
   - ✅ Expected: Success message still visible
   - Take screenshot with stopwatch showing ~3s elapsed

6. **Verify Submit Button Re-enable (after 4 seconds total)**
   - Wait until 4 seconds from submission
   - ✅ Expected: Submit button enabled again
   - ✅ Expected: Can click submit button again
   - Take screenshot with stopwatch showing ~4s elapsed

7. **Verify Message Auto-Dismiss (after 5 seconds total)**
   - Wait until 5 seconds from submission
   - ✅ Expected: Success message fades out smoothly
   - ✅ Expected: Message removed from DOM after fade
   - ✅ Expected: Form fully ready for new submission
   - Take screenshot with stopwatch showing ~5s elapsed

8. **Test Multiple Submissions**
   - Fill form again
   - Submit again
   - ✅ Expected: Same flow works correctly
   - ✅ Expected: No leftover messages from previous submission

9. **Test Error Handling**
   - Disconnect internet (DevTools offline mode)
   - Try to submit form
   - ✅ Expected: Red error message appears
   - ✅ Expected: "Ocurrió un error. Por favor, inténtelo de nuevo."
   - ✅ Expected: Error message does NOT auto-dismiss
   - ✅ Expected: Form does NOT reset

### Timeline Summary
```
0s  → Submit clicked, "Enviando..." appears, button disabled
1s  → Success message appears (green), button re-enabled then disabled
3s  → Form resets to page 1, all fields cleared
4s  → Submit button re-enabled
5s  → Success message fades out and disappears
```

### Pass Criteria
- ✅ Success message appears prominently at top
- ✅ Message auto-dismisses after 5 seconds with fade animation
- ✅ Form resets after 3 seconds
- ✅ Form returns to page 1 after reset
- ✅ Submit button disabled for 4 seconds after submission
- ✅ VAS sliders reset properly
- ✅ Navigator history cleared
- ✅ Multiple submissions work correctly
- ✅ Error messages do NOT auto-dismiss
- ✅ No JavaScript errors in console

---

## Test 5: Cross-Browser Compatibility

### Objective
Ensure all fixes work across different browsers and devices.

### Browsers to Test
- ✅ Chrome/Chromium (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile Safari (iOS 15+)
- ✅ Chrome Mobile (Android 10+)

### Test Matrix
For EACH browser, verify:
1. Radio deselection works
2. Navigation buttons show correctly
3. VAS label styles apply
4. Post-submission flow completes

### Mobile-Specific Tests
- Test on physical device (not just DevTools)
- Verify touch targets are adequate (44x44px)
- Verify no horizontal scrolling
- Verify animations are smooth
- Test landscape and portrait orientations

---

## Test 6: Accessibility

### Objective
Ensure fixes maintain WCAG AA compliance.

### Steps

1. **Keyboard Navigation**
   - Use Tab to navigate through form
   - ✅ Expected: Focus visible on all interactive elements
   - ✅ Expected: Radio fields can be selected with Space
   - ✅ Expected: VAS sliders work with arrow keys
   - ✅ Expected: Navigation buttons accessible via Tab

2. **Screen Reader Testing**
   - Use NVDA (Windows) or VoiceOver (Mac)
   - ✅ Expected: Radio groups announced correctly
   - ✅ Expected: VAS labels read aloud
   - ✅ Expected: Success message announced
   - ✅ Expected: Error messages announced

3. **Color Contrast**
   - Use browser contrast checker
   - ✅ Expected: All text meets 4.5:1 minimum
   - ✅ Expected: VAS label styles maintain contrast
   - ✅ Expected: Success/error messages readable

4. **Focus Indicators**
   - Desktop: 2px outline visible
   - Mobile/Tablet (≤768px): 3px outline visible
   - ✅ Expected: EIPSI blue (#005a87) focus rings
   - ✅ Expected: No invisible focus states

---

## Test 7: Edge Cases

### Test 7.1: Single-Page Form
- Create form with only 1 page
- ✅ Expected: No "Anterior" button
- ✅ Expected: Only "Enviar" button shows
- ✅ Expected: Post-submission reset works

### Test 7.2: Form with Conditional Logic
- Create form with conditional navigation
- ✅ Expected: Radio deselection triggers re-evaluation
- ✅ Expected: Navigation buttons update correctly
- ✅ Expected: Skipped pages handled properly

### Test 7.3: Required VAS Slider
- Mark VAS slider as required
- Try to submit without touching slider
- ✅ Expected: Validation error shows
- Move slider then submit
- ✅ Expected: Submits successfully

### Test 7.4: Long Form (10+ Pages)
- Create form with 10+ pages
- Navigate through all pages
- ✅ Expected: Navigation buttons work on all pages
- Submit and verify reset
- ✅ Expected: Returns to page 1 correctly

### Test 7.5: Rapid Clicking
- Fill form and click Submit rapidly 5 times
- ✅ Expected: Only 1 submission occurs
- ✅ Expected: Button disabled prevents duplicate submissions
- ✅ Expected: No errors in console

---

## Automated Testing Script

For developers, run the following to verify builds:

```bash
# 1. Install dependencies
npm install

# 2. Build blocks
npm run build

# 3. Verify JavaScript syntax
node -c assets/js/eipsi-forms.js

# 4. Run linter
npm run lint:js assets/js/eipsi-forms.js

# 5. Check compiled CSS
grep -q "vas-slider-container.label-style-simple" build/style-index.css && echo "✅ VAS CSS compiled" || echo "❌ VAS CSS missing"

# 6. Verify fadeout animation exists
grep -q "form-message--fadeout" assets/css/eipsi-forms.css && echo "✅ Fadeout animation exists" || echo "❌ Fadeout missing"
```

Expected output:
```
✅ VAS CSS compiled
✅ Fadeout animation exists
```

---

## Rollback Plan

If critical issues are found in production:

1. **Immediate**: Deactivate plugin or revert to previous version
2. **Identify**: Check browser console for JavaScript errors
3. **Verify**: Test in staging environment first
4. **Report**: Document exact reproduction steps

### Common Issues & Solutions

**Issue**: Radio fields not deselecting
- **Check**: Browser console for errors
- **Verify**: `eipsi-forms.js` loaded correctly
- **Test**: Different radio groups separately

**Issue**: Navigation buttons incorrect
- **Check**: Form has `data-allow-backwards-nav` attribute
- **Verify**: Current page field exists and is correct
- **Test**: Single-page form first

**Issue**: VAS styles not working
- **Check**: Browser cache cleared
- **Verify**: `build/style-index.css` exists and is loaded
- **Test**: Hard refresh (Ctrl+Shift+R)

**Issue**: Form not resetting
- **Check**: Console for errors in `ConditionalNavigator.reset()`
- **Verify**: Success message appears
- **Test**: Simple form without conditional logic

---

## Success Criteria Summary

All tests must pass before deployment:

- ✅ Radio Fields: All groups work, deselection functional
- ✅ Navigation: Correct buttons on each page type
- ✅ VAS Labels: All styles and alignments work
- ✅ Post-Submission: Correct timing (3s reset, 5s dismiss)
- ✅ Cross-Browser: Works in Chrome, Firefox, Safari, Edge
- ✅ Mobile: Works on iOS and Android devices
- ✅ Accessibility: WCAG AA compliant, keyboard accessible
- ✅ Edge Cases: Single-page, conditional, rapid clicks all handled

---

## Documentation

After successful testing:

1. Update plugin changelog
2. Document any discovered edge cases
3. Create user guide with screenshots
4. Notify stakeholders of deployment

---

**Testing Date**: _____________
**Tester Name**: _____________
**Browser/Device**: _____________
**Result**: ✅ PASS / ❌ FAIL
**Notes**: _____________

---

Generated: 2025-01-XX
Plugin: EIPSI Forms (vas-dinamico-forms)
Version: 1.2.0+
Branch: fix/forms-radio-nav-toggle-vas-post-submit-ux
