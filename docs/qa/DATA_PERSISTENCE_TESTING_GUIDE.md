# Data Persistence Testing Guide

**Plugin:** EIPSI Forms (VAS Dinamico Forms)  
**Version:** 1.2.0  
**Testing Focus:** Data persistence, database switching, fallback behavior  
**Prerequisite:** WordPress 6.7+, PHP 7.4+, MySQL 5.7+  
**Duration:** ~2 hours for complete manual testing

---

## Table of Contents

1. [Environment Setup](#1-environment-setup)
2. [Default Storage Testing](#2-default-storage-testing)
3. [External Database Testing](#3-external-database-testing)
4. [Fallback Behavior Testing](#4-fallback-behavior-testing)
5. [Session Persistence Testing](#5-session-persistence-testing)
6. [Database Switching Testing](#6-database-switching-testing)
7. [Data Integrity Verification](#7-data-integrity-verification)
8. [SQL Validation Queries](#8-sql-validation-queries)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Environment Setup

### Prerequisites

#### WordPress Instance
```bash
# Enable debug logging
# Add to wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### Database Access Tools
- **Option 1:** phpMyAdmin (recommended for beginners)
- **Option 2:** MySQL CLI
  ```bash
  mysql -u root -p
  ```
- **Option 3:** MySQL Workbench

#### External Database (Optional)
```sql
-- Create test database
CREATE DATABASE research_db_external CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create dedicated user
CREATE USER 'eipsi_test_user'@'%' IDENTIFIED BY 'secure_password_123';
GRANT ALL PRIVILEGES ON research_db_external.* TO 'eipsi_test_user'@'%';
FLUSH PRIVILEGES;
```

#### Test Forms Setup
1. Create a page: **Test Forms**
2. Add "EIPSI Form Container" block
3. Add fields:
   - Text input: "Name"
   - Email input: "Email"
   - Textarea: "Message"
   - Likert scale (optional)
   - VAS slider (optional)

### Pre-Test Checklist

- [ ] WordPress debug.log is writable and empty
- [ ] EIPSI Forms plugin activated
- [ ] Test form created and published
- [ ] Database access confirmed
- [ ] Browser DevTools familiar (Network, Console, Storage tabs)

---

## 2. Default Storage Testing

### Test 2.1: Clean Installation

**Objective:** Verify tables created on plugin activation

**Steps:**
1. Deactivate EIPSI Forms plugin
2. Access database (phpMyAdmin or CLI)
3. Drop tables:
   ```sql
   DROP TABLE IF EXISTS wp_vas_form_results;
   DROP TABLE IF EXISTS wp_vas_form_events;
   ```
4. Reactivate plugin
5. Verify tables re-created

**Expected Results:**
- `wp_vas_form_results` exists with 17 columns
- `wp_vas_form_events` exists with 7 columns
- No PHP errors in debug.log

**Validation Query:**
```sql
SHOW TABLES LIKE 'wp_vas_form%';

-- Should return:
-- wp_vas_form_results
-- wp_vas_form_events
```

**✅ Pass Criteria:** Both tables exist  
**❌ Fail Action:** Check debug.log for activation errors

---

### Test 2.2: Form Submission

**Objective:** Submit form and verify record inserted

**Steps:**
1. Navigate to test form
2. Open Browser DevTools → Network tab
3. Fill out form:
   - Name: "John Doe"
   - Email: "john.doe@example.com"
   - Message: "Test submission 1"
4. Click Submit
5. Verify success message

**Expected AJAX Response:**
```json
{
  "success": true,
  "data": {
    "message": "Form submitted successfully!",
    "external_db": false,
    "insert_id": 1
  }
}
```

**Validation Query:**
```sql
SELECT 
    id,
    form_id,
    participant_id,
    form_name,
    created_at,
    duration_seconds,
    LENGTH(form_responses) as json_size
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 1;
```

**Expected Output:**
```
id: 1
form_id: TF-a3b2c1
participant_id: FP-8a7b6c5d
form_name: test-form
created_at: 2025-01-16 14:23:45
duration_seconds: 12.456
json_size: 89
```

**✅ Pass Criteria:** Record inserted with all fields populated  
**❌ Fail Action:** Check AJAX response for errors, inspect debug.log

---

### Test 2.3: Timestamp Precision

**Objective:** Verify millisecond-precision timestamps

**Steps:**
1. Submit form (as in Test 2.2)
2. Record exact start time from browser (open console, type `Date.now()`)
3. Complete form
4. Check database timestamps

**Validation Query:**
```sql
SELECT 
    id,
    start_timestamp_ms,
    end_timestamp_ms,
    duration_seconds,
    ROUND((end_timestamp_ms - start_timestamp_ms) / 1000, 3) as calculated_duration
FROM wp_vas_form_results
WHERE id = <insert_id>;
```

**Expected:**
- `start_timestamp_ms` is 13 digits (e.g., 1705414980000)
- `end_timestamp_ms` > `start_timestamp_ms`
- `duration_seconds` matches calculated duration

**✅ Pass Criteria:** Timestamps accurate to milliseconds  
**❌ Fail Action:** Check JavaScript console for timing errors

---

### Test 2.4: JSON Payload

**Objective:** Verify form responses stored as valid JSON

**Steps:**
1. Submit form with special characters:
   - Name: `John "Doe" O'Brien`
   - Message: `Line 1\nLine 2 & "quoted"`
2. Check database

**Validation Query:**
```sql
SELECT 
    id,
    JSON_VALID(form_responses) as is_valid,
    JSON_EXTRACT(form_responses, '$.name') as name_value,
    JSON_EXTRACT(form_responses, '$.message') as message_value,
    form_responses
FROM wp_vas_form_results
WHERE id = <insert_id>;
```

**Expected:**
- `is_valid` = 1
- Special characters escaped correctly
- JSON can be parsed by `JSON_EXTRACT`

**✅ Pass Criteria:** JSON_VALID returns 1, no corruption  
**❌ Fail Action:** Check PHP json_encode errors

---

### Test 2.5: Analytics Events

**Objective:** Verify tracking events recorded

**Steps:**
1. Clear sessionStorage (DevTools → Application → Storage → Clear)
2. Load form (expect `view` event)
3. Click into Name field (expect `start` event)
4. Navigate away WITHOUT submitting (expect `abandon` event)

**Validation Query:**
```sql
-- Get session ID from browser console:
-- JSON.parse(sessionStorage.getItem('eipsiAnalyticsSessions'))

SELECT 
    id,
    event_type,
    page_number,
    created_at
FROM wp_vas_form_events
WHERE session_id = '<session_id_from_browser>'
ORDER BY created_at ASC;
```

**Expected Sequence:**
```
id  event_type  created_at
1   view        14:20:00
2   start       14:20:05
3   abandon     14:20:30
```

**✅ Pass Criteria:** All 3 events tracked in correct order  
**❌ Fail Action:** Check Network tab for failed AJAX requests

---

### Test 2.6: form_id Stability

**Objective:** Same form name generates same form_id

**Steps:**
1. Submit form 3 times with same form name
2. Check form_id in database

**Validation Query:**
```sql
SELECT 
    form_name,
    form_id,
    COUNT(*) as submission_count
FROM wp_vas_form_results
WHERE form_name = 'test-form'
GROUP BY form_name, form_id;
```

**Expected:**
- All submissions have identical `form_id`
- Format: `[2-3 uppercase letters]-[6 hex chars]`

**✅ Pass Criteria:** form_id is stable  
**❌ Fail Action:** Check `generate_stable_form_id` function

---

### Test 2.7: participant_id Stability

**Objective:** Same email generates same participant_id

**Steps:**
1. Submit form with email: `test@example.com`
2. Submit again with same email
3. Check participant_id

**Validation Query:**
```sql
SELECT 
    participant_id,
    JSON_EXTRACT(form_responses, '$.email') as email,
    COUNT(*) as count
FROM wp_vas_form_results
WHERE JSON_EXTRACT(form_responses, '$.email') = 'test@example.com'
GROUP BY participant_id;
```

**Expected:**
- Both submissions have same `participant_id`
- Format: `FP-[8 hex chars]`
- participant_id does NOT contain the email

**✅ Pass Criteria:** participant_id is stable and hashed  
**❌ Fail Action:** Check `generateStableFingerprint` function

---

## 3. External Database Testing

### Test 3.1: Connection Test

**Objective:** Verify external DB connection

**Steps:**
1. Navigate to **EIPSI Forms → Configuration**
2. Enter external DB credentials:
   - Host: `localhost` (or external IP)
   - User: `eipsi_test_user`
   - Password: `secure_password_123`
   - Database: `research_db_external`
3. Click **Test Connection**

**Expected Response:**
```
✅ Connection successful! Schema validated.
Database: research_db_external
Records: 0
```

**External DB Validation:**
```sql
-- Switch to external database
USE research_db_external;

-- Check if table was created
SHOW TABLES LIKE '%vas_form_results';

-- Verify schema
SHOW CREATE TABLE wp_vas_form_results;
-- OR
SHOW CREATE TABLE vas_form_results;
```

**✅ Pass Criteria:** Success message, table auto-created  
**❌ Fail Action:** Check credentials, firewall, MySQL logs

---

### Test 3.2: Save Configuration

**Objective:** Save external DB credentials securely

**Steps:**
1. After successful test (Test 3.1)
2. Click **Save Configuration**
3. Check `wp_options` table

**Validation Query (WordPress DB):**
```sql
SELECT 
    option_name,
    option_value
FROM wp_options
WHERE option_name LIKE 'eipsi_external_db_%'
ORDER BY option_name;
```

**Expected Options:**
```
eipsi_external_db_enabled: 1
eipsi_external_db_host: localhost
eipsi_external_db_user: eipsi_test_user
eipsi_external_db_password: [base64 encrypted string]
eipsi_external_db_name: research_db_external
eipsi_external_db_last_updated: 2025-01-16 14:30:00
```

**✅ Pass Criteria:** Password is encrypted, not plaintext  
**❌ Fail Action:** Check encryption functions

---

### Test 3.3: Form Submission to External DB

**Objective:** Submissions stored in external DB

**Steps:**
1. Ensure external DB is enabled (Test 3.2)
2. Submit test form
3. Verify record in EXTERNAL DB (not WordPress DB)

**Expected AJAX Response:**
```json
{
  "success": true,
  "data": {
    "message": "Form submitted successfully!",
    "external_db": true,
    "insert_id": 1
  }
}
```

**Validation (External DB):**
```sql
-- In research_db_external
SELECT 
    id,
    form_id,
    form_name,
    created_at
FROM vas_form_results
-- OR wp_vas_form_results
ORDER BY id DESC
LIMIT 1;
```

**Validation (WordPress DB - should be EMPTY):**
```sql
-- In WordPress DB
SELECT COUNT(*) as wp_count
FROM wp_vas_form_results
WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE);

-- Expected: 0 (since external DB is active)
```

**✅ Pass Criteria:** Record in external DB, NOT in WordPress DB  
**❌ Fail Action:** Check `is_enabled()` and `insert_form_submission()`

---

### Test 3.4: Schema Migration

**Objective:** Missing columns added automatically

**Steps:**
1. In external DB, create incomplete table:
   ```sql
   DROP TABLE IF EXISTS vas_form_results;
   CREATE TABLE vas_form_results (
       id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
       form_name varchar(255) NOT NULL,
       created_at datetime NOT NULL,
       form_responses longtext,
       PRIMARY KEY (id)
   );
   ```
2. In WordPress admin, click **Test Connection**
3. Verify columns added

**Validation Query:**
```sql
SHOW COLUMNS FROM vas_form_results;
```

**Expected:**
- All 17 columns present after test connection
- New columns: `form_id`, `participant_id`, `duration_seconds`, etc.

**✅ Pass Criteria:** Missing columns auto-added  
**❌ Fail Action:** Check `ensure_required_columns` function

---

## 4. Fallback Behavior Testing

### Test 4.1: Connection Failure Fallback

**Objective:** Submission succeeds even when external DB fails

**Steps:**
1. Enable external DB (as in Test 3.2)
2. **BREAK** external DB:
   - Option A: Stop MySQL on external server
   - Option B: Change password to invalid in `wp_options`
     ```sql
     UPDATE wp_options 
     SET option_value = 'invalid_encrypted_password'
     WHERE option_name = 'eipsi_external_db_password';
     ```
3. Submit form
4. Verify fallback warning in response

**Expected AJAX Response:**
```json
{
  "success": true,
  "data": {
    "message": "Form submitted successfully!",
    "external_db": false,
    "fallback_used": true,
    "warning": "Form was saved to local database (external database temporarily unavailable).",
    "insert_id": 2,
    "error_code": "CONNECTION_FAILED"
  }
}
```

**Validation (WordPress DB):**
```sql
-- Should now have record in WordPress DB
SELECT id, form_name, created_at
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 1;
```

**Debug Log Check:**
```bash
tail -f /wp-content/debug.log | grep "EIPSI Forms"
# Expected:
# EIPSI Forms: External DB insert failed, falling back to WordPress DB - Failed to connect to external database
```

**✅ Pass Criteria:** User submission saved, warning shown, no data loss  
**❌ Fail Action:** Data lost = CRITICAL BUG

---

### Test 4.2: Error Recording

**Objective:** Error details recorded for admin review

**Steps:**
1. After fallback (Test 4.1)
2. Check error records in `wp_options`

**Validation Query:**
```sql
SELECT 
    option_name,
    option_value,
    CASE 
        WHEN option_name = 'eipsi_external_db_last_error_time' 
        THEN FROM_UNIXTIME(UNIX_TIMESTAMP(option_value))
        ELSE option_value
    END as display_value
FROM wp_options
WHERE option_name IN (
    'eipsi_external_db_last_error',
    'eipsi_external_db_last_error_code',
    'eipsi_external_db_last_error_time'
)
ORDER BY option_name;
```

**Expected Output:**
```
last_error: Failed to connect to external database
last_error_code: CONNECTION_FAILED
last_error_time: 2025-01-16 14:35:00
```

**✅ Pass Criteria:** All 3 error fields populated  
**❌ Fail Action:** Check `record_error` function call

---

### Test 4.3: Admin Status Banner

**Objective:** Admin sees fallback warning

**Steps:**
1. After error recorded (Test 4.2)
2. Navigate to **EIPSI Forms → Configuration**
3. Look for orange warning banner

**Expected UI:**
```
⚠️ Fallback Mode Active
Recent submissions were saved to the WordPress database because the 
external database was unavailable.

Last Error: Failed to connect to external database
Error Code: CONNECTION_FAILED
Occurred: January 16, 2025 2:35 PM
```

**✅ Pass Criteria:** Warning banner visible with error details  
**❌ Fail Action:** Check `configuration.php` status display

---

### Test 4.4: Recovery After Fix

**Objective:** Error clears when connection restored

**Steps:**
1. Fix external DB (restart MySQL, restore correct password)
2. Navigate to **EIPSI Forms → Configuration**
3. Verify warning banner disappears

**Validation Query:**
```sql
-- After page load, error should be cleared
SELECT option_value
FROM wp_options
WHERE option_name = 'eipsi_external_db_last_error';

-- Expected: empty or NULL
```

**✅ Pass Criteria:** Error auto-cleared, banner gone  
**❌ Fail Action:** Check `get_status` and `clear_errors` functions

---

## 5. Session Persistence Testing

### Test 5.1: sessionStorage Persistence

**Objective:** Sessions survive page refresh

**Steps:**
1. Open Browser DevTools → Console
2. Load test form
3. Check sessionStorage:
   ```javascript
   const sessions = JSON.parse(sessionStorage.getItem('eipsiAnalyticsSessions'));
   console.log('Sessions:', sessions);
   const sessionId = sessions['test-form'].sessionId;
   console.log('Session ID:', sessionId);
   ```
4. **Refresh page** (F5)
5. Check sessionStorage again
6. Verify session_id is IDENTICAL

**Expected Console Output:**
```javascript
// Before refresh:
Sessions: { "test-form": { sessionId: "3f7a9b2c...", viewTracked: true, ... } }
Session ID: 3f7a9b2c1d8e5f4a6b3c7d2e9f1a5b8c

// After refresh:
Sessions: { "test-form": { sessionId: "3f7a9b2c...", viewTracked: true, ... } }
Session ID: 3f7a9b2c1d8e5f4a6b3c7d2e9f1a5b8c  // SAME ID
```

**✅ Pass Criteria:** session_id persists across refresh  
**❌ Fail Action:** Check `persistSessions` and `restoreSessions`

---

### Test 5.2: Crypto-Secure Session IDs

**Objective:** Session IDs are cryptographically random

**Steps:**
1. Open Console
2. Generate 10 session IDs:
   ```javascript
   const ids = [];
   for (let i = 0; i < 10; i++) {
       sessionStorage.removeItem('eipsiAnalyticsSessions');
       location.reload();
       // After each reload, copy session ID
   }
   ```
3. Verify all IDs unique

**Expected Format:**
- Length: 32 hex characters
- Pattern: `[0-9a-f]{32}`
- No duplicates in 10 iterations

**✅ Pass Criteria:** All IDs unique, crypto API used  
**❌ Fail Action:** Check `generateSessionId` function

---

### Test 5.3: Abandon Event Tracking

**Objective:** Form abandonment tracked reliably

**Steps:**
1. Clear sessionStorage
2. Load form
3. Click into Name field (trigger `start` event)
4. **Close tab** (do NOT submit)
5. Check database

**Validation Query:**
```sql
-- Get latest session
SELECT 
    session_id,
    GROUP_CONCAT(event_type ORDER BY created_at SEPARATOR ' → ') as event_sequence
FROM wp_vas_form_events
WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
GROUP BY session_id
ORDER BY MAX(created_at) DESC
LIMIT 1;
```

**Expected Sequence:**
```
session_id: abc123...
event_sequence: view → start → abandon
```

**Alternative Test (if Beacon API not working):**
1. Navigate to different page instead of closing tab
2. Check for `abandon` event

**✅ Pass Criteria:** Abandon event recorded  
**❌ Fail Action:** Check `flushAbandonEvents` and `beforeunload` listener

---

## 6. Database Switching Testing

### Test 6.1: WordPress → External Switch

**Objective:** Clean switch with no duplicates

**Steps:**
1. Start with external DB **disabled**
2. Submit 3 forms → Records in WordPress DB
3. Enable external DB (Test 3.2)
4. Submit 3 more forms → Records in external DB
5. Verify no duplicates in either database

**Validation (WordPress DB):**
```sql
SELECT COUNT(*) as wp_count
FROM wp_vas_form_results;
-- Expected: 3
```

**Validation (External DB):**
```sql
SELECT COUNT(*) as external_count
FROM vas_form_results;
-- Expected: 3
```

**✅ Pass Criteria:** 3 in WordPress, 3 in external, no overlap  
**❌ Fail Action:** Check database selection logic

---

### Test 6.2: External → WordPress Switch

**Objective:** Disable external DB cleanly

**Steps:**
1. Start with external DB **enabled**
2. Submit 2 forms → Records in external DB
3. Click **Disable External Database**
4. Submit 2 more forms → Records in WordPress DB

**Validation (External DB):**
```sql
SELECT COUNT(*) as external_count
FROM vas_form_results;
-- Expected: 2 (unchanged)
```

**Validation (WordPress DB):**
```sql
SELECT COUNT(*) as wp_count
FROM wp_vas_form_results;
-- Expected: 2 (new records)
```

**✅ Pass Criteria:** Clean switch, no errors  
**❌ Fail Action:** Check `disable()` function

---

### Test 6.3: Record Count Accuracy

**Objective:** Status panel shows correct counts

**Steps:**
1. Enable external DB
2. Note record count in status panel: e.g., "Records: 5"
3. Submit 3 forms
4. Refresh Configuration page
5. Verify count increased by 3: "Records: 8"

**Manual SQL Verification:**
```sql
-- Direct count
SELECT COUNT(*) FROM vas_form_results;
-- Should match admin panel
```

**✅ Pass Criteria:** Counts match between UI and SQL  
**❌ Fail Action:** Check `get_record_count` function

---

## 7. Data Integrity Verification

### Test 7.1: PII Protection in Admin

**Objective:** Response data hidden in admin modal

**Steps:**
1. Submit form with sensitive data:
   - Name: "John Doe"
   - Email: "john.doe@example.com"
   - Message: "Sensitive information here"
2. Navigate to **EIPSI Forms → Results**
3. Click "View Details" on submission

**Expected UI:**
- ✅ Shows: form_id, participant_id, timestamps, device info
- ❌ Hidden: Name, Email, Message fields
- Notice: "For privacy and data protection, questionnaire responses are not displayed in the dashboard."
- Export buttons available: CSV, Excel

**✅ Pass Criteria:** No PII visible in modal  
**❌ Fail Action:** CRITICAL - PII leak, check `eipsi_ajax_get_response_details`

---

### Test 7.2: SQL Injection Prevention

**Objective:** Prepared statements block injection

**Steps:**
1. Open Browser DevTools → Network tab
2. Submit form with malicious input:
   - Name: `test'; DROP TABLE wp_vas_form_results; --`
3. Check database table still exists

**Validation:**
```sql
-- Table should still exist
SHOW TABLES LIKE 'wp_vas_form_results';

-- Data stored as literal string
SELECT form_responses
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 1;
```

**Expected:**
- Table NOT dropped
- Input stored as harmless string
- No SQL executed from user input

**✅ Pass Criteria:** SQL injection blocked  
**❌ Fail Action:** CRITICAL SECURITY BUG

---

### Test 7.3: XSS Prevention

**Objective:** Script tags escaped in admin

**Steps:**
1. Submit form with XSS attempt:
   - Message: `<script>alert('XSS')</script>`
2. View in admin Results page
3. Verify no alert popup

**Expected:**
- Script tags escaped: `&lt;script&gt;`
- No JavaScript execution
- Data stored safely

**✅ Pass Criteria:** No XSS execution  
**❌ Fail Action:** CRITICAL SECURITY BUG

---

### Test 7.4: Timezone Consistency

**Objective:** Timestamps use WordPress timezone

**Steps:**
1. Check WordPress timezone: **Settings → General → Timezone**
2. Note timezone (e.g., "America/New_York")
3. Submit form at known time: e.g., 3:00 PM local
4. Check database

**Validation Query:**
```sql
SELECT 
    id,
    created_at,
    NOW() as server_time,
    UTC_TIMESTAMP() as utc_time
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 1;
```

**Expected:**
- `created_at` matches WordPress timezone (3:00 PM)
- NOT UTC time

**Admin Modal Check:**
- Should show: "2025-01-16 15:00:00 (America/New_York)"

**✅ Pass Criteria:** Timezone consistent with WordPress settings  
**❌ Fail Action:** Check `current_time('mysql')` usage

---

## 8. SQL Validation Queries

### Quick Reference

```sql
-- 1. Check active database mode
SELECT option_value FROM wp_options 
WHERE option_name = 'eipsi_external_db_enabled';

-- 2. Get submission count by database
SELECT 'WordPress' as db, COUNT(*) as count FROM wp_vas_form_results
UNION ALL
SELECT 'External' as db, COUNT(*) as count FROM research_db_external.vas_form_results;

-- 3. Recent submissions (last hour)
SELECT id, form_id, form_name, created_at, duration_seconds
FROM wp_vas_form_results
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;

-- 4. Event sequences for recent sessions
SELECT 
    session_id,
    GROUP_CONCAT(event_type ORDER BY created_at SEPARATOR ' → ') as sequence
FROM wp_vas_form_events
WHERE DATE(created_at) = CURDATE()
GROUP BY session_id
ORDER BY MAX(created_at) DESC
LIMIT 20;

-- 5. Check for duplicate session IDs (should be none)
SELECT session_id, COUNT(*) as count
FROM wp_vas_form_events
GROUP BY session_id
HAVING count > 10
ORDER BY count DESC;

-- 6. Verify timestamp precision
SELECT 
    id,
    start_timestamp_ms,
    end_timestamp_ms,
    duration_seconds,
    ROUND((end_timestamp_ms - start_timestamp_ms) / 1000, 3) as calculated
FROM wp_vas_form_results
WHERE start_timestamp_ms IS NOT NULL
ORDER BY id DESC
LIMIT 5;

-- 7. Check fallback errors
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE 'eipsi_external_db_last_%'
ORDER BY option_name;

-- 8. Validate JSON integrity
SELECT 
    id,
    JSON_VALID(form_responses) as is_valid,
    JSON_LENGTH(form_responses) as field_count
FROM wp_vas_form_results
WHERE JSON_VALID(form_responses) = 0;
-- Should return 0 rows (all valid)
```

---

## 9. Troubleshooting

### Common Issues

#### Issue: Table Not Created

**Symptoms:**
- Form submission fails
- SQL error: "Table doesn't exist"

**Solutions:**
1. Manually trigger activation:
   ```php
   // In WordPress admin: Tools → Site Health → Info → Copy
   // Or deactivate/reactivate plugin
   ```
2. Check database user privileges:
   ```sql
   SHOW GRANTS FOR CURRENT_USER();
   -- Should have CREATE privilege
   ```

#### Issue: External DB Connection Fails

**Symptoms:**
- "Connection failed" error in admin
- Fallback mode always active

**Solutions:**
1. Check MySQL is running:
   ```bash
   sudo systemctl status mysql
   ```
2. Verify firewall allows port 3306
3. Test connection manually:
   ```bash
   mysql -h <host> -u <user> -p<password> <database>
   ```
4. Check bind-address in MySQL config:
   ```bash
   # /etc/mysql/mysql.conf.d/mysqld.cnf
   bind-address = 0.0.0.0  # Allow remote connections
   ```

#### Issue: Sessions Not Persisting

**Symptoms:**
- New session_id after each page refresh
- Duplicate 'view' events

**Solutions:**
1. Check browser supports sessionStorage:
   ```javascript
   console.log(typeof sessionStorage); // Should be 'object'
   ```
2. Check browser privacy mode (Incognito blocks storage)
3. Verify no JavaScript errors:
   ```javascript
   // Open Console, look for errors
   ```

#### Issue: Abandon Events Not Tracked

**Symptoms:**
- No 'abandon' events in database
- Event sequence ends at 'start'

**Solutions:**
1. Test with different browser (Beacon API support)
2. Check Network tab for blocked requests
3. Try navigation instead of closing tab:
   ```javascript
   // Navigate to another page (triggers visibilitychange)
   ```

#### Issue: Timestamps Incorrect

**Symptoms:**
- Negative duration
- Future timestamps

**Solutions:**
1. Check server time:
   ```bash
   date
   timedatectl
   ```
2. Verify timezone in WordPress:
   ```sql
   SELECT option_value FROM wp_options WHERE option_name = 'timezone_string';
   ```
3. Check browser clock sync

#### Issue: Fallback Not Working

**Symptoms:**
- Form submission fails when external DB down
- Error shown to user

**Solutions:**
1. Check `$used_fallback` logic in `vas_dinamico_submit_form_handler`
2. Verify WordPress DB is accessible
3. Check debug.log for wpdb errors

---

## Test Report Template

```markdown
# Data Persistence Test Report

**Tester:** [Your Name]
**Date:** [YYYY-MM-DD]
**Environment:** [Local/Staging/Production]
**WordPress Version:** [e.g., 6.7.1]
**Plugin Version:** [e.g., 1.2.0]

## Test Results Summary

| Test Category | Tests | Passed | Failed | Pass Rate |
|---------------|-------|--------|--------|-----------|
| Default Storage | 7 | 7 | 0 | 100% |
| External DB | 4 | 4 | 0 | 100% |
| Fallback | 4 | 4 | 0 | 100% |
| Session Persistence | 3 | 3 | 0 | 100% |
| Database Switching | 3 | 3 | 0 | 100% |
| Data Integrity | 4 | 4 | 0 | 100% |
| **TOTAL** | **25** | **25** | **0** | **100%** |

## Detailed Test Results

### Test 2.1: Clean Installation
- **Status:** ✅ PASS
- **Notes:** Both tables created correctly on activation

### Test 2.2: Form Submission
- **Status:** ✅ PASS
- **Insert ID:** 1
- **Duration:** 12.456 seconds

[Continue for all tests...]

## Issues Found

[None | List any bugs or concerns]

## Recommendations

[Any suggestions for improvements]

## Sign-off

- [ ] All tests passed
- [ ] No critical issues found
- [ ] Ready for production deployment

**Signature:** ___________________  
**Date:** ___________________
```

---

## Completion Checklist

Before closing this testing phase:

- [ ] All 25 manual tests completed
- [ ] Automated validation script passed (88/88 tests)
- [ ] Documentation reviewed (QA_PHASE3_RESULTS.md)
- [ ] No critical bugs found
- [ ] Test report filled out
- [ ] Evidence collected (SQL snapshots, screenshots)
- [ ] Debug log reviewed (no errors)

**Testing Status:** ✅ COMPLETE / ⚠️ ISSUES FOUND / ❌ BLOCKED

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-16  
**Next Review:** After any database-related code changes
