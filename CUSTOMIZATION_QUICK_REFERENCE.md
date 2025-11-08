# EIPSI Forms - Customization Panel Quick Reference

## Panel Structure

```
InspectorControls
├── Form Settings (PanelBody)
│   ├── Form ID/Slug
│   ├── Submit Button Label
│   └── Description
│
└── FormStylePanel Component
    ├── 🎨 Theme Presets
    │   ├── Clinical Blue (default)
    │   ├── Minimal White
    │   ├── Warm Neutral
    │   ├── High Contrast
    │   └── Reset to Default Button
    │
    ├── 🎨 Colors
    │   ├── Brand Colors (primary, primaryHover, secondary)
    │   ├── Background & Text (background, text, textMuted)
    │   ├── Input Fields (inputBg, inputText, inputBorder, inputBorderFocus)
    │   ├── Buttons (buttonBg, buttonText, buttonHoverBg)
    │   ├── Status & Feedback (error, success, warning)
    │   └── Borders (border, borderDark)
    │
    ├── ✍️ Typography
    │   ├── Font Families (fontFamilyHeading, fontFamilyBody)
    │   ├── Font Sizes (fontSizeBase, fontSizeH1/H2/H3, fontSizeSmall)
    │   ├── Font Weights (fontWeightNormal, fontWeightMedium, fontWeightBold)
    │   └── Line Heights (lineHeightBase, lineHeightHeading)
    │
    ├── 📐 Spacing & Layout
    │   ├── Key Spacing (containerPadding, fieldGap, sectionGap)
    │   └── Spacing Scale (xs, sm, md, lg, xl)
    │
    ├── 🔲 Borders & Radius
    │   ├── Border Radius (radiusSm, radiusMd, radiusLg)
    │   └── Border Width & Style (width, widthFocus, style)
    │
    ├── ✨ Shadows & Effects
    │   └── Shadow Tokens (sm, md, lg, focus)
    │
    └── ⚡ Hover & Interaction
        ├── Transition (transitionDuration, transitionTiming)
        ├── Effects (hoverScale)
        └── Focus (focusOutlineWidth, focusOutlineOffset)
```

## Design Tokens Reference

### Colors (18 tokens)
```javascript
colors: {
  primary: '#005a87',           // Main brand color
  primaryHover: '#003d5b',      // Hover state
  secondary: '#e3f2fd',         // Light accent
  background: '#ffffff',         // Main background
  backgroundSubtle: '#f8f9fa',  // Subtle background
  text: '#2c3e50',              // Primary text
  textMuted: '#64748b',         // Secondary text
  inputBg: '#ffffff',           // Input background
  inputText: '#2c3e50',         // Input text
  inputBorder: '#e2e8f0',       // Input border
  inputBorderFocus: '#005a87',  // Input focus border
  buttonBg: '#005a87',          // Button background
  buttonText: '#ffffff',        // Button text
  buttonHoverBg: '#003d5b',     // Button hover
  error: '#ff6b6b',             // Error messages
  success: '#28a745',           // Success messages
  warning: '#ffc107',           // Warning messages
  border: '#e2e8f0',            // General borders
  borderDark: '#cbd5e0'         // Dark borders
}
```

### Typography (12 tokens)
```javascript
typography: {
  fontFamilyHeading: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
  fontFamilyBody: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
  fontSizeBase: '16px',
  fontSizeH1: '2rem',
  fontSizeH2: '1.75rem',
  fontSizeH3: '1.5rem',
  fontSizeSmall: '0.875rem',
  fontWeightNormal: '400',
  fontWeightMedium: '500',
  fontWeightBold: '700',
  lineHeightBase: '1.6',
  lineHeightHeading: '1.3'
}
```

### Spacing (8 tokens)
```javascript
spacing: {
  xs: '0.5rem',
  sm: '1rem',
  md: '1.5rem',
  lg: '2rem',
  xl: '2.5rem',
  containerPadding: '2.5rem',
  fieldGap: '1.5rem',
  sectionGap: '2rem'
}
```

### Borders (6 tokens)
```javascript
borders: {
  radiusSm: '8px',
  radiusMd: '12px',
  radiusLg: '20px',
  width: '1px',
  widthFocus: '2px',
  style: 'solid'
}
```

### Shadows (4 tokens)
```javascript
shadows: {
  sm: '0 2px 8px rgba(0, 90, 135, 0.08)',
  md: '0 4px 12px rgba(0, 90, 135, 0.1)',
  lg: '0 8px 25px rgba(0, 90, 135, 0.1)',
  focus: '0 0 0 3px rgba(0, 90, 135, 0.1)'
}
```

### Interactivity (5 tokens)
```javascript
interactivity: {
  transitionDuration: '0.2s',
  transitionTiming: 'ease',
  hoverScale: '1.02',
  focusOutlineWidth: '2px',
  focusOutlineOffset: '2px'
}
```

## CSS Variable Mapping

| Token | CSS Variable | Example Value |
|-------|-------------|---------------|
| colors.primary | `--eipsi-color-primary` | #005a87 |
| colors.background | `--eipsi-color-background` | #ffffff |
| typography.fontSizeBase | `--eipsi-font-size-base` | 16px |
| spacing.containerPadding | `--eipsi-spacing-container-padding` | 2.5rem |
| borders.radiusMd | `--eipsi-border-radius-md` | 12px |
| shadows.md | `--eipsi-shadow-md` | 0 4px 12px... |
| interactivity.transitionDuration | `--eipsi-transition-duration` | 0.2s |

## Code Examples

### Updating a Single Token

```javascript
// In FormStylePanel or any component with styleConfig access
const updateConfig = (category, key, value) => {
  const updated = {
    ...styleConfig,
    [category]: {
      ...styleConfig[category],
      [key]: value,
    },
  };
  setStyleConfig(updated);
};

// Usage
updateConfig('colors', 'primary', '#007bff');
updateConfig('spacing', 'containerPadding', '3rem');
updateConfig('typography', 'fontSizeBase', '18px');
```

### Applying a Preset

```javascript
import { STYLE_PRESETS } from '../utils/stylePresets';

const applyPreset = (preset) => {
  setStyleConfig(JSON.parse(JSON.stringify(preset.config)));
};

// Usage
const minimalWhite = STYLE_PRESETS[1]; // Minimal White
applyPreset(minimalWhite);
```

### Checking Contrast

```javascript
import { getContrastRating, passesWCAGAA } from '../utils/contrastChecker';

const textColor = '#2c3e50';
const bgColor = '#ffffff';

// Simple pass/fail
if (passesWCAGAA(textColor, bgColor)) {
  console.log('Accessible!');
}

// Detailed rating
const rating = getContrastRating(textColor, bgColor);
console.log(rating);
// {
//   passes: true,
//   level: 'AAA',
//   ratio: '12.63',
//   message: 'Excellent contrast (WCAG AAA)'
// }
```

### Using CSS Variables in Components

```css
/* Reference tokens in your CSS */
.my-custom-field {
  background: var(--eipsi-color-input-bg, #ffffff);
  color: var(--eipsi-color-input-text, #2c3e50);
  border: var(--eipsi-border-width, 1px) solid var(--eipsi-color-input-border, #e2e8f0);
  border-radius: var(--eipsi-border-radius-md, 12px);
  padding: var(--eipsi-spacing-sm, 1rem);
  font-size: var(--eipsi-font-size-base, 16px);
  transition: all var(--eipsi-transition-duration, 0.2s) var(--eipsi-transition-timing, ease);
}

.my-custom-field:focus {
  border-color: var(--eipsi-color-input-border-focus, #005a87);
  box-shadow: var(--eipsi-shadow-focus, 0 0 0 3px rgba(0, 90, 135, 0.1));
  outline: var(--eipsi-focus-outline-width, 2px) solid var(--eipsi-color-primary, #005a87);
  outline-offset: var(--eipsi-focus-outline-offset, 2px);
}
```

## Accessibility Guidelines

### Contrast Ratios
- **Normal text**: 4.5:1 minimum (WCAG AA)
- **Large text** (18pt+): 3:1 minimum (WCAG AA)
- **Best practice**: 7:1 for AAA compliance

### Font Sizes
- **Minimum base**: 16px (no browser zoom needed)
- **Small text**: 14px minimum (0.875rem)
- **Large headings**: 24px+ for hierarchy

### Interactive Elements
- **Focus outline**: 2-3px visible on all controls
- **Touch targets**: 44×44px minimum
- **Hover feedback**: Clear visual change
- **Keyboard navigation**: Full support

### Color Usage
- **Don't rely on color alone**: Use icons, text, or patterns
- **Test with tools**: Use browser DevTools or WebAIM checker
- **Consider colorblindness**: Test with simulators

## Common Workflows

### Workflow 1: Brand Matching
1. Start with closest preset (Clinical Blue or Minimal White)
2. Update `colors.primary` to match brand
3. Adjust `colors.primaryHover` (darker shade)
4. Update `colors.buttonBg` to match primary
5. Verify contrast warnings clear
6. Test on mobile device

### Workflow 2: Maximum Readability
1. Apply High Contrast preset
2. Increase `typography.fontSizeBase` to 18px
3. Set `typography.lineHeightBase` to 1.8
4. Increase `spacing.fieldGap` to 2rem
5. Increase `spacing.containerPadding` to 3rem
6. Test with screen reader

### Workflow 3: Participant Comfort
1. Apply Warm Neutral preset
2. Increase `borders.radiusMd` to 14px
3. Set `interactivity.transitionDuration` to 0.25s
4. Set `interactivity.transitionTiming` to ease-out
5. Increase `spacing.containerPadding` to 3rem
6. Soften `shadows.md` (reduce opacity to 0.08)

## File Locations

```
src/
├── components/
│   ├── FormStylePanel.js        # Main panel component
│   └── FormStylePanel.css       # Panel styles
│
├── utils/
│   ├── styleTokens.js           # Token system & serialization
│   ├── contrastChecker.js       # WCAG contrast validation
│   └── stylePresets.js          # Pre-configured themes
│
└── blocks/
    └── form-container/
        └── edit.js              # Integration point

assets/
└── css/
    └── eipsi-forms.css          # Frontend CSS with token refs
```

## Keyboard Shortcuts

When using the panel:
- **Tab**: Navigate between controls
- **Space/Enter**: Open color picker or activate button
- **Arrow keys**: Adjust range sliders
- **Escape**: Close color picker or dialog

## Performance Notes

- **Inline styles**: CSS variables applied via inline style attribute
- **No extra requests**: All tokens embedded in block attributes
- **Live preview**: Updates via React state, no DOM manipulation
- **Optimized serialization**: Only changed tokens saved to database
- **CSS cascade**: Fallback values prevent render blocking

## Testing Checklist

Before deploying customized forms:
- [ ] Run `npm run build` to compile changes
- [ ] Test all 4 presets to ensure they apply
- [ ] Verify no contrast warnings on default preset
- [ ] Check mobile responsive at 320px, 768px, 1200px
- [ ] Test keyboard navigation (Tab, Enter, Space, Arrows)
- [ ] Verify focus states are visible
- [ ] Test with screen reader (NVDA, JAWS, or VoiceOver)
- [ ] Save and reload - confirm styles persist
- [ ] Check frontend matches editor preview
- [ ] Test form submission with custom styles

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Preset doesn't apply | Click again, check console for errors, try Reset first |
| Contrast warning won't clear | Use WebAIM checker, adjust to 4.5:1 minimum |
| Changes don't save | Ensure block is selected, check validation errors |
| Preview doesn't update | Hard refresh (Ctrl+Shift+R), check console |
| Frontend doesn't match | Clear cache, check CSS enqueue order |
| Colors look wrong | Check for theme overrides, use !important sparingly |
| Spacing too tight on mobile | Use rem units, test at 320px width |
| Focus states invisible | Increase outline width, use high contrast colors |

## Support Resources

- **Full Documentation**: [CUSTOMIZATION_PANEL_GUIDE.md](./CUSTOMIZATION_PANEL_GUIDE.md)
- **Design Token System**: [CSS_REBUILD_DOCUMENTATION.md](./CSS_REBUILD_DOCUMENTATION.md)
- **Main README**: [README.md](./README.md)
- **WebAIM Contrast Checker**: https://webaim.org/resources/contrastchecker/
- **WCAG Guidelines**: https://www.w3.org/WAI/WCAG21/quickref/
