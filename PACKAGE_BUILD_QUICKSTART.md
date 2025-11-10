# EIPSI Forms - Package Build Quickstart Guide

## 🚀 Quick Build (5 Minutes)

### Prerequisites
- Node.js 14+
- npm 6+
- Git

### One-Line Build Command
```bash
chmod +x build-release.sh && ./build-release.sh
```

That's it! The script will:
1. Clean old artifacts
2. Install dependencies
3. Build blocks
4. Create package
5. Generate checksums

---

## 📦 What You Get

### Output Files:
- `eipsi-forms-1.1.0.zip` - **Distribution package (201 KB)**
- `release-metadata-1.1.0.json` - Version metadata & checksums

### Package Contains:
- ✅ Compiled blocks (`build/` directory)
- ✅ Source code (`src/` directory)
- ✅ Production assets (`assets/` directory)
- ✅ Admin panel (`admin/` directory)
- ✅ Block definitions (`blocks/` directory)
- ✅ Documentation (`README.md`, `CHANGES.md`, `LICENSE`)

### Package Excludes:
- ❌ Development files (`node_modules/`, `.git/`)
- ❌ Test files (`test-*.js`, `test-*.html`)
- ❌ Documentation (58 audit/test MD files)
- ❌ Build scripts

---

## ✅ Quick Verification (2 Minutes)

### Check Package:
```bash
# View metadata
cat release-metadata-1.1.0.json

# Verify size (~200 KB)
ls -lh eipsi-forms-1.1.0.zip

# List contents
unzip -l eipsi-forms-1.1.0.zip | less

# Verify build directory included
unzip -l eipsi-forms-1.1.0.zip | grep "build/"

# Check no dev files
unzip -l eipsi-forms-1.1.0.zip | grep -E "(node_modules|test-|\.git/)"
# Should return nothing
```

---

## 🧪 Quick Smoke Test (20 Minutes)

### 1. Install Plugin
```bash
# Extract to WordPress plugins directory
unzip eipsi-forms-1.1.0.zip -d /path/to/wordpress/wp-content/plugins/

# Or use WP-CLI
wp plugin install eipsi-forms-1.1.0.zip --activate
```

### 2. Verify Functionality
- ✅ Plugin activates without errors
- ✅ "VAS Forms" menu appears in WordPress admin
- ✅ "EIPSI Forms" blocks appear in block inserter (11 blocks)
- ✅ Create test form and publish
- ✅ Submit form on frontend
- ✅ View response in admin dashboard
- ✅ Export to Excel works

---

## 📁 Key Files Reference

| File | Purpose |
|------|---------|
| `build-release.sh` | Main build script |
| `.distignore` | Exclusion rules |
| `eipsi-forms-1.1.0.zip` | Distribution package |
| `release-metadata-1.1.0.json` | Build metadata |
| `RELEASE_PACKAGE_DOCUMENTATION.md` | Full documentation |
| `SMOKE_TEST_PROCEDURES.md` | Testing guide |
| `RELEASE_VERIFICATION_REPORT.md` | Build verification |

---

## 🔧 Troubleshooting

### Build fails?
```bash
# Clean everything
rm -rf build/ node_modules/ dist/

# Try again
./build-release.sh
```

### Package too large?
```bash
# Check what's included
unzip -l eipsi-forms-1.1.0.zip

# Look for node_modules or .git
unzip -l eipsi-forms-1.1.0.zip | grep -E "(node_modules|\.git/)"
```

### Build directory missing?
```bash
# Verify .distignore doesn't exclude build/
grep "^build$" .distignore
# Should return nothing

# Check build exists before packaging
ls -la build/
```

---

## 📚 Full Documentation

For detailed information, see:
- **Build Process:** `RELEASE_PACKAGE_DOCUMENTATION.md`
- **Testing:** `SMOKE_TEST_PROCEDURES.md`
- **Verification:** `RELEASE_VERIFICATION_REPORT.md`

---

## ✨ Quick Commands Cheat Sheet

```bash
# Build package
./build-release.sh

# View metadata
cat release-metadata-1.1.0.json

# Verify checksums
md5sum -c eipsi-forms-1.1.0.zip.md5
sha256sum -c eipsi-forms-1.1.0.zip.sha256

# Extract and inspect
unzip eipsi-forms-1.1.0.zip -d /tmp/inspect
tree /tmp/inspect/eipsi-forms/ -L 2

# Clean everything
rm -rf build/ node_modules/ dist/ eipsi-forms-*.zip release-metadata-*.json
```

---

**Version:** 1.0  
**Last Updated:** 2025-11-10
