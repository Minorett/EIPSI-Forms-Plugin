# Conditional Logic Guide - EIPSI Forms

## Overview

The EIPSI Forms plugin supports advanced conditional logic for dynamic form navigation. This guide covers both discrete (radio/checkbox/select) and numeric (VAS slider) conditional logic.

## Types of Conditional Logic

### 1. Discrete Conditional Logic

Used for fields with discrete options (radio buttons, checkboxes, select dropdowns).

**Supported Fields:**
- Radio buttons
- Checkboxes
- Select dropdowns

**Rule Structure:**
```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-1234567890",
      "matchValue": "Option A",
      "action": "goToPage",
      "targetPage": 3
    }
  ],
  "defaultAction": "nextPage"
}
```

### 2. Numeric Conditional Logic (VAS Slider)

Used for VAS slider fields with numeric thresholds and comparison operators.

**Supported Fields:**
- VAS Slider

**Rule Structure:**
```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-1234567890",
      "operator": ">=",
      "threshold": 50,
      "action": "goToPage",
      "targetPage": 3
    }
  ],
  "defaultAction": "nextPage"
}
```

**Supported Operators:**
- `>=` - Greater than or equal to
- `<=` - Less than or equal to
- `>` - Greater than
- `<` - Less than
- `==` - Equal to

## Setting Up Conditional Logic

### In the Block Editor

#### For Radio/Checkbox/Select Fields:

1. Select the field block
2. In the sidebar, open the "Lógica Condicional" panel
3. Toggle "Habilitar lógica condicional"
4. Click "Agregar regla"
5. Configure:
   - **When participant selects:** Choose the option value
   - **Then:** Choose the action (next page, go to specific page, or submit)
   - **Go to page:** If "go to specific page" is selected, choose the target page

#### For VAS Slider Fields:

1. Select the VAS slider block
2. In the sidebar, open the "Lógica Condicional" panel
3. Toggle "Habilitar lógica condicional"
4. Click "Agregar regla"
5. Configure:
   - **When slider value is:** Choose the comparison operator
   - **Threshold value:** Enter the numeric threshold
   - **Then:** Choose the action (next page, go to specific page, or submit)
   - **Go to page:** If "go to specific page" is selected, choose the target page

### Actions Available

- **Next Page (nextPage):** Continue to the next sequential page
- **Go to Specific Page (goToPage):** Jump to a specific page number
- **Submit Form (submit):** End the form and submit

### Default Action

When no rules match, the default action is executed. Configure this in the "Acción predeterminada" section.

## Examples

### Example 1: VAS Slider Severity Branching

**Scenario:** Pain intensity scale (0-100) - high scores skip to emergency protocol

```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-high-pain",
      "operator": ">=",
      "threshold": 80,
      "action": "goToPage",
      "targetPage": 10
    },
    {
      "id": "rule-moderate-pain",
      "operator": ">=",
      "threshold": 50,
      "action": "goToPage",
      "targetPage": 5
    }
  ],
  "defaultAction": "nextPage"
}
```

**Logic Flow:**
- Value >= 80: Jump to page 10 (emergency protocol)
- Value >= 50 and < 80: Jump to page 5 (moderate care)
- Value < 50: Continue to next page

### Example 2: VAS Slider with Equal Comparison

**Scenario:** Satisfaction scale - specific follow-up for neutral responses

```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-neutral",
      "operator": "==",
      "threshold": 50,
      "action": "goToPage",
      "targetPage": 8
    }
  ],
  "defaultAction": "nextPage"
}
```

### Example 3: Radio Button Discrete Logic

**Scenario:** Yes/No screening question

```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-yes",
      "matchValue": "Yes",
      "action": "goToPage",
      "targetPage": 5
    },
    {
      "id": "rule-no",
      "matchValue": "No",
      "action": "submit"
    }
  ],
  "defaultAction": "nextPage"
}
```

### Example 4: Combined Multi-Page Form

**Scenario:** Mental health screening with VAS and radio logic

**Page 1: Depression Severity (VAS 0-100)**
```json
{
  "enabled": true,
  "rules": [
    {
      "operator": ">=",
      "threshold": 70,
      "action": "goToPage",
      "targetPage": 10
    }
  ],
  "defaultAction": "nextPage"
}
```

**Page 2: Suicidal Ideation (Radio)**
```json
{
  "enabled": true,
  "rules": [
    {
      "matchValue": "Yes",
      "action": "goToPage",
      "targetPage": 10
    },
    {
      "matchValue": "No",
      "action": "nextPage"
    }
  ]
}
```

## Implementation Details

### Frontend Runtime (eipsi-forms.js)

The `ConditionalNavigator` class handles all conditional logic evaluation:

#### Key Methods:

**getFieldValue(field):**
- Extracts the current value from a form field
- Returns numeric value for VAS sliders
- Returns string value for discrete fields
- Returns array for checkboxes

**findMatchingRule(rules, fieldValue):**
- Evaluates rules against the field value
- Supports both numeric comparisons and discrete matching
- Returns the first matching rule or null

**getNextPage(currentPage):**
- Determines the next page based on conditional logic
- Returns action object: `{ action, targetPage, fieldId, matchedValue }`

### Block Editor Integration

**ConditionalLogicControl Component:**
- React component for configuring conditional logic
- Supports `mode` prop: "discrete" or "numeric"
- Validates rules based on mode
- Integrates with WordPress block editor

**Props:**
```javascript
{
  attributes: object,
  setAttributes: function,
  options: array,      // For discrete mode
  clientId: string,
  mode: string         // "discrete" or "numeric"
}
```

### Data Storage

Conditional logic is stored in the block's `conditionalLogic` attribute and rendered as:

```html
<div data-conditional-logic='{"enabled":true,"rules":[...]}'></div>
```

## Testing

### Manual Testing Checklist

#### VAS Slider Numeric Logic:

- [ ] Create a multi-page form with VAS slider on page 1
- [ ] Add rule: value >= 75 → jump to page 5
- [ ] Add rule: value >= 50 → jump to page 3
- [ ] Set default action: next page
- [ ] Test with value 80 → should jump to page 5
- [ ] Test with value 60 → should jump to page 3
- [ ] Test with value 30 → should go to page 2
- [ ] Test edge cases: 75, 50 (boundary values)
- [ ] Test with > vs >= operators
- [ ] Test with < vs <= operators
- [ ] Test with == operator

#### Discrete Field Logic:

- [ ] Create radio field with options A, B, C
- [ ] Add rule: A → page 3
- [ ] Add rule: B → submit
- [ ] Test all options
- [ ] Verify default action works for option C

### Automated Testing

See `test-conditional-flows.js` for automated test scenarios.

## Analytics Integration

All conditional branching events are tracked via `window.EIPSITracking.trackEvent()`:

```javascript
window.EIPSITracking.trackEvent('branch_jump', {
  formId: 'form-123',
  fromPage: 2,
  toPage: 5,
  fieldId: 'pain_severity',
  operator: '>=',
  threshold: 80,
  matchedValue: 85
});
```

## Troubleshooting

### Common Issues:

**1. Rules not firing:**
- Check that conditional logic is enabled
- Verify `data-conditional-logic` attribute is present in HTML
- Check browser console for JavaScript errors
- Ensure field has `data-field-type` attribute

**2. Wrong page navigation:**
- Verify rule order (first matching rule wins)
- Check operator (>= vs > makes a difference)
- Test boundary values

**3. VAS slider not responding:**
- Ensure field type is "vas-slider"
- Check that value is numeric (not string)
- Verify threshold is a number

**4. Validation errors in editor:**
- Ensure all required fields are filled
- Check that threshold is a valid number
- Verify target page exists

### Debugging:

Enable debug mode in configuration:

```javascript
window.eipsiFormsConfig = {
  settings: {
    debug: true
  }
};
```

This will log conditional logic evaluation to the console.

## Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Not supported (uses modern JavaScript)

## Best Practices

1. **Order matters:** Rules are evaluated in order; first match wins
2. **Test boundaries:** Always test threshold values (e.g., if rule is >= 50, test 49, 50, 51)
3. **Use meaningful IDs:** Give rules descriptive IDs for debugging
4. **Default action:** Always set a sensible default action
5. **Page validation:** Ensure target pages exist before deploying
6. **Analytics:** Monitor branching behavior via tracking events
7. **User testing:** Test conditional flows with real participants

## Version History

- **v1.2.0:** Added numeric conditional logic for VAS slider
- **v1.1.0:** Initial discrete conditional logic (radio/checkbox/select)

## Support

For issues or questions:
- GitHub Issues: https://github.com/roofkat/VAS-dinamico-mvp/issues
- Documentation: See plugin README.md
