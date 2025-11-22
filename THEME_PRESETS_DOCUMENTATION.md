# Theme Presets Documentation
## EIPSI Forms Plugin - Visual Design System

**Version:** 2.0 (Refreshed January 2025)  
**WCAG Compliance:** All presets meet Level AA requirements (4.5:1 text, 3:1 UI components)

---

## Overview

The EIPSI Forms plugin provides **4 professionally designed theme presets** with **Universal Dark Mode Toggle** support. Each preset is optimized for different clinical research contexts and participant needs. All presets can be toggled between light and dark modes for optimal viewing comfort using a single, accessible toggle button.

### Quick Selection Guide

| Preset | Best For | Visual Identity | Key Features |
|--------|----------|-----------------|--------------|
| **Clinical Blue** | General medical research | Professional EIPSI blue branding | Balanced, trustworthy, corporate |
| **Minimal White** | Sensitive assessments | Ultra-clean, distraction-free | Sharp corners, no shadows, generous spacing |
| **Warm Neutral** | Psychotherapy research | Inviting, approachable | Rounded corners, serif headings, warm tones |
| **Serene Teal** | Stress/anxiety studies | Calming, therapeutic | Soft teal palette, balanced curves |

### Universal Dark Mode Toggle 🌙

All presets now support dark mode through a single toggle button:
- **Location:** Header (top-right on desktop, fixed bottom-right on mobile)
- **Activation:** Click toggle or press Ctrl/Cmd + Shift + D
- **Persistence:** Theme choice saved in localStorage
- **Coverage:** Complete form re-theming (all elements)
- **Accessibility:** WCAG AAA compliant with keyboard support

---

## 1. Clinical Blue (Default)

### Visual Identity
Professional medical research aesthetic with EIPSI blue branding, balanced spacing, subtle shadows, and modern sans-serif typography.

### Color Palette
```css
Primary: #005a87 (EIPSI Blue)
Primary Hover: #003d5b
Text: #2c3e50 (Dark Gray)
Background: #ffffff (White)
Border: #64748b (Medium Gray)
Error: #d32f2f (Clinical Red)
Success: #198754 (Professional Green)
Warning: #b35900 (Attention Brown)
```

### Typography
- **Headings:** System default (Apple System/Segoe UI/Roboto)
- **Body:** System default
- **Base Size:** 16px
- **H1:** 2rem (32px)
- **Line Height:** 1.6 (body), 1.3 (headings)

### Spacing & Borders
- **Container Padding:** 2.5rem (40px)
- **Border Radius:** 8-12px (medium curves)
- **Shadows:** Subtle with EIPSI blue tint
- **Field Gap:** 1.5rem

### Use Cases
- General clinical trials
- Medical surveys
- Corporate research initiatives
- EIPSI-branded forms

### Contrast Ratios (WCAG AA ✓)
- Text vs Background: 10.98:1 (AAA)
- Button Text: 7.47:1
- Borders: 4.76:1
- All semantic colors: 4.5:1+

---

## 2. Minimal White

### Visual Identity
Ultra-clean minimalist design with sharp lines, abundant white space, muted slate accents, and no visual distractions (no shadows).

### Color Palette
```css
Primary: #475569 (Slate Gray)
Primary Hover: #1e293b
Text: #0f172a (Near Black)
Background: #ffffff (Pure White)
Border: #64748b (Visible Gray)
Error: #c53030
Success: #28744c
Warning: #b35900
```

### Typography
- **Headings:** System default
- **Body:** System default
- **Base Size:** 16px
- **H1:** 1.875rem (30px)
- **Line Height:** 1.7 (body), 1.25 (headings - tight)

### Spacing & Borders
- **Container Padding:** 3.5rem (56px - most spacious)
- **Border Radius:** 4-6px (sharp, minimal)
- **Shadows:** None (flat design)
- **Field Gap:** 2rem (generous)

### Use Cases
- Sensitive mental health assessments
- Mindfulness studies
- Distraction-free cognitive tasks
- Minimalist brand alignment

### Contrast Ratios (WCAG AA ✓)
- Text vs Background: 17.85:1 (AAA)
- Button Text: 7.58:1
- Borders: 4.76:1
- Highest contrast of non-HC presets

---

## 3. Warm Neutral

### Visual Identity
Warm and approachable with rounded corners, inviting serif typography for headings, cozy brown/tan tones, and gentle shadows.

### Color Palette
```css
Primary: #8b6f47 (Warm Brown)
Primary Hover: #6b5437
Text: #3d3935 (Warm Dark Gray)
Background: #fdfcfa (Warm White)
Border: #8b7a65 (Tan)
Error: #c53030
Success: #2a7850
Warning: #b04d1f (Warm Orange-Brown)
```

### Typography
- **Headings:** Georgia (serif - warm, traditional)
- **Body:** System default (readable)
- **Base Size:** 16px
- **H1:** 2rem (32px)
- **Line Height:** 1.7 (body - comfortable), 1.35 (headings)

### Spacing & Borders
- **Container Padding:** 2.5rem
- **Border Radius:** 10-14px (soft, rounded)
- **Shadows:** Warm-toned with brown tint
- **Field Gap:** 1.75rem

### Use Cases
- Psychotherapy research
- Quality of life assessments
- Patient comfort questionnaires
- Warm, inviting brand contexts

### Contrast Ratios (WCAG AA ✓)
- Text vs Background: 11.16:1 (AAA)
- Button Text: 4.71:1
- Borders: 4.04:1
- Warm tones optimized for readability

---

## 4. Serene Teal

### Visual Identity
Calming teal tones with balanced design for therapeutic assessments. Soft curves, modern sans-serif, and gentle shadows with teal tints.

### Color Palette
```css
Primary: #0e7490 (Deep Teal)
Primary Hover: #155e75
Text: #0c4a6e (Teal-Gray)
Background: #ffffff (White)
Background Subtle: #f0f9ff (Very Light Blue)
Border: #0891b2 (Visible Teal)
Error: #dc2626
Success: #047857 (Teal-Green)
Warning: #b35900
```

### Typography
- **Headings:** System default
- **Body:** System default
- **Base Size:** 16px
- **H1:** 2rem (32px)
- **Line Height:** 1.65 (body), 1.3 (headings)

### Spacing & Borders
- **Container Padding:** 2.75rem
- **Border Radius:** 10-16px (balanced curves)
- **Shadows:** Soft with teal tint
- **Field Gap:** 1.75rem

### Use Cases
- Stress reduction studies
- Anxiety assessments
- Mindfulness research
- Therapeutic interventions
- Calming brand contexts

### Contrast Ratios (WCAG AA ✓)
- Text vs Background: 9.46:1 (AAA)
- Button Text: 5.36:1
- Borders: 3.68:1
- Success: 5.48:1

---

## Design Token Comparison

### Border Radius (Roundness)
| Preset | Small | Medium | Large | Feel |
|--------|-------|--------|-------|------|
| Clinical Blue | 8px | 12px | 20px | Professional |
| Minimal White | 4px | 6px | 8px | Sharp, clean |
| Warm Neutral | 10px | 14px | 20px | Soft, inviting |
| Serene Teal | 10px | 16px | 24px | Balanced curves |

### Container Padding (Spaciousness)
| Preset | Padding | Feel |
|--------|---------|------|
| Clinical Blue | 2.5rem (40px) | Balanced |
| Minimal White | 3.5rem (56px) | Most spacious |
| Warm Neutral | 2.5rem (40px) | Cozy |
| Serene Teal | 2.75rem (44px) | Comfortable |

### Shadows
| Preset | Usage | Effect |
|--------|-------|--------|
| Clinical Blue | Subtle with blue tint | Depth |
| Minimal White | None | Flat, clean |
| Warm Neutral | Warm-toned | Gentle depth |
| Serene Teal | Soft with teal tint | Calming depth |

### Font Size (H1)
| Preset | Desktop | Feel |
|--------|---------|------|
| Clinical Blue | 2rem (32px) | Standard |
| Minimal White | 1.875rem (30px) | Subtle |
| Warm Neutral | 2rem (32px) | Warm |
| Serene Teal | 2rem (32px) | Balanced |

---

## Implementation Notes

### Applying Presets
Presets can be applied via the **FormStylePanel** in the WordPress block editor:
1. Select any EIPSI Forms block
2. Open "🎨 Theme Presets" panel in sidebar
3. Click desired preset tile
4. All form elements update instantly

### Preview Tiles
Each preset preview shows:
- Background color (subtle background)
- Button sample (with actual border radius)
- Text sample (with actual font family)
- Border color
- Shadow effect (if applicable)

### Customization
After applying a preset:
- All individual color/typography/spacing settings remain editable
- Manual changes clear the "active preset" indicator
- Reset to Default button restores Clinical Blue

---

## WCAG Compliance Summary

### Testing Methodology
All presets validated using automated contrast ratio calculation (WCAG 2.1):
- **Text colors:** Minimum 4.5:1 ratio (Level AA)
- **UI components (borders):** Minimum 3:1 ratio (Level AA)
- **Large text (≥18pt):** Minimum 3:1 ratio (Level AA)

### Validation Results
```
✓ Clinical Blue    12/12 tests passed
✓ Minimal White    12/12 tests passed
✓ Warm Neutral     12/12 tests passed
✓ Serene Teal      12/12 tests passed
```

**Note:** Dark mode uses a separate color system with WCAG AAA compliance (7:1+ text contrast).

**Validation Script:** `wcag-contrast-validation.js` in plugin root

---

## Clinical Design Rationale

### Color Psychology

**Clinical Blue:** Trust, professionalism, medical authority  
**Minimal White:** Clarity, purity, mental space  
**Warm Neutral:** Comfort, safety, therapeutic alliance  
**Serene Teal:** Calm, healing, stress reduction  
**Dark Mode:** Eye comfort, modern professionalism, reduced glare in low-light environments

### Typography Choices

- **System fonts (all presets):** Familiar, fast-loading, reduces cognitive load
- **Georgia serif (Warm Neutral only):** Traditional, warm, literary feel for psychotherapy

### Spacing Strategy

- **Generous spacing (Minimal White):** Reduces overwhelm in sensitive assessments
- **Balanced spacing (Clinical Blue, Warm Neutral, Serene Teal):** Professional without being sterile

---

## Migration from v1.x Presets

### What Changed
1. **Minimal White:** Changed from blue accent (#2c5aa0) to slate gray (#475569) for better distinction
2. **All Presets:** Borders darkened from ~1.2:1 to 3:1+ contrast for WCAG compliance
3. **Serene Teal:** New preset added for therapeutic contexts (January 2025)
4. **Dark EIPSI:** New dark mode preset added for eye strain reduction (November 2025)
5. **Preview System:** Now shows button samples and border radius, not just color swatches

### Breaking Changes
- Border colors are now darker and more visible (intentional for accessibility)
- Minimal White no longer uses blue primary (use Clinical Blue if blue branding required)

### Backward Compatibility
- Existing forms retain their styleConfig settings
- No automatic migration of colors
- Preset names unchanged (except new Serene Teal)

---

## Best Practices

### Choosing a Preset
1. **Consider participant population:** Elderly → High Contrast; Young adults → Minimal/Serene
2. **Match study context:** Medical → Clinical Blue; Therapy → Warm Neutral
3. **Test with actual users:** A/B test presets if unsure
4. **Brand alignment:** Use Clinical Blue for EIPSI branding

### Customization Guidelines
- **Maintain 4.5:1 text contrast** - FormStylePanel warns if violated
- **Test on actual devices** - Colors appear different on various screens
- **Consider color blindness** - All presets use colorblind-safe semantic colors
- **Validate custom colors** - Use built-in contrast warnings

### Performance
- All presets use CSS variables (no inline styles bloat)
- System fonts load instantly (no web font delay)
- Minimal DOM impact (theme switching is instant)

---

## Technical Reference

### Files Modified
- `src/utils/stylePresets.js` - Preset configurations
- `src/utils/styleTokens.js` - Default (Clinical Blue) configuration
- `src/components/FormStylePanel.js` - Preview rendering
- `src/components/FormStylePanel.css` - Preview styles

### CSS Variable Mapping
All presets export to 52 CSS variables:
```css
--eipsi-color-primary
--eipsi-color-text
--eipsi-color-background
--eipsi-border-radius-md
--eipsi-shadow-md
/* ... etc */
```

### Validation Script Usage
```bash
node wcag-contrast-validation.js
# Exit code 0 = all pass, 1 = failures
```

---

## Support & Feedback

For questions about preset selection for your study, contact the EIPSI research team.

**Last Updated:** January 2025  
**Next Review:** June 2025 (post-user testing feedback)

---

## Universal Dark Mode Toggle (v3.0)

### Overview

All EIPSI Forms presets now include **Universal Dark Mode** support with a semantic, accessible toggle system. The dark mode toggle provides:

- **Multiple Locations**: Header, footer, and mobile fixed position
- **localStorage Persistence**: User preference saved across sessions
- **System Preference Sync**: Respects `prefers-color-scheme` media query
- **Smooth Transitions**: 0.3s ease transitions for all color changes
- **No-JavaScript Fallback**: Toggles hidden when JS is disabled
- **WCAG AAA Compliance**: All contrast ratios meet AAA standards in both modes
- **Keyboard Shortcut**: Ctrl/Cmd + Shift + D to toggle

### Implementation Details

#### HTML Attributes

```html
<!-- Set on <html> element -->
<html data-theme="light" data-preset="clinical-blue">
```

- `data-theme`: Controls color mode (`"light"` or `"dark"`)
- `data-preset`: Controls base preset (`"clinical-blue"`, `"minimal-white"`, `"warm-neutral"`, `"serene-teal"`, `"dark-eipsi"`)

#### Toggle Button Locations

1. **Header Toggle**: Top-right of form, visible on desktop/tablet
2. **Footer Toggle**: Centered below form, visible on desktop/tablet
3. **Mobile Fixed Toggle**: Bottom-right corner, circular button (mobile only)

#### Dark Mode Color Adaptations

Each preset adapts its primary colors for optimal dark mode experience:

**Clinical Blue (Dark)**
```css
--eipsi-primary: #60a5fa (Lighter blue)
--eipsi-primary-hover: #3b82f6
--eipsi-background-subtle: #1e3a8a
```

**Serene Teal (Dark)**
```css
--eipsi-primary: #5eead4 (Cyan-teal)
--eipsi-primary-hover: #2dd4bf
--eipsi-background-subtle: #164e63
```

**Warm Neutral (Dark)**
```css
--eipsi-primary: #d4a574 (Warm tan)
--eipsi-primary-hover: #d97706
--eipsi-background-subtle: #5d4037
```

**Minimal White (Dark)**
```css
--eipsi-primary: #e2e8f0 (Light gray)
--eipsi-primary-hover: #f1f5f9
--eipsi-background-subtle: #334155
```

### Usage in Blocks

The dark mode system is automatically integrated into `form-container/save.js`:

```jsx
<header className="eipsi-header">
  <h2>{description || 'Formulario'}</h2>
  <button 
    type="button" 
    className="eipsi-toggle" 
    aria-label="Toggle dark mode"
  >
    🌙 Nocturno
  </button>
</header>
```

### JavaScript API

The toggle system exposes a global API for programmatic control:

```javascript
// Get current theme
window.eipsiTheme.getTheme() // returns 'light' or 'dark'

// Set theme manually
window.eipsiTheme.setTheme('dark')

// Toggle theme
window.eipsiTheme.toggle()
```

### Accessibility Features

- **Focus Indicators**: 3-4px solid outline on `:focus-visible`
- **Reduced Motion**: Respects `prefers-reduced-motion` (transitions → 0.01ms)
- **High Contrast Mode**: Enhanced borders (3px) in Windows High Contrast
- **Screen Reader Labels**: `aria-label` updates dynamically with theme state
- **Keyboard Navigation**: Full keyboard support + Ctrl/Cmd + Shift + D shortcut
- **NoScript Graceful Degradation**: Toggle buttons hidden without JavaScript

### Performance

- **Bundle Size**: ~2.3KB JS (minified) + ~3.1KB CSS
- **Load Impact**: Zero render-blocking, loads asynchronously
- **Storage**: Uses localStorage (negligible footprint)
- **Transitions**: Hardware-accelerated CSS transitions (transform, opacity)

### Testing Checklist

✅ Toggle works in header, footer, and mobile positions  
✅ Theme persists across page reloads  
✅ Respects system preference on first visit  
✅ Smooth transitions (0.3s) on all color changes  
✅ Loading state visual feedback  
✅ WCAG AAA contrast in both light and dark modes  
✅ Works with all 5 presets  
✅ Keyboard shortcut functional  
✅ NoScript fallback hides toggles  
✅ Mobile fixed button accessible with thumb  
✅ Lighthouse performance ≥92  
✅ axe-core accessibility: 0 errors

### Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari 14+, Chrome Android 90+)

### Customization Options (WordPress Customizer)

Administrators can set default theme via **Appearance → Customize → EIPSI Forms - Tema**:

- **Light**: Always start in light mode
- **Dark**: Always start in dark mode  
- **Auto**: Respect system preference (default)

---

## Changelog

### Version 3.0.0 (January 2025)
- ✅ Added Universal Dark Mode Toggle with 3 positions
- ✅ Removed High Contrast preset (superseded by dark mode + accessibility features)
- ✅ Reduced preset count: 6 → 5 (streamlined)
- ✅ Added semantic HTML attributes (`data-theme`, `data-preset`)
- ✅ Implemented localStorage persistence
- ✅ Added keyboard shortcut (Ctrl/Cmd + Shift + D)
- ✅ Enhanced mobile UX with fixed circular toggle

### Version 2.0.0 (January 2025)
- Added Serene Teal preset
- Added Dark EIPSI preset
- WCAG 2.1 AA validation across all presets
- Enhanced contrast ratios

### Version 1.0.0 (November 2024)
- Initial release with 4 presets
