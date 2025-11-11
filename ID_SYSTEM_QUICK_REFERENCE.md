# ID System Quick Reference

## Form ID Format
```
{INITIALS}-{HASH_6}
```

### Examples
- "Evaluación Bienestar" → **EIB-x9y8z7**
- "Ansiedad Social" → **AS-a1b2c3**
- "Depresión" → **DEP-m8n9o0**

### Characteristics
- ✅ Stable (same form = same ID)
- ✅ Readable (includes initials)
- ✅ Compact (9-10 chars)
- ✅ Unique per form

## Participant ID Format

### Identified (with email/name)
```
FP-{HASH_8}
```
Example: **FP-a1b2c3d4**

### Anonymous (no email/name)
```
FP-SESS-{HASH_6}
```
Example: **FP-SESS-x9y8z7**

### When to Use
| Use Case | ID Type | Stability |
|----------|---------|-----------|
| Longitudinal studies | `FP-xxxxxxxx` | Stable across sessions |
| Clinical trials | `FP-xxxxxxxx` | Stable forever |
| Anonymous surveys | `FP-SESS-xxxxxx` | Per session only |
| Exploratory research | `FP-SESS-xxxxxx` | Not for tracking |

## Excel Export Format

```
Form ID | Participant ID | Form Name | Date | Time | Duration(s) | IP Address | Device | Browser | OS | [Questions...]
--------|----------------|-----------|------|------|-------------|------------|--------|---------|----|--------------
EIB-x9y8z7 | FP-a1b2c3d4 | Evaluación Bienestar | 2025-11-10 | 21:40:00 | 45.123 | 192.168.1.100 | desktop | Chrome | Windows | ...
```

### Field Descriptions
- **Form ID**: Stable form identifier
- **Participant ID**: Stable participant identifier (FP or FP-SESS)
- **Form Name**: Original form name
- **Date**: YYYY-MM-DD format
- **Time**: HH:MM:SS format
- **Duration(s)**: Seconds with milliseconds (e.g., 45.123)
- **IP Address**: Participant IP
- **Device**: desktop, mobile, tablet
- **Browser**: Chrome, Firefox, Safari, etc.
- **OS**: Windows, macOS, iOS, Android, Linux

## Quick Implementation Check

### ✅ Checklist
- [ ] Database columns added (form_id, participant_id, submitted_at, duration_seconds)
- [ ] Start time captured in JavaScript (Date.now() at init)
- [ ] End time captured in JavaScript (Date.now() at submit)
- [ ] ID generation functions in ajax-handlers.php
- [ ] ID generation functions in export.php
- [ ] Excel export updated with new format
- [ ] CSV export updated with new format
- [ ] External database support updated
- [ ] Internal fields excluded from exports

### ⚠️ Important Notes
1. **FP-SESS IDs are NOT stable** - they change with each session
2. **Duration includes milliseconds** - 3 decimal places
3. **Date and Time are separate columns** - easier for analysis
4. **Internal fields are excluded** - action, nonce, timestamps not in question columns
5. **IDs are generated automatically** - no researcher intervention needed

## Researcher Quick Start

### Identifying Participants
```javascript
// Same participant across multiple sessions
FP-a1b2c3d4 (same email/name) = same person ✅

// Different participants or different sessions
FP-SESS-x9y8z7 (session 1) ≠ FP-SESS-a1b2c3 (session 2) ⚠️
```

### Analyzing Data
```excel
// Count submissions per participant
=COUNTIF(B:B, "FP-a1b2c3d4")

// Average duration for a form
=AVERAGEIF(A:A, "EIB-x9y8z7", F:F)

// Filter by date range
=FILTER(A:Z, (D:D >= "2025-01-01") * (D:D <= "2025-12-31"))
```

## Technical Implementation

### Files Modified
1. `vas-dinamico-forms.php` - Database schema
2. `assets/js/eipsi-forms.js` - Timing capture
3. `admin/ajax-handlers.php` - ID generation + saving
4. `admin/export.php` - Export formatting
5. `admin/database.php` - External DB support

### Key Functions

**Backend (ajax-handlers.php)**
- `generate_stable_form_id($form_name)` - Generate Form ID
- `get_form_initials($form_name)` - Extract initials
- `generateStableFingerprint($user_data)` - Generate Participant ID
- `normalizeName($name)` - Normalize name for fingerprint

**Export (export.php)**
- `export_generate_stable_form_id($form_name)` - Same as backend
- `export_generateStableFingerprint($user_data)` - Same as backend

### Database Schema
```sql
ALTER TABLE wp_vas_form_results ADD COLUMN form_id VARCHAR(20);
ALTER TABLE wp_vas_form_results ADD COLUMN participant_id VARCHAR(20);
ALTER TABLE wp_vas_form_results ADD COLUMN submitted_at DATETIME;
ALTER TABLE wp_vas_form_results ADD COLUMN duration_seconds DECIMAL(8,3);

CREATE INDEX idx_form_id ON wp_vas_form_results(form_id);
CREATE INDEX idx_participant_id ON wp_vas_form_results(participant_id);
CREATE INDEX idx_submitted_at ON wp_vas_form_results(submitted_at);
CREATE INDEX idx_form_participant ON wp_vas_form_results(form_id, participant_id);
```

## Common Issues & Solutions

### Issue: Legacy data without IDs
**Solution**: IDs generated during export from form_name and response data

### Issue: Anonymous participant tracking
**Solution**: Use FP-SESS IDs only for single-session analysis, not longitudinal

### Issue: Same participant, different IDs
**Solution**: Ensure email/name fields are consistently named and formatted

### Issue: Duration showing as integers
**Solution**: Use duration_seconds column (DECIMAL) not duration (INT)

### Issue: Internal fields appearing in export
**Solution**: They're filtered in export functions (not visible in Excel/CSV)

## Deployment Checklist

- [ ] Backup database before activation
- [ ] Deactivate plugin
- [ ] Re-activate plugin (triggers schema update)
- [ ] Test new submission (verify all fields populated)
- [ ] Export test data (verify format)
- [ ] Test identified participant (with email/name)
- [ ] Test anonymous participant (without email/name)
- [ ] Verify duration precision (3 decimals)
- [ ] Verify date/time formatting
- [ ] Test external database (if configured)

## Support

For issues or questions, refer to:
- Full documentation: `EXCEL_EXPORT_ID_SYSTEM_IMPLEMENTATION.md`
- Codebase: `/admin/`, `/assets/js/`
- Database: `wp_vas_form_results` table
