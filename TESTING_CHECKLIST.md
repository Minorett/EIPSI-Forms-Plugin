# Testing Checklist - Integrated Thank-You Page

## Pre-Testing Setup
- [ ] Ensure WordPress site is running
- [ ] Plugin is activated
- [ ] At least one multi-page form exists

## Admin Panel Testing

### Navigation
- [ ] Go to EIPSI Forms → Results & Experience
- [ ] Verify tab is named "Finalización" (not "Completion Message")
- [ ] Click on "Finalización" tab

### Configuration Fields
- [ ] **Título** field is visible and has default value
- [ ] **Mensaje** rich text editor is visible with media upload button
- [ ] **Mostrar logo del sitio** checkbox exists and is checked by default
- [ ] **Mostrar botón "Volver al inicio"** checkbox exists and is checked by default
- [ ] **Texto del botón** field exists with "Comenzar de nuevo" default
- [ ] **Acción del botón** dropdown exists with 3 options:
  - [ ] "Recargar formulario (ideal para kiosks)" - selected by default
  - [ ] "Cerrar pestaña"
  - [ ] "Ninguna acción"
- [ ] **Animación sutil** checkbox exists and is unchecked by default
- [ ] **Redirect URL** field is NOT present (eliminated)

### Save Functionality
- [ ] Change title to "Thank you for participating!"
- [ ] Add custom message in rich text editor
- [ ] Toggle logo off
- [ ] Change button text to "Start over"
- [ ] Change button action to "Cerrar pestaña"
- [ ] Enable animation
- [ ] Click "💾 Guardar Configuración"
- [ ] Verify success message appears: "✅ Completion message saved successfully"
- [ ] Refresh page
- [ ] Verify all changes persisted

## Frontend Testing

### Basic Form Submission
- [ ] Navigate to a page with a multi-page form
- [ ] Fill out all pages
- [ ] Click "Enviar" on last page
- [ ] Verify success message appears: "✓ Respuesta guardada correctamente"
- [ ] Verify NO "Redirigiendo..." text appears
- [ ] Wait 1.5 seconds
- [ ] Verify thank-you page appears on the SAME URL (no redirect)

### Thank-You Page Content
- [ ] Title displays correctly (matches admin config)
- [ ] Message displays correctly (matches admin config, including rich text formatting)
- [ ] If logo enabled: Site logo appears at top
- [ ] If logo disabled: No logo appears
- [ ] If button enabled: Button appears with correct text
- [ ] If button disabled: No button appears

### Navigation Controls Hidden
- [ ] Previous button is hidden
- [ ] Next button is hidden
- [ ] Submit button is hidden
- [ ] Progress indicator is hidden
- [ ] All form pages are hidden

### Button Actions
#### Test 1: Reload (Default)
- [ ] Set action to "Recargar formulario" in admin
- [ ] Submit form
- [ ] Click button on thank-you page
- [ ] Verify page reloads
- [ ] Verify form is clean (all fields reset)
- [ ] Verify you're on page 1

#### Test 2: Close Tab
- [ ] Set action to "Cerrar pestaña" in admin
- [ ] Open form in NEW tab/window
- [ ] Submit form
- [ ] Click button on thank-you page
- [ ] Verify tab/window attempts to close
- [ ] Note: May not work if tab wasn't opened via JavaScript

#### Test 3: No Action
- [ ] Set action to "Ninguna acción" in admin
- [ ] Submit form
- [ ] Click button on thank-you page
- [ ] Verify nothing happens (button is decorative)

### Animation Testing
#### Animation Enabled
- [ ] Enable "Animación sutil" in admin
- [ ] Submit form
- [ ] Verify thank-you page fades in smoothly
- [ ] Verify subtle pulse animation (if not prefers-reduced-motion)

#### Animation Disabled
- [ ] Disable "Animación sutil" in admin
- [ ] Submit form
- [ ] Verify thank-you page appears without pulse animation
- [ ] Verify basic fade-in still occurs

#### Reduced Motion
- [ ] Enable reduced motion in OS settings
- [ ] Submit form
- [ ] Verify no animations play (accessibility)

### Responsive Design
#### Desktop (1920px)
- [ ] Thank-you page displays correctly
- [ ] Content is centered
- [ ] Button is inline

#### Tablet (768px)
- [ ] Content adjusts to smaller width
- [ ] All text remains readable
- [ ] Button remains clickable

#### Mobile (375px)
- [ ] Content adjusts to mobile width
- [ ] Title scales down
- [ ] Button becomes full-width
- [ ] Logo scales appropriately

### Theme Compatibility
#### Light Mode
- [ ] Thank-you page uses light background
- [ ] Text is dark and readable
- [ ] Primary color is EIPSI blue

#### Dark Mode (if theme toggle enabled)
- [ ] Switch to dark mode
- [ ] Submit form
- [ ] Verify dark background
- [ ] Verify light text
- [ ] Verify good contrast

### Edge Cases
#### No Logo Configured
- [ ] Remove site logo in Appearance → Customize
- [ ] Enable "Mostrar logo del sitio" in admin
- [ ] Submit form
- [ ] Verify thank-you page displays without logo (no error)

#### Button Disabled
- [ ] Uncheck "Mostrar botón 'Volver al inicio'" in admin
- [ ] Submit form
- [ ] Verify no button appears
- [ ] Verify page is still functional

#### Empty Message
- [ ] Clear message field in admin
- [ ] Submit form
- [ ] Verify thank-you page still displays (title + button only)

#### Network Error (AJAX)
- [ ] Open browser DevTools
- [ ] Block network requests to `/wp-admin/admin-ajax.php`
- [ ] Submit form
- [ ] Verify fallback thank-you page displays with defaults

#### Multiple Submissions
- [ ] Submit form once
- [ ] Click "Comenzar de nuevo"
- [ ] Fill form again
- [ ] Submit again
- [ ] Verify thank-you page appears again
- [ ] Repeat 5 times to test stability

## Kiosk Mode Testing
- [ ] Set action to "Recargar formulario"
- [ ] Open form in full-screen mode (F11)
- [ ] Submit form
- [ ] Click "Comenzar de nuevo"
- [ ] Verify smooth reload
- [ ] Repeat 10 times to simulate kiosk usage
- [ ] Verify no memory leaks (check DevTools Performance)

## Gutenberg Editor Testing
- [ ] Open form in Gutenberg editor
- [ ] Verify no "Thank You" page block appears
- [ ] Verify no "Thank You" option in inserter
- [ ] Verify existing pages display normally
- [ ] Save and preview
- [ ] Submit form in preview
- [ ] Verify thank-you page appears (generated dynamically)

## Accessibility Testing
- [ ] Navigate form with keyboard only (Tab, Enter, Space)
- [ ] Submit form with keyboard
- [ ] Verify thank-you page is keyboard-accessible
- [ ] Test with screen reader (NVDA, JAWS, VoiceOver)
- [ ] Verify all content is announced
- [ ] Verify button has proper label

## Performance Testing
- [ ] Submit form with DevTools Performance tab open
- [ ] Verify no JavaScript errors in console
- [ ] Verify AJAX request completes in < 500ms
- [ ] Verify thank-you page renders in < 100ms
- [ ] Verify smooth animations (60fps)

## Cross-Browser Testing
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (macOS/iOS)
- [ ] Mobile Chrome (Android)
- [ ] Mobile Safari (iOS)

## Security Testing
- [ ] Verify all admin inputs are properly sanitized
- [ ] Verify no XSS vulnerabilities in title/message
- [ ] Verify nonce validation on save
- [ ] Verify capability checks (`manage_options`)

## Regression Testing
- [ ] Verify existing forms still work
- [ ] Verify old submissions display correctly in admin
- [ ] Verify export functionality still works
- [ ] Verify conditional logic still works
- [ ] Verify multi-page navigation still works
- [ ] Verify all field types still work

## Navigation Controls Testing (Critical)

### First Page (Page 1 of N)
- [ ] Previous button is hidden
- [ ] Next button is visible
- [ ] Submit button is hidden
- [ ] Progress shows "Página 1 de N"
- [ ] Clicking Next advances to page 2

### Middle Page (e.g., Page 2 of 3)
#### With allowBackwardsNav = true
- [ ] Previous button is visible
- [ ] Next button is visible
- [ ] Submit button is hidden
- [ ] Progress shows "Página 2 de 3"
- [ ] Clicking Previous goes to page 1
- [ ] Clicking Next goes to page 3

#### With allowBackwardsNav = false
- [ ] Previous button is hidden
- [ ] Next button is visible
- [ ] Submit button is hidden
- [ ] Progress shows "Página 2 de 3"
- [ ] Clicking Next goes to page 3

### Last Page (Page N of N)
- [ ] Previous button is visible (if allowBackwardsNav = true)
- [ ] Next button is hidden
- [ ] Submit button is visible
- [ ] Progress shows "Página N de N"
- [ ] Clicking Submit submits the form
- [ ] During submission: all buttons show disabled state (opacity 0.6)

### Conditional Navigation
#### Auto-submit from middle page
- [ ] Create form with conditional rule: "If field X = value Y, submit"
- [ ] Fill field X with value Y on page 2 (of 3 pages)
- [ ] Click Next
- [ ] Verify form submits immediately (no page 3 shown)
- [ ] Verify thank-you page appears

#### Jump to last page
- [ ] Create form with conditional rule: "If field X = value Y, go to page 3"
- [ ] Fill field X with value Y on page 1 (of 3 pages)
- [ ] Click Next
- [ ] Verify form shows page 3 (page 2 is skipped)
- [ ] Verify Submit button is visible (not Next)

### Mobile Layout (< 768px)
- [ ] All buttons stack vertically
- [ ] Button order on mobile: Progress → Next/Submit → Previous
- [ ] All buttons are full-width
- [ ] Touch targets are at least 44×44 px
- [ ] No overlapping buttons

### Submitting State
- [ ] Click Submit on last page
- [ ] Verify Submit button shows "Enviando..." text
- [ ] Verify Submit button has is-disabled class (opacity 0.6)
- [ ] Verify Previous button is disabled during submission
- [ ] Verify all buttons re-enable after submission completes
- [ ] If submission fails: verify error message appears and buttons re-enable

### ARIA & Screen Reader
- [ ] Previous button has aria-label with page context
- [ ] Next button has aria-label with page context
- [ ] Submit button has aria-label with page context
- [ ] Hidden buttons have aria-hidden="true"
- [ ] Disabled buttons have aria-disabled="true"
- [ ] Navigate form with keyboard only (Tab, Enter)
- [ ] Test with screen reader (NVDA/JAWS/VoiceOver)

## Final Verification
- [ ] No console errors
- [ ] No PHP warnings/notices
- [ ] No broken styles
- [ ] No broken functionality
- [ ] Meets all acceptance criteria
- [ ] Ready for production

---

## Test Results Summary

**Date:** _______________
**Tester:** _______________
**Environment:** _______________

**Total Tests:** ___ / ___
**Pass Rate:** ___%

**Critical Issues:** _______________
**Minor Issues:** _______________
**Recommendations:** _______________

**Approved for Production:** [ ] YES [ ] NO

**Signature:** _______________
