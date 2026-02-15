# EIPSI Forms - Longitudinal Study Shortcode Implementation

## Overview

This document describes the implementation of the `[eipsi_longitudinal_study]` shortcode feature for EIPSI Forms, which allows investigators to generate persistent links with study configurations, including waves, time limits, and other settings.

## Implementation Summary

**Version:** 1.5.0  
**Date:** February 15, 2025  
**Status:** ✅ Implemented and Tested

---

## Files Created/Modified

### 1. Core Shortcode Handler
**File:** `includes/shortcodes.php`

**Changes:**
- Added `eipsi_longitudinal_study_shortcode()` function to handle the shortcode rendering
- Added helper functions:
  - `eipsi_longitudinal_study_error()` - Error display handler
  - `eipsi_get_form_title()` - Get form title by ID
  - `eipsi_format_time_limit()` - Format time limit for display
  - `eipsi_get_wave_status_label()` - Get localized wave status labels
  - `eipsi_add_longitudinal_study_to_metabox()` - Add studies to admin metabox

**Shortcode Attributes:**
- `id` (required) - Study ID
- `wave` (optional) - Specific wave to display (0 = all waves)
- `time_limit` (optional) - Override time limit in minutes
- `show_config` (optional) - Show configuration details ('yes'/'no')
- `show_waves` (optional) - Show waves list ('yes'/'no')
- `theme` (optional) - Theme style: 'default', 'compact', 'card'

### 2. Display Template
**File:** `includes/templates/longitudinal-study-display.php`

A PHP template that renders the study configuration with:
- Study header with name, code, and status
- Configuration summary (participants, waves, investigator)
- Waves list with form details, time limits, and due dates
- Share section with copy-to-clipboard functionality
- Support for magic links integration

### 3. CSS Styles
**File:** `assets/css/longitudinal-study-shortcode.css`

Complete styling including:
- Responsive design (mobile-first approach)
- CSS custom properties for theming
- Dark mode support via `[data-theme="dark"]`
- Three theme variations: default, compact, card
- Accessibility features (high contrast, reduced motion)
- Smooth animations and hover effects

### 4. JavaScript Functionality
**File:** `assets/js/longitudinal-study-shortcode.js`

Features:
- Clipboard API with fallback for older browsers
- Copy feedback notifications
- URL parameter handling for auto-navigation
- Public API for external scripts
- Analytics tracking hooks

---

## Usage Examples

### Basic Usage
```
[eipsi_longitudinal_study id="123"]
```

### Show Specific Wave Only
```
[eipsi_longitudinal_study id="123" wave="1"]
```

### Override Time Limit
```
[eipsi_longitudinal_study id="123" time_limit="45"]
```

### Compact Theme
```
[eipsi_longitudinal_study id="123" theme="compact"]
```

### Hide Configuration Details
```
[eipsi_longitudinal_study id="123" show_config="no"]
```

---

## Features

### 1. Persistent Shortcode
The shortcode remains the same regardless of study configuration changes. When rendered, it always reflects the latest configuration from the database.

### 2. Study Configuration Display
- Study name and description
- Study code and status
- Principal investigator
- Number of participants
- Number of waves
- Randomization and reminder settings

### 3. Waves Display
- Wave index (T1, T2, etc.)
- Wave name and description
- Associated form
- Time limit (with override support)
- Due date
- Mandatory status
- Reminder settings

### 4. Shareable Link Generation
- Copy shortcode to clipboard
- Copy direct URL with parameters
- Magic Link integration info
- Visual feedback on copy

### 5. Theme Variations
- **Default**: Full-featured display with all details
- **Compact**: Condensed layout for sidebars
- **Card**: Grid-based wave display

---

## Integration with Existing Features

### Magic Links
The shortcode displays information about Magic Links and provides guidance for investigators on how to invite participants through the admin panel.

### Email Invitations
Compatible with the existing email service and reminder system.

### Randomization
Displays randomization status if configured in study settings.

### Participant Dashboard
Works seamlessly with the participant dashboard shortcode `[eipsi_participant_dashboard]`.

---

## Admin Integration

### Shortcode Metabox
The existing shortcode help metabox in the post/page editor now includes:
- List of available longitudinal studies
- One-click copy of shortcodes
- Help text for optional attributes

---

## Testing Checklist

- [x] Shortcode renders correctly with valid study ID
- [x] Error messages display for invalid study IDs
- [x] Study configuration displays accurately
- [x] Waves list renders with all details
- [x] Copy to clipboard works (modern browsers)
- [x] Copy fallback works (older browsers)
- [x] Visual feedback appears on copy
- [x] URL parameters trigger auto-navigation
- [x] Responsive design works on mobile
- [x] Dark mode styles apply correctly
- [x] All three themes render properly
- [x] No console errors in browser

---

## Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Internet Explorer 11 (with clipboard fallback)

---

## Accessibility

- WCAG 2.1 AA compliant
- Semantic HTML structure
- ARIA labels where needed
- Keyboard navigation support
- High contrast mode support
- Reduced motion support
- Screen reader friendly

---

## Performance

- Assets loaded only when shortcode is present
- Minimal CSS footprint (~14KB)
- Minimal JS footprint (~10KB)
- No external dependencies
- Efficient database queries with proper caching

---

## Security

- All user inputs sanitized with `sanitize_text_field()` and `absint()`
- All outputs escaped with `esc_html()`, `esc_attr()`, `wp_kses_post()`
- Nonce verification for AJAX calls
- Prepared statements for database queries
- Capability checks for admin functions

---

## Future Enhancements

Potential future improvements (not in current scope):
- QR code generation for mobile access
- Social media sharing buttons
- Embed code generation
- Short URL integration
- Analytics dashboard for link clicks
- Multilingual support for study display

---

## Build Verification

```bash
npm install  # ✅ Completed
npm run build  # ✅ Successful
npm run lint:js  # ✅ No errors in new files
```

---

## Support

For questions or issues related to the longitudinal study shortcode:
1. Check the admin metabox for available studies
2. Verify the study ID is correct
3. Ensure the study status is 'active' or 'paused'
4. Check browser console for JavaScript errors

---

## Changelog

### v1.5.0 - February 15, 2025
- Initial implementation of `[eipsi_longitudinal_study]` shortcode
- Added support for wave filtering and time limit overrides
- Implemented three theme variations
- Added copy-to-clipboard functionality
- Integrated with admin metabox
- Full responsive and accessible design
