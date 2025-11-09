# EIPSI Forms - Editor Smoke Test Guide

## Overview

This guide covers both **automated** and **manual** smoke testing for the EIPSI Forms plugin's Gutenberg editor integration. Smoke tests verify core functionality works as expected before deeper regression testing.

## Test Deliverables

1. **`test-editor-smoke.js`** - Automated Puppeteer-based test script
2. **`EDITOR_SMOKE_TEST_CHECKLIST.md`** - Comprehensive manual testing checklist
3. **`EDITOR_SMOKE_TEST_REPORT.md`** - Generated report (after running automated tests)
4. **`test-screenshots/`** - Screenshots captured during automated testing

---

## Quick Start

### Prerequisites

1. **Node.js & npm** installed (v16+ recommended)
2. **Dependencies installed:**
   ```bash
   npm install
   ```
3. **Plugin built:**
   ```bash
   npm run build
   ```

### Option 1: Automated Testing with wp-env

**Step 1: Start WordPress environment**
```bash
npx @wordpress/env start
```

This will:
- Start WordPress 6.4 on http://localhost:8888
- Auto-install and activate the EIPSI Forms plugin
- Set up admin credentials: `admin` / `password`

**Step 2: Run automated smoke tests**
```bash
node test-editor-smoke.js
```

**Step 3: Review results**
```bash
# View the generated report
cat EDITOR_SMOKE_TEST_REPORT.md

# View screenshots
ls -lh test-screenshots/
```

### Option 2: Automated Testing with Custom WordPress

If you have WordPress running elsewhere:

```bash
WP_URL=https://your-site.local \
WP_USERNAME=your-admin \
WP_PASSWORD=your-password \
node test-editor-smoke.js
```

### Option 3: Manual Testing Only

If automated tests aren't feasible (e.g., local dev environment issues):

1. Open `EDITOR_SMOKE_TEST_CHECKLIST.md`
2. Follow each test scenario step-by-step
3. Check boxes as you complete each item
4. Document any failures using the provided template

---

## Automated Test Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `WP_URL` | `http://localhost:8888` | WordPress site URL |
| `WP_USERNAME` | `admin` | Admin username |
| `WP_PASSWORD` | `password` | Admin password |
| `HEADLESS` | `true` | Run browser in headless mode |
| `SLOW_MO` | `0` | Slow down actions by N milliseconds (for debugging) |

### Example: Debug Mode (Visible Browser)

```bash
HEADLESS=false SLOW_MO=100 node test-editor-smoke.js
```

This will:
- Show the browser window
- Slow down each action by 100ms
- Useful for watching the test execution

### Example: Custom WordPress Site

```bash
WP_URL=https://staging.example.com \
WP_USERNAME=staging-admin \
WP_PASSWORD=secure-password-123 \
HEADLESS=true \
node test-editor-smoke.js
```

---

## Test Scenarios Covered

### 1. Block Insertion ✓
- Form Container block
- Multiple Page blocks (3+)
- Mixed field types:
  - Text Field
  - Text Area
  - Select
  - Radio
  - Checkbox
  - Likert Scale
  - VAS Slider

### 2. Configuration Workflows ✓
- Inspector control rendering
- Attribute updates
- Toggle controls
- Field options management

### 3. Conditional Logic ✓
- Enable/disable conditional logic
- Add navigation rules
- Field dependency configuration
- Validation error detection (duplicates, empty values)

### 4. Form Style Panel ✓
- Theme preset selection
- Custom color modifications
- Preview instant updates
- CSS variable application
- No layout shifts during changes

### 5. Editor Workflows ✓
- Undo/Redo (Ctrl+Z / Ctrl+Shift+Z)
- List View navigation
- Block duplication
- Move blocks between pages
- Copy/paste operations

### 6. Persistence ✓
- Save post (Ctrl+S)
- Reload page
- Verify all attributes preserved:
  - `styleConfig`
  - `conditionalLogic`
  - Field attributes
  - Form settings

### 7. Console Monitoring ✓
- JavaScript errors
- React warnings
- Deprecated API warnings
- Performance issues

---

## Understanding Test Results

### Report Structure

The generated `EDITOR_SMOKE_TEST_REPORT.md` includes:

1. **Summary Statistics**
   - Total scenarios run
   - Passed/Failed/Skipped counts
   - Console error count

2. **Scenario Details**
   - Each test scenario with:
     - Status (passed/failed/skipped)
     - Duration (ms)
     - Detailed results (JSON)

3. **Console Errors Section**
   - All JavaScript errors captured
   - Timestamps and stack traces

4. **Console Warnings Section**
   - React-related warnings
   - Deprecated API usage

5. **Screenshots**
   - Saved to `test-screenshots/` directory
   - Timestamped filenames
   - Full-page captures

### Exit Codes

- **0** - All tests passed, no errors
- **1** - One or more tests failed OR console errors detected

---

## Troubleshooting

### Issue: "Cannot connect to WordPress"

**Solution:**
1. Verify WordPress is running:
   ```bash
   curl http://localhost:8888
   ```
2. Check wp-env status:
   ```bash
   npx @wordpress/env status
   ```
3. Restart wp-env:
   ```bash
   npx @wordpress/env stop
   npx @wordpress/env start
   ```

### Issue: "Block not found in inserter"

**Possible Causes:**
1. Plugin not activated
2. Build not completed
3. Block registration error

**Solution:**
1. Rebuild plugin:
   ```bash
   npm run build
   ```
2. Check wp-admin plugins page manually
3. Check browser console for registration errors

### Issue: "Test hangs during block insertion"

**Solution:**
1. Run in debug mode to see what's happening:
   ```bash
   HEADLESS=false SLOW_MO=500 node test-editor-smoke.js
   ```
2. Check selector timeouts in script
3. Verify block inserter UI hasn't changed in WordPress version

### Issue: "Screenshots not captured"

**Solution:**
1. Check `test-screenshots/` directory exists
2. Verify write permissions:
   ```bash
   mkdir -p test-screenshots
   chmod 755 test-screenshots
   ```

### Issue: "styleConfig not persisting"

**Debugging Steps:**
1. Open post in editor
2. Select Form Container block
3. Open browser DevTools Console
4. Run:
   ```javascript
   const blocks = wp.data.select('core/block-editor').getBlocks();
   const formBlock = blocks.find(b => b.name === 'vas-dinamico/form-container');
   console.log('styleConfig:', formBlock?.attributes?.styleConfig);
   ```
4. Should return object with 52 design tokens

---

## Manual Testing Workflow

When automated tests pass but you need deeper verification:

### Phase 1: Quick Smoke (5 minutes)
Follow sections 1-3 of `EDITOR_SMOKE_TEST_CHECKLIST.md`:
- Insert form and pages
- Add a few fields
- Save and reload

### Phase 2: Comprehensive (30 minutes)
Complete all sections 1-6 of the checklist:
- Full block coverage
- Conditional logic scenarios
- Style panel customization
- All editor workflows

### Phase 3: Stress Testing (15 minutes)
Section 7 of the checklist:
- Large forms (20+ fields)
- Rapid attribute changes
- Complex conditional logic

### Phase 4: Cross-Browser (10 minutes per browser)
Section 8 of the checklist:
- Chrome
- Firefox
- Safari (if Mac)
- Edge

---

## CI/CD Integration

### GitHub Actions Example

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
        
      - name: Start WordPress environment
        run: npx @wordpress/env start
        
      - name: Run smoke tests
        run: node test-editor-smoke.js
        
      - name: Upload test report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: smoke-test-report
          path: |
            EDITOR_SMOKE_TEST_REPORT.md
            test-screenshots/
```

---

## Extending the Test Suite

### Adding New Test Scenarios

Edit `test-editor-smoke.js` and add a new function:

```javascript
async function testMyNewFeature(page) {
    const startTime = Date.now();
    const scenarioName = 'My New Feature Test';
    
    try {
        // Your test logic here
        
        recordScenario(scenarioName, 'passed', Date.now() - startTime, { 
            customData: 'value' 
        });
        return true;
    } catch (error) {
        recordScenario(scenarioName, 'failed', Date.now() - startTime, { 
            error: error.message 
        });
        await takeScreenshot(page, 'my-new-feature-error');
        return false;
    }
}
```

Then call it in `runSmokeTests()`:

```javascript
await testMyNewFeature(page);
```

### Adding Manual Checklist Items

Edit `EDITOR_SMOKE_TEST_CHECKLIST.md` and add a new section:

```markdown
### 9. My New Feature

#### 9.1 Basic Functionality
- [ ] Test case 1
- [ ] Test case 2

#### 9.2 Edge Cases
- [ ] Edge case 1
```

---

## Best Practices

### When to Run Smoke Tests

1. **Before every release** (mandatory)
2. **After significant refactoring**
3. **When updating WordPress core dependency**
4. **When adding new blocks or components**
5. **After fixing critical bugs**

### Test Data Management

- Use consistent test data (form IDs, field names)
- Clean up test posts after manual testing
- Use unique identifiers (timestamps) to avoid conflicts

### Screenshot Organization

Screenshots are saved with format:
```
{scenario-name}-{timestamp}.png
```

Example:
```
form-container-inserted-1704123456789.png
```

### Reporting Issues

When tests fail:

1. **Review the generated report first**
2. **Check screenshots** in `test-screenshots/`
3. **Verify console errors** in report
4. **Reproduce manually** using checklist
5. **Document** with:
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details
   - Screenshots
   - Console logs

---

## Performance Benchmarks

Expected durations (automated tests):

| Scenario | Expected Duration | Acceptable Range |
|----------|------------------|------------------|
| Form Container Insertion | 2-5s | <10s |
| Page Blocks Insertion | 3-8s | <15s |
| Field Blocks Insertion | 5-15s | <30s |
| Conditional Logic Config | 2-5s | <10s |
| Style Panel Modification | 2-5s | <10s |
| Editor Workflows | 3-8s | <15s |
| Save and Reload | 5-10s | <20s |
| **Total Suite** | **20-60s** | **<120s** |

If tests exceed acceptable ranges, investigate:
- Network latency
- WordPress server performance
- Browser/Puppeteer overhead
- Complex form structures

---

## FAQ

### Q: Can I run tests without wp-env?

**A:** Yes, use a custom WordPress installation with the environment variables:
```bash
WP_URL=https://your-site.local \
WP_USERNAME=admin \
WP_PASSWORD=password \
node test-editor-smoke.js
```

### Q: Can I run tests in a real browser (non-headless)?

**A:** Yes:
```bash
HEADLESS=false node test-editor-smoke.js
```

### Q: How do I debug a failing test?

**A:** Use debug mode:
```bash
HEADLESS=false SLOW_MO=1000 node test-editor-smoke.js
```
This slows down actions by 1 second each and shows the browser.

### Q: What if the manual checklist is too long?

**A:** Use the Quick Smoke phase (sections 1-3, ~5 minutes) for routine checks. Full checklist for major releases.

### Q: Can I integrate this with my CI/CD pipeline?

**A:** Yes, see the GitHub Actions example above. The script exits with code 1 on failure, making it CI-friendly.

### Q: What if I get false positives?

**A:** 
1. Check WordPress version compatibility
2. Verify plugin built successfully
3. Clear browser cache/localStorage
4. Re-run test 2-3 times to rule out race conditions

### Q: How do I test on different WordPress versions?

**A:** Edit `.wp-env.json`:
```json
{
  "core": "WordPress/WordPress#6.5",
  "plugins": ["."],
  ...
}
```
Then restart:
```bash
npx @wordpress/env stop
npx @wordpress/env start
```

---

## Related Documentation

- **`STYLE_PANEL_TESTING_GUIDE.md`** - Detailed style panel testing procedures
- **`CONDITIONAL_FLOW_TESTING.md`** - Conditional logic testing scenarios
- **`MANUAL_TESTING_GUIDE.md`** - General manual testing procedures
- **`REVIEW_CHECKLIST.md`** - Code review checklist

---

## Support & Contribution

### Reporting Issues

File issues with:
- Test report (`EDITOR_SMOKE_TEST_REPORT.md`)
- Screenshots
- Console errors
- Environment details

### Contributing Test Cases

To add new test scenarios:
1. Add automated test function to `test-editor-smoke.js`
2. Add manual checklist items to `EDITOR_SMOKE_TEST_CHECKLIST.md`
3. Update this README with new scenario documentation
4. Submit PR with test results

---

**Last Updated:** 2024  
**Plugin Version:** 2.2.0  
**WordPress Version:** 6.4+
