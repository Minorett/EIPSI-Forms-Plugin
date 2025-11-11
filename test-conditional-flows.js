/**
 * Test Suite for Conditional Logic Flows
 * Tests both discrete (radio/checkbox) and numeric (VAS slider) conditional logic
 * 
 * Usage: node test-conditional-flows.js
 * 
 * Or in browser console:
 * <script src="test-conditional-flows.js"></script>
 */

(function() {
    'use strict';

    // Test results tracking
    const results = {
        passed: 0,
        failed: 0,
        total: 0,
        tests: []
    };

    // Helper functions
    function assert(condition, message) {
        results.total++;
        if (condition) {
            results.passed++;
            results.tests.push({ name: message, status: 'PASS' });
            console.log('✓', message);
        } else {
            results.failed++;
            results.tests.push({ name: message, status: 'FAIL' });
            console.error('✗', message);
        }
    }

    function assertEqual(actual, expected, message) {
        const condition = actual === expected;
        assert(condition, `${message} (expected: ${expected}, got: ${actual})`);
    }

    function assertDeepEqual(actual, expected, message) {
        const condition = JSON.stringify(actual) === JSON.stringify(expected);
        assert(condition, message);
    }

    // Mock ConditionalNavigator class (simplified version from eipsi-forms.js)
    class ConditionalNavigator {
        constructor() {}

        parseConditionalLogic(jsonString) {
            if (!jsonString || jsonString === 'true') return null;
            try {
                return JSON.parse(jsonString);
            } catch (error) {
                return null;
            }
        }

        normalizeConditionalLogic(logic) {
            if (!logic) return null;

            if (Array.isArray(logic)) {
                return {
                    enabled: logic.length > 0,
                    rules: logic.map((rule) => ({
                        id: rule.id || `rule-${Date.now()}`,
                        matchValue: rule.value || rule.matchValue || '',
                        operator: rule.operator,
                        threshold: rule.threshold,
                        action: rule.action || 'nextPage',
                        targetPage: rule.targetPage || null,
                    })),
                    defaultAction: 'nextPage',
                    defaultTargetPage: null,
                };
            }

            if (typeof logic === 'object' && logic.enabled !== undefined) {
                return logic;
            }

            return null;
        }

        getFieldValue(field) {
            const fieldType = field.dataset.fieldType;

            switch (fieldType) {
                case 'select':
                    const select = field.querySelector('select');
                    return select ? select.value : '';

                case 'radio':
                    const checkedRadio = field.querySelector('input[type="radio"]:checked');
                    return checkedRadio ? checkedRadio.value : '';

                case 'checkbox':
                    const checkedBoxes = field.querySelectorAll('input[type="checkbox"]:checked');
                    return Array.from(checkedBoxes).map((cb) => cb.value);

                case 'vas-slider':
                    const slider = field.querySelector('input[type="range"]');
                    if (slider) {
                        const value = parseFloat(slider.value);
                        return !Number.isNaN(value) ? value : null;
                    }
                    return null;

                default:
                    return '';
            }
        }

        findMatchingRule(rules, fieldValue) {
            if (!Array.isArray(rules)) {
                return null;
            }

            for (const rule of rules) {
                // Numeric comparison
                if (rule.operator && rule.threshold !== undefined) {
                    if (typeof fieldValue === 'number') {
                        const threshold = parseFloat(rule.threshold);

                        if (Number.isNaN(threshold)) {
                            continue;
                        }

                        let matches = false;
                        switch (rule.operator) {
                            case '>=':
                                matches = fieldValue >= threshold;
                                break;
                            case '<=':
                                matches = fieldValue <= threshold;
                                break;
                            case '>':
                                matches = fieldValue > threshold;
                                break;
                            case '<':
                                matches = fieldValue < threshold;
                                break;
                            case '==':
                                matches = fieldValue === threshold;
                                break;
                        }

                        if (matches) {
                            return rule;
                        }
                    }
                }
                // Discrete comparison
                else if (rule.matchValue !== undefined || rule.value !== undefined) {
                    if (Array.isArray(fieldValue)) {
                        for (const value of fieldValue) {
                            if (rule.matchValue === value || rule.value === value) {
                                return rule;
                            }
                        }
                    } else {
                        if (rule.matchValue === fieldValue || rule.value === fieldValue) {
                            return rule;
                        }
                    }
                }
            }

            return null;
        }
    }

    // Mock DOM elements (Node.js compatible)
    function createMockElement(tag) {
        return {
            tagName: tag.toUpperCase(),
            dataset: {},
            children: [],
            querySelector(selector) {
                return this.children.find(child => {
                    if (selector === 'select' && child.tagName === 'SELECT') return true;
                    if (selector === 'input[type="radio"]:checked' && child.type === 'radio' && child.checked) return true;
                    if (selector === 'input[type="range"]' && child.type === 'range') return true;
                    return false;
                });
            },
            querySelectorAll(selector) {
                if (selector === 'input[type="checkbox"]:checked') {
                    return this.children.filter(child => child.type === 'checkbox' && child.checked);
                }
                return [];
            },
            appendChild(child) {
                this.children.push(child);
            }
        };
    }

    function createMockVASField(value) {
        const field = createMockElement('div');
        field.dataset.fieldType = 'vas-slider';
        field.dataset.fieldName = 'pain_intensity';
        
        const slider = {
            tagName: 'INPUT',
            type: 'range',
            value: value.toString(),
            min: '0',
            max: '100'
        };
        
        field.appendChild(slider);
        return field;
    }

    function createMockRadioField(selectedValue) {
        const field = createMockElement('div');
        field.dataset.fieldType = 'radio';
        field.dataset.fieldName = 'question_1';
        
        ['Yes', 'No', 'Maybe'].forEach(value => {
            const radio = {
                tagName: 'INPUT',
                type: 'radio',
                value: value,
                name: 'question_1',
                checked: value === selectedValue
            };
            field.appendChild(radio);
        });
        
        return field;
    }

    // Test suite
    console.log('\n=== EIPSI Forms Conditional Logic Test Suite ===\n');

    const navigator = new ConditionalNavigator();

    // Test 1: VAS slider returns numeric value
    console.log('\n--- Test Group: VAS Slider Field Value ---');
    const vasField85 = createMockVASField(85);
    const value85 = navigator.getFieldValue(vasField85);
    assertEqual(value85, 85, 'VAS slider returns correct numeric value');
    assertEqual(typeof value85, 'number', 'VAS slider value is a number');

    // Test 2: Numeric comparison operators
    console.log('\n--- Test Group: Numeric Operators ---');
    
    const numericRules = [
        { id: 'rule-1', operator: '>=', threshold: 80, action: 'goToPage', targetPage: 5 },
        { id: 'rule-2', operator: '>=', threshold: 50, action: 'goToPage', targetPage: 3 },
    ];

    // Test >= operator with matching value
    const match85 = navigator.findMatchingRule(numericRules, 85);
    assert(match85 !== null, 'Value 85 matches >= 80 rule');
    assertEqual(match85?.targetPage, 5, 'Matched rule has correct target page (5)');

    // Test >= operator with non-matching value
    const match40 = navigator.findMatchingRule(numericRules, 40);
    assertEqual(match40, null, 'Value 40 does not match any rule');

    // Test rule order (first match wins)
    const match75 = navigator.findMatchingRule(numericRules, 75);
    assertEqual(match75?.targetPage, 3, 'Value 75 matches second rule (>= 50)');

    // Test boundary value (exactly on threshold)
    const match80 = navigator.findMatchingRule(numericRules, 80);
    assertEqual(match80?.targetPage, 5, 'Boundary value 80 matches >= 80 rule');

    const match50 = navigator.findMatchingRule(numericRules, 50);
    assertEqual(match50?.targetPage, 3, 'Boundary value 50 matches >= 50 rule');

    // Test 3: All operator types
    console.log('\n--- Test Group: All Numeric Operators ---');

    const testOperator = (operator, threshold, testValue, shouldMatch) => {
        const rule = [{ id: 'test', operator, threshold, action: 'goToPage', targetPage: 10 }];
        const result = navigator.findMatchingRule(rule, testValue);
        const matched = result !== null;
        assertEqual(
            matched,
            shouldMatch,
            `${operator} ${threshold}: value ${testValue} ${shouldMatch ? 'should' : 'should not'} match`
        );
    };

    // >= operator tests
    testOperator('>=', 50, 51, true);
    testOperator('>=', 50, 50, true);
    testOperator('>=', 50, 49, false);

    // <= operator tests
    testOperator('<=', 50, 49, true);
    testOperator('<=', 50, 50, true);
    testOperator('<=', 50, 51, false);

    // > operator tests
    testOperator('>', 50, 51, true);
    testOperator('>', 50, 50, false);
    testOperator('>', 50, 49, false);

    // < operator tests
    testOperator('<', 50, 49, true);
    testOperator('<', 50, 50, false);
    testOperator('<', 50, 51, false);

    // == operator tests
    testOperator('==', 50, 50, true);
    testOperator('==', 50, 49, false);
    testOperator('==', 50, 51, false);

    // Test 4: Discrete field logic (backward compatibility)
    console.log('\n--- Test Group: Discrete Field Logic (Backward Compatibility) ---');

    const radioField = createMockRadioField('Yes');
    const radioValue = navigator.getFieldValue(radioField);
    assertEqual(radioValue, 'Yes', 'Radio field returns correct string value');

    const discreteRules = [
        { id: 'rule-1', matchValue: 'Yes', action: 'goToPage', targetPage: 5 },
        { id: 'rule-2', matchValue: 'No', action: 'submit' },
    ];

    const matchYes = navigator.findMatchingRule(discreteRules, 'Yes');
    assert(matchYes !== null, 'Discrete value "Yes" matches rule');
    assertEqual(matchYes?.action, 'goToPage', 'Matched discrete rule has correct action');

    const matchMaybe = navigator.findMatchingRule(discreteRules, 'Maybe');
    assertEqual(matchMaybe, null, 'Value "Maybe" without rule returns null');

    // Test 5: Mixed rules (both numeric and discrete)
    console.log('\n--- Test Group: Mixed Rule Types ---');

    const mixedRules = [
        { id: 'rule-1', operator: '>=', threshold: 80, action: 'goToPage', targetPage: 10 },
        { id: 'rule-2', matchValue: 'High Risk', action: 'goToPage', targetPage: 8 },
    ];

    const matchNumeric = navigator.findMatchingRule(mixedRules, 85);
    assertEqual(matchNumeric?.targetPage, 10, 'Numeric value matches numeric rule in mixed set');

    const matchDiscrete = navigator.findMatchingRule(mixedRules, 'High Risk');
    assertEqual(matchDiscrete?.targetPage, 8, 'Discrete value matches discrete rule in mixed set');

    // Test 6: Edge cases
    console.log('\n--- Test Group: Edge Cases ---');

    // NaN threshold
    const nanRule = [{ id: 'test', operator: '>=', threshold: 'invalid', action: 'goToPage', targetPage: 5 }];
    const matchNaN = navigator.findMatchingRule(nanRule, 50);
    assertEqual(matchNaN, null, 'Invalid threshold (NaN) returns null');

    // Empty rules array
    const matchEmpty = navigator.findMatchingRule([], 50);
    assertEqual(matchEmpty, null, 'Empty rules array returns null');

    // Null/undefined value
    const matchNull = navigator.findMatchingRule(numericRules, null);
    assertEqual(matchNull, null, 'Null field value returns null');

    // String value against numeric rule
    const matchString = navigator.findMatchingRule(numericRules, '85');
    assertEqual(matchString, null, 'String value does not match numeric rule');

    // Test 7: JSON parsing
    console.log('\n--- Test Group: JSON Parsing ---');

    const validJSON = '{"enabled":true,"rules":[{"id":"rule-1","operator":">=","threshold":50,"action":"goToPage","targetPage":3}]}';
    const parsed = navigator.parseConditionalLogic(validJSON);
    assert(parsed !== null, 'Valid JSON string parses correctly');
    assertEqual(parsed.enabled, true, 'Parsed logic has enabled flag');

    const invalidJSON = '{invalid json}';
    const parsedInvalid = navigator.parseConditionalLogic(invalidJSON);
    assertEqual(parsedInvalid, null, 'Invalid JSON returns null');

    // Test 8: Normalization
    console.log('\n--- Test Group: Logic Normalization ---');

    const arrayLogic = [
        { id: 'rule-1', value: 'Yes', action: 'goToPage', targetPage: 3 }
    ];
    const normalized = navigator.normalizeConditionalLogic(arrayLogic);
    assert(normalized !== null, 'Array logic normalizes correctly');
    assertEqual(normalized.enabled, true, 'Normalized logic has enabled flag');
    assertEqual(normalized.rules[0].matchValue, 'Yes', 'Normalized rule has matchValue');

    // Test 9: Complex scenario - Mental health screening
    console.log('\n--- Test Group: Complex Clinical Scenario ---');

    const mentalHealthRules = [
        { id: 'rule-1', operator: '>=', threshold: 80, action: 'goToPage', targetPage: 15 }, // Crisis
        { id: 'rule-2', operator: '>=', threshold: 60, action: 'goToPage', targetPage: 10 }, // High
        { id: 'rule-3', operator: '>=', threshold: 40, action: 'goToPage', targetPage: 5 },  // Moderate
        // < 40 goes to next page (default action)
    ];

    assertEqual(navigator.findMatchingRule(mentalHealthRules, 85)?.targetPage, 15, 'Crisis level (85) → page 15');
    assertEqual(navigator.findMatchingRule(mentalHealthRules, 70)?.targetPage, 10, 'High severity (70) → page 10');
    assertEqual(navigator.findMatchingRule(mentalHealthRules, 50)?.targetPage, 5, 'Moderate severity (50) → page 5');
    assertEqual(navigator.findMatchingRule(mentalHealthRules, 30), null, 'Low severity (30) → default action');

    // Print summary
    console.log('\n=== Test Summary ===');
    console.log(`Total: ${results.total}`);
    console.log(`Passed: ${results.passed} ✓`);
    console.log(`Failed: ${results.failed} ${results.failed > 0 ? '✗' : ''}`);
    console.log(`Success rate: ${Math.round((results.passed / results.total) * 100)}%`);

    if (results.failed > 0) {
        console.log('\n=== Failed Tests ===');
        results.tests.filter(t => t.status === 'FAIL').forEach(t => {
            console.log(`✗ ${t.name}`);
        });
    }

    // Export results for Node.js
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = results;
    }

    // Return results for browser
    if (typeof window !== 'undefined') {
        window.conditionalLogicTestResults = results;
    }

    // Exit with appropriate code for CI
    if (typeof process !== 'undefined' && process.exit) {
        process.exit(results.failed > 0 ? 1 : 0);
    }

})();
