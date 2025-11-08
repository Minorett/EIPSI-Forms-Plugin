# FormStylePanel - Testing & QA Checklist

## Build Verification

- [x] `npm run build` completes without errors
- [x] Build output includes FormStylePanel CSS (eipsi-preset-grid)
- [x] Build output includes preset names (Clinical Blue, Minimal White, etc.)
- [x] Build size reasonable (~79K for index.js)
- [x] No TypeScript/ESLint errors blocking build

## Component Integration

### Panel Rendering
- [ ] Form Container block selected → Inspector sidebar shows panels
- [ ] "🎨 Theme Presets" panel visible at top
- [ ] 7 panels total: Presets, Colors, Typography, Spacing, Borders, Shadows, Interaction
- [ ] Panels collapsible (chevron icon)
- [ ] Panel descriptions display correctly

### Preset System
- [ ] 4 preset thumbnails visible in grid
- [ ] Preset thumbnails show preview colors correctly
- [ ] Clinical Blue preset marked as active by default
- [ ] Clicking preset applies immediately
- [ ] Active preset shows checkmark icon
- [ ] Preset names display: Clinical Blue, Minimal White, Warm Neutral, High Contrast
- [ ] Reset to Default button visible
- [ ] Reset confirmation dialog appears on click

## Color Controls

### Color Pickers
- [ ] 18 color controls render (Primary, Primary Hover, Secondary, etc.)
- [ ] Each control shows color indicator (current color swatch)
- [ ] Clicking indicator opens color picker
- [ ] Color picker shows preset colors (EIPSI Blue, White, etc.)
- [ ] Custom color input works
- [ ] Selecting color updates preview immediately
- [ ] Color changes persist after save

### Contrast Checking
- [ ] No warnings on default Clinical Blue preset
- [ ] Warning appears when text/background contrast < 4.5:1
- [ ] Warning shows for text/background combination
- [ ] Warning shows for input text/background
- [ ] Warning shows for button text/background
- [ ] Warning message includes ratio (e.g., "3.2:1")
- [ ] Warning disappears when contrast fixed
- [ ] Warning box styled correctly (yellow background)

## Typography Controls

- [ ] Font family dropdowns show options (System Default, Arial, Georgia, etc.)
- [ ] Heading font changes apply to preview
- [ ] Body font changes apply to preview
- [ ] Font size text inputs accept values (e.g., "18px", "1.5rem")
- [ ] Font weight sliders range 100-900 in steps of 100
- [ ] Line height inputs accept unitless values (e.g., "1.6")
- [ ] Helper text displays for key fields

## Spacing Controls

- [ ] Container padding slider (0-5rem)
- [ ] Field gap slider (0.5-4rem)
- [ ] Section gap slider (1-5rem)
- [ ] Spacing scale sliders (xs, sm, md, lg, xl)
- [ ] All spacing changes reflect in preview
- [ ] Values displayed next to sliders

## Border Controls

- [ ] Small radius slider (0-20px)
- [ ] Medium radius slider (0-30px)
- [ ] Large radius slider (0-40px)
- [ ] Border width slider (0-10px)
- [ ] Focus border width slider (0-10px)
- [ ] Border style dropdown (Solid, Dashed, Dotted, None)
- [ ] Changes apply to inputs and buttons in preview

## Shadow Controls

- [ ] Small shadow text input
- [ ] Medium shadow text input
- [ ] Large shadow text input
- [ ] Focus shadow text input
- [ ] CSS syntax accepted (e.g., "0 2px 8px rgba(0,0,0,0.1)")
- [ ] Invalid syntax handled gracefully

## Interaction Controls

- [ ] Transition duration text input
- [ ] Transition timing dropdown (linear, ease, ease-in, etc.)
- [ ] Hover scale text input
- [ ] Focus outline width slider (0-10px)
- [ ] Focus outline offset slider (0-10px)

## Live Preview

### Editor Preview Updates
- [ ] Color changes apply instantly
- [ ] Typography changes apply instantly
- [ ] Spacing changes apply instantly
- [ ] Border changes apply instantly
- [ ] Shadow changes apply instantly
- [ ] Interaction changes apply instantly
- [ ] Preview wrapper has inline styles with CSS variables
- [ ] Preview matches expected design

### Preview Consistency
- [ ] Form title reflects styles
- [ ] Form description reflects styles
- [ ] Field labels reflect styles
- [ ] Input fields reflect styles
- [ ] Submit button reflects styles
- [ ] Hover states work in preview
- [ ] Focus states work in preview

## Persistence

- [ ] Changes saved to block attributes
- [ ] styleConfig attribute updated on changes
- [ ] Reload page → styles persist
- [ ] Switch to another block → styles persist when returning
- [ ] Publish post → styles appear on frontend
- [ ] Frontend output matches editor preview

## Preset Workflows

### Clinical Blue (Default)
- [ ] Applies on fresh form
- [ ] EIPSI blue (#005a87) primary color
- [ ] High contrast maintained
- [ ] No contrast warnings
- [ ] Professional appearance

### Minimal White
- [ ] Applies when clicked
- [ ] Navy blue (#2c5aa0) primary
- [ ] Off-white backgrounds
- [ ] Clean, minimal aesthetic
- [ ] No contrast warnings

### Warm Neutral
- [ ] Applies when clicked
- [ ] Brown (#8b6f47) primary
- [ ] Warm earth tones
- [ ] Georgia serif headings visible
- [ ] Comfortable appearance
- [ ] No contrast warnings

### High Contrast
- [ ] Applies when clicked
- [ ] Bold blue (#0050d8) primary
- [ ] Black text on white
- [ ] Thick borders visible
- [ ] Larger font size (18px base)
- [ ] Maximum readability
- [ ] No shadows (set to "none")
- [ ] Passes WCAG AAA

## Reset Functionality

- [ ] Reset button always visible
- [ ] Confirmation dialog prevents accidents
- [ ] Clicking "OK" restores defaults
- [ ] Clicking "Cancel" keeps changes
- [ ] Active preset updates to Clinical Blue
- [ ] Preview updates immediately

## Accessibility

### Keyboard Navigation
- [ ] Tab moves between controls
- [ ] Enter/Space activates buttons
- [ ] Arrow keys adjust sliders
- [ ] Escape closes pickers/dialogs
- [ ] Focus visible on all controls
- [ ] Preset buttons keyboard accessible

### Screen Reader
- [ ] Panel titles announced
- [ ] Control labels announced
- [ ] Color values announced
- [ ] Slider values announced
- [ ] Warnings announced
- [ ] Helper text announced

### Visual
- [ ] High contrast mode compatible
- [ ] Focus indicators 2px minimum
- [ ] Color not sole indicator
- [ ] Text readable at 200% zoom
- [ ] Touch targets 44×44px minimum

## Mobile Editor

### Responsive Panel
- [ ] Panel usable on tablet (768px)
- [ ] Preset grid stacks on mobile
- [ ] Sliders touch-friendly
- [ ] Color pickers accessible
- [ ] No horizontal scroll

## Error Handling

- [ ] Invalid color syntax reverts to default
- [ ] Invalid spacing syntax reverts to default
- [ ] Missing styleConfig migrates legacy attributes
- [ ] Corrupted styleConfig resets gracefully
- [ ] Console errors logged clearly

## Performance

- [ ] Panel loads quickly (<500ms)
- [ ] Color picker opens instantly
- [ ] Slider interaction smooth (60fps)
- [ ] No lag when typing in inputs
- [ ] Preview updates without flicker
- [ ] Build time reasonable (<5s)

## Browser Compatibility

### Chrome/Edge (Chromium)
- [ ] All controls render
- [ ] Color pickers work
- [ ] Preview updates live
- [ ] Styles persist

### Firefox
- [ ] All controls render
- [ ] Color pickers work
- [ ] Preview updates live
- [ ] Styles persist

### Safari
- [ ] All controls render
- [ ] Color pickers work
- [ ] Preview updates live
- [ ] Styles persist

## Frontend Verification

- [ ] Published form loads CSS variables
- [ ] Colors match editor preview
- [ ] Typography matches editor preview
- [ ] Spacing matches editor preview
- [ ] Borders match editor preview
- [ ] Shadows match editor preview
- [ ] Hover states work correctly
- [ ] Focus states work correctly
- [ ] Mobile responsive
- [ ] No console errors

## Documentation

- [ ] CUSTOMIZATION_PANEL_GUIDE.md complete
- [ ] CUSTOMIZATION_QUICK_REFERENCE.md complete
- [ ] README.md updated with feature
- [ ] Code comments present in key functions
- [ ] Examples provided for common workflows

## Edge Cases

- [ ] Form without styleConfig migrates automatically
- [ ] Legacy attributes (backgroundColor, etc.) converted
- [ ] Switching presets multiple times works
- [ ] Resetting after manual changes works
- [ ] Undo/redo in editor works
- [ ] Copy/paste block preserves styles
- [ ] Duplicate block preserves styles

## Clinical Use Cases

### Long Questionnaire (30+ questions)
- [ ] Minimal White preset reduces fatigue
- [ ] Generous spacing improves readability
- [ ] Subtle shadows minimize distraction

### Sensitive Assessment (trauma, depression)
- [ ] Warm Neutral preset feels approachable
- [ ] Serif headings add warmth
- [ ] Earth tones feel comfortable

### Visually Impaired Participants
- [ ] High Contrast preset maximizes readability
- [ ] 18px base font prevents zoom issues
- [ ] Thick borders clearly define fields
- [ ] Focus states highly visible

### Mobile-First Research
- [ ] All presets responsive
- [ ] Touch targets sufficient size
- [ ] Text readable without pinch-zoom
- [ ] Forms submit successfully

## Integration Testing

- [ ] Works with Form Page blocks
- [ ] Works with all field types (text, select, radio, etc.)
- [ ] Works with Conditional Logic
- [ ] Works with VAS Slider
- [ ] Works with Likert Scale
- [ ] Styles don't conflict with theme CSS

## Known Limitations

Document any known issues:
- [ ] IE11 not supported (by design)
- [ ] Custom fonts require theme support
- [ ] Advanced shadow syntax may not preview exactly
- [ ] RTL support needs testing

## Sign-off

**Tester**: _________________  
**Date**: _________________  
**Build Version**: _________________  
**WordPress Version**: _________________  
**Browser(s) Tested**: _________________  

**Overall Assessment**: Pass / Fail / Needs Work

**Notes**:
_________________________________________________________________________
_________________________________________________________________________
_________________________________________________________________________
