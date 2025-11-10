# EIPSI Forms - Build & Release Instructions

## Quick Start

### Prerequisites
- Node.js 14+ and npm
- (Optional) WP-CLI for automated packaging

### Standard Build Process
```bash
# 1. Clean previous builds
rm -rf build/ node_modules/

# 2. Install dependencies
npm install

# 3. Build blocks
npm run build

# 4. Verify build output
ls -la build/
```

---

## Creating Distribution Package

### Option 1: Automated Build Script (Recommended)
```bash
# Run the automated build script
./build-release.sh
```

This script will:
1. Clean old artifacts
2. Install dependencies
3. Build blocks
4. Create distribution package
5. Verify package contents
6. Report size and summary

**Output:** `vas-dinamico-forms-{version}.zip`

---

### Option 2: Manual with WP-CLI
```bash
# 1. Clean and build
rm -rf build/ node_modules/
npm install
npm run build

# 2. Create package
wp dist-archive . --plugin-dirname=vas-dinamico-forms

# 3. Verify package
unzip -l vas-dinamico-forms.zip | less
```

---

### Option 3: Manual with rsync
```bash
# 1. Clean and build
rm -rf build/ node_modules/
npm install
npm run build

# 2. Create clean copy
mkdir -p dist/vas-dinamico-forms
rsync -av --exclude-from=.distignore . dist/vas-dinamico-forms/

# 3. Create zip
cd dist
zip -r ../vas-dinamico-forms.zip vas-dinamico-forms/
cd ..

# 4. Clean up
rm -rf dist/
```

---

## Build Scripts (package.json)

### Development
```bash
npm run start          # Watch mode for development
npm run build          # Production build
npm run lint:js        # Lint JavaScript
npm run format         # Format code
```

### Testing
```bash
npm run test:editor              # Run editor smoke tests
npm run test:editor:debug        # Run tests with browser visible
npm run test:editor:check        # Dry run test check
```

---

## What Gets Excluded?

The `.distignore` file automatically excludes:

### Development Files
- `.git/` - Version control
- `node_modules/` - Dependencies
- `package-lock.json` - Lock file
- `.wp-env.json` - Dev environment
- `server.log` - Logs

### Test Files (10 files)
- `test-*.js`
- `test-*.html`
- `test-*.sh`
- `*.sql`
- `wcag-contrast-validation.js`

### Documentation (58 files)
- All `*_AUDIT_*.md`
- All `*_TEST*.md`
- All `*_IMPLEMENTATION*.md`
- All internal guides and reports
- **Exception:** `README.md`, `LICENSE`, `CHANGES.md` are kept

### Build Artifacts
- `build/` directory (regenerated fresh)

---

## What Gets Included?

### Core Plugin Files
- `vas-dinamico-forms.php` - Main plugin
- `admin/` - Admin functionality
- `assets/` - Production CSS/JS
- `blocks/` - Block definitions
- `lib/` - Libraries
- `languages/` - Translations

### Source Files (for rebuilding)
- `src/` - Block source code
- `package.json` - Build scripts

### Documentation
- `README.md` - User guide
- `LICENSE` - GPL license
- `CHANGES.md` - Changelog

### WordPress.org Assets
- `.wordpress-org/` - Banner and icon

---

## Verification Checklist

### Before Building:
- [ ] Update version in `vas-dinamico-forms.php`
- [ ] Update `CHANGES.md` with new version
- [ ] Commit all changes
- [ ] Tag release in Git (optional)

### After Building:
```bash
# Check package size (should be ~1-2 MB)
du -h vas-dinamico-forms-*.zip

# List contents
unzip -l vas-dinamico-forms-*.zip | less

# Verify no test files
unzip -l vas-dinamico-forms-*.zip | grep -i test
# Should return nothing

# Verify no audit docs
unzip -l vas-dinamico-forms-*.zip | grep -i audit
# Should return nothing

# Verify essential files present
unzip -l vas-dinamico-forms-*.zip | grep -E "(vas-dinamico-forms.php|README.md|LICENSE)"
# Should show these files
```

### Installation Testing:
```bash
# Extract to test location
unzip vas-dinamico-forms-*.zip -d /path/to/test/wp-content/plugins/

# Or use WP-CLI
wp plugin install vas-dinamico-forms-*.zip --activate
```

Then verify:
- [ ] Plugin activates without errors
- [ ] All blocks appear in editor
- [ ] Form creation works
- [ ] Form submission works
- [ ] Excel export works
- [ ] No JavaScript console errors
- [ ] Customization panel works
- [ ] Responsive behavior correct

---

## Troubleshooting

### Build fails with module errors
```bash
# Clean everything and reinstall
rm -rf node_modules/ package-lock.json build/
npm cache clean --force
npm install
npm run build
```

### Package too large (>5 MB)
```bash
# Check what's included
unzip -l vas-dinamico-forms-*.zip | sort -k4 -rn | head -20

# Common culprits:
# - node_modules/ included (check .distignore)
# - Documentation not excluded (check .distignore)
# - Extra log files (clean before building)
```

### Missing files in package
```bash
# Check .distignore doesn't over-exclude
cat .distignore

# Test rsync exclusions
rsync -avn --exclude-from=.distignore . /tmp/test/
```

### Build output not fresh
```bash
# Always clean before building
rm -rf build/
npm run build

# Check timestamps
ls -la build/
```

---

## Release Workflow

### Standard Release Process:

1. **Prepare Release**
   ```bash
   # Update version numbers
   # Update CHANGES.md
   git commit -am "Release v1.x.x"
   git tag v1.x.x
   ```

2. **Build Package**
   ```bash
   ./build-release.sh
   ```

3. **Test Package**
   ```bash
   # Install in test WordPress
   # Run through checklist
   # Verify all functionality
   ```

4. **Distribute**
   ```bash
   # Upload to WordPress.org
   # Or distribute via other channels
   ```

5. **Post-Release**
   ```bash
   git push origin main --tags
   # Update documentation
   # Announce release
   ```

---

## File Size Reference

| Component | Expected Size |
|-----------|---------------|
| Complete package | 1-2 MB |
| Core plugin files | ~500 KB |
| Source files | ~300 KB |
| Build output | ~200 KB |
| Documentation | ~35 KB |
| WordPress.org assets | ~2 KB |

**Note:** If package is significantly larger, check for:
- Included `node_modules/`
- Included developer documentation
- Included test files
- Included `.git/` directory

---

## Support & Resources

### Documentation
- `DISTRIBUTION_CLEANUP.md` - Detailed cleanup strategy
- `DISTRIBUTION_CHECKLIST.md` - Stakeholder approval checklist
- `DISTRIBUTION_INVENTORY.md` - Complete file inventory
- `.distignore` - Exclusion rules

### Build Tools
- `build-release.sh` - Automated build script
- `package.json` - NPM scripts
- `.distignore` - Packaging exclusions

### WordPress Resources
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WP-CLI](https://wp-cli.org/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-10  
**For:** EIPSI Forms v1.1.0+
