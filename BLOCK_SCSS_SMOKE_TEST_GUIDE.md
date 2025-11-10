# Block SCSS Migration - Smoke Test Guide

**Purpose:** Verify that all block styles respond correctly to theme presets after CSS variable migration  
**Estimated Time:** 15-20 minutes  
**Prerequisites:** WordPress site with EIPSI Forms plugin active

---

## Test Form Setup

### 1. Create Test Form
Create a new form in WordPress with the following blocks (in order):

1. **Form Container** (wrapper)
2. **Page 1** with:
   - Description block (any text)
   - Text input field (label: "Full Name", required)
   - Textarea field (label: "Comments")
   - Radio field (label: "How satisfied are you?", options: Very Dissatisfied, Dissatisfied, Neutral, Satisfied, Very Satisfied)
   - Multiple choice field (label: "Which symptoms apply?", options: Anxiety, Depression, Sleep Issues, Other)
3. **Page 2** with:
   - Likert scale (label: "Rate your agreement", 5 options)
   - VAS slider (label: "Pain level", 0-100)
4. **Page 3** with:
   - Select dropdown (label: "Country", options: USA, Canada, UK, Other)

### 2. Configure Form Settings
- Enable "Show progress indicator"
- Set "Submit button text" to "Complete Survey"

---

## Theme Preset Testing

### Test 1: Clinical Blue (Default)
**Objective:** Verify default theme applies correctly to all blocks

1. Open form in **Editor**
2. In Form Style panel, select **"Clinical Blue"** preset
3. **Verify in Editor Preview:**
   - [ ] Description block has light gray background (#f8f9fa)
   - [ ] Description block has blue left border (#005a87)
   - [ ] Text inputs have white background (#ffffff)
   - [ ] Text inputs have light gray border (#e2e8f0)
   - [ ] Radio/checkbox items have white background
   - [ ] Radio/checkbox items have light gray borders
   - [ ] Likert items have light gray background
   - [ ] VAS slider container has light gray background
   - [ ] Navigation buttons have light gray background
   - [ ] Progress indicator shows blue accent (#005a87)

4. **Publish form and view Frontend:**
   - [ ] All editor styles match frontend rendering
   - [ ] Text is dark (#2c3e50), not white
   - [ ] Form is fully legible on light background
   - [ ] Focus states show blue outline (#005a87)
   - [ ] Hover states work correctly

5. **Test Interactions:**
   - [ ] Click text input → blue focus ring appears
   - [ ] Type in text input → dark text visible
   - [ ] Hover over radio item → blue border appears
   - [ ] Select radio item → blue accent appears
   - [ ] Move VAS slider → blue gradient thumb visible
   - [ ] Click "Next" button → button hover shows blue border

### Test 2: Minimal White
**Objective:** Verify custom preset overrides default styles

1. Return to **Editor**
2. In Form Style panel, select **"Minimal White"** preset
3. **Verify Changes Applied:**
   - [ ] All blocks immediately update
   - [ ] Color scheme shifts to minimal aesthetic
   - [ ] Text becomes darker/softer
   - [ ] Borders become more subtle

4. **Frontend Verification:**
   - [ ] Preset changes reflected on frontend
   - [ ] Form maintains legibility
   - [ ] All interactive elements still functional

### Test 3: Warm Neutral
**Objective:** Verify warm color palette applies to blocks

1. Return to **Editor**
2. In Form Style panel, select **"Warm Neutral"** preset
3. **Verify Warm Tones:**
   - [ ] Beige/warm backgrounds visible
   - [ ] Warm accent colors applied
   - [ ] All blocks updated consistently

4. **Frontend Verification:**
   - [ ] Warm palette visible on frontend
   - [ ] Text remains readable
   - [ ] Success/error colors work correctly

### Test 4: High Contrast
**Objective:** Verify accessibility preset with strong contrast

1. Return to **Editor**
2. In Form Style panel, select **"High Contrast"** preset
3. **Verify High Contrast:**
   - [ ] Strong black/white contrast
   - [ ] Bold borders visible
   - [ ] Text very legible
   - [ ] All blocks respond to preset

4. **Frontend Verification:**
   - [ ] High contrast maintained on frontend
   - [ ] Excellent readability
   - [ ] Strong focus indicators

### Test 5: Custom Colors
**Objective:** Verify custom color overrides work

1. Return to **Editor**
2. In Form Style panel, expand **"Advanced Customization"**
3. **Change Primary Color** to purple (#7c3aed)
4. **Verify Custom Color Applied:**
   - [ ] Description block border turns purple
   - [ ] Radio hover states show purple
   - [ ] Checkbox hover states show purple
   - [ ] Likert checked state shows purple
   - [ ] VAS slider thumb gradient uses purple
   - [ ] Navigation button hover shows purple
   - [ ] Progress indicator accent is purple

5. **Change Text Color** to navy (#1e3a8a)
6. **Verify Text Color Applied:**
   - [ ] All label text updates to navy
   - [ ] Description content text updates
   - [ ] Radio/checkbox label text updates
   - [ ] Likert label text updates
   - [ ] Page title text updates

---

## Error State Testing

### Test Error Styling
1. **Leave required text field empty**
2. **Click "Next" button**
3. **Verify Error State:**
   - [ ] Text input border turns red (#d32f2f)
   - [ ] Error message appears below field in red
   - [ ] Error message is legible (contrast ≥ 4.5:1)
   - [ ] Red is dark/serious, not bright/alarming

---

## Responsive Testing

### Mobile View (375px)
1. **Resize browser to 375px wide**
2. **Verify Mobile Layout:**
   - [ ] Form elements stack vertically
   - [ ] Text inputs full width
   - [ ] Radio items remain accessible
   - [ ] Likert items stack vertically
   - [ ] VAS slider labels stack vertically
   - [ ] Navigation buttons stack vertically
   - [ ] Progress indicator centered above buttons
   - [ ] All text readable at mobile size

### Tablet View (768px)
1. **Resize browser to 768px wide**
2. **Verify Tablet Layout:**
   - [ ] Likert items display horizontally
   - [ ] VAS slider labels side-by-side
   - [ ] Navigation buttons side-by-side
   - [ ] Form maintains good spacing

---

## Cross-Browser Testing

### Chrome/Edge
- [ ] All styles render correctly
- [ ] CSS variables work
- [ ] Gradients display correctly

### Firefox
- [ ] All styles render correctly
- [ ] CSS variables work
- [ ] VAS slider styled correctly (moz-range-thumb)

### Safari (if available)
- [ ] All styles render correctly
- [ ] CSS variables work
- [ ] webkit-slider-thumb styles applied

---

## Accessibility Testing

### Keyboard Navigation
1. **Tab through form fields**
2. **Verify Focus Indicators:**
   - [ ] Text input shows blue focus ring
   - [ ] Radio items show focus outline
   - [ ] Checkboxes show focus outline
   - [ ] Likert items show focus outline
   - [ ] VAS slider shows focus outline
   - [ ] Navigation buttons show focus outline
   - [ ] Focus indicators visible and strong

### Screen Reader (Optional)
1. **Enable screen reader (VoiceOver/NVDA/JAWS)**
2. **Navigate form**
3. **Verify:**
   - [ ] Labels announced correctly
   - [ ] Helper text announced
   - [ ] Error messages announced
   - [ ] VAS slider value announced

---

## Background Compatibility Testing

### Light Background
1. **View form on page with white/light background**
2. **Verify:**
   - [ ] All text is dark and readable (not white)
   - [ ] Form elements have visible borders
   - [ ] No elements blend into background
   - [ ] Professional clinical appearance

### Dark Background (if theme supports)
1. **View form on page with dark background**
2. **Verify:**
   - [ ] CSS variables adapt appropriately
   - [ ] Form remains legible
   - [ ] Contrast maintained

---

## Expected Results Summary

### ✅ Pass Criteria
- All 4 theme presets apply to all block types
- Custom colors override defaults correctly
- Text is always dark (#2c3e50) on light backgrounds
- EIPSI blue (#005a87) used for primary color (not WordPress blue #0073aa)
- Error states use accessible red (#d32f2f)
- Forms legible on light backgrounds
- No white text on light backgrounds
- Focus states visible and accessible
- Mobile responsive (320px, 375px, 768px)
- Cross-browser compatible

### ❌ Fail Criteria
- Any block not responding to theme presets
- White text appearing on light backgrounds
- WordPress blue (#0073aa) appearing anywhere
- Bright/saturated error colors (#ff6b6b)
- Forms illegible on light backgrounds
- Missing focus indicators
- Layout breaking at mobile sizes

---

## Troubleshooting

### Issue: Block styles don't change with presets
**Solution:** Clear WordPress cache and browser cache, rebuild with `npm run build`

### Issue: White text on light backgrounds
**Solution:** Verify SCSS migration was complete, check `build/style-index.css` for hardcoded `#ffffff`

### Issue: WordPress blue still appearing
**Solution:** Check for hardcoded `#0073aa` in SCSS files, rebuild

### Issue: Editor preview doesn't match frontend
**Solution:** Check `editor.scss` files (these should use WordPress colors), verify frontend CSS loaded correctly

---

## Reporting Results

### Test Passed
Document successful test with:
- Date and time tested
- WordPress version
- Browser versions tested
- Screenshot of form in each preset

### Test Failed
Document failure with:
- Specific block(s) affected
- Theme preset(s) failing
- Screenshot showing issue
- Browser console errors (if any)
- Expected vs. actual behavior

---

## Quick Pass/Fail Checklist

- [ ] All 8 field blocks respond to Clinical Blue preset
- [ ] All 8 field blocks respond to Minimal White preset
- [ ] All 8 field blocks respond to Warm Neutral preset
- [ ] All 8 field blocks respond to High Contrast preset
- [ ] Custom primary color applies to all blocks
- [ ] Custom text color applies to all blocks
- [ ] Error states styled correctly (dark red)
- [ ] No white text on light backgrounds
- [ ] No WordPress blue (#0073aa) visible
- [ ] EIPSI blue (#005a87) used for primary
- [ ] Forms legible on light backgrounds
- [ ] Focus indicators visible
- [ ] Mobile responsive (375px)
- [ ] Tablet layout correct (768px)
- [ ] Cross-browser compatible

**If all items checked:** ✅ Migration successful, ready for production  
**If any items unchecked:** ⚠️ Review failed items, investigate root cause

---

## Contact

For issues or questions about this smoke test, refer to:
- `BLOCK_SCSS_MIGRATION_REPORT.md` - Technical details
- `MASTER_ISSUES_LIST.md` - Original issues resolved
- `wcag-contrast-validation.js` - Automated contrast validation
