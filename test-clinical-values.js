// Test script to verify clinically validated VAS alignment values
const { calculateLabelLeftPercent } = require('./src/blocks/vas-slider/calculateLabelSpacing.js');

console.log('Testing clinically validated VAS alignment values...\n');

const testCases = [
    { labels: 3, alignment: 100, expected: [5, 50, 87], description: '3 labels, alignment 100' },
    { labels: 3, alignment: 50, expected: [20, 50, 70], description: '3 labels, alignment 50' },
    { labels: 4, alignment: 100, expected: [5, 30, 70, 88], description: '4 labels, alignment 100' },
    { labels: 4, alignment: 50, expected: [15, 35, 65, 80], description: '4 labels, alignment 50' },
    { labels: 5, alignment: 100, expected: [5, 25, 50, 75, 90], description: '5 labels, alignment 100' },
    { labels: 5, alignment: 50, expected: [15, 28, 50, 70, 80], description: '5 labels, alignment 50' },
];

let allPassed = true;

testCases.forEach((testCase) => {
    const calculated = [];
    for (let i = 0; i < testCase.labels; i++) {
        calculated.push(Math.round(calculateLabelLeftPercent(i, testCase.labels, testCase.alignment)));
    }
    
    const passed = JSON.stringify(calculated) === JSON.stringify(testCase.expected);
    const status = passed ? '✓ PASS' : '✗ FAIL';
    
    console.log(`${status}: ${testCase.description}`);
    console.log(`  Expected: [${testCase.expected.join(', ')}]`);
    console.log(`  Calculated: [${calculated.join(', ')}]`);
    
    if (!passed) {
        allPassed = false;
        console.log('  ❌ MISMATCH!');
    }
    console.log();
});

// Test interpolation
console.log('Testing interpolation (alignment 25 for 3 labels):');
const interpolated = [];
for (let i = 0; i < 3; i++) {
    interpolated.push(Math.round(calculateLabelLeftPercent(i, 3, 25)));
}
console.log(`  Calculated: [${interpolated.join(', ')}]`);
console.log('  (Should be between [25, 50, 75] and [20, 50, 70])');
console.log();

console.log(allPassed ? '✅ All tests PASSED!' : '❌ Some tests FAILED!');
process.exit(allPassed ? 0 : 1);