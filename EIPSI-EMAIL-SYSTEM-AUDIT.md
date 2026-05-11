# 📧 EIPSI Forms - Email System Architecture Audit
**Version:** 2.6.1  
**Date:** May 10, 2026  
**Purpose:** Complete technical documentation of the email system for Claude AI analysis

---

## 🎯 Executive Summary

The EIPSI Forms email system is a comprehensive transactional email infrastructure designed for longitudinal research studies. It manages participant communications across multiple touchpoints including:

- **Welcome & Onboarding** (magic links, confirmations)
- **Wave Availability Notifications** (T1-Anchor system)
- **Progressive Nudge System** (5-stage reminder cascade: Nudge 0-4)
- **Dropout Recovery** (empathetic re-engagement)
- **Administrative Alerts** (failed email monitoring)

**Current Issue:** Email type logging shows empty values (`email_type=''`) for some emails despite code improvements in v2.6.1.

---

## 📊 System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    EMAIL SYSTEM ARCHITECTURE                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐ │
│  │   TRIGGERS   │─────▶│   SERVICES   │─────▶│   DELIVERY   │ │
│  └──────────────┘      └──────────────┘      └──────────────┘ │
│        │                      │                      │          │
│        │                      │                      │          │
│   ┌────▼────┐           ┌────▼────┐           ┌────▼────┐    │
│   │ Cron    │           │ Email   │           │ SMTP    │    │
│   │ Jobs    │           │ Service │           │ Service │    │
│   │         │           │         │           │         │    │
│   │ Wave    │           │ Wave    │           │ wp_mail │    │
│   │ Events  │           │ Avail.  │           │         │    │
│   │         │           │ Service │           │         │    │
│   │ Manual  │           │         │           │         │    │
│   │ Admin   │           │ Confirm │           │         │    │
│   └─────────┘           │ Service │           └─────────┘    │
│                         │         │                 │          │
│                         │ Failed  │                 │          │
│                         │ Alerts  │                 │          │
│                         └─────────┘                 │          │
│                               │                     │          │
│                               ▼                     ▼          │
│                         ┌──────────────────────────────┐      │
│                         │   EMAIL LOG DATABASE         │      │
│                         │   wp_survey_email_log        │      │
│                         │                              │      │
│                         │  - id, participant_id        │      │
│                         │  - email_type (VARCHAR 100)  │      │
│                         │  - status, sent_at           │      │
│                         │  - metadata (JSON)           │      │
│                         └──────────────────────────────┘      │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Core Components

### 1. **Backend Services** (PHP)

#### 1.1 Main Email Service
**File:** `admin/services/class-email-service.php`  
**Class:** `EIPSI_Email_Service`  
**Responsibilities:**
- Central email sending orchestration
- Template rendering
- Magic link generation
- Email logging with metadata
- Rate limiting and retry logic

**Key Methods:**
```php
// Core sending
send_email($survey_id, $participant_id, $to, $type, $subject, $content, $metadata)
log_email($survey_id, $participant_id, $type, $status, $error_message, $subject, $metadata)

// Specific email types
send_welcome_email($survey_id, $participant_id)
send_magic_link_email($survey_id, $participant_id, $custom_message)
send_confirmation_email($survey_id, $participant_id, $confirmation_token)
send_wave_reminder_email($survey_id, $participant_id, $wave, $nudge_stage)
send_wave_confirmation_email($survey_id, $participant_id, $wave, $next_wave)

// Magic link utilities
generate_magic_link_url($survey_id, $participant_id)
get_latest_magic_link_url($survey_id, $participant_id)
extend_magic_link_expiry($survey_id, $participant_id, $hours)

// Resend functionality
resend_participant_email($participant_id, $email_type, $survey_id, $wave_id)
```

**Email Type Enhancement (v2.6.1):**
```php
// Lines 561-576: Wave-specific email types
$wave_index = isset($wave->wave_index) ? $wave->wave_index : 1;
$base_type = ($stage === 0) ? 'wave_availability' : 'nudge_' . $stage;
$email_type = $base_type . '_T' . $wave_index;  // e.g., "wave_availability_T1", "nudge_1_T2"

$metadata = array(
    'wave_id' => $wave->id,
    'wave_index' => $wave_index,
    'wave_name' => isset($wave->name) ? $wave->name : '',
    'nudge_stage' => $stage,
    'base_type' => $base_type
);
```

**Validation & Safety (v2.6.1):**
```php
// Lines 1000-1008: Ensure email_type is never empty
if (empty($type)) {
    $type = 'custom';
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
    $caller = isset($backtrace[1]) ? $backtrace[1]['function'] : 'unknown';
    $file = isset($backtrace[1]) ? basename($backtrace[1]['file']) : 'unknown';
    error_log("[EIPSI Email] WARNING: email_type was empty, defaulting to 'custom'. Called from: {$caller} in {$file}");
}
```

---

#### 1.2 Wave Availability Email Service
**File:** `admin/services/class-wave-availability-email-service.php`  
**Class:** `EIPSI_Wave_Availability_Email_Service`  
**Purpose:** Specialized service for Nudge 0 (wave availability notifications)

**Key Features:**
- **Duplicate Prevention:** Multi-layered verification
- **Retry Logic:** Up to 3 attempts with 5-minute cooldown
- **T1-Anchor Validation:** Ensures wave is actually available

**Core Method:**
```php
ensure_wave_availability_email_sent($assignment, $wave, $participant, $study_id)
```

**Verification Layers:**
1. **Email Log Check:** Searches for existing `wave_availability` emails with matching `wave_id` in metadata
2. **Form Results Check:** Verifies if participant already accessed the wave
3. **Assignment Status Check:** Confirms assignment isn't already `submitted` or `in_progress`

**Email Type Format (v2.6.1):**
```php
// Lines 437-448
$wave_index = isset($wave->wave_index) ? $wave->wave_index : 1;
$email_type = 'wave_availability_T' . $wave_index;

$metadata = array(
    'wave_id' => $wave->id,
    'wave_index' => $wave_index,
    'wave_name' => $wave->name,
    'nudge_stage' => 0,
    'email_variant' => 'wave_available',
    'base_type' => 'wave_availability'
);
```

---

#### 1.3 Email Confirmation Service (Double Opt-In)
**File:** `admin/services/class-email-confirmation-service.php`  
**Class:** `EIPSI_Email_Confirmation_Service`

**Key Methods:**
```php
generate_confirmation_token($survey_id, $participant_id, $email)
validate_confirmation_token($token, $email)
mark_confirmed($token, $email)
generate_confirmation_url($token, $email)
resend_confirmation_email($participant_id)
cleanup_expired_confirmations()
```

**Security Fix (v1.1.0):**
- Removed email from URL query string to prevent `+` character decoding issues
- Token-only validation for improved security

---

#### 1.4 Failed Email Alerts Service
**File:** `admin/services/class-failed-email-alerts-service.php`  
**Class:** `EIPSI_Failed_Email_Alerts_Service`

**Monitoring Features:**
```php
get_failed_emails($days, $limit)
get_failure_summary($days)
retry_failed_email($log_id)
bulk_retry($log_ids)
get_participant_retry_history($participant_id)
get_dashboard_widget_data()
```

**Analytics:**
- Failed email trends (24h, 7d, 30d)
- Failure reasons breakdown
- Retry success rates
- Participant-specific history

---

#### 1.5 SMTP Service
**File:** `admin/services/class-smtp-service.php`  
**Class:** `EIPSI_SMTP_Service`

**Configuration:**
- Host, port, encryption (SSL/TLS)
- Authentication (username/password)
- From address and name
- Connection testing

**Fallback Chain:**
1. SMTP (if configured)
2. `wp_mail()` (WordPress default)

---

### 2. **AJAX Handlers** (Backend API)

#### 2.1 Email Log Handlers
**File:** `admin/ajax-email-log-handlers.php`

**Endpoints:**
```php
wp_ajax_eipsi_get_email_logs              // Fetch paginated email logs
wp_ajax_eipsi_get_email_details           // Get single email details
wp_ajax_eipsi_resend_email                // Retry failed email
wp_ajax_eipsi_email_log_export            // Export logs to CSV
```

**Data Format:**
```php
// Email log entry structure
array(
    'id' => 1023,
    'participant_id' => 154,
    'email_type' => 'wave_availability_T3',  // ← Enhanced in v2.6.1
    'recipient_email' => 'user@example.com',
    'subject' => 'Tu siguiente evaluación está disponible',
    'status' => 'sent',
    'sent_at' => '2026-05-10 17:21:06',
    'sent_at_formatted' => 'mayo 10, 2026, 5:21 pm',  // WordPress timezone
    'metadata' => array(
        'wave_id' => 179,
        'wave_index' => 3,
        'wave_name' => 'Toma 3',
        'nudge_stage' => 0,
        'base_type' => 'wave_availability'
    )
)
```

---

#### 2.2 Study Dashboard API
**File:** `admin/study-dashboard-api.php`

**Email-Related Endpoints:**
```php
wp_ajax_eipsi_get_study_email_logs        // Study-specific email logs
wp_ajax_eipsi_resend_participant_email    // Resend to participant
wp_ajax_eipsi_get_participant_email_history  // Participant email timeline
```

**Resend Email Handler:**
```php
// Lines 1196-1290
function wp_ajax_eipsi_resend_participant_email_handler() {
    // Validates: participant_id, email_type, survey_id, wave_id
    // Supported types: 'welcome', 'magic_link', 'reminder', 'confirmation', 'recovery'
    // Calls: EIPSI_Email_Service::resend_participant_email()
}
```

---

#### 2.3 Failed Email Handlers
**File:** `admin/ajax-phase3-handlers.php`

**Endpoints:**
```php
wp_ajax_eipsi_get_failed_email_alerts     // Get failed emails list
wp_ajax_eipsi_retry_failed_email          // Retry single email
wp_ajax_eipsi_bulk_retry_emails           // Bulk retry
wp_ajax_eipsi_get_email_failure_summary   // Analytics summary
```

---

### 3. **Frontend Components** (JavaScript)

#### 3.1 Email Log UI
**File:** `admin/js/email-log.js`

**Responsibilities:**
- Render email log table
- Filter by status, type, date
- Search by email address
- Display email details modal
- Resend failed emails
- Export to CSV

**Email Type Labels (v2.6.1):**
```javascript
// Lines 270-329: Enhanced type detection
function getTypeLabel(type) {
    // Wave-specific types (v2.6.1)
    if (type.startsWith('wave_availability_T')) {
        const waveNum = type.split('_T')[1];
        return `📋 Wave Availability (T${waveNum})`;
    }
    if (type.match(/^nudge_\d+_T\d+$/)) {
        const [_, stage, wave] = type.match(/^nudge_(\d+)_T(\d+)$/);
        return `🔔 Nudge ${stage} (T${wave})`;
    }
    
    // Legacy types
    const labels = {
        'welcome': '👋 Bienvenida',
        'magic_link': '🔗 Magic Link',
        'reminder': '🔔 Recordatorio',
        'confirmation_request': '✉️ Confirmación Email',
        'wave_availability': '📋 Wave Disponible',
        'nudge_0': '🔔 Nudge 0 (Availability)',
        'nudge_1': '🔔 Nudge 1',
        'nudge_2': '🔔 Nudge 2',
        'nudge_3': '🔔 Nudge 3',
        'nudge_4': '🔔 Nudge 4 (Final)',
        'dropout_recovery': '🚨 Recuperación Dropout',
        'custom': '📧 Personalizado'
    };
    
    return labels[type] || type;
}
```

**Date Formatting (Timezone Fix v2.6.1):**
```javascript
// Lines 225-254: Use server-formatted dates
function formatDateTime(dateString) {
    // Server already formats with WordPress timezone via wp_date()
    return dateString;  // No client-side conversion needed
}
```

---

#### 3.2 Study Dashboard Email Integration
**File:** `admin/js/study-dashboard.js`

**Email Log Rendering:**
```javascript
// Lines 2760-2841: renderEmailLogs()
function renderEmailLogs(logs) {
    logs.forEach(log => {
        const typeLabel = emailTypeLabels[log.email_type] || log.email_type;
        const statusBadge = getStatusBadge(log.status);
        const formattedDate = log.sent_at_formatted;  // Server-formatted
        
        // Render table row with resend button for failed emails
    });
}
```

**Email Type Labels:**
```javascript
// Lines 3287-3306
const emailTypeLabels = {
    'wave_availability_T1': '📋 Wave T1 Disponible',
    'wave_availability_T2': '📋 Wave T2 Disponible',
    'wave_availability_T3': '📋 Wave T3 Disponible',
    'nudge_1_T1': '🔔 Nudge 1 (T1)',
    'nudge_1_T2': '🔔 Nudge 1 (T2)',
    'nudge_2_T1': '🔔 Nudge 2 (T1)',
    'nudge_2_T2': '🔔 Nudge 2 (T2)',
    // ... etc
};
```

---

### 4. **Email Templates** (HTML)

**Location:** `includes/emails/`

**Available Templates:**
1. **`welcome.php`** - Welcome email with magic link
2. **`magic-link.php`** - Standalone magic link email
3. **`email-confirmation.php`** - Double opt-in confirmation
4. **`wave-nudge-0.php`** - Wave availability notification (Nudge 0)
5. **`wave-nudge-1.php`** - First reminder (gentle)
6. **`wave-nudge-2.php`** - Second reminder (important)
7. **`wave-nudge-3.php`** - Third reminder (urgent)
8. **`wave-nudge-4.php`** - Final reminder (last chance)
9. **`wave-reminder.php`** - Generic wave reminder (fallback)
10. **`wave-confirmation.php`** - Wave completion confirmation
11. **`dropout-recovery.php`** - Standard dropout recovery
12. **`dropout-recovery-empathetic.php`** - Empathetic dropout recovery
13. **`weekly-t1-reminder.php`** - Weekly T1 reminder
14. **`gentle-reminder.php`** - Gentle reminder variant
15. **`manual-reminder.php`** - Admin-triggered manual reminder
16. **`reminder-take.php`** - Take-specific reminder

**Template Structure:**
```php
<?php
// Template: wave-nudge-0.php
// Placeholders: first_name, survey_name, wave_index, magic_link, due_date_html, etc.
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Responsive email styles */
    </style>
</head>
<body>
    <div class="email-container">
        <h1>¡Hola <?php echo esc_html($first_name); ?>!</h1>
        <p>Tu siguiente evaluación está disponible...</p>
        <a href="<?php echo esc_url($magic_link); ?>" class="cta-button">
            Comenzar Evaluación →
        </a>
    </div>
</body>
</html>
```

---

### 5. **Database Schema**

#### 5.1 Email Log Table
**Table:** `wp_survey_email_log`

**Schema (v2.6.1):**
```sql
CREATE TABLE wp_survey_email_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    participant_id BIGINT(20) UNSIGNED NOT NULL,
    survey_id INT(11),
    email_type VARCHAR(100) DEFAULT 'custom',  -- ← Changed from ENUM in v2.6.1
    wave_id BIGINT(20) UNSIGNED,
    recipient_email VARCHAR(255),
    subject VARCHAR(500),
    content TEXT,
    sent_at DATETIME NOT NULL,
    status ENUM('sent', 'failed', 'bounced', 'audit') DEFAULT 'sent',
    error_message TEXT,
    metadata TEXT,  -- JSON: {wave_id, wave_index, nudge_stage, etc.}
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY participant_id (participant_id),
    KEY survey_id (survey_id),
    KEY email_type (email_type),
    KEY status (status),
    KEY sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration Issue (v2.6.1):**
- **Problem:** Column `email_type` is still `ENUM` in production database
- **Expected:** `VARCHAR(100)` to support longer types like `wave_availability_T3`
- **Impact:** New email types are truncated to empty string
- **Solution:** Automatic migration added in `database-schema-manager.php`

---

#### 5.2 Email Confirmations Table
**Table:** `wp_survey_email_confirmations`

```sql
CREATE TABLE wp_survey_email_confirmations (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    survey_id BIGINT(20) UNSIGNED NOT NULL,
    participant_id BIGINT(20) UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    token_plain VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    confirmed_at DATETIME,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY token_hash (token_hash),
    KEY participant_id (participant_id),
    KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

#### 5.3 Magic Links Table
**Table:** `wp_survey_magic_links`

```sql
CREATE TABLE wp_survey_magic_links (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    survey_id BIGINT(20) UNSIGNED NOT NULL,
    participant_id BIGINT(20) UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY token (token),
    KEY participant_id (participant_id),
    KEY survey_id (survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🔄 Email Flow Diagrams

### Flow 1: Wave Availability Email (Nudge 0)

```
┌─────────────────────────────────────────────────────────────────┐
│                   WAVE AVAILABILITY FLOW                         │
└─────────────────────────────────────────────────────────────────┘

1. TRIGGER
   ├─ Cron Job (hourly): admin/cron-reminders-handler.php
   ├─ T1 Completion: ajax-handlers.php → Wave_Service::mark_assignment_submitted()
   └─ Manual Admin: Study Dashboard → "Send Reminder"

2. VALIDATION (Wave_Availability_Email_Service)
   ├─ Is wave available? (T1-Anchor check)
   ├─ Already sent? (3-layer verification)
   │  ├─ Email log check (metadata.wave_id + nudge_stage=0)
   │  ├─ Form results check (participant accessed wave?)
   │  └─ Assignment status check (submitted/in_progress?)
   └─ Retry limit reached? (max 3 attempts)

3. EMAIL GENERATION (Email_Service)
   ├─ Generate magic link token
   ├─ Build study page URL
   ├─ Render template: wave-nudge-0.php
   ├─ Set email_type: "wave_availability_T{wave_index}"
   └─ Add metadata: {wave_id, wave_index, nudge_stage: 0}

4. DELIVERY
   ├─ Try SMTP (if configured)
   └─ Fallback to wp_mail()

5. LOGGING
   ├─ Insert into wp_survey_email_log
   ├─ email_type: "wave_availability_T3"
   ├─ status: "sent" or "failed"
   ├─ metadata: JSON with wave details
   └─ sent_at: current_time('mysql')

6. POST-SEND
   ├─ Mark Nudge 0 as sent (transient cache)
   ├─ Schedule Nudge 1 (if follow-up enabled)
   └─ Update assignment.reminder_count = 1
```

---

### Flow 2: Progressive Nudge System

```
┌─────────────────────────────────────────────────────────────────┐
│                    PROGRESSIVE NUDGE FLOW                        │
└─────────────────────────────────────────────────────────────────┘

NUDGE 0 (Availability)
   │
   ├─ Sent immediately when wave becomes available
   ├─ email_type: "wave_availability_T{n}"
   └─ Template: wave-nudge-0.php
   │
   ▼
NUDGE 1 (Gentle Reminder) - After X hours/days
   │
   ├─ Triggered by: Cron job or Event Scheduler
   ├─ Condition: assignment.status = 'pending' AND reminder_count = 0
   ├─ email_type: "nudge_1_T{n}"
   └─ Template: wave-nudge-1.php
   │
   ▼
NUDGE 2 (Important) - After Y hours/days
   │
   ├─ Condition: reminder_count = 1
   ├─ email_type: "nudge_2_T{n}"
   └─ Template: wave-nudge-2.php
   │
   ▼
NUDGE 3 (Urgent) - After Z hours/days
   │
   ├─ Condition: reminder_count = 2
   ├─ email_type: "nudge_3_T{n}"
   └─ Template: wave-nudge-3.php
   │
   ▼
NUDGE 4 (Final) - Last chance
   │
   ├─ Condition: reminder_count = 3
   ├─ email_type: "nudge_4_T{n}"
   └─ Template: wave-nudge-4.php
   │
   ▼
DROPOUT RECOVERY (if still not completed)
   │
   ├─ Triggered after wave expires
   ├─ email_type: "dropout_recovery"
   └─ Template: dropout-recovery-empathetic.php
```

---

## 🐛 Known Issues & Fixes

### Issue 1: Empty email_type in Logs (v2.6.1)

**Symptom:**
```
[EIPSI EMAIL LOGS] Log ID 1023: email_type='', status='sent'
[EIPSI EMAIL LOGS] Log ID 1022: email_type='', status='sent'
```

**Root Cause:**
- Database column `email_type` is still `ENUM` instead of `VARCHAR(100)`
- ENUM cannot store new wave-specific types like `wave_availability_T3`
- Values are truncated to empty string

**Evidence:**
```php
// Code sends correct type
$email_type = 'wave_availability_T3';  // ✓ Correct

// But database schema is:
email_type ENUM('welcome', 'magic_link', 'reminder', ...) DEFAULT 'custom'
// ✗ Cannot store 'wave_availability_T3'
```

**Solution Implemented:**
1. **Schema Definition Updated** (`database-schema-manager.php:596-597`)
   ```php
   'email_type' => "VARCHAR(100) DEFAULT 'custom'",
   ```

2. **Automatic Migration Added** (`database-schema-manager.php:1635-1681`)
   ```php
   public static function migrate_email_type_to_varchar() {
       global $wpdb;
       $table_name = $wpdb->prefix . 'survey_email_log';
       
       $column_info = $wpdb->get_results(
           "SHOW COLUMNS FROM `{$table_name}` LIKE 'email_type'"
       );
       
       if (!empty($column_info)) {
           $current_type = $column_info[0]->Type;
           
           if (strpos($current_type, 'enum') !== false) {
               $wpdb->query("ALTER TABLE `{$table_name}` 
                            MODIFY COLUMN email_type VARCHAR(100) DEFAULT 'custom'");
               error_log("[EIPSI Migration v2.6.1] Changed email_type from ENUM to VARCHAR(100)");
           }
       }
   }
   ```

3. **Migration Hook** (`eipsi-forms.php:245-246`)
   ```php
   add_action('admin_init', array('EIPSI_Database_Schema_Manager', 'migrate_email_type_to_varchar'));
   ```

**Verification:**
- Migration runs automatically on every admin page load
- Only executes once (checks if column is still ENUM)
- Logs success message to error log
- New emails will have correct `email_type` after migration

---

### Issue 2: Timezone Mismatch in Participant Dashboard (FIXED v2.6.1)

**Symptom:**
- Timestamps showing UTC instead of WordPress configured timezone
- Example: `available_at` showing `2026-05-10 17:19:27` (UTC) instead of `2026-05-10 14:19:27` (Argentina UTC-3)

**Solution:**
```php
// Before (UTC)
$available_at = $assignment->available_at;  // "2026-05-10 17:19:27"

// After (Local timezone)
$available_at_local = get_date_from_gmt($assignment->available_at);
$formatted = wp_date('F j, Y, g:i a', strtotime($available_at_local));
// "mayo 10, 2026, 2:19 pm"
```

**Files Modified:**
- `includes/templates/longitudinal-study-display.php`
- `includes/templates/dashboard/timeline-history.php`
- `includes/templates/dashboard/hero-card.php`
- `admin/study-dashboard-api.php`

---

## 📈 Email Type Taxonomy (v2.6.1)

### Current Email Types

#### **Wave-Specific Types** (New in v2.6.1)
```
wave_availability_T1    → Wave 1 (T1) availability notification
wave_availability_T2    → Wave 2 (T2) availability notification
wave_availability_T3    → Wave 3 (T3) availability notification
nudge_1_T1              → Nudge 1 for Wave 1
nudge_1_T2              → Nudge 1 for Wave 2
nudge_2_T1              → Nudge 2 for Wave 1
nudge_2_T2              → Nudge 2 for Wave 2
nudge_3_T1              → Nudge 3 for Wave 1
nudge_4_T2              → Nudge 4 for Wave 2
```

#### **Legacy Types** (Pre-v2.6.1)
```
welcome                 → Welcome email with magic link
magic_link              → Standalone magic link email
confirmation_request    → Double opt-in confirmation
reminder                → Generic wave reminder (deprecated)
wave_availability       → Wave available (no wave index)
nudge_0                 → Availability notification (deprecated)
nudge_1                 → First reminder (deprecated)
nudge_2                 → Second reminder (deprecated)
nudge_3                 → Third reminder (deprecated)
nudge_4                 → Final reminder (deprecated)
dropout_recovery        → Dropout recovery email
custom                  → Custom/manual email
```

#### **System Types**
```
welcome_after_confirmation  → Post-confirmation welcome
wave_confirmation           → Wave completion confirmation
weekly_t1_reminder          → Weekly T1 reminder
```

---

## 🔍 Debugging & Monitoring

### Log Patterns

**Successful Email:**
```
[EIPSI Email] log_email called - type: 'wave_availability_T3', status: 'sent', participant: 154
[EIPSI Email] SMTP send successful to: user@example.com (log_id=1026)
[EIPSI WaveEmail] ✓ Enviado p=154 w=179 (log_id=1026)
```

**Empty Email Type Warning:**
```
[EIPSI Email] WARNING: email_type was empty, defaulting to 'custom'. 
Called from: send_wave_reminder_email in class-email-service.php
```

**Wave Availability Check:**
```
[EIPSI WaveEmail] Verificando p=154 w=179 | disponible=SÍ | enviado=NO | reintentos=0/3
[EIPSI WaveEmail] Enviando email (intento #1)
[EIPSI WaveEmail] Magic link generado para p=154
[EIPSI WaveEmail] Nudge 0 marcado como enviado. Log ID: 1026
```

**Database Schema Check:**
```
[EIPSI Migration v2.6.1] Changed email_type from ENUM to VARCHAR(100) in wp_survey_email_log
```

---

### SQL Queries for Debugging

**Check email_type column type:**
```sql
SHOW COLUMNS FROM wp_survey_email_log LIKE 'email_type';
```

**Find emails with empty type:**
```sql
SELECT id, participant_id, email_type, status, sent_at, metadata
FROM wp_survey_email_log
WHERE email_type = '' OR email_type IS NULL
ORDER BY sent_at DESC
LIMIT 50;
```

**Check wave-specific email types:**
```sql
SELECT email_type, COUNT(*) as count
FROM wp_survey_email_log
WHERE email_type LIKE '%_T%'
GROUP BY email_type
ORDER BY count DESC;
```

**Verify metadata structure:**
```sql
SELECT id, email_type, metadata
FROM wp_survey_email_log
WHERE email_type LIKE 'wave_availability%'
ORDER BY id DESC
LIMIT 10;
```

---

## 🎨 Frontend Display Components

### Admin Email Log Tab
**File:** `admin/tabs/email-log-tab.php`

**Features:**
- Email statistics cards (sent, failed, total)
- Trend analysis (7-day comparison)
- Common error types display
- Filterable email log table
- Export to CSV functionality

**Statistics Display:**
```php
// Lines 26-53: Calculate stats
$sent_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_survey_email_log WHERE status = 'sent'");
$failed_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_survey_email_log WHERE status = 'failed'");
$success_rate = round(($sent_count / $total_emails) * 100, 1);
```

---

### Study Dashboard Email Section
**File:** `admin/js/study-dashboard.js`

**Email Log Table:**
- Participant name
- Email type (with icon)
- Sent date (WordPress timezone)
- Status badge
- Resend button (for failed emails)

**Resend Functionality:**
```javascript
// Lines 2830-2849
function resendEmail(participantId, emailType, waveId) {
    fetch(ajaxurl, {
        method: 'POST',
        body: new URLSearchParams({
            action: 'eipsi_resend_participant_email',
            participant_id: participantId,
            email_type: emailType,
            wave_id: waveId,
            nonce: eipsiStudyDashboard.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Email reenviado correctamente', 'success');
            refreshEmailLogs();
        }
    });
}
```

---

## 🔐 Security & Privacy

### Email Address Handling
- **Sanitization:** `sanitize_email()` on all email inputs
- **Validation:** `is_email()` before sending
- **Storage:** Encrypted in database (if encryption enabled)

### Magic Link Security
- **Token Generation:** `bin2hex(random_bytes(32))` - cryptographically secure
- **Token Hashing:** `wp_hash()` for database storage
- **Expiration:** 48 hours default (configurable)
- **One-time Use:** Token invalidated after first use
- **Participant Binding:** Token tied to specific participant_id

### Double Opt-In
- **Confirmation Required:** Participants must confirm email before activation
- **Token Expiry:** 48 hours (configurable via `EIPSI_CONFIRMATION_TOKEN_EXPIRY_HOURS`)
- **Cleanup:** Automatic deletion of expired tokens and unconfirmed participants
- **Retention:** 72 hours for unconfirmed participants (configurable)

---

## 📊 Performance Considerations

### Email Sending
- **Batch Processing:** Cron jobs process emails in batches (5 per execution)
- **Rate Limiting:** Configurable delays between sends
- **Retry Logic:** 3 attempts with 5-minute cooldown
- **Timeout Protection:** 25-second max execution time for cron jobs

### Database Optimization
- **Indexes:** email_type, participant_id, survey_id, status, sent_at
- **Partitioning:** Consider partitioning by sent_at for large datasets
- **Cleanup:** Archive old logs (>90 days) to separate table

### Caching
- **Transients:** Retry status cached for 24 hours
- **Nudge Tracking:** Sent status cached for 7 days
- **Magic Links:** Token cache for quick validation

---

## 🧪 Testing Checklist

### Email Sending Tests
- [ ] Welcome email with magic link
- [ ] Email confirmation (double opt-in)
- [ ] Wave availability (Nudge 0) for T1, T2, T3
- [ ] Progressive nudges (1-4) for each wave
- [ ] Wave completion confirmation
- [ ] Dropout recovery email
- [ ] Manual admin-triggered emails

### Email Type Verification
- [ ] Verify `email_type` is not empty in logs
- [ ] Confirm wave-specific types: `wave_availability_T1`, `nudge_1_T2`, etc.
- [ ] Check metadata contains: wave_id, wave_index, nudge_stage

### Timezone Tests
- [ ] Email log timestamps show WordPress timezone
- [ ] Participant dashboard shows local times
- [ ] Email content shows correct local times

### Duplicate Prevention
- [ ] Nudge 0 not sent twice for same wave
- [ ] Nudges respect cooldown period
- [ ] Retry logic doesn't create duplicates

### Error Handling
- [ ] Failed emails logged with error message
- [ ] Retry functionality works
- [ ] Bulk retry processes correctly
- [ ] Failed email alerts display

---

## 📝 Configuration Options

### WordPress Options
```php
// Investigator info
get_option('eipsi_investigator_name', 'Equipo de Investigación')
get_option('eipsi_investigator_email', get_option('admin_email'))

// SMTP settings
get_option('eipsi_smtp_enabled')
get_option('eipsi_smtp_host')
get_option('eipsi_smtp_port')
get_option('eipsi_smtp_encryption')  // 'ssl', 'tls', or ''
get_option('eipsi_smtp_username')
get_option('eipsi_smtp_password')
get_option('eipsi_smtp_from_email')
get_option('eipsi_smtp_from_name')

// Double opt-in
EIPSI_DOUBLE_OPTIN_ENABLED  // true/false
EIPSI_CONFIRMATION_TOKEN_EXPIRY_HOURS  // default: 48
EIPSI_UNCONFIRMED_PARTICIPANT_RETENTION_HOURS  // default: 72
```

---

## 🚀 Future Enhancements

### Planned Features
1. **Email Templates Editor:** Visual editor for customizing email templates
2. **A/B Testing:** Test different email variants for better engagement
3. **Personalization:** Dynamic content based on participant attributes
4. **Scheduling:** Schedule emails for specific times/days
5. **Unsubscribe Management:** Allow participants to opt-out of specific email types
6. **Email Analytics:** Open rates, click rates, conversion tracking
7. **Webhook Integration:** Trigger external services on email events

### Performance Improvements
1. **Queue System:** Redis/database queue for high-volume sending
2. **Async Processing:** Background jobs for email generation
3. **CDN Integration:** Host email assets on CDN
4. **Template Caching:** Pre-compile email templates

---

## 📚 Related Documentation

- **T1-Anchor System:** See `PHASE-2-T1-ANCHOR-ROADMAP.md`
- **Wave Management:** See `admin/waves-manager-api.php`
- **Cron Jobs:** See `admin/cron-reminders-handler.php`
- **Database Schema:** See `admin/database-schema-manager.php`
- **Email Templates:** See `includes/emails/`

---

## 🆘 Support & Troubleshooting

### Common Issues

**1. Emails not sending**
- Check SMTP configuration
- Verify `wp_mail()` is working
- Check error logs for SMTP errors
- Verify participant email is valid

**2. Empty email_type in logs**
- Run database migration: `migrate_email_type_to_varchar()`
- Check column type: `SHOW COLUMNS FROM wp_survey_email_log LIKE 'email_type'`
- Should be `VARCHAR(100)`, not `ENUM`

**3. Duplicate emails**
- Check transient cache: `eipsi_wave_email_retries_{participant_id}_{wave_id}`
- Verify email log metadata
- Check assignment.reminder_count

**4. Timezone issues**
- Verify WordPress timezone setting
- Check `wp_date()` usage in code
- Confirm `get_date_from_gmt()` conversions

---

## 📞 Contact

**Plugin:** EIPSI Forms v2.6.1  
**Author:** EIPSI Research Team  
**Support:** Check error logs at `/wp-content/debug.log`  
**Documentation:** This file + inline code comments

---

**Last Updated:** May 10, 2026  
**Document Version:** 1.0  
**Status:** ✅ Complete and ready for Claude AI analysis
