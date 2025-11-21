# Installation Guide - EIPSI Forms v1.2.2

## Prerequisites

Before installing EIPSI Forms, ensure your environment meets these requirements:

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.3+)
- **User Role:** Administrator access to WordPress dashboard

---

## Installation Methods

### Method 1: Upload via WordPress Admin (Recommended)

#### Step 1: Download Plugin

Download the latest release:
- `eipsi-forms-v1.2.2.zip`

#### Step 2: Upload to WordPress

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Click the **"Upload Plugin"** button at the top
4. Click **"Choose File"** and select `eipsi-forms-v1.2.2.zip`
5. Click **"Install Now"**

Wait for the upload and installation to complete (~30 seconds).

#### Step 3: Activate Plugin

1. After installation, click **"Activate Plugin"**
2. You should see a success message: *"Plugin activated successfully"*
3. The **"EIPSI Forms"** menu should now appear in your WordPress admin sidebar

#### Step 4: Verify Installation

Navigate to **Settings → EIPSI Forms** to confirm:
- ✅ Database Configuration page loads
- ✅ Privacy Settings page exists
- ✅ Results & Experience page accessible

---

### Method 2: Manual Installation via FTP

If you prefer manual installation or have FTP access:

#### Step 1: Extract Plugin

1. Download `eipsi-forms-v1.2.2.zip`
2. Extract the ZIP file to a folder on your computer
3. You should see a folder named `eipsi-forms-plugin/`

#### Step 2: Upload via FTP

1. Connect to your server via FTP (FileZilla, Cyberduck, etc.)
2. Navigate to `/wp-content/plugins/`
3. Upload the entire `eipsi-forms-plugin/` folder
4. Ensure all files and folders are uploaded completely

#### Step 3: Activate via WordPress Admin

1. Log in to WordPress admin
2. Navigate to **Plugins → Installed Plugins**
3. Find **"EIPSI Forms"** in the list
4. Click **"Activate"**

---

### Method 3: WP-CLI Installation (Advanced)

For developers using WP-CLI:

```bash
# Navigate to WordPress root
cd /path/to/wordpress/

# Install plugin from ZIP
wp plugin install /path/to/eipsi-forms-v1.2.2.zip --activate

# Verify installation
wp plugin list | grep eipsi
```

Expected output:
```
eipsi-forms-plugin  active  1.2.2
```

---

## Post-Installation Setup

### 1. Database Configuration

After activation, configure your database connection:

#### Option A: Use WordPress Database (Quick Setup)

For small studies or testing:

1. Navigate to **EIPSI Forms → Database Configuration**
2. Leave all fields blank
3. Click **"Save Configuration"**

Data will be stored in your WordPress database in table `wp_vas_form_results`.

✅ **Pros:** No additional setup required  
⚠️ **Cons:** Shares WordPress database (not recommended for large studies)

#### Option B: Use External Database (Recommended for Production)

For clinical studies or large deployments:

1. **Create a MySQL database:**
   ```sql
   CREATE DATABASE eipsi_forms_data CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Create a MySQL user:**
   ```sql
   CREATE USER 'eipsi_user'@'localhost' IDENTIFIED BY 'secure_password_here';
   GRANT ALL PRIVILEGES ON eipsi_forms_data.* TO 'eipsi_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Configure in WordPress admin:**
   - Navigate to **EIPSI Forms → Database Configuration**
   - Enter database credentials:
     - **Host:** `localhost` (or your database server IP)
     - **Username:** `eipsi_user`
     - **Password:** `secure_password_here`
     - **Database Name:** `eipsi_forms_data`
   - Click **"Test Connection"** (should show ✅ Success)
   - Click **"Save Configuration"**

4. **Verify schema creation:**
   - Plugin automatically creates required tables
   - Tables: `vas_form_results`, `vas_form_events`
   - Check admin notification for confirmation

✅ **Pros:** Isolated data storage, better performance, easier backups  
✅ **Recommended for:** Clinical research, HIPAA/GDPR compliance

---

### 2. Privacy Configuration

Configure privacy settings according to your study protocol:

1. Navigate to **EIPSI Forms → Privacy Settings**
2. Configure metadata capture toggles:

**Recommended Settings for Clinical Research:**
- ✅ **IP Address:** ON (for audit trail and fraud detection)
- ✅ **Device Type:** ON (mobile/desktop/tablet - useful for analysis)
- ⚙️ **Browser:** OFF by default (enable only if needed)
- ⚙️ **OS:** OFF by default (enable only if needed)
- ⚙️ **Screen Width:** OFF by default (enable only if needed)

**GDPR Compliance Recommendation:**
- Only enable metadata fields required for your research
- Document in your privacy policy what you collect
- Implement data retention policies (default: 90 days for IP)

3. Click **"Save Settings"**

---

### 3. Test Form Creation

Verify installation by creating a test form:

1. Create a new **Post** or **Page**
2. Add the **"EIPSI Form Container"** block
3. Inside the container, add an **"EIPSI Page"** block
4. Add a simple field (e.g., **"EIPSI Campo Texto"**)
5. Configure the field:
   - Label: "What is your name?"
   - Field Name: `test_name`
   - Required: Yes
6. Publish the page
7. Open in an incognito window
8. Fill out and submit the form
9. Verify submission in **EIPSI Forms → Results & Experience**

---

## Verification Checklist

After installation, verify these items:

- [ ] Plugin appears in **Plugins → Installed Plugins** as active
- [ ] **EIPSI Forms** menu visible in admin sidebar
- [ ] **Settings → EIPSI Forms** accessible
- [ ] Database connection test successful
- [ ] Privacy settings page loads
- [ ] **Results & Experience** page displays
- [ ] Gutenberg blocks available in editor:
  - [ ] EIPSI Form Container
  - [ ] EIPSI Página
  - [ ] EIPSI VAS Slider
  - [ ] EIPSI Campo Likert
  - [ ] EIPSI Campo Radio
  - [ ] EIPSI Campo Multiple
  - [ ] EIPSI Campo Select
  - [ ] EIPSI Campo Texto
  - [ ] EIPSI Campo Textarea
  - [ ] EIPSI Campo Descripción
- [ ] Test form submits successfully
- [ ] Submission appears in admin Results page

---

## Common Installation Issues

### Issue: "Missing required PHP extension"

**Error:** *"PHP extension mysqli is required"*

**Solution:**
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli
sudo service apache2 restart

# CentOS/RHEL
sudo yum install php-mysqlnd
sudo service httpd restart
```

### Issue: "The plugin does not have a valid header"

**Cause:** Corrupted ZIP file or incomplete upload

**Solution:**
1. Re-download the plugin ZIP
2. Verify file integrity (check file size)
3. Try manual FTP upload instead

### Issue: Blocks Not Appearing in Gutenberg

**Cause:** WordPress cache or build files not loaded

**Solution:**
1. Clear WordPress cache (if using caching plugin)
2. Hard refresh browser: `Ctrl+F5` (Windows) or `Cmd+Shift+R` (Mac)
3. Deactivate and reactivate plugin
4. Check browser console for JavaScript errors (F12)

### Issue: "Unable to create directory" during upload

**Cause:** Insufficient file permissions

**Solution:**
```bash
# Set correct permissions for plugins directory
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/
sudo chmod -R 755 /var/www/html/wp-content/plugins/
```

---

## Upgrading from Previous Versions

### From v1.2.1 to v1.2.2

**Automatic Schema Repair:** v1.2.2 includes automatic database schema synchronization. No manual migration required.

**Upgrade Steps:**
1. **Backup database** (critical for production)
   ```bash
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
   ```
2. Deactivate EIPSI Forms plugin
3. Delete old plugin folder via FTP (or Plugins → Delete)
4. Install v1.2.2 using Method 1 or 2 above
5. Activate plugin
6. Navigate to **EIPSI Forms → Database Configuration**
7. Click **"Test Connection"** to trigger schema repair
8. Verify existing data intact in **Results & Experience**

**What's New in v1.2.2:**
- ✅ Automatic database schema repair (zero data loss)
- ✅ 4-layer redundant protection for schema integrity
- ✅ Improved error handling and recovery
- ✅ Performance optimizations

### From v1.0/v1.1 to v1.2.2

**Critical:** v1.2.2 includes schema changes. Follow upgrade steps above carefully.

**Schema Changes:**
- Added columns: `participant_id`, `session_id`, `device`, `browser`, `os`, `screen_width`, `duration_seconds`
- Auto-repair automatically adds missing columns
- No data loss

**Backup Recommendation:** Always backup before upgrading across major versions.

---

## Uninstalling EIPSI Forms

To completely remove the plugin:

### Option 1: Keep Data (Deactivate Only)

1. Navigate to **Plugins → Installed Plugins**
2. Find **EIPSI Forms**
3. Click **"Deactivate"**

This keeps all data in the database for future reactivation.

### Option 2: Remove Plugin (Keep Database Tables)

1. Deactivate plugin (see above)
2. Click **"Delete"** on the EIPSI Forms plugin
3. Confirm deletion

Database tables remain intact for data preservation.

### Option 3: Complete Removal (Data + Plugin)

⚠️ **Warning:** This permanently deletes all form submissions and settings.

1. **Backup data first:**
   - Navigate to **EIPSI Forms → Results & Experience**
   - Click **"Export to Excel"** for each form
   - Save exports securely

2. **Delete database tables:**
   ```sql
   DROP TABLE IF EXISTS wp_vas_form_results;
   DROP TABLE IF EXISTS wp_vas_form_events;
   ```

3. **Delete plugin:**
   - Navigate to **Plugins → Installed Plugins**
   - Deactivate EIPSI Forms
   - Click **"Delete"**
   - Confirm deletion

4. **Clean up options** (optional):
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE '%eipsi%';
   DELETE FROM wp_options WHERE option_name LIKE '%vas_dinamico%';
   ```

---

## Next Steps

After successful installation:

1. **Read Configuration Guide:** [CONFIGURATION.md](CONFIGURATION.md)
2. **Create Your First Form:** Follow Quick Start in README.md
3. **Configure Privacy Settings:** Review GDPR compliance needs
4. **Test End-to-End:** Submit test forms and verify data capture
5. **Review Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

## Support

If you encounter issues during installation:

1. Check **WordPress debug log:** `wp-content/debug.log`
2. Review **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** for common solutions
3. Verify **server requirements** (PHP 7.4+, MySQL 5.7+)
4. Check **file permissions** (755 for directories, 644 for files)
5. Contact support with:
   - WordPress version
   - PHP version
   - MySQL version
   - Error messages from debug log
   - Steps to reproduce issue

---

**Installation Guide Version:** 1.2.2  
**Last Updated:** January 2025  
**Plugin Version:** 1.2.2
