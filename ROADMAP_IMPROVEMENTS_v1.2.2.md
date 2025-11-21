# EIPSI Forms - Improvement Roadmap v1.2.2

**Generated:** January 2025  
**Based on:** Comprehensive README analysis + industry competitor comparison  
**Purpose:** Strategic roadmap for positioning EIPSI Forms as REDCap-lite alternative

---

## Executive Summary

**Current Position:** EIPSI Forms is a **solid clinical research form plugin** with excellent accessibility (WCAG 2.1 AA), privacy controls (GDPR compliant), and basic conditional logic.

**Gap Analysis:** Compared to industry leaders (Qualtrics, REDCap, Gravity Forms), EIPSI lacks:
- Visual progress indicators
- Save & resume functionality
- Advanced analytics UI
- Field-level encryption
- Multi-language support
- Form versioning audit trails

**Strategic Opportunity:** Adding **Phase 1 & 2 features** (8-10 weeks) would position EIPSI as a **REDCap-lite alternative** for clinical researchers seeking open-source, self-hosted, WCAG-compliant solutions.

---

## Current State - What EIPSI Has ✅

### Core Features (Production Ready)
- ✅ **Multi-page forms** with pagination
- ✅ **11 Gutenberg blocks** (VAS Slider, Likert, Radio, Multiple, Select, Text, Textarea, Description, Form Container, Form Block, Página)
- ✅ **Conditional/Skip Logic** (show/hide fields, jump to page, IF-THEN-ELSE)
- ✅ **5 Professional presets** (Clinical Blue, Minimal White, Warm Neutral, Serene Teal, Dark EIPSI)
- ✅ **WCAG 2.1 AA compliant** (44×44px touch targets, 4.5:1+ contrast ratios)
- ✅ **Metadata capture** (timestamps, duration, device, browser, OS, IP, therapeutic engagement, clinical consistency, avoidance patterns)
- ✅ **Privacy controls** (GDPR compliant, configurable metadata toggles)
- ✅ **Excel/CSV export** (dynamic column expansion, UTF-8 with BOM)
- ✅ **External database support** (MySQL/MariaDB with auto-schema repair)
- ✅ **Tracking system** (6 event types: view, start, page_change, submit, abandon, branch_jump)
- ✅ **Form ID system** (auto-generated, stable, reproducible)
- ✅ **Participant ID** (UUID v4, persists in localStorage for longitudinal tracking)
- ✅ **Session ID** (unique per attempt, abandonment tracking)
- ✅ **Admin panel** (3 tabs: Submissions, Completion Message, Privacy & Metadata)
- ✅ **Security** (nonce verification, XSS protection, sanitization, credential encryption)
- ✅ **Stress test suite** (30-minute automated tests, 5 test categories)
- ✅ **Quality flag system** (HIGH/NORMAL/LOW based on completion patterns)

### Documentation (Comprehensive)
- ✅ Installation guide
- ✅ Block reference
- ✅ Privacy configuration
- ✅ Database schema sync (500+ lines)
- ✅ Conditional logic guide
- ✅ Theme presets documentation
- ✅ QA reports (Phases 5-9, 3000+ lines)
- ✅ Stress test guide (600+ lines)

---

## Missing vs Industry Standards

### Comparison Matrix: EIPSI vs Competitors

| Feature | EIPSI v1.2.2 | Qualtrics | REDCap | Gravity Forms |
|---------|--------------|-----------|--------|---------------|
| **Core Features** |
| Multi-page forms | ✅ | ✅ | ✅ | ✅ |
| Conditional Logic | ✅ | ✅ | ✅ | ✅ |
| Dark Mode | ✅ | ✅ | ❌ | ✅ |
| WCAG 2.1 AA | ✅ | ✅ | ✅ | ⚠️ Partial |
| **Research Features** |
| Progress Bar (visual) | ⏳ Text only | ✅ | ✅ | ✅ |
| Save & Continue | ❌ | ✅ | ✅ | ✅ |
| Auto-Scoring UI | ⏳ Quality flag only | ✅ | ✅ | ✅ |
| Form Versioning | ❌ | ✅ | ✅ Gold Standard | ⚠️ Basic |
| Time Limits | ❌ | ✅ | ✅ | ❌ |
| Matrix Questions | ❌ | ✅ | ✅ | ✅ |
| **Analytics** |
| Tracking System | ✅ Backend only | ✅ Full UI | ✅ Full UI | ✅ Full UI |
| Analytics Dashboard | ⏳ In Development | ✅ | ✅ | ✅ |
| Response Distribution | ❌ | ✅ | ✅ | ✅ |
| Real-time Monitoring | ❌ | ✅ | ✅ | ⚠️ Paid |
| **Export & Integration** |
| Excel/CSV Export | ✅ | ✅ | ✅ | ✅ |
| PDF Export | ❌ | ✅ | ✅ | ✅ |
| SPSS Format | ⏳ CSV only | ✅ | ✅ | ❌ |
| API REST | ❌ | ✅ | ✅ | ✅ |
| Webhooks | ❌ | ✅ | ❌ | ✅ |
| **Security & Compliance** |
| GDPR Compliant | ✅ | ✅ | ✅ | ⚠️ Partial |
| HIPAA Ready | ⚠️ Prepared | ✅ | ✅ Gold Standard | ⚠️ Partial |
| Field Encryption | ❌ | ✅ | ✅ | ❌ |
| Audit Logs | ⏳ Tracking only | ✅ Full UI | ✅ Full UI | ⚠️ Paid |
| 2FA for Admin | ❌ | ✅ | ✅ | ⚠️ Paid |
| **UX Features** |
| Multilingual | ❌ | ✅ | ✅ | ✅ |
| Custom CSS | ❌ | ✅ | ⚠️ Limited | ✅ |
| Recaptcha | ❌ | ✅ | ❌ | ✅ |
| Email Notifications | ❌ | ✅ | ✅ | ✅ |
| **Deployment** |
| Open Source | ✅ | ❌ Proprietary | ✅ | ❌ Proprietary |
| Self-Hosted | ✅ | ❌ Cloud only | ✅ | ✅ |
| External DB | ✅ | N/A | ✅ | ❌ |
| Zero Data Loss | ✅ 4-layer protection | ✅ | ✅ | ⚠️ |

**Legend:**
- ✅ = Fully implemented
- ⏳ = Partially implemented or in development
- ❌ = Not available
- ⚠️ = Limited or requires paid version

---

## PHASE 1: Quick Wins (2-3 weeks)

**Priority:** HIGH  
**Goal:** Deliver high-impact UX improvements with low effort

### 1.1 Progress Bar (Visual Indicator)

**Current State:** Text-only indicator ("Página X de Y")  
**Gap:** No visual progress bar like REDCap/Qualtrics  
**Effort:** Low (1-2 days)  
**Impact:** High (UX improvement, reduces abandonment)

**Implementation:**
- Visual progress bar component (horizontal bar with percentage)
- Optional: Estimated time remaining ("~3 minutos restantes")
- Configurable: Show/hide progress bar per form
- Responsive: Scales properly on mobile
- Accessible: ARIA progress role with live region updates

**Benefits:**
- ✅ Reduced form abandonment (participants see progress)
- ✅ Better participant experience (clear expectations)
- ✅ Professional appearance (matches industry standards)
- ✅ Accessibility enhancement (screen reader announcements)

**Technical Details:**
- Add `ProgressBar` component to `FormContainer` block
- Props: `currentPage`, `totalPages`, `showPercentage`, `showTimeEstimate`
- CSS: Responsive, matches preset color schemes
- ARIA: `role="progressbar"`, `aria-valuenow`, `aria-valuemin`, `aria-valuemax`

**Acceptance Criteria:**
- [ ] Visual progress bar renders above/below form pages
- [ ] Percentage calculation accurate (currentPage / totalPages * 100)
- [ ] Optional time estimate based on average page time
- [ ] Configurable in block settings (show/hide, position)
- [ ] Mobile responsive (stacked layout on < 768px)
- [ ] WCAG 2.1 AA compliant (contrast, screen reader support)

---

### 1.2 Time Limits (Timed Assessments)

**Current State:** No time limit functionality  
**Gap:** REDCap/Qualtrics support timed assessments for protocol compliance  
**Effort:** Low (1-2 days)  
**Impact:** High (research protocol requirement)

**Implementation:**
- Configurable time limit per form (minutes/seconds)
- Countdown timer display (optional: show/hide)
- Warning at 2 minutes, 1 minute, 30 seconds remaining
- Auto-submit on timeout (saves current responses)
- Optional: Extend time button (for accessibility accommodations)

**Benefits:**
- ✅ Protocol adherence (timed clinical assessments)
- ✅ Research validity (standardized completion time)
- ✅ Cognitive load assessment (time-to-complete as variable)
- ✅ Accessibility consideration (optional extensions)

**Technical Details:**
- Add `timeLimitMinutes` attribute to `FormContainer` block
- JavaScript timer: `setInterval` with `localStorage` persistence
- Warning modals: 2 min, 1 min, 30 sec remaining
- Auto-submit: Triggers standard form submission on timeout
- Metadata: Capture `timed_out: true`, `time_limit_minutes: X`

**Acceptance Criteria:**
- [ ] Configurable time limit in block settings (0 = disabled)
- [ ] Countdown timer visible (optional toggle)
- [ ] Warnings displayed at configurable intervals
- [ ] Auto-submit on timeout with metadata flag
- [ ] Timer persists across page refreshes (sessionStorage)
- [ ] Optional "Request Extension" button (accessibility)
- [ ] Timer pauses if tab is hidden (visibilitychange API)

---

### 1.3 Form Versioning (Audit Trail)

**Current State:** No versioning system (changes overwrite previous version)  
**Gap:** REDCap gold standard for form versioning, EIPSI has none  
**Effort:** Medium (2-3 days)  
**Impact:** High (compliance requirement, data integrity)

**Implementation:**
- Version number per form (v1.0, v1.1, v1.2, etc.)
- Automatic versioning on publish (major/minor versions)
- Participant responses tied to specific form version
- Admin UI: Version history (who changed what, when)
- Export includes form version column

**Benefits:**
- ✅ GDPR compliance (audit trail requirement)
- ✅ Protocol compliance (track form changes over time)
- ✅ Data integrity (know which version participants completed)
- ✅ Research validity (version-specific analysis)

**Technical Details:**
- Add `form_version` column to `wp_vas_form_results` table (e.g., "1.2")
- Store version in post meta: `_eipsi_form_version`
- Increment version on publish: Gutenberg `save_post` hook
- Admin UI: New tab "Version History" with diff viewer
- Export: Add `Form Version` column

**Acceptance Criteria:**
- [ ] Version number auto-increments on publish
- [ ] Participant responses include form version
- [ ] Admin can view version history (changes, timestamp, user)
- [ ] Export includes form version column
- [ ] Optional: Revert to previous version (admin only)
- [ ] Version comparison UI (show field changes)

---

### 1.4 Analytics Dashboard UI (Phase 1)

**Current State:** Tracking backend fully implemented, UI is "En Desarrollo"  
**Gap:** Qualtrics/REDCap have full analytics dashboards, EIPSI has raw data only  
**Effort:** Medium (3-4 days)  
**Impact:** High (researchers need visual insights)

**Implementation:**
- Basic dashboard: Form-level statistics
- Metrics: Total submissions, completion rate, average time, abandonment rate
- Charts: Submission timeline (chart.js or similar)
- Filters: Date range, form ID
- Accessible: Keyboard navigation, screen reader support

**Benefits:**
- ✅ Real-time monitoring (track data collection progress)
- ✅ Data quality insights (completion rates, average times)
- ✅ Abandonment analysis (identify problem pages)
- ✅ Professional reporting (visual dashboards)

**Technical Details:**
- Add new admin tab: "Analytics" in Results & Experience panel
- Query `wp_vas_form_events` and `wp_vas_form_results` tables
- Display metrics: Total views, starts, completions, abandons
- Calculate: Completion rate (completions / starts * 100)
- Chart: Daily submissions over last 30 days (line chart)

**Acceptance Criteria:**
- [ ] New "Analytics" tab in admin panel
- [ ] Display: Total submissions, completion rate, avg time, abandonment rate
- [ ] Filter by: Form ID, date range (last 7/30/90 days)
- [ ] Line chart: Submissions over time
- [ ] Page-level abandonment breakdown (which pages lose participants)
- [ ] Responsive design (mobile-friendly dashboard)
- [ ] Export analytics report (CSV/PDF)

---

## PHASE 2: Research-Grade Features (4-6 weeks)

**Priority:** CRITICAL  
**Goal:** Match REDCap feature parity for clinical research

### 2.1 Partial Submissions / Save & Continue

**Current State:** Mentioned as "roadmap future", not implemented  
**Gap:** REDCap/Qualtrics allow participants to save progress and resume later  
**Effort:** High (4-5 days)  
**Impact:** Very High (higher completion rates, realistic clinical workflows)

**Implementation:**
- Auto-save progress every 30 seconds (configurable)
- Unique resume token (e.g., `resume-xyz123abc`)
- Email resume link to participant (optional)
- LocalStorage + server-side storage (redundant)
- Resume from any device with token

**Benefits:**
- ✅ Higher completion rates (participants can take breaks)
- ✅ Better participant experience (flexibility for long forms)
- ✅ Real-world scenario support (multi-session assessments)
- ✅ Accessibility enhancement (accommodates cognitive load)

**Technical Details:**
- Add `draft_status` column to `wp_vas_form_results` table
- Status: `draft` | `completed`
- Auto-save: AJAX request every 30 seconds (if form dirty)
- Resume token: UUID stored in `wp_options` (expire after 30 days)
- Resume URL: `?resume_token=xyz123abc`
- UI: "Save Progress" button + "Resume Later" modal

**Acceptance Criteria:**
- [ ] Auto-save every 30 seconds (configurable interval)
- [ ] Manual "Save Progress" button visible
- [ ] Resume token generated and stored
- [ ] Resume URL provided to participant (display + optional email)
- [ ] Resume restores all field values, current page
- [ ] Draft expiration after 30 days (configurable)
- [ ] Admin can view drafts vs. completed submissions
- [ ] GDPR compliance: Participant can delete draft

---

### 2.2 Auto-Scoring UI (Custom Formulas)

**Current State:** Quality flag system exists (HIGH/NORMAL/LOW), but no custom scoring UI  
**Gap:** REDCap allows custom scoring formulas, EIPSI has basic quality flag only  
**Effort:** Medium (3-4 days)  
**Impact:** Very High (psychometric research requirement)

**Implementation:**
- Block-level setting: "Score Value" (assign numeric value to each option)
- Formula builder: Sum, Average, Min, Max, Count
- Calculated fields: Display score to participant (optional)
- Export: Include calculated scores as columns
- Likert aggregation: Sum Likert responses automatically

**Benefits:**
- ✅ Real-time scoring (immediate feedback to participants)
- ✅ Psychometric analysis (automated scale calculations)
- ✅ Reduced manual errors (no post-processing needed)
- ✅ Research validity (standardized scoring protocols)

**Technical Details:**
- Add `scoreValue` attribute to Likert, Radio, Multiple blocks
- New block: "Calculated Field" (displays formula result)
- Formula syntax: `{field_name_1} + {field_name_2}` (simple parser)
- Store scores in `form_responses` JSON (e.g., `total_anxiety_score: 42`)
- Export: Add calculated score columns

**Acceptance Criteria:**
- [ ] Assign score values to each option (Likert, Radio, Multiple)
- [ ] Formula builder UI in block settings
- [ ] Calculated fields display result in real-time (client-side)
- [ ] Scores saved in form_responses JSON
- [ ] Export includes calculated score columns
- [ ] Validation: Prevent division by zero, invalid formulas
- [ ] Optional: Display score to participant (toggle per formula)

---

### 2.3 Matrix / Grid Questions

**Current State:** Not implemented  
**Gap:** Qualtrics/REDCap support matrix questions for compact multi-question formats  
**Effort:** Medium (2-3 days)  
**Impact:** High (common research need, reduces form length)

**Implementation:**
- New block: "EIPSI Matrix"
- Rows: Questions (e.g., "I feel anxious", "I feel sad", "I feel angry")
- Columns: Options (e.g., "Never", "Rarely", "Sometimes", "Often", "Always")
- Cell type: Radio buttons or checkboxes
- Responsive: Stacks on mobile (< 768px)

**Benefits:**
- ✅ Compact format (reduces form length)
- ✅ Common research pattern (Likert scales, rating grids)
- ✅ Better UX (related questions grouped together)
- ✅ Efficient data collection (multiple items per screen)

**Technical Details:**
- New block: `eipsi-matrix`
- Attributes: `rows: []`, `columns: []`, `cellType: 'radio' | 'checkbox'`
- Render: HTML table with proper ARIA labels
- Validation: Ensure all rows answered (if required)
- Export: One column per row (e.g., `matrix_row_1`, `matrix_row_2`)

**Acceptance Criteria:**
- [ ] Matrix block with configurable rows/columns
- [ ] Cell types: Radio (single choice) or Checkbox (multiple)
- [ ] Required validation (all rows must be answered)
- [ ] Responsive: Stacks on mobile (< 768px breakpoint)
- [ ] WCAG 2.1 AA compliant (proper table headers, ARIA labels)
- [ ] Export: Separate columns for each row
- [ ] Conditional logic support (show/hide matrix based on responses)

---

### 2.4 Enhanced Audit Logs (Admin UI)

**Current State:** Tracking data exists in `wp_vas_form_events`, but no admin UI  
**Gap:** REDCap has comprehensive audit log UI, EIPSI has raw SQL queries only  
**Effort:** Low (1-2 days)  
**Impact:** High (compliance requirement, transparency)

**Implementation:**
- New admin page: "Audit Logs" (under EIPSI Forms menu)
- Display: All form events (view, start, page_change, submit, abandon, branch_jump)
- Filters: Form ID, event type, date range, participant ID
- Searchable: Full-text search by session ID, participant ID
- Export: Download audit log as CSV (for compliance)

**Benefits:**
- ✅ GDPR compliance (audit trail requirement)
- ✅ Accountability (track all form interactions)
- ✅ Security monitoring (detect suspicious activity)
- ✅ Research insights (participant behavior analysis)

**Technical Details:**
- Query `wp_vas_form_events` table
- Display: Event type, form ID, session ID, participant ID, timestamp, metadata
- Pagination: 50 events per page
- Filters: Dropdown for event type, date picker for range
- Export: CSV with all columns

**Acceptance Criteria:**
- [ ] New "Audit Logs" admin page (under EIPSI Forms menu)
- [ ] Display all events from wp_vas_form_events table
- [ ] Filters: Form ID, event type, date range, participant ID
- [ ] Search: Full-text search by session ID, participant ID
- [ ] Pagination: 50 events per page
- [ ] Export: Download as CSV (all columns)
- [ ] Responsive: Mobile-friendly table (stacked on < 768px)

---

## PHASE 3: Security & Compliance (3-4 weeks)

**Priority:** IMPORTANT  
**Goal:** Achieve HIPAA compliance certification readiness

### 3.1 Field-Level Encryption (AES-256)

**Current State:** Only credential encryption exists (for external DB), no field encryption  
**Gap:** REDCap supports field-level encryption for sensitive data (SSN, emails, IDs)  
**Effort:** Medium (2-3 days)  
**Impact:** High (HIPAA compliance requirement)

**Implementation:**
- Block-level setting: "Encrypt this field" (checkbox)
- Encryption: AES-256-CBC (same as credential encryption)
- Key storage: WordPress encryption key (or custom key in wp-config.php)
- Decryption: On retrieval in admin panel
- Export: Decrypted values (admin only, with audit log entry)

**Benefits:**
- ✅ HIPAA compliance (encryption of PHI at rest)
- ✅ Extra security layer (even if DB breached, data is encrypted)
- ✅ Data protection (sensitive fields like SSN, email, phone)
- ✅ Configurable per field (only encrypt what's necessary)

**Technical Details:**
- Add `isEncrypted: boolean` attribute to all input blocks
- Encryption: `openssl_encrypt()` with AES-256-CBC cipher
- Store: Encrypted value in `form_responses` JSON with prefix `encrypted:`
- Decryption: `openssl_decrypt()` in admin panel
- Key: Use WordPress `AUTH_KEY` or custom constant `EIPSI_ENCRYPTION_KEY`

**Acceptance Criteria:**
- [ ] "Encrypt this field" checkbox in block settings
- [ ] Encryption on form submission (server-side)
- [ ] Decryption on admin retrieval (view submission)
- [ ] Export: Decrypted values (with audit log entry)
- [ ] Audit log: Track who decrypted which field, when
- [ ] Key rotation: Support key migration (decrypt with old, re-encrypt with new)
- [ ] Documentation: HIPAA compliance guide updated

---

### 3.2 Two-Factor Authentication (2FA) for Admin

**Current State:** No 2FA support (relies on WordPress default authentication)  
**Gap:** REDCap/Qualtrics support 2FA for admin security  
**Effort:** Medium (1-2 days)  
**Impact:** Medium (security best practice, not research-critical)

**Implementation:**
- Integration with existing 2FA plugins (e.g., "Two Factor Authentication")
- Or: Built-in TOTP support (Google Authenticator, Authy)
- Admin setting: Enforce 2FA for all users with "manage_eipsi_forms" capability
- Backup codes: Generate 10 backup codes (in case device lost)

**Benefits:**
- ✅ Admin security (prevent unauthorized access)
- ✅ Breach prevention (compromised password insufficient)
- ✅ Compliance enhancement (security best practice)
- ✅ User trust (participants trust secure systems)

**Technical Details:**
- Check for existing 2FA plugin: "Two Factor", "Google Authenticator", etc.
- If exists: Add capability requirement to enforce 2FA
- If not: Implement TOTP using `phpGangsta/GoogleAuthenticator` library
- Admin UI: QR code setup, backup codes download
- Enforce: Hook into `wp_authenticate` to require 2FA

**Acceptance Criteria:**
- [ ] Admin setting: "Require 2FA for EIPSI Forms admins" (checkbox)
- [ ] Integration with existing 2FA plugins (if installed)
- [ ] Built-in TOTP support (if no 2FA plugin installed)
- [ ] QR code setup for Google Authenticator/Authy
- [ ] Backup codes: Generate and download 10 codes
- [ ] Enforce: Block access to EIPSI Forms admin without 2FA
- [ ] User profile: 2FA setup instructions

---

### 3.3 Audit Log Export & Retention Policy

**Current State:** Audit logs exist, but no export UI or automatic retention policy  
**Gap:** REDCap has configurable retention policies and audit log exports  
**Effort:** Low (1 day)  
**Impact:** High (GDPR/HIPAA compliance requirement)

**Implementation:**
- Export audit logs as CSV/Excel (from "Audit Logs" admin page)
- Configurable retention policy: Auto-delete logs after X days (default: 90 days)
- Admin setting: "Audit Log Retention (days)" (0 = keep forever)
- Cron job: Daily cleanup of expired logs

**Benefits:**
- ✅ GDPR compliance (data retention requirements)
- ✅ HIPAA compliance (audit trail retention)
- ✅ Storage optimization (reduce database size)
- ✅ Configurable policy (match institutional requirements)

**Technical Details:**
- Add "Export" button to Audit Logs admin page (Excel/CSV)
- Add admin setting: `eipsi_audit_log_retention_days` (default: 90)
- Cron job: `wp_schedule_event` (daily, delete logs older than retention days)
- Query: `DELETE FROM wp_vas_form_events WHERE created_at < DATE_SUB(NOW(), INTERVAL X DAY)`

**Acceptance Criteria:**
- [ ] Export audit logs as CSV/Excel (from admin page)
- [ ] Admin setting: "Audit Log Retention (days)" (default: 90)
- [ ] Cron job: Daily cleanup of expired logs
- [ ] Warning: "Logs older than X days will be automatically deleted"
- [ ] Manual retention override: "Keep Forever" option (0 days)
- [ ] Export includes all columns (event type, form ID, session ID, timestamp, metadata)

---

## PHASE 4: Professional Features (4-6 weeks)

**Priority:** IMPORTANT  
**Goal:** Expand user base and feature parity with Gravity Forms

### 4.1 Multilingual Support (i18n)

**Current State:** Mentioned as "roadmap future", no i18n implementation  
**Gap:** Qualtrics/REDCap support multi-language forms, EIPSI is Spanish-only by default  
**Effort:** Medium (2-3 days)  
**Impact:** High (global reach, accessibility)

**Implementation:**
- WordPress i18n best practices (.po/.mo files)
- Translatable strings: All user-facing text (field labels, buttons, messages)
- Admin UI: Language selector per form (or global setting)
- Participant UI: Language switcher (dropdown)
- RTL support: Existing (index-rtl.css already generated)

**Benefits:**
- ✅ Global reach (support international research)
- ✅ Accessibility (participants in native language)
- ✅ Compliance (some regulations require native language)
- ✅ User satisfaction (better UX for non-Spanish speakers)

**Technical Details:**
- Wrap all strings in `__()`, `_e()`, `_n()`, etc.
- Text domain: `eipsi-forms`
- Generate .pot file: `wp i18n make-pot`
- Provide .po/.mo files for: English (en_US), Spanish (es_ES)
- Admin UI: Language selector in FormContainer block settings
- Frontend: Language switcher button (dropdown with flag icons)

**Acceptance Criteria:**
- [ ] All user-facing strings wrapped in i18n functions
- [ ] .pot file generated (template)
- [ ] .po/.mo files for English and Spanish
- [ ] Admin UI: Language selector per form
- [ ] Participant UI: Language switcher (dropdown)
- [ ] RTL support validated (Arabic, Hebrew)
- [ ] Documentation: Translation guide for contributors

---

### 4.2 Custom CSS per Form

**Current State:** 5 presets available, but no custom CSS per form  
**Gap:** Gravity Forms allows custom CSS per form, EIPSI has global presets only  
**Effort:** Low (1 day)  
**Impact:** Medium (customization, branding)

**Implementation:**
- Block setting: "Custom CSS" (textarea)
- Scoped CSS: Inject CSS with form-specific class (e.g., `.eipsi-form-[form_id]`)
- Preview: Instant preview in Gutenberg editor (same as presets)
- Sanitization: Escape CSS to prevent XSS

**Benefits:**
- ✅ Branding customization (match institutional colors)
- ✅ Advanced styling (beyond presets)
- ✅ Per-form flexibility (different styles per form)
- ✅ Professional appearance (unique designs)

**Technical Details:**
- Add `customCSS: string` attribute to FormContainer block
- Inject CSS: `<style> .eipsi-form-{formId} { {customCSS} } </style>`
- Sanitization: `wp_strip_all_tags()` + validate CSS syntax
- Preview: Apply CSS to editor (same as presets)

**Acceptance Criteria:**
- [ ] "Custom CSS" textarea in FormContainer block settings
- [ ] CSS scoped to form-specific class (.eipsi-form-[form_id])
- [ ] Instant preview in Gutenberg editor (WYSIWYG)
- [ ] Sanitization: Prevent XSS (strip scripts, validate syntax)
- [ ] Documentation: CSS customization guide with examples
- [ ] Warning: "Custom CSS may override preset styles"

---

### 4.3 Recaptcha / Anti-Spam

**Current State:** No spam protection  
**Gap:** Gravity Forms has Recaptcha v3 integration, EIPSI has none  
**Effort:** Low (1 day)  
**Impact:** Medium (data quality, prevent spam submissions)

**Implementation:**
- Google Recaptcha v3 integration (invisible)
- Admin setting: Recaptcha site key + secret key
- Score threshold: Reject submissions with score < 0.5 (configurable)
- Fallback: Honeypot fields (hidden input, reject if filled)

**Benefits:**
- ✅ Data quality (reduce spam submissions)
- ✅ Researcher time savings (no manual spam removal)
- ✅ User-friendly (invisible Recaptcha, no challenge)
- ✅ Configurable threshold (balance security vs. false positives)

**Technical Details:**
- Admin setting: "Recaptcha Site Key", "Recaptcha Secret Key", "Score Threshold"
- Frontend: Load Recaptcha v3 script, execute on form submit
- Backend: Verify Recaptcha token with Google API
- Honeypot: Add hidden field, reject if filled (name: `eipsi_honeypot`)

**Acceptance Criteria:**
- [ ] Admin setting: Recaptcha site key + secret key (under Privacy & Metadata tab)
- [ ] Score threshold: Configurable (default: 0.5)
- [ ] Frontend: Recaptcha v3 script loaded, token submitted
- [ ] Backend: Verify token with Google API, reject if score < threshold
- [ ] Honeypot: Hidden field, reject if filled (fallback if no Recaptcha)
- [ ] Error message: "Submission blocked: Suspicious activity detected"
- [ ] Admin log: Track rejected submissions (audit log)

---

### 4.4 Email Notifications

**Current State:** No email notifications (participant or admin)  
**Gap:** Gravity Forms sends email notifications on submission, EIPSI has none  
**Effort:** Medium (2-3 days)  
**Impact:** Medium (workflow automation, not research-critical)

**Implementation:**
- Admin setting: "Send notification emails" (checkbox)
- Notification types: Admin (on submission), Participant (confirmation)
- Admin email: Send to admin email (configurable)
- Participant email: Send to participant (if email field exists)
- Customizable templates: Email subject, body (with placeholders)

**Benefits:**
- ✅ Workflow automation (admin notified immediately)
- ✅ Participant confirmation (email receipt of submission)
- ✅ Professional experience (expected feature)
- ✅ Integration potential (trigger workflows)

**Technical Details:**
- Add admin setting: "Email Notifications" (under Results & Experience → tab 4?)
- Admin notification: Send to `get_option('admin_email')` (or custom email)
- Participant notification: Send to field with name `email` or `participant_email`
- Template placeholders: `{form_name}`, `{participant_id}`, `{form_responses}`, `{submission_date}`
- Use `wp_mail()` function

**Acceptance Criteria:**
- [ ] Admin setting: "Enable Email Notifications" (checkbox)
- [ ] Admin notification: Send to configurable email on submission
- [ ] Participant notification: Send to participant email (if exists)
- [ ] Customizable templates: Subject + body with placeholders
- [ ] Test email: "Send Test Email" button (verify configuration)
- [ ] Conditional emails: Only send if form has email field
- [ ] Unsubscribe: Participant can opt-out (GDPR compliance)

---

## PHASE 5: Advanced Features (8-12 weeks)

**Priority:** NICE-TO-HAVE  
**Goal:** Enterprise-grade features for advanced use cases

### 5.1 API REST (CRUD Operations)

**Current State:** Mentioned as "roadmap future", no API exists  
**Gap:** Qualtrics/REDCap have full REST APIs, EIPSI has none  
**Effort:** High (5-7 days)  
**Impact:** High (integration potential, automation)

**Implementation:**
- REST API endpoints: GET, POST, PUT, DELETE
- Resources: Forms, Submissions, Audit Logs
- Authentication: WordPress REST API authentication (OAuth2, JWT, API keys)
- Rate limiting: Prevent abuse (e.g., 100 requests/hour)
- Documentation: Swagger/OpenAPI spec

**Benefits:**
- ✅ Integration potential (connect to other systems)
- ✅ Automation (programmatic form creation, data retrieval)
- ✅ Advanced workflows (trigger external actions on submission)
- ✅ Developer-friendly (standard REST API patterns)

**Technical Details:**
- Register routes: `register_rest_route('eipsi/v1', '/forms', ...)`
- Endpoints:
  - `GET /eipsi/v1/forms` - List all forms
  - `GET /eipsi/v1/forms/{id}` - Get form by ID
  - `POST /eipsi/v1/forms` - Create form
  - `PUT /eipsi/v1/forms/{id}` - Update form
  - `DELETE /eipsi/v1/forms/{id}` - Delete form
  - `GET /eipsi/v1/submissions` - List submissions (with filters)
  - `GET /eipsi/v1/submissions/{id}` - Get submission by ID
  - `POST /eipsi/v1/submissions` - Create submission (external submission)
  - `DELETE /eipsi/v1/submissions/{id}` - Delete submission
  - `GET /eipsi/v1/audit-logs` - List audit logs (with filters)
- Authentication: `permission_callback` with capability check
- Rate limiting: Store request count in transients, block if exceeded

**Acceptance Criteria:**
- [ ] REST API endpoints registered (9 endpoints)
- [ ] Authentication: Capability-based permission checks
- [ ] Rate limiting: 100 requests/hour per user (configurable)
- [ ] Documentation: Swagger/OpenAPI spec (auto-generated)
- [ ] Error handling: Proper HTTP status codes (200, 201, 400, 401, 403, 404, 500)
- [ ] Pagination: Support limit/offset for list endpoints
- [ ] Filtering: Support query parameters (form_id, date_range, participant_id)
- [ ] Testing: Automated API tests (Postman collection)

---

### 5.2 Webhooks (Real-Time Notifications)

**Current State:** Mentioned as "roadmap future", no webhooks exist  
**Gap:** Qualtrics/Gravity Forms support webhooks, EIPSI has none  
**Effort:** Medium (2-3 days)  
**Impact:** High (automation, integrations)

**Implementation:**
- Admin setting: Webhook URLs (one or more)
- Trigger: On form submission (POST request to webhook URL)
- Payload: JSON with form_id, participant_id, form_responses, metadata
- Retry logic: Retry up to 3 times if webhook fails
- Security: HMAC signature for payload verification

**Benefits:**
- ✅ Automation (trigger external workflows on submission)
- ✅ Integrations (connect to Zapier, Make, n8n, etc.)
- ✅ Real-time notifications (Slack, Discord, email)
- ✅ Custom workflows (trigger data processing pipelines)

**Technical Details:**
- Add admin setting: "Webhooks" (textarea with one URL per line)
- Hook: `do_action('eipsi_form_after_submit', ...)` → trigger webhooks
- HTTP POST: Send JSON payload to each webhook URL
- Payload: `{ form_id, participant_id, form_responses, metadata, timestamp }`
- Signature: HMAC-SHA256 with secret key (configurable)
- Retry: Use `wp_schedule_single_event()` for retries

**Acceptance Criteria:**
- [ ] Admin setting: "Webhook URLs" (textarea, one per line)
- [ ] Trigger: POST request on form submission
- [ ] Payload: JSON with form_id, participant_id, form_responses, metadata
- [ ] Signature: HMAC-SHA256 header (X-EIPSI-Signature)
- [ ] Retry: Up to 3 retries if webhook fails (exponential backoff)
- [ ] Logging: Track webhook successes/failures (audit log)
- [ ] Test webhook: "Send Test Webhook" button (with sample payload)

---

### 5.3 PDF Export

**Current State:** Excel/CSV export only, no PDF export  
**Gap:** Qualtrics/REDCap support PDF export, EIPSI has Excel/CSV only  
**Effort:** Medium (2-3 days)  
**Impact:** Medium (archival needs, print-friendly)

**Implementation:**
- Library: TCPDF or mPDF (PHP PDF libraries)
- Format: One PDF per submission (participant responses)
- Include: Form name, participant ID, submission date, all responses
- Optional: Logo, header/footer, custom styling

**Benefits:**
- ✅ Archival (PDF is long-term format)
- ✅ Print-friendly (printable participant reports)
- ✅ Professional (institutional branding)
- ✅ Offline sharing (email PDF reports)

**Technical Details:**
- Add "Export PDF" button to individual submissions (admin panel)
- Library: `tecnickcom/tcpdf` (install via Composer)
- Generate: Iterate over form_responses, format as table
- Include: Logo (site logo), header (form name), footer (page numbers)
- Download: `Content-Disposition: attachment; filename="submission-{id}.pdf"`

**Acceptance Criteria:**
- [ ] "Export PDF" button on individual submission page
- [ ] PDF includes: Form name, participant ID, submission date, all responses
- [ ] Professional styling: Logo, header/footer, table formatting
- [ ] Responsive: Page breaks for long forms
- [ ] Filename: `{form_name}_{participant_id}_{date}.pdf`
- [ ] Bulk export: "Export All as PDF (ZIP)" (multiple PDFs in ZIP archive)

---

### 5.4 A/B Testing (Form Variants)

**Current State:** Mentioned as "roadmap future", no A/B testing exists  
**Gap:** Qualtrics supports A/B testing, EIPSI has none  
**Effort:** High (4-5 days)  
**Impact:** Medium (research methodology, advanced use case)

**Implementation:**
- Create form variants (e.g., Form A, Form B)
- Randomized assignment: 50/50 split (or custom ratio)
- Track variant in metadata: `variant: 'A' | 'B'`
- Analytics: Compare completion rates, average times, responses

**Benefits:**
- ✅ Research methodology (test form design hypotheses)
- ✅ Optimization (identify better form versions)
- ✅ Evidence-based design (data-driven decisions)
- ✅ Advanced use case (supports intervention research)

**Technical Details:**
- Add "Variants" tab to FormContainer block settings
- Define variants: Variant A (baseline), Variant B (experimental)
- Randomization: On form load, assign participant to variant (store in localStorage)
- Track: Add `variant` field to `form_responses` metadata
- Analytics: Add variant comparison charts to Analytics Dashboard

**Acceptance Criteria:**
- [ ] Create form variants (A/B) in block settings
- [ ] Randomized assignment: 50/50 split (or custom ratio)
- [ ] Track variant in metadata (form_responses JSON)
- [ ] Analytics: Compare completion rates, average times by variant
- [ ] Admin UI: Variant comparison dashboard (charts)
- [ ] Export: Include variant column in exports
- [ ] Optional: Multi-variant (A/B/C/D) testing

---

## Feature Impact Matrix

| Feature | Effort | Impact | Research Grade | Priority | Timeline |
|---------|--------|--------|-----------------|----------|----------|
| **PHASE 1: Quick Wins** |
| Progress Bar (visual) | Low | High | ⭐⭐⭐⭐ | HIGH | Week 1 |
| Time Limits | Low | High | ⭐⭐⭐⭐ | HIGH | Week 1 |
| Form Versioning | Medium | High | ⭐⭐⭐⭐⭐ | HIGH | Week 2 |
| Analytics Dashboard UI | Medium | High | ⭐⭐⭐⭐ | HIGH | Week 2-3 |
| **PHASE 2: Research-Grade** |
| Partial Submissions | High | Very High | ⭐⭐⭐⭐⭐ | CRITICAL | Week 4-5 |
| Auto-Scoring UI | Medium | Very High | ⭐⭐⭐⭐⭐ | CRITICAL | Week 5-6 |
| Matrix Questions | Medium | High | ⭐⭐⭐⭐ | HIGH | Week 6-7 |
| Enhanced Audit Logs | Low | High | ⭐⭐⭐⭐ | HIGH | Week 7 |
| **PHASE 3: Security & Compliance** |
| Field Encryption | Medium | High | ⭐⭐⭐⭐⭐ | CRITICAL | Week 8-9 |
| 2FA for Admin | Medium | Medium | ⭐⭐⭐ | IMPORTANT | Week 9 |
| Audit Log Export | Low | High | ⭐⭐⭐⭐ | IMPORTANT | Week 9 |
| **PHASE 4: Professional** |
| Multilingual Support | Medium | High | ⭐⭐⭐⭐ | IMPORTANT | Week 10-11 |
| Custom CSS | Low | Medium | ⭐⭐ | NICE-TO-HAVE | Week 11 |
| Recaptcha | Low | Medium | ⭐⭐⭐ | IMPORTANT | Week 11 |
| Email Notifications | Medium | Medium | ⭐⭐⭐ | IMPORTANT | Week 12 |
| **PHASE 5: Advanced** |
| API REST | High | High | ⭐⭐⭐⭐ | NICE-TO-HAVE | Week 13-15 |
| Webhooks | Medium | High | ⭐⭐⭐⭐ | NICE-TO-HAVE | Week 15-16 |
| PDF Export | Medium | Medium | ⭐⭐⭐ | NICE-TO-HAVE | Week 16-17 |
| A/B Testing | High | Medium | ⭐⭐⭐⭐ | NICE-TO-HAVE | Week 18-20 |

**Effort Scale:**
- **Low:** 1-2 days (8-16 hours)
- **Medium:** 2-4 days (16-32 hours)
- **High:** 4-7 days (32-56 hours)

**Impact Scale:**
- **Low:** Nice to have, minimal user request
- **Medium:** Useful, moderate user request
- **High:** Important, frequent user request
- **Very High:** Critical, essential for research-grade use

**Research Grade Scale:**
- ⭐ = Basic feature
- ⭐⭐ = Standard feature
- ⭐⭐⭐ = Professional feature
- ⭐⭐⭐⭐ = Research-grade feature
- ⭐⭐⭐⭐⭐ = Gold standard (REDCap-equivalent)

---

## Recommended Implementation Order

### Quarter 1 (Months 1-3): Foundation

**Goal:** Match Gravity Forms feature parity

1. ✅ **Week 1-2:** Progress Bar + Time Limits (Quick wins)
2. ✅ **Week 2-3:** Form Versioning + Analytics Dashboard UI (Compliance + visibility)
3. ✅ **Week 4-5:** Partial Submissions (Highest user demand)
4. ✅ **Week 6-7:** Auto-Scoring UI + Matrix Questions (Research-grade features)
5. ✅ **Week 8-9:** Enhanced Audit Logs + Recaptcha (Data quality)
6. ✅ **Week 10-12:** Email Notifications + Custom CSS (Professional polish)

**Deliverables:**
- ✅ Visual progress indicators
- ✅ Save & resume functionality
- ✅ Research-grade scoring
- ✅ Compliance-ready audit trails
- ✅ Professional email workflows

**Positioning:** **"REDCap-lite for WordPress"** - Open-source, WCAG-compliant, research-grade forms with save & resume, auto-scoring, and audit trails.

---

### Quarter 2 (Months 4-6): Research Grade

**Goal:** Match REDCap feature parity

7. ✅ **Week 13-15:** Field Encryption (HIPAA compliance)
8. ✅ **Week 16-18:** Multilingual Support (Global reach)
9. ✅ **Week 19-21:** 2FA + Audit Log Export (Security enhancement)
10. ✅ **Week 22-24:** API REST (Phase 1: Read-only endpoints)

**Deliverables:**
- ✅ HIPAA-certified encryption
- ✅ Multi-language forms
- ✅ Enterprise-grade security
- ✅ Basic API for integrations

**Positioning:** **"Open-Source REDCap Alternative"** - Full feature parity with REDCap for clinical research, with better accessibility and modern UX.

---

### Quarter 3 (Months 7-9): Enterprise

**Goal:** Match Qualtrics feature parity

11. ✅ **Week 25-27:** Webhooks + PDF Export
12. ✅ **Week 28-30:** API REST (Phase 2: Write endpoints + rate limiting)
13. ✅ **Week 31-33:** A/B Testing (Form variants)
14. ✅ **Week 34-36:** Analytics Dashboard (Phase 2: Advanced charts, filtering, export)

**Deliverables:**
- ✅ Real-time integrations (webhooks)
- ✅ Full CRUD API (WordPress ecosystem integration)
- ✅ Intervention research support (A/B testing)
- ✅ Professional analytics dashboards

**Positioning:** **"Enterprise Research Platform"** - All features of Qualtrics + REDCap, but open-source, self-hosted, and WCAG AAA compliant.

---

### Quarter 4 (Months 10-12): Innovation

**Goal:** Beyond industry standards

15. ✅ **Advanced Analytics:** Predictive models (completion likelihood, data quality prediction)
16. ✅ **EMR Integration:** Connect to Electronic Medical Records (FHIR API)
17. ✅ **Voice Input:** Accessibility enhancement (speech-to-text for responses)
18. ✅ **Offline Mode:** Progressive Web App (PWA) for offline data collection
19. ✅ **AI-Powered QA:** Automatic detection of inconsistent responses

**Deliverables:**
- ✅ Predictive analytics (ML models)
- ✅ Healthcare integration (EMR/EHR)
- ✅ Accessibility beyond WCAG AAA
- ✅ Offline-first architecture
- ✅ AI-enhanced data quality

**Positioning:** **"Next-Generation Research Platform"** - The only research platform with AI-powered data quality, offline support, and voice input for accessibility.

---

## Strategic Positioning by Phase

| Phase | Positioning | Comparable To | Unique Advantage |
|-------|-------------|---------------|------------------|
| **Current (v1.2.2)** | Clinical Forms Plugin | Basic Gravity Forms | WCAG 2.1 AA + Zero Data Loss |
| **Phase 1 (3 months)** | REDCap-lite | Gravity Forms Pro | Save & Resume + Auto-Scoring |
| **Phase 2 (6 months)** | Open-Source REDCap | REDCap | Better UX + Modern Tech Stack |
| **Phase 3 (9 months)** | Enterprise Platform | Qualtrics | Open Source + Self-Hosted |
| **Phase 4 (12 months)** | Next-Gen Research | Beyond Industry | AI + Voice + Offline |

---

## Success Metrics

### Phase 1 Success Criteria (3 months):
- [ ] 50% reduction in form abandonment (progress bar + partial submissions)
- [ ] 90% researcher satisfaction with auto-scoring UI
- [ ] 100% audit compliance (form versioning + audit logs)
- [ ] 5+ community contributions (open-source engagement)

### Phase 2 Success Criteria (6 months):
- [ ] HIPAA certification readiness (field encryption + 2FA + audit logs)
- [ ] 10+ languages supported (multilingual forms)
- [ ] 100+ API integrations (REST API + webhooks)
- [ ] 10x user base growth (vs. v1.2.2)

### Phase 3 Success Criteria (9 months):
- [ ] Feature parity with Qualtrics (all core features)
- [ ] 50+ research publications using EIPSI Forms
- [ ] Enterprise adoption (universities, hospitals)
- [ ] Community ecosystem (plugins, themes, extensions)

### Phase 4 Success Criteria (12 months):
- [ ] Industry innovation leader (first with AI + voice + offline)
- [ ] 100+ research institutions using EIPSI
- [ ] Published case studies (impact on research)
- [ ] Conference presentations (academic conferences)

---

## Conclusion

**Current State:** EIPSI Forms v1.2.2 is a **solid clinical research form plugin** with excellent accessibility, privacy controls, and conditional logic. It's **production-ready** for basic research needs.

**Strategic Gap:** Compared to REDCap/Qualtrics, EIPSI lacks:
- **Visual progress indicators** (text-only currently)
- **Save & resume functionality** (long forms are challenging)
- **Advanced analytics UI** (tracking exists, but no dashboard)
- **Field-level encryption** (HIPAA requirement)
- **Multi-language support** (limited to Spanish currently)

**Opportunity:** Adding **Phase 1 & 2 features** (8-10 weeks) would position EIPSI as:
- **"REDCap-lite for WordPress"** - Open-source alternative with better UX
- **WCAG AAA-compliant** - Best accessibility in the market
- **Zero Data Loss Guarantee** - Unique selling point for clinical research
- **Privacy-first GDPR** - European research compliance

**Recommendation:** Prioritize **Phase 1 (Quick Wins)** to deliver immediate value, then **Phase 2 (Research-Grade)** to achieve REDCap feature parity. This 6-month roadmap would establish EIPSI Forms as the **go-to open-source research platform** for clinical researchers worldwide.

---

**Next Steps:**
1. Review and prioritize roadmap with stakeholders
2. Allocate resources (developer time, budget)
3. Create GitHub issues for Phase 1 features
4. Establish user feedback channels (beta testers, research community)
5. Update README with roadmap and comparison table

**Questions? Feedback?**  
Contact: support@eipsi.research  
GitHub: [Open Issue](https://github.com/roofkat/VAS-dinamico-mvp/issues)

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Author:** EIPSI Research Team + cto.new AI Agent
