# EIPSI Tracking Handler Implementation

## Overview

This document describes the implementation of the event tracking system for the EIPSI Forms plugin. The tracking system captures user interactions with forms for research analytics purposes.

## Components Implemented

### 1. Database Table (`vas_form_events`)

A dedicated table for storing tracking events with the following schema:

```sql
CREATE TABLE {prefix}vas_form_events (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    form_id varchar(255) NOT NULL DEFAULT '',
    session_id varchar(255) NOT NULL,
    event_type varchar(50) NOT NULL,
    page_number int(11) DEFAULT NULL,
    user_agent text DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY form_id (form_id),
    KEY session_id (session_id),
    KEY event_type (event_type),
    KEY created_at (created_at),
    KEY form_session (form_id, session_id)
)
```

**Table Creation:**
- Created automatically on plugin activation via `vas_dinamico_activate()` hook
- Uses `dbDelta()` for safe schema updates
- Includes optimized indexes for common query patterns

### 2. AJAX Handler (`eipsi_track_event_handler`)

Location: `/admin/ajax-handlers.php`

**Endpoints:**
- `wp_ajax_nopriv_eipsi_track_event` - For non-logged-in users
- `wp_ajax_eipsi_track_event` - For logged-in users

**Features:**
- ✅ Nonce verification (`eipsi_tracking_nonce`)
- ✅ Allowed event types validation
- ✅ Input sanitization for all POST data
- ✅ Graceful error handling
- ✅ Resilient design (returns success even on DB errors)

**Allowed Event Types:**
1. `view` - Form viewed
2. `start` - User started interacting
3. `page_change` - Multi-page form navigation
4. `submit` - Form submitted
5. `abandon` - User left without completing

**Request Parameters:**
```javascript
{
    action: 'eipsi_track_event',
    nonce: 'eipsi_tracking_nonce',  // Required
    form_id: 'string',              // Optional
    session_id: 'string',           // Required
    event_type: 'string',           // Required (must be in allowed list)
    page_number: 'integer',         // Optional
    user_agent: 'string'            // Optional
}
```

**Response Format:**

Success:
```json
{
    "success": true,
    "data": {
        "message": "Event tracked successfully.",
        "event_id": 123,
        "tracked": true
    }
}
```

Error (Invalid Nonce):
```json
{
    "success": false,
    "data": {
        "message": "Invalid security token."
    }
}
```

Error (Invalid Event Type):
```json
{
    "success": false,
    "data": {
        "message": "Invalid event type."
    }
}
```

### 3. Frontend Integration

The tracking handler works seamlessly with the existing frontend tracking script:

**File:** `/assets/js/eipsi-tracking.js`

**Configuration:** The nonce is already being generated and passed to the frontend:
```php
wp_localize_script('eipsi-tracking-js', 'eipsiTrackingConfig', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('eipsi_tracking_nonce'),
));
```

**Frontend Usage:**
```javascript
// Events are automatically tracked by the EIPSITracking module
window.EIPSITracking.registerForm(formElement, 'form-id');
window.EIPSITracking.recordPageChange('form-id', 2);
window.EIPSITracking.recordSubmit('form-id');
```

## Security Features

### Nonce Verification
- Every request must include a valid `eipsi_tracking_nonce`
- Nonces are generated per-page-load and expire after 12-24 hours (WordPress default)
- Invalid nonces return 403 Forbidden response

### Input Sanitization
- `form_id`: `sanitize_text_field()` - Removes tags and special characters
- `session_id`: `sanitize_text_field()` - Removes tags and special characters
- `event_type`: `sanitize_text_field()` + whitelist validation
- `page_number`: `intval()` - Ensures integer value
- `user_agent`: `sanitize_text_field()` - Removes tags and special characters

### Validation
- Event types must match allowed list: `view`, `start`, `page_change`, `submit`, `abandon`
- Session ID is required (cannot be empty)
- Invalid requests return appropriate HTTP status codes

## Error Handling

### Resilient Design Philosophy
The tracking system is designed to **never break the user experience**:

1. **Database Errors:** If insertion fails, the handler logs the error but still returns success
2. **Network Errors:** Frontend catches all fetch errors silently
3. **Missing Data:** Optional fields can be omitted without causing failures

### Error Logging
Database errors are logged to PHP error log:
```php
error_log('EIPSI Tracking: Failed to insert event - ' . $wpdb->last_error);
```

## Testing

### Manual Testing with Test Page

A test HTML page is provided: `/test-tracking.html`

**Setup:**
1. Open `test-tracking.html` in a browser
2. Update the AJAX URL to your WordPress site's admin-ajax.php
3. Get the nonce value by viewing the page source of a form page:
   ```javascript
   console.log(eipsiTrackingConfig.nonce);
   ```
4. Click the test buttons to send events
5. Check database for new entries

### Testing with WordPress Frontend

1. **Activate the plugin:**
   ```bash
   wp plugin activate vas-dinamico-forms
   ```

2. **Check database table:**
   ```bash
   wp db query "DESCRIBE wp_vas_form_events;"
   ```

3. **Load a form page** and open browser console:
   ```javascript
   // Check configuration
   console.log(eipsiTrackingConfig);
   
   // Manually trigger an event
   fetch(eipsiTrackingConfig.ajaxUrl, {
       method: 'POST',
       headers: {
           'Content-Type': 'application/x-www-form-urlencoded'
       },
       body: new URLSearchParams({
           action: 'eipsi_track_event',
           nonce: eipsiTrackingConfig.nonce,
           form_id: 'test-form',
           session_id: 'test-session-123',
           event_type: 'view',
           user_agent: navigator.userAgent
       })
   })
   .then(r => r.json())
   .then(d => console.log('Response:', d));
   ```

4. **Check database entries:**
   ```bash
   wp db query "SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 5;"
   ```

### Testing with WP-CLI

```bash
# Test view event
wp eval "
\$_POST['action'] = 'eipsi_track_event';
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = 'cli-session-' . time();
\$_POST['event_type'] = 'view';
\$_POST['user_agent'] = 'WP-CLI Test';
do_action('wp_ajax_nopriv_eipsi_track_event');
"

# Check results
wp db query "SELECT * FROM wp_vas_form_events WHERE form_id = 'cli-test-form';"
```

## Database Queries for Analytics

### Event Counts by Type
```sql
SELECT event_type, COUNT(*) as count
FROM wp_vas_form_events
GROUP BY event_type
ORDER BY count DESC;
```

### Completion Rate (Submit vs View)
```sql
SELECT 
    COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END) as views,
    COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) as submits,
    ROUND(
        COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) * 100.0 /
        NULLIF(COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END), 0), 
        2
    ) as completion_rate
FROM wp_vas_form_events;
```

### Abandonment Analysis
```sql
SELECT 
    form_id,
    COUNT(DISTINCT session_id) as total_sessions,
    COUNT(DISTINCT CASE WHEN event_type = 'abandon' THEN session_id END) as abandoned,
    ROUND(
        COUNT(DISTINCT CASE WHEN event_type = 'abandon' THEN session_id END) * 100.0 /
        COUNT(DISTINCT session_id),
        2
    ) as abandon_rate
FROM wp_vas_form_events
GROUP BY form_id;
```

### Events Timeline
```sql
SELECT 
    form_id,
    session_id,
    event_type,
    page_number,
    created_at
FROM wp_vas_form_events
WHERE session_id = 'specific-session-id'
ORDER BY created_at ASC;
```

### Recent Activity
```sql
SELECT 
    e.form_id,
    e.event_type,
    e.page_number,
    e.created_at,
    LEFT(e.user_agent, 50) as browser
FROM wp_vas_form_events e
ORDER BY e.created_at DESC
LIMIT 20;
```

## Performance Considerations

### Database Indexes
The table includes optimized indexes for common query patterns:
- `form_id` - Filter events by form
- `session_id` - Track individual user sessions
- `event_type` - Aggregate by event type
- `created_at` - Time-based queries
- `form_session` - Composite index for form+session lookups

### Async Tracking
The frontend uses:
- `fetch()` with fire-and-forget pattern
- `navigator.sendBeacon()` for page unload events
- Resilient error handling (no user-facing errors)

### Storage Efficiency
- Session data stored in `sessionStorage` (not database)
- Only essential event data persisted
- User agent strings stored as TEXT (variable length)

## Future Enhancements

Potential improvements for future iterations:

1. **Analytics Dashboard:** Admin UI for viewing tracking data
2. **Export Functionality:** CSV/Excel export for researchers
3. **Data Retention:** Configurable cleanup of old events
4. **IP Address Logging:** Optional IP capture for geo-analytics
5. **Custom Event Parameters:** Extensible metadata storage
6. **Real-time Monitoring:** WebSocket-based live analytics
7. **A/B Testing:** Form variant tracking
8. **Heatmaps:** Field-level interaction tracking

## Troubleshooting

### Events Not Being Tracked

1. **Check JavaScript Console:**
   ```javascript
   console.log(window.EIPSITracking);
   console.log(eipsiTrackingConfig);
   ```

2. **Verify Nonce:**
   ```javascript
   console.log('Nonce:', eipsiTrackingConfig.nonce);
   ```

3. **Check Network Tab:**
   - Look for POST requests to `admin-ajax.php`
   - Check response codes (200 = success)
   - Review response payload

4. **Database Table Exists:**
   ```bash
   wp db query "SHOW TABLES LIKE '%vas_form_events%';"
   ```

### 400 Bad Request Responses

Check for:
- Invalid event type (must be in allowed list)
- Missing session_id
- Invalid nonce (refresh page to get new nonce)

### 403 Forbidden Responses

- Nonce verification failed
- Refresh the page to get a new nonce
- Check WordPress nonce settings

## Code References

### Files Modified/Created

1. **`/vas-dinamico-forms.php`**
   - Updated `vas_dinamico_activate()` to create events table
   - Lines: 63-82

2. **`/admin/ajax-handlers.php`**
   - Added AJAX action hooks (lines 12-13)
   - Added `eipsi_track_event_handler()` function (lines 229-306)

3. **`/test-tracking.html`** (NEW)
   - Manual testing interface

4. **`/TRACKING_IMPLEMENTATION.md`** (NEW)
   - This documentation file

### Existing Integration Points

- **Frontend Tracking:** `/assets/js/eipsi-tracking.js` (unchanged)
- **Form Handler:** `/assets/js/eipsi-forms.js` (unchanged)
- **Nonce Generation:** `/vas-dinamico-forms.php` lines 273-276 (unchanged)

## Compliance Notes

### GDPR/Privacy Considerations

The tracking system collects:
- ✅ Session IDs (anonymous, randomly generated)
- ✅ Event types (behavioral data)
- ✅ Timestamps (when events occurred)
- ✅ User agents (browser/device info)
- ❌ No personal identifiable information (PII)
- ❌ No IP addresses (by default)

**Recommendation:** Include tracking disclosure in your privacy policy and consent forms.

### Research Ethics

This tracking system is designed for psychotherapy research:
- Participant behavior is tracked anonymously
- Session IDs cannot be linked back to individuals
- Data helps researchers understand form usability
- Complies with clinical research data standards

---

## Summary

The EIPSI tracking handler is now fully implemented and ready for use. It provides:

✅ Secure, nonce-protected AJAX endpoints
✅ Validated and sanitized data storage
✅ Resilient error handling
✅ Research-grade analytics capabilities
✅ Performance-optimized database schema
✅ Comprehensive testing tools

The system prevents 400 responses by properly handling all tracking requests and returning appropriate success/error responses based on validation results.
