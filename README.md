# VAS Dinámico Forms - MVP

Professional WordPress form builder with Gutenberg blocks for research and surveys.

## Features

- **Gutenberg Blocks**: Fully integrated block editor support
  - Form Container block for multi-page forms
  - Field blocks: Text, Textarea, Select, Radio, Checkboxes, Likert Scale, VAS Slider
  - Static description blocks for instructions

- **Form Functionality**
  - Multi-page form support with pagination
  - Form field validation
  - Automatic data capture: IP address, device type, browser, OS, screen width, duration
  - JSON-based response storage

- **Admin Dashboard**
  - Simple results table with pagination
  - View/Delete individual responses
  - CSV export functionality
  - Excel export functionality

- **Professional UI**
  - Clinical research-grade design system
  - **Comprehensive customization panel** with FormGent-level controls
  - Design token system for centralized theming (60+ tokens)
  - 4 clinical presets: Clinical Blue, Minimal White, Warm Neutral, High Contrast
  - Real-time WCAG AA contrast checking
  - Mobile-friendly responsive forms
  - Professional form styling with CSS variables
  - AJAX-powered submissions

## Installation

1. Extract the plugin to `/wp-content/plugins/vas-dinamico-forms/`
2. Activate in WordPress admin: Plugins > VAS Dinámico Forms > Activate
3. Forms will be available in the Gutenberg block editor under "EIPSI Forms" category

## Creating Forms

1. Create or edit a page in the WordPress block editor
2. Add an "EIPSI Form Container" block
3. Customize form appearance (optional):
   - Open Inspector sidebar → Block settings
   - **Quick Start**: Apply a theme preset (Clinical Blue, Minimal White, Warm Neutral, High Contrast)
   - **Advanced**: Use 7 customization panels for full control:
      - 🎨 Theme Presets - One-click professional themes
      - 🎨 Colors - 18 color tokens with contrast checking
      - ✍️ Typography - Fonts, sizes, weights, line heights
      - 📐 Spacing & Layout - Padding, gaps, margins
      - 🔲 Borders & Radius - Corner radius, border styles
      - ✨ Shadows & Effects - Depth and elevation
      - ⚡ Hover & Interaction - Animations and transitions
   - Changes apply instantly with live preview
4. Add field blocks inside the form container:
   - EIPSI Campo Texto (text input with various types)
   - EIPSI Campo Textarea (multi-line text)
   - EIPSI Campo Descripción (static text/instructions)
   - EIPSI Campo Select (dropdown)
   - EIPSI Campo Radio (radio buttons)
   - EIPSI Campo Multiple (checkboxes)
   - EIPSI Campo Likert (Likert scale 1-5 or 1-7)
   - EIPSI VAS Slider (visual analog scale 0-100)

5. For multi-page forms:
   - Add "EIPSI Página" blocks inside the form container
   - Add field blocks inside page blocks
   - Pagination navigation appears automatically

6. Configure conditional logic (branching):
   - Select a field block (select, radio, or checkboxes)
   - In the block settings panel, find "Lógica Condicional"
   - Toggle "Habilitar lógica condicional"
   - Add rules to redirect participants based on their responses
   - Set a default action for values without specific rules
   - Fields with conditional logic show a lightning bolt (⚡) badge in the editor

## Conditional Logic (Form Branching)

The plugin supports conditional logic for select, radio, and checkbox fields, allowing you to create dynamic forms that adapt to participant responses.

### How It Works

1. **Enable Conditional Logic**: In the block inspector, toggle "Habilitar lógica condicional"
2. **Add Rules**: Click "+ Agregar regla" to create branching rules
3. **Configure Rules**: For each rule, select:
   - Which option/value triggers the rule
   - What action to take (go to next page, go to specific page, or finish form)
   - Which page to navigate to (if applicable)
4. **Set Default Action**: Define what happens when participants select values without specific rules

### Rule Schema

Conditional logic is stored in the `conditionalLogic` block attribute with this structure:

```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-1234567890",
      "matchValue": "Option 1",
      "action": "goToPage|nextPage|submit",
      "targetPage": 2
    }
  ],
  "defaultAction": "nextPage",
  "defaultTargetPage": null
}
```

### Actions

- **nextPage**: Continue to the next page in sequence
- **goToPage**: Jump to a specific page (requires `targetPage`)
- **submit**: Finish the form immediately

### Features

- **Duplicate Detection**: The inspector warns if multiple rules use the same value
- **Page Titles**: Page dropdowns show "Página N – Title" format for clarity
- **Visual Indicators**: Fields with conditional logic display a lightning bolt badge
- **Backward Compatibility**: Legacy conditional logic formats are automatically upgraded
- **Clinical UX**: Clear, accessible interface aligned with research form standards

## View Results

1. In WordPress admin, go to "VAS Forms"
2. View all form responses in a table
3. Click "View" to see full response details
4. Download responses as CSV or Excel

## Database

Plugin creates table: `wp_vas_form_results` with:
- Response ID
- Form name
- Timestamp
- Device/Browser/OS info
- IP address
- Response duration
- Form data (JSON)

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Gutenberg support

## Support

For issues or questions, please refer to the plugin documentation.

---

**Version**: 1.1.0  
**License**: GPL v2 or later
# EIPSI Forms Plugin
Plugin de WordPress para formularios dinámicos
