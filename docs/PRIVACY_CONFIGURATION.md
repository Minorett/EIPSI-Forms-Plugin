# 🔒 Privacy Configuration & Metadata System

## Overview

EIPSI Forms implements a comprehensive yet privacy-conscious metadata system for clinical research. This document explains each metadata field, configuration options, and data retention policies.

---

## 📊 Metadata Structure

### Complete JSON Example

```json
{
    "form_id": "ANC-a3f1b2",
    "participant_id": "p-a1b2c3d4e5f6",
    "session_id": "sess-1705764645000-xyz123",
    
    "timestamps": {
        "start": 1705764645123,
        "end": 1705764890456,
        "duration_seconds": 245
    },
    
    "device_info": {
        "device_type": "mobile"
    },
    
    "network_info": {
        "ip_address": "190.194.12.34",
        "ip_storage_type": "plain_text"
    },
    
    "clinical_insights": {
        "therapeutic_engagement": 0.85,
        "clinical_consistency": 0.92,
        "avoidance_patterns": []
    },
    
    "quality_metrics": {
        "quality_flag": "HIGH",
        "completion_rate": 1.0
    }
}
```

---

## 🔐 Security & Identity Fields (REQUIRED)

### Form ID
- **Format:** `ACA-a3f1b2` (3-letter prefix + 6-char hash)
- **Generation:** First 3 letters of each word (max 3 total) + MD5 hash
- **Purpose:** Unique identifier for form template
- **Configurable:** ❌ No (Always captured)
- **Examples:**
  - "Anxiety Clinical Assessment" → `ACA-a3f1b2`
  - "Depression Screening" → `DEP-b2c3d4`
  - "PHQ-9 Questionnaire" → `PHQ-c3d4e5`

### Participant ID
- **Format:** `p-a1b2c3d4e5f6` (p- prefix + 12 alphanumeric)
- **Generation:** UUID v4 truncated to 12 characters
- **Storage:** localStorage (persistent across sessions)
- **Purpose:** Anonymous tracking across multiple forms
- **Anonymity:** ✅ Completely anonymous (no PII)
- **Configurable:** ❌ No (Always captured)

### Session ID
- **Format:** `sess-1705764645000-xyz123`
- **Generation:** Timestamp + random 6-char string
- **Purpose:** Unique identifier for each form submission
- **Configurable:** ❌ No (Always captured)

---

## ⏱️ Timestamps (REQUIRED)

### Fields
- `start`: Unix timestamp in milliseconds (form load)
- `end`: Unix timestamp in milliseconds (form submit)
- `duration_seconds`: Calculated time to complete

### Purpose
- Clinical engagement analysis
- Response speed assessment
- Data quality indicators

### Configurable
❌ No (Always captured for research validity)

---

## 📋 Traceability Fields

### Device Type
- **Values:** `mobile`, `desktop`, `tablet`
- **Purpose:** Context of administration
- **Clinical Value:** Mobile responses may indicate informal settings
- **Configurable:** ✅ Yes (Recommended: ON)

### IP Address ⚠️
- **Format:** IPv4 or IPv6 (e.g., `190.194.12.34`)
- **Storage:** Plain text (NOT anonymized)
- **Retention:** 90 days
- **Purpose:** Clinical audit trail, fraud detection
- **Proxy Detection:** ✅ Supports Cloudflare, X-Forwarded-For
- **Configurable:** ❌ No (REQUIRED by clinical team)

**Why IP is Required:**
1. **Clinical Audit:** Verifying response authenticity
2. **Fraud Detection:** Identifying duplicate/spam submissions
3. **Research Ethics:** Maintaining data provenance
4. **Compliance:** Meeting institutional research requirements

---

## 🎯 Clinical Behavioral Metrics (RECOMMENDED)

### Therapeutic Engagement
- **Type:** Score (0.0 - 1.0)
- **Calculation:** `avg_time_per_field / 60` (capped at 1.0)
- **Interpretation:**
  - `< 0.3`: Low engagement (rushed responses)
  - `0.3 - 0.8`: Normal engagement
  - `> 0.8`: High engagement (thoughtful responses)
- **Clinical Value:** Indicates participant investment in assessment
- **Configurable:** ✅ Yes (Recommended: ON)

### Clinical Consistency
- **Type:** Score (0.0 - 1.0)
- **Calculation:** Detects logical inconsistencies in responses
- **Example:** Reporting "never anxious" but high anxiety scale score
- **Clinical Value:** Data quality indicator, potential response sets
- **Configurable:** ✅ Yes (Recommended: ON)
- **Current Status:** Placeholder (returns 1.0) - future implementation

### Avoidance Patterns
- **Type:** Array of detected patterns
- **Detection:** Identifies skipping, backtracking, omissions
- **Clinical Value:** May indicate emotional avoidance or distress
- **Configurable:** ✅ Yes (Recommended: ON)
- **Current Status:** Placeholder (returns []) - future implementation

---

## ✅ Quality Metrics (REQUIRED)

### Quality Flag
- **Values:** `HIGH`, `NORMAL`, `LOW`
- **Calculation:** Combined score of engagement + consistency
  - `HIGH`: avg_score ≥ 0.8
  - `NORMAL`: avg_score ≥ 0.5
  - `LOW`: avg_score < 0.5
- **Purpose:** Quick filtering for data analysis
- **Configurable:** ❌ No (Always calculated)

### Completion Rate
- **Type:** Decimal (0.0 - 1.0)
- **Calculation:** Answered fields / total fields
- **Purpose:** Data completeness indicator
- **Configurable:** ❌ No (Always calculated)

---

## 🚫 Excluded Fields (By Design)

These fields are **intentionally excluded** for privacy and scope:

### Not Captured
- ❌ Screen resolution
- ❌ Browser/OS details
- ❌ Full User-Agent string
- ❌ IP geolocation
- ❌ Connection type (WiFi/Cellular)
- ❌ Mouse movement tracking
- ❌ Keystroke dynamics

### Out of Scope
- ❌ Mood tracking
- ❌ Research consent fields
- ❌ Demographic data (unless explicit form fields)

---

## 🔧 Configuration via Privacy Dashboard

### Accessing Dashboard
1. Navigate to WordPress Admin
2. Go to "EIPSI Forms" → "Privacy Settings"
3. Select form ID to configure

### Configurable Options

#### ✅ Can Enable/Disable
- Therapeutic Engagement
- Clinical Consistency
- Avoidance Patterns
- Device Type

#### ❌ Cannot Disable (Required)
- Form ID
- Participant ID
- Session ID
- Quality Flag
- IP Address
- Timestamps

### Saving Configuration
Configuration is **per-form** and stored in WordPress options:
- Option key: `eipsi_privacy_config_{form_id}`
- Example: `eipsi_privacy_config_ACA-a3f1b2`

---

## 📁 Database Schema

### Table: `wp_vas_form_results`

```sql
CREATE TABLE wp_vas_form_results (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    form_id varchar(20) DEFAULT NULL,
    participant_id varchar(255) DEFAULT NULL,
    session_id varchar(255) DEFAULT NULL,
    
    form_name varchar(255) NOT NULL,
    created_at datetime NOT NULL,
    submitted_at datetime DEFAULT NULL,
    
    device varchar(100) DEFAULT NULL,
    browser varchar(100) DEFAULT NULL,
    os varchar(100) DEFAULT NULL,
    screen_width int(11) DEFAULT NULL,
    
    duration int(11) DEFAULT NULL,
    duration_seconds decimal(8,3) DEFAULT NULL,
    start_timestamp_ms bigint(20) DEFAULT NULL,
    end_timestamp_ms bigint(20) DEFAULT NULL,
    
    ip_address varchar(45) DEFAULT NULL,
    metadata LONGTEXT DEFAULT NULL,
    
    quality_flag enum('HIGH','NORMAL','LOW') DEFAULT 'NORMAL',
    status enum('pending','submitted','error') DEFAULT 'submitted',
    
    form_responses LONGTEXT NOT NULL,
    
    PRIMARY KEY (id),
    KEY form_id (form_id),
    KEY participant_id (participant_id),
    KEY session_id (session_id),
    KEY ip_address (ip_address),
    KEY submitted_at (submitted_at)
);
```

### Key Columns
- `metadata`: JSON string with structured metadata
- `ip_address`: Dedicated column for easy querying
- `quality_flag`: Indexed for fast filtering
- `session_id`: Tracks individual submissions

---

## 🔒 GDPR & Privacy Compliance

### Data Minimization
✅ Only clinically relevant metadata captured
✅ No unnecessary tracking (no mouse movements, keystroke timing)
✅ Configurable behavioral metrics

### Anonymity
✅ Participant ID is completely anonymous UUID
✅ No PII in metadata unless explicit form fields
✅ Session-based tracking (no cookies)

### Right to Be Forgotten
✅ All data tied to `participant_id` for easy deletion
✅ IP addresses stored separately for 90-day retention
✅ Metadata can be selectively purged

### Transparency
✅ Privacy Dashboard shows exactly what's captured
✅ Clear labeling of required vs. optional fields
✅ Documentation available to participants

---

## 📊 Export Formats

### CSV Export
- Includes all metadata fields as separate columns
- IP address included in dedicated column
- Quality metrics pre-calculated
- Compatible with SPSS, R, Python

### Excel Export
- Formatted metadata in readable structure
- Color-coded quality flags
- Timestamp conversions to readable dates
- Suitable for manual review

---

## 🔍 Example Use Cases

### Research Quality Control
```sql
-- Find high-quality responses from desktop
SELECT * FROM wp_vas_form_results 
WHERE quality_flag = 'HIGH' 
AND device = 'desktop'
AND JSON_EXTRACT(metadata, '$.clinical_insights.therapeutic_engagement') > 0.7;
```

### Fraud Detection
```sql
-- Find multiple submissions from same IP
SELECT ip_address, COUNT(*) as submission_count
FROM wp_vas_form_results
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY ip_address
HAVING submission_count > 5;
```

### Participant Journey
```sql
-- Track participant across multiple forms
SELECT form_id, created_at, quality_flag
FROM wp_vas_form_results
WHERE participant_id = 'p-a1b2c3d4e5f6'
ORDER BY created_at;
```

---

## ⚙️ Technical Implementation

### Frontend (JavaScript)
- `getUniversalParticipantId()`: Generates/retrieves participant ID
- `getSessionId()`: Generates unique session ID
- LocalStorage key: `eipsi_participant_id`

### Backend (PHP)
- `get_privacy_config($form_id)`: Retrieves configuration
- `eipsi_calculate_engagement_score()`: Calculates engagement
- `eipsi_calculate_quality_flag()`: Determines quality level

### AJAX Handler
- Endpoint: `eipsi_save_privacy_config`
- Nonce: `eipsi_privacy_nonce`
- Permissions: `manage_options`

---

## 🛡️ Security Best Practices

### IP Storage
✅ Validated with `FILTER_VALIDATE_IP`
✅ Proxy-aware (Cloudflare, X-Forwarded-For)
✅ Stored in separate column for auditing
✅ Retained for 90 days (configurable)

### Data Validation
✅ All inputs sanitized (`sanitize_text_field`)
✅ JSON metadata validated before storage
✅ Type checking on numeric fields
✅ SQL prepared statements for external DB

### Access Control
✅ Privacy config requires `manage_options` capability
✅ AJAX endpoints use nonce verification
✅ No public API for metadata retrieval

---

## 📞 Support & Feedback

For questions about privacy configuration or metadata fields:
- Review this documentation
- Check Privacy Dashboard tooltips
- Contact development team for custom requirements

**Last Updated:** November 2025
**Version:** Phase 14 Implementation
