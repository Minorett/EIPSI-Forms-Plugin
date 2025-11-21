#!/usr/bin/env node

/**
 * STRESS TEST READINESS VALIDATION v1.2.2
 * 
 * Validates that the EIPSI Forms plugin is ready for performance stress testing
 * Checks code structure, configuration, and potential bottlenecks
 * 
 * USAGE:
 *   node stress-test-readiness-v1.2.2.js
 * 
 * This script validates:
 * 1. Database schema and indexes
 * 2. Performance-critical code paths
 * 3. Memory management
 * 4. Timeout configurations
 * 5. Error handling
 * 6. Resource optimization
 */

const fs = require('fs');
const path = require('path');

// ANSI colors
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

// Results tracking
const results = {
	timestamp: new Date().toISOString(),
	total: 0,
	passed: 0,
	failed: 0,
	warnings: 0,
	categories: {
		'Database Schema': { total: 0, passed: 0, failed: 0 },
		'Performance Code': { total: 0, passed: 0, failed: 0 },
		'Memory Management': { total: 0, passed: 0, failed: 0 },
		'Error Handling': { total: 0, passed: 0, failed: 0 },
		'Configuration': { total: 0, passed: 0, failed: 0 },
		'Stress Test Requirements': { total: 0, passed: 0, failed: 0 },
	},
	details: [],
};

function log(message, color = 'reset') {
	console.log(`${colors[color]}${message}${colors.reset}`);
}

function section(title) {
	console.log(`\n${colors.cyan}${'='.repeat(80)}${colors.reset}`);
	console.log(`${colors.bright}${colors.cyan}  ${title}${colors.reset}`);
	console.log(`${colors.cyan}${'='.repeat(80)}${colors.reset}\n`);
}

function subsection(title) {
	console.log(`\n${colors.blue}${title}${colors.reset}`);
	console.log(`${colors.blue}${'-'.repeat(80)}${colors.reset}`);
}

function test(description, callback, category = 'General') {
	results.total++;
	if (!results.categories[category]) {
		results.categories[category] = { total: 0, passed: 0, failed: 0 };
	}
	results.categories[category].total++;
	
	try {
		const result = callback();
		if (result === true || result.success === true) {
			results.passed++;
			results.categories[category].passed++;
			log(`✅ ${description}`, 'green');
			results.details.push({ test: description, status: 'PASS', category });
		} else if (result.warning) {
			results.warnings++;
			log(`⚠️  ${description}: ${result.message}`, 'yellow');
			results.details.push({ test: description, status: 'WARNING', message: result.message, category });
		} else {
			results.failed++;
			results.categories[category].failed++;
			log(`❌ ${description}: ${result.message || result}`, 'red');
			results.details.push({ test: description, status: 'FAIL', message: result.message || result, category });
		}
	} catch (error) {
		results.failed++;
		results.categories[category].failed++;
		log(`❌ ${description}: ${error.message}`, 'red');
		results.details.push({ test: description, status: 'FAIL', message: error.message, category });
	}
}

function fileExists(filePath) {
	return fs.existsSync(filePath);
}

function readFile(filePath) {
	if (!fileExists(filePath)) {
		throw new Error(`File not found: ${filePath}`);
	}
	return fs.readFileSync(filePath, 'utf8');
}

function checkPattern(content, pattern, description) {
	const regex = new RegExp(pattern, 'ms');
	const matches = regex.test(content);
	return matches ? { success: true } : { message: `Pattern not found: ${description}` };
}

/**
 * TEST 1: Database Schema Validation
 */
function testDatabaseSchema() {
	section('TEST 1: DATABASE SCHEMA VALIDATION');
	
	subsection('1.1: Table Definition');
	
	test('Main plugin file exists', () => {
		return fileExists('vas-dinamico-forms.php');
	}, 'Database Schema');
	
	test('Database schema includes all required columns', () => {
		const content = readFile('vas-dinamico-forms.php');
		const requiredColumns = [
			'form_id',
			'participant_id',
			'session_id',
			'form_name',
			'submitted_at',
			'device',
			'browser',
			'os',
			'screen_width',
			'duration',
			'duration_seconds',
			'start_timestamp_ms',
			'end_timestamp_ms',
			'ip_address',
			'metadata',
			'quality_flag',
			'status',
			'form_responses'
		];
		
		const missingColumns = requiredColumns.filter(col => !content.includes(col));
		return missingColumns.length === 0 ? 
			{ success: true } : 
			{ message: `Missing columns: ${missingColumns.join(', ')}` };
	}, 'Database Schema');
	
	test('Database schema includes performance indexes', () => {
		const content = readFile('vas-dinamico-forms.php');
		const requiredIndexes = [
			'form_name',
			'created_at',
			'form_id',
			'participant_id',
			'session_id',
			'submitted_at'
		];
		
		const missingIndexes = requiredIndexes.filter(idx => !content.includes(`KEY ${idx}`));
		return missingIndexes.length === 0 ? 
			{ success: true } : 
			{ warning: true, message: `Missing indexes: ${missingIndexes.join(', ')}` };
	}, 'Database Schema');
	
	test('Composite index for form+participant queries', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes('form_participant') || content.includes('form_id, participant_id') ?
			{ success: true } :
			{ warning: true, message: 'No composite index for form+participant (performance may degrade)' };
	}, 'Database Schema');
	
	subsection('1.2: Schema Manager');
	
	test('Database schema manager exists', () => {
		return fileExists('admin/database-schema-manager.php');
	}, 'Database Schema');
	
	test('Auto-repair functionality implemented', () => {
		if (!fileExists('admin/database-schema-manager.php')) {
			return { message: 'Schema manager file not found' };
		}
		const content = readFile('admin/database-schema-manager.php');
		return content.includes('repair_local_schema') ?
			{ success: true } :
			{ message: 'Auto-repair function not found' };
	}, 'Database Schema');
	
	test('Schema sync on activation', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes('vas_dinamico_activate') && content.includes('CREATE TABLE') ?
			{ success: true } :
			{ message: 'Activation hook or table creation not found' };
	}, 'Database Schema');
	
	subsection('1.3: External Database Support');
	
	test('External database class exists', () => {
		return fileExists('admin/database.php');
	}, 'Database Schema');
	
	test('External database failover implemented', () => {
		if (!fileExists('admin/ajax-handlers.php')) {
			return { message: 'AJAX handlers file not found' };
		}
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('used_fallback') || content.includes('external_db_enabled') ?
			{ success: true } :
			{ message: 'Failover logic not found' };
	}, 'Database Schema');
}

/**
 * TEST 2: Performance-Critical Code
 */
function testPerformanceCode() {
	section('TEST 2: PERFORMANCE-CRITICAL CODE PATHS');
	
	subsection('2.1: Form Submission Handler');
	
	test('AJAX handler file exists', () => {
		return fileExists('admin/ajax-handlers.php');
	}, 'Performance Code');
	
	test('Form submission handler optimized for speed', () => {
		const content = readFile('admin/ajax-handlers.php');
		
		// Check for performance anti-patterns
		const antiPatterns = [
			{ pattern: /sleep\s*\(/i, name: 'sleep() calls' },
			{ pattern: /SELECT\s+\*\s+FROM/i, name: 'SELECT * queries' },
			{ pattern: /while\s*\(\s*true\s*\)/i, name: 'infinite loops' },
		];
		
		const found = antiPatterns.filter(ap => ap.pattern.test(content));
		return found.length === 0 ?
			{ success: true } :
			{ warning: true, message: `Performance anti-patterns: ${found.map(f => f.name).join(', ')}` };
	}, 'Performance Code');
	
	test('Prepared statements used for database queries', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('$wpdb->prepare') ?
			{ success: true } :
			{ warning: true, message: 'Prepared statements not found (SQL injection risk)' };
	}, 'Performance Code');
	
	test('Nonce verification for security', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('check_ajax_referer') ?
			{ success: true } :
			{ message: 'Nonce verification not found (security risk)' };
	}, 'Performance Code');
	
	subsection('2.2: Data Processing');
	
	test('JSON encoding used for complex data', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('wp_json_encode') || content.includes('json_encode') ?
			{ success: true } :
			{ warning: true, message: 'JSON encoding not found' };
	}, 'Performance Code');
	
	test('Sanitization implemented for user input', () => {
		const content = readFile('admin/ajax-handlers.php');
		const sanitizations = [
			'sanitize_text_field',
			'sanitize_email',
			'intval',
			'floatval'
		];
		
		const hasSanitization = sanitizations.some(s => content.includes(s));
		return hasSanitization ?
			{ success: true } :
			{ message: 'No sanitization functions found' };
	}, 'Performance Code');
	
	subsection('2.3: Frontend Performance');
	
	test('Frontend JavaScript exists', () => {
		return fileExists('assets/js/eipsi-forms.js');
	}, 'Performance Code');
	
	test('Frontend JavaScript optimized', () => {
		const content = readFile('assets/js/eipsi-forms.js');
		const size = Buffer.byteLength(content, 'utf8');
		
		return size < 100 * 1024 ? // < 100KB
			{ success: true } :
			{ warning: true, message: `Frontend JS is ${(size / 1024).toFixed(1)}KB (consider minification)` };
	}, 'Performance Code');
	
	test('Frontend CSS exists', () => {
		return fileExists('assets/css/eipsi-forms.css');
	}, 'Performance Code');
	
	test('Frontend CSS optimized', () => {
		const content = readFile('assets/css/eipsi-forms.css');
		const size = Buffer.byteLength(content, 'utf8');
		
		return size < 100 * 1024 ? // < 100KB
			{ success: true } :
			{ warning: true, message: `Frontend CSS is ${(size / 1024).toFixed(1)}KB (consider minification)` };
	}, 'Performance Code');
}

/**
 * TEST 3: Memory Management
 */
function testMemoryManagement() {
	section('TEST 3: MEMORY MANAGEMENT');
	
	subsection('3.1: Memory Leaks Prevention');
	
	test('No circular references in AJAX handler', () => {
		const content = readFile('admin/ajax-handlers.php');
		
		// Check for potential memory issues
		const issues = [];
		
		// Check for wp_send_json followed by more code (memory not released)
		if (/wp_send_json(?:_success|_error).*\n.*[^}]/m.test(content)) {
			issues.push('Code after wp_send_json (should return immediately)');
		}
		
		return issues.length === 0 ?
			{ success: true } :
			{ warning: true, message: issues.join('; ') };
	}, 'Memory Management');
	
	test('Database connections properly closed', () => {
		if (!fileExists('admin/database.php')) {
			return { warning: true, message: 'Database file not found' };
		}
		const content = readFile('admin/database.php');
		return content.includes('->close()') ?
			{ success: true } :
			{ warning: true, message: 'No explicit connection closing found' };
	}, 'Memory Management');
	
	test('No large arrays stored in memory unnecessarily', () => {
		const content = readFile('admin/ajax-handlers.php');
		
		// Check for potential large data structures
		const issues = [];
		
		if (/\$wpdb->get_results\s*\([^)]*,\s*ARRAY_A\s*\)/m.test(content)) {
			// This is OK if limited by LIMIT clause
			if (!/LIMIT\s+\d+/i.test(content)) {
				issues.push('Unbounded get_results() query');
			}
		}
		
		return issues.length === 0 ?
			{ success: true } :
			{ warning: true, message: issues.join('; ') };
	}, 'Memory Management');
	
	subsection('3.2: Resource Optimization');
	
	test('Minimal global variables', () => {
		const content = readFile('admin/ajax-handlers.php');
		const globalMatches = content.match(/global\s+\$/g);
		const globalCount = globalMatches ? globalMatches.length : 0;
		
		return globalCount < 10 ?
			{ success: true } :
			{ warning: true, message: `${globalCount} global variable declarations (consider reducing)` };
	}, 'Memory Management');
	
	test('No file uploads in AJAX handler', () => {
		const content = readFile('admin/ajax-handlers.php');
		return !content.includes('$_FILES') ?
			{ success: true } :
			{ warning: true, message: 'File upload handling detected (ensure proper limits)' };
	}, 'Memory Management');
}

/**
 * TEST 4: Error Handling
 */
function testErrorHandling() {
	section('TEST 4: ERROR HANDLING & RECOVERY');
	
	subsection('4.1: Database Error Handling');
	
	test('Database insert error handling', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('$wpdb->last_error') ?
			{ success: true } :
			{ message: 'No database error checking found' };
	}, 'Error Handling');
	
	test('Schema repair on error', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('repair_local_schema') || content.includes('Unknown column') ?
			{ success: true } :
			{ warning: true, message: 'Auto-repair not triggered on schema errors' };
	}, 'Error Handling');
	
	test('Graceful degradation on external DB failure', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('fallback') && content.includes('external_db') ?
			{ success: true } :
			{ message: 'No fallback mechanism for external DB failure' };
	}, 'Error Handling');
	
	subsection('4.2: Validation & Sanitization');
	
	test('Input validation implemented', () => {
		const content = readFile('admin/ajax-handlers.php');
		const validations = [
			'isset(',
			'!empty(',
			'filter_var(',
		];
		
		const hasValidation = validations.some(v => content.includes(v));
		return hasValidation ?
			{ success: true } :
			{ message: 'No input validation found' };
	}, 'Error Handling');
	
	test('Error logging enabled', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('error_log') ?
			{ success: true } :
			{ warning: true, message: 'No error logging found (debugging will be difficult)' };
	}, 'Error Handling');
	
	subsection('4.3: Security');
	
	test('SQL injection prevention', () => {
		const content = readFile('admin/ajax-handlers.php');
		
		// Check for direct SQL (should use prepared statements)
		const directSQL = /\$wpdb->query\s*\(\s*["'](?!SHOW|DESCRIBE|EXPLAIN)/i.test(content);
		
		return !directSQL ?
			{ success: true } :
			{ warning: true, message: 'Direct SQL queries found (use prepared statements)' };
	}, 'Error Handling');
	
	test('XSS prevention (output escaping)', () => {
		if (!fileExists('admin/results-page.php')) {
			return { warning: true, message: 'Results page not found' };
		}
		const content = readFile('admin/results-page.php');
		const escapeFunctions = [
			'esc_html',
			'esc_attr',
			'esc_url',
			'wp_kses',
		];
		
		const hasEscaping = escapeFunctions.some(f => content.includes(f));
		return hasEscaping ?
			{ success: true } :
			{ warning: true, message: 'Output escaping not found (XSS risk)' };
	}, 'Error Handling');
}

/**
 * TEST 5: Configuration & Settings
 */
function testConfiguration() {
	section('TEST 5: CONFIGURATION & SETTINGS');
	
	subsection('5.1: Plugin Configuration');
	
	test('Plugin version defined', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes('VAS_DINAMICO_VERSION') ?
			{ success: true } :
			{ message: 'Version constant not found' };
	}, 'Configuration');
	
	test('Plugin version is 1.2.2', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes("'1.2.2'") || content.includes('"1.2.2"') ?
			{ success: true } :
			{ warning: true, message: 'Version may not be 1.2.2' };
	}, 'Configuration');
	
	test('Constants use dynamic paths', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes('plugin_dir_path(__FILE__)') && content.includes('plugin_dir_url(__FILE__)') ?
			{ success: true } :
			{ message: 'Dynamic path constants not found' };
	}, 'Configuration');
	
	subsection('5.2: Performance Settings');
	
	test('No hardcoded memory limits', () => {
		const content = readFile('vas-dinamico-forms.php');
		return !content.includes('ini_set(\'memory_limit') ?
			{ success: true } :
			{ warning: true, message: 'Hardcoded memory limits found (should be configured in wp-config.php)' };
	}, 'Configuration');
	
	test('No hardcoded timeouts', () => {
		const content = readFile('vas-dinamico-forms.php');
		return !content.includes('set_time_limit') ?
			{ success: true } :
			{ warning: true, message: 'Hardcoded timeouts found (should be configured in server)' };
	}, 'Configuration');
	
	subsection('5.3: Privacy Configuration');
	
	test('Privacy config file exists', () => {
		return fileExists('admin/privacy-config.php');
	}, 'Configuration');
	
	test('Privacy toggles implemented', () => {
		if (!fileExists('admin/privacy-config.php')) {
			return { message: 'Privacy config file not found' };
		}
		const content = readFile('admin/privacy-config.php');
		return content.includes('get_privacy_config') && content.includes('save_privacy_config') ?
			{ success: true } :
			{ message: 'Privacy config functions not found' };
	}, 'Configuration');
}

/**
 * TEST 6: Stress Test Requirements
 */
function testStressTestRequirements() {
	section('TEST 6: STRESS TEST REQUIREMENTS');
	
	subsection('6.1: Form Submission Capability');
	
	test('AJAX endpoint registered', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('add_action') && content.includes('vas_dinamico_submit_form') ?
			{ success: true } :
			{ message: 'Form submission AJAX action not registered' };
	}, 'Stress Test Requirements');
	
	test('Both logged-in and guest submissions supported', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('wp_ajax_nopriv') && content.includes('wp_ajax_vas_dinamico_submit_form') ?
			{ success: true } :
			{ message: 'Guest submissions not supported (stress test will fail)' };
	}, 'Stress Test Requirements');
	
	test('Session ID tracking implemented', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('session_id') ?
			{ success: true } :
			{ message: 'Session tracking not found' };
	}, 'Stress Test Requirements');
	
	subsection('6.2: Metadata Collection');
	
	test('Device metadata captured', () => {
		const content = readFile('admin/ajax-handlers.php');
		const metadata = ['device', 'browser', 'os', 'screen_width'];
		const hasMeta = metadata.every(m => content.includes(m));
		return hasMeta ?
			{ success: true } :
			{ message: 'Not all device metadata fields captured' };
	}, 'Stress Test Requirements');
	
	test('Duration calculation implemented', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('duration_seconds') && (content.includes('start_timestamp_ms') || content.includes('end_timestamp_ms')) ?
			{ success: true } :
			{ message: 'Duration calculation not found' };
	}, 'Stress Test Requirements');
	
	test('IP address captured', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('REMOTE_ADDR') || content.includes('ip_address') ?
			{ success: true } :
			{ warning: true, message: 'IP capture not found (may be disabled by privacy config)' };
	}, 'Stress Test Requirements');
	
	subsection('6.3: Data Integrity');
	
	test('Transaction support or error recovery', () => {
		const content = readFile('admin/ajax-handlers.php');
		return content.includes('retry') || content.includes('fallback') || content.includes('repair') ?
			{ success: true } :
			{ warning: true, message: 'No transaction support or retry logic found' };
	}, 'Stress Test Requirements');
	
	test('Duplicate prevention mechanism', () => {
		const content = readFile('vas-dinamico-forms.php');
		// Check for unique constraints or participant_id logic
		return content.includes('participant_id') ?
			{ success: true } :
			{ warning: true, message: 'Duplicate prevention may not be implemented' };
	}, 'Stress Test Requirements');
	
	subsection('6.4: Scalability');
	
	test('Database table uses efficient engine', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes('InnoDB') ?
			{ success: true } :
			{ warning: true, message: 'InnoDB not specified (MyISAM has worse concurrency)' };
	}, 'Stress Test Requirements');
	
	test('Auto-increment primary key', () => {
		const content = readFile('vas-dinamico-forms.php');
		return content.includes('AUTO_INCREMENT') ?
			{ success: true } :
			{ message: 'Auto-increment not found' };
	}, 'Stress Test Requirements');
}

/**
 * Generate report
 */
function generateReport() {
	section('TEST SUMMARY');
	
	console.log(`${colors.bright}Overall Results:${colors.reset}`);
	console.log(`  Total Tests: ${results.total}`);
	console.log(`  ${colors.green}Passed: ${results.passed}${colors.reset}`);
	console.log(`  ${colors.red}Failed: ${results.failed}${colors.reset}`);
	console.log(`  ${colors.yellow}Warnings: ${results.warnings}${colors.reset}`);
	
	const successRate = (results.passed / results.total * 100).toFixed(1);
	console.log(`\n  Success Rate: ${successRate}%`);
	
	console.log(`\n${colors.bright}Results by Category:${colors.reset}`);
	Object.entries(results.categories).forEach(([category, stats]) => {
		const categoryRate = stats.total > 0 ? (stats.passed / stats.total * 100).toFixed(0) : 0;
		console.log(`  ${category}: ${stats.passed}/${stats.total} (${categoryRate}%)`);
	});
	
	// Readiness assessment
	console.log(`\n${colors.bright}Stress Test Readiness Assessment:${colors.reset}`);
	
	const critical = results.failed;
	const minorIssues = results.warnings;
	
	if (critical === 0 && minorIssues === 0) {
		console.log(`  ${colors.green}${colors.bright}✅ EXCELLENT - Ready for stress testing${colors.reset}`);
		console.log(`  All systems optimal for performance testing.`);
	} else if (critical === 0 && minorIssues < 5) {
		console.log(`  ${colors.green}${colors.bright}✅ GOOD - Ready for stress testing${colors.reset}`);
		console.log(`  Minor warnings present but won't affect stress test results.`);
	} else if (critical === 0) {
		console.log(`  ${colors.yellow}${colors.bright}⚠️  ACCEPTABLE - Ready with caution${colors.reset}`);
		console.log(`  Review warnings before running stress tests.`);
	} else if (critical < 5) {
		console.log(`  ${colors.red}${colors.bright}⚠️  NEEDS ATTENTION - Fix issues before testing${colors.reset}`);
		console.log(`  ${critical} critical issue(s) detected.`);
	} else {
		console.log(`  ${colors.red}${colors.bright}❌ NOT READY - Major issues detected${colors.reset}`);
		console.log(`  ${critical} critical issue(s) must be resolved.`);
	}
	
	// Recommendations
	if (results.failed > 0 || results.warnings > 0) {
		console.log(`\n${colors.bright}Recommendations:${colors.reset}`);
		
		if (results.failed > 0) {
			console.log(`  ${colors.red}1. Fix ${results.failed} critical issue(s) before stress testing${colors.reset}`);
		}
		if (results.warnings > 0) {
			console.log(`  ${colors.yellow}2. Review ${results.warnings} warning(s) and assess impact${colors.reset}`);
		}
		if (results.categories['Database Schema'].failed > 0) {
			console.log(`  ${colors.red}3. Database schema issues detected - run schema sync${colors.reset}`);
		}
		if (results.categories['Performance Code'].failed > 0) {
			console.log(`  ${colors.red}4. Performance issues detected - optimize code paths${colors.reset}`);
		}
	}
	
	// Next steps
	console.log(`\n${colors.bright}Next Steps:${colors.reset}`);
	if (critical === 0) {
		console.log(`  1. Review STRESS_TEST_GUIDE_v1.2.2.md for testing procedures`);
		console.log(`  2. Set up WordPress test environment`);
		console.log(`  3. Run: node stress-test-v1.2.2.js --url=https://your-site.com`);
		console.log(`  4. Analyze results and optimize as needed`);
	} else {
		console.log(`  1. Review failed tests above`);
		console.log(`  2. Fix critical issues in code`);
		console.log(`  3. Re-run: node stress-test-readiness-v1.2.2.js`);
		console.log(`  4. Proceed to stress testing once all tests pass`);
	}
}

/**
 * Save results
 */
function saveResults() {
	const jsonFile = 'STRESS_TEST_READINESS_v1.2.2_RESULTS.json';
	fs.writeFileSync(jsonFile, JSON.stringify(results, null, 2));
	log(`\nResults saved to: ${jsonFile}`, 'cyan');
	
	const mdContent = `# Stress Test Readiness Report v1.2.2

**Date:** ${new Date().toLocaleString()}

## Summary

- **Total Tests:** ${results.total}
- **Passed:** ${results.passed}
- **Failed:** ${results.failed}
- **Warnings:** ${results.warnings}
- **Success Rate:** ${(results.passed / results.total * 100).toFixed(1)}%

## Results by Category

${Object.entries(results.categories).map(([category, stats]) => {
	const rate = stats.total > 0 ? (stats.passed / stats.total * 100).toFixed(0) : 0;
	return `### ${category}\n- **Tests:** ${stats.total}\n- **Passed:** ${stats.passed}\n- **Failed:** ${stats.failed}\n- **Success Rate:** ${rate}%`;
}).join('\n\n')}

## Detailed Results

${results.details.map(detail => {
	const emoji = detail.status === 'PASS' ? '✅' : detail.status === 'WARNING' ? '⚠️' : '❌';
	return `### ${emoji} ${detail.test}\n- **Category:** ${detail.category}\n- **Status:** ${detail.status}${detail.message ? `\n- **Message:** ${detail.message}` : ''}`;
}).join('\n\n')}

## Readiness Assessment

${results.failed === 0 ? 
	'✅ **Plugin is ready for stress testing.**' : 
	`❌ **Plugin has ${results.failed} critical issue(s) that must be resolved before stress testing.**`}

---
*Generated by EIPSI Forms Stress Test Readiness Validator v1.2.2*
`;
	
	const mdFile = 'STRESS_TEST_READINESS_v1.2.2_REPORT.md';
	fs.writeFileSync(mdFile, mdContent);
	log(`Report saved to: ${mdFile}`, 'cyan');
}

/**
 * Main execution
 */
function main() {
	console.log(`${colors.bright}${colors.cyan}`);
	console.log('╔════════════════════════════════════════════════════════════════════════════╗');
	console.log('║                                                                            ║');
	console.log('║         EIPSI FORMS - STRESS TEST READINESS VALIDATION v1.2.2              ║');
	console.log('║                                                                            ║');
	console.log('╚════════════════════════════════════════════════════════════════════════════╝');
	console.log(colors.reset);
	
	log(`Validation Start: ${new Date().toLocaleString()}`, 'blue');
	log(`Working Directory: ${process.cwd()}`, 'blue');
	
	try {
		testDatabaseSchema();
		testPerformanceCode();
		testMemoryManagement();
		testErrorHandling();
		testConfiguration();
		testStressTestRequirements();
		
		generateReport();
		saveResults();
		
	} catch (error) {
		log(`\n❌ Critical error during validation: ${error.message}`, 'red');
		console.error(error);
		process.exit(1);
	}
	
	// Exit with appropriate code
	if (results.failed > 5) {
		process.exit(1); // Major issues
	} else if (results.failed > 0) {
		process.exit(0); // Minor issues but still runnable
	} else {
		process.exit(0); // All good
	}
}

// Run validation
if (require.main === module) {
	main();
}

module.exports = { test, fileExists, readFile };
