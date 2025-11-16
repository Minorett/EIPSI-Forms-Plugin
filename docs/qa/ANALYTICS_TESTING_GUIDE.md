# Analytics Tracking - Manual Testing Guide

**Quick Reference:** Step-by-step procedures for validating analytics tracking

---

## Prerequisites

### 1. Environment Setup

```bash
# Enable WordPress debug mode (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 2. Browser DevTools Setup

- **Chrome/Edge:** F12 → Network tab → Preserve log ☑
- **Firefox:** F12 → Network tab → Persist logs ☑
- **Safari:** Develop → Show Web Inspector → Network tab

### 3. Database Access

- **phpMyAdmin** OR
- **MySQL Workbench** OR
- **Terminal:** `mysql -u username -p database_name`

---

## Test 1: Basic Event Lifecycle (5 minutes)

### Procedure

1. **Open form page** in browser with DevTools open
2. **Filter Network tab:** Type `eipsi_track_event` in filter box
3. **Watch for events:**

| Action | Expected Event | Payload Check |
|--------|----------------|---------------|
| Page loads | `view` | `event_type=view` |
| Click first field | `start` | `event_type=start` |
| Navigate to page 2 | `page_change` | `page_number=2` |
| Navigate to page 3 | `page_change` | `page_number=3` |
| Submit form | `submit` | `event_type=submit` |

### Success Criteria

- ✅ All events return HTTP 200
- ✅ Each event has `"success": true` in response
- ✅ No JavaScript console errors

### Screenshots

Take screenshots of:
1. Network tab showing all events
2. Console tab (should be empty - no errors)

---

## Test 2: Session Persistence (3 minutes)

### Procedure

1. **Load form page**
2. **Open DevTools → Application → Session Storage**
3. **Expand `eipsiAnalyticsSessions` key**
4. **Copy sessionId value** (e.g., `a3f2e1d4c5b6a7e8...`)
5. **Interact with first field** (triggers `start` event)
6. **Refresh page (F5)**
7. **Check Network tab**

### Success Criteria

- ✅ **NO** new `view` or `start` events after refresh
- ✅ Same `sessionId` in Session Storage after refresh
- ✅ `viewTracked: true` and `startTracked: true` persist

### Failure Example

❌ If you see duplicate `view` events after refresh, session persistence is broken.

---

## Test 3: Abandon Event (sendBeacon) (2 minutes)

### Procedure

1. **Load form page** with DevTools Network tab open
2. **Enable "Preserve log"** (important!)
3. **Click into first field** (triggers `start` event)
4. **Close tab immediately** OR **navigate to another site**
5. **Check preserved network log**

### Success Criteria

- ✅ `abandon` event appears in network log
- ✅ Request Initiator shows `sendBeacon`
- ✅ Payload includes `page_number` (current page when abandoned)

### Network Request Details

```
POST /wp-admin/admin-ajax.php
Initiator: sendBeacon
Payload:
  action: eipsi_track_event
  event_type: abandon
  page_number: 1
  session_id: a3f2e1d4c5b6a7e8...
```

---

## Test 4: Conditional Logic (Branch Jump) (3 minutes)

### Setup

Create a form with conditional logic:
- Page 1: Radio button "Are you experiencing symptoms?"
  - "No" → Jump to Page 5 (skip detailed questions)
  - "Yes" → Continue to Page 2

### Procedure

1. **Load form page**
2. **Select "No" option** (triggers jump)
3. **Check Network tab for `branch_jump` event**

### Success Criteria

- ✅ `branch_jump` event fires
- ✅ Payload includes:
  ```
  from_page: 1
  to_page: 5
  field_id: campo-radio-q1
  matched_value: No
  ```

### Verify Metadata Storage

```sql
SELECT metadata 
FROM wp_vas_form_events 
WHERE event_type = 'branch_jump' 
ORDER BY created_at DESC 
LIMIT 1;
```

**Expected:**
```json
{"from_page":1,"to_page":5,"field_id":"campo-radio-q1","matched_value":"No"}
```

---

## Test 5: Database Verification (5 minutes)

### Query 1: Verify All Event Types

```sql
SELECT event_type, COUNT(*) as count 
FROM wp_vas_form_events 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY event_type;
```

**Expected Output:**

```
+-------------+-------+
| event_type  | count |
+-------------+-------+
| view        |     1 |
| start       |     1 |
| page_change |     3 |
| submit      |     1 |
+-------------+-------+
```

### Query 2: Session Timeline

```sql
SELECT 
    event_type,
    page_number,
    TIMEDIFF(created_at, LAG(created_at) OVER (ORDER BY created_at)) as time_delta,
    created_at
FROM wp_vas_form_events
WHERE session_id = 'YOUR_SESSION_ID_HERE'
ORDER BY created_at;
```

**Expected:** Chronological sequence of events with time deltas.

### Query 3: Check Indexes

```sql
SHOW INDEX FROM wp_vas_form_events;
```

**Expected Indexes:**
- PRIMARY (id)
- form_id
- session_id
- event_type
- created_at
- form_session (form_id, session_id)

---

## Test 6: Error Resilience (3 minutes)

### Test 6a: Invalid Nonce

```javascript
// Execute in browser console
window.eipsiTrackingConfig.nonce = 'INVALID';

// Then interact with form field
// Expected: HTTP 403, but NO console errors
```

### Test 6b: Network Failure

```javascript
// Chrome DevTools → Network tab → Offline ☑
// Then interact with form field
// Expected: Request fails silently, NO console errors, form still works
```

### Success Criteria

- ✅ Tracking errors **do not** break form functionality
- ✅ No JavaScript errors in console
- ✅ Form submission still works even if tracking fails

---

## Test 7: Multi-Form Support (4 minutes)

### Setup

Create page with 2 forms:
- Form A: `formId="consent-form"`
- Form B: `formId="demographics-form"`

### Procedure

1. **Load page**
2. **Interact with Form A only**
3. **Check Session Storage**

```javascript
// In DevTools Console
JSON.parse(sessionStorage.getItem('eipsiAnalyticsSessions'))
```

**Expected:**
```json
{
  "consent-form": {
    "sessionId": "abc123...",
    "viewTracked": true,
    "startTracked": true
  },
  "demographics-form": {
    "sessionId": "xyz789...",
    "viewTracked": true,
    "startTracked": false
  }
}
```

### Success Criteria

- ✅ Both forms have **different sessionIds**
- ✅ Form A shows `startTracked: true`
- ✅ Form B shows `startTracked: false` (not interacted with yet)

---

## Test 8: Browser Compatibility (10 minutes)

### Test Matrix

| Browser | Version | view | start | page_change | submit | abandon | Status |
|---------|---------|------|-------|-------------|--------|---------|--------|
| Chrome  | 120+    | ☐    | ☐     | ☐           | ☐      | ☐       |        |
| Firefox | 121+    | ☐    | ☐     | ☐           | ☐      | ☐       |        |
| Safari  | 17+     | ☐    | ☐     | ☐           | ☐      | ☐       |        |
| Edge    | 120+    | ☐    | ☐     | ☐           | ☐      | ☐       |        |
| Chrome Mobile | Latest | ☐ | ☐  | ☐           | ☐      | ☐       |        |
| Safari Mobile | iOS 17 | ☐ | ☐  | ☐           | ☐      | ☐       |        |

### Mobile Testing Notes

- ✅ Touch interactions should trigger `start` event
- ✅ Background tab switch should trigger `abandon` event
- ✅ Session persists across orientation changes

---

## Troubleshooting

### Issue: Events Not Firing

**Check:**
1. Is JavaScript loading? (Check browser console)
2. Is `eipsiTrackingConfig` defined?
   ```javascript
   // In console:
   console.log(window.eipsiTrackingConfig);
   // Expected: {ajaxUrl: "...", nonce: "..."}
   ```
3. Are there CORS errors in console?
4. Is WordPress AJAX working?
   ```bash
   curl -X POST http://your-site.com/wp-admin/admin-ajax.php \
     -d "action=eipsi_track_event&nonce=abc123"
   # Expected: HTTP 403 (nonce invalid, but handler responds)
   ```

### Issue: Duplicate Events After Refresh

**Check:**
1. Session Storage support:
   ```javascript
   // In console:
   typeof sessionStorage !== 'undefined'
   // Expected: true
   ```
2. Session restoration logic:
   ```javascript
   // In assets/js/eipsi-tracking.js line 23
   this.restoreSessions();
   ```

### Issue: Abandon Events Not Firing

**Check:**
1. Is `start` event tracked? (Abandon only fires if user started)
2. Is `submit` already tracked? (Abandon won't fire if submitted)
3. Is browser blocking sendBeacon? (Rare, but check console)

---

## Quick Verification Script

Run this in browser console after interacting with form:

```javascript
// Get session data
const sessions = JSON.parse(sessionStorage.getItem('eipsiAnalyticsSessions') || '{}');
console.table(sessions);

// Check if events fired
fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
        action: 'eipsi_get_db_status', // Custom check
        nonce: window.eipsiTrackingConfig.nonce
    })
});
```

---

## Export HAR for Documentation

1. **Chrome:** DevTools → Network → Right-click → "Save all as HAR"
2. **Firefox:** DevTools → Network → Gear icon → "Save All As HAR"
3. **Save to:** `docs/qa/analytics-tracking-{date}.har`

---

## Acceptance Checklist

Before marking Phase 6 complete, verify:

- [ ] ✅ All 6 event types fire correctly (view, start, page_change, submit, abandon, branch_jump)
- [ ] ✅ Session persistence works (no duplicate events after refresh)
- [ ] ✅ sendBeacon used for abandon events
- [ ] ✅ Database table `wp_vas_form_events` populated correctly
- [ ] ✅ Multi-form support confirmed (independent sessions)
- [ ] ✅ Error resilience tested (invalid nonce, network failures)
- [ ] ✅ Browser compatibility verified (Chrome, Firefox, Safari, mobile)
- [ ] ✅ No JavaScript console errors during testing
- [ ] ✅ HAR file exported and archived
- [ ] ✅ Database queries return expected results

---

**Testing Time:** ~30 minutes  
**Required Tools:** Browser DevTools, Database client, Text editor  
**Output:** Screenshots, HAR file, SQL query results

**Next Steps:** Document results in `QA_PHASE6_RESULTS.md`
