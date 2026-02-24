# Phase 2 - Minimum Viable Participant Login System - Implementation Summary

## Status: ✅ CORE IMPLEMENTATION COMPLETE

---

## ✅ Tasks Completed

### 2A - Authentication Foundation

| Task | Status | Notes |
|------|--------|-------|
| **Participant Registration Form** | ✅ | Form with email, password (min 8 chars), first_name, last_name, study_code |
| **Study Code Field** | ✅ | Links participant to correct study without exposing numeric IDs |
| **Email Already Registered → Login Prompt** | ✅ | Shows "Already registered? Login here" message with link |
| **Password Login with Session Cookie** | ✅ | 7-day TTL default, 30-day with "remember me" |
| **Account Recovery via Magic Link** | ✅ | "Forgot password" sends magic link, reuses existing infrastructure |
| **Rate Limiting** | ✅ | 5 failed attempts per 15 minutes per email |
| **CSRF Protection** | ✅ | `wp_nonce_field()` on every participant form |

### 2B - Participant Portal

| Task | Status | Notes |
|------|--------|-------|
| **Study Dashboard** | ✅ | Shows study name, current wave, due date, completion status |
| **Continue Study Button** | ✅ | Single clear CTA to current wave form |
| **Session Expiry Handling** | ✅ | Redirects gracefully to login, preserves wave state |
| **No Admin UI Ever** | ✅ | Guard clauses prevent rendering admin components |

### 2C - Access Logging (Compliance)

| Task | Status | Notes |
|------|--------|-------|
| **wp_survey_participant_access_log Table** | ✅ | Created with all required columns |
| **Access Logging** | ✅ | Logs registration, login, login_failed, magic_link_clicked, wave_started, wave_completed, logout |
| **Retention Policy** | ⏳ | Cron job needs to be scheduled (service method ready) |

---

## Files Created

### New Services
- `admin/services/class-participant-access-log-service.php` - Access logging and rate limiting
- `admin/services/class-participant-auth-handler.php` - AJAX handlers for login/registration/magic link

### New Assets
- `assets/js/participant-portal.js` - Frontend authentication handling
- `assets/css/participant-portal.css` - Portal styling

### Modified Files
- `admin/database-schema-manager.php` - Added `wp_survey_participant_access_log` table
- `includes/templates/survey-login-form.php` - Added study_code field and CSRF nonces
- `eipsi-forms.php` - Added new service includes

---

## Database Schema

### New Table: wp_survey_participant_access_log

```sql
CREATE TABLE wp_survey_participant_access_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    participant_id BIGINT(20) UNSIGNED NOT NULL,
    study_id INT(11) NOT NULL,
    action_type ENUM('registration', 'login', 'login_failed', 'magic_link_clicked', 'magic_link_sent', 'wave_started', 'wave_completed', 'logout', 'session_expired', 'password_reset_requested', 'password_reset_completed') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500),
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_participant_id (participant_id),
    INDEX idx_study_id (study_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_participant_action (participant_id, action_type),
    INDEX idx_study_created (study_id, created_at)
);
```

---

## Security Features Implemented

1. **CSRF Protection**: Nonce verification on all forms
2. **Rate Limiting**: 5 attempts per 15 minutes per email
3. **Password Hashing**: Uses WordPress `wp_hash_password()` and `wp_check_password()`
4. **Session Security**: HTTP-only cookies, SameSite=Lax
5. **Input Sanitization**: All inputs sanitized with WordPress functions
6. **IP Logging**: Client IP captured for audit trails

---

## API Endpoints (AJAX)

| Action | Method | Description |
|--------|--------|-------------|
| `eipsi_participant_login` | POST | Login with email/password |
| `eipsi_participant_register` | POST | Register new participant |
| `eipsi_participant_magic_link` | POST | Request magic link (login/reset) |
| `eipsi_participant_logout` | POST | Destroy session |
| `eipsi_participant_check_session` | POST | Check session validity |

---

## Remaining Tasks

### Priority 1 - Cron Job Setup
```php
// Schedule daily purge of old access logs
add_action('wp', function() {
    if (!wp_next_scheduled('eipsi_purge_access_logs')) {
        wp_schedule_event(time(), 'daily', 'eipsi_purge_access_logs');
    }
});

add_action('eipsi_purge_access_logs', function() {
    $retention_days = get_option('eipsi_access_log_retention_days', 365);
    EIPSI_Participant_Access_Log_Service::purge_old_logs($retention_days);
});
```

### Priority 2 - Admin Settings
- Add retention_days setting to EIPSI configuration
- Add access log viewer in admin panel

### Priority 3 - Session Expiry UI
- Add countdown timer when session near expiry
- Add "extend session" functionality

---

## Testing Checklist

- [ ] Register with valid study_code
- [ ] Register with invalid study_code (should fail)
- [ ] Register with existing email (should show login prompt)
- [ ] Login with correct credentials
- [ ] Login with incorrect password (should increment rate limit)
- [ ] Rate limit after 5 failed attempts
- [ ] Request magic link
- [ ] Use magic link to login
- [ ] Logout
- [ ] Access dashboard after login
- [ ] Verify access logs are recorded
- [ ] Test session expiry and redirect

---

## Version History

- **v2.0.0** - Phase 2 implementation complete
  - New: Participant access logging
  - New: Rate limiting for login attempts
  - New: CSRF protection on all forms
  - New: Study code field in registration
  - New: Email exists → login prompt
  - New: Session expiry handling
  - Improved: Magic link for password reset
