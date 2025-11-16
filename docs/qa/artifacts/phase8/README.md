# EIPSI Forms - Phase 8 Artifacts Directory

This directory contains all evidence and test artifacts from Phase 8: Edge Case & Robustness Testing.

## Directory Structure

```
phase8/
├── validation/          # Validation & error handling test evidence
├── database/            # Database failure test evidence
├── network/             # Network interruption test evidence
├── long-forms/          # Long form performance test evidence
├── browsers/            # Cross-browser compatibility evidence
│   └── console-logs/    # Browser console logs
├── security/            # Security hygiene test evidence
└── README.md            # This file
```

## Artifact Categories

### 1. Validation (validation/)

Screenshots and evidence for:
- Required field validation errors
- Email format validation
- VAS slider touch validation
- Radio/checkbox group validation
- Server-side sanitization (XSS prevention)
- ARIA live announcements
- Focus management
- Error clearing

**Key Files:**
- `required-field-error.png` - Screenshot of required field validation
- `email-validation-error.png` - Invalid email format error
- `vas-slider-touch-error.png` - VAS slider untouched error
- `xss-attempt-sanitized.png` - XSS attempt sanitized in admin
- `screen-reader-test.mp4` - Video of screen reader announcements

### 2. Database (database/)

Evidence for database failure handling:
- External DB connection failures
- Fallback to WordPress DB
- Error logging
- Admin diagnostics
- Export during DB failure

**Key Files:**
- `external-db-fallback-success.png` - Success message with fallback warning
- `console-log-fallback.json` - AJAX response with fallback data
- `admin-results-fallback-record.png` - Admin view of fallback record
- `db-connection-test-invalid.png` - Invalid credentials error
- `db-connection-test-valid.png` - Successful connection test

### 3. Network (network/)

Network interruption handling evidence:
- Offline mode submission
- Slow network (3G throttling)
- Double-submit prevention
- Loading states
- Error recovery

**Key Files:**
- `offline-error-message.png` - Error message during offline
- `network-tab-offline.har` - HAR file of failed request
- `double-submit-prevention.mp4` - Video showing single submission
- `slow-3g-loading-state.png` - Loading indicator during slow network
- `admin-single-record.png` - Confirmation of no duplicate records

### 4. Long Forms (long-forms/)

Performance and behavior of 10+ page forms:
- Page transitions
- Progress indicators
- Memory usage
- Conditional navigation
- Form reset

**Key Files:**
- `10-page-form-progress.png` - Progress indicator screenshot
- `performance-trace.json` - Chrome Performance trace
- `heap-snapshot-before.heapsnapshot` - Memory before navigation
- `heap-snapshot-after.heapsnapshot` - Memory after navigation
- `conditional-jump-page-2-to-8.mp4` - Video of branching logic
- `sticky-nav-scrolling.mp4` - Sticky navigation behavior

### 5. Browsers (browsers/)

Cross-browser compatibility screenshots and logs:

**Desktop:**
- `chrome-desktop-screenshot.png`
- `firefox-desktop-screenshot.png`
- `safari-desktop-screenshot.png`
- `edge-desktop-screenshot.png`

**Mobile:**
- `ios-safari-portrait.png`
- `ios-safari-landscape.png`
- `android-chrome-screenshot.png`
- `ios-interaction.mp4` - Touch interaction video

**Console Logs:**
- `console-logs/chrome.log`
- `console-logs/firefox.log`
- `console-logs/safari.log`
- `console-logs/mobile.log`

**Other:**
- `compatibility-matrix.xlsx` - Comprehensive compatibility results
- `lighthouse-report.html` - Lighthouse audit results

### 6. Security (security/)

Security testing evidence:
- Nonce expiration
- Unauthorized AJAX calls
- SQL injection attempts
- CSRF prevention
- Direct file access

**Key Files:**
- `nonce-expiration-403.png` - Expired nonce rejection
- `unauthorized-ajax-403.png` - Capability check blocking access
- `sql-injection-sanitized.png` - SQL injection prevented
- `csrf-prevention-failed.png` - CSRF attack blocked
- `direct-access-blocked.png` - Direct PHP file access prevented
- `event-type-whitelist-400.json` - Invalid event type rejected

## File Naming Conventions

- **Screenshots:** `[test-description]-[result].png`
- **Videos:** `[test-description].mp4`
- **Logs:** `[test-description].log` or `[test-description].json`
- **HAR Files:** `[test-description].har`
- **Performance:** `[test-description].json` or `.heapsnapshot`

## Evidence Requirements

Each artifact should include:

1. **Filename** that clearly describes the test
2. **Timestamp** (in filename or metadata)
3. **Context** - What test scenario it represents
4. **Clear visibility** of key elements (errors, success messages, etc.)
5. **Console logs** when applicable
6. **Network requests** (HAR files) for AJAX tests

## Screenshot Standards

- **Resolution:** Minimum 1280x800 for desktop, native resolution for mobile
- **Format:** PNG for screenshots, MP4 for videos
- **Annotations:** Use red boxes/arrows to highlight key elements
- **Browser Info:** Visible in screenshot or documented in filename

## Video Standards

- **Duration:** Keep under 60 seconds per test
- **Format:** MP4 (H.264 codec)
- **Frame Rate:** 30fps minimum
- **Include:** Show entire test flow from setup to result

## Log File Standards

- **Format:** JSON for structured data, .log for text logs
- **Content:** Include timestamps, request/response data, error messages
- **Sanitization:** Remove any sensitive data (passwords, API keys)

## HAR File Standards

- **Export:** From Chrome DevTools Network tab
- **Scope:** Include all requests during test scenario
- **Sensitive Data:** Redact authorization tokens if present

## Metadata Template

For each artifact, document in QA_PHASE8_RESULTS.md:

```markdown
**Test:** [Test ID and Name]
**Evidence:** [Artifact filename]
**Date:** [YYYY-MM-DD]
**Browser/Device:** [Chrome 120, iOS Safari 17, etc.]
**Result:** [Pass/Fail]
**Notes:** [Any observations]
```

## Quality Checklist

Before finalizing artifacts:

- [ ] All filenames follow naming conventions
- [ ] Screenshots are clear and readable
- [ ] Videos demonstrate complete test scenarios
- [ ] Console logs include relevant errors/warnings
- [ ] HAR files contain necessary request/response data
- [ ] Performance traces show key metrics
- [ ] All artifacts referenced in QA_PHASE8_RESULTS.md
- [ ] Sensitive data redacted (passwords, tokens)
- [ ] Directory structure organized
- [ ] README.md updated

## Artifact Count Targets

| Category | Minimum Files | Recommended |
|----------|---------------|-------------|
| Validation | 10 | 15 |
| Database | 6 | 10 |
| Network | 5 | 8 |
| Long Forms | 6 | 10 |
| Browsers | 12 | 20+ |
| Security | 6 | 10 |
| **Total** | **45** | **73+** |

## Storage & Retention

- **Repository:** Commit to git with LFS for large files (videos, HAR files)
- **Backup:** Archive to cloud storage for long-term retention
- **Retention Period:** 2 years minimum
- **Access:** Restrict to QA team and technical leads

## Related Documents

- **Test Plan:** `docs/qa/EDGE_CASE_TESTING_GUIDE.md`
- **Results Report:** `docs/qa/QA_PHASE8_RESULTS.md`
- **Validation Script:** `edge-case-validation.js`
- **Validation Results:** `docs/qa/edge-case-validation.json`

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-01-XX | [Name] | Initial structure created |

---

**Last Updated:** January 2025  
**Status:** Ready for testing

For questions about artifacts or evidence requirements, contact: [QA Lead Email]
