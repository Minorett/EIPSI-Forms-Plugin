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
  - Dark theme responsive design
  - Mobile-friendly forms
  - Professional form styling
  - AJAX-powered submissions

## Installation

1. Extract the plugin to `/wp-content/plugins/vas-dinamico-forms/`
2. Activate in WordPress admin: Plugins > VAS Dinámico Forms > Activate
3. Forms will be available in the Gutenberg block editor under "EIPSI Forms" category

## Creating Forms

1. Create or edit a page in the WordPress block editor
2. Add an "EIPSI Form Container" block
3. Add field blocks inside the form container:
   - EIPSI Campo Texto (text input with various types)
   - EIPSI Campo Textarea (multi-line text)
   - EIPSI Campo Descripción (static text/instructions)
   - EIPSI Campo Select (dropdown)
   - EIPSI Campo Radio (radio buttons)
   - EIPSI Campo Multiple (checkboxes)
   - EIPSI Campo Likert (Likert scale 1-5 or 1-7)
   - EIPSI VAS Slider (visual analog scale 0-100)

4. For multi-page forms:
   - Add "EIPSI Página" blocks inside the form container
   - Add field blocks inside page blocks
   - Pagination navigation appears automatically

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
