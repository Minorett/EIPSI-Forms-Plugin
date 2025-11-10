# EIPSI Forms - Manual Testing Quick Guide

**Version:** 1.1.0  
**Test Duration:** ~30-45 minutes  
**Tester:** QA / Developer

---

## 🎯 Quick Start

### Prerequisites
- [ ] Clean WordPress 5.8+ installation
- [ ] PHP 7.4+ available
- [ ] Admin access
- [ ] Browser DevTools open (F12)

### Installation (5 minutes)
```bash
# Step 1: Upload plugin
WordPress Admin → Plugins → Add New → Upload Plugin
Select: eipsi-forms-1.1.0.zip
Click: Install Now

# Step 2: Activate
Click: Activate Plugin

# Step 3: Verify
Check: "VAS Forms" menu appears in sidebar
Check: No errors in WordPress debug.log
```

---

## 🧪 Critical Tests (Must Pass)

### Test 1: Block Editor Integration (3 min)
```bash
1. Create new Page
2. Click (+) to add block
3. Search "EIPSI"
4. Verify all 11 blocks appear:
   ✓ EIPSI Form Container
   ✓ EIPSI Página
   ✓ EIPSI Campo Texto
   ✓ EIPSI Campo Textarea
   ✓ EIPSI Campo Select
   ✓ EIPSI Campo Radio
   ✓ EIPSI Campo Multiple
   ✓ EIPSI Campo Likert
   ✓ EIPSI VAS Slider
   ✓ EIPSI Campo Descripción
   ✓ EIPSI Form Block (hidden)
```

**Pass Criteria:**
- [ ] All blocks listed under "EIPSI Forms" category
- [ ] Blocks insert without errors
- [ ] No console errors (F12 → Console)

---

### Test 2: Simple Form Creation (5 min)
```bash
1. Add "EIPSI Form Container"
2. Inside, add:
   - Campo Texto: "Name" (required)
   - Campo Select: "Gender" (Male/Female/Other)
   - Campo Radio: "Age Range" (18-25, 26-35, 36-45, 45+)
3. Configure each field in Inspector sidebar
4. Save and Publish
```

**Pass Criteria:**
- [ ] Fields render in editor
- [ ] Inspector settings appear
- [ ] Save succeeds without errors
- [ ] Page loads in frontend

---

### Test 3: Multi-Page Form (5 min)
```bash
1. Edit the form
2. Add 3 "EIPSI Página" blocks
3. Add different fields to each page:
   - Page 1: Campo Texto, Campo Select
   - Page 2: Campo Likert, Campo Radio
   - Page 3: VAS Slider, Campo Textarea
4. Save and Publish
5. View frontend
```

**Pass Criteria:**
- [ ] Only Page 1 visible initially
- [ ] "Next" button appears
- [ ] Clicking Next shows Page 2
- [ ] "Prev" button works
- [ ] Navigation counter correct (e.g., "Página 2 de 3")

---

### Test 4: Conditional Logic (7 min)
```bash
1. Edit form
2. Select the "Gender" select field
3. In Inspector, find "Lógica Condicional"
4. Toggle ON: "Habilitar lógica condicional"
5. Add rule:
   - If value = "Male" → Go to Page 2
   - If value = "Female" → Go to Page 3
6. Default action: Next Page
7. Save and Publish

8. Frontend test:
   - Select "Male" → Click Next → Should go to Page 2
   - Reload → Select "Female" → Click Next → Should go to Page 3
```

**Pass Criteria:**
- [ ] Lightning bolt (⚡) appears on field in editor
- [ ] Rules save correctly
- [ ] Frontend skips pages correctly
- [ ] No console errors

---

### Test 5: Style Customization (5 min)
```bash
1. Select Form Container block
2. Open Inspector → "Personalización del Formulario"
3. Apply preset: "Clinical Blue"
4. Verify colors change in editor preview
5. Change:
   - Primary color to #005a87 (EIPSI blue)
   - Font size to 18px
   - Border radius to 8px
6. Save and view frontend
```

**Pass Criteria:**
- [ ] 4 presets available (Clinical Blue, Minimal White, Warm Neutral, High Contrast)
- [ ] Changes apply instantly in editor
- [ ] Frontend matches editor
- [ ] WCAG contrast warnings appear if contrast < 4.5:1

---

### Test 6: Form Submission (5 min)
```bash
1. Frontend: Fill out complete form
2. Open DevTools → Network tab
3. Click "Enviar" (Submit)
4. Watch network request to admin-ajax.php
5. Verify success message appears
```

**Pass Criteria:**
- [ ] Required field validation works
- [ ] "Enviando..." loading state appears
- [ ] AJAX request succeeds (status 200)
- [ ] Success message shows
- [ ] No console errors

---

### Test 7: Admin Data View (3 min)
```bash
1. Go to WordPress Admin → VAS Forms
2. Verify response table shows submitted data
3. Click "View" on a response
4. Verify all fields present
5. Check metadata: IP, device, browser, duration
```

**Pass Criteria:**
- [ ] Response appears in table
- [ ] All form fields visible
- [ ] Metadata captured correctly
- [ ] Timestamp accurate

---

### Test 8: Data Export (3 min)
```bash
1. In VAS Forms admin page
2. Click "Export to CSV"
3. Download and open in spreadsheet
4. Click "Export to Excel"
5. Download and open in Excel/LibreOffice
```

**Pass Criteria:**
- [ ] CSV downloads successfully
- [ ] CSV has correct headers and data
- [ ] Excel (.xlsx) downloads successfully
- [ ] Excel opens correctly
- [ ] All fields present as columns

---

## 🔍 Console & Network Inspection

### Browser Console (Must Be Clean)
```bash
F12 → Console Tab
```

**PASS:** No red errors  
**ACCEPTABLE:** Yellow warnings for third-party scripts  
**FAIL:** JavaScript errors related to eipsi-forms

### Network Tab (Check 404s)
```bash
F12 → Network Tab → Reload page
```

**PASS:** All eipsi-forms assets load (200 status)  
**FAIL:** Any 404 errors for CSS/JS files

### Critical Assets to Verify
- ✓ `eipsi-forms.css` (200)
- ✓ `eipsi-forms.js` (200)
- ✓ `eipsi-tracking.js` (200)
- ✓ `build/index.js` (200)
- ✓ `build/style-index.css` (200)

---

## 📱 Responsive Testing (Optional but Recommended)

### Breakpoints
```bash
F12 → Toggle Device Toolbar (Ctrl+Shift+M)
```

Test at:
- [ ] 320px (ultra-small phone)
- [ ] 375px (iPhone SE)
- [ ] 768px (tablet)
- [ ] 1024px (small desktop)

**Pass Criteria:**
- [ ] No horizontal scrolling
- [ ] Text readable (min 16px)
- [ ] Buttons touchable (44x44px)
- [ ] Navigation buttons stack on mobile
- [ ] Forms fully functional

---

## ♿ Accessibility Quick Check

### Keyboard Navigation
```bash
1. Tab through form
2. Verify all fields reachable
3. Check focus indicators visible (2px outline)
4. Press Enter on buttons
```

**Pass Criteria:**
- [ ] Logical tab order
- [ ] Focus outlines visible
- [ ] No keyboard traps
- [ ] Submit works with Enter key

### Color Contrast
```bash
Use FormStylePanel in editor:
1. Change colors
2. Check for contrast warnings
3. Verify warnings at < 4.5:1 ratio
```

**Pass Criteria:**
- [ ] Default colors pass WCAG AA (4.5:1)
- [ ] Panel warns on low contrast
- [ ] Error/success colors distinguishable

---

## 🚨 Failure Scenarios

### If Activation Fails
```bash
Check:
1. PHP version ≥ 7.4?
2. WordPress version ≥ 5.8?
3. Conflicting plugin active?
4. Check wp-content/debug.log for errors
```

### If Blocks Don't Appear
```bash
Check:
1. Is plugin activated?
2. Is Gutenberg/block editor active?
3. Browser console for JS errors?
4. Try Classic Editor → switch to Block Editor
```

### If Form Doesn't Submit
```bash
Check:
1. Required fields filled?
2. Console for JavaScript errors?
3. Network tab: admin-ajax.php returns 200?
4. Server error logs?
5. AJAX enabled on site?
```

### If Conditional Logic Fails
```bash
Check:
1. Rules configured correctly?
2. Target page exists?
3. Console for "ConditionalNavigator" errors?
4. Default action set?
```

---

## 📋 Test Report Template

```markdown
# EIPSI Forms Manual Test Report

**Tester:** [Your Name]
**Date:** [YYYY-MM-DD]
**Environment:**
- WordPress: [version]
- PHP: [version]
- Browser: [Chrome/Firefox/Safari version]
- Device: [Desktop/Mobile]

## Test Results

### ✅ Passed Tests
- [ ] Block editor integration
- [ ] Simple form creation
- [ ] Multi-page form
- [ ] Conditional logic
- [ ] Style customization
- [ ] Form submission
- [ ] Admin data view
- [ ] Data export

### ❌ Failed Tests
- [ ] [Test name]: [Description of failure]

### ⚠️ Warnings/Issues
- [Issue 1 description]
- [Issue 2 description]

### Console Errors
```
[Paste any console errors here]
```

### Screenshots
- [Attach relevant screenshots]

## Final Verdict
[ ] ✅ APPROVED - Ready for production
[ ] ⚠️  APPROVED WITH NOTES - Minor issues, not blockers
[ ] ❌ REJECTED - Critical issues found

**Notes:** [Additional comments]

**Signature:** [Your Name]
```

---

## 🎓 Tips for Efficient Testing

### 1. Use Browser Profiles
- Create clean browser profile
- No extensions (avoid conflicts)
- Fresh cache

### 2. Keep DevTools Open
- Console: Catch JavaScript errors
- Network: Check 404s and AJAX
- Elements: Inspect applied styles

### 3. Test Edge Cases
- Very long form titles
- Special characters in responses
- Empty form submissions (should fail validation)
- Navigate back/forward rapidly

### 4. Document Issues
- Screenshot the issue
- Note browser and version
- Copy console errors
- Describe steps to reproduce

---

## ⏱️ Time Estimates

| Test | Duration |
|------|----------|
| Installation | 5 min |
| Block integration | 3 min |
| Simple form | 5 min |
| Multi-page form | 5 min |
| Conditional logic | 7 min |
| Style customization | 5 min |
| Form submission | 5 min |
| Admin data view | 3 min |
| Data export | 3 min |
| **TOTAL CORE TESTS** | **41 min** |
| Responsive testing | +10 min |
| Accessibility check | +5 min |
| **TOTAL COMPREHENSIVE** | **~56 min** |

---

## 📞 Support

**If you encounter issues:**
1. Check `INSTALLATION_VALIDATION_REPORT.md` for known issues
2. Review WordPress debug.log
3. Check browser console for JS errors
4. Verify system requirements (WP 5.8+, PHP 7.4+)
5. Document issue with screenshots and steps to reproduce

---

**Document Version:** 1.0  
**Last Updated:** 2025-11-10  
**For Plugin Version:** 1.1.0
