# Testing Guide: Database Schema Fix

This document provides quick testing procedures for the database schema fix implemented in branch `fix-wp-vas-form-results-add-missing-columns`.

---

## Quick Test Checklist

### 1. Pre-Deployment Verification

```bash
# Check that changes are present
cd /home/engine/project
grep -n "required_db_version = '1.4'" vas-dinamico-forms.php
# Expected: Line 106 with version 1.4

grep -n "'form_id'" vas-dinamico-forms.php | head -5
# Expected: Lines showing form_id in CREATE TABLE and ALTER TABLE
```

### 2. Fresh Installation Test

**Steps:**
1. Install plugin on clean WordPress site
2. Activate plugin
3. Check database schema

**Verification SQL:**
```sql
-- Connect to WordPress database
USE your_wordpress_database;

-- Check table exists with all columns
DESCRIBE wp_vas_form_results;

-- Expected output should include:
-- form_id              | varchar(20)
-- duration_seconds     | decimal(8,3)
-- start_timestamp_ms   | bigint(20)
-- end_timestamp_ms     | bigint(20)

-- Check database version
SELECT option_value FROM wp_options WHERE option_name = 'vas_dinamico_db_version';
-- Expected: 1.4
```

### 3. Upgrade Test (Most Important)

This simulates an existing installation with old schema.

**Setup old schema:**
```sql
-- Backup existing table
RENAME TABLE wp_vas_form_results TO wp_vas_form_results_backup_test;

-- Create old schema (missing the 4 columns)
CREATE TABLE wp_vas_form_results (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    participant varchar(255) DEFAULT NULL,
    form_name varchar(255) NOT NULL,
    created_at datetime NOT NULL,
    device varchar(100) DEFAULT NULL,
    browser varchar(100) DEFAULT NULL,
    os varchar(100) DEFAULT NULL,
    screen_width int(11) DEFAULT NULL,
    duration int(11) DEFAULT NULL,
    ip_address varchar(45) DEFAULT NULL,
    form_responses longtext DEFAULT NULL,
    PRIMARY KEY (id),
    KEY form_name (form_name),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reset database version to simulate old installation
UPDATE wp_options SET option_value = '1.0' WHERE option_name = 'vas_dinamico_db_version';
-- Or delete it
DELETE FROM wp_options WHERE option_name = 'vas_dinamico_db_version';
```

**Trigger migration:**
```php
// Visit any WordPress admin page (e.g., /wp-admin/)
// The plugins_loaded hook will trigger the migration automatically
```

**Verify migration:**
```sql
-- Check all columns were added
DESCRIBE wp_vas_form_results;

-- Expected: Should now have all 18 columns including:
-- form_id
-- duration_seconds
-- start_timestamp_ms
-- end_timestamp_ms

-- Check indexes were added
SHOW INDEX FROM wp_vas_form_results;

-- Expected indexes:
-- PRIMARY (id)
-- form_name (form_name)
-- created_at (created_at)
-- form_id (form_id)                    ← NEW
-- participant_id (participant_id)
-- submitted_at (submitted_at)
-- form_participant (form_id, participant_id)  ← NEW

-- Check version updated
SELECT option_value FROM wp_options WHERE option_name = 'vas_dinamico_db_version';
-- Expected: 1.4
```

**Check logs:**
```bash
# View WordPress debug log
tail -n 50 /path/to/wp-content/debug.log | grep "EIPSI Forms"

# Expected entries:
# EIPSI Forms: Successfully added column form_id
# EIPSI Forms: Successfully added column duration_seconds
# EIPSI Forms: Successfully added column start_timestamp_ms
# EIPSI Forms: Successfully added column end_timestamp_ms
```

**Restore if needed:**
```sql
DROP TABLE wp_vas_form_results;
RENAME TABLE wp_vas_form_results_backup_test TO wp_vas_form_results;
```

### 4. Form Submission Test

**Steps:**
1. Create a simple form in WordPress admin
2. Add a few fields (text, radio, etc.)
3. Visit form on frontend
4. Fill out and submit

**Verify submission:**
```sql
SELECT 
    id,
    form_id,
    form_name,
    duration,
    duration_seconds,
    start_timestamp_ms,
    end_timestamp_ms,
    created_at,
    submitted_at
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 1;
```

**Expected results:**
- ✅ `form_id` is NOT NULL (e.g., "PSY-a3f8c2")
- ✅ `duration_seconds` > 0 (e.g., 12.456)
- ✅ `start_timestamp_ms` is a 13-digit number (e.g., 1704067200000)
- ✅ `end_timestamp_ms` > `start_timestamp_ms`
- ✅ No errors in debug.log
- ✅ No "Unknown column" errors

### 5. Using Verification Script

```bash
# Run the schema check script
cd /path/to/wordpress
wp eval-file check-database-schema.php

# Or access via browser (requires admin login)
# https://yoursite.com/check-database-schema.php
```

**Expected output:**
```
===========================================
EIPSI Forms - Database Schema Verification
===========================================

✓ Table 'wp_vas_form_results' exists

Column Verification:
--------------------
  ✓ id (bigint(20) unsigned)
⭐ ✓ form_id (varchar(20))
  ✓ participant_id (varchar(20))
  ✓ form_name (varchar(255))
  ✓ created_at (datetime)
  ✓ submitted_at (datetime)
  ✓ duration (int(11))
⭐ ✓ duration_seconds (decimal(8,3))
⭐ ✓ start_timestamp_ms (bigint(20))
⭐ ✓ end_timestamp_ms (bigint(20))
  ✓ form_responses (longtext)

Index Verification:
-------------------
  ✓ PRIMARY (id)
  ✓ form_name (form_name)
  ✓ form_id (form_id)
  ✓ form_participant (form_id, participant_id)

Database Version:
-----------------
  Current: 1.4
  Required: 1.4
  ✓ Database version is current

===========================================
Summary:
===========================================
✅ ALL CHECKS PASSED
   Database schema is complete and up to date.
   Form submissions should work correctly.
```

---

## Common Issues & Solutions

### Issue 1: Migration not running

**Symptoms:**
- Database version still shows 1.0, 1.2, or 1.3
- Columns still missing after visiting admin page

**Solutions:**
1. **Clear WordPress object cache:**
   ```bash
   wp cache flush
   ```

2. **Manually trigger migration:**
   ```bash
   wp eval "do_action('plugins_loaded');"
   ```

3. **Check for errors:**
   ```bash
   tail -f wp-content/debug.log | grep "EIPSI Forms"
   ```

4. **Verify table exists:**
   ```sql
   SHOW TABLES LIKE 'wp_vas_form_results';
   ```

### Issue 2: Permission errors in logs

**Error:**
```
EIPSI Forms: Failed to add column form_id - ALTER command denied...
```

**Solution:**
Grant ALTER privileges to database user:
```sql
GRANT ALTER ON your_wordpress_db.* TO 'wp_user'@'localhost';
FLUSH PRIVILEGES;
```

### Issue 3: Column exists but different type

**Error:**
```
Duplicate column name 'form_id'
```

**Solution:**
Column already exists but migration thinks it doesn't. Check actual type:
```sql
SHOW COLUMNS FROM wp_vas_form_results LIKE 'form_id';
```

If type is wrong, manually fix:
```sql
ALTER TABLE wp_vas_form_results MODIFY COLUMN form_id varchar(20) DEFAULT NULL;
```

### Issue 4: Form submissions still fail

**Error:**
```
Unknown column 'form_id' in 'field list'
```

**Verification:**
1. Check table has column:
   ```sql
   DESCRIBE wp_vas_form_results;
   ```

2. Check database version:
   ```sql
   SELECT option_value FROM wp_options WHERE option_name = 'vas_dinamico_db_version';
   ```

3. Check for typos in table name (prefix):
   ```sql
   SHOW TABLES LIKE '%vas_form_results';
   ```

**Solution:**
Run verification script to identify exact issue:
```bash
wp eval-file check-database-schema.php
```

---

## Performance Testing

### Large Table Migration

If table has >10,000 records, test migration performance:

```sql
-- Check record count
SELECT COUNT(*) FROM wp_vas_form_results;

-- Time the migration
SET @start = NOW();
ALTER TABLE wp_vas_form_results ADD COLUMN test_col varchar(20) DEFAULT NULL;
SET @end = NOW();
SELECT TIMEDIFF(@end, @start) as migration_time;

-- Clean up test
ALTER TABLE wp_vas_form_results DROP COLUMN test_col;
```

**Expected times:**
- 1,000 rows: <1 second
- 10,000 rows: 1-5 seconds
- 100,000 rows: 5-30 seconds

If migration takes >30 seconds, consider running during maintenance window.

---

## Rollback Procedure

If issues occur after deployment:

### Option 1: Restore from backup

```sql
-- If you created backup before testing
DROP TABLE wp_vas_form_results;
RENAME TABLE wp_vas_form_results_backup TO wp_vas_form_results;

-- Reset database version
UPDATE wp_options SET option_value = '1.3' WHERE option_name = 'vas_dinamico_db_version';
```

### Option 2: Remove new columns

```sql
-- Remove columns added by v1.4 migration
ALTER TABLE wp_vas_form_results DROP COLUMN form_id;
ALTER TABLE wp_vas_form_results DROP COLUMN duration_seconds;
ALTER TABLE wp_vas_form_results DROP COLUMN start_timestamp_ms;
ALTER TABLE wp_vas_form_results DROP COLUMN end_timestamp_ms;

-- Remove indexes
ALTER TABLE wp_vas_form_results DROP INDEX form_id;
ALTER TABLE wp_vas_form_results DROP INDEX form_participant;

-- Reset database version
UPDATE wp_options SET option_value = '1.0' WHERE option_name = 'vas_dinamico_db_version';
```

### Option 3: Deactivate plugin

```bash
# Via WP-CLI
wp plugin deactivate vas-dinamico-forms

# Or via WordPress admin
# Plugins → EIPSI Forms → Deactivate
```

---

## Production Deployment Checklist

- [ ] **Backup database** before deployment
- [ ] **Enable WP_DEBUG** temporarily for migration logging
- [ ] **Deploy during low-traffic period** (if table is large)
- [ ] **Monitor error logs** during first hour after deployment
- [ ] **Test form submission** immediately after deployment
- [ ] **Run verification script** to confirm all columns present
- [ ] **Check 3-5 recent submissions** in database
- [ ] **Monitor for "Unknown column" errors** in logs
- [ ] **Disable WP_DEBUG** after confirming success
- [ ] **Document any issues** encountered

---

## Success Indicators

After deployment, you should see:

1. ✅ No "Unknown column" errors in debug.log
2. ✅ Database version = 1.4 in wp_options
3. ✅ All 4 critical columns present in table
4. ✅ Form submissions complete successfully
5. ✅ Duration tracking works (duration_seconds > 0)
6. ✅ Timing metadata populated (start/end timestamps)
7. ✅ form_id values populated (e.g., "PSY-a3f8c2")
8. ✅ Admin dashboard shows submissions correctly
9. ✅ CSV/Excel exports include new columns
10. ✅ No user complaints about form failures

---

## Support Resources

- **Implementation Details:** `DATABASE_SCHEMA_FIX_SUMMARY.md`
- **Verification Script:** `check-database-schema.php`
- **Related Docs:**
  - `DURATION_TRACKING_REPAIR_SUMMARY.md`
  - `EXTERNAL_DB_STABILIZATION_SUMMARY.md`
  - `TIMESTAMP_METADATA_IMPLEMENTATION.md`

---

**Last Updated:** January 2025  
**Branch:** `fix-wp-vas-form-results-add-missing-columns`
