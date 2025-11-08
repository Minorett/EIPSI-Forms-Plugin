# Quick Test Reference Card

## 🚀 Quick Start (5 Minutes)

### 1. Build & Start
```bash
npm run build
# Access: http://localhost:8888/wp-admin
```

### 2. Create Test Form
- New Page → Add "Form Container" block
- Add "Form Page" blocks (multi-page)
- Add "Radio Buttons" field with options
- Enable "Conditional Logic" in field settings
- Configure rule: `Value → Action → Target`

### 3. Test on Frontend
```javascript
// Console (F12)
EIPSIConditionalTests.runAll()

// Check state
const nav = window.EIPSIForms.conditionalNavigators.get(
  document.querySelector('.vas-dinamico-form')
);
console.log('History:', [...nav.history]);
console.log('Skipped:', [...nav.skippedPages]);
```

---

## 📚 File Guide

| File | Purpose | Use When |
|------|---------|----------|
| `CONDITIONAL_FLOW_TESTING.md` | Full testing procedures | Need detailed test specs |
| `MANUAL_TESTING_GUIDE.md` | Step-by-step form creation | Creating test forms |
| `TESTING_COMPLETION_SUMMARY.md` | Overview & checklist | Starting testing session |
| `test-conditional-flows.js` | Automated tests | Testing in browser |
| `test-report-generator.html` | Report interface | Documenting results |

---

## 🎯 4 Test Scenarios

### Test 1: Linear ⬆️
- 4 pages, no conditional logic
- Verify: Sequential navigation
- Time: ~5 minutes

### Test 2: Single-Branch 🔀
- 4 pages, 1 rule (goToPage)
- Verify: Jump behavior, skipped tracking
- Time: ~10 minutes

### Test 3: Multi-Branch 🌲
- 6 pages, 4 rules (different targets)
- Verify: Each path routes correctly
- Time: ~15 minutes

### Test 4: Submit Shortcut 🚀
- 4 pages, submit action rule
- Verify: Early termination
- Time: ~10 minutes

**Total: ~40 minutes**

---

## 🔍 Key Things to Check

### ✅ Working Correctly
- Pages navigate in expected order
- History array matches path taken
- Skipped pages tracked in Set
- Back button goes to previous visited page
- Console shows no errors

### ❌ Red Flags
- `ConditionalNavigator is not defined`
- Wrong page displayed after Next click
- History doesn't match reality
- Skipped pages showing up
- Navigation dead ends

---

## 💻 Console Cheat Sheet

```javascript
// Get navigator instance
const form = document.querySelector('.vas-dinamico-form');
const nav = window.EIPSIForms.conditionalNavigators.get(form);

// Check state
nav.history          // Array: [1, 3, 5]
nav.visitedPages     // Set: {1, 3, 5}
nav.skippedPages     // Set: {2, 4}

// Test navigation
nav.getNextPage(1)   // {action: 'goToPage', targetPage: 3}
nav.shouldSubmit(1)  // true/false

// Find conditional fields
document.querySelectorAll('[data-conditional-logic]')

// Parse field logic
const field = document.querySelector('[data-conditional-logic]');
JSON.parse(field.dataset.conditionalLogic)

// Run automated tests
EIPSIConditionalTests.runAll()
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| "EIPSIForms not defined" | Hard refresh (Ctrl+Shift+R) |
| Next button doesn't work | Check required fields filled |
| Wrong page shown | Verify matchValue matches option text |
| History wrong | Check pushHistory() called on navigation |

---

## 📊 Expected Results

### Linear Flow
```javascript
History: [1, 2, 3, 4]
Skipped: []
```

### Single-Branch (Yes → Page 3)
```javascript
History: [1, 3, 4]
Skipped: [2]
```

### Multi-Branch (Researcher → Page 2)
```javascript
History: [1, 2, 6]
Skipped: [3, 4, 5]
```

### Submit Shortcut (No thanks → Submit)
```javascript
History: [1]
Skipped: [2, 3, 4]
Action: 'submit'
```

---

## 🎓 Conditional Logic JSON

### Basic Rule
```json
{
  "enabled": true,
  "rules": [
    {
      "id": "rule-1",
      "matchValue": "Option 1",
      "action": "goToPage",
      "targetPage": 3
    }
  ],
  "defaultAction": "nextPage"
}
```

### Actions
- `nextPage` - Go to next sequential page
- `goToPage` - Jump to specific page (requires targetPage)
- `submit` - Submit form immediately

---

## ✅ Acceptance Criteria

- [ ] All 4 test forms work as designed
- [ ] No console errors during navigation
- [ ] History/skip state consistent
- [ ] Backward navigation correct
- [ ] Test report completed

---

## 📞 Need Help?

1. **Form creation:** See `MANUAL_TESTING_GUIDE.md`
2. **Test details:** See `CONDITIONAL_FLOW_TESTING.md`
3. **Implementation:** See `CONDITIONAL_LOGIC_GUIDE.md`

---

**Total Testing Time:** 2.5-3 hours  
**Status:** ✅ Ready to execute

---

*Print this card for quick reference during testing!*
