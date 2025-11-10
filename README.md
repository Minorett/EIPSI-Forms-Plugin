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

## Usage

### Building Forms

#### Creating a Basic Form

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

#### Customizing Form Appearance

Customize form appearance through the Inspector sidebar → Block settings:

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

#### Creating Multi-Page Forms

1. Add "EIPSI Página" blocks inside the form container
2. Add field blocks inside each page block
3. Pagination navigation appears automatically
4. Forms can have unlimited pages with automatic progress tracking

### Conditional Logic (Form Branching)

The plugin supports conditional logic for select, radio, and checkbox fields, allowing you to create dynamic forms that adapt to participant responses.

#### Configuring Conditional Logic

1. Select a field block (select, radio, or checkboxes)
2. In the block settings panel, find "Lógica Condicional"
3. Toggle "Habilitar lógica condicional"
4. **Add Rules**: Click "+ Agregar regla" to create branching rules
5. **Configure Rules**: For each rule, select:
   - Which option/value triggers the rule
   - What action to take (go to next page, go to specific page, or finish form)
   - Which page to navigate to (if applicable)
6. **Set Default Action**: Define what happens when participants select values without specific rules
7. Fields with conditional logic show a lightning bolt (⚡) badge in the editor

#### Rule Schema

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

#### Actions

- **nextPage**: Continue to the next page in sequence
- **goToPage**: Jump to a specific page (requires `targetPage`)
- **submit**: Finish the form immediately

#### Features

- **Duplicate Detection**: The inspector warns if multiple rules use the same value
- **Page Titles**: Page dropdowns show "Página N – Title" format for clarity
- **Visual Indicators**: Fields with conditional logic display a lightning bolt badge
- **Backward Compatibility**: Legacy conditional logic formats are automatically upgraded
- **Clinical UX**: Clear, accessible interface aligned with research form standards

### Viewing Results

1. In WordPress admin, go to "VAS Forms"
2. View all form responses in a table with pagination
3. Click "View" to see full response details including:
   - Participant information
   - Device, browser, and OS data
   - Response duration and timestamp
   - All form field values
4. Download responses as CSV or Excel for analysis

### Database Configuration (External Database Support)

The plugin supports storing form submissions in an external MySQL database instead of the WordPress database. This is useful for:
- Separating research data from website data
- Connecting multiple WordPress sites to a shared database
- Maintaining data compliance and security requirements
- Centralizing research data management

#### Setting Up External Database

1. **Access Configuration Panel**
   - In WordPress admin, go to "EIPSI Forms" → "Configuration"
   - The Database Configuration page appears

2. **Enter Database Credentials**
   - **Host**: MySQL server hostname or IP (e.g., `localhost`, `192.168.1.100`)
   - **Username**: MySQL user with INSERT and SELECT privileges
   - **Password**: MySQL user password (encrypted before storage)
   - **Database Name**: Target database name (e.g., `research_db_custom`)

3. **Test Connection**
   - Click "Test Connection" button
   - System verifies credentials and displays:
     - ✅ **Green status**: Connection successful, shows record count
     - ❌ **Red status**: Connection failed, shows error message
   - **Important**: Connection must succeed before saving

4. **Save Configuration**
   - Click "Save Configuration" after successful test
   - Credentials are encrypted using WordPress security functions
   - All new form submissions will route to external database

5. **Monitor Status**
   - Status box shows:
     - Connection state (Connected/Disconnected)
     - Current database name
     - Total record count
     - Last configuration update time

#### Important Notes

- **Table Structure**: External database must have identical table structure to WordPress database
- **Security**: Passwords encrypted using AES-256-CBC with WordPress salts
- **Testing Required**: Configuration validates connection before saving to prevent data loss
- **Fallback Behavior**: If external DB becomes unavailable, submissions will fail (intentional to prevent data inconsistency)
- **Disable External DB**: Click "Disable External Database" to return to WordPress database storage

#### Credential Encryption

The plugin uses WordPress's built-in security functions:
- Encryption: `openssl_encrypt()` with AES-256-CBC
- Salt: WordPress `wp_salt('auth')` for key generation
- Storage: Encrypted credentials stored in `wp_options` table
- Keys: `eipsi_external_db_host`, `eipsi_external_db_user`, `eipsi_external_db_password`, `eipsi_external_db_name`

#### Technical Implementation

Form submission flow:
1. Frontend JavaScript validates form
2. AJAX request sent to `vas_dinamico_submit_form`
3. Handler checks if external DB is enabled (`EIPSI_External_Database::is_enabled()`)
4. If enabled: Data inserted via `mysqli` connection to external DB
5. If disabled: Data inserted to WordPress DB (default behavior)

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
