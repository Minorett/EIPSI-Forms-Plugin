# EIPSI Forms - Editor Smoke Test Quick Start

⚡ **5-Minute Setup Guide**

---

## Prerequisites

✓ Node.js 16+ installed  
✓ Plugin source code cloned  
✓ 5 minutes of time

---

## Step 1: Install Dependencies

```bash
cd /path/to/eipsi-forms-plugin
npm install
```

**Expected output:** `added X packages`

---

## Step 2: Build Plugin

```bash
npm run build
```

**Expected output:** `webpack compiled successfully`

---

## Step 3: Verify Test Infrastructure

```bash
npm run test:editor:check
```

**Expected output:**
```
✓ All infrastructure checks passed!
```

If you see any ✗ errors, follow the instructions to fix them.

---

## Step 4: Start WordPress (Option A - wp-env)

```bash
npx @wordpress/env start
```

**First run:** Downloads WordPress, may take 2-3 minutes  
**Subsequent runs:** Starts in ~30 seconds

**Once started:**
- WordPress: http://localhost:8888
- Admin: http://localhost:8888/wp-admin
- Username: `admin`
- Password: `password`

---

## Step 4: Start WordPress (Option B - Existing Site)

If you have WordPress running elsewhere:

```bash
# Set environment variables
export WP_URL="https://your-site.local"
export WP_USERNAME="your-admin"
export WP_PASSWORD="your-password"
```

---

## Step 5: Run Automated Tests

### Standard Run (Headless)
```bash
npm run test:editor
```

**Duration:** 30-90 seconds  
**Output:** Progress logs and final summary

### Debug Run (Visible Browser)
```bash
npm run test:editor:debug
```

**Duration:** 60-120 seconds (slower for visibility)  
**Use case:** Watch the test execute, debug failures

---

## Step 6: Review Results

```bash
# View the report
cat EDITOR_SMOKE_TEST_REPORT.md

# Or open in your editor
code EDITOR_SMOKE_TEST_REPORT.md
```

**Look for:**
- ✅ **Passed:** All scenarios passed
- ❌ **Failed:** Investigate failures
- 🔍 **Console Errors:** Check if any JavaScript errors

**Screenshots saved to:** `test-screenshots/`

---

## Step 7: Manual Verification (Optional)

```bash
# Open the checklist
code EDITOR_SMOKE_TEST_CHECKLIST.md
```

**When to do this:**
- First time running tests
- After major changes
- Before release
- When automated tests pass but you want extra confidence

**Duration:** 30-45 minutes for full checklist

---

## Common Commands

```bash
# Check infrastructure
npm run test:editor:check

# Run tests (headless)
npm run test:editor

# Run tests (visible browser, slow motion)
npm run test:editor:debug

# Build plugin
npm run build

# Start WordPress
npx @wordpress/env start

# Stop WordPress
npx @wordpress/env stop

# Clean and restart WordPress
npx @wordpress/env clean all
npx @wordpress/env start
```

---

## Interpreting Results

### ✅ All Tests Passed

```
✓ Passed: 7
✗ Failed: 0
○ Skipped: 0
Console Errors: 0
```

**Action:** You're good to go! ✨

### ❌ Some Tests Failed

```
✓ Passed: 5
✗ Failed: 2
○ Skipped: 0
Console Errors: 3
```

**Action:**
1. Check `EDITOR_SMOKE_TEST_REPORT.md` for details
2. Review screenshots in `test-screenshots/`
3. Look at console errors section
4. Try to reproduce manually

### ⚠️ Console Errors Detected

```
✓ Passed: 7
✗ Failed: 0
○ Skipped: 0
Console Errors: 2
```

**Action:**
1. Check if errors are critical (JavaScript exceptions vs. warnings)
2. Review console errors section in report
3. Fix underlying issues
4. Re-run tests

---

## Troubleshooting

### Issue: "Cannot connect to WordPress"

```
✗ Login failed or WordPress not accessible
```

**Solutions:**
1. Check WordPress is running: `curl http://localhost:8888`
2. Verify wp-env status: `npx @wordpress/env status`
3. Restart: `npx @wordpress/env stop && npx @wordpress/env start`

### Issue: "Block not found"

```
✗ Form Container block not found in editor
```

**Solutions:**
1. Rebuild plugin: `npm run build`
2. Restart wp-env: `npx @wordpress/env restart`
3. Check plugin is activated in wp-admin

### Issue: "Test hangs"

**Solutions:**
1. Stop with Ctrl+C
2. Run in debug mode: `npm run test:editor:debug`
3. Watch what the browser is doing
4. Check if selector needs updating

### Issue: "Puppeteer not found"

```
✗ Puppeteer NOT installed
```

**Solution:**
```bash
npm install
```

### Issue: "Build directory empty"

```
⚠ Build directory empty - run: npm run build
```

**Solution:**
```bash
npm run build
```

---

## Next Steps After Tests Pass

1. **Commit your changes** (if any)
   ```bash
   git add .
   git commit -m "Verify editor smoke tests pass"
   ```

2. **Run before every release**
   ```bash
   npm run build
   npm run test:editor
   ```

3. **Add to CI/CD** (see README_EDITOR_SMOKE_TEST.md for examples)

4. **Perform manual verification** for visual elements
   - Open checklist: `EDITOR_SMOKE_TEST_CHECKLIST.md`
   - Focus on sections 3 (Conditional Logic) and 3 (Style Panel)

---

## Full Documentation

- **README_EDITOR_SMOKE_TEST.md** - Complete guide (13KB)
- **EDITOR_SMOKE_TEST_CHECKLIST.md** - Manual checklist (20KB)
- **EDITOR_SMOKE_TEST_SUMMARY.md** - Implementation summary (15KB)

---

## Integration with Development Workflow

### Before Starting Work
```bash
# Verify baseline
npm run build
npm run test:editor:check
```

### During Development
```bash
# After each significant change
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
# Review EDITOR_SMOKE_TEST_REPORT.md
# Complete manual checklist sections 1-6
```

---

## FAQ

**Q: How long do tests take?**  
A: 30-90 seconds for automated tests, 30-45 minutes for full manual checklist.

**Q: Can I run tests on a remote server?**  
A: Yes, set `WP_URL`, `WP_USERNAME`, `WP_PASSWORD` environment variables.

**Q: Do I need to run manual tests every time?**  
A: No, automated tests are usually sufficient. Manual tests for major releases or visual verification.

**Q: What if I get false positives?**  
A: Run 2-3 times to rule out race conditions. Check WordPress/plugin versions match expectations.

**Q: Can I add my own test scenarios?**  
A: Yes! Edit `test-editor-smoke.js` and add new test functions. See README_EDITOR_SMOKE_TEST.md for details.

---

## Summary

```bash
# The essentials
npm install           # Once
npm run build         # After code changes
npm run test:editor   # Before commits
```

That's it! 🎉

---

**Need Help?** See README_EDITOR_SMOKE_TEST.md for comprehensive documentation.
