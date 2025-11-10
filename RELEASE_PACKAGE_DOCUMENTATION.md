# EIPSI Forms - Release Package Documentation

## Overview
This document provides complete instructions for building, packaging, validating, and distributing the EIPSI Forms WordPress plugin.

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Build Process](#build-process)
3. [Package Contents](#package-contents)
4. [Verification Steps](#verification-steps)
5. [Smoke Testing](#smoke-testing)
6. [Distribution](#distribution)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software
- **Node.js**: v14 or higher
- **npm**: v6 or higher
- **Git**: For version control
- **Zip utility**: For creating archives
- **rsync**: For file copying (usually pre-installed on Linux/macOS)

### Optional Tools
- **WP-CLI**: For automated packaging (recommended)
- **WordPress Test Environment**: For smoke testing

### Verification
```bash
# Check installed versions
node --version    # Should be v14+
npm --version     # Should be v6+
git --version
zip --version
rsync --version

# Optional: Check WP-CLI
wp --version
```

---

## Build Process

### Method 1: Automated Build Script (Recommended)

#### Step 1: Prepare Environment
```bash
cd /path/to/eipsi-forms-plugin
git status  # Ensure clean working directory
```

#### Step 2: Run Build Script
```bash
chmod +x build-release.sh
./build-release.sh
```

#### Step 3: Expected Output
```
============================================
EIPSI Forms - Release Package Builder
============================================
Version: 1.1.0
Build Date: 2025-01-10 14:30:00

Step 1: Cleaning old build artifacts...
✓ Cleaned

Step 2: Installing npm dependencies with npm ci...
✓ Dependencies installed

Step 3: Building Gutenberg blocks...
✓ Blocks compiled

Step 4: Verifying build output...
✓ Build output verified

Step 5: Checking for wp-cli...
⚠ wp-cli not found, will use manual method

Step 6: Creating distribution package...
Using .distignore for exclusions...
✓ Package created: eipsi-forms-1.1.0.zip

Step 7: Verifying package contents...
✓ No excluded files found

Step 8: Generating checksums...
✓ MD5: a1b2c3d4e5f6...
✓ SHA256: 1a2b3c4d5e6f...

Step 9: Creating release metadata...
✓ Metadata saved to: release-metadata-1.1.0.json

Step 10: Summary
============================================
Build Summary
============================================
Package: eipsi-forms-1.1.0.zip
Size: 1.8M (1887436 bytes)
Files: 245
MD5: a1b2c3d4e5f6...
SHA256: 1a2b3c4d5e6f...

✓ Release package is ready!

Next steps:
1. Review metadata: cat release-metadata-1.1.0.json
2. Test installation: unzip eipsi-forms-1.1.0.zip -d /path/to/wordpress/wp-content/plugins/
3. Run smoke tests (see SMOKE_TEST_PROCEDURES.md)
4. Upload to WordPress.org or distribute
```

#### Step 4: Review Metadata
```bash
cat release-metadata-1.1.0.json
```

Expected output:
```json
{
  "plugin": "EIPSI Forms",
  "slug": "eipsi-forms",
  "version": "1.1.0",
  "archive": "eipsi-forms-1.1.0.zip",
  "buildDate": "2025-01-10 14:30:00 UTC",
  "size": {
    "bytes": 1887436,
    "human": "1.8M"
  },
  "fileCount": 245,
  "checksums": {
    "md5": "a1b2c3d4e5f6...",
    "sha256": "1a2b3c4d5e6f..."
  },
  "requirements": {
    "wordpress": "5.8+",
    "php": "7.4+",
    "gutenberg": true
  },
  "verification": {
    "excludedFilesCheck": true,
    "buildOutputVerified": true
  }
}
```

---

### Method 2: Manual Build Process

If the automated script fails, follow these manual steps:

#### Step 1: Clean Old Artifacts
```bash
rm -rf build/
rm -rf node_modules/
rm -rf dist/
rm -f eipsi-forms-*.zip
```

#### Step 2: Install Dependencies
```bash
npm ci
```

**Important:** Use `npm ci` (not `npm install`) for reproducible builds.

#### Step 3: Build Blocks
```bash
npm run build
```

#### Step 4: Verify Build Output
```bash
ls -la build/
```

Expected files:
- `index.js` (main bundle, ~80KB)
- `index.css` (editor styles, ~30KB)
- `index-rtl.css` (RTL editor styles)
- `style-index.css` (frontend styles, ~17KB)
- `style-index-rtl.css` (RTL frontend styles)
- `index.asset.php` (asset metadata)

#### Step 5: Create Distribution Directory
```bash
mkdir -p dist/eipsi-forms
```

#### Step 6: Copy Files Using .distignore
```bash
rsync -av --exclude-from=.distignore . dist/eipsi-forms/
```

#### Step 7: Create Zip Archive
```bash
cd dist
zip -r ../eipsi-forms-1.1.0.zip eipsi-forms/
cd ..
```

#### Step 8: Generate Checksums
```bash
md5sum eipsi-forms-1.1.0.zip
sha256sum eipsi-forms-1.1.0.zip
```

#### Step 9: Clean Up
```bash
rm -rf dist/
```

---

## Package Contents

### Directory Structure

```
eipsi-forms/
├── admin/                    # Admin panel functionality
│   ├── ajax-handlers.php
│   ├── export.php
│   ├── handlers.php
│   ├── menu.php
│   └── results-page.php
├── assets/                   # Production assets
│   ├── css/
│   │   ├── admin-style.css
│   │   └── eipsi-forms.css  # Main stylesheet (~1400 lines)
│   └── js/
│       ├── admin-script.js
│       ├── eipsi-forms.js   # Frontend logic
│       └── eipsi-tracking.js # Analytics
├── blocks/                   # Block definitions
│   ├── campo-descripcion/
│   ├── campo-likert/
│   ├── campo-multiple/
│   ├── campo-radio/
│   ├── campo-select/
│   ├── campo-texto/
│   ├── campo-textarea/
│   ├── form-block/
│   ├── form-container/
│   ├── pagina/
│   └── vas-slider/
│   └── [each contains block.json, index.php]
├── build/                    # Compiled blocks
│   ├── index.js
│   ├── index.css
│   ├── index.asset.php
│   ├── style-index.css
│   └── [RTL versions]
├── languages/                # Translation files
├── lib/                      # Third-party libraries
├── src/                      # Source code for blocks
│   ├── blocks/
│   │   └── [block source: edit.js, save.js, style.scss, editor.scss]
│   ├── components/
│   │   ├── ConditionalLogicControl.js
│   │   └── FormStylePanel.js
│   └── utils/
│       ├── contrastChecker.js
│       ├── stylePresets.js
│       └── styleTokens.js
├── .wordpress-org/           # WordPress.org assets
│   ├── banner-772x250.svg
│   └── icon-256x256.svg
├── .distignore              # Packaging exclusion rules
├── CHANGES.md               # Changelog
├── LICENSE                  # GPL v2+ license
├── package.json             # Build configuration
├── README.md                # User documentation
└── vas-dinamico-forms.php   # Main plugin file
```

### Excluded from Distribution

The following files/directories are **NOT** included in the distribution package (per `.distignore`):

#### Development Files
- `node_modules/` (150-200 MB)
- `package-lock.json`
- `.git/` directory
- `.gitignore`, `.gitattributes`
- `.wp-env.json`

#### Test Files
- `test-*.js`, `test-*.html`, `test-*.sh`
- `wcag-contrast-validation.js`
- `tracking-queries.sql`

#### Documentation (58 files)
- All `*_AUDIT_*.md` files
- All `*_TEST*.md` files  
- All `*_IMPLEMENTATION*.md` files
- All `*_GUIDE.md` files
- `DISTRIBUTION_*.md` files
- `SMOKE_TEST_PROCEDURES.md`
- `RELEASE_PACKAGE_DOCUMENTATION.md`

#### Build Scripts
- `build-release.sh`
- `deploy.sh`, `release.sh`
- `release-metadata-*.json`

#### OS/IDE Artifacts
- `.DS_Store`, `Thumbs.db`
- `.vscode/`, `.idea/`
- `*.swp`, `*.swo`, `*.log`

### Size Comparison

| Category | Before Cleanup | After Cleanup | Savings |
|----------|----------------|---------------|---------|
| Development Files | 150-200 MB | 0 | ~100% |
| Documentation | 1.5 MB | 0 | 100% |
| Test Files | 200 KB | 0 | 100% |
| **Total Package** | 5-10 MB | **~1.8 MB** | **75-80%** |

---

## Verification Steps

### 1. Package Size Check
```bash
du -h eipsi-forms-1.1.0.zip
```
**Expected:** ~1.5-2.0 MB

### 2. File Count Verification
```bash
unzip -l eipsi-forms-1.1.0.zip | tail -n 1
```
**Expected:** ~200-300 files

### 3. Excluded Files Check

Check that no development files are included:

```bash
# Should return no results
unzip -l eipsi-forms-1.1.0.zip | grep -E "test-.*\.(js|html|sh)"
unzip -l eipsi-forms-1.1.0.zip | grep -E "AUDIT.*\.md"
unzip -l eipsi-forms-1.1.0.zip | grep "\.git/"
unzip -l eipsi-forms-1.1.0.zip | grep "node_modules/"
unzip -l eipsi-forms-1.1.0.zip | grep "package-lock.json"
```

### 4. Required Files Check

Verify essential files are present:

```bash
# Should find these files
unzip -l eipsi-forms-1.1.0.zip | grep "vas-dinamico-forms.php"
unzip -l eipsi-forms-1.1.0.zip | grep "README.md"
unzip -l eipsi-forms-1.1.0.zip | grep "LICENSE"
unzip -l eipsi-forms-1.1.0.zip | grep "build/index.js"
unzip -l eipsi-forms-1.1.0.zip | grep "build/index.asset.php"
unzip -l eipsi-forms-1.1.0.zip | grep "assets/css/eipsi-forms.css"
unzip -l eipsi-forms-1.1.0.zip | grep "assets/js/eipsi-forms.js"
```

### 5. Checksum Verification

Store checksums for distribution:

```bash
# Generate checksums
md5sum eipsi-forms-1.1.0.zip > eipsi-forms-1.1.0.zip.md5
sha256sum eipsi-forms-1.1.0.zip > eipsi-forms-1.1.0.zip.sha256

# Verify later
md5sum -c eipsi-forms-1.1.0.zip.md5
sha256sum -c eipsi-forms-1.1.0.zip.sha256
```

### 6. Extract and Inspect

```bash
# Extract to temporary location
mkdir -p /tmp/eipsi-verify
unzip eipsi-forms-1.1.0.zip -d /tmp/eipsi-verify

# Verify structure
ls -la /tmp/eipsi-verify/eipsi-forms/

# Check main plugin file
head -n 20 /tmp/eipsi-verify/eipsi-forms/vas-dinamico-forms.php

# Clean up
rm -rf /tmp/eipsi-verify
```

---

## Smoke Testing

### Quick Test (20 minutes)

See `SMOKE_TEST_PROCEDURES.md` for detailed instructions.

#### 1. Install on Test WordPress Site
```bash
# Copy to WordPress plugins directory
unzip eipsi-forms-1.1.0.zip -d /path/to/wordpress/wp-content/plugins/

# Or use WP-CLI
wp plugin install eipsi-forms-1.1.0.zip --activate
```

#### 2. Verify Installation
- ✅ Plugin activates without errors
- ✅ Admin menu shows "VAS Forms"
- ✅ Database tables created
- ✅ No PHP warnings or notices

#### 3. Test Block Editor
- ✅ "EIPSI Forms" category appears in block inserter
- ✅ All 11 blocks available
- ✅ Form Container adds successfully
- ✅ Customization panel functional
- ✅ No JavaScript console errors

#### 4. Test Frontend
- ✅ Form renders correctly on published page
- ✅ Responsive design works (320px, 768px, 1280px)
- ✅ Customization styles apply
- ✅ No console errors

#### 5. Test Submission
- ✅ Form submits via AJAX
- ✅ Data saves to database
- ✅ Admin dashboard shows response
- ✅ Excel export works

#### 6. Document Results

Create file: `smoke-test-results-1.1.0.md`

```markdown
# Smoke Test Results - EIPSI Forms v1.1.0

**Date:** 2025-01-10
**Tester:** [Name]
**Environment:** WordPress 6.4, PHP 8.1, Chrome 120

## Test Summary
- ✅ Installation: PASS
- ✅ Block Editor: PASS
- ✅ Frontend Rendering: PASS
- ✅ Form Submission: PASS
- ✅ Admin Dashboard: PASS

## Issues Found
None

## Status: ✅ APPROVED FOR RELEASE
```

---

## Distribution

### WordPress.org Submission

#### 1. Prepare Assets
- ✅ Plugin zip: `eipsi-forms-1.1.0.zip`
- ✅ Screenshots (in `.wordpress-org/` or separate)
- ✅ Banner: `banner-772x250.svg` (or PNG)
- ✅ Icon: `icon-256x256.svg` (or PNG)
- ✅ `README.txt` (WordPress.org format)

#### 2. Update README.txt
WordPress.org requires a specific format:

```
=== EIPSI Forms ===
Contributors: [username]
Tags: forms, survey, gutenberg, blocks, clinical-research
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional form builder with Gutenberg blocks for research and surveys.

== Description ==
[Copy from README.md]

== Installation ==
[Installation instructions]

== Frequently Asked Questions ==
[FAQ items]

== Screenshots ==
1. Block editor with EIPSI Forms
2. Customization panel
3. Frontend form rendering
4. Admin dashboard

== Changelog ==
= 1.1.0 =
* Initial release
[Copy from CHANGES.md]
```

#### 3. Submit to WordPress.org
1. Log in to WordPress.org
2. Navigate to: https://wordpress.org/plugins/developers/add/
3. Upload `eipsi-forms-1.1.0.zip`
4. Fill out plugin details
5. Submit for review

#### 4. SVN Commit (After Approval)
```bash
# Checkout SVN repository
svn co https://plugins.svn.wordpress.org/eipsi-forms

# Add new version
cd eipsi-forms
cp /path/to/eipsi-forms-1.1.0.zip trunk/
svn add trunk/*

# Tag release
svn cp trunk tags/1.1.0

# Commit
svn ci -m "Release version 1.1.0"
```

---

### Direct Distribution

#### 1. Host on Your Server
```bash
# Upload to web server
scp eipsi-forms-1.1.0.zip user@server:/var/www/downloads/

# Create download page with checksums
echo "MD5: $(cat eipsi-forms-1.1.0.zip.md5)" >> download-page.html
echo "SHA256: $(cat eipsi-forms-1.1.0.zip.sha256)" >> download-page.html
```

#### 2. GitHub Release
```bash
# Tag release in Git
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0

# Create GitHub release
# - Go to: https://github.com/[user]/[repo]/releases/new
# - Select tag: v1.1.0
# - Upload: eipsi-forms-1.1.0.zip
# - Attach: release-metadata-1.1.0.json
# - Add release notes from CHANGES.md
```

#### 3. Self-Hosted Update Server

For private distribution with auto-updates, use plugins like:
- **Plugin Update Checker** by YahnisElsts
- **WP Update Server**

---

## Troubleshooting

### Build Errors

#### Error: "npm: command not found"
**Solution:**
```bash
# Install Node.js and npm
# Ubuntu/Debian:
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs

# macOS:
brew install node

# Verify
node --version
npm --version
```

#### Error: "build/index.js not found"
**Solution:**
```bash
# Clean and rebuild
rm -rf build/ node_modules/
npm ci
npm run build

# Check for errors in output
```

#### Error: "npm ERR! code ELIFECYCLE"
**Solution:**
```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules and reinstall
rm -rf node_modules/
npm ci
```

---

### Packaging Errors

#### Error: "rsync: command not found"
**Solution:**
```bash
# Install rsync
# Ubuntu/Debian:
sudo apt-get install rsync

# macOS: (usually pre-installed)
brew install rsync
```

#### Warning: "Test files found in package"
**Solution:**
```bash
# Verify .distignore file exists
cat .distignore

# Manually exclude test files
rm -rf dist/eipsi-forms/test-*
```

#### Error: "Package too large (>5MB)"
**Solution:**
```bash
# Check for node_modules
unzip -l eipsi-forms-1.1.0.zip | grep node_modules

# If found, rebuild package ensuring .distignore is respected
./build-release.sh
```

---

### Installation Errors

#### Error: "The plugin does not have a valid header"
**Solution:**
- Ensure `vas-dinamico-forms.php` is in root of zip
- Verify plugin header format:
  ```php
  <?php
  /**
   * Plugin Name: EIPSI Forms
   * Version: 1.1.0
   * ...
   */
  ```

#### Error: "Missing dependency: @wordpress/blocks"
**Solution:**
- Rebuild with clean dependencies:
  ```bash
  rm -rf node_modules/ build/
  npm ci
  npm run build
  ./build-release.sh
  ```

#### Error: "Database table creation failed"
**Solution:**
- Check WordPress database user has CREATE TABLE permissions
- Verify `wp_vas_form_results` and `wp_vas_form_events` don't exist
- Manually run SQL from plugin activation hook

---

## Release Checklist

Final checklist before distribution:

### Pre-Build
- [ ] All code changes committed to Git
- [ ] Version number updated in:
  - [ ] `vas-dinamico-forms.php` (header + constant)
  - [ ] `package.json`
  - [ ] `README.md`
- [ ] `CHANGES.md` updated with release notes
- [ ] All tests passing
- [ ] Documentation reviewed

### Build
- [ ] Clean build completed successfully
- [ ] `build/` directory populated correctly
- [ ] No build warnings or errors

### Package
- [ ] Distribution zip created
- [ ] Package size reasonable (~1.5-2 MB)
- [ ] Checksums generated (MD5, SHA256)
- [ ] Metadata file created
- [ ] No excluded files in package
- [ ] All required files present

### Verification
- [ ] Package extracted successfully
- [ ] Main plugin file readable
- [ ] `build/` directory included
- [ ] `assets/` directory included
- [ ] `.distignore` NOT included in package
- [ ] Test files NOT included
- [ ] Documentation NOT included (dev docs)

### Smoke Testing
- [ ] Clean WordPress installation prepared
- [ ] Plugin installs without errors
- [ ] Plugin activates successfully
- [ ] Database tables created
- [ ] Blocks appear in editor
- [ ] Form renders on frontend
- [ ] Submission works
- [ ] Admin dashboard functional
- [ ] Export works
- [ ] No console errors
- [ ] Responsive design verified

### Documentation
- [ ] Smoke test results documented
- [ ] Screenshots captured
- [ ] Known issues noted (if any)
- [ ] Release notes finalized

### Distribution
- [ ] Package uploaded to distribution server
- [ ] Checksums published
- [ ] Download link tested
- [ ] WordPress.org submission (if applicable)
- [ ] GitHub release created (if applicable)
- [ ] Announcement prepared

---

## Support and Maintenance

### Post-Release Monitoring

#### Week 1: Critical Monitoring
- Monitor error logs
- Check support tickets/issues
- Verify download counts
- Test on latest WordPress version

#### Month 1: Stability Monitoring
- Collect user feedback
- Track compatibility issues
- Plan hotfix if needed

#### Ongoing: Maintenance
- Security updates
- WordPress compatibility updates
- Feature requests evaluation

### Hotfix Process

If critical bug found after release:

1. **Identify Issue**
   - Reproduce bug
   - Assess severity
   - Determine if hotfix needed

2. **Fix and Test**
   - Create fix branch
   - Implement fix
   - Test thoroughly
   - Update version (e.g., 1.1.0 → 1.1.1)

3. **Release Hotfix**
   - Update `CHANGES.md`
   - Run build process
   - Fast-track smoke testing
   - Distribute immediately

4. **Notify Users**
   - Announce hotfix
   - Document changes
   - Encourage updates

---

## Appendices

### A. Build Commands Reference

```bash
# Clean workspace
rm -rf build/ node_modules/ dist/

# Install dependencies (reproducible)
npm ci

# Build for production
npm run build

# Build for development (with watch)
npm run start

# Lint JavaScript
npm run lint:js

# Format code
npm run format
```

### B. Directory Size Reference

```bash
# Check directory sizes
du -sh admin/      # ~20 KB
du -sh assets/     # ~150 KB
du -sh blocks/     # ~30 KB
du -sh build/      # ~200 KB
du -sh lib/        # ~500 KB (if libraries included)
du -sh src/        # ~100 KB
du -sh languages/  # ~10 KB
```

### C. File Permissions

Correct permissions for distribution:
```bash
# Directories: 755 (rwxr-xr-x)
find eipsi-forms/ -type d -exec chmod 755 {} \;

# Files: 644 (rw-r--r--)
find eipsi-forms/ -type f -exec chmod 644 {} \;

# PHP files: 644 (not executable)
find eipsi-forms/ -name "*.php" -exec chmod 644 {} \;
```

### D. WordPress Requirements Matrix

| WordPress Version | PHP Version | Plugin Compatibility |
|-------------------|-------------|----------------------|
| 6.7 | 8.0+ | ✅ Fully Compatible |
| 6.4 - 6.6 | 7.4+ | ✅ Fully Compatible |
| 6.0 - 6.3 | 7.4+ | ✅ Compatible |
| 5.8 - 5.9 | 7.4+ | ⚠️ Minimal Testing |
| < 5.8 | < 7.4 | ❌ Not Supported |

---

## Document Information

**Version:** 1.0  
**Last Updated:** 2025-01-10  
**Maintained By:** EIPSI Forms Development Team  

**Related Documentation:**
- `SMOKE_TEST_PROCEDURES.md` - Detailed testing procedures
- `DISTRIBUTION_CHECKLIST.md` - Quick reference checklist
- `CHANGES.md` - Version history and changelog
- `README.md` - User-facing documentation
- `.distignore` - Package exclusion rules

**Quick Links:**
- Build Script: `build-release.sh`
- Main Plugin File: `vas-dinamico-forms.php`
- Package Configuration: `package.json`

---

**End of Documentation**
