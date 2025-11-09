# EIPSI Forms - Navigation UX Implementation Guide

This guide documents how pagination, validation, and navigation controls work in multi-page EIPSI forms.

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    FORM CONTAINER BLOCK                      │
│  (src/blocks/form-container/)                               │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  save.js - Renders Navigation Markup                 │    │
│  │  • Previous Button (hidden initially)                │    │
│  │  • Next Button (visible)                             │    │
│  │  • Submit Button (hidden initially)                  │    │
│  │  • Progress Indicator (Página X de Y)                │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│              FRONTEND JAVASCRIPT RUNTIME                     │
│  (assets/js/eipsi-forms.js)                                 │
│                                                              │
│  ┌──────────────────────────────────────────────────┐      │
│  │  ConditionalNavigator Class                       │      │
│  │  • history: [1, 2, 4] (visited pages)            │      │
│  │  • visitedPages: Set {1, 2, 4}                   │      │
│  │  • skippedPages: Set {3} (branched logic)        │      │
│  │  • getNextPage() - Calculates target page        │      │
│  │  • pushHistory() - Records navigation            │      │
│  │  • popHistory() - Returns previous page          │      │
│  └──────────────────────────────────────────────────┘      │
│                                                              │
│  ┌──────────────────────────────────────────────────┐      │
│  │  Navigation Functions                             │      │
│  │  • initPagination() - Setup event listeners      │      │
│  │  • handlePagination('next' | 'prev')             │      │
│  │  • validateCurrentPage() - Block if invalid      │      │
│  │  • updatePaginationDisplay() - Show/hide buttons │      │
│  │  • updatePageVisibility() - CSS display toggle   │      │
│  │  • focusFirstInvalidField() - Error focus        │      │
│  │  • submitForm() - AJAX with loading states       │      │
│  └──────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                   CSS STYLING                                │
│  (assets/css/eipsi-forms.css)                               │
│                                                              │
│  • Button hover/focus states                                │
│  • Progress bar styling                                     │
│  • Responsive breakpoints (768px, 480px)                    │
│  • Reduced motion support                                   │
│  • ARIA-based visibility                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 Component 1: Navigation Markup

**File:** `src/blocks/form-container/save.js`

### Button Structure

```javascript
// Lines 78-102
<div className="form-navigation">
    <button
        type="button"
        className="eipsi-prev-button"
        style={{ display: 'none' }}          // Hidden on Page 1
        data-testid="prev-button"
    >
        Anterior                              // Spanish: "Previous"
    </button>
    
    <button
        type="button"
        className="eipsi-next-button"
        data-testid="next-button"
    >
        Siguiente                             // Spanish: "Next"
    </button>
    
    <button
        type="submit"
        className="eipsi-submit-button"
        style={{ display: 'none' }}          // Hidden until last page
        data-testid="submit-button"
    >
        {submitButtonLabel || 'Enviar'}       // Spanish: "Send"
    </button>
</div>
```

**Key Design Decisions:**
- ✅ `type="button"` for Prev/Next (prevents accidental form submission)
- ✅ `type="submit"` for final button (leverages browser validation)
- ✅ Inline `style` for initial state (JS updates dynamically)
- ✅ `data-testid` attributes for automated testing

### Progress Indicator

```javascript
// Lines 105-108
<div className="form-progress">
    Página <span className="current-page">1</span> de{' '}
    <span className="total-pages">?</span>
</div>
```

**Features:**
- ✅ Starts at "Página 1 de ?" (JS populates total)
- ✅ Separate `<span>` elements for dynamic updates
- ✅ Spanish terminology for clinical context

---

## 🧠 Component 2: ConditionalNavigator Class

**File:** `assets/js/eipsi-forms.js` (Lines 12-281)

### Class Overview

```javascript
class ConditionalNavigator {
    constructor(form) {
        this.form = form;
        this.fieldCache = new Map();          // Performance optimization
        this.history = [];                     // [1, 3, 5] - Pages visited in order
        this.visitedPages = new Set();         // {1, 3, 5} - Unique pages seen
        this.skippedPages = new Set();         // {2, 4} - Pages bypassed by logic
    }
    
    // ... methods
}
```

### Method: getNextPage()

**Purpose:** Calculate target page based on conditional logic rules

```javascript
// Lines 116-228
getNextPage(currentPage) {
    const currentPageElement = EIPSIForms.getPageElement(this.form, currentPage);
    
    // 1. Find fields with conditional logic
    const conditionalFields = currentPageElement.querySelectorAll(
        '[data-conditional-logic]'
    );
    
    // 2. Iterate through conditional fields
    for (const field of conditionalFields) {
        const jsonString = field.dataset.conditionalLogic;
        const parsedLogic = this.parseConditionalLogic(jsonString);
        const conditionalLogic = this.normalizeConditionalLogic(parsedLogic);
        
        if (!conditionalLogic || !conditionalLogic.enabled) {
            continue; // Skip if logic disabled
        }
        
        // 3. Get current field value
        const fieldValue = this.getFieldValue(field);
        
        if (!fieldValue || (Array.isArray(fieldValue) && fieldValue.length === 0)) {
            continue; // Skip if field empty
        }
        
        // 4. Find matching rule
        const matchingRule = this.findMatchingRule(
            conditionalLogic.rules,
            fieldValue
        );
        
        // 5. Execute rule action
        if (matchingRule) {
            if (matchingRule.action === 'submit') {
                return { action: 'submit' }; // Trigger form submission
            }
            
            if (matchingRule.action === 'goToPage' && matchingRule.targetPage) {
                const targetPage = parseInt(matchingRule.targetPage, 10);
                const totalPages = EIPSIForms.getTotalPages(this.form);
                const boundedTarget = Math.min(
                    Math.max(targetPage, 1),
                    totalPages
                ); // Clamp to valid range
                
                return {
                    action: 'goToPage',
                    targetPage: boundedTarget,
                    fieldId: field.id || field.dataset.fieldName,
                    matchedValue: Array.isArray(fieldValue) 
                        ? fieldValue[0] 
                        : fieldValue,
                };
            }
        }
        
        // 6. Apply default action if no rule matched
        if (conditionalLogic.defaultAction === 'goToPage') {
            // ... similar logic for default target
        }
    }
    
    // 7. No conditional logic found - advance to next page
    return { action: 'nextPage', targetPage: currentPage + 1 };
}
```

**Return Values:**
- `{ action: 'nextPage', targetPage: 2 }` - Normal progression
- `{ action: 'goToPage', targetPage: 5 }` - Skip pages
- `{ action: 'submit' }` - End form early

### Method: pushHistory() / popHistory()

**Purpose:** Track navigation path for Previous button

```javascript
// Lines 235-251
pushHistory(pageNumber) {
    // Only add if different from last entry
    if (
        this.history.length === 0 ||
        this.history[this.history.length - 1] !== pageNumber
    ) {
        this.history.push(pageNumber);           // Add to array
        this.visitedPages.add(pageNumber);       // Add to set
    }
}

popHistory() {
    if (this.history.length > 1) {
        this.history.pop();                      // Remove current page
        return this.history[this.history.length - 1]; // Return previous
    }
    return null;                                 // Already at first page
}
```

**Example Navigation Flow:**
```
User Path:      Page 1 → Page 2 → Page 5 (skip 3,4) → Previous
history:        [1]      [1,2]    [1,2,5]            [1,2]
Result:         -        -        Page 2 visible      Page 2 visible
```

---

## ⚙️ Component 3: Navigation Logic

### Function: handlePagination()

**File:** `assets/js/eipsi-forms.js` (Lines 836-925)

```javascript
handlePagination(form, direction) {
    const currentPage = this.getCurrentPage(form);
    let targetPage = currentPage;
    let isBranchJump = false;
    let branchDetails = null;
    
    // ========== NEXT BUTTON ==========
    if (direction === 'next') {
        // 1. VALIDATION CHECKPOINT
        if (!this.validateCurrentPage(form)) {
            return; // ❌ Block navigation if validation fails
        }
        
        // 2. CHECK CONDITIONAL LOGIC
        const navigator = this.getNavigator(form);
        if (navigator) {
            const result = navigator.getNextPage(currentPage);
            
            // 2a. Conditional submit?
            if (result.action === 'submit') {
                this.handleSubmit({ preventDefault: () => {} }, form);
                return;
            }
            
            // 2b. Conditional page jump?
            if (result.action === 'goToPage' && result.targetPage) {
                targetPage = result.targetPage;
                isBranchJump = (targetPage !== currentPage + 1);
                branchDetails = result;
            } else {
                // 2c. Normal progression
                const totalPages = this.getTotalPages(form);
                if (currentPage < totalPages) {
                    targetPage = currentPage + 1;
                }
            }
            
            // 3. MARK SKIPPED PAGES
            if (isBranchJump) {
                navigator.markSkippedPages(currentPage, targetPage);
            }
            
            // 4. RECORD IN HISTORY
            navigator.pushHistory(targetPage);
        }
    }
    
    // ========== PREVIOUS BUTTON ==========
    else if (direction === 'prev') {
        const navigator = this.getNavigator(form);
        if (navigator) {
            // Use history stack (respects branched path)
            const previousPage = navigator.popHistory();
            if (previousPage !== null) {
                targetPage = previousPage;
            } else if (currentPage > 1) {
                targetPage = currentPage - 1;
            }
        } else if (currentPage > 1) {
            // Fallback: simple decrement
            targetPage = currentPage - 1;
        }
    }
    
    // ========== APPLY PAGE CHANGE ==========
    if (targetPage !== currentPage) {
        this.setCurrentPage(form, targetPage);
        
        // Log branch jumps for analytics
        if (isBranchJump && branchDetails && window.EIPSITracking) {
            this.recordBranchJump(form, currentPage, targetPage, branchDetails);
        }
    }
}
```

**Key Features:**
1. ✅ **Validation Gate:** `validateCurrentPage()` blocks forward navigation if errors exist
2. ✅ **Conditional Logic Integration:** Checks for branching rules on current page
3. ✅ **History Tracking:** Records path for accurate Previous button behavior
4. ✅ **Skipped Page Tracking:** Marks pages bypassed by logic
5. ✅ **Analytics Events:** Logs branch jumps for research insights

---

### Function: validateCurrentPage()

**File:** `assets/js/eipsi-forms.js` (Lines 1239-1283)

```javascript
validateCurrentPage(form) {
    if (!form) return true;
    
    const currentPage = this.getCurrentPage(form);
    const pageElement = this.getPageElement(form, currentPage);
    
    if (!pageElement) return true;
    
    // 1. GET ALL FIELDS ON CURRENT PAGE
    const fields = pageElement.querySelectorAll('input, textarea, select');
    let isValid = true;
    const validatedGroups = new Set();
    
    // 2. VALIDATE EACH FIELD
    fields.forEach((field) => {
        const formGroup = field.closest('.form-group');
        const groupKey = formGroup
            ? formGroup.dataset.fieldName || formGroup.id || ''
            : '';
        
        // 2a. Skip if radio/checkbox already validated
        if ((field.type === 'radio' || field.type === 'checkbox') && groupKey) {
            if (validatedGroups.has(groupKey)) {
                return; // Already checked this group
            }
            validatedGroups.add(groupKey);
        }
        
        // 2b. Run validation
        if (!this.validateField(field)) {
            isValid = false;
        }
    });
    
    // 3. FOCUS FIRST ERROR
    if (!isValid) {
        this.focusFirstInvalidField(form, pageElement);
    }
    
    return isValid; // ✅ true = allow navigation, ❌ false = block
}
```

**Validation Rules:**
- ✅ Required fields must be filled
- ✅ Email fields must match pattern
- ✅ Radio/checkbox groups validated once
- ✅ Only validates fields on current page

**Error Display:**
```html
<!-- Generated by validateField() -->
<div class="form-group has-error">
    <label>Nombre <span class="required-asterisk">*</span></label>
    <input type="text" class="error" aria-invalid="true" />
    <div class="form-error" style="display:block;">
        Este campo es obligatorio.
    </div>
</div>
```

---

### Function: updatePaginationDisplay()

**File:** `assets/js/eipsi-forms.js` (Lines 962-1041)

```javascript
updatePaginationDisplay(form, currentPage, totalPages) {
    const prevButton = form.querySelector('.eipsi-prev-button');
    const nextButton = form.querySelector('.eipsi-next-button');
    const submitButton = form.querySelector('.eipsi-submit-button');
    const progressText = form.querySelector('.form-progress .current-page');
    const totalPagesText = form.querySelector('.form-progress .total-pages');
    const navigator = this.getNavigator(form);
    
    // ========== PREVIOUS BUTTON VISIBILITY ==========
    const hasHistory = navigator && navigator.history.length > 1;
    
    if (prevButton) {
        prevButton.style.display = 
            (hasHistory || currentPage > 1) ? '' : 'none';
    }
    
    // ========== NEXT BUTTON VISIBILITY ==========
    const shouldShowNext = navigator
        ? !navigator.shouldSubmit(currentPage) && currentPage < totalPages
        : currentPage < totalPages;
    
    if (nextButton) {
        nextButton.style.display = shouldShowNext ? '' : 'none';
    }
    
    // ========== SUBMIT BUTTON VISIBILITY ==========
    const shouldShowSubmit = navigator
        ? navigator.shouldSubmit(currentPage) || currentPage === totalPages
        : currentPage === totalPages;
    
    if (submitButton) {
        submitButton.style.display = shouldShowSubmit ? '' : 'none';
    }
    
    // ========== PROGRESS TEXT UPDATE ==========
    if (progressText) {
        progressText.textContent = currentPage; // "Página 3 de X"
    }
    
    // ========== SMART TOTAL CALCULATION ==========
    if (totalPagesText && navigator && navigator.visitedPages.size > 0) {
        const activePath = navigator.getActivePath(); // [1, 3, 5]
        const currentIndex = activePath.indexOf(currentPage);
        
        if (currentIndex !== -1) {
            const remainingPages = 
                totalPages - activePath[activePath.length - 1];
            const estimatedTotal = 
                activePath.length + Math.max(0, remainingPages);
            
            // Show asterisk if branched route differs from linear
            if (estimatedTotal !== totalPages) {
                totalPagesText.textContent = `${estimatedTotal}*`;
                totalPagesText.title = 'Estimado basado en tu ruta actual';
            } else {
                totalPagesText.textContent = totalPages;
                totalPagesText.title = '';
            }
        }
    }
    
    // ========== UPDATE PAGE VISIBILITY & ARIA ==========
    this.updatePageVisibility(form, currentPage);
    this.updatePageAriaAttributes(form, currentPage);
    
    // ========== TRACKING ==========
    if (window.EIPSITracking) {
        const trackingFormId = this.getTrackingFormId(form);
        if (trackingFormId) {
            window.EIPSITracking.setCurrentPage(
                trackingFormId,
                currentPage,
                { trackChange: false }
            );
        }
    }
}
```

**Button Visibility Logic:**

| Page | Previous | Next | Submit | Reason |
|------|----------|------|--------|--------|
| 1 of 4 | Hidden | Visible | Hidden | First page, more ahead |
| 2 of 4 | Visible | Visible | Hidden | Middle page |
| 4 of 4 | Visible | Hidden | Visible | Last page |
| 3 of 4 (conditional submit) | Visible | Hidden | Visible | Early exit trigger |

---

### Function: submitForm()

**File:** `assets/js/eipsi-forms.js` (Lines 1393-1456)

```javascript
submitForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    
    formData.append('action', 'vas_dinamico_submit_form');
    formData.append('nonce', this.config.nonce);
    
    this.setFormLoading(form, true); // Add .form-loading class
    
    // ========== BUTTON STATE: LOADING ==========
    if (submitButton) {
        submitButton.disabled = true;                          // ❌ Prevent clicks
        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.textContent = 'Enviando...';              // Spanish: "Sending..."
    }
    
    // ========== AJAX REQUEST ==========
    fetch(this.config.ajaxUrl, {
        method: 'POST',
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                this.showMessage(form, 'success', '¡Formulario enviado correctamente!');
                
                // Track submission
                if (window.EIPSITracking) {
                    const trackingFormId = this.getTrackingFormId(form);
                    if (trackingFormId) {
                        window.EIPSITracking.recordSubmit(trackingFormId);
                    }
                }
                
                form.reset(); // Clear form
            } else {
                this.showMessage(form, 'error', 'Ocurrió un error. Por favor, inténtelo de nuevo.');
            }
        })
        .catch(() => {
            this.showMessage(form, 'error', 'Ocurrió un error. Por favor, inténtelo de nuevo.');
        })
        .finally(() => {
            this.setFormLoading(form, false); // Remove .form-loading class
            
            // ========== BUTTON STATE: RESTORE ==========
            if (submitButton) {
                submitButton.disabled = false;                  // ✅ Re-enable
                submitButton.textContent = 
                    submitButton.dataset.originalText || 'Enviar';
            }
        });
}
```

**State Transitions:**
```
Idle State:
  Button: "Enviar" (enabled)
  
User Clicks:
  Button: "Enviando..." (disabled) ← Prevents double submission
  
Request Success:
  Message: "¡Formulario enviado correctamente!"
  Button: "Enviar" (enabled, re-enabled in finally block)
  
Request Failure:
  Message: "Ocurrió un error..."
  Button: "Enviar" (enabled, re-enabled in finally block)
```

**Key Features:**
- ✅ Button disabled during request (UX feedback + prevent double-submit)
- ✅ Text changes to "Enviando..." (clinical language)
- ✅ `finally` block ensures button always re-enables
- ✅ Analytics tracking for research metrics

---

## 🎨 Component 4: CSS Styling

**File:** `assets/css/eipsi-forms.css`

### Navigation Button Styles

```css
/* Lines 992-1090 */

/* Base Button Styles */
.eipsi-prev-button,
.eipsi-next-button,
.eipsi-submit-button {
    padding: 0.875rem 2rem;                     /* 14px 32px */
    font-size: var(--eipsi-font-size-base, 1rem);
    font-weight: var(--eipsi-font-weight-medium, 600);
    border-radius: var(--eipsi-border-radius-sm, 8px);
    cursor: pointer;
    transition: all var(--eipsi-transition-duration, 0.2s) 
                var(--eipsi-transition-timing, ease);
    border: var(--eipsi-border-width-focus, 2px) solid transparent;
}

/* Previous Button (Secondary) */
.eipsi-prev-button {
    background: var(--eipsi-color-background, #ffffff);
    color: var(--eipsi-color-primary, #005a87);
    border-color: var(--eipsi-color-border, #e2e8f0);
}

.eipsi-prev-button:hover {
    background: var(--eipsi-color-background-subtle, #f8f9fa);
    border-color: var(--eipsi-color-border-dark, #cbd5e0);
    transform: translateX(-2px);                /* Slide left on hover */
}

.eipsi-prev-button:focus {
    outline: var(--eipsi-focus-outline-width, 2px) solid 
             var(--eipsi-color-primary, #005a87);
    outline-offset: var(--eipsi-focus-outline-offset, 3px);
}

/* Next Button (Primary) */
.eipsi-next-button {
    background: var(--eipsi-color-button-bg, #005a87);
    color: var(--eipsi-color-button-text, #ffffff);
    border-color: var(--eipsi-color-button-bg, #005a87);
}

.eipsi-next-button:hover {
    background: var(--eipsi-color-button-hover-bg, #003d5b);
    transform: translateX(2px);                 /* Slide right on hover */
    box-shadow: var(--eipsi-shadow-md, 0 4px 12px rgba(0, 90, 135, 0.3));
}

/* Submit Button (Primary + Emphasized) */
.eipsi-submit-button {
    background: var(--eipsi-color-button-bg, #005a87);
    color: var(--eipsi-color-button-text, #ffffff);
    padding: 1rem 2.5rem;                       /* Larger for emphasis */
    font-weight: var(--eipsi-font-weight-bold, 700);
    box-shadow: var(--eipsi-shadow-md, 0 4px 12px rgba(0, 90, 135, 0.2));
}

.eipsi-submit-button:hover {
    transform: translateY(-2px);                /* Lift on hover */
    box-shadow: 0 6px 20px rgba(0, 90, 135, 0.35);
}

/* Disabled State */
.eipsi-submit-button:disabled {
    background: var(--eipsi-color-border-dark, #cbd5e0);
    border-color: var(--eipsi-color-border-dark, #cbd5e0);
    color: var(--eipsi-color-text-muted, #6c757d);
    cursor: not-allowed;
    opacity: 0.6;
    transform: none;                            /* No hover effects */
    box-shadow: none;
}
```

**Visual Hierarchy:**
1. **Previous Button:** Secondary style (white bg, blue border) - less emphasis
2. **Next Button:** Primary style (blue bg, white text) - strong CTA
3. **Submit Button:** Primary + bold + larger - strongest emphasis

### Progress Indicator Styles

```css
/* Lines 1093-1109 */
.form-progress {
    background: #f8f9fa;                        /* Subtle gray */
    border: 2px solid #e2e8f0;
    border-radius: 20px;                        /* Pill shape */
    padding: 0.625rem 1.25rem;
    font-size: 0.9375rem;                       /* 15px */
    font-weight: 500;
    color: #2c3e50;
    white-space: nowrap;                        /* Prevent wrapping */
}

.form-progress .current-page,
.form-progress .total-pages {
    color: #005a87;                             /* Clinical blue */
    font-weight: 700;                           /* Bold numbers */
    font-size: 1.125rem;                        /* 18px - larger */
}
```

**Design Rationale:**
- ✅ Pill shape (border-radius: 20px) is modern and friendly
- ✅ Bold, larger numbers draw attention to position
- ✅ Clinical blue color matches brand
- ✅ Subtle gray background separates from form content

### Responsive Mobile Layout

```css
/* Lines 1115-1156 */
@media (max-width: 768px) {
    .form-navigation {
        flex-direction: column-reverse;         /* Stack vertically */
        gap: 1rem;
    }
    
    .eipsi-prev-button,
    .eipsi-next-button,
    .eipsi-submit-button {
        width: 100%;                            /* Full width */
        text-align: center;
        justify-content: center;
    }
    
    .form-progress {
        width: 100%;
        text-align: center;
    }
}
```

**Mobile Layout:**
```
┌─────────────────────────┐
│      [Next Button]      │ ← Primary action at top (thumb-friendly)
├─────────────────────────┤
│   [Previous Button]     │ ← Secondary action below
├─────────────────────────┤
│   Página 2 de 4         │ ← Progress indicator
└─────────────────────────┘
```

**Why `column-reverse`?**
- ✅ On mobile, thumbs are at bottom of screen
- ✅ Placing "Next" (primary action) at top makes it easier to reach
- ✅ "Previous" (secondary action) less critical, can be lower

### Reduced Motion Support

```css
/* Lines 1244-1254 */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;   /* Near-instant */
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;        /* Instant scroll */
    }
}
```

**Accessibility Impact:**
- ✅ Users with vestibular disorders can disable motion
- ✅ All transitions become near-instant (0.01ms)
- ✅ Smooth scrolling disabled (`scroll-behavior: auto`)
- ✅ `!important` used appropriately for accessibility override

---

## 🎯 Use Case Examples

### Example 1: Linear Navigation

**Form Structure:** 3 pages, no conditional logic

**User Journey:**
```
Page 1 → Click Next → Page 2 → Click Next → Page 3 → Click Submit
```

**History Stack:**
```javascript
Initial:  history = []
Page 1:   history = [1]           // pushHistory(1) on init
Page 2:   history = [1, 2]        // pushHistory(2) on Next
Page 3:   history = [1, 2, 3]     // pushHistory(3) on Next
```

**Previous Button Behavior:**
```
On Page 3: Click Prev → popHistory() → returns 2 → Go to Page 2
On Page 2: Click Prev → popHistory() → returns 1 → Go to Page 1
On Page 1: Prev button hidden (history.length === 1)
```

---

### Example 2: Conditional Skip

**Form Structure:** 4 pages, Page 1 has conditional logic

**Conditional Rule:**
```javascript
{
    "enabled": true,
    "rules": [
        {
            "matchValue": "Nunca",
            "action": "goToPage",
            "targetPage": 4
        }
    ],
    "defaultAction": "nextPage"
}
```

**User Journey:**
```
Page 1 (select "Nunca") → Click Next → Skip to Page 4
```

**Navigation Logic:**
```javascript
// User on Page 1, clicked Next
handlePagination(form, 'next')
  → validateCurrentPage(form) // ✅ Pass
  → navigator.getNextPage(1)
    → Finds field with data-conditional-logic
    → Gets field value: "Nunca"
    → Finds matching rule (matchValue === "Nunca")
    → Returns { action: 'goToPage', targetPage: 4 }
  → targetPage = 4
  → isBranchJump = true (4 !== 1 + 1)
  → navigator.markSkippedPages(1, 4) // Marks 2,3 as skipped
  → navigator.pushHistory(4)
  → setCurrentPage(form, 4)
```

**History Stack:**
```javascript
history = [1, 4]              // Pages 2 and 3 never visited
visitedPages = Set {1, 4}
skippedPages = Set {2, 3}
```

**Previous Button Behavior:**
```
On Page 4: Click Prev → popHistory() → returns 1 → Go to Page 1
(Skips back over Pages 2 and 3, maintaining logical path)
```

**Progress Indicator:**
```
Before jump: "Página 1 de 4"
After jump:  "Página 4 de 2*" 
             ↑ Asterisk indicates branched route
```

---

### Example 3: Validation Blocking

**Form Structure:** Page 1 has 2 required fields

**User Journey:**
```
Page 1 (leave fields empty) → Click Next → Validation fails → Stay on Page 1
```

**Validation Flow:**
```javascript
handlePagination(form, 'next')
  → validateCurrentPage(form)
    → Gets Page 1 element
    → Finds all inputs on page: [input#name, input#email]
    → Validates input#name:
      → Required, value = "" → ❌ INVALID
      → Adds .has-error class
      → Shows error message: "Este campo es obligatorio."
    → Validates input#email:
      → Required, value = "" → ❌ INVALID
      → Adds .has-error class
      → Shows error message: "Este campo es obligatorio."
    → isValid = false
    → focusFirstInvalidField(form, pageElement)
      → Finds first .has-error element (input#name)
      → Scrolls to element (if auto-scroll enabled)
      → Sets focus: input#name.focus()
    → Returns false
  → Early return (line 848) // ❌ Navigation blocked
```

**Visual Result:**
```html
<div class="form-group has-error">
    <label>Nombre <span class="required-asterisk">*</span></label>
    <input id="name" type="text" class="error" aria-invalid="true" />
    <div class="form-error" style="display:block;">
        Este campo es obligatorio.
    </div>
</div>
```

**User Experience:**
- ✅ Page does not advance
- ✅ Red error messages appear below fields
- ✅ Focus moves to first invalid field
- ✅ Screen readers announce error via `aria-invalid`

---

## 🔧 Configuration & Extensibility

### WordPress Filters

**File:** `vas-dinamico-forms.php` (Lines 318-323)

```php
'settings' => array(
    'enableAutoScroll' => apply_filters('vas_dinamico_enable_auto_scroll', true),
    'scrollOffset' => apply_filters('vas_dinamico_scroll_offset', 20),
    'validateOnBlur' => apply_filters('vas_dinamico_validate_on_blur', true),
    'smoothScroll' => apply_filters('vas_dinamico_smooth_scroll', true),
)
```

**Customization Example:**

```php
// In theme functions.php or custom plugin

// Disable auto-scroll
add_filter('vas_dinamico_enable_auto_scroll', '__return_false');

// Increase scroll offset to 50px
add_filter('vas_dinamico_scroll_offset', function() {
    return 50;
});

// Disable smooth scrolling (instant jump)
add_filter('vas_dinamico_smooth_scroll', '__return_false');
```

### CSS Custom Properties

All colors, spacing, and styling are themeable via CSS variables:

```css
/* Override in your theme or custom CSS */
.vas-dinamico-form {
    /* Change primary color from blue to green */
    --eipsi-color-primary: #2d8659;
    --eipsi-color-button-bg: #2d8659;
    
    /* Adjust spacing */
    --eipsi-spacing-lg: 3rem;
    
    /* Change border radius */
    --eipsi-border-radius-sm: 12px;
}
```

---

## 🧪 Testing Guide

### Manual Testing Checklist

**Test 1: Initial State**
- [ ] Page 1 visible
- [ ] Previous button hidden
- [ ] Next button visible
- [ ] Submit button hidden
- [ ] Progress shows "Página 1 de X"

**Test 2: Validation Blocking**
- [ ] Click Next with empty required fields
- [ ] Page does not advance
- [ ] Error messages appear
- [ ] Focus moves to first error
- [ ] Red border on invalid fields

**Test 3: Forward Navigation**
- [ ] Fill required fields
- [ ] Click Next
- [ ] Page 2 visible, Page 1 hidden
- [ ] Progress updates to "Página 2 de X"
- [ ] Previous button now visible

**Test 4: Backward Navigation**
- [ ] Click Previous on Page 2
- [ ] Return to Page 1
- [ ] Form data preserved
- [ ] Progress updates to "Página 1 of X"
- [ ] Previous button hidden again

**Test 5: Conditional Logic**
- [ ] Select option that triggers page skip
- [ ] Click Next
- [ ] Correct target page displayed
- [ ] Intermediate pages skipped
- [ ] Progress indicator shows asterisk (*) if branched

**Test 6: Submit Button**
- [ ] Navigate to last page
- [ ] Submit button visible
- [ ] Next button hidden
- [ ] Required fields validated
- [ ] Cannot submit with errors

**Test 7: Submit Process**
- [ ] Fill all required fields on last page
- [ ] Click Submit
- [ ] Button text changes to "Enviando..."
- [ ] Button becomes disabled (grayed out)
- [ ] Success message appears after submission
- [ ] Button re-enables after completion

**Test 8: Keyboard Navigation**
- [ ] Use Tab key to navigate
- [ ] Focus visible on buttons
- [ ] Enter/Space activates buttons
- [ ] No keyboard traps

**Test 9: Mobile Responsive**
- [ ] Resize browser to 375px width
- [ ] Buttons stack vertically
- [ ] Buttons become full width
- [ ] Next button appears above Previous

**Test 10: Accessibility**
- [ ] Screen reader announces page changes
- [ ] Error messages linked to inputs
- [ ] ARIA attributes present on hidden pages
- [ ] Focus indicators visible (2px outline)

---

## 📚 Related Documentation

- **Conditional Logic Guide:** `CONDITIONAL_LOGIC_IMPLEMENTATION.md`
- **Customization Panel:** `CUSTOMIZATION_PANEL_GUIDE.md`
- **CSS Architecture:** `assets/css/eipsi-forms.css` (comments)
- **Test Form:** `test-navigation-ux.html`

---

**Last Updated:** 2024  
**Version:** 2.2  
**Branch:** `test/forms-navigation-ux-check`
