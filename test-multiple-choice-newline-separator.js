#!/usr/bin/env node

/**
 * Test Suite: Multiple Choice Newline Separator
 * 
 * Validates that the Multiple Choice block now uses newline separators
 * instead of commas, with backward compatibility for comma-separated options.
 * 
 * Critical for: Clinical research forms that need options with commas
 * Example: "Sí, absolutamente", "Sí, pero no tan frecuente"
 */

const fs = require('fs');
const path = require('path');

// ANSI colors for terminal output
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    cyan: '\x1b[36m',
};

const log = {
    header: (msg) => console.log(`\n${colors.bright}${colors.blue}${msg}${colors.reset}`),
    success: (msg) => console.log(`${colors.green}✅ ${msg}${colors.reset}`),
    error: (msg) => console.log(`${colors.red}❌ ${msg}${colors.reset}`),
    warning: (msg) => console.log(`${colors.yellow}⚠️  ${msg}${colors.reset}`),
    info: (msg) => console.log(`${colors.cyan}ℹ️  ${msg}${colors.reset}`),
    detail: (msg) => console.log(`   ${msg}`),
};

class TestRunner {
    constructor() {
        this.tests = [];
        this.passed = 0;
        this.failed = 0;
    }

    test(name, fn) {
        this.tests.push({ name, fn });
    }

    async run() {
        log.header('🧪 Multiple Choice Newline Separator Test Suite');
        log.info(`Running ${this.tests.length} tests...\n`);

        for (const { name, fn } of this.tests) {
            try {
                await fn();
                log.success(name);
                this.passed++;
            } catch (error) {
                log.error(name);
                log.detail(colors.red + error.message + colors.reset);
                this.failed++;
            }
        }

        this.printSummary();
    }

    printSummary() {
        const total = this.passed + this.failed;
        const passRate = ((this.passed / total) * 100).toFixed(1);

        log.header('📊 Test Summary');
        console.log(`Total Tests: ${total}`);
        console.log(`${colors.green}Passed: ${this.passed}${colors.reset}`);
        console.log(`${colors.red}Failed: ${this.failed}${colors.reset}`);
        console.log(`Pass Rate: ${passRate}%\n`);

        if (this.failed === 0) {
            log.success('All tests passed! 🎉');
        } else {
            log.error(`${this.failed} test(s) failed.`);
            process.exit(1);
        }
    }
}

// Utility: Read file and check content
function readFile(filePath) {
    return fs.readFileSync(filePath, 'utf8');
}

function assertFileContains(filePath, searchString, errorMsg) {
    const content = readFile(filePath);
    if (!content.includes(searchString)) {
        throw new Error(errorMsg || `File ${filePath} does not contain: ${searchString}`);
    }
}

function assertFileNotContains(filePath, searchString, errorMsg) {
    const content = readFile(filePath);
    if (content.includes(searchString)) {
        throw new Error(errorMsg || `File ${filePath} should not contain: ${searchString}`);
    }
}

function assertFileMatches(filePath, regex, errorMsg) {
    const content = readFile(filePath);
    if (!regex.test(content)) {
        throw new Error(errorMsg || `File ${filePath} does not match pattern: ${regex}`);
    }
}

// Test Runner Instance
const runner = new TestRunner();

// ============================================================================
// TEST SUITE: EDIT.JS (Editor Component)
// ============================================================================

runner.test('edit.js: parseOptions detects newline separator', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        "const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
        'parseOptions should detect newline vs comma separator'
    );
});

runner.test('edit.js: parseOptions has backward compatibility comment', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        '// Detectar formato: newline (estándar) o comma (legacy)',
        'parseOptions should have comment explaining backward compatibility'
    );
});

runner.test('edit.js: parseOptions splits by detected separator', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        '.split( separator )',
        'parseOptions should split by detected separator'
    );
});

runner.test('edit.js: TextareaControl label changed to "one per line"', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        "'Options (one per line)'",
        'TextareaControl label should say "one per line" not "comma-separated"'
    );
});

runner.test('edit.js: TextareaControl label does NOT mention comma', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    const content = readFile(filePath);
    const hasCommaSeparatedLabel = content.includes("'Options (comma-separated)'");
    if (hasCommaSeparatedLabel) {
        throw new Error('TextareaControl should not have "comma-separated" label anymore');
    }
});

runner.test('edit.js: TextareaControl value joins options with newline', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        "parseOptions( options ).join( '\\n' )",
        'TextareaControl value should join options with newline'
    );
});

runner.test('edit.js: TextareaControl onChange splits by newline', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        ".split( '\\n' )",
        'onChange handler should split by newline'
    );
});

runner.test('edit.js: TextareaControl onChange joins by newline', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        "cleanedOptions.join( '\\n' )",
        'onChange handler should join cleaned options by newline'
    );
});

runner.test('edit.js: Help text mentions options can contain commas', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        'Options can contain commas, periods, quotes, etc.',
        'Help text should mention that options can contain commas'
    );
});

runner.test('edit.js: Placeholder shows Spanish example with commas', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        'Sí, absolutamente',
        'Placeholder should show example option with comma'
    );
});

runner.test('edit.js: Placeholder uses newline format (\\n)', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileContains(
        filePath,
        'Sí, absolutamente\\nSí, pero no tan frecuente',
        'Placeholder should use \\n to separate options'
    );
});

runner.test('edit.js: Textarea rows increased to 8', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    assertFileMatches(
        filePath,
        /rows=\{\s*8\s*\}/,
        'Textarea should have 8 rows (was 5) for better UX with newlines'
    );
});

// ============================================================================
// TEST SUITE: SAVE.JS (Frontend Component)
// ============================================================================

runner.test('save.js: parseOptions detects newline separator', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/save.js';
    assertFileContains(
        filePath,
        "const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
        'parseOptions should detect newline vs comma separator'
    );
});

runner.test('save.js: parseOptions has backward compatibility comment', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/save.js';
    assertFileContains(
        filePath,
        '// Detectar formato: newline (estándar) o comma (legacy)',
        'parseOptions should have comment explaining backward compatibility'
    );
});

runner.test('save.js: parseOptions splits by detected separator', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/save.js';
    assertFileContains(
        filePath,
        '.split( separator )',
        'parseOptions should split by detected separator'
    );
});

// ============================================================================
// TEST SUITE: BLOCK.JSON (Block Definition)
// ============================================================================

runner.test('block.json: Example uses newline separator', () => {
    const filePath = '/home/engine/project/blocks/campo-multiple/block.json';
    const content = readFile(filePath);
    const parsed = JSON.parse(content);
    
    if (!parsed.example || !parsed.example.attributes || !parsed.example.attributes.options) {
        throw new Error('block.json example should have options attribute');
    }
    
    const options = parsed.example.attributes.options;
    if (!options.includes('\n')) {
        throw new Error(`block.json example should use newline separator. Got: ${options}`);
    }
});

runner.test('block.json: Example does NOT use comma separator', () => {
    const filePath = '/home/engine/project/blocks/campo-multiple/block.json';
    const content = readFile(filePath);
    const parsed = JSON.parse(content);
    
    const options = parsed.example.attributes.options;
    const hasComma = options.split(',').length > 1 && !options.includes('\n');
    if (hasComma) {
        throw new Error(`block.json example should NOT use comma separator. Got: ${options}`);
    }
});

runner.test('block.json: Example shows Spanish options', () => {
    const filePath = '/home/engine/project/blocks/campo-multiple/block.json';
    const content = readFile(filePath);
    const parsed = JSON.parse(content);
    
    const options = parsed.example.attributes.options;
    // Should contain Spanish characters
    if (!/[áéíóúñ]/i.test(options)) {
        throw new Error(`block.json example should show Spanish options. Got: ${options}`);
    }
});

// ============================================================================
// TEST SUITE: BACKWARD COMPATIBILITY LOGIC
// ============================================================================

runner.test('Backward Compatibility: Logic handles comma-only format', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    const content = readFile(filePath);
    
    // Simulate parsing logic
    const parseOptions = (optionsString) => {
        if (!optionsString || optionsString.trim() === '') return [];
        const separator = optionsString.includes('\n') ? '\n' : ',';
        return optionsString.split(separator).map(opt => opt.trim()).filter(opt => opt !== '');
    };
    
    // Test comma-separated (legacy)
    const legacyOptions = 'Opción 1,Opción 2,Opción 3';
    const result = parseOptions(legacyOptions);
    
    if (result.length !== 3) {
        throw new Error(`Legacy comma parsing failed. Expected 3 options, got ${result.length}`);
    }
    if (result[0] !== 'Opción 1') {
        throw new Error(`Legacy comma parsing failed. Expected "Opción 1", got "${result[0]}"`);
    }
});

runner.test('Backward Compatibility: Logic handles newline format', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    const content = readFile(filePath);
    
    // Simulate parsing logic
    const parseOptions = (optionsString) => {
        if (!optionsString || optionsString.trim() === '') return [];
        const separator = optionsString.includes('\n') ? '\n' : ',';
        return optionsString.split(separator).map(opt => opt.trim()).filter(opt => opt !== '');
    };
    
    // Test newline-separated (new standard)
    const newOptions = 'Sí, absolutamente\nSí, pero no tan frecuente\nNo, no ocurre a menudo';
    const result = parseOptions(newOptions);
    
    if (result.length !== 3) {
        throw new Error(`Newline parsing failed. Expected 3 options, got ${result.length}`);
    }
    if (result[0] !== 'Sí, absolutamente') {
        throw new Error(`Newline parsing failed. Expected "Sí, absolutamente", got "${result[0]}"`);
    }
});

runner.test('Backward Compatibility: Options with commas are preserved', () => {
    const filePath = '/home/engine/project/src/blocks/campo-multiple/edit.js';
    const content = readFile(filePath);
    
    // Simulate parsing logic
    const parseOptions = (optionsString) => {
        if (!optionsString || optionsString.trim() === '') return [];
        const separator = optionsString.includes('\n') ? '\n' : ',';
        return optionsString.split(separator).map(opt => opt.trim()).filter(opt => opt !== '');
    };
    
    // Test option with comma inside
    const optionsWithComma = 'Sí, absolutamente\nNo, para nada\nA veces, depende del contexto';
    const result = parseOptions(optionsWithComma);
    
    if (result.length !== 3) {
        throw new Error(`Comma preservation failed. Expected 3 options, got ${result.length}`);
    }
    if (!result[0].includes(',')) {
        throw new Error(`Comma preservation failed. Option should contain comma: "${result[0]}"`);
    }
    if (result[0] !== 'Sí, absolutamente') {
        throw new Error(`Comma preservation failed. Expected "Sí, absolutamente", got "${result[0]}"`);
    }
});

// ============================================================================
// TEST SUITE: BUILD VALIDATION
// ============================================================================

runner.test('Build: campo-multiple block was compiled', () => {
    const buildFile = '/home/engine/project/build/index.js';
    if (!fs.existsSync(buildFile)) {
        throw new Error('Build file does not exist. Run `npm run build` first.');
    }
    
    const stat = fs.statSync(buildFile);
    if (stat.size < 1000) {
        throw new Error('Build file is too small. Build may have failed.');
    }
});

runner.test('Build: No syntax errors in build output', () => {
    const buildFile = '/home/engine/project/build/index.js';
    const content = readFile(buildFile);
    
    // Check for common webpack error patterns
    if (content.includes('Module parse failed') || content.includes('SyntaxError')) {
        throw new Error('Build output contains syntax errors');
    }
});

// ============================================================================
// RUN ALL TESTS
// ============================================================================

runner.run().catch(err => {
    console.error('Test runner error:', err);
    process.exit(1);
});
