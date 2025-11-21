# Performance Stress Test Guide v1.2.2

## Overview

This guide provides instructions for running comprehensive performance stress tests on the EIPSI Forms plugin v1.2.2.

## Prerequisites

### 1. Environment Requirements
- **Live WordPress Installation** (local or staging server)
- **EIPSI Forms v1.2.2** installed and activated
- **Node.js 14+** installed
- **axios package**: `npm install axios`
- **Database access** for verification (optional but recommended)

### 2. Server Requirements
- **PHP 7.4+**
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Memory:** At least 256MB PHP memory limit
- **Execution Time:** At least 300 seconds (5 minutes) max execution time

## Test Suite Structure

The stress test suite covers 5 major areas:

1. **Multiple Simultaneous Submissions** (10 min)
   - Sequential submissions (10 forms)
   - Near-simultaneous submissions (5 forms < 100ms apart)
   - Sustained load (20 forms over 5 minutes)

2. **Complex Forms** (5 min)
   - Large forms (8 pages, 50+ fields)
   - Complex multiple choice (20+ options)
   - Large text data (5000+ characters)

3. **Metadata Under Stress** (5 min)
   - Metadata capture verification (20 submissions)
   - Duration calculations
   - Timestamp coherence

4. **Database Under Stress** (5 min)
   - Connection stability (20 queries in 1 minute)
   - Query performance (< 100ms target)
   - Transaction integrity

5. **Memory & CPU Monitoring** (5 min)
   - Memory usage tracking
   - Memory leak detection
   - System responsiveness

## Running the Tests

### Automated Testing (Recommended)

#### Step 1: Install Dependencies

```bash
cd /path/to/eipsi-forms/
npm install axios
```

#### Step 2: Configure Test Parameters

Edit the test script configuration or use command-line arguments:

```bash
# Basic usage
node stress-test-v1.2.2.js --url=https://your-site.com

# With specific form
node stress-test-v1.2.2.js --url=https://your-site.com --form-id=TEST-FORM
```

#### Step 3: Run Tests

```bash
node stress-test-v1.2.2.js --url=https://your-wordpress-site.com
```

The test will take approximately **30 minutes** to complete all tests.

#### Step 4: Review Results

The test generates two output files:

1. **JSON Results:** `STRESS_TEST_RESULTS_v1.2.2_[timestamp].json`
   - Machine-readable detailed results
   - Can be imported into monitoring tools
   - Includes all metrics and timings

2. **Markdown Report:** `STRESS_TEST_REPORT_v1.2.2_[timestamp].md`
   - Human-readable summary
   - Performance metrics
   - Pass/fail status for each test
   - Recommendations

### Manual Testing

If you cannot run the automated tests, follow these manual procedures:

#### Test 1: Sequential Submissions

1. Create a form with 4 pages in WordPress
2. Open the form in a browser
3. Fill out and submit 10 forms sequentially
4. Verify:
   - ✅ All 10 submissions appear in admin panel
   - ✅ Average submit time < 2 seconds
   - ✅ No errors in browser console or WordPress debug log

#### Test 2: Rapid Submissions

1. Open the same form in 5 different browser tabs
2. Fill out each form
3. Submit all 5 within 10 seconds
4. Verify:
   - ✅ All 5 submissions saved
   - ✅ No duplicates
   - ✅ No data loss

#### Test 3: Large Form

1. Create a form with:
   - 8 pages
   - 7 fields per page (56 total fields)
   - Mix of field types
2. Fill out completely and submit
3. Verify:
   - ✅ Submission completes in < 60 seconds
   - ✅ All data saved correctly
   - ✅ Navigation remains smooth

#### Test 4: Complex Multiple Choice

1. Create a form with:
   - 1 Multiple Choice field
   - 25 options
2. Select 10 options
3. Submit form
4. Verify:
   - ✅ All selections saved
   - ✅ JSON structure valid in database

#### Test 5: Large Text

1. Create a form with a Text Area field
2. Paste 5000+ characters
3. Submit form
4. Verify:
   - ✅ All text saved (no truncation)
   - ✅ Can view complete text in admin

#### Test 6: Metadata Capture

1. Submit 20 forms from different devices if possible
2. In admin panel, verify each submission has:
   - ✅ IP address (if privacy config allows)
   - ✅ Device type
   - ✅ Browser info (if privacy config allows)
   - ✅ OS info (if privacy config allows)
   - ✅ Screen width (if privacy config allows)
   - ✅ Duration (seconds)
   - ✅ Start/end timestamps

#### Test 7: Duration Accuracy

1. Open a form
2. Wait exactly 30 seconds
3. Submit form
4. In database, verify:
   - ✅ `duration_seconds` is approximately 30 (±2 seconds acceptable)
   - ✅ `start_timestamp_ms` and `end_timestamp_ms` are present
   - ✅ Calculated duration matches: `(end - start) / 1000 ≈ 30`

#### Test 8: Database Performance

1. Submit 20 forms in 1 minute (approx 1 every 3 seconds)
2. Monitor server:
   - ✅ No timeout errors
   - ✅ Response times remain consistent
   - ✅ Database connections stable

#### Test 9: Memory Monitoring

**Via WordPress:**
1. Install Query Monitor plugin
2. Submit 20 forms
3. Check Query Monitor for:
   - ✅ Memory usage not increasing excessively
   - ✅ Query times < 100ms
   - ✅ No slow queries

**Via Server:**
```bash
# Monitor memory while testing
watch -n 1 'free -m'

# Monitor MySQL
mysqladmin -u root -p processlist status
```

## Performance Thresholds

### ✅ PASS Criteria

| Metric | Threshold | Description |
|--------|-----------|-------------|
| **Form Submissions** | 20+ | At least 20 forms submitted successfully |
| **Average Response Time** | < 2 seconds | Mean time from submit to confirmation |
| **Max Response Time** | < 5 seconds | No single submission over 5 seconds |
| **Query Performance** | < 100ms | Database queries complete quickly |
| **Memory Growth** | < 10MB | Memory doesn't grow excessively |
| **Timeouts** | 0 | Zero timeout errors |
| **Data Loss** | 0 | All submissions saved correctly |
| **Duplicates** | 0 | No duplicate entries |

### ⚠️ WARNING Criteria

| Metric | Threshold | Action Required |
|--------|-----------|-----------------|
| **Average Response Time** | 2-3 seconds | Investigate performance |
| **Max Response Time** | 5-10 seconds | Check server resources |
| **Memory Growth** | 10-20MB | Monitor for leaks |
| **Query Performance** | 100-500ms | Optimize queries/indexes |

### ❌ FAIL Criteria

| Metric | Threshold | Action Required |
|--------|-----------|-----------------|
| **Success Rate** | < 95% | Critical fixes needed |
| **Average Response Time** | > 3 seconds | Major performance issues |
| **Data Loss** | > 0 | Critical data integrity issue |
| **Timeouts** | > 0 | Fix connection/timeout issues |

## Database Verification

### Check Record Count

```sql
-- Count total submissions
SELECT COUNT(*) FROM wp_vas_form_results;

-- Count submissions by form
SELECT form_id, form_name, COUNT(*) as submissions
FROM wp_vas_form_results
GROUP BY form_id, form_name;

-- Check for duplicates
SELECT participant_id, session_id, COUNT(*) as count
FROM wp_vas_form_results
GROUP BY participant_id, session_id
HAVING count > 1;
```

### Verify Metadata

```sql
-- Check metadata presence
SELECT 
    id,
    form_id,
    participant_id,
    device,
    browser,
    os,
    screen_width,
    ip_address,
    duration_seconds,
    JSON_VALID(metadata) as metadata_valid
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 10;

-- Check duration calculations
SELECT 
    id,
    duration_seconds,
    (end_timestamp_ms - start_timestamp_ms) / 1000 as calculated_duration,
    ABS(duration_seconds - ((end_timestamp_ms - start_timestamp_ms) / 1000)) as difference
FROM wp_vas_form_results
WHERE start_timestamp_ms IS NOT NULL 
AND end_timestamp_ms IS NOT NULL
ORDER BY id DESC
LIMIT 20;
```

### Check Query Performance

```sql
-- Enable slow query log (MySQL)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1; -- 100ms

-- Check indexes
SHOW INDEX FROM wp_vas_form_results;

-- Analyze table
ANALYZE TABLE wp_vas_form_results;

-- Check table status
SHOW TABLE STATUS LIKE 'wp_vas_form_results';
```

## Troubleshooting

### Issue: Connection Timeouts

**Symptoms:**
- Submissions fail with timeout errors
- Response times > 10 seconds

**Solutions:**
1. Increase PHP max_execution_time:
   ```php
   // In wp-config.php
   set_time_limit(300); // 5 minutes
   ```

2. Increase MySQL timeout:
   ```sql
   SET GLOBAL wait_timeout = 300;
   SET GLOBAL interactive_timeout = 300;
   ```

3. Check server resources (CPU, memory)

### Issue: Memory Errors

**Symptoms:**
- "Allowed memory size exhausted" errors
- Server crashes during testing

**Solutions:**
1. Increase PHP memory limit:
   ```php
   // In wp-config.php
   define('WP_MEMORY_LIMIT', '256M');
   ```

2. Check for memory leaks in custom code

3. Optimize large queries

### Issue: Slow Queries

**Symptoms:**
- Database queries > 500ms
- Response times degrading over time

**Solutions:**
1. Add missing indexes:
   ```sql
   ALTER TABLE wp_vas_form_results 
   ADD INDEX idx_form_participant (form_id, participant_id);
   
   ALTER TABLE wp_vas_form_results 
   ADD INDEX idx_submitted_at (submitted_at);
   ```

2. Optimize table:
   ```sql
   OPTIMIZE TABLE wp_vas_form_results;
   ```

3. Check MySQL configuration (query cache, buffer pool)

### Issue: Data Loss

**Symptoms:**
- Submissions don't appear in database
- Inconsistent record counts

**Solutions:**
1. Check WordPress debug log:
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Verify database schema:
   ```sql
   DESCRIBE wp_vas_form_results;
   ```

3. Check external database configuration (if using)

4. Verify auto-repair is working:
   - Check debug.log for "Auto-repaired schema" messages
   - Manually trigger repair: `EIPSI_Database_Schema_Manager::repair_local_schema();`

### Issue: Duplicate Submissions

**Symptoms:**
- Same submission appears multiple times
- Duplicate insert IDs

**Solutions:**
1. Check for race conditions in JavaScript

2. Add unique constraints:
   ```sql
   ALTER TABLE wp_vas_form_results 
   ADD UNIQUE KEY unique_session_submission (session_id, form_id, submitted_at);
   ```

3. Verify nonce validation is working

## Performance Optimization Tips

### 1. Database Optimization

```sql
-- Add composite indexes for common queries
ALTER TABLE wp_vas_form_results 
ADD INDEX idx_form_date (form_id, submitted_at);

ALTER TABLE wp_vas_form_results 
ADD INDEX idx_participant_date (participant_id, submitted_at);

-- Use InnoDB for better concurrent inserts
ALTER TABLE wp_vas_form_results ENGINE=InnoDB;

-- Optimize table regularly
OPTIMIZE TABLE wp_vas_form_results;
```

### 2. PHP Optimization

```php
// In wp-config.php

// Increase memory for large forms
define('WP_MEMORY_LIMIT', '256M');

// Increase max execution time
set_time_limit(300);

// Enable object caching
define('WP_CACHE', true);
```

### 3. Server Optimization

**Apache (.htaccess):**
```apache
# Increase PHP limits
php_value max_execution_time 300
php_value memory_limit 256M
php_value post_max_size 50M
php_value upload_max_filesize 50M
```

**Nginx (nginx.conf):**
```nginx
# Increase timeouts
fastcgi_read_timeout 300;
fastcgi_send_timeout 300;

# Increase buffer sizes
fastcgi_buffer_size 128k;
fastcgi_buffers 256 16k;
```

### 4. WordPress Optimization

1. **Disable unnecessary plugins** during testing
2. **Use object caching** (Redis, Memcached)
3. **Enable opcache** for PHP
4. **Optimize database** (wp-optimize plugin)

## Expected Results

### Baseline Performance (Shared Hosting)

- **Response Time:** 500-1500ms
- **Forms/minute:** 20-40
- **Memory Growth:** 5-10MB
- **Query Time:** 50-200ms

### Good Performance (VPS/Dedicated)

- **Response Time:** 200-800ms
- **Forms/minute:** 40-100
- **Memory Growth:** 2-5MB
- **Query Time:** 10-50ms

### Excellent Performance (Optimized Server)

- **Response Time:** < 200ms
- **Forms/minute:** 100+
- **Memory Growth:** < 2MB
- **Query Time:** < 10ms

## Reporting Issues

If tests fail, please report with:

1. **Test Results:**
   - Attach JSON and Markdown reports
   - Include specific failing tests

2. **Environment Details:**
   - WordPress version
   - PHP version
   - MySQL/MariaDB version
   - Server type (shared/VPS/dedicated)
   - Available memory/CPU

3. **Error Logs:**
   - WordPress debug.log
   - PHP error log
   - MySQL slow query log

4. **Database Schema:**
   ```sql
   SHOW CREATE TABLE wp_vas_form_results;
   ```

## Next Steps After Testing

### ✅ All Tests Pass

1. **Document results** for compliance
2. **Set up monitoring** for production
3. **Schedule regular testing** (monthly recommended)
4. **Establish performance baselines**

### ⚠️ Tests Pass with Warnings

1. **Review warnings** and assess impact
2. **Implement optimizations** if needed
3. **Re-test** after changes
4. **Monitor closely** in production

### ❌ Tests Fail

1. **Review error logs** for root causes
2. **Apply troubleshooting steps**
3. **Re-test** after fixes
4. **Consider professional support** if issues persist

## Additional Resources

- **WordPress Performance:** https://developer.wordpress.org/advanced-administration/performance/optimization/
- **MySQL Optimization:** https://dev.mysql.com/doc/refman/8.0/en/optimization.html
- **PHP Performance:** https://www.php.net/manual/en/security.performance.php

---

**Version:** 1.2.2  
**Last Updated:** January 2025  
**Author:** EIPSI Forms Team  
**Support:** https://github.com/roofkat/VAS-dinamico-mvp/issues
