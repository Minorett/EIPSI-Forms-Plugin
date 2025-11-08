# EIPSI Tracking Handler - Testing Guide

## Quick Testing Methods

This guide provides step-by-step instructions for testing the tracking handler implementation.

---

## Method 1: Browser Console (Quickest)

### Prerequisites
- WordPress site with EIPSI Forms plugin activated
- Browser with developer tools (Chrome, Firefox, Safari)

### Steps

1. **Load a page with a form** (or any page where tracking is loaded)

2. **Open Developer Console** (F12 or Cmd+Option+I)

3. **Verify tracking is loaded:**
   ```javascript
   console.log(window.EIPSITracking);
   console.log(eipsiTrackingConfig);
   ```

4. **Test a 'view' event:**
   ```javascript
   fetch(eipsiTrackingConfig.ajaxUrl, {
       method: 'POST',
       headers: {
           'Content-Type': 'application/x-www-form-urlencoded'
       },
       body: new URLSearchParams({
           action: 'eipsi_track_event',
           nonce: eipsiTrackingConfig.nonce,
           form_id: 'test-form',
           session_id: 'test-' + Date.now(),
           event_type: 'view',
           user_agent: navigator.userAgent
       })
   })
   .then(r => r.json())
   .then(d => {
       console.log('✅ Response:', d);
       if (d.success) {
           console.log('✅ Event tracked! ID:', d.data.event_id);
       } else {
           console.error('❌ Error:', d.data.message);
       }
   });
   ```

5. **Check Network Tab:**
   - Look for POST to `admin-ajax.php`
   - Status should be 200 OK
   - Response should show `success: true`

6. **Verify in database:**
   ```javascript
   // You'll need to check database separately
   // See Database Verification section below
   ```

**Expected Result:** Console shows success message with event ID.

---

## Method 2: Test HTML Interface (Most User-Friendly)

### Prerequisites
- Browser only (no WordPress needed for initial test)
- EIPSI Forms plugin activated on WordPress site

### Steps

1. **Open test page:**
   ```bash
   # From plugin directory
   open test-tracking.html
   
   # Or navigate to:
   # http://your-site.com/wp-content/plugins/vas-dinamico-forms/test-tracking.html
   ```

2. **Configure the page:**
   - Update "AJAX URL" to your WordPress site's admin-ajax.php
     - Example: `http://localhost/wp-admin/admin-ajax.php`
   - Get nonce from your site:
     1. Load a page with a form
     2. Open console
     3. Run: `console.log(eipsiTrackingConfig.nonce)`
     4. Copy the nonce value
   - Paste nonce into "Tracking Nonce" field

3. **Test events:**
   - Click "Test View Event"
   - Click "Test Start Event"
   - Click "Test Page Change Event"
   - Click "Test Submit Event"
   - Click "Test Abandon Event"

4. **Test error handling:**
   - Click "Test Invalid Event"
   - Click "Test Invalid Nonce"

5. **Review results:**
   - Success messages appear in green
   - Error messages appear in red
   - JSON responses shown for debugging

**Expected Result:** Green success messages for valid events, red error messages for invalid ones.

---

## Method 3: WP-CLI Automated Tests (Most Comprehensive)

### Prerequisites
- WP-CLI installed
- WordPress accessible via command line
- Plugin activated

### Steps

1. **Navigate to WordPress root:**
   ```bash
   cd /path/to/wordpress
   ```

2. **Run test script:**
   ```bash
   ./wp-content/plugins/vas-dinamico-forms/test-tracking-cli.sh
   ```

3. **Review results:**
   - Test results displayed with ✓/✗ indicators
   - Summary shows pass/fail counts
   - Database entries displayed

**Expected Result:** All 10 tests pass.

### Individual WP-CLI Commands

Test specific functionality:

```bash
# Check table exists
wp db query "SHOW TABLES LIKE '%vas_form_events%';"

# Check table structure
wp db query "DESCRIBE wp_vas_form_events;"

# Test a single event
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'manual-test';
\$_POST['session_id'] = 'test-' . time();
\$_POST['event_type'] = 'view';
\$_POST['user_agent'] = 'WP-CLI Manual Test';
do_action('wp_ajax_nopriv_eipsi_track_event');
"

# View recent events
wp db query "SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 5;"
```

---

## Method 4: Database Verification

### Using WP-CLI

```bash
# View all events
wp db query "SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 10;"

# Count events by type
wp db query "
SELECT event_type, COUNT(*) as count 
FROM wp_vas_form_events 
GROUP BY event_type;
"

# Check specific session
wp db query "
SELECT * FROM wp_vas_form_events 
WHERE session_id = 'your-session-id' 
ORDER BY created_at;
"
```

### Using phpMyAdmin

1. Login to phpMyAdmin
2. Select your WordPress database
3. Find table `wp_vas_form_events`
4. Browse or run queries from `tracking-queries.sql`

### Using MySQL Command Line

```bash
mysql -u username -p database_name

# Then run queries:
SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 10;
```

---

## Method 5: Frontend Form Testing (Real-World)

### Prerequisites
- Page with EIPSI form
- Browser with developer tools

### Steps

1. **Load form page**

2. **Open Network tab** (F12 → Network)

3. **Filter by XHR requests**

4. **Interact with form:**
   - Page loads → Should see 'view' event
   - Click first field → Should see 'start' event
   - Navigate pages → Should see 'page_change' events
   - Submit form → Should see 'submit' event
   - Close page → Should see 'abandon' event (if not submitted)

5. **Check each request:**
   - URL: `admin-ajax.php`
   - Status: 200 OK
   - Response: `{"success":true, ...}`

6. **Verify in database:**
   ```bash
   wp db query "
   SELECT * FROM wp_vas_form_events 
   WHERE form_id = 'your-form-id' 
   ORDER BY created_at DESC;
   "
   ```

**Expected Result:** Events tracked automatically as you interact with form.

---

## Common Test Scenarios

### Scenario 1: Complete Form Submission Flow

**Test:** User completes form successfully

**Events Expected:**
1. `view` - Page loads
2. `start` - User clicks first field
3. `page_change` (if multi-page) - User navigates
4. `submit` - User submits form

**Verification:**
```sql
SELECT event_type, created_at 
FROM wp_vas_form_events 
WHERE session_id = 'test-session-id' 
ORDER BY created_at;
```

### Scenario 2: Form Abandonment

**Test:** User views form but doesn't submit

**Events Expected:**
1. `view` - Page loads
2. `start` - User clicks field
3. `abandon` - User closes/leaves page

**Verification:**
```sql
SELECT * FROM wp_vas_form_events 
WHERE event_type = 'abandon' 
ORDER BY created_at DESC;
```

### Scenario 3: Invalid Request Handling

**Test:** Malformed requests rejected

**Requests to Test:**
- Invalid event type
- Missing nonce
- Missing session_id
- Invalid nonce

**Expected:** All return error responses, no database entries

---

## Troubleshooting Test Failures

### "Table doesn't exist"

**Solution:**
```bash
# Activate plugin
wp plugin activate vas-dinamico-forms

# Or manually create table
wp db query < tracking-queries.sql
```

### "Invalid nonce"

**Solution:**
- Nonces expire after 12-24 hours
- Refresh page to get new nonce
- Don't hardcode nonces in tests

### "Function not found"

**Solution:**
```bash
# Check plugin is activated
wp plugin list

# Check ajax-handlers.php is loaded
wp eval "echo function_exists('eipsi_track_event_handler') ? 'yes' : 'no';"
```

### "404 on admin-ajax.php"

**Solution:**
- Check WordPress is accessible
- Verify URL is correct
- Check .htaccess rules
- Test: `curl http://your-site.com/wp-admin/admin-ajax.php`

### "Events not appearing in database"

**Solution:**
```bash
# Check database connection
wp db check

# Check table permissions
wp db query "SELECT * FROM wp_vas_form_events LIMIT 1;"

# Check WordPress debug log
tail -f wp-content/debug.log
```

---

## Performance Testing

### Load Testing

Test with multiple concurrent requests:

```bash
# Using Apache Bench (if installed)
ab -n 100 -c 10 -p post_data.txt -T 'application/x-www-form-urlencoded' \
   http://your-site.com/wp-admin/admin-ajax.php
```

Where `post_data.txt` contains:
```
action=eipsi_track_event&nonce=YOUR_NONCE&form_id=test&session_id=load-test&event_type=view&user_agent=LoadTest
```

### Database Performance

```sql
-- Check query performance
EXPLAIN SELECT * FROM wp_vas_form_events 
WHERE form_id = 'test' AND event_type = 'view';

-- Check index usage
SHOW INDEX FROM wp_vas_form_events;

-- Analyze table
ANALYZE TABLE wp_vas_form_events;
```

---

## Test Data Cleanup

### Remove test events:

```bash
# WP-CLI
wp db query "DELETE FROM wp_vas_form_events WHERE form_id LIKE 'test%';"

# Or keep last 100 events for reference
wp db query "
DELETE FROM wp_vas_form_events 
WHERE id NOT IN (
    SELECT id FROM (
        SELECT id FROM wp_vas_form_events 
        ORDER BY created_at DESC LIMIT 100
    ) tmp
);
"
```

### Reset table:

```bash
# Truncate (removes all data, keeps structure)
wp db query "TRUNCATE TABLE wp_vas_form_events;"
```

---

## Success Criteria

### ✅ Tests Pass If:

1. **Database**
   - Table `wp_vas_form_events` exists
   - 7 columns present
   - 5 indexes created

2. **AJAX Handler**
   - Function `eipsi_track_event_handler` exists
   - Returns 200 for valid requests
   - Returns 403 for invalid nonce
   - Returns 400 for invalid event type

3. **Event Tracking**
   - Events inserted into database
   - Timestamps recorded correctly
   - Session IDs preserved
   - Page numbers tracked (when provided)

4. **Error Handling**
   - Invalid requests rejected gracefully
   - Database errors logged but don't crash
   - Frontend continues working on failures

---

## Next Steps After Testing

Once all tests pass:

1. **Review Analytics:**
   ```bash
   # View summary
   wp db query < tracking-queries.sql
   ```

2. **Set Up Monitoring:**
   - Check database size regularly
   - Monitor error logs
   - Review abandonment rates

3. **Implement Retention Policy:**
   - Decide how long to keep events
   - Set up automatic cleanup
   - Export data for research before deletion

4. **Document for Team:**
   - Share access to analytics
   - Train researchers on data export
   - Establish review schedule

---

## Support Resources

- **Full Documentation:** `TRACKING_IMPLEMENTATION.md`
- **SQL Queries:** `tracking-queries.sql`
- **Test Interface:** `test-tracking.html`
- **Automated Tests:** `test-tracking-cli.sh`
- **Changes Summary:** `CHANGES.md`

---

## Quick Reference

### Most Common Test Command
```bash
./test-tracking-cli.sh
```

### Most Common SQL Query
```sql
SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 10;
```

### Most Common Browser Console Test
```javascript
console.log(eipsiTrackingConfig);
```

---

**Happy Testing! 🧪**

If you encounter issues not covered here, check `TRACKING_IMPLEMENTATION.md` for detailed troubleshooting.
