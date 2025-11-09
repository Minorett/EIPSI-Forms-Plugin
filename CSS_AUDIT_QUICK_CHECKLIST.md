# CSS Clinical Styles - Quick Reference Checklist

**Project:** EIPSI Forms Plugin  
**Version:** v2.2  
**Date:** 2024-01-15

---

## ✅ PASSING - Clinical Design Compliance

### Main Stylesheet (`assets/css/eipsi-forms.css`)
- [x] 52 CSS variables defined in :root
- [x] All variables use EIPSI Blue (#005a87) as primary
- [x] var() usage includes fallback values
- [x] Focus states on all interactive elements
- [x] Hover transitions (0.2s ease)
- [x] Disabled states with cursor:not-allowed
- [x] Accessibility (reduced motion, high contrast)
- [x] Responsive breakpoints (768px, 480px)
- [x] Minimal specificity (max 3-4 levels)
- [x] !important usage justified (9 instances)

### Component Styles
- [x] FormStylePanel uses EIPSI Blue (#005a87)
- [x] Focus outlines present (2px solid)
- [x] Contrast indicators functional
- [x] Responsive grid layout

### Admin Styles
- [x] WordPress admin aesthetics maintained
- [x] Enhanced contrast for readability
- [x] !important usage justified for WP overrides

### Editor Styles (11 files)
- [x] WordPress Blue (#0073aa) appropriate for editor
- [x] Visual distinction from frontend
- [x] Badge labels present

---

## ❌ FAILING - Critical Issues

### Block SCSS Files (10 files)

#### campo-texto/style.scss
- [ ] Uses CSS variables
- [ ] EIPSI Blue (#005a87) instead of #0073aa
- [ ] Dark text (#2c3e50) instead of white (#ffffff)
- [ ] Background: var(--eipsi-color-input-bg) instead of rgba(255,255,255,0.05)

#### campo-textarea/style.scss
- [ ] Uses CSS variables
- [ ] EIPSI Blue
- [ ] Dark text
- [ ] Proper backgrounds

#### campo-select/style.scss
- [ ] Uses CSS variables
- [ ] EIPSI Blue
- [ ] Dark text
- [ ] Proper backgrounds

#### campo-radio/style.scss
- [ ] Uses CSS variables (lines 13-14, 21, 41)
- [ ] EIPSI Blue (line 21)
- [ ] Dark text (line 41)
- [ ] Background: var(--eipsi-color-background) instead of rgba

#### campo-multiple/style.scss
- [ ] Uses CSS variables
- [ ] EIPSI Blue
- [ ] Dark text
- [ ] Proper backgrounds

#### campo-descripcion/style.scss
- [ ] Uses CSS variables
- [ ] EIPSI Blue (line 7: border-left)
- [ ] Dark text (line 8)
- [ ] Background: var(--eipsi-color-background-subtle)

#### campo-likert/style.scss
- [ ] Uses CSS variables (82+ hardcoded colors)
- [ ] EIPSI Blue (lines 53, 101, 146, 150, 157)
- [ ] Dark text (lines 113, 146)
- [ ] Proper backgrounds (lines 5, 37, 100)

#### vas-slider/style.scss
- [ ] Uses CSS variables (60+ hardcoded colors)
- [ ] EIPSI Blue (lines 51, 58, 76, 99, 127, 138)
- [ ] Dark text (lines 29, 78)
- [ ] Proper backgrounds (lines 6, 32, 39, 44, 56, 74, 97)

#### pagina/style.scss
- [ ] Uses CSS variables
- [ ] EIPSI Blue (if primary used)
- [ ] Dark text (line 15)
- [ ] Proper backgrounds (line 16: border)

#### form-container/style.scss
- [ ] Uses CSS variables (40+ hardcoded colors)
- [ ] EIPSI Blue (lines 62, 68, 99)
- [ ] Dark text (lines 6, 46, 89)
- [ ] Proper backgrounds (lines 26, 44, 49, 92)

---

## ⚠️ MINOR ISSUES - Main Stylesheet

### Hardcoded Values to Replace

- [ ] Line 342: Placeholder color `#adb5bd` → `var(--eipsi-color-text-muted, #64748b)`
- [ ] Lines 375-398: Textarea colors → CSS variables
- [ ] Lines 417-447: Select colors → CSS variables

---

## 🔧 Fix Template - Block SCSS

### Before (WRONG):
```scss
.eipsi-text-field {
    label {
        color: #ffffff;  // ❌ White text
    }
    
    input {
        background: rgba(255, 255, 255, 0.05);  // ❌ Transparent white
        border: 2px solid rgba(255, 255, 255, 0.15);  // ❌ White border
        color: #ffffff;  // ❌ White text
        
        &:focus {
            border-color: rgba(0, 115, 170, 0.6);  // ❌ WordPress blue
        }
    }
}
```

### After (CORRECT):
```scss
.eipsi-text-field {
    label {
        color: var(--eipsi-color-text, #2c3e50);  // ✅ Dark text
    }
    
    input {
        background: var(--eipsi-color-input-bg, #ffffff);  // ✅ Clinical white
        border: var(--eipsi-border-width-focus, 2px) 
                var(--eipsi-border-style, solid) 
                var(--eipsi-color-input-border, #e2e8f0);  // ✅ Clinical gray
        color: var(--eipsi-color-input-text, #2c3e50);  // ✅ Dark text
        
        &:focus {
            border-color: var(--eipsi-color-input-border-focus, #005a87);  // ✅ EIPSI blue
            box-shadow: var(--eipsi-shadow-focus, 0 0 0 3px rgba(0, 90, 135, 0.1));
        }
    }
}
```

---

## 🎨 Color Reference

### ✅ CORRECT Colors (EIPSI Clinical Palette)
| Color | Hex | Use Case | CSS Variable |
|-------|-----|----------|--------------|
| EIPSI Blue | `#005a87` | Primary, links, focus | `--eipsi-color-primary` |
| EIPSI Blue Dark | `#003d5b` | Hover states | `--eipsi-color-primary-hover` |
| Clinical White | `#ffffff` | Backgrounds | `--eipsi-color-background` |
| Subtle Gray | `#f8f9fa` | Secondary backgrounds | `--eipsi-color-background-subtle` |
| Soft Dark | `#2c3e50` | Body text | `--eipsi-color-text` |
| Muted Gray | `#64748b` | Helper text | `--eipsi-color-text-muted` |
| Border Gray | `#e2e8f0` | Input borders | `--eipsi-color-border` |
| Border Dark | `#cbd5e0` | Hover borders | `--eipsi-color-border-dark` |
| Error Red | `#ff6b6b` | Validation errors | `--eipsi-color-error` |
| Success Green | `#28a745` | Success messages | `--eipsi-color-success` |
| Warning Yellow | `#ffc107` | Warnings | `--eipsi-color-warning` |

### ❌ WRONG Colors (Should NOT Appear)
| Color | Hex | Issue | Replace With |
|-------|-----|-------|--------------|
| WordPress Blue | `#0073aa` | Wrong primary | `#005a87` (EIPSI Blue) |
| Pure White | `#ffffff` | Text on light backgrounds | `#2c3e50` (Soft Dark) |
| Transparent White | `rgba(255,255,255,0.05)` | Assumes dark background | `var(--eipsi-color-input-bg, #ffffff)` |

---

## 🧪 Quick Test Commands

### Check for Wrong Colors in Blocks
```bash
# Find WordPress Blue (#0073aa)
grep -rn "#0073aa\|rgba(0, 115, 170" src/blocks/*/style.scss

# Find white text (#ffffff)
grep -rn "color: #ffffff\|color: #fff" src/blocks/*/style.scss

# Find transparent white backgrounds
grep -rn "rgba(255, 255, 255," src/blocks/*/style.scss
```

### Check for CSS Variable Usage
```bash
# Should return results (currently returns NONE)
grep -rn "var(--eipsi-" src/blocks/*/style.scss
```

### Check Compiled Output
```bash
# Check what colors made it to build
grep -o "color:#[0-9a-f]\{3,6\}" build/style-index.css | sort | uniq -c
```

### Rebuild After Fixes
```bash
npm run build
```

---

## 📊 Progress Tracker

### High Priority (Block SCSS Migration)
- [ ] campo-texto/style.scss (72 lines)
- [ ] campo-textarea/style.scss (~60 lines)
- [ ] campo-select/style.scss (~50 lines)
- [ ] campo-radio/style.scss (54 lines)
- [ ] campo-multiple/style.scss (~60 lines)
- [ ] campo-descripcion/style.scss (28 lines)
- [ ] campo-likert/style.scss (172 lines)
- [ ] vas-slider/style.scss (151 lines)
- [ ] pagina/style.scss (38 lines)
- [ ] form-container/style.scss (130 lines)
- [ ] Rebuild and test (npm run build)
- [ ] Visual regression testing

**Estimated Time:** 4-6 hours

### Medium Priority (Main CSS Cleanup)
- [ ] Line 342: Placeholder color fix
- [ ] Lines 375-398: Textarea CSS variable migration
- [ ] Lines 417-447: Select CSS variable migration

**Estimated Time:** 1 hour

### Low Priority (Future Enhancements)
- [ ] FormStylePanel CSS variable integration
- [ ] Dark mode support (@media prefers-color-scheme)
- [ ] VAS slider scale toggle via CSS variable

**Estimated Time:** 4-5 hours

---

## 🎯 Success Criteria

### When All Boxes Checked:
1. ✅ All block SCSS files use CSS variables
2. ✅ No instances of #0073aa in block styles
3. ✅ No white text (#ffffff) in block styles
4. ✅ No transparent white backgrounds (rgba(255,255,255,0.XX))
5. ✅ `grep -r "var(--eipsi-" src/blocks/*/style.scss` returns 100+ matches
6. ✅ Forms on light backgrounds are legible
7. ✅ Customization panel changes affect all form elements
8. ✅ Build output (build/style-index.css) uses correct colors
9. ✅ Visual regression tests pass
10. ✅ Cross-browser testing (Chrome, Firefox, Safari, Edge) passes

---

## 📞 Quick Reference Links

- **Full Audit Report:** `CSS_CLINICAL_STYLES_AUDIT_REPORT.md`
- **Design Tokens Reference:** `src/utils/styleTokens.js` (lines 28-94)
- **Style Presets:** `src/utils/stylePresets.js`
- **Main Stylesheet:** `assets/css/eipsi-forms.css`
- **Build Output:** `build/style-index.css`

---

**Last Updated:** 2024-01-15  
**Status:** 🔴 Critical issues identified, migration needed  
**Next Action:** Begin block SCSS migration to CSS variables
