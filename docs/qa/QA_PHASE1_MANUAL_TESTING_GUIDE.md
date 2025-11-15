# QA Phase 1: Manual Testing Guide
# Core Interactivity - Participant-Facing Components

**Document Version:** 1.0  
**Test Date:** 2025-11-15  
**Plugin Version:** 1.2.1  
**Test Branch:** qa/test-core-interactivity

---

## Table of Contents

1. [Environment Setup](#environment-setup)
2. [Testing Methodology](#testing-methodology)
3. [Component Test Checklists](#component-test-checklists)
4. [Cross-Browser Testing Matrix](#cross-browser-testing-matrix)
5. [Device Testing Matrix](#device-testing-matrix)
6. [Accessibility Testing](#accessibility-testing)
7. [Bug Reporting Template](#bug-reporting-template)

---

## Environment Setup

### Prerequisites

- **WordPress Version:** 6.7 or higher
- **PHP Version:** 7.4 or higher
- **Browser DevTools:** Installed and accessible
- **Test Devices:** At least one desktop, one tablet, one smartphone

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd vas-dinamico-forms
   git checkout qa/test-core-interactivity
   ```

2. **Build the plugin:**
   ```bash
   npm install
   npm run build
   ```

3. **Install in WordPress:**
   - Copy the plugin folder to `/wp-content/plugins/vas-dinamico-forms`
   - Activate via WordPress admin

4. **Create test form:**
   - Create a new page in WordPress
   - Add the "EIPSI Form Container" block
   - Add test fields for each component type (see below)

---

## Testing Methodology

### Test Approach

Each component should be tested across three interaction methods:

1. **Mouse/Trackpad** - Point-and-click interactions
2. **Touch** - Mobile/tablet touchscreen interactions
3. **Keyboard** - Keyboard-only navigation and input

### Recording Results

For each test:
- ✅ **PASS** - Feature works as expected
- ❌ **FAIL** - Feature does not work or has critical issues
- ⚠️ **WARN** - Feature works but has minor issues or edge cases
- 🔍 **NOTE** - Observation or recommendation

### Console Monitoring

Keep browser console open during all tests:
```javascript
// Expected: No JavaScript errors
// Watch for warnings about:
// - Missing elements
// - Failed validations
// - Event listener issues
```

---

## Component Test Checklists

### 1. Likert Scale Block

**Test Form Setup:**
```
Add Campo Likert block with:
- Label: "How satisfied are you with this service?"
- Scale: 5 points (1-5)
- Labels: "Very Dissatisfied" to "Very Satisfied"
- Required: Yes
```

#### Test 1.1: Rendering & Visual Feedback

| Test | Mouse | Touch | Keyboard | Notes |
|------|-------|-------|----------|-------|
| All 5 options visible | ☐ | ☐ | ☐ | |
| Labels readable and aligned | ☐ | ☐ | ☐ | |
| Hover state shows background change | ☐ | N/A | N/A | |
| Focus state shows outline (keyboard) | N/A | N/A | ☐ | Should be 2px blue |
| Selected state shows different styling | ☐ | ☐ | ☐ | Blue background |

#### Test 1.2: Selection Behavior

| Test | Mouse | Touch | Keyboard | Notes |
|------|-------|-------|----------|-------|
| Click/tap selects option | ☐ | ☐ | ☐ | |
| Only one option selected at a time | ☐ | ☐ | ☐ | Radio behavior |
| Can change selection | ☐ | ☐ | ☐ | |
| Selection persists when navigating away and back | ☐ | ☐ | ☐ | Multi-page forms |

#### Test 1.3: Keyboard Navigation

| Test | Result | Notes |
|------|--------|-------|
| Tab key moves to Likert field | ☐ | Focus should land on first option |
| Left Arrow selects previous option | ☐ | |
| Right Arrow selects next option | ☐ | |
| Space/Enter confirms selection | ☐ | |
| Focus indicator clearly visible | ☐ | 2px outline minimum |

#### Test 1.4: Validation

| Test | Result | Notes |
|------|--------|-------|
| Required field shows error if empty on submit | ☐ | |
| Error message displays clearly | ☐ | "This field is required" |
| Error state removes when option selected | ☐ | |
| Focus moves to error on validation fail | ☐ | |

**ARIA Attributes to Verify:**
```html
<input type="radio" 
       role="radio"
       aria-checked="true|false"
       aria-required="true"
       name="likert-field-{id}">
```

---

### 2. VAS Slider Block

**Test Form Setup:**
```
Add VAS Slider block with:
- Label: "Rate your current pain level"
- Min: 0 (label: "No Pain")
- Max: 100 (label: "Worst Pain")
- Step: 1
- Show Value: Yes
- Required: Yes
```

#### Test 2.1: Rendering & Visual Elements

| Test | Result | Notes |
|------|--------|-------|
| Slider track displays correctly | ☐ | 12px height, rounded |
| Slider thumb is visible | ☐ | 32×32px circle, blue gradient |
| Min label ("No Pain") displays | ☐ | Left side or top on mobile |
| Max label ("Worst Pain") displays | ☐ | Right side or bottom on mobile |
| Value readout displays (large number) | ☐ | Below slider, 2.5rem font |

#### Test 2.2: Mouse Interaction

| Test | Result | Notes |
|------|--------|-------|
| Click on track moves thumb to position | ☐ | |
| Drag thumb updates value smoothly | ☐ | requestAnimationFrame throttling |
| Hover on thumb shows scale effect | ☐ | 1.15× scale |
| Value readout updates during drag | ☐ | Max 80ms throttle |
| Release updates final value | ☐ | |

#### Test 2.3: Touch Interaction (Mobile/Tablet)

| Test | Result | Notes |
|------|--------|-------|
| Tap on track moves thumb | ☐ | Pointer events |
| Swipe/drag thumb works smoothly | ☐ | No lag |
| Touch area adequate (min 44×44px) | ☐ | Thumb + padding |
| No conflicts with page scrolling | ☐ | |
| Value updates visible during touch | ☐ | |

#### Test 2.4: Keyboard Interaction

| Test | Result | Notes |
|------|--------|-------|
| Tab key focuses slider | ☐ | |
| Left Arrow decreases value by step | ☐ | Default: -1 |
| Right Arrow increases value by step | ☐ | Default: +1 |
| Up Arrow increases value by step | ☐ | |
| Down Arrow decreases value by step | ☐ | |
| Home key jumps to minimum (0) | ☐ | |
| End key jumps to maximum (100) | ☐ | |
| Focus outline visible | ☐ | 2px blue, 4px offset |

#### Test 2.5: Value Precision

| Test | Result | Notes |
|------|--------|-------|
| Fractional values work (step=0.1) | ☐ | If configured |
| Value rounds correctly | ☐ | No floating point errors |
| Value stays within min/max bounds | ☐ | |

#### Test 2.6: Validation

| Test | Result | Notes |
|------|--------|-------|
| Required slider marked on first touch | ☐ | data-touched="true" |
| Validation triggers after interaction | ☐ | |
| Error state visible if not touched | ☐ | Red border on container |

**ARIA Attributes to Verify:**
```html
<input type="range"
       role="slider"
       aria-valuemin="0"
       aria-valuemax="100"
       aria-valuenow="50"
       aria-labelledby="slider-label">
```

---

### 3. Radio Input Block

**Test Form Setup:**
```
Add Campo Radio block with:
- Label: "What is your preferred contact method?"
- Options: "Email", "Phone", "SMS", "In-person"
- Layout: Vertical list
- Required: Yes
- One option disabled
```

#### Test 3.1: Rendering & States

| Test | Mouse | Touch | Keyboard | Notes |
|------|-------|-------|----------|-------|
| All options visible in list | ☐ | ☐ | ☐ | |
| Hover shows background change | ☐ | N/A | N/A | Slight blue tint |
| Focus-visible outline on keyboard | N/A | N/A | ☐ | 2px blue |
| Checked state shows check mark/fill | ☐ | ☐ | ☐ | |
| Disabled option is grayed out | ☐ | ☐ | ☐ | Opacity 0.6 |
| Disabled option not selectable | ☐ | ☐ | ☐ | |

#### Test 3.2: Selection Behavior

| Test | Result | Notes |
|------|--------|-------|
| Only one option can be selected | ☐ | Radio constraint |
| Selecting new option deselects previous | ☐ | |
| Selection persists across page navigation | ☐ | Multi-page forms |
| Click on label selects radio | ☐ | Label-for association |

#### Test 3.3: Keyboard Navigation

| Test | Result | Notes |
|------|--------|-------|
| Tab moves to radio group | ☐ | Focus on first radio |
| Arrow keys move between options | ☐ | Up/Down or Left/Right |
| Space selects focused option | ☐ | |
| Tab moves out of group | ☐ | To next field |

#### Test 3.4: Touch Targets

| Test | Result | Notes |
|------|--------|-------|
| List item clickable area ≥44px height | ☐ | WCAG AAA |
| Touch doesn't require precise tap | ☐ | Full li element clickable |
| No accidental selections | ☐ | |

#### Test 3.5: Validation

| Test | Result | Notes |
|------|--------|-------|
| Required field error on empty submit | ☐ | |
| Error clears when option selected | ☐ | |
| Error message descriptive | ☐ | |

**HTML Structure to Verify:**
```html
<ul class="radio-list">
  <li>
    <input type="radio" id="option-1" name="field-x" value="Email">
    <label for="option-1">Email</label>
  </li>
</ul>
```

---

### 4. Text Input Block

**Test Form Setup:**
```
Add Campo Texto block with:
- Label: "Full Name"
- Placeholder: "Enter your full name"
- Required: Yes
- Max Length: 100

Add Campo Textarea block with:
- Label: "Comments"
- Placeholder: "Please share any additional thoughts"
- Required: No
- Rows: 5
- Max Length: 500
```

#### Test 4.1: Rendering & States

| Test | Mouse | Touch | Keyboard | Notes |
|------|-------|-------|----------|-------|
| Label displays above input | ☐ | ☐ | ☐ | |
| Placeholder visible when empty | ☐ | ☐ | ☐ | |
| Required indicator (asterisk) visible | ☐ | ☐ | ☐ | |
| Border thickness correct (1px default) | ☐ | ☐ | ☐ | |

#### Test 4.2: Focus States

| Test | Result | Notes |
|------|--------|-------|
| Click/tap focuses input | ☐ | |
| Focus shows blue border | ☐ | 2px, #005a87 |
| Focus shows subtle shadow | ☐ | Box-shadow |
| Blur removes focus styles | ☐ | |
| Focus outline on keyboard (Tab) | ☐ | 2px outline, 2px offset |

#### Test 4.3: Input Behavior

| Test | Result | Notes |
|------|--------|-------|
| Can type characters | ☐ | |
| Copy/paste works | ☐ | |
| Character counter works (if enabled) | ☐ | Shows remaining chars |
| Max length enforced | ☐ | Can't type beyond limit |
| Textarea expands vertically | ☐ | Auto-resize if enabled |

#### Test 4.4: Validation - Required Field

| Test | Result | Notes |
|------|--------|-------|
| Submit with empty required field shows error | ☐ | |
| Error message displays below field | ☐ | "This field is required" |
| Error border shows (red, 2px) | ☐ | |
| Background changes to error color | ☐ | #fff5f5 |
| Error icon displays (if implemented) | ☐ | |

#### Test 4.5: Validation - Blur Validation

| Test | Result | Notes |
|------|--------|-------|
| Leave empty required field (blur) shows error | ☐ | If validateOnBlur enabled |
| Error clears when text entered | ☐ | |
| Focus returns on validation error | ☐ | |

#### Test 4.6: Validation - HTML5 Patterns

| Test | Result | Notes |
|------|--------|-------|
| Email validation (if type="email") | ☐ | Invalid format shows error |
| URL validation (if type="url") | ☐ | |
| Pattern attribute enforced | ☐ | Regex validation |
| Browser native errors display | ☐ | |

#### Test 4.7: Accessibility

| Test | Result | Notes |
|------|--------|-------|
| Label has for="input-id" | ☐ | |
| Input has aria-required="true" | ☐ | If required |
| Input has aria-invalid="true" on error | ☐ | |
| Error message has role="alert" | ☐ | Screen reader announcement |

**HTML Structure to Verify:**
```html
<div class="eipsi-text-field">
  <label for="field-id">Full Name <span class="required">*</span></label>
  <input type="text" 
         id="field-id" 
         name="field-id"
         placeholder="Enter your full name"
         required
         maxlength="100"
         aria-required="true"
         aria-describedby="field-id-error">
  <div class="error-message" id="field-id-error" role="alert">
    This field is required
  </div>
</div>
```

---

### 5. Interactive States Audit

#### Test 5.1: Focus Indicators (Desktop)

| Component | Outline Width | Outline Color | Offset | Pass |
|-----------|---------------|---------------|--------|------|
| Text Input | 2px | #005a87 | 2px | ☐ |
| Textarea | 2px | #005a87 | 2px | ☐ |
| Radio Input | 2px | #005a87 | 2px | ☐ |
| Likert Option | 2px | #005a87 | 4px | ☐ |
| VAS Slider | 2px | #005a87 | 4px | ☐ |
| Button | 2px | #005a87 | 3px | ☐ |

#### Test 5.2: Focus Indicators (Mobile/Tablet)

| Component | Outline Width | Pass | Notes |
|-----------|---------------|------|-------|
| Text Input | 3px | ☐ | Should be thicker than desktop |
| Radio Input | 3px | ☐ | |
| Likert Option | 3px | ☐ | |
| VAS Slider | 3px | ☐ | |

**CSS Rule to Verify:**
```css
@media (max-width: 768px) {
    .vas-dinamico-form *:focus-visible {
        outline-width: 3px;
        outline-offset: 3px;
    }
}
```

#### Test 5.3: Touch Targets (Mobile)

| Component | Minimum Size | Actual Size | Pass |
|-----------|--------------|-------------|------|
| Radio list item | 44×44px | | ☐ |
| Likert option | 44×44px | | ☐ |
| Navigation button | 44×44px | | ☐ |
| VAS slider thumb | 44×44px | | ☐ |

**Measurement Tool:** Browser DevTools > Inspect Element > Computed

#### Test 5.4: Hover States

| Component | Hover Effect | Verified |
|-----------|--------------|----------|
| Text Input | Border color change | ☐ |
| Radio list item | Background change + translateX | ☐ |
| Likert option | Background + border + shadow | ☐ |
| VAS slider thumb | Scale 1.15× | ☐ |
| Button | Background + translateY | ☐ |

#### Test 5.5: Disabled States

| Component | Visual Indicator | Not Clickable | Pass |
|-----------|------------------|---------------|------|
| Text Input | Opacity 0.6, cursor not-allowed | ☐ | ☐ |
| Radio option | Grayed out | ☐ | ☐ |
| Submit button | Gray background | ☐ | ☐ |

---

## Cross-Browser Testing Matrix

### Desktop Browsers

| Test | Chrome | Firefox | Safari | Edge | Notes |
|------|--------|---------|--------|------|-------|
| Likert rendering | ☐ | ☐ | ☐ | ☐ | |
| Likert keyboard nav | ☐ | ☐ | ☐ | ☐ | |
| VAS slider mouse drag | ☐ | ☐ | ☐ | ☐ | |
| VAS slider keyboard | ☐ | ☐ | ☐ | ☐ | |
| Radio selection | ☐ | ☐ | ☐ | ☐ | |
| Text input validation | ☐ | ☐ | ☐ | ☐ | |
| Focus indicators | ☐ | ☐ | ☐ | ☐ | |
| CSS Grid/Flexbox layout | ☐ | ☐ | ☐ | ☐ | |
| Form submission | ☐ | ☐ | ☐ | ☐ | |

**Browser Versions to Test:**
- Chrome: Latest stable
- Firefox: Latest stable
- Safari: Latest stable (macOS)
- Edge: Latest stable

### Mobile Browsers

| Test | Chrome Mobile | Safari iOS | Samsung Internet | Notes |
|------|---------------|------------|------------------|-------|
| Touch interactions | ☐ | ☐ | ☐ | |
| VAS slider swipe | ☐ | ☐ | ☐ | |
| Responsive layout | ☐ | ☐ | ☐ | |
| Virtual keyboard handling | ☐ | ☐ | ☐ | |
| Pinch zoom disabled on inputs | ☐ | ☐ | ☐ | |

---

## Device Testing Matrix

### Desktop

| Test | 1920×1080 | 1366×768 | 1280×720 | Pass |
|------|-----------|----------|----------|------|
| Form container max-width 800px | ☐ | ☐ | ☐ | ☐ |
| All elements visible | ☐ | ☐ | ☐ | ☐ |
| No horizontal scroll | ☐ | ☐ | ☐ | ☐ |

### Tablet

| Test | iPad (768×1024) | iPad Pro (834×1194) | Surface (768×1024) | Pass |
|------|-----------------|---------------------|--------------------|------|
| Likert scales stack | ☐ | ☐ | ☐ | ☐ |
| VAS labels stack | ☐ | ☐ | ☐ | ☐ |
| Touch targets adequate | ☐ | ☐ | ☐ | ☐ |
| Navigation buttons full-width | ☐ | ☐ | ☐ | ☐ |

### Mobile

| Test | iPhone SE (375×667) | iPhone 12 (390×844) | Pixel 5 (393×851) | Galaxy S21 (360×800) |
|------|---------------------|---------------------|-------------------|----------------------|
| All content visible | ☐ | ☐ | ☐ | ☐ |
| No text cutoff | ☐ | ☐ | ☐ | ☐ |
| Buttons full-width | ☐ | ☐ | ☐ | ☐ |
| Form usable | ☐ | ☐ | ☐ | ☐ |

### Ultra-Small Mobile

| Test | 320px width | Pass |
|------|-------------|------|
| Form container scales down | ☐ | ☐ |
| Text remains readable (≥14px) | ☐ | ☐ |
| Touch targets still adequate | ☐ | ☐ |

**Test Breakpoints:**
- 320px (ultra-small)
- 375px (small phone)
- 480px (phone)
- 768px (tablet)
- 1024px (desktop)
- 1280px (large desktop)

---

## Accessibility Testing

### Screen Reader Testing

#### NVDA (Windows) Checklist

| Test | Result | Notes |
|------|--------|-------|
| Form landmark announced | ☐ | `<form>` or role="form" |
| Field labels read before inputs | ☐ | `<label>` association |
| Required fields announced | ☐ | aria-required="true" |
| Radio groups announced | ☐ | "Radio button 1 of 4" |
| Likert options announced | ☐ | |
| VAS slider announces min/max/current | ☐ | aria-valuemin/max/now |
| Error messages announced | ☐ | role="alert" |
| Button labels clear | ☐ | "Next", "Previous", "Submit" |

#### JAWS (Windows) Checklist

| Test | Result | Notes |
|------|--------|-------|
| Same as NVDA tests | ☐ | |
| Form mode activates correctly | ☐ | |

#### VoiceOver (macOS/iOS) Checklist

| Test | Result | Notes |
|------|--------|-------|
| Swipe navigation works | ☐ | iOS |
| Rotor navigation works | ☐ | "Form Controls" item |
| Same semantic checks as NVDA | ☐ | |

### Keyboard-Only Navigation

| Test | Result | Notes |
|------|--------|-------|
| Can navigate entire form with Tab | ☐ | |
| Shift+Tab moves backward | ☐ | |
| All interactive elements reachable | ☐ | No keyboard traps |
| Focus order logical (top to bottom) | ☐ | |
| Submit possible without mouse | ☐ | |
| Can skip navigation (if implemented) | ☐ | Skip links |

### Color Contrast Testing

**Tool:** WCAG Contrast Checker or browser extension

| Element | Foreground | Background | Ratio | Pass (≥4.5:1) |
|---------|------------|------------|-------|---------------|
| Primary text | #2c3e50 | #ffffff | 10.98:1 | ☐ |
| Primary button | #ffffff | #005a87 | 7.47:1 | ☐ |
| Border | #64748b | #ffffff | 4.76:1 | ☐ |
| Error text | #d32f2f | #ffffff | 4.98:1 | ☐ |
| Success text | #198754 | #ffffff | 4.53:1 | ☐ |

**All ratios must meet WCAG AA (4.5:1 for text, 3:1 for UI components).**

### Axe DevTools Audit

Run axe DevTools extension on test page:

| Test | Result | Issues Found |
|------|--------|--------------|
| No critical issues | ☐ | |
| No serious issues | ☐ | |
| Minor issues acceptable | ☐ | |

---

## Bug Reporting Template

Use this template for any bugs found:

```markdown
### Bug #[NUMBER]: [Short Description]

**Severity:** Critical / High / Medium / Low

**Component:** Likert / VAS Slider / Radio / Text Input / Navigation / Other

**Environment:**
- Browser: [e.g., Chrome 120.0.6099.109]
- OS: [e.g., Windows 11]
- Device: [e.g., Desktop / iPhone 12]
- Screen Size: [e.g., 1920×1080]

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Behavior:**
[What should happen]

**Actual Behavior:**
[What actually happens]

**Screenshots/Video:**
[Attach if applicable]

**Console Errors:**
```
[Paste any console errors]
```

**Additional Context:**
[Any other relevant information]

**Workaround:**
[If any temporary fix exists]
```

---

## Test Completion Checklist

### Before Signing Off

- [ ] All component tests completed
- [ ] All browsers tested
- [ ] All device sizes tested
- [ ] Accessibility audit passed
- [ ] No critical or high severity bugs
- [ ] All bugs documented
- [ ] Screenshots/videos captured
- [ ] Test results added to QA_PHASE1_RESULTS.md

### Sign-Off

**Tester Name:** ___________________________  
**Date:** ___________________________  
**Signature:** ___________________________  

**Overall Assessment:** PASS / FAIL / PASS WITH MINOR ISSUES

**Recommendation:** RELEASE / FIX BUGS THEN RELEASE / MAJOR REWORK NEEDED

---

**Document End**
