# EIPSI Forms - Event Tracking System

## 🎯 Overview

This tracking system captures anonymous user interactions with forms for psychotherapy research analytics.

**Status:** ✅ Complete and Ready for Testing

---

## 🚀 Quick Start

### For Developers

```bash
# 1. Plugin is already activated
# 2. Run automated tests
./test-tracking-cli.sh

# 3. Check results
wp db query "SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 5;"
```

### For Testers

1. Open `test-tracking.html` in browser
2. Configure AJAX URL and nonce
3. Click test buttons
4. Verify green success messages

### For Researchers

```bash
# View analytics
wp db query < tracking-queries.sql

# Export data
wp db query "SELECT * FROM wp_vas_form_events" --csv > events_export.csv
```

---

## 📁 Documentation Files

| File | Purpose | Size |
|------|---------|------|
| **TICKET_COMPLETION.md** | Ticket status and checklist | 11 KB |
| **TESTING_GUIDE.md** | Step-by-step testing instructions | 11 KB |
| **IMPLEMENTATION_SUMMARY.md** | Quick reference guide | 10 KB |
| **TRACKING_IMPLEMENTATION.md** | Complete technical docs | 12 KB |
| **CHANGES.md** | Detailed change log | 11 KB |
| **README_TRACKING.md** | This file - overview | 2 KB |

---

## 🛠️ Testing Resources

| Resource | Type | Purpose |
|----------|------|---------|
| `test-tracking-cli.sh` | Bash Script | Automated WP-CLI tests (10 tests) |
| `test-tracking.html` | HTML/JS | Interactive browser testing |
| `tracking-queries.sql` | SQL | Analytics and verification queries |

---

## 🎓 Where to Start

### New to This Codebase?
→ Start with **IMPLEMENTATION_SUMMARY.md**

### Want to Test?
→ Follow **TESTING_GUIDE.md**

### Need Technical Details?
→ Read **TRACKING_IMPLEMENTATION.md**

### Want to See Changes?
→ Check **CHANGES.md**

### Ready to Close Ticket?
→ Review **TICKET_COMPLETION.md**

---

## ⚡ Core Features

### Event Types Tracked
- `view` - Form viewed
- `start` - User started interacting
- `page_change` - Multi-page navigation
- `submit` - Form submitted
- `abandon` - User left without submitting

### Duration Tracking (Updated: January 2025)
- ✅ `form_start_time` set once on initialization
- ✅ `form_end_time` captured before submission
- ✅ Protected against multiple submissions
- ✅ Accurate duration calculation in seconds (millisecond precision)
- ✅ Works with both normal and conditional-logic auto-submit flows

### Security
- ✅ Nonce verification
- ✅ Input sanitization
- ✅ SQL injection prevention
- ✅ No PII collected

### Performance
- ✅ Indexed database
- ✅ Non-blocking requests
- ✅ Silent error handling

---

## 📊 What Was Implemented

### Code Changes
- **Modified:** 3 files
- **Created:** 8 files
- **PHP Code:** ~100 lines
- **Tests:** 10 automated
- **Documentation:** ~50 KB

### Database
- **Table:** `wp_vas_form_events`
- **Columns:** 7
- **Indexes:** 5

### API
- **Endpoints:** 2 (logged-in + public)
- **Event Types:** 5
- **Security:** Nonce-protected

---

## ✅ Verification

### Quick Check
```bash
# All in one command
wp db query "SHOW TABLES LIKE '%vas_form_events%';" && \
wp eval "echo function_exists('eipsi_track_event_handler') ? '✓ Handler exists' : '✗ Handler missing';"
```

**Expected:**
```
wp_vas_form_events
✓ Handler exists
```

---

## 🆘 Troubleshooting

### Issue: Table doesn't exist
**Fix:** `wp plugin activate vas-dinamico-forms`

### Issue: Tests fail
**Fix:** Check `TESTING_GUIDE.md` → Troubleshooting section

### Issue: Events not tracked
**Fix:** Check `TRACKING_IMPLEMENTATION.md` → Troubleshooting section

---

## 📞 Support

1. **Check documentation** in order:
   - TESTING_GUIDE.md
   - IMPLEMENTATION_SUMMARY.md
   - TRACKING_IMPLEMENTATION.md

2. **Run diagnostics:**
   ```bash
   ./test-tracking-cli.sh
   ```

3. **Check logs:**
   ```bash
   tail -f wp-content/debug.log
   ```

---

## 🎉 Success Criteria

Your implementation is working if:

- ✅ Tests pass (run `./test-tracking-cli.sh`)
- ✅ Table exists (`wp_vas_form_events`)
- ✅ Events insert (check database)
- ✅ API responds (200 status codes)
- ✅ No errors in console

---

## 📈 Next Steps

### After Testing
1. Review analytics queries
2. Set up data retention policy
3. Train team on data export
4. Monitor error logs

### Future Enhancements (Optional)
- Admin analytics dashboard
- Data export UI
- Real-time monitoring
- Heatmap integration

---

## 📝 Quick Commands

```bash
# Run all tests
./test-tracking-cli.sh

# View recent events
wp db query "SELECT * FROM wp_vas_form_events ORDER BY created_at DESC LIMIT 10;"

# Count events by type
wp db query "SELECT event_type, COUNT(*) FROM wp_vas_form_events GROUP BY event_type;"

# Export all events
wp db query "SELECT * FROM wp_vas_form_events" --csv > events.csv

# Check table structure
wp db query "DESCRIBE wp_vas_form_events;"

# Test single event
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'test';
\$_POST['session_id'] = 'quick-test';
\$_POST['event_type'] = 'view';
do_action('wp_ajax_nopriv_eipsi_track_event');
"
```

---

## 🏆 Implementation Highlights

✅ **Complete** - All ticket requirements met
✅ **Tested** - Multiple testing methods
✅ **Documented** - Comprehensive guides
✅ **Secure** - WordPress best practices
✅ **Resilient** - Graceful error handling
✅ **Research-grade** - GDPR-compliant, ethical

---

## 📚 File Map

```
vas-dinamico-forms/
├── admin/
│   └── ajax-handlers.php          ← Handler implementation
├── vas-dinamico-forms.php         ← Table creation
├── test-tracking-cli.sh           ← Automated tests
├── test-tracking.html             ← Manual testing UI
├── tracking-queries.sql           ← SQL queries
├── TICKET_COMPLETION.md           ← Ticket checklist
├── TESTING_GUIDE.md               ← How to test
├── IMPLEMENTATION_SUMMARY.md      ← Quick reference
├── TRACKING_IMPLEMENTATION.md     ← Technical docs
├── CHANGES.md                     ← What changed
└── README_TRACKING.md             ← This file
```

---

**Ready to test?** → Start with `TESTING_GUIDE.md`

**Ready to merge?** → Review `TICKET_COMPLETION.md`

**Need help?** → Check `TRACKING_IMPLEMENTATION.md`

---

**Implementation Date:** November 8, 2024
**Branch:** `feat/eipsi-tracking-handler`
**Status:** ✅ Complete

---

*For psychotherapy research with ❤️*
