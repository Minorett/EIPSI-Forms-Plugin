# Manual Test Plan - Field Color Normalization

**Branch**: `feat/forms/normalize-field-colors`  
**Issues**: #20, #21

## Automated Tests ✅ PASSED

- [x] Build: `npm run build` - webpack compiled successfully
- [x] WCAG AA: `node wcag-contrast-validation.js` - All 4 presets pass
- [x] JavaScript Linting: Modified files pass without errors

## Manual Tests Required

### 1. Editor - Style Panel Theming

**Test Steps**:
1. Create a new page in WordPress
2. Add an EIPSI Form Container block
3. Add text input, textarea, and select field blocks inside
4. Open Inspector → "Style Customization" panel
5. Change Primary Color to bright red (#ff0000)
6. Verify select dropdown caret changes to red
7. Change to each of the 4 presets:
   - Clinical Blue
   - Minimal White
   - Warm Neutral
   - High Contrast
8. For each preset, verify all field colors update

**Expected**: All fields (inputs, textareas, selects, radio, checkbox) respond to theme changes including:
- Border colors
- Background colors
- Icon/caret colors
- Hover states
- Focus states

### 2. Frontend - Error State Display

**Test Steps**:
1. Create a form with:
   - Required text input
   - Required textarea
   - Required select dropdown
   - Required radio buttons
   - Required checkboxes
2. Publish page and view on frontend
3. Submit form without filling fields
4. Verify error states show:
   - Red border on all invalid fields
   - Light red background (#fff5f5 by default)
   - Error messages below fields
5. Focus on an error field
6. Verify error focus shadow appears (subtle red glow)

**Expected**: All field types show consistent error treatment

### 3. Frontend - Select Dropdown Icon

**Test Steps**:
1. Create form with select dropdown
2. View on frontend
3. Verify down-pointing chevron/caret is visible
4. In Style Customization panel, change primary color
5. Reload frontend
6. Verify caret color matches new primary color

**Expected**: Select caret responds to theme changes

### 4. Responsive Behavior

**Test Breakpoints**: 320px, 375px, 768px, 1024px, 1280px

**Test Steps**:
1. Open form on frontend
2. Use browser dev tools responsive mode
3. Test each breakpoint
4. Verify:
   - Error backgrounds visible at all sizes
   - Select caret visible at all sizes
   - No color/contrast issues
   - No horizontal scrolling

**Expected**: All error states and icons remain visible and functional

### 5. Cross-Browser Testing

**Browsers**: Chrome, Firefox, Safari, Edge

**Test Steps**:
1. View form with error states in each browser
2. Verify:
   - Error backgrounds render correctly
   - Select dropdown caret displays properly
   - Colors match across browsers
   - CSS variables work (gradients for caret)

**Expected**: Consistent appearance across all modern browsers

### 6. Theme Override Testing

**Test Steps**:
1. Add to active theme's `style.css`:
```css
.vas-dinamico-form {
    --eipsi-color-input-error-bg: #ffe0e0 !important;
    --eipsi-color-input-icon: #8b4513 !important;
    --eipsi-shadow-error: 0 0 0 4px rgba(255, 0, 0, 0.2) !important;
}
```
2. View form with validation errors
3. Verify custom colors override defaults:
   - Error background is brighter pink
   - Select caret is brown
   - Error focus shadow is more prominent red

**Expected**: Theme-level CSS variable overrides work correctly

### 7. Legacy Form Compatibility

**Test Steps**:
1. Find an existing form created before this update (if available)
2. Load in editor
3. Verify form displays correctly without updates
4. View on frontend
5. Verify error states work with fallback colors

**Expected**: Existing forms continue to work without migration

## Acceptance Checklist

- [ ] All input types (text, email, number, tel, url, date) show error backgrounds
- [ ] Textarea shows error background and focus shadow
- [ ] Select dropdown shows error background and focus shadow
- [ ] Select dropdown caret visible and responds to theme changes
- [ ] Radio buttons and checkboxes show error states
- [ ] Style Customization panel affects all field types
- [ ] All 4 presets work correctly
- [ ] Responsive behavior correct at all breakpoints
- [ ] Cross-browser compatibility verified
- [ ] WCAG AA contrast maintained (automated test passed)
- [ ] No hardcoded colors remain in error states
- [ ] Theme overrides work as expected
- [ ] Backward compatibility maintained

## Rollback Plan

If issues are discovered:
1. Revert commits on `feat/forms/normalize-field-colors` branch
2. Forms will continue using fallback colors
3. No data loss or breaking changes

## Notes

- Select dropdown uses CSS gradients instead of SVG for caret (CSS variables work)
- Error shadow is subtle by default (rgba 0.15 opacity)
- High Contrast preset uses stronger shadows (rgba 0.3 opacity)
- All changes maintain WCAG AA compliance
