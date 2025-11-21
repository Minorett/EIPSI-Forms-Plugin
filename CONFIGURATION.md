# Configuration Guide - EIPSI Forms v1.2.2

## Overview

This guide covers all configuration options for EIPSI Forms plugin to help researchers set up optimal clinical research environments.

---

## Table of Contents

1. [Database Configuration](#database-configuration)
2. [Privacy & Metadata Settings](#privacy--metadata-settings)
3. [Navigation Settings](#navigation-settings)
4. [Form Creation & Design](#form-creation--design)
5. [Admin Panel Configuration](#admin-panel-configuration)
6. [Advanced Settings](#advanced-settings)

---

## Database Configuration

### Understanding Database Options

EIPSI Forms supports two database storage strategies:

| Option | Use Case | Pros | Cons |
|--------|----------|------|------|
| **WordPress Database** | Small studies, testing, demos | Easy setup, no configuration | Shares WP database, harder to isolate |
| **External Database** | Clinical research, production | Isolated storage, better performance, easier backups | Requires MySQL setup |

### Option 1: WordPress Database (Quick Setup)

**Best for:** Testing, small studies (< 100 participants)

**Steps:**
1. Navigate to **EIPSI Forms → Database Configuration**
2. Leave all fields blank
3. Click **"Save Configuration"**

**Storage:**
- Table: `wp_vas_form_results` (in WordPress database)
- Events: `wp_vas_form_events`

**Automatic Setup:**
- Tables created on plugin activation
- Schema auto-repairs if columns missing
- Zero configuration required

---

### Option 2: External Database (Recommended for Production)

**Best for:** Clinical research, large studies, HIPAA/GDPR compliance

#### Step 1: Create MySQL Database

Use phpMyAdmin, MySQL Workbench, or command line:

```sql
-- Create database with UTF-8 support
CREATE DATABASE eipsi_forms_data 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

**Important:** Use `utf8mb4` for proper Unicode support (emojis, international characters).

#### Step 2: Create MySQL User

```sql
-- Create dedicated user
CREATE USER 'eipsi_user'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';

-- Grant privileges
GRANT ALL PRIVILEGES ON eipsi_forms_data.* TO 'eipsi_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

**Security Best Practices:**
- Use strong password (16+ characters, mixed case, numbers, symbols)
- Grant only necessary privileges (SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER)
- Use `localhost` if database on same server
- Consider IP-restricted access for remote databases

#### Step 3: Configure in WordPress Admin

1. Navigate to **EIPSI Forms → Database Configuration**
2. Enter credentials:

| Field | Example | Description |
|-------|---------|-------------|
| **Database Host** | `localhost` | Use `localhost` for local DB, or IP address for remote |
| **Database Username** | `eipsi_user` | MySQL username created in Step 2 |
| **Database Password** | `SecurePass123!` | MySQL password (stored encrypted with AES-256-CBC) |
| **Database Name** | `eipsi_forms_data` | Database name from Step 1 |

3. Click **"Test Connection"**
   - ✅ Success: "Connection successful! Schema verified."
   - ❌ Error: See error message for troubleshooting

4. Click **"Save Configuration"**

#### Step 4: Verify Schema Creation

After saving, plugin automatically:
- Creates `vas_form_results` table (27 columns)
- Creates `vas_form_events` table (8 columns)
- Adds performance indexes (6+ indexes)
- Verifies schema integrity

**Manual Verification:**
```sql
-- Check tables exist
SHOW TABLES LIKE 'vas_form%';

-- Verify structure
DESCRIBE vas_form_results;

-- Check indexes
SHOW INDEX FROM vas_form_results;
```

---

### Database Schema Auto-Repair

**Problem Solved:** "Unknown column 'participant_id'" errors after updates.

**How It Works:**

EIPSI Forms v1.2.2 includes **4-layer redundant protection** for zero data loss:

1. **Layer 1 - Installation:** Complete schema created on plugin activation
2. **Layer 2 - Periodic Check:** Auto-repair runs every 24 hours
3. **Layer 3 - Manual Trigger:** Click "Test Connection" to force repair
4. **Layer 4 - Emergency Recovery:** Auto-repair on INSERT failure with retry

**Manual Schema Repair:**

If you suspect schema issues:

1. Navigate to **EIPSI Forms → Database Configuration**
2. Click **"Test Connection"**
3. Check for success message with schema status
4. If issues detected, auto-repair runs automatically

**Monitoring Repair Status:**

Check WordPress debug log (`wp-content/debug.log`):
```
[EIPSI Forms] Schema verification: Missing columns detected
[EIPSI Forms] Adding column: participant_id
[EIPSI Forms] Schema repair completed successfully
```

---

### Database Failover & Redundancy

**External Database Failure Handling:**

If external database becomes unavailable:

1. **Automatic Fallback:** Plugin switches to WordPress database
2. **Zero Data Loss:** Submissions saved successfully
3. **Admin Notification:** Warning displayed in admin panel
4. **Auto-Recovery:** Switches back when external DB restored

**Monitoring Connection Status:**

Check **EIPSI Forms → Database Configuration** for connection indicator:
- 🟢 **Connected:** External database operational
- 🟡 **Fallback Mode:** Using WordPress database temporarily
- 🔴 **Error:** Connection failed, check credentials

---

## Privacy & Metadata Settings

### Understanding Metadata Capture

EIPSI Forms captures metadata to support clinical research analysis while respecting participant privacy.

**Navigate to:** **EIPSI Forms → Privacy Settings**

---

### Metadata Categories

#### 1. Always Captured (Cannot Disable)

These fields are essential for clinical research integrity:

- **Form ID:** Unique identifier for form version
- **Participant ID:** Anonymous UUID (e.g., `p-a1b2c3d4e5f6`)
- **Session ID:** Unique per submission attempt (e.g., `sess-1705764645000-xyz`)
- **Timestamps:** Form start, submission time, duration (seconds)
- **Quality Flag:** Automatic data quality assessment (HIGH, NORMAL, LOW)

**Privacy Note:** Participant IDs are completely anonymous and contain no personally identifiable information (PII).

---

#### 2. Recommended for Clinical Research (ON by Default)

**Therapeutic Engagement Metrics:**
- Time spent per field
- Navigation patterns
- Field changes/revisions
- Form engagement level

**Clinical Consistency Analysis:**
- Response coherence scoring
- Pattern detection
- Inconsistency flags

**Avoidance Pattern Detection:**
- Skipped fields
- Excessive backtracking
- Unusual completion patterns

**Device Type:**
- Mobile, Desktop, or Tablet
- Useful for analyzing completion rates by device

**Toggle:** ✅ ON by default (recommended to keep enabled)

**Use Cases:**
- Identify participants struggling with form completion
- Detect response patterns indicative of inattention
- Analyze device-specific user experience issues

---

#### 3. Audit Trail (ON by Default, GDPR-Configurable)

**IP Address Capture:**
- Primary use: Fraud detection, duplicate submission prevention
- Secondary use: Geographic analysis (anonymized)
- Privacy: Can be disabled for GDPR-strict studies
- Retention: 90 days by default (configurable)

**Toggle:** ✅ ON by default

**When to Enable:**
- Clinical trial integrity (duplicate detection)
- Fraud prevention (bot submissions)
- Geographic restriction enforcement
- Audit trail for regulatory compliance

**When to Disable:**
- Maximum anonymity required
- GDPR Art. 6 compliance needs
- No justifiable research need

---

#### 4. Device Information (OFF by Default, Optional)

**Browser:**
- Chrome, Firefox, Safari, Edge, etc.
- Version information

**Operating System:**
- Windows, macOS, Linux, iOS, Android
- Version information

**Screen Width:**
- Device screen resolution (px)
- Useful for responsive design analysis

**Toggle:** ⚙️ OFF by default (enable only if research requires)

**When to Enable:**
- Technical compatibility studies
- Device-specific UX analysis
- Screen adaptation research
- Accessibility studies

**Privacy Consideration:** These fields are disabled by default for maximum privacy. Enable only if your study protocol justifies their collection.

---

### Privacy Configuration Best Practices

#### For GDPR Compliance:

1. **Document Justification:**
   - List specific research questions requiring each metadata field
   - Include in study protocol and ethics approval

2. **Minimize Data Collection:**
   ```
   ✅ Enable: Participant ID, Session ID, Timestamps, Device Type
   ⚙️ Consider: IP Address (if fraud detection needed)
   ❌ Disable: Browser, OS, Screen Width (unless justified)
   ```

3. **Update Privacy Policy:**
   - Disclose all captured metadata to participants
   - Explain purpose and retention period
   - Provide opt-out mechanism if applicable

4. **Implement Data Retention:**
   - Set automatic deletion after study completion
   - Archive data securely before deletion
   - Document retention schedule

#### For HIPAA Compliance:

1. **Enable Audit Trail:**
   ```
   ✅ IP Address: ON (audit trail)
   ✅ Timestamps: ON (always)
   ✅ Quality Flags: ON (data integrity)
   ```

2. **Disable Unnecessary PII:**
   ```
   ❌ Browser: OFF (not PHI, but unnecessary)
   ❌ OS: OFF (not PHI, but unnecessary)
   ```

3. **Secure Storage:**
   - Use external database with encryption at rest
   - Enable HTTPS for data transmission
   - Implement access controls (WordPress capabilities)

4. **Business Associate Agreement (BAA):**
   - Ensure hosting provider has BAA
   - Document data flow and storage locations

---

### Configuring Privacy Settings

**Step-by-Step:**

1. Navigate to **EIPSI Forms → Privacy Settings**

2. Review each toggle:
   - **Therapeutic Engagement:** ✅ Recommended ON
   - **Clinical Consistency:** ✅ Recommended ON
   - **Avoidance Patterns:** ✅ Recommended ON
   - **Device Type:** ✅ Recommended ON
   - **IP Address:** ⚙️ ON by default (configure based on study)
   - **Browser:** ⚙️ OFF by default (enable only if needed)
   - **OS:** ⚙️ OFF by default (enable only if needed)
   - **Screen Width:** ⚙️ OFF by default (enable only if needed)

3. Click **"Save Settings"**

4. **Verify Configuration:**
   - Submit test form
   - Navigate to **Results & Experience**
   - Check submission details to confirm only enabled fields captured

---

### Exporting Privacy-Compliant Data

When exporting data (**Results & Experience → Export to Excel**):

**Automatically Excluded:**
- Any metadata fields disabled in Privacy Settings
- IP addresses if IP toggle disabled
- Browser/OS/Screen Width if toggles disabled

**Always Included:**
- Form responses (primary data)
- Participant ID (anonymous)
- Session ID
- Timestamps
- Quality Flag

**GDPR Right to Erasure:**

To delete specific participant data:

1. Navigate to **Results & Experience → Submissions**
2. Filter by Participant ID
3. Select submissions
4. Click **"Delete Selected"**
5. Confirm deletion

Or via SQL:
```sql
DELETE FROM vas_form_results WHERE participant_id = 'p-abc123def456';
```

---

## Navigation Settings

### Allow Backwards Navigation Toggle

**Location:** Form Editor → EIPSI Form Container → Settings

Controls whether participants can return to previous pages in multi-page forms.

#### Option 1: Allow Backwards Navigation (ON)

**Behavior:**
- "Anterior" (Previous) button appears on pages 2+
- Participants can review and change previous answers
- Data persists when navigating backwards
- Useful for review and correction

**Best For:**
- General questionnaires
- Self-paced assessments
- Forms allowing participant review
- Studies where data revision is acceptable

**Clinical Use Cases:**
- Demographic forms (participants may need to correct info)
- Quality of life questionnaires
- Patient-reported outcome measures (PROMs)

#### Option 2: Prevent Backwards Navigation (OFF)

**Behavior:**
- "Anterior" button hidden on all pages
- Participants can only move forward
- Prevents response contamination from review
- Enforces sequential completion

**Best For:**
- Timed assessments
- Cognitive tests
- Studies requiring uncontaminated responses
- Research protocols preventing revision

**Clinical Use Cases:**
- Implicit Association Tests (IAT)
- Response time measurements
- Priming studies
- Cognitive assessment batteries

---

### Configuring Navigation Behavior

**Step-by-Step:**

1. Open WordPress editor
2. Add **EIPSI Form Container** block
3. Select the container block
4. In right sidebar, find **"Navigation Settings"**
5. Toggle **"Allow Backwards Navigation"**
   - ✅ ON: Participants can go back
   - ❌ OFF: Participants cannot go back
6. Publish form

**Testing:**

1. Open form in incognito window
2. Fill page 1, click "Siguiente" (Next)
3. On page 2:
   - **If ON:** "Anterior" button visible
   - **If OFF:** Only "Siguiente" or "Submit" visible
4. Verify behavior matches study protocol

---

## Form Creation & Design

### Creating Your First Form

#### Step 1: Add Form Container

1. Create new Post or Page
2. Click **"+"** to add block
3. Search for **"EIPSI Form Container"**
4. Add block to page

#### Step 2: Add Pages

Multi-page forms require **EIPSI Página** blocks:

1. Inside Form Container, add **"EIPSI Página"** block
2. Add more pages as needed (recommended: 3-5 pages max)
3. Each page can contain multiple fields

**Page Organization Tips:**
- Group related questions together
- Keep pages short (5-10 fields max)
- Use descriptions between sections
- Test navigation flow before deployment

#### Step 3: Add Fields

Inside each **EIPSI Página**, add field blocks:

**Available Field Types:**

| Field Type | Use Case | Example |
|------------|----------|---------|
| **EIPSI VAS Slider** | Continuous scales (0-100, 0-10) | Pain intensity, mood rating |
| **EIPSI Campo Likert** | Likert scales (3, 5, 7 points) | Agreement scales, frequency ratings |
| **EIPSI Campo Radio** | Single choice | Gender, yes/no questions |
| **EIPSI Campo Multiple** | Multiple choice | Symptoms checklist, preferences |
| **EIPSI Campo Select** | Dropdown selection | Country, education level |
| **EIPSI Campo Texto** | Short text input | Name, age, email |
| **EIPSI Campo Textarea** | Long text input | Open-ended responses, comments |
| **EIPSI Campo Descripción** | Instructions/information | Section headers, instructions |

#### Step 4: Configure Fields

For each field:

1. **Label:** Question text (e.g., "How are you feeling today?")
2. **Field Name:** Unique identifier (e.g., `mood_today`) - no spaces, lowercase
3. **Required:** Toggle if response is mandatory
4. **Helper Text:** Additional instructions (optional)

**Field Name Best Practices:**
- Use lowercase letters
- Use underscores for spaces: `pain_level`
- Be descriptive: `anxiety_gad7_q1`
- Avoid special characters: `mood_today` ✅ `mood/today` ❌
- Keep under 64 characters

---

### Design & Styling

#### Choosing a Preset

EIPSI Forms includes 5 professionally designed presets:

**In Form Container → Settings → "Preset":**

| Preset | Description | Best For | Contrast Ratio |
|--------|-------------|----------|----------------|
| **Clinical Blue** | Professional EIPSI branding | Medical/clinical studies | 7.47:1 (WCAG AAA) |
| **Minimal White** | Ultra-clean, modern | General research | 12.63:1 (WCAG AAA) |
| **Warm Neutral** | Warm, approachable | Therapy/counseling contexts | 10.15:1 (WCAG AAA) |
| **Serene Teal** | Calming, stress-reducing | Mindfulness/relaxation studies | 8.21:1 (WCAG AAA) |
| **Dark EIPSI** | Dark mode, high contrast | Accessibility, low-light environments | 14.68:1 (WCAG AAA) |

**All presets meet WCAG 2.1 AA standards** (4.5:1 minimum contrast).

#### WYSIWYG Instant Preview

**New in v1.2.1:** See preset changes instantly in editor:

1. Select Form Container
2. Change "Preset" dropdown
3. Preview updates immediately (no save required)
4. Compare presets visually before publishing

#### Custom Styling

Advanced users can customize colors via Form Container settings:

- Primary color
- Hover color
- Active color
- Text color
- Background color
- Error/Success/Warning colors

**52 CSS variables available** for granular control.

---

### Multi-Page Form Best Practices

#### Optimal Page Structure

```
📄 Page 1: Introduction & Demographics (3-5 fields)
   - Participant ID (auto-generated)
   - Age, gender, education

📄 Page 2: Primary Assessment (5-10 fields)
   - Core research questions
   - Main outcome measures

📄 Page 3: Secondary Measures (5-10 fields)
   - Additional questionnaires
   - Exploratory measures

📄 Page 4: Open-Ended & Comments (2-3 fields)
   - Qualitative responses
   - Feedback

📄 Page 5: Completion (1 field)
   - Thank you message
   - Contact information
```

#### Progress Indication

**Automatic:** Plugin displays "Página X de Y" on each page

**Tips:**
- Keep total pages ≤ 5 for optimal completion rates
- Most important questions on early pages (reduce abandonment impact)
- Test on mobile devices (44x44px touch targets)

---

## Admin Panel Configuration

### Results & Experience Page

**Navigate to:** **EIPSI Forms → Results & Experience**

Three-tab interface for managing form data:

#### Tab 1: Submissions

**Features:**
- View all form submissions
- Filter by Form ID, date range
- Search by Participant ID
- Export to Excel/CSV
- View detailed submission metadata
- Delete individual submissions

**Columns Displayed:**
- ID, Form Name, Form ID, Participant ID, Session ID
- Created At, Submitted At, Duration
- Device, Browser, OS, Screen Width (if enabled)
- IP Address (if enabled)
- Quality Flag, Status

**Actions:**
- **View Details:** Click submission to expand full response data
- **Export:** Download filtered results as Excel/CSV
- **Delete:** Remove submission (requires confirmation)

#### Tab 2: Completion Message

**Features:**
- Customize global thank you message
- Rich text editor with media upload
- Show/hide site logo
- Show/hide "Return to Start" button
- Optional redirect URL

**Configuration:**

1. **Message Text:**
   - Use wp_editor for formatting
   - Include completion instructions
   - Add contact information if applicable

2. **Visual Elements:**
   - ✅ Show Logo: Display site logo above message
   - ✅ Show "Return to Start" Button: Allow participants to return to form start

3. **Redirect URL (Optional):**
   - Leave blank: Show static completion message
   - Enter URL: Redirect participant after submission (e.g., `https://example.com/next-step`)

4. Click **"Save Settings"**

**Preview:** Shows live preview of completion message as participants will see it.

#### Tab 3: Privacy & Metadata

Duplicate of Privacy Settings for convenient access:

- Configure metadata capture toggles
- View current privacy configuration
- Quick access without navigating to separate settings page

---

### Database Configuration Page

**Navigate to:** **EIPSI Forms → Database Configuration**

**Features:**

1. **Connection Settings:**
   - Host, Username, Password, Database Name
   - "Test Connection" button (triggers schema verification)
   - "Save Configuration" button (stores encrypted credentials)

2. **Connection Status:**
   - 🟢 Connected: External database operational
   - 🟡 Fallback: Using WordPress database temporarily
   - 🔴 Error: Connection failed

3. **Schema Management:**
   - Automatic schema verification on test connection
   - Manual repair trigger
   - Schema status display

4. **Security:**
   - Passwords stored encrypted (AES-256-CBC)
   - Credentials never displayed after saving
   - Nonce verification on all actions

---

## Advanced Settings

### WordPress Hooks & Filters

Developers can extend plugin functionality using WordPress hooks:

#### Action Hooks

```php
// Before form renders
do_action('eipsi_form_before_render', $form_id, $attributes);

// After successful submission
do_action('eipsi_form_after_submit', $form_id, $participant_id, $responses);

// When tracking event occurs
do_action('eipsi_tracking_event', $event_type, $form_id, $session_id, $metadata);
```

#### Filter Hooks

```php
// Modify field validation
apply_filters('eipsi_validate_field', $is_valid, $field_name, $value, $field_config);

// Sanitize field data
apply_filters('eipsi_sanitize_field', $sanitized_value, $field_name, $raw_value);

// Customize style tokens
apply_filters('eipsi_style_tokens', $style_config, $form_id);
```

#### Example: Custom Validation

```php
// Add to theme's functions.php or custom plugin
add_filter('eipsi_validate_field', 'custom_age_validation', 10, 4);

function custom_age_validation($is_valid, $field_name, $value, $field_config) {
    if ($field_name === 'age') {
        // Age must be between 18 and 100
        if ($value < 18 || $value > 100) {
            return new WP_Error('invalid_age', 'Age must be between 18 and 100.');
        }
    }
    return $is_valid;
}
```

#### Example: Log All Submissions

```php
add_action('eipsi_form_after_submit', 'log_form_submission', 10, 3);

function log_form_submission($form_id, $participant_id, $responses) {
    error_log(sprintf(
        '[EIPSI Forms] Form %s submitted by participant %s with %d responses',
        $form_id,
        $participant_id,
        count($responses)
    ));
}
```

---

### wp-config.php Settings

Optimize plugin performance with WordPress configuration:

```php
// Increase memory limit for large forms
define('WP_MEMORY_LIMIT', '256M');

// Increase max execution time for exports
set_time_limit(300);

// Enable caching for better performance
define('WP_CACHE', true);

// Enable debug mode (development only)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

### Database Optimization

For large studies (1000+ submissions):

```sql
-- Add composite indexes for common queries
ALTER TABLE vas_form_results 
ADD INDEX idx_form_date (form_id, submitted_at);

-- Use InnoDB for better concurrent inserts
ALTER TABLE vas_form_results ENGINE=InnoDB;

-- Optimize table regularly
OPTIMIZE TABLE vas_form_results;

-- Analyze table for query optimization
ANALYZE TABLE vas_form_results;
```

---

### Performance Tuning

#### PHP Settings

Edit `php.ini` or `.htaccess`:

```ini
# Increase memory limit
memory_limit = 256M

# Increase max execution time
max_execution_time = 300

# Increase POST data size (large forms)
post_max_size = 32M
upload_max_filesize = 32M
```

#### Server Optimization

- Enable **OpCache** for PHP bytecode caching
- Use **Redis** or **Memcached** for object caching
- Enable **Gzip compression** for assets
- Use **CDN** for static assets (if applicable)

---

## Configuration Checklist

After configuration, verify:

- [ ] Database connection successful (Test Connection)
- [ ] Privacy settings match study protocol
- [ ] Navigation settings appropriate for study type
- [ ] Preset selected and visually verified
- [ ] Test form created and submitted successfully
- [ ] Submission appears in Results page with correct metadata
- [ ] Export functionality tested (Excel/CSV)
- [ ] Completion message customized
- [ ] Debug logging enabled (for troubleshooting)
- [ ] Backup strategy implemented
- [ ] Data retention policy documented
- [ ] Privacy policy updated with metadata disclosure

---

## Next Steps

1. **Create Production Forms:** Design forms for your study
2. **Test End-to-End:** Submit test forms and verify data flow
3. **Pilot Test:** Run small pilot with 5-10 participants
4. **Monitor Performance:** Review submission times and error rates
5. **Launch Study:** Deploy to participants with monitoring

---

## Support

For configuration assistance:

1. Review **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** for common issues
2. Check WordPress debug log: `wp-content/debug.log`
3. Verify server requirements (PHP 7.4+, MySQL 5.7+)
4. Contact support with configuration details and error messages

---

**Configuration Guide Version:** 1.2.2  
**Last Updated:** January 2025  
**Plugin Version:** 1.2.2
