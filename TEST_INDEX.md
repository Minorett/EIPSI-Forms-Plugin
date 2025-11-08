# Conditional Navigation Testing - Complete Index

## 📑 Document Overview

This index provides a complete map of all testing resources for the EIPSI Forms conditional navigation feature.

---

## 🎯 Start Here

### New to Testing?
1. Read: **[QUICK_TEST_REFERENCE.md](QUICK_TEST_REFERENCE.md)** (5 min)
2. Then: **[MANUAL_TESTING_GUIDE.md](MANUAL_TESTING_GUIDE.md)** (15 min)
3. Execute: Follow the guide to create and test forms

### Need Full Details?
1. Read: **[TESTING_COMPLETION_SUMMARY.md](TESTING_COMPLETION_SUMMARY.md)**
2. Reference: **[CONDITIONAL_FLOW_TESTING.md](CONDITIONAL_FLOW_TESTING.md)**
3. Use: **[test-conditional-flows.js](test-conditional-flows.js)** + **[test-report-generator.html](test-report-generator.html)**

### Reporting Results?
1. Open: **[test-report-generator.html](test-report-generator.html)** in browser
2. Complete: All test checklists
3. Export: JSON report for submission

---

## 📚 Documentation Files

### 1. Quick Reference Materials

| File | Lines | Purpose | Read Time |
|------|-------|---------|-----------|
| **QUICK_TEST_REFERENCE.md** | ~150 | Cheat sheet with console commands | 5 min |
| **TEST_INDEX.md** | ~200 | This file - navigation guide | 3 min |

### 2. Comprehensive Guides

| File | Lines | Purpose | Read Time |
|------|-------|---------|-----------|
| **TESTING_COMPLETION_SUMMARY.md** | ~600 | Project overview, status, workflow | 15 min |
| **CONDITIONAL_FLOW_TESTING.md** | ~800 | Full test specifications & procedures | 25 min |
| **MANUAL_TESTING_GUIDE.md** | ~850 | Step-by-step form creation & testing | 30 min |

### 3. Implementation References

| File | Lines | Purpose |
|------|-------|---------|
| **CONDITIONAL_LOGIC_GUIDE.md** | ~500 | Technical implementation details |
| **assets/js/eipsi-forms.js** | ~1754 | Frontend runtime code |
| **src/components/ConditionalLogicControl.js** | ~600 | Editor UI component |

---

## 🛠️ Testing Tools

### 1. Automated Test Script

**File:** `test-conditional-flows.js`  
**Type:** Browser-based JavaScript  
**Size:** ~700 lines  

**Features:**
- 7 automated test functions
- ConditionalNavigator state inspection
- JSON serialization validation
- Navigation simulation
- Summary report generation

**Usage:**
```javascript
// In browser console on form page
EIPSIConditionalTests.runAll()

// Or run specific test
EIPSIConditionalTests.run('navigator')
EIPSIConditionalTests.run('serialization')
EIPSIConditionalTests.run('history')
```

**Available Tests:**
- `navigator` - State properties verification
- `serialization` - JSON data validation
- `page` - Current page state checks
- `buttons` - Navigation button status
- `validation` - Field validation checks
- `history` - History stack integrity
- `navigation` - Simulated forward/backward

### 2. Visual Test Report

**File:** `test-report-generator.html`  
**Type:** Interactive HTML interface  
**Size:** ~550 lines (HTML + CSS + JS)  

**Features:**
- 4 test scenario checklists
- Interactive status badges
- Environment details form
- Issues and notes sections
- JSON export functionality
- Print-ready layout
- Real-time summary statistics

**Usage:**
1. Open file in browser
2. Complete test checklists
3. Fill environment details
4. Document issues/notes
5. Click "Export to JSON"

---

## 🧪 Test Scenarios

### Scenario 1: Linear Flow (Baseline)
**Form:** 4 pages, no conditional logic  
**Duration:** ~5 minutes  
**Purpose:** Verify standard pagination works correctly  

**Pages:**
1. Personal Information (text field)
2. Contact Details (text field)
3. Additional Comments (textarea)
4. Thank You (static)

**Expected:**
- Sequential navigation: 1→2→3→4
- History: `[1, 2, 3, 4]`
- SkippedPages: `[]`

**Guide:** See MANUAL_TESTING_GUIDE.md § Test Form 1

---

### Scenario 2: Single-Branch Flow
**Form:** 4 pages, 1 conditional rule (goToPage)  
**Duration:** ~10 minutes  
**Purpose:** Validate page jumping and skip tracking  

**Pages:**
1. Experience Level (radio: Yes/No + conditional)
2. Background Details (text area)
3. Experience Rating (radio)
4. Complete (static)

**Conditional Logic:**
```json
{
  "enabled": true,
  "rules": [
    {"matchValue": "Yes", "action": "goToPage", "targetPage": 3}
  ],
  "defaultAction": "nextPage"
}
```

**Expected:**
- Path A (Yes): 1→3→4, History: `[1, 3, 4]`, Skipped: `{2}`
- Path B (No): 1→2→3→4, History: `[1, 2, 3, 4]`, Skipped: `{}`

**Guide:** See MANUAL_TESTING_GUIDE.md § Test Form 2

---

### Scenario 3: Multi-Branch Flow
**Form:** 6 pages, 4 conditional rules (complex routing)  
**Duration:** ~15 minutes  
**Purpose:** Test multiple rules with different targets  

**Pages:**
1. Category Selection (select + conditional)
2. Research Background
3. Clinical Practice
4. Student Information
5. Other Details
6. Summary (convergence)

**Conditional Logic:**
```json
{
  "enabled": true,
  "rules": [
    {"matchValue": "Researcher", "action": "goToPage", "targetPage": 2},
    {"matchValue": "Clinician", "action": "goToPage", "targetPage": 3},
    {"matchValue": "Student", "action": "goToPage", "targetPage": 4},
    {"matchValue": "Other", "action": "goToPage", "targetPage": 5}
  ],
  "defaultAction": "nextPage"
}
```

**Expected:**
- Researcher: 1→2→6, Skipped: `{3, 4, 5}`
- Clinician: 1→3→6, Skipped: `{2, 4, 5}`
- Student: 1→4→6, Skipped: `{2, 3, 5}`
- Other: 1→5→6, Skipped: `{2, 3, 4}`

**Guide:** See MANUAL_TESTING_GUIDE.md § Test Form 3

---

### Scenario 4: Submit Shortcut
**Form:** 4 pages, submit action rule  
**Duration:** ~10 minutes  
**Purpose:** Validate early termination via submit action  

**Pages:**
1. Participation Consent (radio + conditional)
2. Informed Consent (checkbox)
3. Demographics (text)
4. Contact Information (text)

**Conditional Logic:**
```json
{
  "enabled": true,
  "rules": [
    {"matchValue": "No thanks", "action": "submit"}
  ],
  "defaultAction": "nextPage"
}
```

**Expected:**
- Path A (Yes): 1→2→3→4→submit
- Path B (No thanks): 1→submit, History: `[1]`, Skipped: `{2, 3, 4}`

**Guide:** See MANUAL_TESTING_GUIDE.md § Test Form 4

---

## 🔄 Testing Workflow

### Phase 1: Setup (10-15 min)
1. Build plugin assets: `npm run build`
2. Start WordPress environment
3. Activate EIPSI Forms plugin
4. Clear browser cache

### Phase 2: Create Forms (30-45 min)
1. Follow MANUAL_TESTING_GUIDE.md
2. Create Test Form 1 (Linear)
3. Create Test Form 2 (Single-Branch)
4. Create Test Form 3 (Multi-Branch)
5. Create Test Form 4 (Submit Shortcut)
6. Publish all forms

### Phase 3: Automated Testing (40-60 min)
For each form:
1. Load form on frontend
2. Open DevTools Console (F12)
3. Load test-conditional-flows.js
4. Run: `EIPSIConditionalTests.runAll()`
5. Review results
6. Document findings

### Phase 4: Manual Verification (60-80 min)
For each form:
1. Test different option paths
2. Verify page navigation
3. Check console state
4. Test backward navigation
5. Complete submission
6. Mark checklist in report generator

### Phase 5: Report (10-15 min)
1. Open test-report-generator.html
2. Complete all checklists
3. Fill environment details
4. Document issues/notes
5. Export JSON report
6. Capture screenshots if issues found

**Total Time: 2.5-3.5 hours**

---

## 💻 Console Commands Reference

### Get Navigator Instance
```javascript
const form = document.querySelector('.vas-dinamico-form');
const nav = window.EIPSIForms.conditionalNavigators.get(form);
```

### Check Current State
```javascript
console.log('History:', [...nav.history]);
console.log('Visited Pages:', [...nav.visitedPages]);
console.log('Skipped Pages:', [...nav.skippedPages]);
console.log('Active Path:', nav.getActivePath());
```

### Test Navigation Logic
```javascript
// Get next page for current page
const result = nav.getNextPage(1);
console.log('Next Action:', result);
// Returns: {action: 'goToPage', targetPage: 3} or {action: 'nextPage', targetPage: 2}

// Check if should submit
const shouldSubmit = nav.shouldSubmit(1);
console.log('Should Submit:', shouldSubmit);
```

### Inspect Conditional Fields
```javascript
// Find all fields with conditional logic
const fields = document.querySelectorAll('[data-conditional-logic]');
console.log('Conditional Fields:', fields.length);

// Parse logic for each field
fields.forEach(field => {
  const logic = JSON.parse(field.dataset.conditionalLogic);
  console.log('Field:', field.dataset.fieldName);
  console.log('Rules:', logic.rules);
});
```

### Run Automated Tests
```javascript
// Run all tests
const summary = await EIPSIConditionalTests.runAll();
console.log('Results:', summary);

// Run specific test
const navResults = await EIPSIConditionalTests.run('navigator');
const historyResults = await EIPSIConditionalTests.run('history');
```

---

## 🐛 Common Issues & Solutions

### Issue: EIPSIForms not defined
**Cause:** JavaScript not loaded  
**Solution:**
```javascript
// Check if loaded
console.log(window.EIPSIForms); // Should show object

// If undefined:
// 1. Hard refresh: Ctrl+Shift+R
// 2. Check Network tab for eipsi-forms.js
// 3. Verify plugin activated
```

### Issue: ConditionalNavigator not found
**Cause:** Navigator not initialized for form  
**Solution:**
```javascript
// Check navigators
console.log(window.EIPSIForms.conditionalNavigators);

// Check form initialization
const form = document.querySelector('.vas-dinamico-form');
console.log('Initialized:', form.dataset.initialized);

// Manual initialization if needed
window.EIPSIForms.initForm(form);
```

### Issue: Conditional logic not triggering
**Cause:** JSON parse error or value mismatch  
**Solution:**
```javascript
// Check field has logic
const field = document.querySelector('[data-conditional-logic]');
console.log('Has Logic:', field !== null);

// Verify JSON is valid
try {
  const logic = JSON.parse(field.dataset.conditionalLogic);
  console.log('Valid JSON:', logic);
} catch (e) {
  console.error('Invalid JSON:', e.message);
}

// Check matchValue matches exactly
const fieldValue = nav.getFieldValue(field);
console.log('Field Value:', fieldValue);
console.log('Rules:', logic.rules.map(r => r.matchValue));
```

### Issue: Navigation not working
**Cause:** Validation failing or button disabled  
**Solution:**
```javascript
// Check button state
const nextBtn = form.querySelector('.next-button');
console.log('Button Disabled:', nextBtn.disabled);

// Check validation errors
const errors = form.querySelectorAll('.form-error');
console.log('Errors:', errors.length);
errors.forEach(e => console.log('Error:', e.textContent));

// Check required fields
const required = form.querySelectorAll('[data-required="true"]');
console.log('Required Fields:', required.length);
```

---

## ✅ Acceptance Criteria Checklist

- [ ] **Environment Setup**
  - [ ] Plugin built successfully
  - [ ] WordPress running locally
  - [ ] Plugin activated
  - [ ] Browser DevTools accessible

- [ ] **Forms Created**
  - [ ] Test Form 1: Linear Flow
  - [ ] Test Form 2: Single-Branch
  - [ ] Test Form 3: Multi-Branch
  - [ ] Test Form 4: Submit Shortcut

- [ ] **Automated Tests**
  - [ ] Test script loads without errors
  - [ ] All 7 test functions execute
  - [ ] Summary report generated
  - [ ] No critical errors reported

- [ ] **Manual Verification**
  - [ ] All navigation paths tested
  - [ ] History state verified per path
  - [ ] SkippedPages set correctly
  - [ ] Backward navigation works
  - [ ] Submit actions trigger correctly

- [ ] **Documentation**
  - [ ] Test report completed
  - [ ] Issues documented with steps
  - [ ] Screenshots captured (if issues)
  - [ ] JSON report exported
  - [ ] Results submitted

---

## 📊 Expected Pass Criteria

### ✅ Tests Should Pass If:

1. **Navigator State:**
   - All properties exist (history, visitedPages, skippedPages)
   - Data types correct (Array, Set, Set)
   - Form reference valid

2. **Conditional Logic:**
   - JSON serializes correctly to DOM
   - All rules have required fields
   - Actions valid (goToPage, nextPage, submit)
   - MatchValues match option text exactly

3. **Navigation:**
   - Pages navigate as configured
   - History only includes visited pages
   - SkippedPages only includes bypassed pages
   - Page counter accurate
   - No overlap between visited and skipped

4. **State Consistency:**
   - Current page = last history entry
   - History grows on forward navigation
   - History shrinks on backward navigation
   - VisitedPages Set matches history

5. **User Experience:**
   - No console errors
   - No navigation dead ends
   - Validation works before navigation
   - Form submission successful

---

## 📁 File Structure

```
/home/engine/project/
├── Documentation/
│   ├── CONDITIONAL_FLOW_TESTING.md      # Full test specifications
│   ├── MANUAL_TESTING_GUIDE.md          # Step-by-step guide
│   ├── TESTING_COMPLETION_SUMMARY.md    # Project overview
│   ├── QUICK_TEST_REFERENCE.md          # Cheat sheet
│   └── TEST_INDEX.md                    # This file
│
├── Testing Tools/
│   ├── test-conditional-flows.js        # Automated test script
│   └── test-report-generator.html       # Visual report interface
│
├── Implementation/
│   ├── assets/js/eipsi-forms.js         # Frontend runtime
│   └── src/components/
│       └── ConditionalLogicControl.js   # Editor component
│
└── References/
    └── CONDITIONAL_LOGIC_GUIDE.md       # Technical details
```

---

## 🎓 Learning Path

### For QA Testers
1. ⏱️ 5 min: Read QUICK_TEST_REFERENCE.md
2. ⏱️ 30 min: Read MANUAL_TESTING_GUIDE.md
3. ⏱️ 45 min: Create test forms
4. ⏱️ 60 min: Execute tests
5. ⏱️ 15 min: Generate report

**Total: ~2.5 hours**

### For Developers
1. ⏱️ 15 min: Read TESTING_COMPLETION_SUMMARY.md
2. ⏱️ 25 min: Read CONDITIONAL_FLOW_TESTING.md
3. ⏱️ 20 min: Review test-conditional-flows.js code
4. ⏱️ 30 min: Review implementation (eipsi-forms.js)
5. ⏱️ 60 min: Execute tests + debug

**Total: ~2.5 hours**

### For Project Managers
1. ⏱️ 10 min: Read TESTING_COMPLETION_SUMMARY.md
2. ⏱️ 5 min: Review TEST_INDEX.md (this file)
3. ⏱️ 10 min: Check test-report-generator.html interface

**Total: ~25 minutes**

---

## 📞 Getting Help

### Questions About Testing Process?
- **Quick answers:** QUICK_TEST_REFERENCE.md
- **Detailed steps:** MANUAL_TESTING_GUIDE.md
- **Troubleshooting:** CONDITIONAL_FLOW_TESTING.md § Debugging

### Questions About Implementation?
- **Overview:** CONDITIONAL_LOGIC_GUIDE.md
- **Code details:** Review eipsi-forms.js lines 12-281
- **Editor UI:** Review ConditionalLogicControl.js

### Reporting Issues?
- **Template:** See MANUAL_TESTING_GUIDE.md § Issue Reporting
- **Format:** Use test-report-generator.html
- **Include:** Steps, expected vs actual, console logs, screenshots

---

## 🏆 Success Metrics

### Testing Complete When:
- ✅ All 4 test forms created
- ✅ Automated tests run (no crashes)
- ✅ Manual verification done (all paths)
- ✅ Report generated (JSON exported)
- ✅ Issues documented (if any found)

### Quality Indicators:
- **Pass Rate:** >90% of checks pass
- **Coverage:** All 4 scenarios tested
- **Documentation:** All issues have reproduction steps
- **State Integrity:** History/skip sets consistent

---

## 🚀 Next Steps

1. **Read:** QUICK_TEST_REFERENCE.md (5 min)
2. **Prepare:** Build plugin + start WordPress (15 min)
3. **Create:** Follow MANUAL_TESTING_GUIDE.md (45 min)
4. **Test:** Run automated + manual tests (2 hours)
5. **Report:** Complete test-report-generator.html (15 min)

**Total Time Investment: ~3 hours**

---

## 📄 Related Documentation

- `CONDITIONAL_LOGIC_GUIDE.md` - Implementation architecture
- `IMPLEMENTATION_SUMMARY.md` - Overall plugin features
- `CHANGES.md` - Version history
- `README.md` - Plugin overview

---

**Last Updated:** December 2024  
**Version:** 1.0  
**Status:** ✅ Complete - Ready for Execution

---

*This index serves as the central navigation hub for all conditional navigation testing resources.*
