# CSS Clinical Styles - Action Plan

**Status:** 🔴 **Critical Issues Identified**  
**Priority:** High - Affects all frontend form rendering via Gutenberg blocks  
**Timeline:** 4-6 hours for critical fixes

---

## Issue Summary

### The Problem
Block-level SCSS files (10 files) were created before the design token system (v2.1) and never updated to use CSS variables. This creates:

1. **Visual Inconsistency:** Forms look different depending on rendering method
2. **Broken Customization:** Style panel changes don't affect block-rendered forms
3. **Dark Theme Assumption:** White text on transparent backgrounds = invisible on light backgrounds
4. **Wrong Brand Colors:** WordPress blue (#0073aa) instead of EIPSI blue (#005a87)

### The Impact
- ❌ Customization panel is **non-functional** for block-based forms
- ❌ Forms are **illegible** on standard clinical white backgrounds
- ❌ Brand inconsistency with wrong primary color
- ❌ Design token system is **bypassed entirely** by blocks

---

## Phase 1: Critical Block SCSS Migration (4-6 hours)

### Goal
Migrate all 10 block SCSS files to use CSS variables from the design token system.

### Files to Update (Priority Order)

#### 1. Text Input (30 min)
**File:** `src/blocks/campo-texto/style.scss`  
**Lines:** 72  
**Changes:**
- Line 10: `color: #ffffff` → `color: var(--eipsi-color-text, #2c3e50)`
- Line 21: `background: rgba(255, 255, 255, 0.05)` → `background: var(--eipsi-color-input-bg, #ffffff)`
- Line 22: `border: 2px solid rgba(255, 255, 255, 0.15)` → `border: var(--eipsi-border-width-focus, 2px) var(--eipsi-border-style, solid) var(--eipsi-color-input-border, #e2e8f0)`
- Line 24: `color: #ffffff` → `color: var(--eipsi-color-input-text, #2c3e50)`
- Line 35: `border-color: rgba(0, 115, 170, 0.6)` → `border-color: var(--eipsi-color-input-border-focus, #005a87)`
- Line 37: Add `box-shadow: var(--eipsi-shadow-focus, 0 0 0 3px rgba(0, 90, 135, 0.1))`

#### 2. Radio Buttons (30 min)
**File:** `src/blocks/campo-radio/style.scss`  
**Lines:** 54  
**Changes:**
- Line 13: `background: rgba(255, 255, 255, 0.05)` → `background: var(--eipsi-color-background, #ffffff)`
- Line 14: `border: 2px solid rgba(255, 255, 255, 0.1)` → `border: var(--eipsi-border-width-focus, 2px) var(--eipsi-border-style, solid) var(--eipsi-color-border, #e2e8f0)`
- Line 21: `border-color: rgba(0, 115, 170, 0.5)` → `border-color: var(--eipsi-color-primary, #005a87)`
- Line 41: `color: #ffffff` → `color: var(--eipsi-color-text, #2c3e50)`

#### 3. Description Block (20 min)
**File:** `src/blocks/campo-descripcion/style.scss`  
**Lines:** 28  
**Changes:**
- Line 5: `background: rgba(255, 255, 255, 0.05)` → `background: var(--eipsi-color-background-subtle, #f8f9fa)`
- Line 7: `border-left: 3px solid #0073aa` → `border-left: 4px solid var(--eipsi-color-primary, #005a87)`
- Line 8: `color: #e0e0e0` → `color: var(--eipsi-color-text, #2c3e50)`

#### 4. Likert Scale (1 hour)
**File:** `src/blocks/campo-likert/style.scss`  
**Lines:** 172 (most complex)  
**Critical Changes:**
- Lines 5-6: Background/border → CSS variables
- Lines 37-38: Item background/border → CSS variables
- Line 53: Hover border-color → `var(--eipsi-color-primary, #005a87)`
- Line 100-102: Checked state → CSS variables
- Line 113: Text color → `var(--eipsi-color-text, #2c3e50)`
- Line 132: Radio border → `var(--eipsi-color-border-dark, #cbd5e0)`
- Line 146-153: Checked state colors → CSS variables

**Strategy:** Use find/replace for common patterns:
```bash
# Replace all rgba(255, 255, 255, 0.05) with var(--eipsi-color-background-subtle, #f8f9fa)
# Replace all #0073aa with var(--eipsi-color-primary, #005a87)
# Replace all color: #ffffff with color: var(--eipsi-color-text, #2c3e50)
```

#### 5. VAS Slider (1 hour)
**File:** `src/blocks/vas-slider/style.scss`  
**Lines:** 151 (second most complex)  
**Critical Changes:**
- Lines 6-7: Container background/border → CSS variables
- Lines 29, 78: Label text color → `var(--eipsi-color-text, #2c3e50)`
- Lines 32, 74: Label background → `var(--eipsi-color-secondary, #e3f2fd)` or custom
- Line 51, 58, 92: Current value color → `var(--eipsi-color-primary, #005a87)`
- Line 127, 138: Thumb gradient → start with EIPSI blue

#### 6. Page Block (15 min)
**File:** `src/blocks/pagina/style.scss`  
**Lines:** 38  
**Changes:**
- Line 15: `color: #ffffff` → `color: var(--eipsi-color-primary, #005a87)`
- Line 16: `border-bottom: 2px solid rgba(255, 255, 255, 0.2)` → `border-bottom: var(--eipsi-border-width-focus, 2px) solid var(--eipsi-color-border, #e2e8f0)`

#### 7. Form Container (45 min)
**File:** `src/blocks/form-container/style.scss`  
**Lines:** 130  
**Changes:**
- Line 6: Description color → `var(--eipsi-color-text, #2c3e50)`
- Line 26: Border-top → CSS variables
- Lines 44-46: Button background/color/border → CSS variables
- Lines 62, 68: Submit button gradient → start with EIPSI blue
- Lines 89-101: Progress indicator → CSS variables

#### 8. Textarea (25 min)
**File:** `src/blocks/campo-textarea/style.scss`  
**Similar to campo-texto, same patterns**

#### 9. Select (25 min)
**File:** `src/blocks/campo-select/style.scss`  
**Similar to campo-texto, same patterns**

#### 10. Multiple Choice (25 min)
**File:** `src/blocks/campo-multiple/style.scss`  
**Similar to campo-radio, same patterns**

---

## Phase 1 Checklist

### Before Starting
- [ ] Backup current SCSS files
- [ ] Read design token reference: `src/utils/styleTokens.js` lines 28-94
- [ ] Review main stylesheet for patterns: `assets/css/eipsi-forms.css`
- [ ] Set up test form with all block types

### During Migration (Per File)
- [ ] Open file in editor
- [ ] Find all color hex codes: `#[0-9a-fA-F]{3,6}`
- [ ] Replace with CSS variables + fallbacks
- [ ] Find all rgba() values
- [ ] Replace with CSS variables
- [ ] Test compile: `npm run build`
- [ ] Check syntax errors
- [ ] Verify build/style-index.css output

### After Each File
- [ ] Run `npm run build` successfully
- [ ] Check browser DevTools for CSS variable cascade
- [ ] Test on light background (#ffffff)
- [ ] Test on dark background (#2c3e50)
- [ ] Verify no console errors

### After All Files
- [ ] Run full build: `npm run build`
- [ ] Clear browser cache
- [ ] Test customization panel changes
- [ ] Test all 4 presets (Clinical Blue, Minimal White, Warm Neutral, High Contrast)
- [ ] Visual regression testing
- [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsive testing

---

## Phase 2: Main CSS Cleanup (1 hour)

### Goal
Replace remaining hardcoded values in main stylesheet.

### Changes Required

#### 1. Placeholder Colors (5 min)
**File:** `assets/css/eipsi-forms.css`  
**Line:** 342

**Before:**
```css
.eipsi-text-field input::placeholder {
    color: #adb5bd;
}
```

**After:**
```css
.eipsi-text-field input::placeholder {
    color: var(--eipsi-color-text-muted, #64748b);
}
```

#### 2. Textarea Styles (30 min)
**File:** `assets/css/eipsi-forms.css`  
**Lines:** 375-398

**Before:**
```css
.eipsi-textarea-field textarea {
    color: #2c3e50;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    /* ... */
}
```

**After:**
```css
.eipsi-textarea-field textarea {
    color: var(--eipsi-color-input-text, #2c3e50);
    background: var(--eipsi-color-input-bg, #ffffff);
    border: var(--eipsi-border-width-focus, 2px) 
            var(--eipsi-border-style, solid) 
            var(--eipsi-color-input-border, #e2e8f0);
    /* ... */
}
```

#### 3. Select Styles (30 min)
**File:** `assets/css/eipsi-forms.css`  
**Lines:** 417-447

**Before:**
```css
.eipsi-select-field select {
    color: #2c3e50;
    background: #ffffff url(/* ... */);
    border: 2px solid #e2e8f0;
    /* ... */
}
```

**After:**
```css
.eipsi-select-field select {
    color: var(--eipsi-color-input-text, #2c3e50);
    background: var(--eipsi-color-input-bg, #ffffff) url(/* ... */);
    border: var(--eipsi-border-width-focus, 2px) 
            var(--eipsi-border-style, solid) 
            var(--eipsi-color-input-border, #e2e8f0);
    /* ... */
}
```

---

## Phase 3: Testing & Validation (2 hours)

### Manual Testing

#### Test 1: Customization Panel Integration
1. Open WordPress editor
2. Add EIPSI Form Container block
3. Add various field blocks (text, Likert, VAS, radio, etc.)
4. Open FormStylePanel in sidebar
5. Change primary color to red (#dc3545)
6. **Expected:** All form elements update to red
7. Change to green (#28a745)
8. **Expected:** All elements update to green
9. Test all 4 presets
10. **Expected:** Each preset changes all elements

#### Test 2: Light Background Legibility
1. Create new form
2. Set background color to white (#ffffff)
3. Add all field types
4. **Expected:** All text is dark and readable
5. **No white text on white background**

#### Test 3: Dark Background Legibility
1. Create new form
2. Set background color to dark (#2c3e50)
3. Add all field types
4. Adjust text color to light via panel
5. **Expected:** Text remains readable

#### Test 4: Focus States
1. Tab through all form elements
2. **Expected:** Blue outline (2px solid #005a87) on all
3. Test with keyboard only (no mouse)
4. **Expected:** Clear visual indicator always present

#### Test 5: Hover States
1. Hover all buttons
2. **Expected:** Subtle transform, color shift, shadow
3. Hover inputs
4. **Expected:** Border color darkens
5. Hover Likert items
6. **Expected:** Lift effect (translateY(-2px))

#### Test 6: Responsive Mobile
1. Resize browser to 375px width
2. Test all field types
3. **Expected:** Likert stacks vertically
4. **Expected:** Navigation buttons stack
5. **Expected:** Text remains readable

### Automated Testing

```bash
# Color verification
echo "Checking for wrong colors..."
grep -r "#0073aa" src/blocks/*/style.scss && echo "❌ WordPress blue found" || echo "✅ No WordPress blue"
grep -r "color: #ffffff" src/blocks/*/style.scss && echo "❌ White text found" || echo "✅ No white text"

# CSS variable usage
echo "Checking CSS variable usage..."
count=$(grep -r "var(--eipsi-" src/blocks/*/style.scss | wc -l)
echo "CSS variable references: $count"
[ $count -gt 100 ] && echo "✅ Good coverage" || echo "❌ Insufficient coverage"

# Build test
echo "Testing build..."
npm run build && echo "✅ Build successful" || echo "❌ Build failed"

# Compiled output check
echo "Checking compiled colors..."
grep "#0073aa" build/style-index.css && echo "❌ Wrong blue in build" || echo "✅ Correct colors in build"
```

### Browser DevTools Testing

#### Chrome DevTools Console:
```javascript
// Check CSS variable cascade
const form = document.querySelector('.vas-dinamico-form');
const computed = window.getComputedStyle(form);
console.log('Primary color:', computed.getPropertyValue('--eipsi-color-primary'));
console.log('Background:', computed.getPropertyValue('--eipsi-color-background'));

// Verify focus states
document.querySelectorAll('input, button').forEach(el => {
    el.focus();
    const outline = window.getComputedStyle(el, ':focus').outline;
    console.log(el.type, outline);
});

// Check for white text
const whiteText = Array.from(document.querySelectorAll('*')).filter(el => {
    const color = window.getComputedStyle(el).color;
    return color === 'rgb(255, 255, 255)' || color === '#ffffff';
});
console.log('Elements with white text:', whiteText.length);
whiteText.forEach(el => console.log(el.className, el.textContent.slice(0, 50)));
```

---

## Phase 4: Documentation Update (30 min)

### Files to Update
- [ ] Update STYLE_PANEL_AUDIT_REPORT.md (if exists)
- [ ] Update README.md with new design token coverage
- [ ] Update CHANGELOG.md with migration notes
- [ ] Mark this action plan as COMPLETED

### Documentation Checklist
- [ ] Document all CSS variable usage
- [ ] Update design token reference table
- [ ] Add migration notes for future block development
- [ ] Update testing documentation

---

## Timeline Estimate

| Phase | Task | Time | Status |
|-------|------|------|--------|
| **Phase 1** | Block SCSS Migration | 4-6 hours | ⏳ Pending |
| 1.1 | campo-texto | 30 min | ⏳ |
| 1.2 | campo-radio | 30 min | ⏳ |
| 1.3 | campo-descripcion | 20 min | ⏳ |
| 1.4 | campo-likert | 60 min | ⏳ |
| 1.5 | vas-slider | 60 min | ⏳ |
| 1.6 | pagina | 15 min | ⏳ |
| 1.7 | form-container | 45 min | ⏳ |
| 1.8 | campo-textarea | 25 min | ⏳ |
| 1.9 | campo-select | 25 min | ⏳ |
| 1.10 | campo-multiple | 25 min | ⏳ |
| **Phase 2** | Main CSS Cleanup | 1 hour | ⏳ Pending |
| 2.1 | Placeholder colors | 5 min | ⏳ |
| 2.2 | Textarea styles | 30 min | ⏳ |
| 2.3 | Select styles | 30 min | ⏳ |
| **Phase 3** | Testing & Validation | 2 hours | ⏳ Pending |
| 3.1 | Manual testing | 1 hour | ⏳ |
| 3.2 | Automated testing | 30 min | ⏳ |
| 3.3 | Browser testing | 30 min | ⏳ |
| **Phase 4** | Documentation | 30 min | ⏳ Pending |
| **TOTAL** | | **7.5-9.5 hours** | |

---

## Success Criteria

### Completion Checklist
- [ ] All 10 block SCSS files use CSS variables
- [ ] Zero instances of #0073aa in block styles
- [ ] Zero instances of `color: #ffffff` in block styles
- [ ] Zero instances of `rgba(255, 255, 255, 0.XX)` backgrounds
- [ ] `npm run build` completes without errors
- [ ] Customization panel affects all form elements
- [ ] Forms legible on light backgrounds
- [ ] Forms legible on dark backgrounds
- [ ] All 4 presets work correctly
- [ ] Focus states visible on all elements
- [ ] Hover states smooth on all elements
- [ ] Mobile responsive (375px+)
- [ ] Cross-browser tested (Chrome, Firefox, Safari, Edge)
- [ ] No console errors
- [ ] Visual regression tests pass

### Definition of Done
1. ✅ All blocks integrate with design token system
2. ✅ Customization panel fully functional
3. ✅ Clinical design guidelines met (EIPSI Blue #005a87)
4. ✅ Accessibility maintained (focus, hover, reduced motion)
5. ✅ Responsive design preserved
6. ✅ No visual regressions
7. ✅ Documentation updated
8. ✅ Code reviewed and approved

---

## Rollback Plan

### If Issues Arise
1. Git revert to pre-migration commit
2. Review specific block causing issues
3. Test block in isolation
4. Fix and retest before merging back

### Git Strategy
```bash
# Create migration branch
git checkout -b fix/block-scss-design-tokens

# Commit after each file
git add src/blocks/campo-texto/style.scss
git commit -m "fix(styles): migrate campo-texto to CSS variables"

# Test and rebuild
npm run build
# If successful, continue. If not, revert:
git reset --hard HEAD~1

# After all files complete
git push origin fix/block-scss-design-tokens
# Create PR for review
```

---

## Contact & Support

**Primary Reference Documents:**
- Full audit: `CSS_CLINICAL_STYLES_AUDIT_REPORT.md`
- Quick checklist: `CSS_AUDIT_QUICK_CHECKLIST.md`
- Design tokens: `src/utils/styleTokens.js`
- Style presets: `src/utils/stylePresets.js`

**Questions?**
- Review existing EIPSI Forms documentation
- Check `STYLE_PANEL_AUDIT_REPORT.md` for design token patterns
- Reference main CSS (`assets/css/eipsi-forms.css`) for correct implementation

---

**Action Plan Created:** 2024-01-15  
**Status:** 🔴 Ready to Execute  
**Next Action:** Begin Phase 1.1 - campo-texto migration  
**Estimated Completion:** 7.5-9.5 hours from start
