# EIPSI Forms - Customization Panel Guide

## Overview

The EIPSI Forms plugin now includes a comprehensive **FormStylePanel** - a FormGent-inspired inspector panel that provides complete control over form styling with live preview. This panel transforms the form container into a fully customizable clinical research tool while maintaining accessibility and professional aesthetics.

## Features

### 🎨 Theme Presets

Apply professionally designed themes optimized for clinical research with one click:

1. **Clinical Blue** (Default)
   - Professional medical research aesthetic
   - EIPSI blue (#005a87) primary color
   - High contrast, trustworthy appearance
   - Recommended for most psychotherapy research contexts

2. **Minimal White**
   - Clean and minimal for distraction-free assessments
   - Subtle navy accent (#2c5aa0)
   - Off-white backgrounds for reduced eye strain
   - Ideal for long questionnaires

3. **Warm Neutral**
   - Warm and approachable tones
   - Earth-tone brown (#8b6f47) for comfort
   - Serif headings (Georgia) for warmth
   - Perfect for sensitive clinical interviews

4. **High Contrast**
   - Maximum readability for visually impaired participants
   - Bold colors and thick borders
   - Larger base font size (18px)
   - WCAG AAA compliant throughout

### 🎨 Colors Panel

Complete color customization with accessibility warnings:

#### Brand Colors
- **Primary**: Main brand color (buttons, links, focus states)
- **Primary Hover**: Darker shade for hover states
- **Secondary**: Light accent color for backgrounds

#### Background & Text
- **Background**: Main form background
- **Text**: Primary text color
- **Text Muted**: Secondary text, helper text
- *Automatic WCAG AA contrast checking between text and background*

#### Input Fields
- **Input Background**: Field background color
- **Input Text**: Text color inside inputs
- **Input Border**: Default border color
- **Input Border (Focus)**: Border color when focused
- *Automatic contrast checking for input text/background*

#### Buttons
- **Button Background**: Primary button color
- **Button Text**: Button label color
- **Button Hover**: Darker background on hover
- *Automatic contrast checking for button accessibility*

#### Status & Feedback
- **Error**: Validation error messages (#ff6b6b)
- **Success**: Success messages (#28a745)
- **Warning**: Warning alerts (#ffc107)

#### Borders
- **Border**: General border color
- **Border Dark**: Darker borders for emphasis

### ✍️ Typography Panel

Professional typography controls for optimal readability:

#### Font Families
- **Heading Font**: Font for titles and headings
- **Body Font**: Font for body text and labels
- Options include: System Default, Arial, Georgia, Helvetica, Times New Roman, Courier New, Verdana

#### Font Sizes
- **Base Size**: Body text (recommended: 16px minimum)
- **Heading 1 Size**: Main page titles
- **Heading 2 Size**: Section headings
- **Heading 3 Size**: Subsection headings
- **Small Text Size**: Helper text, footnotes

#### Font Weights
- **Normal Weight**: Regular text (100-900)
- **Medium Weight**: Emphasis (100-900)
- **Bold Weight**: Strong emphasis (100-900)

#### Line Heights
- **Base Line Height**: Body text (recommended: 1.6-1.8)
- **Heading Line Height**: Titles (recommended: 1.3-1.4)

### 📐 Spacing & Layout Panel

Control breathing room and visual hierarchy:

#### Key Spacing
- **Container Padding**: Space around form content (0-5rem)
- **Field Gap**: Vertical space between fields (0.5-4rem)
- **Section Gap**: Space between major sections (1-5rem)

#### Spacing Scale
Fine-tune the complete spacing system:
- **Extra Small** (xs): Tight spacing
- **Small** (sm): Compact spacing
- **Medium** (md): Standard spacing
- **Large** (lg): Generous spacing
- **Extra Large** (xl): Maximum breathing room

### 🔲 Borders & Radius Panel

Configure border styles and corner radius:

#### Border Radius
- **Small Radius**: Small elements (0-20px)
- **Medium Radius**: Inputs and buttons (0-30px)
- **Large Radius**: Containers and sections (0-40px)

#### Border Width & Style
- **Border Width**: Default border thickness (0-10px)
- **Focus Border Width**: Thicker border for focused elements (0-10px)
- **Border Style**: Solid, Dashed, Dotted, or None

### ✨ Shadows & Effects Panel

Add depth and visual feedback:

- **Small Shadow**: Subtle elevation for small elements
- **Medium Shadow**: Standard card depth
- **Large Shadow**: Prominent elevation
- **Focus Shadow**: Ring effect for focused elements

CSS box-shadow syntax supported (e.g., `0 2px 8px rgba(0, 90, 135, 0.08)`)

### ⚡ Hover & Interaction Panel

Configure animation and interaction feedback:

- **Transition Duration**: Animation speed (e.g., 0.2s or 200ms)
- **Transition Timing**: Easing function (linear, ease, ease-in, ease-out, ease-in-out)
- **Hover Scale**: Growth on hover (e.g., 1.02 for 2% growth)
- **Focus Outline Width**: Accessibility outline thickness (recommended: 2-3px)
- **Focus Outline Offset**: Space between element and outline

## Accessibility Features

### WCAG AA Contrast Warnings

The panel automatically checks color contrast ratios and displays warnings when combinations fail to meet WCAG AA standards (4.5:1 minimum):

```
⚠️ Contrast Warning: Insufficient contrast (3.2:1). 
Minimum 4.5:1 required for accessibility.
```

Warnings appear in three contexts:
1. **Text/Background**: Main content contrast
2. **Input Text/Background**: Form field contrast
3. **Button Text/Background**: Button label contrast

When contrast is sufficient, warnings disappear automatically.

### Accessibility Best Practices

The panel includes inline guidance:
- Minimum 16px font size for base text
- 1.6-1.8 line height for comfortable reading
- 2-3px focus outline width for keyboard navigation
- Touch targets minimum 44×44px (via spacing)

## Live Preview

All changes update the editor preview **instantly** via CSS custom properties. The system:

1. Updates `styleConfig` attribute on every change
2. Serializes config to CSS variables
3. Applies inline styles to editor preview wrapper
4. Persists to database on save

No page reload required - see changes as you make them.

## Reset to Defaults

Click **Reset to Default** to restore the Clinical Blue theme:
- Confirmation dialog prevents accidental resets
- Restores all 60+ design tokens
- Reverts to EIPSI's professional defaults

## Usage Workflow

### Scenario 1: Apply a Preset Theme

1. Select Form Container block
2. Open Inspector sidebar (right panel)
3. Expand **🎨 Theme Presets** panel
4. Click desired preset (Clinical Blue, Minimal White, etc.)
5. Preview updates instantly
6. Save post/page to persist

### Scenario 2: Customize Colors

1. Select Form Container block
2. Expand **🎨 Colors** panel
3. Click color indicator next to color name
4. Choose from preset colors or use custom color picker
5. Watch for contrast warnings
6. Adjust if warnings appear
7. Save when satisfied

### Scenario 3: Adjust Typography

1. Expand **✍️ Typography** panel
2. Select font families from dropdowns
3. Adjust sizes using text inputs (e.g., "18px", "1.5rem")
4. Use sliders for font weights (100-900)
5. Set line heights (unitless or with units)
6. Preview updates immediately

### Scenario 4: Fine-Tune Spacing

1. Expand **📐 Spacing & Layout** panel
2. Use sliders to adjust padding, gaps
3. Changes measured in rem for responsive scaling
4. Preview shows spacing changes live
5. Useful for mobile optimization

### Scenario 5: Custom Shadows

1. Expand **✨ Shadows & Effects** panel
2. Edit shadow values using CSS syntax
3. Example: `0 4px 12px rgba(0, 90, 135, 0.15)`
4. Components (x-offset, y-offset, blur, spread, color)
5. Set to `none` to disable shadows

## Technical Details

### Design Token Architecture

The system uses a centralized design token approach:

```javascript
// Tokens stored in styleConfig attribute
{
  colors: { primary: '#005a87', ... },      // 18 color tokens
  typography: { fontSizeBase: '16px', ... }, // 12 typography tokens
  spacing: { containerPadding: '2.5rem', ... }, // 8 spacing tokens
  borders: { radiusMd: '12px', ... },       // 6 border tokens
  shadows: { md: '0 4px...', ... },         // 4 shadow tokens
  interactivity: { transitionDuration: '0.2s', ... } // 5 interaction tokens
}
```

### CSS Variable Mapping

Tokens serialize to CSS custom properties:

```css
.eipsi-form-container-preview {
  --eipsi-color-primary: #005a87;
  --eipsi-font-size-base: 16px;
  --eipsi-spacing-container-padding: 2.5rem;
  /* ... 60+ variables */
}
```

All form components reference these variables:

```css
.eipsi-form input {
  background: var(--eipsi-color-input-bg, #ffffff);
  color: var(--eipsi-color-input-text, #2c3e50);
  border: var(--eipsi-border-width, 1px) solid var(--eipsi-color-input-border);
}
```

### Backward Compatibility

Legacy attributes (`backgroundColor`, `textColor`, etc.) automatically migrate to `styleConfig` on first load. Old forms render identically without manual intervention.

## Best Practices

### For Clinical Research Forms

1. **Start with Clinical Blue preset** - optimized for medical contexts
2. **Maintain high contrast** - ensure all text passes WCAG AA
3. **Use 16px+ base font** - improves readability for all ages
4. **Generous spacing** - reduces cognitive load during assessments
5. **Test on mobile** - many participants use phones/tablets

### For Long Questionnaires

1. **Minimal White preset** - reduces visual fatigue
2. **Increase line height** - 1.7-1.8 for comfort
3. **Larger field gaps** - 2rem+ for breathing room
4. **Subtle shadows** - minimal visual noise
5. **Soft colors** - avoid harsh contrasts

### For Sensitive Topics

1. **Warm Neutral preset** - more approachable
2. **Serif headings** - Georgia creates warmth
3. **Earth tones** - browns and warm grays
4. **Rounded corners** - 14px+ for softness
5. **Smooth transitions** - 0.25s ease-out

## Testing Checklist

Before publishing a customized form:

- [ ] All text/background combinations pass contrast check
- [ ] No contrast warnings displayed
- [ ] Form readable on mobile devices
- [ ] Font size 16px minimum for body text
- [ ] Focus states clearly visible (outline + shadow)
- [ ] Buttons have sufficient size/padding
- [ ] Hover states provide clear feedback
- [ ] Form tested with screen reader
- [ ] Changes saved and persist after reload
- [ ] Frontend matches editor preview

## Mobile Considerations

The responsive CSS automatically adjusts for smaller screens:

- Container padding reduces to maintain space
- Font sizes remain readable (no zoom needed)
- Touch targets minimum 44px height
- Horizontal layouts stack vertically
- Sliders remain usable with thumb dragging

Test your customizations at these breakpoints:
- **Mobile**: 320px - 480px
- **Tablet**: 768px - 1024px
- **Desktop**: 1200px+

## Troubleshooting

### Changes Not Appearing

1. Ensure Form Container block is selected
2. Check Inspector sidebar is open
3. Save post/page after changes
4. Clear browser cache if needed
5. Check for WordPress block validation errors

### Contrast Warnings Won't Clear

1. Use color picker to adjust text or background
2. Aim for ratio 4.5:1 or higher
3. Use [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/) to verify
4. Consider High Contrast preset if issues persist

### Preset Doesn't Apply

1. Click preset thumbnail again
2. Check for JavaScript console errors
3. Ensure styleConfig attribute exists
4. Try Reset to Default first, then apply preset

### Live Preview Not Updating

1. Ensure block is selected
2. Check webpack build completed successfully
3. Hard refresh browser (Ctrl+Shift+R)
4. Check browser console for errors

## Developer Notes

### Extending the Panel

To add new controls:

1. Update `DEFAULT_STYLE_CONFIG` in `src/utils/styleTokens.js`
2. Add corresponding CSS variable in `serializeToCSSVariables()`
3. Add control to appropriate PanelBody in `FormStylePanel.js`
4. Use `updateConfig(category, key, value)` helper
5. Reference variable in CSS: `var(--eipsi-new-token, fallback)`

### Adding New Presets

Edit `src/utils/stylePresets.js`:

```javascript
const NEW_PRESET = {
  name: 'Custom Theme',
  description: 'Description for preset',
  config: {
    colors: { /* ... */ },
    typography: { /* ... */ },
    // ... other categories
  }
};

export const STYLE_PRESETS = [
  CLINICAL_BLUE,
  MINIMAL_WHITE,
  WARM_NEUTRAL,
  HIGH_CONTRAST,
  NEW_PRESET, // Add here
];
```

### Custom Contrast Checking

Use the contrast checker utility:

```javascript
import { getContrastRating, passesWCAGAA } from '../utils/contrastChecker';

const rating = getContrastRating('#2c3e50', '#ffffff');
// { passes: true, level: 'AAA', ratio: '12.63', message: '...' }

if (!passesWCAGAA(textColor, bgColor)) {
  // Show warning
}
```

## Files Reference

### Core Components
- `/src/components/FormStylePanel.js` - Main panel component
- `/src/components/FormStylePanel.css` - Panel styling
- `/src/blocks/form-container/edit.js` - Integration point

### Utilities
- `/src/utils/styleTokens.js` - Token system and serialization
- `/src/utils/contrastChecker.js` - WCAG contrast validation
- `/src/utils/stylePresets.js` - Pre-configured themes

### Styling
- `/assets/css/eipsi-forms.css` - Frontend CSS with token references

## Support & Feedback

For issues or feature requests related to the customization panel:

1. Check this guide and troubleshooting section
2. Verify WordPress and plugin versions are current
3. Test with default preset to isolate issues
4. Document steps to reproduce
5. Include browser console errors if present

## Version History

### v2.2 (Current)
- FormGent-inspired comprehensive customization panel
- 7 collapsible sections (Presets, Colors, Typography, Spacing, Borders, Shadows, Interaction)
- 4 clinical presets with visual thumbnails
- Real-time WCAG AA contrast checking
- 60+ design tokens with live preview
- Inline accessibility guidance
- Reset to defaults functionality

### Future Enhancements
- Export/import custom themes
- Save personal preset library
- Advanced shadow builder with visual controls
- Gradient color support
- Animation presets
- Dark mode toggle
- Custom font upload support
