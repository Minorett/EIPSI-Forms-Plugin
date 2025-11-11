# Theme Presets Documentation
## EIPSI Forms Plugin - Visual Design System

**Version:** 2.0 (Refreshed January 2025)  
**WCAG Compliance:** All presets meet Level AA requirements (4.5:1 text, 3:1 UI components)

---

## Overview

The EIPSI Forms plugin provides **6 professionally designed theme presets** that offer dramatically distinct visual identities. Each preset is optimized for different clinical research contexts and participant needs.

### Quick Selection Guide

| Preset | Best For | Visual Identity | Key Features |
|--------|----------|-----------------|--------------|
| **Clinical Blue** | General medical research | Professional EIPSI blue branding | Balanced, trustworthy, corporate |
| **Minimal White** | Sensitive assessments | Ultra-clean, distraction-free | Sharp corners, no shadows, generous spacing |
| **Warm Neutral** | Psychotherapy research | Inviting, approachable | Rounded corners, serif headings, warm tones |
| **High Contrast** | Visually impaired participants | Maximum accessibility | Bold borders, large text, AAA ratios |
| **Serene Teal** | Stress/anxiety studies | Calming, therapeutic | Soft teal palette, balanced curves |
| **Dark EIPSI** | Evening studies, eye strain reduction | Dark mode with EIPSI branding | Dark blue background, light text, reduced glare |

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

## 4. High Contrast

### Visual Identity
Maximum accessibility with bold borders (2-3px), large text, no decorative shadows, and extreme contrast ratios (AAA level).

### Color Palette
```css
Primary: #0050d8 (Bright Blue)
Primary Hover: #003da6
Text: #000000 (Pure Black)
Background: #ffffff (Pure White)
Border: #000000 (Solid Black)
Error: #d30000 (Strong Red)
Success: #006600 (Strong Green)
Warning: #b35900
```

### Typography
- **Headings:** Arial (highly legible)
- **Body:** Arial (no serifs for clarity)
- **Base Size:** 18px (larger default)
- **H1:** 2.25rem (36px - largest)
- **Line Height:** 1.8 (body - very comfortable), 1.4 (headings)

### Spacing & Borders
- **Container Padding:** 2rem
- **Border Radius:** 4-6px (minimal curves)
- **Border Width:** 2px (thick for visibility)
- **Shadows:** None (no decorative effects)
- **Field Gap:** 1.75rem

### Use Cases
- Visually impaired participants
- Low vision studies
- Maximum accessibility requirements
- Screen reader + visual compliance

### Contrast Ratios (WCAG AAA ✓)
- Text vs Background: 21.00:1 (perfect)
- Button Text: 6.69:1
- Borders: 21.00:1
- All ratios exceed AAA (7:1)

---

## 5. Serene Teal (NEW)

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

## 6. Dark EIPSI (NEW)

### Visual Identity
Professional dark mode with EIPSI blue background and high-contrast light text. Designed to reduce eye strain during evening studies while maintaining EIPSI brand identity.

### Color Palette
```css
Primary: #22d3ee (Cyan Accent)
Primary Hover: #06b6d4
Text: #ffffff (White)
Text Muted: #94a3b8 (Light Gray)
Background: #005a87 (EIPSI Blue - Dark)
Background Subtle: #003d5b (Darker Blue)
Border: #cbd5e1 (Light Gray)
Button BG: #0e7490 (Dark Teal)
Button Text: #ffffff (White)
Error: #fecaca (Light Pink)
Success: #6ee7b7 (Light Green)
Warning: #fcd34d (Light Yellow)
```

### Typography
- **Headings:** System default
- **Body:** System default
- **Base Size:** 16px
- **H1:** 2rem (32px)
- **Line Height:** 1.65 (body), 1.3 (headings)

### Spacing & Borders
- **Container Padding:** 2.5rem (40px)
- **Border Radius:** 8-12px (medium curves)
- **Shadows:** Dark shadows (black with transparency)
- **Field Gap:** 1.75rem

### Use Cases
- Evening or night-time studies
- Long-duration assessments (reduce eye strain)
- Dark mode preference participants
- Extended screen time protocols
- EIPSI-branded dark theme

### Design Rationale
- **Dark background reduces glare** from screens in low-light environments
- **Light input fields** maintain familiarity and readability for form entries
- **Cyan accent** provides high visibility on dark blue without being harsh
- **EIPSI blue** maintained as primary background for brand consistency

### Contrast Ratios (WCAG AA ✓)
- Text vs Background: 7.47:1 (AAA)
- Text Muted vs Background Subtle: 4.50:1
- Button Text vs Button Background: 5.36:1
- Button Text vs Button Hover: 7.27:1
- Input Text vs Input Background: 13.88:1 (AAA)
- Error vs Background: 5.16:1
- Success vs Background: 4.90:1
- Borders: 5.03:1

---

## Design Token Comparison

### Border Radius (Roundness)
| Preset | Small | Medium | Large | Feel |
|--------|-------|--------|-------|------|
| Clinical Blue | 8px | 12px | 20px | Professional |
| Minimal White | 4px | 6px | 8px | Sharp, clean |
| Warm Neutral | 10px | 14px | 20px | Soft, inviting |
| High Contrast | 4px | 6px | 8px | Functional |
| Serene Teal | 10px | 16px | 24px | Balanced curves |
| Dark EIPSI | 8px | 12px | 16px | Professional |

### Container Padding (Spaciousness)
| Preset | Padding | Feel |
|--------|---------|------|
| Clinical Blue | 2.5rem (40px) | Balanced |
| Minimal White | 3.5rem (56px) | Most spacious |
| Warm Neutral | 2.5rem (40px) | Cozy |
| High Contrast | 2rem (32px) | Compact |
| Serene Teal | 2.75rem (44px) | Comfortable |
| Dark EIPSI | 2.5rem (40px) | Balanced |

### Shadows
| Preset | Usage | Effect |
|--------|-------|--------|
| Clinical Blue | Subtle with blue tint | Depth |
| Minimal White | None | Flat, clean |
| Warm Neutral | Warm-toned | Gentle depth |
| High Contrast | None | No distraction |
| Serene Teal | Soft with teal tint | Calming depth |
| Dark EIPSI | Dark shadows (black) | Dark mode depth |

### Font Size (H1)
| Preset | Desktop | Feel |
|--------|---------|------|
| Clinical Blue | 2rem (32px) | Standard |
| Minimal White | 1.875rem (30px) | Subtle |
| Warm Neutral | 2rem (32px) | Warm |
| High Contrast | 2.25rem (36px) | Largest |
| Serene Teal | 2rem (32px) | Balanced |
| Dark EIPSI | 2rem (32px) | Standard |

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
✓ High Contrast    12/12 tests passed (AAA)
✓ Serene Teal      12/12 tests passed
✓ Dark EIPSI       12/12 tests passed
```

**Validation Script:** `wcag-contrast-validation.js` in plugin root

---

## Clinical Design Rationale

### Color Psychology

**Clinical Blue:** Trust, professionalism, medical authority  
**Minimal White:** Clarity, purity, mental space  
**Warm Neutral:** Comfort, safety, therapeutic alliance  
**High Contrast:** Accessibility, inclusivity, clarity  
**Serene Teal:** Calm, healing, stress reduction  
**Dark EIPSI:** Eye comfort, modern professionalism, brand consistency in dark mode

### Typography Choices

- **System fonts (Clinical Blue, Minimal White, Serene Teal, Dark EIPSI):** Familiar, fast-loading, reduces cognitive load
- **Georgia serif (Warm Neutral):** Traditional, warm, literary feel for psychotherapy
- **Arial (High Contrast):** Maximum legibility for low vision

### Spacing Strategy

- **Generous spacing (Minimal White):** Reduces overwhelm in sensitive assessments
- **Balanced spacing (Clinical Blue, Warm Neutral, Serene Teal, Dark EIPSI):** Professional without being sterile
- **Compact spacing (High Contrast):** Reduces scroll distance for mobility-impaired users

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
