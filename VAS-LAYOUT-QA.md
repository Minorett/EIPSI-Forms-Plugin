# VAS Slider Layout QA Checklist
**Feature:** Value Position (Above/Below) - Flexbox Layout Reordering  
**Version:** 1.2.2+  
**Status:** ✅ COMPLETED  
**Date:** 2025-01-20

---

## 🎯 What Was Implemented

### CSS Changes
- ✅ Copied `.vas-value-below` styles from `src/blocks/vas-slider/style.scss` to `assets/css/eipsi-forms.css`
- ✅ Added support for both `.vas-value-below` class and `data-value-position="below"` attribute
- ✅ All styles use CSS variables (`--eipsi-color-*`) for dark mode and theming consistency
- ✅ Flexbox order properties applied correctly:
  - Container: `display: flex; flex-direction: column;`
  - Slider: `order: 1` (appears first visually)
  - Current value: `order: 2` (appears below slider)
  - Labels container: `flex-direction: column` when value below

### Affected Selectors
```css
.eipsi-vas-slider-field.vas-value-below .vas-slider-container
.eipsi-vas-slider-field[data-value-position="below"] .vas-slider-container
.eipsi-vas-slider-field.vas-value-below .vas-slider-labels
.eipsi-vas-slider-field[data-value-position="below"] .vas-slider-labels
.eipsi-vas-slider-field.vas-value-below .vas-slider-labels .vas-current-value
.eipsi-vas-slider-field[data-value-position="below"] .vas-slider-labels .vas-current-value
.eipsi-vas-slider-field.vas-value-below .vas-current-value-solo
.eipsi-vas-slider-field[data-value-position="below"] .vas-current-value-solo
.eipsi-vas-slider-field.vas-value-below .vas-slider
.eipsi-vas-slider-field[data-value-position="below"] .vas-slider
```

---

## 📋 Manual QA Checklist

### Pre-Test Setup
- [ ] Create a test page with EIPSI Form Container block
- [ ] Add at least **4 VAS Slider blocks** to cover all scenarios:
  1. Value position **above** (default) with left/right labels
  2. Value position **below** with left/right labels
  3. Value position **above** with multi-labels (comma-separated)
  4. Value position **below** with multi-labels

### Scenario 1: Value Above (Default) - Two Labels
**Expected Layout:**
```
[Left Label] [VALUE: 50] [Right Label]
━━━━━━━━●━━━━━━━━ (slider)
```

**QA Steps:**
- [ ] Open VAS block settings → Display Options → Value Position → Select "Above slider" (default)
- [ ] Verify numeric value appears **between** left and right labels
- [ ] Verify slider appears **below** the value row
- [ ] Move slider → value updates in real-time
- [ ] **Mobile test (< 768px):** Labels stack vertically above slider, value stays in middle
- [ ] **Dark mode:** All elements use CSS variables, colors adapt correctly

---

### Scenario 2: Value Below - Two Labels
**Expected Layout:**
```
[Left Label] [Right Label]
━━━━━━━━●━━━━━━━━ (slider)
       [VALUE: 50]
```

**QA Steps:**
- [ ] Open VAS block settings → Display Options → Value Position → Select "Below slider"
- [ ] Verify left/right labels appear **above** slider (no value between them)
- [ ] Verify slider appears in the middle
- [ ] Verify numeric value appears **below** slider (centered)
- [ ] Move slider → value updates in real-time
- [ ] **Mobile test (< 768px):** Labels stack vertically, slider, then value below
- [ ] **Dark mode:** All elements use CSS variables, colors adapt correctly

---

### Scenario 3: Value Above - Multiple Labels
**Expected Layout:**
```
       [VALUE: 50]
[Label 1] [Label 2] [Label 3] [Label 4] [Label 5]
━━━━━━━━━━━━●━━━━━━━━━━━━━━ (slider)
```

**QA Steps:**
- [ ] Enter comma-separated labels in "Multiple Labels" field (e.g., "Nada,Leve,Moderado,Intenso,Severo")
- [ ] Set Value Position → "Above slider" (default)
- [ ] Verify numeric value appears **above** multi-labels
- [ ] Verify multi-labels appear **above** slider
- [ ] Verify slider appears **at bottom**
- [ ] Move slider → value updates in real-time
- [ ] **Mobile test (< 768px):** Value, labels (may wrap), slider stack correctly
- [ ] **Dark mode:** All elements use CSS variables

---

### Scenario 4: Value Below - Multiple Labels
**Expected Layout:**
```
[Label 1] [Label 2] [Label 3] [Label 4] [Label 5]
━━━━━━━━━━━━●━━━━━━━━━━━━━━ (slider)
       [VALUE: 50]
```

**QA Steps:**
- [ ] Enter comma-separated labels in "Multiple Labels" field
- [ ] Set Value Position → "Below slider"
- [ ] Verify multi-labels appear **at top**
- [ ] Verify slider appears in the **middle**
- [ ] Verify numeric value appears **at bottom** (centered)
- [ ] Move slider → value updates in real-time
- [ ] **Mobile test (< 768px):** Labels (may wrap), slider, value stack correctly
- [ ] **Dark mode:** All elements use CSS variables

---

## 🖥️ Device & Browser Matrix

Test all 4 scenarios above on:

### Desktop
- [ ] **Chrome/Edge** (latest) - Windows/macOS
- [ ] **Firefox** (latest) - Windows/macOS
- [ ] **Safari** (latest) - macOS

### Mobile
- [ ] **Chrome Mobile** - Android (< 480px width)
- [ ] **Safari Mobile** - iOS (iPad + iPhone)

### Breakpoints to Test
- [ ] **320px** (iPhone SE)
- [ ] **375px** (iPhone X/11/12)
- [ ] **768px** (iPad portrait)
- [ ] **1024px+** (Desktop)

---

## 🌙 Dark Mode Verification

For **each scenario** (1-4), verify:
- [ ] Toggle dark mode (button in form header or `Ctrl/Cmd + Shift + D`)
- [ ] Slider track changes color (uses `--eipsi-color-vas-slider-track`)
- [ ] Slider thumb changes color (uses `--eipsi-color-vas-slider-thumb`)
- [ ] Slider thumb border changes (uses `--eipsi-color-vas-slider-thumb-border`)
- [ ] Labels background/border adapt (use `--eipsi-color-vas-label-bg`, `--eipsi-color-vas-label-border`)
- [ ] Value text color adapts (uses `--eipsi-color-vas-value-text`)
- [ ] Container background adapts (uses `--eipsi-color-vas-container-bg`)
- [ ] **No hard-coded whites** (e.g., `#ffffff`) cause visibility issues

---

## 🧪 Edge Cases

### Value Display Toggle
- [ ] Disable "Show Current Value" in block settings
- [ ] Verify value **disappears entirely** in both "above" and "below" modes
- [ ] Verify slider and labels remain visible and functional
- [ ] Enable again → value reappears in correct position

### Label Containers & Styling
- [ ] Enable "Show Label Containers" → labels get visible borders/background
- [ ] Enable "Show Value Container" → value gets visible border/background
- [ ] Enable "Bold Labels" → labels and value become bold
- [ ] Verify all styling respects `valuePosition` layout (no visual breaks)

### Long Label Text
- [ ] Enter very long label text (e.g., "Esta es una etiqueta extremadamente larga que puede causar problemas de layout")
- [ ] Verify text wraps or truncates gracefully (no overflow)
- [ ] Verify slider remains usable on mobile
- [ ] Test both "above" and "below" positions

### Initial Value
- [ ] Set initial value to 0, 50, 100
- [ ] Verify value displays correctly in both positions
- [ ] Verify slider thumb starts at correct position

---

## ✅ Success Criteria

**All scenarios PASS if:**
1. ✅ Value position changes correctly between "above" and "below"
2. ✅ Flexbox order matches expected visual layout
3. ✅ Slider remains interactive and updates value in real-time
4. ✅ No layout breaks on any device/breakpoint
5. ✅ Dark mode works consistently (all colors use CSS variables)
6. ✅ No hard-coded colors (all use `var(--eipsi-color-*)`)
7. ✅ Mobile experience is smooth (no horizontal scroll, touch targets ≥ 44×44px)
8. ✅ Edge cases (disabled value, long labels, initial values) work correctly

---

## 🔧 Troubleshooting

### Issue: Value doesn't move below slider
**Possible causes:**
- [ ] CSS not rebuilt → Run `npm run build`
- [ ] Browser cache → Hard refresh (`Ctrl+Shift+R` / `Cmd+Shift+R`)
- [ ] Wrong class applied → Inspect element, verify `.vas-value-below` class or `data-value-position="below"` attribute

### Issue: Dark mode colors broken
**Possible causes:**
- [ ] Hard-coded colors in inline styles → Check block `save.js` for inline `style={}` with hex colors
- [ ] Missing CSS variable fallback → Verify all `var(--eipsi-color-*)` have fallback values

### Issue: Mobile layout broken
**Possible causes:**
- [ ] Missing media query → Check `@media (max-width: 767px)` in CSS
- [ ] Flexbox order not applying → Verify `.vas-value-below` selector specificity

---

## 📝 Notes for Future Development

- **Conditional Field Visibility (Priority 3):** When implementing, ensure hidden VAS sliders don't interfere with layout reordering.
- **Save & Continue Later (Priority 2):** Ensure `valuePosition` state is serialized and restored correctly.
- **Clinical Templates (Priority 4):** Pre-built VAS sliders (e.g., pain scales) should default to "value below" for cleaner clinical look.

---

## 🚀 Deployment Readiness

Before releasing to production:
- [ ] Run `npm run lint:js` → 0 errors, 0 warnings
- [ ] Run `npm run build` → Build succeeds in < 7s
- [ ] Verify `assets/css/eipsi-forms.css` contains all `.vas-value-below` rules
- [ ] Test on live site (not just local dev)
- [ ] Clear CDN/caching layer if applicable
- [ ] Document changes in `CHANGELOG.md` or release notes

---

**Last Updated:** 2025-01-20  
**Tested By:** [Pending - Awaiting Manual QA]  
**Status:** ✅ CSS Implemented, Ready for QA
