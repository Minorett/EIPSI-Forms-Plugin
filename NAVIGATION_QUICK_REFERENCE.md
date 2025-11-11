# EIPSI Forms - Navigation Quick Reference

Quick lookup for navigation components, functions, and behaviors.

---

## 🔍 Quick Lookup

### Button Visibility Rules

| Condition | Previous | Next | Submit |
|-----------|----------|------|--------|
| First page (Page 1) | ❌ Hidden | ✅ Visible | ❌ Hidden |
| Middle page (2-3) | ✅ Visible | ✅ Visible | ❌ Hidden |
| Last page (Page 4) | ✅ Visible | ❌ Hidden | ✅ Visible |
| Conditional submit triggered | ✅ Visible | ❌ Hidden | ✅ Visible |
| No history (fresh load) | ❌ Hidden | ✅ Visible | ❌ Hidden |

### Validation Behavior

| Scenario | Next Button | Previous Button | Submit Button |
|----------|-------------|-----------------|---------------|
| Required field empty | ❌ Blocked | ✅ Allowed | ❌ Blocked |
| Invalid email format | ❌ Blocked | ✅ Allowed | ❌ Blocked |
| All fields valid | ✅ Allowed | ✅ Allowed | ✅ Allowed |
| Optional field empty | ✅ Allowed | ✅ Allowed | ✅ Allowed |

### Progress Indicator Formats

| Format | Meaning |
|--------|---------|
| `Página 2 de 4` | Page 2 of 4 (linear route) |
| `Página 3 de 3*` | Page 3, estimated total 3 (branched route) |
| `Página 1 de ?` | Initial state (total not yet calculated) |

---

## 📂 File Locations

### Core Files

```
vas-dinamico-forms/
├── src/blocks/form-container/
│   └── save.js                          # Navigation markup (lines 78-108)
│
├── assets/
│   ├── js/
│   │   └── eipsi-forms.js               # Navigation logic
│   │       ├── ConditionalNavigator     # Lines 12-281
│   │       ├── initPagination()         # Lines 627-676
│   │       ├── handlePagination()       # Lines 836-925
│   │       ├── validateCurrentPage()    # Lines 1239-1283
│   │       ├── updatePaginationDisplay()# Lines 962-1041
│   │       ├── submitForm()             # Lines 1393-1456
│   │       ├── scrollToElement()        # Lines 1556-1574
│   │       └── focusFirstInvalidField() # Lines 1496-1554
│   │
│   └── css/
│       └── eipsi-forms.css
│           ├── Navigation buttons       # Lines 992-1090
│           ├── Progress indicator       # Lines 1093-1109
│           ├── Responsive breakpoints   # Lines 1115-1156
│           └── Reduced motion          # Lines 1244-1254
│
└── vas-dinamico-forms.php               # Configuration (lines 318-323)
```

---

## 🎯 Key Functions

### Navigation

```javascript
// Initialize pagination on form
EIPSIForms.initPagination(form)

// Navigate forward or backward
EIPSIForms.handlePagination(form, 'next')
EIPSIForms.handlePagination(form, 'prev')

// Jump to specific page
EIPSIForms.goToPage(form, pageNumber)

// Get current page number
const currentPage = EIPSIForms.getCurrentPage(form) // Returns 1-indexed number
```

### Validation

```javascript
// Validate current page only (returns true/false)
const isValid = EIPSIForms.validateCurrentPage(form)

// Validate entire form (all visited pages)
const isValid = EIPSIForms.validateForm(form)

// Validate single field
const isValid = EIPSIForms.validateField(inputElement)

// Clear field errors
EIPSIForms.clearFieldError(formGroup, inputElement)
```

### Conditional Logic

```javascript
// Get navigator instance for form
const navigator = EIPSIForms.getNavigator(form)

// Calculate next page based on logic
const result = navigator.getNextPage(currentPage)
// Returns: { action: 'nextPage', targetPage: 2 }
//     or:  { action: 'goToPage', targetPage: 5 }
//     or:  { action: 'submit' }

// Check if current page should trigger submit
const shouldSubmit = navigator.shouldSubmit(currentPage) // true/false

// Get navigation history
const history = navigator.history              // [1, 2, 5]
const visitedPages = navigator.visitedPages    // Set {1, 2, 5}
const skippedPages = navigator.skippedPages    // Set {3, 4}
```

---

## 🎨 CSS Classes

### State Classes

```css
/* Button visibility (controlled by JS) */
.eipsi-prev-button { display: none; }         /* Hidden */
.eipsi-prev-button { display: ''; }           /* Visible */

/* Form states */
.form-loading                                  /* During AJAX submission */
.has-error                                     /* Field validation failed */
.error                                         /* Input with error */

/* Progress indicator */
.current-page                                  /* Current page number */
.total-pages                                   /* Total or estimated total */
```

### Styling Hooks

```css
/* Override navigation colors */
.eipsi-prev-button { ... }                    /* Secondary button */
.eipsi-next-button { ... }                    /* Primary button */
.eipsi-submit-button { ... }                  /* Primary emphasized button */
.eipsi-submit-button:disabled { ... }         /* During submission */

/* Override progress indicator */
.form-progress { ... }                         /* Container */
.form-progress .current-page { ... }           /* Bold number */
.form-progress .total-pages { ... }            /* Bold number */
```

---

## ⚙️ Configuration Options

### PHP Filters (vas-dinamico-forms.php)

```php
// Enable/disable auto-scroll
add_filter('vas_dinamico_enable_auto_scroll', '__return_true');  // Default
add_filter('vas_dinamico_enable_auto_scroll', '__return_false'); // Disable

// Scroll offset (pixels from top)
add_filter('vas_dinamico_scroll_offset', function() {
    return 20; // Default
});

// Smooth scroll animation
add_filter('vas_dinamico_smooth_scroll', '__return_true');  // Default
add_filter('vas_dinamico_smooth_scroll', '__return_false'); // Instant

// Validate on blur (real-time validation)
add_filter('vas_dinamico_validate_on_blur', '__return_true');  // Default
add_filter('vas_dinamico_validate_on_blur', '__return_false'); // Submit only
```

### CSS Custom Properties

```css
/* Change button colors */
--eipsi-color-primary: #005a87;               /* Default blue */
--eipsi-color-button-bg: #005a87;
--eipsi-color-button-hover-bg: #003d5b;
--eipsi-color-button-text: #ffffff;

/* Change spacing */
--eipsi-spacing-lg: 2rem;                     /* Gap above navigation */
--eipsi-spacing-md: 1.5rem;                   /* Gap between buttons */

/* Change borders */
--eipsi-border-radius-sm: 8px;                /* Button roundness */
--eipsi-border-width-focus: 2px;              /* Border on buttons */

/* Change transitions */
--eipsi-transition-duration: 0.2s;            /* Animation speed */
--eipsi-transition-timing: ease;              /* Animation curve */
```

---

## 🔗 Data Attributes

### Form Container

```html
<form 
    data-form-id="my-form"                    <!-- Form identifier -->
    data-total-pages="4"                      <!-- Total pages (set by JS) -->
    data-current-page="1"                     <!-- Current page (set by JS) -->
    data-initialized="true"                   <!-- Init flag (set by JS) -->
>
```

### Page Elements

```html
<div 
    class="eipsi-page" 
    data-page="2"                             <!-- Page number (1-indexed) -->
    data-testid="form-page-2"                 <!-- Test selector -->
    aria-hidden="true"                        <!-- Screen reader visibility -->
    inert                                     <!-- Keyboard trap prevention -->
>
```

### Field Elements (Conditional Logic)

```html
<div 
    class="form-group eipsi-select-field"
    data-field-name="experiencia"             <!-- Field identifier -->
    data-field-type="select"                  <!-- Field type -->
    data-required="true"                      <!-- Required flag -->
    data-conditional-logic='{                 <!-- JSON conditional rules -->
        "enabled": true,
        "rules": [
            {
                "id": "rule-1",
                "matchValue": "Nunca",
                "action": "goToPage",
                "targetPage": 4
            }
        ],
        "defaultAction": "nextPage",
        "defaultTargetPage": null
    }'
>
```

---

## 🧪 Test Selectors

### Button Test IDs

```javascript
// Find navigation buttons
document.querySelector('[data-testid="prev-button"]')
document.querySelector('[data-testid="next-button"]')
document.querySelector('[data-testid="submit-button"]')

// Find progress indicator
document.querySelector('[data-testid="form-progress-my-form"]')

// Find pages
document.querySelector('[data-testid="form-page-1"]')
document.querySelector('[data-testid="form-page-2"]')
```

---

## 📊 Console Debugging

### Browser Console Commands

```javascript
// Get form object
const form = document.querySelector('.vas-form')

// Check current state
console.log('Current Page:', EIPSIForms.getCurrentPage(form))
console.log('Total Pages:', EIPSIForms.getTotalPages(form))

// Get navigator
const nav = EIPSIForms.getNavigator(form)
console.log('History:', nav.history)
console.log('Visited Pages:', Array.from(nav.visitedPages))
console.log('Skipped Pages:', Array.from(nav.skippedPages))

// Test next page calculation
console.log('Next Page:', nav.getNextPage(EIPSIForms.getCurrentPage(form)))

// Check configuration
console.log('Config:', window.eipsiFormsConfig)
```

---

## 🚨 Common Issues & Fixes

### Issue: Navigation buttons not responding (FIXED 2025-01-23)

**Cause:** Disabled attribute not cleared after initialization  
**Symptoms:**
- Buttons visible but unresponsive to clicks
- No console errors
- Form appears normal

**Fix Applied:** Added explicit `removeAttribute('disabled')` in:
- `initPagination()` - Lines 677, 688
- `updatePaginationDisplay()` - Lines 1086, 1098, 1116

**Verification:**
```javascript
const form = document.querySelector('.vas-form')
const nextBtn = form.querySelector('.eipsi-next-button')
console.log('Disabled:', nextBtn.disabled) // Should be false
console.log('Display:', window.getComputedStyle(nextBtn).display) // Should not be 'none'
```

**See:** `NAVIGATION_UX_FIX_REPORT.md` for full details

---

### Issue: Previous button doesn't show

**Cause:** History not initialized  
**Fix:** Ensure `ConditionalNavigator` created for form  
```javascript
const nav = EIPSIForms.getNavigator(form)
console.log(nav.history) // Should show array with at least current page
```

### Issue: Validation not blocking navigation

**Cause:** `data-required="true"` missing on form-group  
**Fix:** Add data attribute to form-group div  
```html
<div class="form-group" data-required="true">
    <input type="text" required />
</div>
```

### Issue: Progress shows "Página X de ?"

**Cause:** `initPagination()` not called or no `.eipsi-page` elements  
**Fix:** Ensure pages have class `eipsi-page` and `data-page` attribute  
```javascript
// Check if pages found
console.log(document.querySelectorAll('.eipsi-page').length)
```

### Issue: Submit button shows "Enviando..." forever

**Cause:** AJAX request failed without error handling  
**Fix:** Check browser console for errors, verify `ajaxUrl` in config  
```javascript
console.log(window.eipsiFormsConfig.ajaxUrl)
```

### Issue: Conditional logic not working

**Cause:** Invalid JSON in `data-conditional-logic`  
**Fix:** Validate JSON syntax  
```javascript
const field = document.querySelector('[data-conditional-logic]')
try {
    JSON.parse(field.dataset.conditionalLogic)
    console.log('✅ Valid JSON')
} catch (e) {
    console.error('❌ Invalid JSON:', e)
}
```

---

## 🎯 Conditional Logic Cheat Sheet

### Rule Structure

```javascript
{
    "enabled": true,                          // Must be true
    "rules": [                                // Array of rules
        {
            "id": "rule-1",                   // Unique identifier
            "matchValue": "Option A",         // Value to match
            "action": "goToPage",             // "nextPage" | "goToPage" | "submit"
            "targetPage": 5                   // Required if action = "goToPage"
        }
    ],
    "defaultAction": "nextPage",              // Fallback if no rule matches
    "defaultTargetPage": null                 // Only if defaultAction = "goToPage"
}
```

### Common Patterns

**Skip to last page:**
```javascript
{
    "enabled": true,
    "rules": [
        {
            "id": "rule-skip",
            "matchValue": "Skip ahead",
            "action": "goToPage",
            "targetPage": 10
        }
    ],
    "defaultAction": "nextPage"
}
```

**Early form submission:**
```javascript
{
    "enabled": true,
    "rules": [
        {
            "id": "rule-submit",
            "matchValue": "Done",
            "action": "submit"
        }
    ],
    "defaultAction": "nextPage"
}
```

**Multiple skip options:**
```javascript
{
    "enabled": true,
    "rules": [
        {
            "id": "rule-1",
            "matchValue": "Path A",
            "action": "goToPage",
            "targetPage": 5
        },
        {
            "id": "rule-2",
            "matchValue": "Path B",
            "action": "goToPage",
            "targetPage": 8
        }
    ],
    "defaultAction": "nextPage"  // Linear for other options
}
```

---

## 🔄 Navigation Flow Diagram

```
User clicks button
        ↓
    [Button Type?]
        ↓
    ┌───┴───┐
    ↓       ↓
[Next]  [Previous]
    ↓       ↓
Validate    Pop History
    ↓       ↓
  Pass?    Get Page
    ↓       ↓
Check Logic  ↓
    ↓       ↓
Get Target  ↓
    ↓       ↓
    └───┬───┘
        ↓
  setCurrentPage()
        ↓
  updatePaginationDisplay()
        ↓
    ┌───┴───┐
    ↓       ↓
Update    Update
Buttons   Progress
    ↓       ↓
    └───┬───┘
        ↓
  updatePageVisibility()
        ↓
  Show/Hide Pages
        ↓
  updatePageAriaAttributes()
        ↓
  Set ARIA & Inert
        ↓
    [Complete]
```

---

## 📱 Responsive Breakpoints

```css
/* Desktop (default) */
.form-navigation {
    flex-direction: row;                      /* Horizontal layout */
    justify-content: space-between;
}

/* Tablet & Mobile (≤768px) */
@media (max-width: 768px) {
    .form-navigation {
        flex-direction: column-reverse;       /* Vertical stack */
        /* Next at top, Previous at bottom */
    }
    
    button {
        width: 100%;                          /* Full width buttons */
    }
}

/* Small Mobile (≤480px) */
@media (max-width: 480px) {
    .eipsi-page-title {
        font-size: 1.25rem;                   /* Smaller headings */
    }
    
    .vas-value-number {
        font-size: 1.75rem;                   /* Smaller VAS display */
    }
}
```

---

## ♿ Accessibility Features

### Keyboard Navigation

| Key | Action |
|-----|--------|
| Tab | Move to next button |
| Shift+Tab | Move to previous button |
| Enter | Activate focused button |
| Space | Activate focused button |

### ARIA Attributes

```html
<!-- Active page -->
<div class="eipsi-page" data-page="2" aria-hidden="false">

<!-- Inactive pages -->
<div class="eipsi-page" data-page="1" aria-hidden="true" inert>

<!-- Invalid inputs -->
<input class="error" aria-invalid="true" />

<!-- Error messages -->
<div class="form-error" role="alert">Este campo es obligatorio.</div>
```

### Focus Management

```javascript
// Focus moves to first error on validation failure
EIPSIForms.focusFirstInvalidField(form, pageElement)

// Scroll behavior respects user preferences
@media (prefers-reduced-motion: reduce) {
    scroll-behavior: auto !important;
}
```

---

## 📚 Related Files

- **Full Test Report:** `NAVIGATION_UX_TEST_REPORT.md`
- **Implementation Guide:** `NAVIGATION_UX_IMPLEMENTATION_GUIDE.md`
- **Test Form:** `test-navigation-ux.html`
- **Conditional Logic Docs:** `CONDITIONAL_LOGIC_IMPLEMENTATION.md`

---

**Last Updated:** 2024  
**Version:** 2.2  
**Branch:** `test/forms-navigation-ux-check`
