# Developer Guide - EIPSI Forms v1.2.2

## Overview

This guide provides technical documentation for developers who want to extend, customize, or integrate with the EIPSI Forms plugin.

---

## Table of Contents

1. [Plugin Architecture](#plugin-architecture)
2. [File Structure](#file-structure)
3. [Database Schema](#database-schema)
4. [Hooks & Filters](#hooks--filters)
5. [Gutenberg Blocks](#gutenberg-blocks)
6. [JavaScript API](#javascript-api)
7. [Build System](#build-system)
8. [Testing](#testing)
9. [Security](#security)
10. [Contributing](#contributing)

---

## Plugin Architecture

### Overview

EIPSI Forms follows WordPress plugin best practices with a modular architecture:

```
┌─────────────────────────────────────────────────┐
│           WordPress Gutenberg Editor            │
│  (React Components for Block Configuration)    │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│          Gutenberg Blocks (11 blocks)           │
│  Form Container, Pages, Fields (VAS, Likert,   │
│  Radio, Multiple, Select, Text, Textarea, etc.) │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│         Frontend JavaScript Layer               │
│  Form rendering, validation, submission,        │
│  navigation, tracking, localStorage             │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│            AJAX Handler (PHP)                   │
│  Submission processing, validation,             │
│  sanitization, database insert                  │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│         Database Layer (MySQL)                  │
│  vas_form_results, vas_form_events              │
│  (WordPress DB or External DB)                  │
└─────────────────────────────────────────────────┘
```

### Core Components

1. **Gutenberg Blocks** (`src/blocks/`)
   - React components for editor
   - `save()` functions for frontend output
   - Block attributes and validation

2. **Frontend JavaScript** (`assets/js/`)
   - Form interaction logic
   - Multi-page navigation
   - Client-side validation
   - AJAX submission
   - Event tracking

3. **PHP Backend** (`includes/`, `admin/`)
   - AJAX handlers
   - Database operations
   - Admin pages
   - Settings management

4. **Database Layer** (`includes/class-database-manager.php`, `includes/class-external-database.php`)
   - Schema management
   - Auto-repair functionality
   - Query optimization
   - Fallback handling

---

## File Structure

```
eipsi-forms-plugin/
│
├── admin/                          # Admin interface
│   ├── css/                        # Admin-specific styles
│   ├── js/                         # Admin-specific JavaScript
│   ├── database-config.php         # Database configuration page
│   ├── privacy-config.php          # Privacy settings page
│   └── results-page.php            # Results & Experience page
│
├── assets/                         # Frontend assets
│   ├── css/                        # Compiled CSS
│   ├── js/
│   │   ├── eipsi-forms.js          # Main form interaction logic
│   │   ├── eipsi-tracking.js       # Event tracking system
│   │   └── vas-slider.js           # VAS slider functionality
│   └── images/                     # Plugin images
│
├── build/                          # Compiled Gutenberg blocks
│   ├── index.js                    # Bundled blocks JavaScript
│   ├── index.asset.php             # Asset dependencies
│   ├── *.css                       # Block styles
│   └── *.asset.php                 # Individual block assets
│
├── includes/                       # Core plugin logic
│   ├── class-database-manager.php  # Database schema management
│   ├── class-external-database.php # External database handler
│   ├── ajax-handlers.php           # AJAX endpoint handlers
│   ├── form-renderer.php           # Form rendering logic
│   └── validation.php              # Server-side validation
│
├── lib/                            # Third-party libraries
│   └── SimpleXLSXGen.php           # Excel export library
│
├── src/                            # Source files (pre-build)
│   ├── blocks/                     # Gutenberg block source
│   │   ├── form-container/         # Main container block
│   │   ├── form-page/              # Page block
│   │   ├── vas-slider/             # VAS slider block
│   │   ├── likert-field/           # Likert field block
│   │   ├── radio-field/            # Radio field block
│   │   ├── multiple-field/         # Multiple choice block
│   │   ├── select-field/           # Select dropdown block
│   │   ├── text-field/             # Text input block
│   │   ├── textarea-field/         # Textarea block
│   │   └── description-field/      # Description block
│   ├── css/                        # SCSS source files
│   └── index.js                    # Block registration entry point
│
├── tests/                          # Automated tests
│   ├── stress-test-v1.2.2.js       # Performance stress tests
│   ├── stress-test-readiness-v1.2.2.js  # Pre-flight validation
│   ├── test-e2e-all-features-v1.2.2.js  # End-to-end tests
│   ├── final-audit-v1.2.2.js       # Production readiness audit
│   └── [other test files]
│
├── eipsi-forms-plugin.php          # Main plugin file
├── package.json                    # npm dependencies
├── webpack.config.js               # Webpack build configuration
├── .distignore                     # Production distribution exclusions
├── README.md                       # User documentation
├── INSTALLATION.md                 # Installation guide
├── CONFIGURATION.md                # Configuration guide
├── TROUBLESHOOTING.md              # Troubleshooting guide
├── CHANGELOG.md                    # Version history
├── DEVELOPER.md                    # This file
└── SUMMARY.md                      # Release summary
```

### Key Files

| File | Purpose | Location |
|------|---------|----------|
| **Main Plugin File** | Plugin bootstrap, hooks registration | `eipsi-forms-plugin.php` |
| **Database Manager** | Schema management, auto-repair | `includes/class-database-manager.php` |
| **External Database** | External DB connection, fallback | `includes/class-external-database.php` |
| **AJAX Handlers** | Form submission, data processing | `includes/ajax-handlers.php` |
| **Form JavaScript** | Client-side form logic | `assets/js/eipsi-forms.js` |
| **Tracking JavaScript** | Event tracking system | `assets/js/eipsi-tracking.js` |
| **Block Index** | Block registration | `src/index.js` |
| **Webpack Config** | Build configuration | `webpack.config.js` |

---

## Database Schema

### Table: `wp_vas_form_results`

Stores form submission data.

```sql
CREATE TABLE wp_vas_form_results (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    form_name VARCHAR(255) NULL,
    form_id VARCHAR(100) NULL,
    participant_id VARCHAR(50) NULL,
    session_id VARCHAR(100) NULL,
    form_responses LONGTEXT NULL,
    metadata LONGTEXT NULL,
    ip_address VARCHAR(45) NULL,
    device VARCHAR(50) NULL,
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,
    screen_width INT NULL,
    created_at DATETIME NULL,
    submitted_at DATETIME NULL,
    duration_seconds INT NULL,
    status VARCHAR(20) DEFAULT 'completed',
    quality_flag VARCHAR(20) DEFAULT 'NORMAL',
    PRIMARY KEY (id),
    INDEX idx_form_id (form_id),
    INDEX idx_participant_id (participant_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_form_participant (form_id, participant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Column Descriptions

| Column | Type | Description | Nullable |
|--------|------|-------------|----------|
| `id` | BIGINT(20) | Primary key, auto-increment | NO |
| `form_name` | VARCHAR(255) | Human-readable form name | YES |
| `form_id` | VARCHAR(100) | Unique form identifier (e.g., `ACA-a3f1b2`) | YES |
| `participant_id` | VARCHAR(50) | Anonymous participant UUID (e.g., `p-a1b2c3d4e5f6`) | YES |
| `session_id` | VARCHAR(100) | Session identifier (e.g., `sess-1705764645000-xyz`) | YES |
| `form_responses` | LONGTEXT | JSON object with field responses | YES |
| `metadata` | LONGTEXT | JSON object with clinical metadata | YES |
| `ip_address` | VARCHAR(45) | Participant IP address (IPv4/IPv6) | YES |
| `device` | VARCHAR(50) | Device type (mobile, desktop, tablet) | YES |
| `browser` | VARCHAR(100) | Browser name and version | YES |
| `os` | VARCHAR(100) | Operating system and version | YES |
| `screen_width` | INT | Screen width in pixels | YES |
| `created_at` | DATETIME | Form start timestamp | YES |
| `submitted_at` | DATETIME | Form submission timestamp | YES |
| `duration_seconds` | INT | Time spent completing form (seconds) | YES |
| `status` | VARCHAR(20) | Submission status (completed, partial, abandoned) | NO |
| `quality_flag` | VARCHAR(20) | Data quality assessment (HIGH, NORMAL, LOW) | NO |

#### Example Data

```json
{
    "id": 1,
    "form_name": "Anxiety Clinical Assessment",
    "form_id": "ACA-a3f1b2",
    "participant_id": "p-abc123def456",
    "session_id": "sess-1705764645000-xyz",
    "form_responses": {
        "anxiety_level": "7",
        "frequency": "Often",
        "comments": "Experiencing anxiety daily..."
    },
    "metadata": {
        "therapeutic_engagement": {...},
        "clinical_consistency": {...},
        "avoidance_patterns": {...}
    },
    "ip_address": "192.168.1.100",
    "device": "mobile",
    "browser": "Chrome 120.0",
    "os": "iOS 17.2",
    "screen_width": 390,
    "created_at": "2025-01-20 10:30:00",
    "submitted_at": "2025-01-20 10:35:45",
    "duration_seconds": 345,
    "status": "completed",
    "quality_flag": "NORMAL"
}
```

---

### Table: `wp_vas_form_events`

Stores form interaction events for tracking and analytics.

```sql
CREATE TABLE wp_vas_form_events (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    form_id VARCHAR(100) NULL,
    session_id VARCHAR(100) NULL,
    participant_id VARCHAR(50) NULL,
    event_type VARCHAR(50) NULL,
    page_number INT NULL,
    metadata LONGTEXT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (id),
    INDEX idx_form_id (form_id),
    INDEX idx_session_id (session_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Event Types

| Event Type | Description | Triggered When |
|------------|-------------|----------------|
| `view` | Form viewed | Page loaded with form |
| `start` | Form started | First interaction with form |
| `page_change` | Page changed | Navigation between pages |
| `submit` | Form submitted | Successful submission |
| `abandon` | Form abandoned | User leaves page before submission |
| `branch_jump` | Conditional jump | Conditional logic triggered |

---

## Hooks & Filters

### Action Hooks

#### `eipsi_form_before_render`

Fired before form renders on frontend.

```php
do_action('eipsi_form_before_render', $form_id, $attributes);
```

**Parameters:**
- `$form_id` (string): Unique form identifier
- `$attributes` (array): Block attributes

**Example:**
```php
add_action('eipsi_form_before_render', 'log_form_view', 10, 2);

function log_form_view($form_id, $attributes) {
    error_log("Form {$form_id} is being rendered");
}
```

---

#### `eipsi_form_after_submit`

Fired after successful form submission.

```php
do_action('eipsi_form_after_submit', $form_id, $participant_id, $responses);
```

**Parameters:**
- `$form_id` (string): Unique form identifier
- `$participant_id` (string): Participant UUID
- `$responses` (array): Form field responses

**Example:**
```php
add_action('eipsi_form_after_submit', 'send_notification_email', 10, 3);

function send_notification_email($form_id, $participant_id, $responses) {
    $admin_email = get_option('admin_email');
    $subject = "New form submission: {$form_id}";
    $message = "Participant {$participant_id} submitted form {$form_id}";
    wp_mail($admin_email, $subject, $message);
}
```

---

#### `eipsi_tracking_event`

Fired when tracking event occurs.

```php
do_action('eipsi_tracking_event', $event_type, $form_id, $session_id, $metadata);
```

**Parameters:**
- `$event_type` (string): Event type (view, start, page_change, submit, abandon, branch_jump)
- `$form_id` (string): Unique form identifier
- `$session_id` (string): Session identifier
- `$metadata` (array): Event metadata

**Example:**
```php
add_action('eipsi_tracking_event', 'log_abandonment', 10, 4);

function log_abandonment($event_type, $form_id, $session_id, $metadata) {
    if ($event_type === 'abandon') {
        error_log("Form {$form_id} abandoned at page {$metadata['page_number']}");
    }
}
```

---

### Filter Hooks

#### `eipsi_validate_field`

Filter field validation result.

```php
apply_filters('eipsi_validate_field', $is_valid, $field_name, $value, $field_config);
```

**Parameters:**
- `$is_valid` (bool|WP_Error): Current validation result
- `$field_name` (string): Field identifier
- `$value` (mixed): Field value
- `$field_config` (array): Field configuration

**Returns:**
- `bool|WP_Error`: Validation result

**Example:**
```php
add_filter('eipsi_validate_field', 'custom_age_validation', 10, 4);

function custom_age_validation($is_valid, $field_name, $value, $field_config) {
    if ($field_name === 'age') {
        if ($value < 18 || $value > 100) {
            return new WP_Error('invalid_age', 'Age must be between 18 and 100.');
        }
    }
    return $is_valid;
}
```

---

#### `eipsi_sanitize_field`

Filter sanitized field value.

```php
apply_filters('eipsi_sanitize_field', $sanitized_value, $field_name, $raw_value);
```

**Parameters:**
- `$sanitized_value` (mixed): Current sanitized value
- `$field_name` (string): Field identifier
- `$raw_value` (mixed): Raw input value

**Returns:**
- `mixed`: Sanitized value

**Example:**
```php
add_filter('eipsi_sanitize_field', 'custom_phone_sanitization', 10, 3);

function custom_phone_sanitization($sanitized_value, $field_name, $raw_value) {
    if ($field_name === 'phone') {
        // Remove all non-numeric characters
        return preg_replace('/[^0-9]/', '', $raw_value);
    }
    return $sanitized_value;
}
```

---

#### `eipsi_style_tokens`

Filter CSS style configuration.

```php
apply_filters('eipsi_style_tokens', $style_config, $form_id);
```

**Parameters:**
- `$style_config` (array): Current style configuration
- `$form_id` (string): Unique form identifier

**Returns:**
- `array`: Modified style configuration

**Example:**
```php
add_filter('eipsi_style_tokens', 'custom_form_colors', 10, 2);

function custom_form_colors($style_config, $form_id) {
    if ($form_id === 'custom-form') {
        $style_config['primary_color'] = '#ff0000';
        $style_config['hover_color'] = '#cc0000';
    }
    return $style_config;
}
```

---

## Gutenberg Blocks

### Block Registration

All blocks registered in `src/index.js`:

```javascript
import { registerBlockType } from '@wordpress/blocks';

// Import block definitions
import formContainerBlock from './blocks/form-container';
import formPageBlock from './blocks/form-page';
import vasSliderBlock from './blocks/vas-slider';
// ... other blocks

// Register blocks
registerBlockType('eipsi/form-container', formContainerBlock);
registerBlockType('eipsi/form-page', formPageBlock);
registerBlockType('eipsi/vas-slider', vasSliderBlock);
// ... other blocks
```

---

### Creating a Custom Block

#### Example: Custom Rating Block

**File:** `src/blocks/rating-field/index.js`

```javascript
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, RangeControl } from '@wordpress/components';

const RatingFieldBlock = {
    title: __('EIPSI Rating Field', 'eipsi-forms'),
    icon: 'star-filled',
    category: 'eipsi-forms',
    attributes: {
        label: { type: 'string', default: 'Rate your experience' },
        fieldName: { type: 'string', default: 'rating' },
        required: { type: 'boolean', default: false },
        maxRating: { type: 'number', default: 5 },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Field Settings', 'eipsi-forms')}>
                        <TextControl
                            label={__('Label', 'eipsi-forms')}
                            value={attributes.label}
                            onChange={(value) => setAttributes({ label: value })}
                        />
                        <TextControl
                            label={__('Field Name', 'eipsi-forms')}
                            value={attributes.fieldName}
                            onChange={(value) => setAttributes({ fieldName: value })}
                        />
                        <ToggleControl
                            label={__('Required', 'eipsi-forms')}
                            checked={attributes.required}
                            onChange={(value) => setAttributes({ required: value })}
                        />
                        <RangeControl
                            label={__('Max Rating', 'eipsi-forms')}
                            value={attributes.maxRating}
                            onChange={(value) => setAttributes({ maxRating: value })}
                            min={3}
                            max={10}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div {...blockProps} className="eipsi-rating-field">
                    <label>
                        {attributes.label}
                        {attributes.required && <span className="required">*</span>}
                    </label>
                    <div className="rating-stars">
                        {[...Array(attributes.maxRating)].map((_, i) => (
                            <span key={i} className="star">★</span>
                        ))}
                    </div>
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        const blockProps = useBlockProps.save();
        
        return (
            <div {...blockProps} className="eipsi-rating-field" data-field-name={attributes.fieldName}>
                <label>
                    {attributes.label}
                    {attributes.required && <span className="required">*</span>}
                </label>
                <div className="rating-stars" data-max-rating={attributes.maxRating}>
                    {[...Array(attributes.maxRating)].map((_, i) => (
                        <span key={i} className="star" data-value={i + 1}>★</span>
                    ))}
                </div>
                <input
                    type="hidden"
                    name={attributes.fieldName}
                    data-required={attributes.required}
                />
            </div>
        );
    },
};

export default RatingFieldBlock;
```

**Register in `src/index.js`:**

```javascript
import ratingFieldBlock from './blocks/rating-field';
registerBlockType('eipsi/rating-field', ratingFieldBlock);
```

**Add client-side interaction (assets/js/):**

```javascript
// Rating field interaction
document.querySelectorAll('.eipsi-rating-field .star').forEach(star => {
    star.addEventListener('click', function() {
        const value = this.getAttribute('data-value');
        const input = this.closest('.eipsi-rating-field').querySelector('input');
        input.value = value;
        
        // Visual feedback
        const stars = this.closest('.rating-stars').querySelectorAll('.star');
        stars.forEach((s, i) => {
            s.classList.toggle('selected', i < value);
        });
    });
});
```

---

## JavaScript API

### Form Submission API

```javascript
// Submit form programmatically
const formData = {
    form_id: 'ACA-a3f1b2',
    participant_id: 'p-abc123def456',
    session_id: 'sess-1705764645000-xyz',
    responses: {
        anxiety_level: '7',
        frequency: 'Often',
        comments: 'Experiencing anxiety daily...'
    },
    metadata: { /* ... */ }
};

fetch(eipsi_forms_ajax.ajax_url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'eipsi_submit_form',
        nonce: eipsi_forms_ajax.nonce,
        form_data: JSON.stringify(formData)
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Form submitted successfully');
    } else {
        console.error('Submission failed:', data.data.message);
    }
})
.catch(error => {
    console.error('Network error:', error);
});
```

---

### Tracking API

```javascript
// Track custom event
window.EIPSITracking.trackEvent({
    event_type: 'custom_event',
    form_id: 'ACA-a3f1b2',
    session_id: 'sess-1705764645000-xyz',
    metadata: {
        custom_data: 'value',
        timestamp: Date.now()
    }
});
```

---

## Build System

### Requirements

- **Node.js:** 14 or higher
- **npm:** 6 or higher

### Installation

```bash
cd /path/to/eipsi-forms-plugin/
npm install
```

### Build Commands

```bash
# Development build (unminified, with source maps)
npm run build

# Production build (minified, optimized)
npm run build:production

# Watch mode (auto-rebuild on file changes)
npm run start

# Lint JavaScript
npm run lint:js

# Lint and auto-fix
npm run lint:js -- --fix

# Format code
npm run format
```

### Build Configuration

**File:** `webpack.config.js`

```javascript
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(__dirname, 'src/index.js'),
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
};
```

### Build Output

After running `npm run build`, the `build/` directory contains:

- `index.js` - Bundled JavaScript (~220 KB)
- `index.asset.php` - Asset dependencies
- `*.css` - Compiled block styles
- `*.asset.php` - Individual block dependencies

---

## Testing

### Automated Tests

#### Run All Tests

```bash
# Readiness validation (48 tests)
node stress-test-readiness-v1.2.2.js

# Stress tests (30 minutes, 5 categories)
node stress-test-v1.2.2.js --url=https://your-site.com

# End-to-end tests (132 tests)
node test-e2e-all-features-v1.2.2.js

# Final audit (36 tests)
node final-audit-v1.2.2.js
```

#### Test Results

All tests generate reports:
- **JSON:** Machine-readable results
- **Markdown:** Human-readable summary

Example: `STRESS_TEST_REPORT_v1.2.2_[timestamp].md`

---

### Manual Testing Checklist

#### Form Creation
- [ ] Create form with Form Container block
- [ ] Add multiple pages (EIPSI Página blocks)
- [ ] Add various field types (VAS, Likert, Radio, Multiple, Text, Textarea)
- [ ] Configure field settings (label, fieldName, required)
- [ ] Select preset (Clinical Blue, Minimal White, Warm Neutral, Serene Teal, Dark EIPSI)
- [ ] Configure navigation (allow/prevent backwards)
- [ ] Publish form

#### Form Submission
- [ ] Open form in incognito window
- [ ] Fill all required fields
- [ ] Test navigation (Next, Previous buttons)
- [ ] Submit form
- [ ] Verify success message
- [ ] Check submission in admin (Results & Experience)

#### Database Verification
- [ ] Navigate to Database Configuration
- [ ] Test connection (should show ✅ Success)
- [ ] Verify schema (should show all 27 columns)
- [ ] Check for submissions in database table

#### Privacy Settings
- [ ] Navigate to Privacy Settings
- [ ] Toggle metadata fields (IP, Browser, OS, Device, Screen Width)
- [ ] Submit test form
- [ ] Verify only enabled fields captured

#### Mobile Testing
- [ ] Test on mobile device (or Chrome DevTools device emulation)
- [ ] Verify touch targets (44x44px minimum)
- [ ] Test expanded clickable areas (Likert, Multiple Choice)
- [ ] Test navigation on mobile
- [ ] Submit form on mobile

---

## Security

### Output Escaping

Always escape output in PHP:

```php
// Text output
echo esc_html($user_input);

// Attribute output
echo '<div class="' . esc_attr($class_name) . '">';

// URL output
echo '<a href="' . esc_url($url) . '">';

// Translation with escaping
esc_html_e('Text', 'eipsi-forms');
```

### Input Sanitization

Always sanitize input:

```php
// Text fields
$clean_text = sanitize_text_field($_POST['field']);

// Email
$clean_email = sanitize_email($_POST['email']);

// Numbers
$clean_number = intval($_POST['number']);

// URLs
$clean_url = esc_url_raw($_POST['url']);

// Complex data (arrays, objects)
$clean_data = map_deep($_POST['data'], 'sanitize_text_field');
```

### SQL Queries

Always use prepared statements:

```php
global $wpdb;

// Correct: Prepared statement
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vas_form_results WHERE form_id = %s",
        $form_id
    )
);

// WRONG: Direct query (vulnerable to SQL injection)
$results = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}vas_form_results WHERE form_id = '{$form_id}'"
);
```

### Nonce Verification

Always verify nonces for AJAX requests:

```php
// Server-side
if (!check_ajax_referer('eipsi_forms_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'Invalid nonce']);
}

// Client-side
fetch(eipsi_forms_ajax.ajax_url, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'eipsi_submit_form',
        nonce: eipsi_forms_ajax.nonce, // Generated by wp_localize_script
        // ... other data
    })
});
```

---

## Contributing

### Guidelines

1. **Follow WordPress Coding Standards**
   - PHP: [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
   - JavaScript: [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
   - CSS: [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

2. **Test Thoroughly**
   - Run all automated tests
   - Manual testing checklist
   - Test on multiple browsers and devices
   - Verify backward compatibility

3. **Document Changes**
   - Update README.md if user-facing changes
   - Update CHANGELOG.md with version entry
   - Update DEVELOPER.md if API changes
   - Add inline code comments for complex logic

4. **Security First**
   - Escape all output
   - Sanitize all input
   - Use prepared statements for SQL
   - Verify nonces for AJAX

5. **Maintain Backward Compatibility**
   - Don't break existing blocks
   - Don't change database schema without migration
   - Don't remove hooks/filters without deprecation notice

### Pull Request Process

1. Fork repository
2. Create feature branch: `git checkout -b feature/your-feature-name`
3. Make changes
4. Run tests: `npm run lint:js && npm run build`
5. Commit changes: `git commit -m "Add feature: description"`
6. Push to branch: `git push origin feature/your-feature-name`
7. Create pull request with detailed description

---

## API Reference

### PHP Classes

#### `EIPSI_Database_Manager`

**Purpose:** Database schema management and auto-repair.

**Methods:**

```php
// Verify and repair schema
EIPSI_Database_Manager::verify_and_repair_schema();

// Check if schema complete
$is_complete = EIPSI_Database_Manager::is_schema_complete();

// Get missing columns
$missing = EIPSI_Database_Manager::get_missing_columns();
```

---

#### `EIPSI_External_Database`

**Purpose:** External database connection and operations.

**Methods:**

```php
// Get instance
$db = EIPSI_External_Database::get_instance();

// Connect
$connection = $db->connect();

// Test connection
$is_connected = $db->test_connection();

// Insert submission
$result = $db->insert_submission($data);
```

---

### JavaScript Functions

#### `EIPSIForms.submitForm(formData)`

Submit form programmatically.

**Parameters:**
- `formData` (object): Form submission data

**Returns:**
- `Promise<object>`: Submission result

---

#### `EIPSITracking.trackEvent(eventData)`

Track custom event.

**Parameters:**
- `eventData` (object): Event data

**Returns:**
- `void`

---

## Support

For technical support:

1. Review this developer guide
2. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
3. Review WordPress debug log
4. Check browser console for JavaScript errors
5. Contact with detailed error information

---

**Developer Guide Version:** 1.2.2  
**Last Updated:** January 2025  
**Plugin Version:** 1.2.2
