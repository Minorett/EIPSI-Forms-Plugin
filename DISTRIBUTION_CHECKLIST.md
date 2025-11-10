# EIPSI Forms - Distribution Package Checklist

## Quick Reference for Stakeholder Review

### Files REMOVED from Distribution ❌

#### Version Control (1.6 MB)
- [ ] `.git/` directory
- [ ] `.gitignore`
- [ ] `.gitattributes`

#### Build Tools & Dependencies (~1-200 MB)
- [ ] `node_modules/` directory
- [ ] `package-lock.json`
- [ ] `.wp-env.json`
- [ ] `server.log`

#### Build Output (~200 KB)
- [ ] `build/` directory (will be regenerated)

#### Test Files (10 files, ~200 KB)
- [ ] `test-conditional-flows.js`
- [ ] `test-editor-smoke.js`
- [ ] `test-editor-smoke-dry-run.js`
- [ ] `test-navigation-ux.html`
- [ ] `test-report-generator.html`
- [ ] `test-tracking-browser.html`
- [ ] `test-tracking-cli.sh`
- [ ] `test-tracking.html`
- [ ] `tracking-queries.sql`
- [ ] `wcag-contrast-validation.js`

#### Developer Documentation (58 files, ~1.5 MB)
- [ ] All `*_AUDIT_*.md` files (9 files)
- [ ] All `*_TEST*.md` files (20 files)
- [ ] All `*_IMPLEMENTATION*.md` files (10 files)
- [ ] All `*_GUIDE.md` files (12 files)
- [ ] All `DELIVERABLES*.md` files (2 files)
- [ ] All `TICKET_*.md` files (2 files)
- [ ] All `WCAG_*.md` files (2 files)
- [ ] Other internal docs (1 file)

#### OS/IDE Artifacts
- [ ] `.DS_Store`, `Thumbs.db`
- [ ] `.vscode/`, `.idea/`
- [ ] `*.swp`, `*.swo`

---

### Files RETAINED in Distribution ✅

#### Core Plugin Files (Required)
- [x] `vas-dinamico-forms.php` - Main plugin file
- [x] `admin/` - Admin functionality
- [x] `assets/` - Production CSS/JS
- [x] `blocks/` - Block definitions
- [x] `lib/` - Required libraries
- [x] `languages/` - Translation files

#### Source Files (For Rebuilding)
- [x] `src/` - Block source code
- [x] `package.json` - Build scripts

#### Essential Documentation
- [x] `README.md` - User documentation
- [x] `LICENSE` - GPL license
- [x] `CHANGES.md` - Changelog

#### WordPress.org Assets
- [x] `.wordpress-org/` directory
  - [x] `banner-772x250.svg`
  - [x] `icon-256x256.svg`

#### Distribution Management
- [x] `.distignore` - Packaging rules
- [x] `DISTRIBUTION_CLEANUP.md` - This documentation
- [x] `DISTRIBUTION_CHECKLIST.md` - This checklist

---

## Size Impact Summary

| Category | Before | After | Savings |
|----------|--------|-------|---------|
| Repository | 5-10 MB | 1-2 MB | 75-80% |
| With node_modules | 105-210 MB | 1-2 MB | 99% |
| Documentation | 58 files | 3 files | 95% |
| Test files | 10 files | 0 files | 100% |

---

## Pre-Release Verification

### Before Creating Distribution Package:
- [ ] Run `rm -rf build/ node_modules/`
- [ ] Run `npm install`
- [ ] Run `npm run build`
- [ ] Verify no errors during build
- [ ] Check `build/` directory contains fresh files

### During Packaging:
- [ ] Use `.distignore` for automated exclusion
- [ ] Create package: `wp dist-archive .`
- [ ] Verify package size (~1-2 MB)

### After Creating Package:
- [ ] Extract and review package contents
- [ ] Confirm no test files present
- [ ] Confirm no audit documentation present
- [ ] Confirm `.git/` not present
- [ ] Confirm `src/` IS present
- [ ] Confirm `assets/` IS present
- [ ] Confirm `README.md` IS present

### Installation Testing:
- [ ] Install plugin from distribution package
- [ ] Activate plugin successfully
- [ ] Create new form with blocks
- [ ] Submit test form
- [ ] Export results to Excel
- [ ] Check browser console for errors
- [ ] Verify customization panel works
- [ ] Test responsive behavior

---

## Rationale Summary

### Why Remove Developer Docs?
- **Size:** 1.5 MB of text documentation
- **Audience:** Internal development team only
- **Access:** Available in Git repository
- **User Value:** Minimal to end users
- **Professional:** Cleaner distribution package

### Why Remove Test Files?
- **Function:** Development/QA only
- **User Value:** None
- **Security:** Reduces exposed internal tooling
- **Size:** ~200 KB savings

### Why Remove Build Output?
- **Best Practice:** Regenerate for each release
- **Quality:** Ensures fresh compilation
- **Consistency:** Prevents stale assets

### Why Keep Source Files?
- **Customization:** Developers can modify blocks
- **Contribution:** Enables community contributions
- **Standard:** Common practice for WordPress blocks
- **Rebuild:** Required for `npm run build`

---

## Approval Status

### Reviewed By:
- [ ] Technical Lead - Date: ___________
- [ ] Product Owner - Date: ___________
- [ ] QA Manager - Date: ___________

### Approved By:
- [ ] Project Stakeholder - Date: ___________

### Notes:
```
[Add any approval notes, concerns, or modifications here]
```

---

## Next Steps

1. ✅ Create `.distignore` file
2. ✅ Document cleanup strategy
3. ✅ Create verification checklist
4. [ ] Stakeholder review and approval
5. [ ] Test packaging workflow
6. [ ] Verify plugin installation from package
7. [ ] Update release documentation

---

**Document Version:** 1.0  
**Created:** 2025-01-10  
**Ticket:** Prune Dev Assets
