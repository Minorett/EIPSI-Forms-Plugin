# EIPSI Forms - Smoke Testing Suite

**Quick Navigation:** [Quick Start](#quick-start) | [Documentation](#documentation) | [Automated Tests](#automated-tests) | [Manual Tests](#manual-tests) | [CI/CD](#cicd-integration)

---

## Overview

Comprehensive smoke testing suite for EIPSI Forms plugin Gutenberg editor integration. Ensures forms can be authored without errors, attributes persist correctly, and provides detailed test reporting.

### What's Included

- ✅ **Automated Puppeteer Tests** - 7 core scenarios (30-90s)
- ✅ **Manual Testing Checklist** - 150+ verification points (30-45m)
- ✅ **Infrastructure Validator** - Verify setup before testing
- ✅ **Comprehensive Documentation** - Guides, troubleshooting, FAQ
- ✅ **CI/CD Ready** - GitHub Actions examples included

---

## Quick Start

### 1. Prerequisites

```bash
# Ensure dependencies installed
npm install
```

### 2. Verify Setup

```bash
# Check infrastructure (30 seconds)
npm run test:editor:check
```

**Expected Output:** `✓ All infrastructure checks passed!`

### 3. Run Tests

```bash
# Build plugin
npm run build

# Start WordPress (first time: 2-3 min, subsequent: 30s)
npx @wordpress/env start

# Run smoke tests (30-90 seconds)
npm run test:editor
```

### 4. Review Results

```bash
# View report
cat EDITOR_SMOKE_TEST_REPORT.md

# View screenshots
ls test-screenshots/
```

---

## Documentation

### 📖 Core Documents

| Document | Purpose | Size | Audience |
|----------|---------|------|----------|
| **[Quick Start](EDITOR_SMOKE_TEST_QUICKSTART.md)** | 5-minute setup guide | 6.4KB | Developers |
| **[Comprehensive Guide](README_EDITOR_SMOKE_TEST.md)** | Complete reference | 13KB | Everyone |
| **[Manual Checklist](EDITOR_SMOKE_TEST_CHECKLIST.md)** | Step-by-step testing | 20KB | QA Engineers |
| **[Test Matrix](EDITOR_SMOKE_TEST_MATRIX.md)** | Coverage details | 17KB | Technical Leads |
| **[Implementation Summary](EDITOR_SMOKE_TEST_SUMMARY.md)** | Technical overview | 15KB | Developers |
| **[Deliverables](EDITOR_SMOKE_TEST_DELIVERABLES.md)** | What's included | 15KB | Project Managers |
| **[Completion Report](TICKET_EDITOR_SMOKE_COMPLETION.md)** | Ticket verification | 12KB | Stakeholders |

### 📊 Test Reports (Generated)

| Document | Purpose | When |
|----------|---------|------|
| **[Test Report](EDITOR_SMOKE_TEST_REPORT.md)** | Latest test results | After each run |
| **Screenshots** (`test-screenshots/`) | Visual evidence | During test runs |

### 🎯 Quick Reference

- **Need to run tests?** → See [Quick Start](#quick-start)
- **First time setup?** → Read [Quick Start Guide](EDITOR_SMOKE_TEST_QUICKSTART.md)
- **Troubleshooting?** → See [Comprehensive Guide](README_EDITOR_SMOKE_TEST.md) Section 7
- **Manual testing?** → Use [Manual Checklist](EDITOR_SMOKE_TEST_CHECKLIST.md)
- **Coverage questions?** → Check [Test Matrix](EDITOR_SMOKE_TEST_MATRIX.md)

---

## Automated Tests

### Test Scenarios

| # | Scenario | Duration | What It Tests |
|---|----------|----------|---------------|
| 1 | Form Container Insertion | 2-5s | Block inserter, rendering |
| 2 | Page Blocks Insertion | 3-8s | Nested blocks, 3+ pages |
| 3 | Field Blocks Insertion | 5-15s | All 7 field types |
| 4 | Conditional Logic | 2-5s | Panel, rules, validation |
| 5 | Style Panel | 2-5s | Presets, colors, preview |
| 6 | Editor Workflows | 3-8s | Undo/redo, List View |
| 7 | Persistence | 5-10s | Save/reload, attributes |
| **Total** | **Full Suite** | **30-90s** | **Comprehensive smoke test** |

### Running Tests

```bash
# Standard run (headless)
npm run test:editor

# Debug mode (visible browser, slow motion)
npm run test:editor:debug

# Infrastructure check only
npm run test:editor:check

# Custom WordPress site
WP_URL=https://staging.example.com \
WP_USERNAME=admin \
WP_PASSWORD=password \
npm run test:editor
```

### Understanding Results

#### ✅ Success
```
✓ Passed: 7
✗ Failed: 0
Console Errors: 0
```
→ All tests passed, no issues

#### ⚠️ Warnings
```
✓ Passed: 7
✗ Failed: 0
Console Errors: 2
```
→ Tests passed but console errors detected  
→ Review `EDITOR_SMOKE_TEST_REPORT.md` console section

#### ❌ Failure
```
✓ Passed: 5
✗ Failed: 2
Console Errors: 3
```
→ Some tests failed  
→ Check report for details  
→ View screenshots in `test-screenshots/`

---

## Manual Tests

### When to Use

- **First Time Testing** - Verify visual elements
- **Before Major Release** - Comprehensive validation
- **After Significant Changes** - Deep dive testing
- **Visual Verification** - Automated tests can't check appearance

### Quick Manual Test (5 minutes)

Use sections 1-3 of [Manual Checklist](EDITOR_SMOKE_TEST_CHECKLIST.md):

1. ✅ Insert Form Container and pages
2. ✅ Add a few fields
3. ✅ Save and reload

### Full Manual Test (30-45 minutes)

Complete sections 1-6 of [Manual Checklist](EDITOR_SMOKE_TEST_CHECKLIST.md):

1. Block Insertion (10 min)
2. Conditional Logic (10 min)
3. Form Style Panel (10 min)
4. Editor Workflows (5 min)
5. Console Monitoring (2 min)
6. Persistence (3 min)

### Stress Testing (Optional, 10 minutes)

Section 7 of checklist:
- Large forms (20+ fields)
- Rapid attribute changes
- Complex conditional logic

### Cross-Browser (Optional, 10 min per browser)

Section 8 of checklist:
- Chrome/Chromium ✅ (automated)
- Firefox (manual)
- Safari (manual)
- Edge (manual)

---

## CI/CD Integration

### GitHub Actions

```yaml
name: Editor Smoke Tests

on:
  pull_request:
    branches: [ main, develop ]
  push:
    branches: [ main ]

jobs:
  smoke-test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '20'
          
      - name: Install dependencies
        run: npm ci
        
      - name: Build plugin
        run: npm run build
        
      - name: Verify test infrastructure
        run: npm run test:editor:check
        
      - name: Start WordPress
        run: npx @wordpress/env start
        
      - name: Run smoke tests
        run: npm run test:editor
        
      - name: Upload test report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: smoke-test-report
          path: |
            EDITOR_SMOKE_TEST_REPORT.md
            test-screenshots/
            
      - name: Comment PR with results
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v6
        with:
          script: |
            const fs = require('fs');
            const report = fs.readFileSync('EDITOR_SMOKE_TEST_REPORT.md', 'utf8');
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: report
            });
```

### Pre-commit Hook

```bash
# .git/hooks/pre-commit
#!/bin/bash

echo "Running smoke tests..."
npm run build
npm run test:editor

if [ $? -ne 0 ]; then
    echo "❌ Smoke tests failed. Commit aborted."
    exit 1
fi

echo "✅ Smoke tests passed"
```

---

## Test Coverage

### Automated Coverage

| Area | Coverage | Status |
|------|----------|--------|
| Block Types | 90% | ✅ Excellent |
| Conditional Logic | 38% | ⚠️ Adequate |
| Style Panel | 24% | ⚠️ Adequate |
| Editor Workflows | 24% | ⚠️ Adequate |
| Persistence | 33% | ⚠️ Adequate |
| Console Monitoring | 63% | ✅ Good |
| **Overall** | **35%** | ✅ **Adequate for smoke testing** |

**Note:** Automated tests focus on critical integration points. Manual checklist provides 100% coverage.

### Manual Coverage

| Area | Coverage | Status |
|------|----------|--------|
| All Areas | 100% | ✅ Complete |

---

## Troubleshooting

### Common Issues

#### "Cannot connect to WordPress"

**Solution:**
```bash
# Check if WordPress is running
curl http://localhost:8888

# Restart wp-env
npx @wordpress/env stop
npx @wordpress/env start
```

#### "Block not found in inserter"

**Solution:**
```bash
# Rebuild plugin
npm run build

# Restart wp-env
npx @wordpress/env restart
```

#### "Test hangs"

**Solution:**
```bash
# Run in debug mode to see what's happening
npm run test:editor:debug
```

#### "Puppeteer not found"

**Solution:**
```bash
npm install
```

### More Help

See [Comprehensive Guide](README_EDITOR_SMOKE_TEST.md) Section 7 for detailed troubleshooting.

---

## Development Workflow

### Before Starting Work

```bash
# Verify baseline
npm run test:editor:check
```

### During Development

```bash
# After significant changes
npm run build
npm run test:editor:debug  # Watch it work
```

### Before Committing

```bash
# Final verification
npm run build
npm run lint:js --fix
npm run test:editor
```

### Before Release

```bash
# Full verification
npm run build
npm run test:editor

# Review report
cat EDITOR_SMOKE_TEST_REPORT.md

# Manual verification (sections 1-6, 30-45 min)
code EDITOR_SMOKE_TEST_CHECKLIST.md
```

---

## Performance Benchmarks

| Scenario | Target | Acceptable Max |
|----------|--------|----------------|
| Form Container Insertion | 2-5s | 10s |
| Page Blocks Insertion | 3-8s | 15s |
| Field Blocks Insertion | 5-15s | 30s |
| Conditional Logic | 2-5s | 10s |
| Style Panel | 2-5s | 10s |
| Editor Workflows | 3-8s | 15s |
| Persistence | 5-10s | 20s |
| **Full Suite** | **30-90s** | **120s** |

If tests exceed acceptable ranges, investigate:
- Network latency
- WordPress performance
- System resources

---

## NPM Scripts Reference

```bash
# Test commands
npm run test:editor          # Run automated tests (headless)
npm run test:editor:debug    # Run tests with visible browser
npm run test:editor:check    # Verify infrastructure only

# Build commands
npm run build                # Build plugin blocks
npm run start                # Watch mode for development

# Linting
npm run lint:js              # Check JavaScript
npm run lint:js --fix        # Auto-fix issues

# WordPress environment
npx @wordpress/env start     # Start WordPress
npx @wordpress/env stop      # Stop WordPress
npx @wordpress/env clean all # Clean and reset
```

---

## FAQ

**Q: How long do tests take?**  
A: Automated: 30-90s, Manual (full): 30-45m

**Q: Do I need to run manual tests every time?**  
A: No, automated tests are usually sufficient. Manual for major releases.

**Q: Can I run tests on a remote server?**  
A: Yes, set `WP_URL`, `WP_USERNAME`, `WP_PASSWORD` env vars.

**Q: What if I get false positives?**  
A: Run 2-3 times to rule out race conditions.

**Q: Can I add my own test scenarios?**  
A: Yes! Edit `test-editor-smoke.js`. See [Guide](README_EDITOR_SMOKE_TEST.md) for details.

**Q: Which browsers are tested?**  
A: Automated: Chrome/Chromium. Manual: Chrome, Firefox, Safari, Edge.

**Q: What WordPress versions are supported?**  
A: Tested on 6.4+. Compatible with 6.5, 6.6, 6.7.

**Q: How do I debug failing tests?**  
A: Use `npm run test:editor:debug` to watch execution.

---

## Related Documentation

### Core Plugin Documentation
- **Main README** - Plugin overview and features
- **MANUAL_TESTING_GUIDE.md** - General form testing
- **CONDITIONAL_FLOW_TESTING.md** - Conditional logic details
- **STYLE_PANEL_TESTING_GUIDE.md** - Style panel deep dive
- **NAVIGATION_UX_TEST_REPORT.md** - Frontend testing

### Testing Documentation
- **TESTING_GUIDE.md** - General testing overview
- **TEST_INDEX.md** - Index of all tests

---

## Support

### Getting Help

1. **Quick Start Issues** → [Quick Start Guide](EDITOR_SMOKE_TEST_QUICKSTART.md)
2. **Detailed Questions** → [Comprehensive Guide](README_EDITOR_SMOKE_TEST.md)
3. **Troubleshooting** → [Guide](README_EDITOR_SMOKE_TEST.md) Section 7
4. **Coverage Questions** → [Test Matrix](EDITOR_SMOKE_TEST_MATRIX.md)

### Reporting Issues

Include:
- Test report (`EDITOR_SMOKE_TEST_REPORT.md`)
- Screenshots from `test-screenshots/`
- Console errors
- Environment details (WordPress version, browser, OS)

---

## Contributing

### Adding Test Scenarios

1. Edit `test-editor-smoke.js`
2. Add test function
3. Call in `runSmokeTests()`
4. Update documentation

### Improving Documentation

1. Identify gaps or unclear sections
2. Update relevant markdown files
3. Test instructions work as expected
4. Submit PR with changes

---

## Changelog

### v1.0.0 (2024-11-09)

**Initial Release**
- ✅ 7 automated test scenarios
- ✅ 150+ manual test checkpoints
- ✅ Infrastructure validator
- ✅ 8 comprehensive documents
- ✅ CI/CD integration examples
- ✅ NPM scripts integration
- ✅ Complete test coverage matrix

---

## License

GPL v2 or later (same as EIPSI Forms plugin)

---

## Credits

**Developed by:** EIPSI Forms Development Team  
**Implementation Date:** 2024-11-09  
**Status:** Production Ready

---

**Quick Links:**
- [Quick Start](#quick-start)
- [Automated Tests](#automated-tests)
- [Manual Tests](#manual-tests)
- [CI/CD Integration](#cicd-integration)
- [Troubleshooting](#troubleshooting)
- [Documentation Index](#documentation)
