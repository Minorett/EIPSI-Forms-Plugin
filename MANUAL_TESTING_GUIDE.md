# Manual Testing Guide - Conditional Navigation Flows

## Overview

This guide provides step-by-step instructions for manually creating and testing conditional navigation flows in the EIPSI Forms plugin.

---

## Prerequisites

1. **WordPress Setup:**
   - WordPress 6.4+ installed and running
   - EIPSI Forms plugin activated
   - Admin access to WordPress dashboard

2. **Plugin Build:**
   ```bash
   cd /path/to/plugin
   npm run build
   ```

3. **Browser Tools:**
   - Modern browser (Chrome, Firefox, Safari)
   - Developer Tools enabled (F12)

---

## Test Form 1: Linear Flow (Baseline)

### Purpose
Verify basic pagination without conditional logic.

### Creation Steps

1. **Create New Page:**
   - Go to Pages → Add New
   - Title: "Test Form 1 - Linear Flow"

2. **Add Form Container Block:**
   - Click (+) to add block
   - Search "Form Container"
   - Click to insert

3. **Configure Form:**
   - In right sidebar, set:
     - Form ID: `test-linear-flow`
     - Enable Multi-Page: ✓

4. **Add Page 1:**
   - Inside container, click (+)
   - Search "Form Page"
   - Insert page block
   - **Settings:**
     - Page Title: "Personal Information"

5. **Add Field to Page 1:**
   - Inside page, click (+)
   - Search "Text Field"
   - Insert
   - **Settings:**
     - Label: "Your Name"
     - Field Name: `name`
     - Required: ✓

6. **Add Page 2:**
   - After Page 1, click (+)
   - Insert another "Form Page"
   - **Settings:**
     - Page Title: "Contact Details"

7. **Add Field to Page 2:**
   - Insert "Text Field"
   - **Settings:**
     - Label: "Your Email"
     - Field Name: `email`
     - Required: ✓

8. **Add Page 3:**
   - Insert "Form Page"
   - **Settings:**
     - Page Title: "Additional Comments"

9. **Add Field to Page 3:**
   - Insert "Text Area Field"
   - **Settings:**
     - Label: "Comments"
     - Field Name: `comments`
     - Required: ✗

10. **Add Page 4:**
    - Insert "Form Page"
    - **Settings:**
      - Page Title: "Thank You"
    - Add "Paragraph" block with: "Thank you for completing the form!"

11. **Publish:**
    - Click "Publish"
    - Click "Publish" again to confirm

### Testing Steps

1. **View Page:**
   - Click "View Page" or navigate to published URL

2. **Open DevTools:**
   - Press F12
   - Go to Console tab

3. **Load Test Script:**
   - Copy contents of `test-conditional-flows.js`
   - Paste into console
   - Press Enter

4. **Run Tests:**
   ```javascript
   EIPSIConditionalTests.runAll()
   ```

5. **Manual Navigation Test:**
   - Enter "John Doe" in name field
   - Click "Next"
   - Verify you're on page 2
   - Enter "john@example.com"
   - Click "Next"
   - Verify you're on page 3
   - Click "Prev"
   - Verify you're back on page 2
   - Click "Prev" again
   - Verify you're on page 1

6. **Check Console:**
   - Should see no errors
   - History should show: `[1, 2, 3, 2, 1]` after back navigation

7. **Complete Form:**
   - Navigate forward again to page 4
   - Click "Submit"
   - Verify submission successful

### Expected Results

✅ All pages navigate sequentially  
✅ Validation prevents empty required fields  
✅ Back button maintains history  
✅ Page counter accurate (1/4, 2/4, etc.)  
✅ No console errors  
✅ History: `[1, 2, 3, 4]` on forward journey  

---

## Test Form 2: Single-Branch Flow (goToPage)

### Purpose
Test conditional logic with one rule jumping to non-sequential page.

### Creation Steps

1. **Create New Page:**
   - Title: "Test Form 2 - Single Branch"

2. **Add Form Container:**
   - Form ID: `test-single-branch`
   - Enable Multi-Page: ✓

3. **Add Page 1:**
   - Page Title: "Experience Level"

4. **Add Radio Field to Page 1:**
   - Insert "Radio Buttons Field"
   - **Settings:**
     - Label: "Do you have prior experience?"
     - Field Name: `experience`
     - Options: `Yes, No`
     - Required: ✓
   
5. **Configure Conditional Logic:**
   - In Radio field settings, scroll down
   - Find "Conditional Logic" section
   - Click toggle to enable
   - **Rule 1:**
     - Match Value: `Yes`
     - Action: `Go to Page`
     - Target Page: `3`
   - Click "Add Rule" if needed
   - **Default Action:** `Next Page`

6. **Add Page 2:**
   - Page Title: "Background Details"

7. **Add Field to Page 2:**
   - Insert "Text Area Field"
   - **Settings:**
     - Label: "Please describe your background"
     - Field Name: `background`
     - Required: ✓

8. **Add Page 3:**
   - Page Title: "Experience Rating"

9. **Add Field to Page 3:**
   - Insert "Radio Buttons Field"
   - **Settings:**
     - Label: "Rate your experience level"
     - Field Name: `rating`
     - Options: `Beginner, Intermediate, Advanced, Expert`
     - Required: ✓

10. **Add Page 4:**
    - Page Title: "Complete"
    - Add paragraph: "Form submitted successfully!"

11. **Publish Form**

### Testing Steps

#### Path A: Select "Yes" (Jump to Page 3)

1. **Load Form** and open DevTools Console

2. **Load Test Script** and run:
   ```javascript
   EIPSIConditionalTests.runAll()
   ```

3. **Manual Test:**
   - Select "Yes"
   - Click "Next"
   
4. **Verify:**
   - Should jump directly to Page 3 (skipping Page 2)
   - Page counter shows "3/4"
   
5. **Check Console:**
   ```javascript
   const nav = window.EIPSIForms.conditionalNavigators.get(
     document.querySelector('.vas-dinamico-form')
   );
   console.log('History:', [...nav.history]);
   console.log('Skipped:', [...nav.skippedPages]);
   ```
   - History should be: `[1, 3]`
   - Skipped should be: `{2}`

6. **Test Backward Navigation:**
   - Click "Prev"
   - Should return to Page 1 (not Page 2)
   - History should update correctly

7. **Complete Form:**
   - Go forward again
   - Select an experience rating
   - Click "Next"
   - Navigate to Page 4
   - Submit

#### Path B: Select "No" (Sequential Flow)

1. **Reload Form**

2. **Manual Test:**
   - Select "No"
   - Click "Next"

3. **Verify:**
   - Should go to Page 2 (not skip)
   - Page counter shows "2/4"

4. **Check Console:**
   - History: `[1, 2]`
   - Skipped: `{}` (empty)

5. **Continue:**
   - Fill in background text
   - Click "Next"
   - Should go to Page 3
   - Complete form normally

### Expected Results

**Path A (Yes):**
✅ Jumps from page 1 → 3  
✅ Page 2 never displayed  
✅ History: `[1, 3]`  
✅ SkippedPages: `{2}`  
✅ Prev button returns to page 1  
✅ Console shows conditional logic matched  

**Path B (No):**
✅ Sequential flow: 1 → 2 → 3 → 4  
✅ All pages shown  
✅ History: `[1, 2, 3, 4]`  
✅ SkippedPages: `{}`  
✅ Standard navigation  

---

## Test Form 3: Multi-Branch Flow (Complex Routing)

### Purpose
Test multiple conditional rules with different targets per option.

### Creation Steps

1. **Create New Page:**
   - Title: "Test Form 3 - Multi-Branch"

2. **Add Form Container:**
   - Form ID: `test-multi-branch`
   - Enable Multi-Page: ✓

3. **Add Page 1:**
   - Page Title: "Category Selection"

4. **Add Select Field to Page 1:**
   - Insert "Select Dropdown Field"
   - **Settings:**
     - Label: "Choose your category"
     - Field Name: `category`
     - Options: `Researcher, Clinician, Student, Other`
     - Required: ✓

5. **Configure Conditional Logic:**
   - Enable Conditional Logic
   - **Rule 1:**
     - Match Value: `Researcher`
     - Action: `Go to Page`
     - Target Page: `2`
   - **Rule 2:**
     - Match Value: `Clinician`
     - Action: `Go to Page`
     - Target Page: `3`
   - **Rule 3:**
     - Match Value: `Student`
     - Action: `Go to Page`
     - Target Page: `4`
   - **Rule 4:**
     - Match Value: `Other`
     - Action: `Go to Page`
     - Target Page: `5`
   - **Default Action:** `Next Page`

6. **Add Page 2:**
   - Page Title: "Research Background"
   - Add Text Field:
     - Label: "Research area"
     - Field Name: `research_area`

7. **Add Page 3:**
   - Page Title: "Clinical Practice"
   - Add Text Field:
     - Label: "Practice specialty"
     - Field Name: `specialty`

8. **Add Page 4:**
   - Page Title: "Student Information"
   - Add Text Field:
     - Label: "Institution"
     - Field Name: `institution`

9. **Add Page 5:**
   - Page Title: "Other Details"
   - Add Text Area:
     - Label: "Tell us more"
     - Field Name: `other_details`

10. **Add Page 6:**
    - Page Title: "Summary"
    - Add paragraph: "Thank you! Your form has been received."

11. **Publish Form**

### Testing Steps

Test each path separately by reloading the form between tests.

#### Path: Researcher

1. Load form, open console
2. Select "Researcher"
3. Click "Next"
4. **Verify:** On Page 2 (Research Background)
5. **Console:**
   ```javascript
   const nav = window.EIPSIForms.conditionalNavigators.get(
     document.querySelector('.vas-dinamico-form')
   );
   console.log('Path: Researcher');
   console.log('History:', [...nav.history]);
   console.log('Skipped:', [...nav.skippedPages]);
   ```
   - History: `[1, 2]`
   - Skipped: `{3, 4, 5}`
6. Complete to Page 6

#### Path: Clinician

1. Reload form
2. Select "Clinician"
3. Click "Next"
4. **Verify:** On Page 3 (Clinical Practice)
5. **Console:**
   - History: `[1, 3]`
   - Skipped: `{2, 4, 5}`

#### Path: Student

1. Reload form
2. Select "Student"
3. Click "Next"
4. **Verify:** On Page 4 (Student Information)
5. **Console:**
   - History: `[1, 4]`
   - Skipped: `{2, 3, 5}`

#### Path: Other

1. Reload form
2. Select "Other"
3. Click "Next"
4. **Verify:** On Page 5 (Other Details)
5. **Console:**
   - History: `[1, 5]`
   - Skipped: `{2, 3, 4}`

### Expected Results

✅ Each option routes to correct unique page  
✅ Intermediate pages skipped appropriately  
✅ History only shows visited pages  
✅ SkippedPages set correct for each path  
✅ All paths converge at Page 6  
✅ Backward navigation works correctly  
✅ No console errors  

---

## Test Form 4: Submit Shortcut Flow

### Purpose
Test `submit` action that bypasses remaining pages.

### Creation Steps

1. **Create New Page:**
   - Title: "Test Form 4 - Submit Shortcut"

2. **Add Form Container:**
   - Form ID: `test-submit-shortcut`
   - Enable Multi-Page: ✓

3. **Add Page 1:**
   - Page Title: "Participation Consent"

4. **Add Radio Field to Page 1:**
   - Insert "Radio Buttons Field"
   - **Settings:**
     - Label: "Would you like to participate in our study?"
     - Field Name: `participate`
     - Options: `Yes, No thanks`
     - Required: ✓

5. **Configure Conditional Logic:**
   - Enable Conditional Logic
   - **Rule 1:**
     - Match Value: `No thanks`
     - Action: `Submit Form`
     - (No target page needed)
   - **Default Action:** `Next Page`

6. **Add Page 2:**
   - Page Title: "Informed Consent"
   - Add Checkbox:
     - Label: "I agree to the terms"
     - Field Name: `consent`
     - Required: ✓

7. **Add Page 3:**
   - Page Title: "Demographics"
   - Add Text Field:
     - Label: "Age"
     - Field Name: `age`

8. **Add Page 4:**
   - Page Title: "Contact Information"
   - Add Text Field:
     - Label: "Email"
     - Field Name: `contact_email`

9. **Publish Form**

### Testing Steps

#### Path A: Select "Yes" (Full Form)

1. Load form, open console
2. Select "Yes"
3. Click "Next"
4. **Verify:** On Page 2 (Informed Consent)
5. Continue through all pages normally
6. Complete submission on Page 4

#### Path B: Select "No thanks" (Submit Shortcut)

1. Reload form
2. Open Network tab in DevTools
3. Select "No thanks"
4. Click "Next" (button might change to "Submit")
5. **Verify:**
   - Form should submit immediately
   - OR Next button should become "Submit" button
   - OR Page should show submission confirmation

6. **Console Check:**
   ```javascript
   const nav = window.EIPSIForms.conditionalNavigators.get(
     document.querySelector('.vas-dinamico-form')
   );
   const result = nav.getNextPage(1);
   console.log('Action:', result.action); // Should be 'submit'
   ```

7. **Network Tab:**
   - Look for form POST request
   - Should only include Page 1 data
   - Pages 2-4 data should be absent

8. **Console State:**
   - History: `[1]`
   - Skipped: `{2, 3, 4}`

### Expected Results

**Path A (Yes):**
✅ Sequential flow through all pages  
✅ Normal submission at end  
✅ All data submitted  

**Path B (No thanks):**
✅ Submit action triggered on page 1  
✅ Pages 2-4 never shown  
✅ Immediate submission  
✅ Only page 1 data submitted  
✅ History: `[1]`  
✅ SkippedPages: `{2, 3, 4}`  
✅ No validation errors for skipped required fields  

---

## Debugging Checklist

### Before Testing

- [ ] Plugin activated
- [ ] Assets built (`npm run build`)
- [ ] Browser cache cleared
- [ ] DevTools open and ready
- [ ] Test script loaded

### During Testing

- [ ] Console shows no JavaScript errors
- [ ] `EIPSIForms` global object present
- [ ] `ConditionalNavigator` initialized (for conditional forms)
- [ ] `data-conditional-logic` attributes present in DOM
- [ ] Page transitions smooth (no flashing)
- [ ] Validation messages appear correctly

### After Testing

- [ ] All test scenarios passed
- [ ] Screenshots captured for issues
- [ ] Console logs saved
- [ ] Network requests captured
- [ ] Form submissions verified

---

## Common Issues and Solutions

### Issue: "EIPSIForms is not defined"

**Cause:** Frontend JavaScript not loaded

**Solution:**
1. Check plugin is activated
2. Hard refresh browser (Ctrl+Shift+R)
3. Verify `eipsi-forms.js` loads in Network tab
4. Check for JavaScript errors earlier in console

### Issue: Conditional logic not triggering

**Cause:** JSON serialization issue or rule mismatch

**Solution:**
1. Inspect field in DOM:
   ```javascript
   const field = document.querySelector('[data-conditional-logic]');
   console.log(field.dataset.conditionalLogic);
   ```
2. Verify JSON is valid
3. Check matchValue exactly matches option text
4. Ensure field has a value selected

### Issue: Page doesn't change on Next click

**Cause:** Validation failing or button disabled

**Solution:**
1. Check console for validation errors
2. Verify all required fields filled
3. Check button disabled state:
   ```javascript
   const btn = document.querySelector('.next-button');
   console.log('Disabled:', btn.disabled);
   ```

### Issue: History not updating correctly

**Cause:** ConditionalNavigator not initialized or pushHistory not called

**Solution:**
1. Check navigator exists:
   ```javascript
   const form = document.querySelector('.vas-dinamico-form');
   const nav = window.EIPSIForms.conditionalNavigators?.get(form);
   console.log('Navigator:', nav);
   ```
2. Set breakpoint in `pushHistory()` method
3. Verify page change event fires

### Issue: Skipped pages still showing

**Cause:** Navigation logic not checking skipped set

**Solution:**
1. Verify skipped set populated:
   ```javascript
   console.log('Skipped:', [...nav.skippedPages]);
   ```
2. Check page display logic respects conditional routing

---

## Test Results Template

Copy this template to document your test results:

```markdown
## Test Results - [Date]

### Environment
- WordPress Version: 
- Plugin Version: 1.1.0
- Browser: 
- OS: 

### Test Form 1: Linear Flow
- Status: ✅ Pass / ❌ Fail
- Issues: 
- Screenshots: 

### Test Form 2: Single-Branch
- Status: ✅ Pass / ❌ Fail
- Path A (Yes): ✅ / ❌
- Path B (No): ✅ / ❌
- Issues: 
- Screenshots: 

### Test Form 3: Multi-Branch
- Status: ✅ Pass / ❌ Fail
- Researcher Path: ✅ / ❌
- Clinician Path: ✅ / ❌
- Student Path: ✅ / ❌
- Other Path: ✅ / ❌
- Issues: 
- Screenshots: 

### Test Form 4: Submit Shortcut
- Status: ✅ Pass / ❌ Fail
- Path A (Yes): ✅ / ❌
- Path B (No thanks): ✅ / ❌
- Issues: 
- Screenshots: 

### Overall Summary
- Total Tests: 4
- Passed: 
- Failed: 
- Pass Rate: 

### Issues Found
1. 
2. 

### Recommendations
1. 
2. 
```

---

## Next Steps

After completing manual testing:

1. **Document Results:**
   - Fill in test results template
   - Attach screenshots of any issues
   - Save console logs if errors occurred

2. **File Issues:**
   - Create GitHub issues for bugs found
   - Include reproduction steps
   - Tag with appropriate labels

3. **Update Documentation:**
   - Add troubleshooting tips based on findings
   - Update user guides if needed
   - Revise developer docs

4. **Run Automated Tests:**
   - Execute `test-conditional-flows.js` for each form
   - Compare automated vs manual results
   - Document any discrepancies

5. **Cross-Browser Testing:**
   - Repeat critical tests in Firefox, Safari
   - Test on mobile devices
   - Document browser-specific issues

---

**Happy Testing! 🧪**
