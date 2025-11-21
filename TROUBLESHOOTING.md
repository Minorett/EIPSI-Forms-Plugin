# Troubleshooting Guide - EIPSI Forms v1.2.2

## Overview

This guide provides solutions to common issues encountered when using EIPSI Forms plugin for WordPress.

---

## Table of Contents

1. [Database Issues](#database-issues)
2. [Form Display Issues](#form-display-issues)
3. [Submission Issues](#submission-issues)
4. [Performance Issues](#performance-issues)
5. [Privacy & Security Issues](#privacy--security-issues)
6. [Installation & Update Issues](#installation--update-issues)
7. [Compatibility Issues](#compatibility-issues)
8. [Diagnostic Tools](#diagnostic-tools)

---

## Database Issues

### Issue: "Unknown column 'participant_id'" Error

**Symptoms:**
- Form submissions fail with database error
- Error message mentions missing columns
- Admin notifications show schema errors

**Cause:**
Database schema incomplete or outdated after plugin update.

**Solution (Automatic):**

1. Navigate to **EIPSI Forms → Database Configuration**
2. Click **"Test Connection"** button
3. Plugin automatically detects and repairs missing columns
4. Success message: "Schema verified and repaired"
5. Try submitting form again

**Solution (Manual - Advanced):**

If automatic repair fails, manually add missing columns:

```sql
-- Check current schema
DESCRIBE wp_vas_form_results;

-- Add missing columns (if not present)
ALTER TABLE wp_vas_form_results 
ADD COLUMN participant_id VARCHAR(50) NULL AFTER id;

ALTER TABLE wp_vas_form_results 
ADD COLUMN session_id VARCHAR(100) NULL AFTER participant_id;

ALTER TABLE wp_vas_form_results 
ADD COLUMN device VARCHAR(50) NULL;

ALTER TABLE wp_vas_form_results 
ADD COLUMN browser VARCHAR(100) NULL;

ALTER TABLE wp_vas_form_results 
ADD COLUMN os VARCHAR(100) NULL;

ALTER TABLE wp_vas_form_results 
ADD COLUMN screen_width INT NULL;

ALTER TABLE wp_vas_form_results 
ADD COLUMN duration_seconds INT NULL;

-- Verify columns added
DESCRIBE wp_vas_form_results;
```

**Prevention:**

Plugin v1.2.2 includes **4-layer automatic schema repair**:
- Layer 1: Complete schema on installation
- Layer 2: Auto-repair every 24 hours
- Layer 3: Manual trigger via "Test Connection"
- Layer 4: Emergency repair on INSERT failure

---

### Issue: "Access denied for user" Error

**Symptoms:**
- Cannot connect to external database
- "Test Connection" fails with access denied message
- Submissions not saving to external database

**Cause:**
Incorrect MySQL credentials or insufficient privileges.

**Solution:**

1. **Verify Credentials:**
   ```bash
   # Test connection from command line
   mysql -h localhost -u eipsi_user -p
   # Enter password when prompted
   ```

2. **Check User Privileges:**
   ```sql
   -- Login as MySQL root
   mysql -u root -p
   
   -- Check privileges
   SHOW GRANTS FOR 'eipsi_user'@'localhost';
   
   -- Should include:
   -- GRANT ALL PRIVILEGES ON eipsi_forms_data.* TO 'eipsi_user'@'localhost'
   ```

3. **Grant Missing Privileges:**
   ```sql
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER 
   ON eipsi_forms_data.* 
   TO 'eipsi_user'@'localhost';
   
   FLUSH PRIVILEGES;
   ```

4. **Reset Password (if forgotten):**
   ```sql
   ALTER USER 'eipsi_user'@'localhost' IDENTIFIED BY 'NewSecurePassword123!';
   FLUSH PRIVILEGES;
   ```

5. **Update WordPress Configuration:**
   - Navigate to **EIPSI Forms → Database Configuration**
   - Enter correct username and password
   - Click "Test Connection"
   - Click "Save Configuration"

**Common Mistakes:**
- ❌ Wrong host: `127.0.0.1` vs `localhost` (use `localhost`)
- ❌ Wrong user format: `eipsi_user` vs `'eipsi_user'@'localhost'`
- ❌ Password with special characters not escaped
- ❌ User created for wrong host (`%` vs `localhost`)

---

### Issue: Connection Timeout

**Symptoms:**
- "Test Connection" hangs or times out
- Forms take very long to submit
- "Maximum execution time exceeded" errors

**Cause:**
Database server unreachable, slow network, or firewall blocking connection.

**Solution:**

1. **Check Database Server Status:**
   ```bash
   # Check if MySQL is running
   sudo service mysql status
   
   # Or for MariaDB
   sudo service mariadb status
   ```

2. **Verify Network Connectivity:**
   ```bash
   # Ping database server
   ping localhost
   
   # Test MySQL port (3306)
   telnet localhost 3306
   # Should connect immediately
   ```

3. **Check Firewall Rules:**
   ```bash
   # Ubuntu/Debian
   sudo ufw status
   sudo ufw allow 3306/tcp
   
   # CentOS/RHEL
   sudo firewall-cmd --list-all
   sudo firewall-cmd --permanent --add-port=3306/tcp
   sudo firewall-cmd --reload
   ```

4. **Increase Timeout Values:**

   In `wp-config.php`:
   ```php
   define('DB_TIMEOUT', 10); // Increase from default 5 seconds
   set_time_limit(300); // Increase max execution time
   ```

   In MySQL config (`my.cnf` or `my.ini`):
   ```ini
   [mysqld]
   wait_timeout = 300
   connect_timeout = 10
   ```

5. **Enable Fallback to WordPress Database:**

   Plugin automatically falls back to WordPress database if external database times out. Check admin notifications for fallback status.

---

### Issue: Data Not Appearing in External Database

**Symptoms:**
- Submissions succeed (participant sees confirmation)
- Data appears in WordPress database
- Data missing from external database

**Cause:**
External database connection failed, plugin using fallback mode.

**Solution:**

1. **Check Connection Status:**
   - Navigate to **EIPSI Forms → Database Configuration**
   - Look for connection indicator:
     - 🟢 Connected: External DB operational
     - 🟡 Fallback: Using WordPress DB
     - 🔴 Error: Connection failed

2. **Verify External Database Configuration:**
   - Click "Test Connection"
   - Review error messages
   - Fix connection issues (see "Access denied" or "Connection timeout" above)

3. **Manually Migrate Data (if needed):**
   ```sql
   -- Copy data from WordPress DB to external DB
   INSERT INTO eipsi_forms_data.vas_form_results
   SELECT * FROM wordpress_db.wp_vas_form_results
   WHERE created_at > '2025-01-20 00:00:00';
   ```

4. **Re-Save Configuration:**
   - Navigate to **EIPSI Forms → Database Configuration**
   - Re-enter credentials (even if unchanged)
   - Click "Test Connection" → Should show ✅ Success
   - Click "Save Configuration"
   - Submit test form
   - Verify appears in external database

---

### Issue: Duplicate Submissions

**Symptoms:**
- Same submission appears multiple times in database
- Participant ID and Session ID identical
- Timestamps very close together (< 1 second)

**Cause:**
Participant clicked submit button multiple times (double-submit).

**Solution:**

1. **Identify Duplicates:**
   ```sql
   -- Find duplicate submissions
   SELECT participant_id, session_id, COUNT(*) as count
   FROM wp_vas_form_results
   GROUP BY participant_id, session_id
   HAVING count > 1;
   ```

2. **Remove Duplicates (keep earliest):**
   ```sql
   -- Delete duplicate submissions, keeping the first one
   DELETE t1 FROM wp_vas_form_results t1
   INNER JOIN wp_vas_form_results t2
   WHERE t1.id > t2.id
   AND t1.participant_id = t2.participant_id
   AND t1.session_id = t2.session_id;
   ```

3. **Prevent Future Duplicates:**

   Plugin includes built-in duplicate prevention:
   - Submit button disabled after first click
   - Nonce verification prevents replay attacks
   - Session tracking prevents duplicate submissions

   If duplicates persist, check JavaScript console for errors.

4. **Add Unique Constraint (Advanced):**
   ```sql
   -- Prevent database-level duplicates
   ALTER TABLE wp_vas_form_results
   ADD UNIQUE INDEX idx_unique_submission (participant_id, session_id);
   ```

---

## Form Display Issues

### Issue: Dark Preset Text Not Visible

**Symptoms:**
- Input fields appear completely white with white text
- Cannot read text in form fields
- Only affects Dark preset

**Cause:**
CSS not loaded properly or browser cache issue.

**Solution:**

1. **Clear Browser Cache:**
   - Windows: `Ctrl + F5` or `Ctrl + Shift + R`
   - Mac: `Cmd + Shift + R`
   - Mobile: Settings → Clear browsing data

2. **Verify CSS File Loaded:**
   - Open browser DevTools (F12)
   - Go to Network tab
   - Reload page
   - Look for `eipsi-forms-*.css` files
   - Should see status 200 (not 404)

3. **Check File Permissions:**
   ```bash
   # Ensure CSS files are readable
   chmod 644 /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin/build/*.css
   ```

4. **Rebuild Assets:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin/
   npm install
   npm run build
   ```

5. **Test with Different Preset:**
   - Edit form
   - Change preset to "Clinical Blue"
   - Publish and test
   - If works, issue is specific to Dark preset
   - Report to support with browser details

**Expected Dark Preset Behavior:**
- Input fields: White background (#ffffff)
- Input text: Dark gray (#333333)
- Contrast ratio: 14.68:1 (WCAG AAA)

---

### Issue: Likert/Multiple Choice Not Clickable

**Symptoms:**
- Cannot click on Likert scale options
- Multiple choice options don't respond to clicks
- Only clicking directly on radio button works

**Cause:**
JavaScript not loaded or CSS clickable area styling missing.

**Solution:**

1. **Check JavaScript Console:**
   - Open browser DevTools (F12)
   - Go to Console tab
   - Look for JavaScript errors (red text)
   - Common errors:
     - "Uncaught ReferenceError: eipsi is not defined"
     - "Failed to load resource: 404" (missing JS file)

2. **Verify JavaScript Bundle Loaded:**
   - DevTools → Network tab → Filter by JS
   - Look for `eipsi-forms-*.js` files
   - Should see status 200

3. **Clear WordPress Cache:**
   - If using caching plugin (WP Super Cache, W3 Total Cache):
     - Go to plugin settings
     - Click "Purge All Caches"
     - Or disable cache temporarily for testing

4. **Test Clickable Area:**
   - v1.2.2 includes expanded clickable areas (44x44px)
   - Entire option label should be clickable
   - Test on mobile device (touch targets meet WCAG AA)

5. **Verify Block Saved Correctly:**
   - Edit form in WordPress editor
   - Select Likert or Multiple Choice block
   - Re-save block settings (no changes needed)
   - Update page
   - Test again

**Expected Behavior:**
- Clicking anywhere on option label selects that option
- Hover effect shows on entire label area
- Mobile touch targets minimum 44x44px

---

### Issue: Multi-Page Navigation Broken

**Symptoms:**
- "Siguiente" (Next) button doesn't advance page
- "Anterior" (Previous) button doesn't go back
- Form stuck on first page

**Cause:**
Data not persisting in localStorage or JavaScript error.

**Solution:**

1. **Check localStorage Enabled:**
   - Open browser DevTools (F12)
   - Go to Console tab
   - Type: `localStorage.setItem('test', '1')`
   - Type: `localStorage.getItem('test')`
   - Should return `"1"`
   - If error, localStorage disabled (private browsing mode?)

2. **Check JavaScript Errors:**
   - DevTools → Console tab
   - Look for errors when clicking "Siguiente"
   - Common issues:
     - "localStorage is not defined"
     - "Uncaught TypeError: Cannot read property..."

3. **Verify Page Structure:**
   - Edit form in WordPress editor
   - Ensure each page is an **EIPSI Página** block
   - Pages must be direct children of **EIPSI Form Container**
   - No nested containers

4. **Test with Simple Form:**
   - Create minimal test form:
     - Page 1: 1 text field
     - Page 2: 1 text field
   - If works, issue is with complex form structure
   - Gradually add fields to identify problem

5. **Check Required Fields:**
   - Ensure all required fields on current page filled
   - Plugin prevents navigation if required fields empty
   - Look for red error messages

**Expected Behavior:**
- "Siguiente" visible on all pages except last
- "Anterior" visible on pages 2+ (if navigation toggle ON)
- "Enviar" (Submit) visible only on last page
- Data persists when navigating between pages

---

### Issue: Form Blocks Not Appearing in Gutenberg

**Symptoms:**
- EIPSI blocks missing from block inserter
- Search for "EIPSI" returns no results
- Cannot add form blocks to page

**Cause:**
Plugin not activated, build files missing, or JavaScript error.

**Solution:**

1. **Verify Plugin Activated:**
   - Navigate to **Plugins → Installed Plugins**
   - Find "EIPSI Forms"
   - Should show "Deactivate" link (if active)
   - If inactive, click "Activate"

2. **Clear Block Editor Cache:**
   - Windows: `Ctrl + Shift + R`
   - Mac: `Cmd + Shift + R`
   - Or open page in incognito/private window

3. **Check Build Files:**
   ```bash
   ls -la /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin/build/
   # Should see:
   # - index.js
   # - index.asset.php
   # - *.css files
   ```

4. **Rebuild Plugin Assets:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin/
   npm install
   npm run build
   # Should complete without errors in ~4 seconds
   ```

5. **Check JavaScript Console:**
   - Open Gutenberg editor
   - Open DevTools (F12) → Console
   - Look for block registration errors
   - Should see: "EIPSI Forms blocks registered successfully"

6. **Verify WordPress Version:**
   - Minimum: WordPress 5.8
   - Check: **Dashboard → Updates**
   - Update WordPress if outdated

---

## Submission Issues

### Issue: Form Not Submitting

**Symptoms:**
- Click "Enviar" (Submit) but nothing happens
- No success message appears
- No error message displayed
- Page does not reload

**Cause:**
JavaScript error, AJAX failure, or validation error.

**Solution:**

1. **Check JavaScript Console:**
   - Open DevTools (F12) → Console tab
   - Click "Enviar" and watch for errors
   - Common errors:
     - "Failed to fetch" (network error)
     - "Nonce verification failed" (security error)
     - "Required field missing" (validation error)

2. **Verify All Required Fields Filled:**
   - Look for red error messages
   - Scroll through all pages
   - Ensure no empty required fields
   - Try submitting with minimal valid data

3. **Check Network Tab:**
   - DevTools → Network tab
   - Click "Enviar"
   - Look for AJAX request to `admin-ajax.php`
   - Check response:
     - Status 200: Success (check response body)
     - Status 400: Validation error (check error message)
     - Status 500: Server error (check debug log)

4. **Check AJAX Handler:**
   ```bash
   # Enable WordPress debug logging
   # In wp-config.php:
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   
   # Check debug log
   tail -f /path/to/wordpress/wp-content/debug.log
   ```

5. **Test with Simple Form:**
   - Create minimal form (1 required text field)
   - Submit
   - If works, issue is with complex form
   - Add fields incrementally to identify problem

6. **Verify Nonce:**
   - Plugin uses nonce verification for security
   - If "Nonce verification failed", reload page
   - Nonce expires after 12-24 hours

**Expected Behavior:**
- Submit button shows loading spinner
- Success message appears: "¡Gracias por tu participación!"
- Form data cleared or redirect occurs

---

### Issue: Data Not Appearing in Admin

**Symptoms:**
- Form submits successfully (participant sees confirmation)
- Submission does not appear in **Results & Experience**
- Database table empty or missing rows

**Cause:**
Wrong database configured, data in different database, or database connection failed.

**Solution:**

1. **Check Both Databases:**
   
   If using external database, check both WordPress and external:
   
   ```sql
   -- WordPress database
   SELECT COUNT(*) FROM wp_vas_form_results;
   
   -- External database
   SELECT COUNT(*) FROM eipsi_forms_data.vas_form_results;
   ```

2. **Verify Database Configuration:**
   - Navigate to **EIPSI Forms → Database Configuration**
   - Check connection status (🟢 / 🟡 / 🔴)
   - If fallback mode (🟡), data in WordPress database

3. **Check Form ID Filter:**
   - In **Results & Experience** → Submissions tab
   - Look for "Filter by Form ID" dropdown
   - Select correct form or "All Forms"
   - Some forms may not show if filtered

4. **Refresh Admin Page:**
   - Submissions may be cached
   - Hard refresh: `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac)
   - Or disable admin caching temporarily

5. **Check Timezone Settings:**
   - Submissions may be filtered by date
   - WordPress timezone: **Settings → General → Timezone**
   - Ensure timezone correct for your location

6. **Verify Table Exists:**
   ```sql
   SHOW TABLES LIKE 'wp_vas_form_results';
   # Should return: wp_vas_form_results
   ```

7. **Check Table Permissions:**
   ```sql
   SHOW GRANTS FOR CURRENT_USER();
   # Should include SELECT privilege on table
   ```

---

### Issue: Participants Seeing Error Messages

**Symptoms:**
- Participants report errors during submission
- Generic "An error occurred" message
- Submission fails to complete

**Cause:**
Server error, database issue, or validation failure.

**Solution:**

1. **Enable Debug Mode:**
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false); // Hide errors from participants
   ```

2. **Reproduce Error:**
   - Submit form as participant
   - Note exact error message
   - Check timestamp when error occurred

3. **Check Debug Log:**
   ```bash
   tail -50 /path/to/wordpress/wp-content/debug.log
   # Look for timestamp matching error
   ```

4. **Common Errors and Solutions:**

   **"Database error: Table doesn't exist"**
   - Run schema repair: **Database Configuration → Test Connection**

   **"Nonce verification failed"**
   - Participant's session expired (> 24 hours)
   - Instruct to refresh page and resubmit

   **"Required field missing"**
   - Validation error (participant missed required field)
   - Check form for proper required field indicators

   **"Maximum execution time exceeded"**
   - Form too large or server too slow
   - Increase PHP `max_execution_time` (see Performance section)

5. **Contact Participants:**
   - Apologize for inconvenience
   - Provide alternative submission method (if critical)
   - Document error details for support

6. **Test Fix:**
   - After applying fix, test submission thoroughly
   - Use multiple devices and browsers
   - Test with realistic form data

---

## Performance Issues

### Issue: Forms Load Slowly

**Symptoms:**
- Page takes > 5 seconds to load
- Blocks appear gradually
- Participants report slow loading
- Mobile users especially affected

**Cause:**
Slow server, large assets, unoptimized images, or caching issues.

**Solution:**

1. **Check Asset Sizes:**
   ```bash
   # Check built JavaScript and CSS sizes
   ls -lh /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin/build/
   # Should be:
   # - index.js: ~220 KB (acceptable for clinical forms)
   # - *.css: < 100 KB total
   ```

2. **Enable Caching:**
   - Install caching plugin (WP Super Cache, W3 Total Cache, or similar)
   - Enable page caching
   - Enable browser caching
   - Enable Gzip compression

3. **Optimize Images (if using in forms):**
   - Compress images before upload
   - Use WebP format for modern browsers
   - Lazy load images

4. **Check Server Resources:**
   ```bash
   # Check CPU usage
   top
   # Look for high CPU processes
   
   # Check memory
   free -h
   # Ensure available memory > 256 MB
   ```

5. **Enable OpCache (PHP Bytecode Cache):**
   ```ini
   # In php.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

6. **Use CDN (if applicable):**
   - Cloudflare (free tier available)
   - StackPath, KeyCDN, BunnyCDN
   - Reduces load on origin server

7. **Optimize Database Queries:**
   ```sql
   -- Add missing indexes
   SHOW INDEX FROM wp_vas_form_results;
   
   -- Optimize table
   OPTIMIZE TABLE wp_vas_form_results;
   ```

**Performance Benchmarks:**
- **Good:** Page load < 2 seconds
- **Acceptable:** Page load 2-5 seconds
- **Poor:** Page load > 5 seconds (needs optimization)

---

### Issue: Submission Takes Too Long

**Symptoms:**
- Submit button shows loading spinner for > 10 seconds
- Participants report "stuck" submission
- Timeout errors in debug log

**Cause:**
Slow database queries, large form data, or server overload.

**Solution:**

1. **Check Database Performance:**
   ```sql
   -- Enable slow query log (MySQL)
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2; -- Log queries > 2 seconds
   
   -- Check slow query log
   SHOW VARIABLES LIKE 'slow_query_log_file';
   ```

2. **Monitor Query Times:**
   ```sql
   -- Check recent insertion times
   SELECT id, created_at, submitted_at, 
          TIMESTAMPDIFF(SECOND, created_at, submitted_at) as duration
   FROM wp_vas_form_results
   ORDER BY id DESC
   LIMIT 10;
   ```

3. **Optimize Database Configuration:**
   ```ini
   # In my.cnf or my.ini
   [mysqld]
   innodb_buffer_pool_size = 256M
   query_cache_size = 32M
   query_cache_limit = 2M
   ```

4. **Increase PHP Limits:**
   ```php
   // In wp-config.php
   define('WP_MEMORY_LIMIT', '256M');
   set_time_limit(300); // 5 minutes
   ```

5. **Check Server Load:**
   ```bash
   # Linux
   uptime
   # Load average should be < number of CPU cores
   
   # Check disk I/O
   iostat -x 1 10
   # %util should be < 80%
   ```

6. **Reduce Form Complexity:**
   - Split very large forms into multiple shorter forms
   - Avoid > 50 fields per form
   - Limit open-ended text field length

**Performance Targets:**
- **Good:** Submission < 2 seconds
- **Acceptable:** Submission 2-5 seconds
- **Poor:** Submission > 5 seconds (needs investigation)

---

### Issue: High Server Resource Usage

**Symptoms:**
- Server CPU consistently > 80%
- Memory usage near limit
- Site becomes unresponsive
- Other sites on shared hosting affected

**Cause:**
Too many simultaneous submissions, inefficient queries, or memory leaks.

**Solution:**

1. **Identify Resource Hog:**
   ```bash
   # Check CPU usage by process
   top -o %CPU
   
   # Check memory usage by process
   top -o %MEM
   
   # Check MySQL queries
   mysqladmin -u root -p processlist
   ```

2. **Optimize MySQL:**
   ```sql
   -- Find slow or stuck queries
   SHOW PROCESSLIST;
   
   -- Kill stuck query (if found)
   KILL QUERY [process_id];
   
   -- Optimize tables
   OPTIMIZE TABLE wp_vas_form_results, wp_vas_form_events;
   ```

3. **Rate Limit Submissions (if bot attack):**
   - Install security plugin (Wordfence, Sucuri)
   - Enable rate limiting
   - Block suspicious IPs

4. **Increase Server Resources:**
   - Upgrade hosting plan (more CPU/RAM)
   - Move to VPS/dedicated server
   - Use managed WordPress hosting

5. **Implement Caching:**
   - Object cache (Redis/Memcached)
   - Page cache
   - Database query cache

6. **Monitor with Tools:**
   - New Relic (performance monitoring)
   - Query Monitor (WordPress plugin)
   - Server monitoring (Nagios, Datadog)

---

## Privacy & Security Issues

### Issue: GDPR Compliance Concerns

**Symptoms:**
- Collecting more data than disclosed
- No mechanism for data deletion
- Unclear privacy policy
- Participants concerned about privacy

**Cause:**
Metadata capture not configured according to privacy requirements.

**Solution:**

1. **Audit Current Metadata Collection:**
   - Navigate to **EIPSI Forms → Privacy Settings**
   - Review all enabled toggles
   - Disable any unnecessary metadata

2. **Recommended GDPR-Compliant Settings:**
   ```
   ✅ Participant ID: ON (anonymous, required)
   ✅ Timestamps: ON (required for research)
   ✅ Quality Flags: ON (data integrity)
   ⚙️ IP Address: ON only if justified (audit trail)
   ⚙️ Device Type: ON only if needed (analysis)
   ❌ Browser: OFF (unnecessary for most studies)
   ❌ OS: OFF (unnecessary for most studies)
   ❌ Screen Width: OFF (unnecessary for most studies)
   ```

3. **Update Privacy Policy:**
   
   Include in participant-facing privacy notice:
   - What data is collected
   - Why each data point is collected
   - How long data is retained
   - Who has access to data
   - How participants can request deletion

4. **Implement Data Deletion Process:**
   
   Document procedure for handling data deletion requests:
   
   ```sql
   -- Delete all data for specific participant
   DELETE FROM wp_vas_form_results WHERE participant_id = 'p-abc123def456';
   DELETE FROM wp_vas_form_events WHERE participant_id = 'p-abc123def456';
   ```

5. **Set Data Retention Policy:**
   - Define retention period (e.g., 90 days post-study)
   - Document in study protocol
   - Implement automatic deletion (manual or scripted)

6. **Obtain Informed Consent:**
   - Add consent question at form start
   - Disclose all metadata collection
   - Provide option to opt-out (if applicable)

---

### Issue: IP Address Exposure Concerns

**Symptoms:**
- Participants concerned about IP address collection
- IP addresses visible in exports
- Privacy policy doesn't mention IP collection

**Cause:**
IP address capture enabled by default for audit trail purposes.

**Solution:**

1. **Disable IP Capture (if not needed):**
   - Navigate to **EIPSI Forms → Privacy Settings**
   - Toggle OFF: "Capture IP Address"
   - Save settings
   - New submissions will not capture IP

2. **Anonymize Existing IP Addresses:**
   ```sql
   -- Replace IP addresses with anonymized version
   UPDATE wp_vas_form_results 
   SET ip_address = CONCAT(
       SUBSTRING_INDEX(ip_address, '.', 3), 
       '.0'
   )
   WHERE ip_address IS NOT NULL;
   
   -- Example: 192.168.1.123 → 192.168.1.0
   ```

3. **Delete Existing IP Addresses:**
   ```sql
   -- Completely remove IP addresses
   UPDATE wp_vas_form_results 
   SET ip_address = NULL;
   ```

4. **Update Privacy Policy:**
   - If capturing IP: Justify purpose (fraud detection, audit trail)
   - If not capturing: State "IP addresses are not collected"
   - Specify retention period (e.g., 90 days)

5. **Configure Export Exclusion:**
   - When IP capture disabled, exports exclude IP column
   - Verify by exporting test submission

---

### Issue: Security Vulnerability Warnings

**Symptoms:**
- Security plugin warnings
- Failed security audit
- XSS or SQL injection concerns

**Cause:**
Potential security vulnerabilities in code or configuration.

**Solution:**

1. **Update to Latest Version:**
   - Current version: 1.2.2
   - Includes comprehensive security hardening
   - All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`
   - All input sanitized with `sanitize_text_field()`, etc.
   - SQL queries use prepared statements

2. **Run Security Scan:**
   - Install Wordfence or Sucuri security plugin
   - Run full security scan
   - Review findings and apply recommendations

3. **Verify Output Escaping:**
   ```bash
   # Check for unescaped output
   grep -rn "echo \$" admin/ --include="*.php"
   # Should return minimal results (all should be escaped)
   ```

4. **Verify Input Sanitization:**
   ```bash
   # Check for unsanitized $_POST
   grep -rn "\$_POST\[" includes/ --include="*.php"
   # All should use sanitize_* or wp_unslash functions
   ```

5. **Enable Security Headers:**
   ```apache
   # In .htaccess
   <IfModule mod_headers.c>
       Header set X-XSS-Protection "1; mode=block"
       Header set X-Content-Type-Options "nosniff"
       Header set X-Frame-Options "SAMEORIGIN"
   </IfModule>
   ```

6. **Regular Security Audits:**
   - Review code before each release
   - Use automated security scanners
   - Keep WordPress and PHP updated
   - Monitor security advisories

**Security Certifications:**
- ✅ XSS Prevention: All output escaped
- ✅ SQL Injection Prevention: Prepared statements
- ✅ CSRF Prevention: Nonce verification
- ✅ Input Validation: Comprehensive sanitization

---

## Installation & Update Issues

### Issue: Plugin Activation Fails

**Symptoms:**
- "Plugin activation failed" error
- White screen after activation
- WordPress admin inaccessible

**Cause:**
PHP error, missing dependencies, or incompatible WordPress version.

**Solution:**

1. **Check PHP Version:**
   ```bash
   php -v
   # Should be PHP 7.4 or higher
   ```

2. **Check WordPress Version:**
   - Minimum: WordPress 5.8
   - Update WordPress: **Dashboard → Updates**

3. **Enable Debug Mode:**
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', true);
   ```

4. **Check Debug Log:**
   ```bash
   tail -50 /path/to/wordpress/wp-content/debug.log
   # Look for fatal errors or warnings
   ```

5. **Common Errors:**

   **"Allowed memory size exhausted"**
   ```php
   // In wp-config.php
   define('WP_MEMORY_LIMIT', '256M');
   ```

   **"Maximum execution time exceeded"**
   ```php
   // In wp-config.php
   set_time_limit(300);
   ```

   **"Cannot redeclare function"**
   - Conflict with another plugin
   - Deactivate other plugins temporarily
   - Activate EIPSI Forms
   - Reactivate other plugins one by one

6. **Manual Deactivation (if admin inaccessible):**
   ```bash
   # Rename plugin folder to deactivate
   mv /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin.disabled
   
   # Access admin should work now
   # Fix issue, then rename back
   ```

---

### Issue: Update Breaks Existing Forms

**Symptoms:**
- Forms display incorrectly after update
- Submissions fail after update
- "Block validation error" in editor

**Cause:**
Block structure changed or backward compatibility issue.

**Solution:**

1. **Clear All Caches:**
   - Browser cache: `Ctrl + Shift + R`
   - WordPress cache: Purge all caches
   - Server cache: Restart PHP-FPM / Apache

2. **Rebuild Block Assets:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/eipsi-forms-plugin/
   npm install
   npm run build
   ```

3. **Fix Block Validation Errors:**
   - Open form in editor
   - Look for "This block contains unexpected or invalid content"
   - Click "Attempt Block Recovery"
   - Re-save form

4. **Check Database Schema:**
   - Navigate to **EIPSI Forms → Database Configuration**
   - Click "Test Connection" (triggers auto-repair)
   - Verify "Schema verified" message

5. **Rollback (if issues persist):**
   ```bash
   # Restore from backup
   # Or manually install previous version
   ```

6. **Report Issue:**
   - Document exact steps to reproduce
   - Include WordPress version, PHP version
   - Provide form structure (JSON from editor)
   - Include debug log excerpts

**v1.2.2 Backward Compatibility:**
- ✅ 100% compatible with v1.2.1 forms
- ✅ Auto-migration of database schema
- ✅ No manual intervention required
- ✅ All existing forms continue working

---

## Compatibility Issues

### Issue: Conflict with Page Builder (Elementor, Divi, etc.)

**Symptoms:**
- EIPSI blocks not working in page builder
- Forms render incorrectly
- Submission fails

**Cause:**
EIPSI Forms uses Gutenberg blocks, not compatible with all page builders.

**Solution:**

1. **Use Gutenberg for EIPSI Forms:**
   - Create form pages with Gutenberg (WordPress native editor)
   - Use page builder for other pages
   - EIPSI Forms designed for Gutenberg only

2. **Embed Form via Shortcode (if available):**
   - Currently not supported in v1.2.2
   - Roadmap for future version
   - Use Gutenberg pages for now

3. **Use iFrame Embed (workaround):**
   - Create form page with Gutenberg
   - Publish page
   - Embed page via iframe in page builder:
     ```html
     <iframe src="https://yoursite.com/form-page/" width="100%" height="800px"></iframe>
     ```

---

### Issue: Theme Styling Conflicts

**Symptoms:**
- Form buttons look different than expected
- Colors don't match preset
- Layout broken

**Cause:**
Theme CSS overriding plugin styles.

**Solution:**

1. **Increase CSS Specificity:**
   
   Add to theme's `style.css` or custom CSS:
   ```css
   /* Override theme styles for EIPSI forms */
   .eipsi-form-container .eipsi-button {
       /* Restore plugin styles */
       background-color: var(--eipsi-color-primary) !important;
       color: white !important;
   }
   ```

2. **Disable Theme Gutenberg Styles:**
   ```php
   // In theme's functions.php
   add_action('wp_enqueue_scripts', function() {
       if (has_block('eipsi/form-container')) {
           wp_dequeue_style('theme-gutenberg-style');
       }
   });
   ```

3. **Test with Default Theme:**
   - Temporarily switch to Twenty Twenty-Four theme
   - Test form display
   - If works, issue is theme-specific
   - Report to theme developer

---

## Diagnostic Tools

### Enable WordPress Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Hide from public
define('SCRIPT_DEBUG', true); // Use non-minified assets
```

### Check System Requirements

```bash
# PHP version
php -v

# MySQL version
mysql --version

# WordPress version
wp core version

# Check PHP extensions
php -m | grep -E 'mysqli|json|mbstring'
```

### Database Diagnostics

```sql
-- Check table structure
DESCRIBE wp_vas_form_results;

-- Check table size
SELECT 
    table_name,
    table_rows,
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
WHERE table_name LIKE 'wp_vas_form%';

-- Check recent submissions
SELECT id, form_id, participant_id, created_at, submitted_at
FROM wp_vas_form_results
ORDER BY id DESC
LIMIT 10;

-- Check for errors in data
SELECT id, form_id, participant_id
FROM wp_vas_form_results
WHERE participant_id IS NULL OR form_responses IS NULL;
```

### Browser Diagnostics

1. **Console Errors:** F12 → Console tab
2. **Network Requests:** F12 → Network tab
3. **Storage:** F12 → Application tab → Local Storage / Session Storage
4. **Performance:** F12 → Performance tab → Record session

### WordPress Health Check

1. Navigate to **Tools → Site Health**
2. Review "Status" and "Info" tabs
3. Address any warnings or errors

### Plugin Conflict Test

```bash
# Deactivate all plugins except EIPSI Forms
wp plugin deactivate --all --skip-plugins=eipsi-forms-plugin

# Test form submission

# Reactivate plugins one by one to identify conflict
wp plugin activate plugin-name
```

---

## Getting Support

If issues persist after troubleshooting:

### Before Contacting Support

Gather this information:

1. **System Information:**
   - WordPress version
   - PHP version
   - MySQL version
   - Plugin version (1.2.2)

2. **Error Details:**
   - Exact error message
   - Steps to reproduce
   - When error started occurring
   - Recent changes (updates, new plugins, etc.)

3. **Debug Information:**
   - WordPress debug log excerpts
   - Browser console errors
   - Network tab screenshots (if AJAX issue)

4. **Form Configuration:**
   - Number of pages
   - Number of fields
   - Field types used
   - Preset selected

### How to Export Debug Info

```bash
# System info
wp core version
php -v
mysql --version

# Plugin info
wp plugin list | grep eipsi

# Database info
wp db query "DESCRIBE wp_vas_form_results" --skip-column-names

# Recent errors
tail -100 wp-content/debug.log > debug-log-export.txt
```

### Contact Information

Include in support request:
- Ticket subject: [EIPSI Forms v1.2.2] Brief description
- Detailed description of issue
- Steps to reproduce
- System information (above)
- Debug log (if applicable)
- Screenshots or screen recordings

---

**Troubleshooting Guide Version:** 1.2.2  
**Last Updated:** January 2025  
**Plugin Version:** 1.2.2
