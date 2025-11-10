# EIPSI Forms - Distribution Quick Reference

## 🚀 Quick Start: Creating a Release Package

```bash
# One command to rule them all
./build-release.sh
```

That's it! The script handles everything automatically.

---

## 📦 What's Included in Distribution?

### ✅ YES - These files ARE included:
- `vas-dinamico-forms.php` - Main plugin
- `admin/`, `assets/`, `blocks/`, `lib/`, `languages/` - Core functionality
- `src/`, `package.json` - Source files for rebuilding
- `README.md`, `LICENSE`, `CHANGES.md` - User documentation
- `.wordpress-org/` - WordPress.org assets

### ❌ NO - These files are NOT included:
- `.git/` - Version control (1.6 MB)
- `node_modules/` - Dependencies (~100-200 MB)
- `build/` - Old compiled files (regenerated fresh)
- `test-*.{js,html,sh}` - Test files (10 files)
- `*.sql` - SQL scripts
- `AUDIT_*.md`, `*_TESTING.md`, `*_IMPLEMENTATION.md` - Dev docs (58 files)

**Total Size Reduction:** 99% (105 MB → 1-2 MB)

---

## 🔧 Manual Build (if needed)

### With WP-CLI:
```bash
rm -rf build/ node_modules/
npm install && npm run build
wp dist-archive . --plugin-dirname=vas-dinamico-forms
```

### Without WP-CLI:
```bash
rm -rf build/ node_modules/
npm install && npm run build
rsync -av --exclude-from=.distignore . dist/vas-dinamico-forms/
cd dist && zip -r ../vas-dinamico-forms.zip vas-dinamico-forms/
rm -rf dist/
```

---

## ✅ Verification Checklist

### Before Building:
- [ ] Update version in `vas-dinamico-forms.php`
- [ ] Update `CHANGES.md` with release notes
- [ ] Commit all changes

### After Building:
```bash
# Check size (should be ~1-2 MB)
du -h vas-dinamico-forms-*.zip

# Verify no test files (should return nothing)
unzip -l vas-dinamico-forms-*.zip | grep -i test

# Verify no dev docs (should return nothing)
unzip -l vas-dinamico-forms-*.zip | grep -i audit
```

### Installation Test:
- [ ] Extract and install in test WordPress
- [ ] Activate plugin without errors
- [ ] Create form with blocks
- [ ] Submit test form
- [ ] Export to Excel
- [ ] No console errors

---

## 📖 Documentation Files

### For Developers:
- `BUILD_INSTRUCTIONS.md` - Full build and release guide
- `DISTRIBUTION_CLEANUP.md` - Detailed cleanup strategy
- `DISTRIBUTION_INVENTORY.md` - Complete file inventory
- `.distignore` - Exclusion rules

### For Stakeholders:
- `DISTRIBUTION_CHECKLIST.md` - Approval checklist
- `PRUNING_COMPLETION_SUMMARY.md` - Project summary

### For Users (in distribution):
- `README.md` - Plugin usage guide
- `LICENSE` - GPL license
- `CHANGES.md` - Version history

---

## 🆘 Troubleshooting

### Build fails:
```bash
rm -rf node_modules/ package-lock.json build/
npm cache clean --force
npm install
npm run build
```

### Package too large (>5 MB):
```bash
# Check what's included
unzip -l vas-dinamico-forms-*.zip | sort -k4 -rn | head -20

# Likely culprits:
# - node_modules/ (check .distignore)
# - Dev docs (check .distignore)
```

### Missing files:
```bash
# Test what rsync would exclude
rsync -avn --exclude-from=.distignore . /tmp/test/
```

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `.distignore` | Exclusion rules (95 lines) |
| `build-release.sh` | Automated build script |
| `package.json` | Build scripts |
| `README.md` | User documentation |
| `vas-dinamico-forms.php` | Main plugin file |

---

## 🎯 File Size Targets

| Component | Size |
|-----------|------|
| **Total package** | **1-2 MB** |
| Core plugin | ~500 KB |
| Source files | ~300 KB |
| Build output | ~200 KB |
| Documentation | ~35 KB |

If your package is significantly larger, something's wrong!

---

## 🔐 Security Notes

**Excluded for security:**
- No `.git/` directory (no version history exposed)
- No test files (no internal tooling exposed)
- No `node_modules/` (no unnecessary dependencies)
- No `.env` files (no environment configs)

---

## 📋 Release Workflow

1. **Update version** → `vas-dinamico-forms.php`
2. **Update changelog** → `CHANGES.md`
3. **Build package** → `./build-release.sh`
4. **Verify package** → Size + contents check
5. **Test install** → Fresh WordPress site
6. **Distribute** → WordPress.org or direct

---

## 🤔 FAQ

### Q: Why exclude `build/` if it's needed?
**A:** It's regenerated fresh during packaging. This ensures no stale/mismatched files.

### Q: Why keep `src/` and `package.json`?
**A:** Allows developers to rebuild and customize blocks. Industry standard for WordPress blocks.

### Q: Can I rebuild from the distribution package?
**A:** Yes! Just run `npm install && npm run build` in the plugin directory.

### Q: Where are the developer docs?
**A:** In the Git repository. Clone the full repo to access all 58 documentation files.

### Q: What if I need to exclude something else?
**A:** Add it to `.distignore` using `.gitignore` syntax. Test with the build script.

---

## 📞 Need Help?

- **Build issues:** See `BUILD_INSTRUCTIONS.md`
- **What's excluded:** See `DISTRIBUTION_INVENTORY.md`
- **Why excluded:** See `DISTRIBUTION_CLEANUP.md`
- **Full details:** See `PRUNING_COMPLETION_SUMMARY.md`

---

**Version:** 1.0  
**Last Updated:** 2025-01-10  
**For:** EIPSI Forms v1.1.0+
