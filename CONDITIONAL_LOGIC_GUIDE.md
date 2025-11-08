# Conditional Logic Implementation Guide

## Overview

The EIPSI Forms plugin now features a completely overhauled conditional logic (form branching) system for clinical research forms. This guide provides technical documentation for the implementation.

---

## Architecture

### Component Structure

```
src/components/
  └── ConditionalLogicControl.js    # Main inspector control component
  └── ConditionalLogicControl.css   # Clinical design system styles

src/blocks/
  ├── campo-select/edit.js          # Select field with conditional logic
  ├── campo-radio/edit.js           # Radio buttons with conditional logic
  └── campo-multiple/edit.js        # Checkboxes with conditional logic
```

### Data Flow

```
Block Edit Component (campo-select, campo-radio, campo-multiple)
    ↓
    ├── Passes clientId to ConditionalLogicControl
    ├── Passes options array (parsed from comma-separated string)
    └── Passes attributes and setAttributes
    
ConditionalLogicControl Component
    ↓
    ├── Uses wp.data.select('core/block-editor') to find form-page blocks
    ├── Normalizes legacy conditionalLogic formats
    ├── Validates rules (duplicate detection)
    └── Renders PanelBody with rules UI
```

---

## Rule Schema

### Current Schema (v2.0)

```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-1234567890123",
      "matchValue": "Option 1",
      "action": "goToPage",
      "targetPage": 2
    },
    {
      "id": "rule-1234567890124",
      "matchValue": "Option 2",
      "action": "submit",
      "targetPage": null
    }
  ],
  "defaultAction": "nextPage",
  "defaultTargetPage": 3
}
```

### Legacy Schema Support

**Array Format (v1.0):**
```json
[
  { "value": "Option 1", "action": "goToPage", "targetPage": 2 }
]
```

**Object without enabled flag (v1.5):**
```json
{
  "rules": [
    { "value": "Option 1", "action": "goToPage", "targetPage": 2 }
  ]
}
```

Both legacy formats are automatically normalized via the `normalizeConditionalLogic()` function.

---

## Key Features

### 1. Fixed Page Block Name Mismatch

**Problem:** Code was searching for `vas-dinamico/pagina` but actual block name is `vas-dinamico/form-page`.

**Solution:**
```javascript
const pageBlocks = formContainer.innerBlocks.filter(
    ( block ) => block.name === 'vas-dinamico/form-page'  // Fixed!
);
```

### 2. Page Titles in Dropdowns

**Implementation:**
```javascript
const label = page.title
    ? `${ __( 'Página', 'vas-dinamico-forms' ) } ${ page.index } – ${ page.title }`
    : `${ __( 'Página', 'vas-dinamico-forms' ) } ${ page.index }`;
```

**Example Output:**
- "Página 1 – Información Personal"
- "Página 2 – Historial Clínico"
- "Página 3"

### 3. Inline Validation

```javascript
const validateRules = ( rules ) => {
    const errors = {};
    const usedValues = new Set();
    
    rules.forEach( ( rule, index ) => {
        // Duplicate value detection
        if ( usedValues.has( rule.matchValue ) ) {
            errors[ index ] = __( 'Este valor ya está siendo usado en otra regla' );
        }
        
        // Empty value detection
        if ( ! rule.matchValue ) {
            errors[ index ] = __( 'Selecciona un valor para esta regla' );
        }
        
        // Invalid page detection
        if ( rule.action === 'goToPage' && ( ! rule.targetPage || rule.targetPage < 1 ) ) {
            errors[ index ] = __( 'Selecciona una página válida' );
        }
    } );
    
    return errors;
};
```

### 4. Default Action Selector

Allows clinicians to define fallback behavior when participants select values without explicit rules.

**Options:**
- **nextPage**: Continue to the next page in sequence
- **goToPage**: Jump to a specific page
- **submit**: Finish the form immediately

### 5. Visual Indicators

Fields with conditional logic get a `data-conditional-logic="true"` attribute, which triggers CSS styling:

```javascript
const hasConditionalLogic = conditionalLogic && ( 
    Array.isArray( conditionalLogic ) 
        ? conditionalLogic.length > 0 
        : conditionalLogic.enabled && conditionalLogic.rules && conditionalLogic.rules.length > 0 
);

const blockProps = useBlockProps( {
    'data-conditional-logic': hasConditionalLogic ? 'true' : undefined,
} );
```

**Visual Result:**
- Blue border around the field block
- Lightning bolt (⚡) badge in top-right corner

---

## CSS Architecture

### Clinical Design System

```css
/* Color Palette */
--primary-clinical: #005a87;      /* EIPSI Blue */
--primary-hover: #003d5b;         /* Darker blue for interactions */
--secondary-calming: #e3f2fd;     /* Light blue backgrounds */
--neutral-background: #ffffff;    /* Clean white */
--border-light: #e2e8f0;          /* Subtle borders */
--text-soft: #2c3e50;             /* Readable text */
--error-clinical: #ff6b6b;        /* Error states */
```

### Key Classes

| Class | Purpose |
|-------|---------|
| `.conditional-logic-panel` | Main container |
| `.conditional-logic-rule` | Individual rule card |
| `.conditional-logic-warning` | Error/warning messages |
| `.conditional-logic-empty-state` | No rules configured state |
| `.conditional-logic-validation-error` | Inline validation errors |
| `.conditional-logic-default-action` | Default action selector |

### Accessibility Features

- **Focus Indicators:** 2px solid outline with 2px offset
- **Color Contrast:** Minimum 4.5:1 for all text
- **Keyboard Navigation:** Full keyboard support for all controls
- **Screen Readers:** Proper ARIA labels and descriptions
- **Reduced Motion:** `prefers-reduced-motion` support

---

## API Reference

### ConditionalLogicControl Component

**Props:**

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `attributes` | Object | Yes | Block attributes object |
| `setAttributes` | Function | Yes | Block setAttributes function |
| `options` | Array | Yes | Array of option strings |
| `clientId` | String | Yes | Block client ID for wp.data queries |

**Example Usage:**

```javascript
import ConditionalLogicControl from '../../components/ConditionalLogicControl';

<ConditionalLogicControl
    attributes={ attributes }
    setAttributes={ setAttributes }
    options={ optionsArray }
    clientId={ clientId }
/>
```

### normalizeConditionalLogic Function

Internal function that converts legacy formats to the current schema.

**Signature:**
```javascript
function normalizeConditionalLogic( conditionalLogic )
```

**Returns:**
```javascript
{
    enabled: boolean,
    rules: Array<Rule>,
    defaultAction: string,
    defaultTargetPage: number|null
}
```

---

## Testing Checklist

### Editor Experience

- [ ] Enable conditional logic toggle appears in inspector
- [ ] Toggle creates initial empty state
- [ ] Add rule button is disabled when no pages/options
- [ ] Add rule button creates new rule with default values
- [ ] Rule displays option dropdown populated with field options
- [ ] Rule displays action dropdown (nextPage, goToPage, submit)
- [ ] Rule displays page dropdown when "goToPage" selected
- [ ] Page dropdown shows "Página N – Title" format
- [ ] Remove rule button deletes the rule
- [ ] Removing last rule clears conditionalLogic attribute
- [ ] Default action selector appears when rules exist
- [ ] Validation errors appear for duplicate values
- [ ] Lightning bolt badge appears on field blocks with logic
- [ ] Blue border appears on field blocks with logic

### Data Persistence

- [ ] Rules persist after saving and reloading page
- [ ] Rules survive block copy/paste
- [ ] Rules survive block duplicate
- [ ] Legacy formats load without console errors
- [ ] Legacy formats are upgraded on first edit

### Edge Cases

- [ ] Form without pages shows appropriate warning
- [ ] Field without options shows appropriate warning
- [ ] Reordering pages updates page numbers in dropdowns
- [ ] Deleting a target page shows validation warning
- [ ] Changing field options updates rule dropdowns
- [ ] Disabling conditional logic removes visual indicators

---

## Migration Guide

### Upgrading from v1.0

**Old Format:**
```json
[
  { "value": "Yes", "action": "goToPage", "targetPage": 2 }
]
```

**New Format:**
```json
{
  "enabled": true,
  "rules": [
    { "id": "rule-123", "matchValue": "Yes", "action": "goToPage", "targetPage": 2 }
  ],
  "defaultAction": "nextPage"
}
```

**Migration is automatic** when opening a block in the editor. The `normalizeConditionalLogic()` function handles conversion.

---

## Troubleshooting

### Issue: Rules not appearing in inspector

**Diagnosis:**
1. Check if field has options configured
2. Check if form has pages (required for conditional logic)
3. Check browser console for JavaScript errors

**Solution:**
- Ensure options are comma-separated (e.g., "Option 1, Option 2, Option 3")
- Add at least one "EIPSI Página" block to the form container

### Issue: Page dropdown shows wrong pages

**Diagnosis:**
1. Check if you're using legacy `vas-dinamico/pagina` block name
2. Check form container structure

**Solution:**
- Ensure you're using `vas-dinamico/form-page` blocks (the official block name)
- Pages must be direct children of `vas-dinamico/form-container`

### Issue: Lightning bolt badge not appearing

**Diagnosis:**
1. Check if `data-conditional-logic` attribute is set
2. Check if CSS is loaded

**Solution:**
- Rebuild blocks: `npm run build`
- Clear browser cache
- Check that `build/index.css` contains `.eipsi-field[data-conditional-logic="true"]`

### Issue: Validation errors not clearing

**Diagnosis:**
- React state not updating properly

**Solution:**
- Fix the duplicate value or empty value issue
- Remove and re-add the rule if stuck

---

## Clinical UX Guidelines

### Language and Tone

Use clear, professional language appropriate for research settings:

- ✅ "Cuando el participante seleccione"
- ✅ "Para configurar la lógica condicional..."
- ❌ "When user picks"
- ❌ "Setup logic..."

### Empty States

Provide helpful guidance, not just error messages:

```
"No hay reglas configuradas. Las reglas permiten redirigir al participante 
a diferentes páginas según su respuesta."
```

### Warning Messages

Be constructive and actionable:

```
"Para configurar la lógica condicional, primero debes agregar páginas al formulario."
```

### Visual Hierarchy

1. **Toggle** - Primary control (enable/disable)
2. **Rules** - Main content area
3. **Default Action** - Secondary control
4. **Add Rule Button** - Call to action

---

## Performance Considerations

### wp.data.select Usage

The `useSelect` hook re-runs when dependencies change:

```javascript
const { pages, hasPages } = useSelect(
    ( select ) => {
        // Query logic
    },
    [ clientId ]  // Re-run when clientId changes
);
```

**Optimization:** Only `clientId` is a dependency, so the query doesn't re-run on every render.

### Validation Timing

Validation runs via `useEffect` only when relevant data changes:

```javascript
useEffect( () => {
    if ( normalizedLogic.enabled && normalizedLogic.rules.length > 0 ) {
        validateRules( normalizedLogic.rules );
    }
}, [ normalizedLogic.enabled, normalizedLogic.rules, options, pages ] );
```

---

## Future Enhancements

### Potential Features

1. **Complex Conditions**
   - Multiple conditions per rule (AND/OR logic)
   - Range conditions for numeric fields
   - Pattern matching for text fields

2. **Visual Rule Builder**
   - Drag-and-drop rule reordering
   - Visual flowchart preview
   - Rule groups/folders

3. **Advanced Actions**
   - Hide/show specific fields
   - Pre-fill field values
   - Skip multiple pages

4. **Testing Tools**
   - Rule simulator in editor
   - Test mode for forms
   - Logic validation report

---

## Code Examples

### Adding Conditional Logic to a Custom Block

```javascript
// 1. Import the control
import ConditionalLogicControl from '../../components/ConditionalLogicControl';

// 2. Add conditionalLogic to block.json attributes
"conditionalLogic": {
    "type": "object",
    "default": null
}

// 3. Add clientId to Edit function signature
export default function Edit( { attributes, setAttributes, clientId } ) {

// 4. Calculate hasConditionalLogic for visual indicator
const hasConditionalLogic = conditionalLogic && ( 
    Array.isArray( conditionalLogic ) 
        ? conditionalLogic.length > 0 
        : conditionalLogic.enabled && conditionalLogic.rules?.length > 0 
);

// 5. Add data attribute to blockProps
const blockProps = useBlockProps( {
    'data-conditional-logic': hasConditionalLogic ? 'true' : undefined,
} );

// 6. Add control to InspectorControls
<InspectorControls>
    <ConditionalLogicControl
        attributes={ attributes }
        setAttributes={ setAttributes }
        options={ optionsArray }
        clientId={ clientId }
    />
</InspectorControls>
```

---

## Support

For issues or questions:

1. Check this guide's troubleshooting section
2. Review the main README.md
3. Check browser console for errors
4. Verify build output: `npm run build`

---

**Version:** 2.0.0  
**Last Updated:** 2024  
**Status:** ✅ Production Ready
