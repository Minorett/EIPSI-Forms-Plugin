# Style Panel Testing Guide
**Quick Reference for Manual Verification**

## 🎯 Quick Start Testing (5 Minutes)

### Test 1: Basic Functionality
1. Create new EIPSI Form Container block
2. Open **Form Settings** sidebar
3. Scroll to **🎨 Theme Presets** panel
4. Click each preset and verify:
   - Preview colors update immediately
   - Checkmark appears on active preset
5. **Expected:** Live preview reflects theme changes

---

### Test 2: CSS Variable Propagation
1. With form open in editor, adjust **Primary Color** to red (`#ff0000`)
2. Verify editor preview shows red buttons
3. **Save** post
4. **View on frontend** (public page)
5. Right-click form container → **Inspect**
6. Look for inline style attribute: `style="--eipsi-color-primary: #ff0000;"`
7. Verify buttons are red on frontend

**Expected:** CSS variables flow from editor → database → frontend

---

### Test 3: Contrast Warnings
1. In **🎨 Colors** panel, expand **Background & Text**
2. Set **Text** color to `#cccccc` (light gray)
3. Keep **Background** as `#ffffff` (white)
4. **Expected:** Yellow warning box appears:
   ```
   ⚠️ Contrast Warning: Insufficient contrast (1.x:1). 
   Minimum 4.5:1 required for accessibility.
   ```
5. Change **Text** to `#000000` (black)
6. **Expected:** Warning disappears immediately

---

### Test 4: Persistence
1. Apply **Warm Neutral** preset
2. Adjust **Container Padding** to `3rem`
3. **Save** post
4. **Refresh** page in editor
5. **Expected:** Warm brown colors and 3rem padding persist

---

## 🔬 Comprehensive Testing (30 Minutes)

### Panel Section Coverage

#### 🎨 Theme Presets
| Preset | Primary Color | Key Feature |
|--------|---------------|-------------|
| Clinical Blue | `#005a87` | Default EIPSI theme |
| Minimal White | `#2c5aa0` | Clean, distraction-free |
| Warm Neutral | `#8b6f47` | Serif headings, warm tones |
| High Contrast | `#0050d8` | No shadows, black borders |

**Test Steps:**
- [ ] Apply each preset
- [ ] Verify visual preview matches description
- [ ] Check active checkmark appears
- [ ] Verify manual edit clears checkmark

---

#### 🎨 Colors (18 Tokens)
**Test:** Adjust each color and verify live preview

| Category | Test Case |
|----------|-----------|
| Brand Colors | Change primary → Verify buttons update |
| Background & Text | Change background → Verify form bg updates |
| Input Fields | Change input border → Verify field borders update |
| Buttons | Change button bg → Verify button color updates |

**Contrast Validations:**
- [ ] Text on Background (4.5:1 minimum)
- [ ] Button Text on Button BG (4.5:1 minimum)
- [ ] Input Text on Input BG (4.5:1 minimum)

---

#### ✍️ Typography (11 Tokens)
**Test Steps:**
1. Change **Font Family (Heading)** to "Georgia (Serif)"
2. Verify form titles use Georgia
3. Change **Font Size (Base)** to `18px`
4. Verify all text scales up
5. Change **Line Height (Base)** to `2.0`
6. Verify increased spacing between text lines

---

#### 📏 Spacing (8 Tokens)
**Test Steps:**
1. Change **Container Padding** from `2.5rem` to `1rem`
2. Verify form container has less internal padding
3. Change **Field Gap** to `2.5rem`
4. Verify more space between form fields

---

#### 🔲 Borders (6 Tokens)
**Test Steps:**
1. Change **Border Radius (Medium)** to `0px` (sharp corners)
2. Verify form container has square corners
3. Change **Border Radius (Medium)** to `50px` (very round)
4. Verify form container is pill-shaped

---

#### 💫 Shadows (4 Tokens)
**Test Steps:**
1. Change **Shadow (Large)** to a custom value: `0 20px 50px rgba(0,0,0,0.3)`
2. Verify form has deeper, more dramatic shadow
3. Apply **High Contrast** preset
4. Verify shadows disappear (`shadow-*: none`)

---

#### 🎬 Interactions (5 Tokens)
**Test Steps:**
1. Change **Transition Duration** to `1s` (slow)
2. Hover over input field
3. Verify slow fade-in of focus ring
4. Change **Hover Scale** to `1.1`
5. Hover over button (if preview supports)
6. Verify button scales up 10%

---

### Advanced Testing

#### Block Operations
- [ ] **Duplicate Block** (Ctrl+Shift+D) → Verify styleConfig copies independently
- [ ] **Undo** (Ctrl+Z) → Verify style changes revert
- [ ] **Redo** (Ctrl+Shift+Z) → Verify style changes reapply
- [ ] **Copy/Paste Block** → Verify styles persist

#### Legacy Migration
**Pre-Requisite:** Access to plugin version 2.0 or earlier

1. Create form with v2.0 (has `backgroundColor`, `primaryColor` attributes)
2. Upgrade to v2.2
3. Open form in editor
4. Check browser console for migration logs (if implemented)
5. Verify visual appearance unchanged
6. Open Form Settings → Verify styleConfig populated
7. Save form
8. Check post meta → Verify `styleConfig` attribute present

#### Browser Compatibility
Test in:
- [ ] Chrome 90+
- [ ] Firefox 88+
- [ ] Safari 14+
- [ ] Edge 90+

**What to Check:**
- CSS variables render correctly
- Color pickers functional
- Range sliders work
- No JavaScript errors in console

---

## 🐛 Bug Reporting Template

If issues are found, document using this template:

```markdown
## Issue: [Brief Description]

**Severity:** Critical / High / Medium / Low

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Screenshots:**
[Attach if applicable]

**Browser/Environment:**
- Browser: Chrome 120
- WordPress: 6.4
- Plugin Version: 2.2
- Theme: Twenty Twenty-Four

**Console Errors:**
```
[Paste any console errors]
```

**Additional Context:**
Any other relevant information
```

---

## ✅ Acceptance Criteria Checklist

Based on ticket requirements:

### Core Functionality
- [ ] Style adjustments persist across save/refresh
- [ ] CSS variables propagate to frontend rendering
- [ ] Generated CSS matches editor selections
- [ ] Block attributes update correctly

### UX & Presets
- [ ] All 4 presets apply correctly
- [ ] Active preset indicator (checkmark) works
- [ ] Manual edits clear preset indicator
- [ ] Panel sections collapse/expand smoothly

### Accessibility
- [ ] Contrast warnings appear for WCAG AA failures (<4.5:1)
- [ ] Contrast warnings disappear when fixed
- [ ] Multiple warnings can display simultaneously
- [ ] Warning messages are clear and actionable

### Developer Experience
- [ ] No React console warnings
- [ ] No JavaScript errors
- [ ] Block duplication works
- [ ] Undo/redo works
- [ ] Template insertion works

### Migration & Compatibility
- [ ] Legacy forms migrate correctly
- [ ] Migrated forms visually identical
- [ ] No performance degradation
- [ ] Cross-browser compatibility

---

## 📊 Test Results Template

```markdown
# Style Panel Test Results
**Date:** YYYY-MM-DD
**Tester:** [Name]
**Environment:** [Browser/WP Version/Theme]

## Summary
- Total Tests: X
- Passed: X
- Failed: X
- Blocked: X

## Detailed Results

### Test 1: Basic Functionality
**Status:** ✅ PASS / ❌ FAIL / ⏭️ SKIPPED
**Notes:** [Any observations]

### Test 2: CSS Variable Propagation
**Status:** ✅ PASS / ❌ FAIL / ⏭️ SKIPPED
**Notes:** [Any observations]

[... continue for all tests ...]

## Issues Found
1. [Issue #1 description]
2. [Issue #2 description]

## Recommendations
1. [Recommendation #1]
2. [Recommendation #2]
```

---

## 🚀 Quick Verification Commands

### Browser DevTools Checks

**1. Verify CSS Variables in DOM:**
```javascript
// In browser console, with form visible
const form = document.querySelector('.vas-dinamico-form');
console.log(form.style.cssText);
// Should show: --eipsi-color-primary: #005a87; --eipsi-color-...
```

**2. Check Computed Styles:**
```javascript
// In browser console
const input = document.querySelector('.vas-dinamico-form input');
const styles = window.getComputedStyle(input);
console.log('Input BG:', styles.backgroundColor);
console.log('Input Border:', styles.borderColor);
// Should match your styleConfig values
```

**3. Verify Block Attributes:**
1. Select form block in editor
2. Open browser console
3. Run:
```javascript
wp.data.select('core/block-editor').getSelectedBlock().attributes.styleConfig
// Should return full config object with colors, typography, etc.
```

---

## 📞 Support

**Issues with Testing:**
- Check `STYLE_PANEL_AUDIT_REPORT.md` for detailed technical analysis
- Review `src/components/FormStylePanel.js` for implementation details
- Check browser console for React/JavaScript errors

**Expected Console Output:**
- No errors (red text)
- No warnings (yellow text) related to FormStylePanel
- SASS deprecation warnings are expected and non-blocking

---

**Last Updated:** 2024-01-15  
**Document Version:** 1.0  
**Related Files:**
- `STYLE_PANEL_AUDIT_REPORT.md` - Comprehensive technical audit
- `src/components/FormStylePanel.js` - Component implementation
- `src/utils/styleTokens.js` - Token system utilities
