# Testing Guide - Form UX Enhancements

## Quick Start Testing

Follow these steps to verify all new features work correctly.

---

## 1. Navigation Buttons Test (5 minutes)

### Setup:
1. Log in to WordPress admin
2. Create a new page or post
3. Add an "EIPSI Form Container" block
4. Add at least 3 "Form Page" blocks inside the container
5. Add some fields to each page (text, radio, etc.)
6. Publish the page

### Test Procedure:

#### Test 1A: Default Behavior (Backward Navigation ON)
1. ✅ View the published form
2. ✅ **Page 1**: Verify only "Siguiente" button appears (no "Anterior")
3. ✅ Click "Siguiente" to go to page 2
4. ✅ **Page 2**: Verify both "Anterior" and "Siguiente" appear
5. ✅ Click "Siguiente" to go to page 3
6. ✅ **Page 3**: Verify "Anterior" and "Enviar" appear (no "Siguiente")
7. ✅ Click "Anterior" to go back to page 2
8. ✅ Click "Anterior" again to go back to page 1

**Expected Result**: Navigation buttons appear/hide correctly based on current page.

#### Test 1B: Backward Navigation Disabled
1. ✅ Edit the form in WordPress
2. ✅ Select the Form Container block
3. ✅ In the right sidebar, expand "Navigation Settings"
4. ✅ Toggle "Allow backwards navigation" to OFF
5. ✅ Update/publish the page
6. ✅ View the published form
7. ✅ **Page 1**: Verify only "Siguiente" button appears
8. ✅ Click "Siguiente" to go to page 2
9. ✅ **Page 2**: Verify only "Siguiente" appears (NO "Anterior" button)
10. ✅ Click "Siguiente" to go to page 3
11. ✅ **Page 3**: Verify only "Enviar" appears (NO "Anterior" button)

**Expected Result**: "Anterior" button never appears when setting is disabled.

---

## 2. Enhanced Success Message Test (3 minutes)

### Setup:
Use the form created in Test 1 (make sure it has at least one required field)

### Test Procedure:

#### Test 2A: Success Message
1. ✅ View the published form
2. ✅ Fill in all required fields
3. ✅ Navigate to the last page
4. ✅ Click "Enviar" to submit the form
5. ✅ Verify the success message appears:
   - Green background
   - Checkmark icon (✓)
   - Title: "¡Formulario enviado correctamente!"
   - Subtitle: "Gracias por completar el formulario"
6. ✅ Wait 5 seconds and verify the message auto-dismisses

**Expected Result**: Professional green success message with icon appears and auto-dismisses.

#### Test 2B: Error Message
1. ✅ Refresh the page to load a new form
2. ✅ Leave required fields empty
3. ✅ Navigate to the last page
4. ✅ Click "Enviar" to submit
5. ✅ Verify the error message appears:
   - Red background
   - Warning icon (!)
   - Title: "Por favor, completa todos los campos requeridos."

**Expected Result**: Professional red error message with icon appears (does not auto-dismiss).

#### Test 2C: Mobile Responsive
1. ✅ Open browser DevTools (F12)
2. ✅ Toggle device toolbar (Ctrl+Shift+M)
3. ✅ Set to iPhone SE (375px width)
4. ✅ Submit the form (successfully)
5. ✅ Verify success message is responsive:
   - Icon is smaller but visible
   - Text is readable
   - No horizontal scrolling
6. ✅ Set to Galaxy Fold (320px width)
7. ✅ Submit again and verify compact layout

**Expected Result**: Success message adapts to mobile screen sizes.

---

## 3. Database Indicator Banner Test (2 minutes)

### Test Procedure:

#### Test 3A: WordPress Database (Default)
1. ✅ Log in to WordPress admin
2. ✅ Navigate to "EIPSI Forms > Configuration"
3. ✅ Verify the banner appears at the top of the page
4. ✅ Verify the banner shows:
   - Blue gradient background
   - Large database icon (circular)
   - Label: "CURRENT STORAGE LOCATION:"
   - Badge: "WordPress Database" with WordPress icon
   - Database name in monospace font (e.g., `wp_mysite_db`)
   - NO "Connected" status indicator

**Expected Result**: Banner shows WordPress database information clearly.

#### Test 3B: External Database (If Configured)
1. ✅ Fill in external database credentials
2. ✅ Click "Test Connection"
3. ✅ If successful, click "Save Configuration"
4. ✅ Page reloads - verify the banner now shows:
   - Badge: "External Database" with site icon
   - External database name (e.g., `research_db_custom`)
   - "Connected" status with pulsing green dot
   - "Connected" text in green

**Expected Result**: Banner updates to show external database with connected status.

#### Test 3C: Mobile Responsive
1. ✅ Open browser DevTools (F12)
2. ✅ Toggle device toolbar (Ctrl+Shift+M)
3. ✅ Set to iPad (768px width)
4. ✅ Verify banner stacks vertically (icon, info, status)
5. ✅ Set to iPhone SE (375px width)
6. ✅ Verify compact layout with smaller icon

**Expected Result**: Banner adapts to mobile screen sizes gracefully.

---

## 4. Accessibility Testing (5 minutes)

### Test Procedure:

#### Test 4A: Keyboard Navigation
1. ✅ View a published form
2. ✅ Press TAB to navigate through form fields
3. ✅ Press TAB to reach navigation buttons
4. ✅ Verify focus outlines are visible
5. ✅ Press ENTER on "Siguiente" button
6. ✅ Verify page changes
7. ✅ Submit the form using keyboard only (TAB + ENTER)
8. ✅ Verify success message appears

**Expected Result**: All interactive elements are keyboard accessible.

#### Test 4B: Screen Reader (Optional)
**Requirements**: NVDA (Windows), JAWS (Windows), or VoiceOver (Mac)

1. ✅ Enable screen reader
2. ✅ Navigate to the form
3. ✅ Fill in fields and submit
4. ✅ Verify screen reader announces success message:
   - "Status: ¡Formulario enviado correctamente!"
   - "Gracias por completar el formulario"

**Expected Result**: Screen reader announces form messages correctly.

#### Test 4C: High Contrast Mode (Windows)
1. ✅ Press Alt+Shift+PrtScn to enable High Contrast mode
2. ✅ View the form
3. ✅ Submit the form
4. ✅ Verify success message is still readable
5. ✅ Navigate to Configuration page
6. ✅ Verify database indicator banner is readable

**Expected Result**: All elements remain visible in high contrast mode.

---

## 5. Cross-Browser Testing (10 minutes)

Test in multiple browsers to ensure compatibility.

### Test Procedure:

#### Chrome/Edge (Chromium):
1. ✅ Test navigation buttons
2. ✅ Test success message
3. ✅ Test database indicator

#### Firefox:
1. ✅ Test navigation buttons
2. ✅ Test success message
3. ✅ Test database indicator

#### Safari (Mac/iOS):
1. ✅ Test navigation buttons
2. ✅ Test success message
3. ✅ Test database indicator

**Expected Result**: All features work identically across browsers.

---

## 6. Performance Testing (Optional)

### Test Procedure:

1. ✅ Open browser DevTools (F12)
2. ✅ Go to "Network" tab
3. ✅ Reload the form page
4. ✅ Verify `eipsi-forms.css` loads successfully
5. ✅ Verify `eipsi-forms.js` loads successfully
6. ✅ Go to "Performance" tab
7. ✅ Record page interaction (submit form)
8. ✅ Verify animation runs at 60fps (success message slide-in)

**Expected Result**: No performance degradation from new features.

---

## Bug Reporting Template

If you find any issues, please report using this format:

```
**Bug Title**: [Brief description]

**Steps to Reproduce**:
1. [Step 1]
2. [Step 2]
3. [Step 3]

**Expected Behavior**:
[What should happen]

**Actual Behavior**:
[What actually happened]

**Environment**:
- Browser: [Chrome 120, Firefox 115, etc.]
- Device: [Desktop, iPhone 12, iPad Pro, etc.]
- Screen Size: [1920x1080, 375x667, etc.]
- WordPress Version: [6.4, 6.5, etc.]

**Screenshots**:
[Attach screenshots if relevant]

**Console Errors**:
[Copy any JavaScript errors from browser console]
```

---

## Test Completion Checklist

Once all tests pass, check off each item:

### Functionality:
- [ ] Navigation buttons appear correctly on all pages
- [ ] Backward navigation toggle works when enabled/disabled
- [ ] Success message appears with green background and icon
- [ ] Error message appears with red background and icon
- [ ] Success message auto-dismisses after 5 seconds
- [ ] Database indicator shows WordPress DB by default
- [ ] Database indicator shows External DB when configured
- [ ] Connected status appears with pulsing green dot

### Responsive:
- [ ] Success message responsive on mobile (≤480px)
- [ ] Database indicator responsive on tablet (≤768px)
- [ ] Database indicator responsive on mobile (≤480px)
- [ ] No horizontal scrolling on any screen size

### Accessibility:
- [ ] Keyboard navigation works on all elements
- [ ] Screen reader announces success/error messages
- [ ] High contrast mode is readable
- [ ] Focus outlines are visible
- [ ] ARIA attributes are present

### Cross-Browser:
- [ ] Chrome/Edge: All features work
- [ ] Firefox: All features work
- [ ] Safari: All features work
- [ ] Mobile browsers: All features work

### Performance:
- [ ] Page loads without delay
- [ ] Animations run smoothly (60fps)
- [ ] No JavaScript console errors
- [ ] No CSS rendering issues

---

## Quick Smoke Test (2 minutes)

For rapid verification after deployment:

1. ✅ Create a 3-page form
2. ✅ Navigate through pages (verify buttons)
3. ✅ Toggle backward navigation OFF (verify prev button hides)
4. ✅ Submit form successfully (verify green success message)
5. ✅ Go to Configuration page (verify database indicator)

**If all 5 steps pass**: ✅ Deployment successful!

---

**Last Updated**: January 2025
**Version**: 1.2.0+
**Status**: Ready for Testing
