# EIPSI Forms Tracking - Quick Reference

## Event Types

| Event | Trigger | Tracked Once? | Includes Metadata? |
|-------|---------|---------------|-------------------|
| `view` | Form loaded | ✅ Yes | No |
| `start` | First field interaction | ✅ Yes | No |
| `page_change` | Navigation between pages | ❌ No (multiple) | page_number |
| `branch_jump` | Conditional logic executed | ❌ No (multiple) | from_page, to_page, field_id, matched_value |
| `submit` | Form submitted | ✅ Yes | No |
| `abandon` | Page unload without submit | ✅ Yes | page_number |

## Integration Points

```javascript
// Form initialization (line 325)
this.attachTracking(form);
  └─> EIPSITracking.registerForm(form, formId)  // Fires 'view' event

// Page navigation (line 828)
window.EIPSITracking.recordPageChange(formId, pageNumber);  // Fires 'page_change'

// Branch jump (line 949)
window.EIPSITracking.trackEvent('branch_jump', formId, {
  from_page: 2,
  to_page: 5,
  field_id: 'satisfaction',
  matched_value: 'Very Satisfied'
});

// Form submission (line 1425)
window.EIPSITracking.recordSubmit(formId);  // Fires 'submit'

// Page unload (automatic in eipsi-tracking.js)
// beforeunload event → flushAbandonEvents()  // Fires 'abandon'
```

## Database Structure

```sql
CREATE TABLE wp_vas_form_events (
    id bigint(20) unsigned AUTO_INCREMENT,
    form_id varchar(255),
    session_id varchar(255) NOT NULL,
    event_type varchar(50) NOT NULL,
    page_number int(11),
    metadata text,              -- JSON for branch_jump events
    user_agent text,
    created_at datetime NOT NULL,
    PRIMARY KEY (id)
);
```

## Common Queries

### Completion Rate
```sql
SELECT 
    form_id,
    COUNT(DISTINCT CASE WHEN event_type = 'start' THEN session_id END) as started,
    COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) as completed,
    ROUND(100.0 * COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) / 
          COUNT(DISTINCT CASE WHEN event_type = 'start' THEN session_id END), 2) as rate
FROM wp_vas_form_events
GROUP BY form_id;
```

### Branch Jump Patterns
```sql
SELECT 
    JSON_EXTRACT(metadata, '$.field_id') as field,
    JSON_EXTRACT(metadata, '$.matched_value') as value,
    JSON_EXTRACT(metadata, '$.to_page') as destination,
    COUNT(*) as count
FROM wp_vas_form_events
WHERE event_type = 'branch_jump'
GROUP BY field, value, destination
ORDER BY count DESC;
```

### Abandonment Hotspots
```sql
SELECT 
    page_number,
    COUNT(*) as abandons
FROM wp_vas_form_events
WHERE event_type = 'abandon'
GROUP BY page_number
ORDER BY abandons DESC;
```

## Testing

### CLI Test
```bash
cd /wp-content/plugins/vas-dinamico-forms
bash test-tracking-cli.sh
```

### Browser Test
1. Open `test-tracking-browser.html` in WordPress
2. Update nonce in CONFIG object
3. Open DevTools → Network tab
4. Click event buttons
5. Inspect requests to `admin-ajax.php?action=eipsi_track_event`

## Files Modified

- **admin/ajax-handlers.php** (line 239): Added `'branch_jump'` to allowed events
- **admin/ajax-handlers.php** (lines 268-285): Added metadata capture logic
- **vas-dinamico-forms.php** (line 71): Added `metadata text` column to schema
- **test-tracking-cli.sh**: Added tests 8, 12 for branch_jump

## Database Migration

For existing installations, run:
```sql
ALTER TABLE wp_vas_form_events 
ADD COLUMN metadata text DEFAULT NULL AFTER page_number;
```

Or via WP-CLI:
```bash
wp db query "ALTER TABLE wp_vas_form_events ADD COLUMN metadata text DEFAULT NULL AFTER page_number;"
```
