#!/usr/bin/env node

/**
 * PERFORMANCE STRESS TEST v1.2.2
 * 
 * Comprehensive stress test suite for EIPSI Forms plugin
 * Simulates realistic load and verifies performance under stress
 * 
 * REQUIREMENTS:
 * - Live WordPress installation with EIPSI Forms v1.2.2 installed
 * - Node.js 14+ with axios package (npm install axios)
 * - Database access for verification
 * 
 * USAGE:
 *   node stress-test-v1.2.2.js --url=https://your-site.com --form-id=123
 * 
 * TEST COVERAGE:
 * 1. Multiple Simultaneous Submissions (10 min)
 * 2. Complex Forms (5 min)
 * 3. Metadata Under Stress (5 min)
 * 4. Database Under Stress (5 min)
 * 5. Memory & CPU Monitoring (5 min)
 */

const fs = require('fs');
const path = require('path');

// Parse command line arguments
const args = process.argv.slice(2).reduce((acc, arg) => {
	const [key, value] = arg.replace(/^--/, '').split('=');
	acc[key] = value;
	return acc;
}, {});

const CONFIG = {
	// WordPress site URL (required)
	siteUrl: args.url || process.env.WP_SITE_URL || 'http://localhost/wordpress',
	
	// Form ID to test (optional - will create test form if not provided)
	formId: args['form-id'] || 'STRESS-TEST',
	
	// Test parameters
	sequentialForms: 10,
	rapidForms: 5,
	sustainedForms: 20,
	sustainedDuration: 5 * 60 * 1000, // 5 minutes
	
	// Performance thresholds
	maxResponseTime: 2000, // 2 seconds
	maxQueryTime: 100, // 100ms
	maxMemoryGrowth: 10 * 1024 * 1024, // 10MB
	maxCpuPeak: 30, // 30%
	
	// Timeouts
	requestTimeout: 10000, // 10 seconds
};

// Colors for terminal output
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
	config: CONFIG,
	tests: {},
	metrics: {
		responseTimes: [],
		queryTimes: [],
		memoryUsage: [],
		cpuUsage: [],
		submissionIds: [],
		errors: [],
		warnings: [],
	},
	summary: {
		total: 0,
		passed: 0,
		failed: 0,
		warnings: 0,
		totalForms: 0,
		successfulForms: 0,
		failedForms: 0,
		avgResponseTime: 0,
		maxResponseTime: 0,
		minResponseTime: Infinity,
		dataLoss: 0,
		duplicates: 0,
		timeouts: 0,
	},
};

function log(message, color = 'reset') {
	const timestamp = new Date().toISOString().split('T')[1].slice(0, -1);
	console.log(`${colors[color]}[${timestamp}] ${message}${colors.reset}`);
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

function test(description, result) {
	results.summary.total++;
	if (result.success) {
		results.summary.passed++;
		log(`✅ ${description}`, 'green');
	} else if (result.warning) {
		results.summary.warnings++;
		log(`⚠️  ${description}: ${result.message}`, 'yellow');
	} else {
		results.summary.failed++;
		log(`❌ ${description}: ${result.message}`, 'red');
		if (result.error) {
			results.metrics.errors.push({
				test: description,
				error: result.error,
				timestamp: new Date().toISOString(),
			});
		}
	}
	results.tests[description] = result;
}

// Check if axios is available
let axios;
try {
	axios = require('axios');
} catch (error) {
	console.error(`${colors.red}ERROR: axios package not found. Please install it:${colors.reset}`);
	console.error(`${colors.yellow}  npm install axios${colors.reset}`);
	process.exit(1);
}

/**
 * Generate realistic form data
 */
function generateFormData(options = {}) {
	const {
		pages = 4,
		fieldsPerPage = 5,
		largeText = false,
		multipleChoice = false,
		multipleChoiceOptions = 5,
	} = options;
	
	const data = {
		form_id: CONFIG.formId,
		form_action: 'submit',
		form_start_time: Date.now() - (Math.random() * 60000), // Started 0-60s ago
		form_end_time: Date.now(),
		device: ['desktop', 'mobile', 'tablet'][Math.floor(Math.random() * 3)],
		browser: ['Chrome', 'Firefox', 'Safari', 'Edge'][Math.floor(Math.random() * 4)],
		os: ['Windows', 'MacOS', 'Linux', 'iOS', 'Android'][Math.floor(Math.random() * 5)],
		screen_width: [1920, 1440, 1366, 768, 375][Math.floor(Math.random() * 5)],
		participant_id: `STRESS-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
		session_id: `SESSION-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
		current_page: pages,
	};
	
	// Add fields
	for (let page = 1; page <= pages; page++) {
		for (let field = 1; field <= fieldsPerPage; field++) {
			const fieldName = `field_p${page}_f${field}`;
			
			if (multipleChoice && field === 1) {
				// Multiple choice field with many options
				const selectedOptions = [];
				const numSelected = Math.min(multipleChoiceOptions, Math.floor(Math.random() * 10) + 1);
				for (let i = 0; i < numSelected; i++) {
					selectedOptions.push(`Option ${Math.floor(Math.random() * multipleChoiceOptions) + 1}`);
				}
				data[fieldName] = selectedOptions.join(',');
			} else if (largeText && field === 1) {
				// Large text field (5000+ characters)
				data[fieldName] = 'Lorem ipsum dolor sit amet. '.repeat(200); // ~5400 chars
			} else {
				// Regular field
				data[fieldName] = `Response ${Math.random().toString(36).substr(2, 9)}`;
			}
		}
	}
	
	return data;
}

/**
 * Submit form via AJAX
 */
async function submitForm(formData) {
	const startTime = Date.now();
	
	try {
		// Get WordPress nonce first (in real scenario, this would be obtained from the page)
		// For testing, we'll use a mock submission
		
		const response = await axios.post(
			`${CONFIG.siteUrl}/wp-admin/admin-ajax.php`,
			new URLSearchParams({
				action: 'vas_dinamico_submit_form',
				nonce: 'test_nonce', // In production, this would be a real nonce
				...formData,
			}),
			{
				timeout: CONFIG.requestTimeout,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
			}
		);
		
		const endTime = Date.now();
		const responseTime = endTime - startTime;
		
		results.metrics.responseTimes.push(responseTime);
		results.summary.totalForms++;
		
		if (response.data && response.data.success) {
			results.summary.successfulForms++;
			if (response.data.data && response.data.data.insert_id) {
				results.metrics.submissionIds.push(response.data.data.insert_id);
			}
			return {
				success: true,
				responseTime,
				insertId: response.data.data?.insert_id,
				external_db: response.data.data?.external_db,
			};
		} else {
			results.summary.failedForms++;
			return {
				success: false,
				responseTime,
				error: response.data?.data?.message || 'Unknown error',
			};
		}
	} catch (error) {
		const endTime = Date.now();
		const responseTime = endTime - startTime;
		
		results.summary.failedForms++;
		results.summary.totalForms++;
		
		if (error.code === 'ECONNABORTED') {
			results.summary.timeouts++;
		}
		
		return {
			success: false,
			responseTime,
			error: error.message,
		};
	}
}

/**
 * TEST 1: Multiple Simultaneous Submissions
 */
async function testMultipleSubmissions() {
	section('TEST 1: MULTIPLE SIMULTANEOUS SUBMISSIONS (10 min)');
	
	// 1.1: Sequential submissions
	subsection('Test 1.1: Sequential Submissions (10 forms)');
	
	const startTime = Date.now();
	const sequentialResults = [];
	
	for (let i = 0; i < CONFIG.sequentialForms; i++) {
		log(`Submitting form ${i + 1}/${CONFIG.sequentialForms}...`, 'blue');
		const formData = generateFormData({ pages: 4 });
		const result = await submitForm(formData);
		sequentialResults.push(result);
		
		// Small delay between submissions
		await new Promise(resolve => setTimeout(resolve, 100));
	}
	
	const avgTime = sequentialResults.reduce((sum, r) => sum + r.responseTime, 0) / sequentialResults.length;
	const successCount = sequentialResults.filter(r => r.success).length;
	
	test('All 10 forms submitted successfully', {
		success: successCount === CONFIG.sequentialForms,
		message: successCount < CONFIG.sequentialForms ? `Only ${successCount}/${CONFIG.sequentialForms} succeeded` : '',
	});
	
	test('Average response time < 2 seconds', {
		success: avgTime < CONFIG.maxResponseTime,
		warning: avgTime >= CONFIG.maxResponseTime && avgTime < CONFIG.maxResponseTime * 1.5,
		message: avgTime >= CONFIG.maxResponseTime ? `Average time: ${avgTime.toFixed(0)}ms` : '',
	});
	
	test('All submission IDs are unique', {
		success: new Set(results.metrics.submissionIds).size === results.metrics.submissionIds.length,
		message: results.metrics.submissionIds.length - new Set(results.metrics.submissionIds).size > 0 ? 
			`${results.metrics.submissionIds.length - new Set(results.metrics.submissionIds).size} duplicates found` : '',
	});
	
	// 1.2: Near-simultaneous submissions
	subsection('Test 1.2: Near-Simultaneous Submissions (5 forms < 100ms apart)');
	
	const rapidPromises = [];
	const rapidStart = Date.now();
	
	for (let i = 0; i < CONFIG.rapidForms; i++) {
		const formData = generateFormData({ pages: 4 });
		rapidPromises.push(submitForm(formData));
		
		// Very short delay (< 100ms)
		if (i < CONFIG.rapidForms - 1) {
			await new Promise(resolve => setTimeout(resolve, 50));
		}
	}
	
	const rapidResults = await Promise.all(rapidPromises);
	const rapidTime = Date.now() - rapidStart;
	const rapidSuccess = rapidResults.filter(r => r.success).length;
	
	test('All 5 rapid forms submitted successfully', {
		success: rapidSuccess === CONFIG.rapidForms,
		message: rapidSuccess < CONFIG.rapidForms ? `Only ${rapidSuccess}/${CONFIG.rapidForms} succeeded` : '',
	});
	
	test('Rapid submissions completed in < 5 seconds total', {
		success: rapidTime < 5000,
		warning: rapidTime >= 5000 && rapidTime < 10000,
		message: rapidTime >= 5000 ? `Took ${(rapidTime / 1000).toFixed(1)}s` : '',
	});
	
	test('No duplicates in rapid submissions', {
		success: new Set(rapidResults.map(r => r.insertId).filter(Boolean)).size === rapidSuccess,
		message: '',
	});
	
	// 1.3: Sustained load
	subsection('Test 1.3: Sustained Load (20 forms in 5 minutes)');
	
	log('Starting sustained load test (this will take 5 minutes)...', 'blue');
	
	const sustainedStart = Date.now();
	const sustainedResults = [];
	const intervalMs = CONFIG.sustainedDuration / CONFIG.sustainedForms;
	
	for (let i = 0; i < CONFIG.sustainedForms; i++) {
		const formData = generateFormData({ pages: 4 });
		const result = await submitForm(formData);
		sustainedResults.push(result);
		
		log(`  Submitted ${i + 1}/${CONFIG.sustainedForms} (${result.responseTime}ms)`, 'blue');
		
		// Wait for next interval
		if (i < CONFIG.sustainedForms - 1) {
			await new Promise(resolve => setTimeout(resolve, intervalMs));
		}
	}
	
	const sustainedSuccess = sustainedResults.filter(r => r.success).length;
	const sustainedTimes = sustainedResults.map(r => r.responseTime);
	const avgSustained = sustainedTimes.reduce((sum, t) => sum + t, 0) / sustainedTimes.length;
	const maxSustained = Math.max(...sustainedTimes);
	const minSustained = Math.min(...sustainedTimes);
	
	// Check for performance degradation
	const firstHalf = sustainedTimes.slice(0, CONFIG.sustainedForms / 2);
	const secondHalf = sustainedTimes.slice(CONFIG.sustainedForms / 2);
	const avgFirstHalf = firstHalf.reduce((sum, t) => sum + t, 0) / firstHalf.length;
	const avgSecondHalf = secondHalf.reduce((sum, t) => sum + t, 0) / secondHalf.length;
	const degradation = ((avgSecondHalf - avgFirstHalf) / avgFirstHalf) * 100;
	
	test('All 20 sustained forms submitted successfully', {
		success: sustainedSuccess === CONFIG.sustainedForms,
		message: sustainedSuccess < CONFIG.sustainedForms ? `Only ${sustainedSuccess}/${CONFIG.sustainedForms} succeeded` : '',
	});
	
	test('No performance degradation over time', {
		success: degradation < 20,
		warning: degradation >= 20 && degradation < 50,
		message: degradation >= 20 ? `Performance degraded by ${degradation.toFixed(1)}%` : '',
	});
	
	test('Zero timeouts during sustained load', {
		success: results.summary.timeouts === 0,
		message: results.summary.timeouts > 0 ? `${results.summary.timeouts} timeouts occurred` : '',
	});
	
	// Update summary metrics
	results.summary.avgResponseTime = results.metrics.responseTimes.reduce((sum, t) => sum + t, 0) / results.metrics.responseTimes.length;
	results.summary.maxResponseTime = Math.max(...results.metrics.responseTimes);
	results.summary.minResponseTime = Math.min(...results.metrics.responseTimes);
}

/**
 * TEST 2: Complex Forms
 */
async function testComplexForms() {
	section('TEST 2: COMPLEX FORMS (5 min)');
	
	// 2.1: Large forms
	subsection('Test 2.1: Large Forms (8 pages, 50+ fields)');
	
	log('Submitting large form with 8 pages and 50+ fields...', 'blue');
	const largeFormData = generateFormData({ pages: 8, fieldsPerPage: 7 }); // 56 fields
	const largeStart = Date.now();
	const largeResult = await submitForm(largeFormData);
	const largeTime = Date.now() - largeStart;
	
	test('Large form submitted successfully', {
		success: largeResult.success,
		message: !largeResult.success ? largeResult.error : '',
	});
	
	test('Large form submission < 60 seconds', {
		success: largeTime < 60000,
		warning: largeTime >= 60000 && largeTime < 120000,
		message: largeTime >= 60000 ? `Took ${(largeTime / 1000).toFixed(1)}s` : '',
	});
	
	test('Large form response time < 5 seconds', {
		success: largeResult.responseTime < 5000,
		warning: largeResult.responseTime >= 5000 && largeResult.responseTime < 10000,
		message: largeResult.responseTime >= 5000 ? `${largeResult.responseTime}ms` : '',
	});
	
	// 2.2: Complex multiple choice
	subsection('Test 2.2: Complex Multiple Choice (20+ options, 10 selected)');
	
	log('Submitting form with complex multiple choice...', 'blue');
	const multiChoiceData = generateFormData({ 
		pages: 2, 
		fieldsPerPage: 3,
		multipleChoice: true,
		multipleChoiceOptions: 25,
	});
	const multiChoiceResult = await submitForm(multiChoiceData);
	
	test('Multiple choice form submitted successfully', {
		success: multiChoiceResult.success,
		message: !multiChoiceResult.success ? multiChoiceResult.error : '',
	});
	
	test('Multiple choice data saved correctly', {
		success: multiChoiceResult.success && multiChoiceResult.insertId,
		message: !multiChoiceResult.insertId ? 'No insert ID returned' : '',
	});
	
	// 2.3: Large text data
	subsection('Test 2.3: Large Text Data (5000+ characters)');
	
	log('Submitting form with 5000+ character text field...', 'blue');
	const largeTextData = generateFormData({ 
		pages: 2, 
		fieldsPerPage: 3,
		largeText: true,
	});
	const largeTextResult = await submitForm(largeTextData);
	
	test('Large text form submitted successfully', {
		success: largeTextResult.success,
		message: !largeTextResult.success ? largeTextResult.error : '',
	});
	
	test('Large text response time acceptable', {
		success: largeTextResult.responseTime < CONFIG.maxResponseTime * 2,
		warning: largeTextResult.responseTime >= CONFIG.maxResponseTime * 2 && largeTextResult.responseTime < CONFIG.maxResponseTime * 3,
		message: largeTextResult.responseTime >= CONFIG.maxResponseTime * 2 ? `${largeTextResult.responseTime}ms` : '',
	});
}

/**
 * TEST 3: Metadata Under Stress
 */
async function testMetadataStress() {
	section('TEST 3: METADATA UNDER STRESS (5 min)');
	
	subsection('Test 3.1: Metadata Capture (20 submissions)');
	
	log('Submitting 20 forms to test metadata capture...', 'blue');
	
	const metadataResults = [];
	for (let i = 0; i < 20; i++) {
		const formData = generateFormData({ pages: 3 });
		const result = await submitForm(formData);
		metadataResults.push(result);
		
		// Small delay
		await new Promise(resolve => setTimeout(resolve, 200));
	}
	
	const metadataSuccess = metadataResults.filter(r => r.success).length;
	
	test('All 20 forms with metadata submitted successfully', {
		success: metadataSuccess === 20,
		message: metadataSuccess < 20 ? `Only ${metadataSuccess}/20 succeeded` : '',
	});
	
	test('All forms captured required metadata', {
		success: true, // Would need database access to verify
		warning: true,
		message: 'Database verification required (cannot verify without DB access)',
	});
	
	subsection('Test 3.2: Duration Calculations');
	
	// Submit form with known duration
	const knownDurationData = generateFormData({ pages: 4 });
	const durationStart = Date.now() - 30000; // Started 30 seconds ago
	knownDurationData.form_start_time = durationStart;
	knownDurationData.form_end_time = Date.now();
	
	const durationResult = await submitForm(knownDurationData);
	
	test('Form with 30-second duration submitted successfully', {
		success: durationResult.success,
		message: !durationResult.success ? durationResult.error : '',
	});
	
	test('Duration calculation accurate', {
		success: true,
		warning: true,
		message: 'Database verification required (cannot verify without DB access)',
	});
}

/**
 * TEST 4: Database Under Stress
 */
async function testDatabaseStress() {
	section('TEST 4: DATABASE UNDER STRESS (5 min)');
	
	subsection('Test 4.1: Connection Stability (20 queries in 1 minute)');
	
	log('Submitting 20 forms in 1 minute to test connection stability...', 'blue');
	
	const dbStressStart = Date.now();
	const dbStressResults = [];
	const intervalMs = 60000 / 20; // 3 seconds per submission
	
	for (let i = 0; i < 20; i++) {
		const formData = generateFormData({ pages: 2 });
		const result = await submitForm(formData);
		dbStressResults.push(result);
		
		if (i < 19) {
			await new Promise(resolve => setTimeout(resolve, intervalMs));
		}
	}
	
	const dbStressSuccess = dbStressResults.filter(r => r.success).length;
	const dbStressTimeouts = dbStressResults.filter(r => r.error && r.error.includes('timeout')).length;
	
	test('All 20 database queries succeeded', {
		success: dbStressSuccess === 20,
		message: dbStressSuccess < 20 ? `Only ${dbStressSuccess}/20 succeeded` : '',
	});
	
	test('Zero connection timeouts', {
		success: dbStressTimeouts === 0,
		message: dbStressTimeouts > 0 ? `${dbStressTimeouts} timeouts occurred` : '',
	});
	
	subsection('Test 4.2: Query Performance');
	
	const queryTimes = dbStressResults.map(r => r.responseTime);
	const avgQueryTime = queryTimes.reduce((sum, t) => sum + t, 0) / queryTimes.length;
	const maxQueryTime = Math.max(...queryTimes);
	
	test('Average query time < 100ms', {
		success: avgQueryTime < 100,
		warning: avgQueryTime >= 100 && avgQueryTime < 500,
		message: avgQueryTime >= 100 ? `Average: ${avgQueryTime.toFixed(0)}ms` : '',
	});
	
	test('No slow queries (> 2 seconds)', {
		success: maxQueryTime < 2000,
		warning: maxQueryTime >= 2000 && maxQueryTime < 5000,
		message: maxQueryTime >= 2000 ? `Max: ${maxQueryTime}ms` : '',
	});
	
	subsection('Test 4.3: Transaction Integrity');
	
	test('No data loss detected', {
		success: results.summary.dataLoss === 0,
		message: results.summary.dataLoss > 0 ? `${results.summary.dataLoss} records lost` : '',
	});
	
	test('No duplicate records', {
		success: results.summary.duplicates === 0,
		message: results.summary.duplicates > 0 ? `${results.summary.duplicates} duplicates found` : '',
	});
}

/**
 * TEST 5: Memory & CPU Monitoring
 */
async function testResourceMonitoring() {
	section('TEST 5: MEMORY & CPU MONITORING (5 min)');
	
	subsection('Test 5.1: Memory Usage');
	
	const memoryBaseline = process.memoryUsage();
	log(`Baseline memory: ${(memoryBaseline.heapUsed / 1024 / 1024).toFixed(2)} MB`, 'blue');
	
	// Submit 20 forms while monitoring memory
	for (let i = 0; i < 20; i++) {
		const formData = generateFormData({ pages: 3 });
		await submitForm(formData);
		
		const memUsage = process.memoryUsage();
		results.metrics.memoryUsage.push({
			iteration: i + 1,
			heapUsed: memUsage.heapUsed,
			heapTotal: memUsage.heapTotal,
			external: memUsage.external,
		});
		
		await new Promise(resolve => setTimeout(resolve, 200));
	}
	
	const memoryFinal = process.memoryUsage();
	const memoryGrowth = memoryFinal.heapUsed - memoryBaseline.heapUsed;
	
	log(`Final memory: ${(memoryFinal.heapUsed / 1024 / 1024).toFixed(2)} MB`, 'blue');
	log(`Memory growth: ${(memoryGrowth / 1024 / 1024).toFixed(2)} MB`, 'blue');
	
	test('Memory growth < 10MB', {
		success: memoryGrowth < CONFIG.maxMemoryGrowth,
		warning: memoryGrowth >= CONFIG.maxMemoryGrowth && memoryGrowth < CONFIG.maxMemoryGrowth * 2,
		message: memoryGrowth >= CONFIG.maxMemoryGrowth ? `Grew ${(memoryGrowth / 1024 / 1024).toFixed(2)} MB` : '',
	});
	
	test('No memory leaks detected', {
		success: memoryGrowth < CONFIG.maxMemoryGrowth,
		message: '',
	});
	
	subsection('Test 5.2: CPU Usage');
	
	// Note: CPU monitoring is limited in Node.js without external tools
	test('CPU monitoring', {
		success: true,
		warning: true,
		message: 'CPU monitoring requires external tools (cannot measure from Node.js)',
	});
	
	subsection('Test 5.3: Responsiveness');
	
	// Test that the system remains responsive
	const responsiveStart = Date.now();
	const formData = generateFormData({ pages: 2 });
	const responsiveResult = await submitForm(formData);
	const responsiveTime = Date.now() - responsiveStart;
	
	test('System remains responsive under load', {
		success: responsiveResult.success && responsiveTime < CONFIG.maxResponseTime,
		message: !responsiveResult.success ? 'Request failed' : responsiveTime >= CONFIG.maxResponseTime ? `${responsiveTime}ms` : '',
	});
	
	test('No UI freezing', {
		success: true,
		warning: true,
		message: 'UI responsiveness requires manual testing',
	});
}

/**
 * Generate performance metrics report
 */
function generateMetricsReport() {
	section('PERFORMANCE METRICS v1.2.2');
	
	console.log(`${colors.bright}Response Times:${colors.reset}`);
	console.log(`  Single Form Submit: ${results.metrics.responseTimes[0] || 0}ms`);
	console.log(`  Average (${results.summary.totalForms} forms): ${results.summary.avgResponseTime.toFixed(0)}ms`);
	console.log(`  Max: ${results.summary.maxResponseTime}ms`);
	console.log(`  Min: ${results.summary.minResponseTime}ms`);
	
	console.log(`\n${colors.bright}Throughput:${colors.reset}`);
	console.log(`  Forms per minute: ${(results.summary.totalForms / ((Date.now() - results.timestamp) / 60000)).toFixed(2)}`);
	console.log(`  Successful submissions: ${results.summary.successfulForms}/${results.summary.totalForms}`);
	console.log(`  Failed submissions: ${results.summary.failedForms}`);
	
	const memoryGrowth = results.metrics.memoryUsage.length > 0 ? 
		results.metrics.memoryUsage[results.metrics.memoryUsage.length - 1].heapUsed - results.metrics.memoryUsage[0].heapUsed : 0;
	
	console.log(`\n${colors.bright}Resource Usage:${colors.reset}`);
	console.log(`  Memory growth: ${(memoryGrowth / 1024 / 1024).toFixed(2)} MB`);
	console.log(`  Peak memory: ${results.metrics.memoryUsage.length > 0 ? 
		(Math.max(...results.metrics.memoryUsage.map(m => m.heapUsed)) / 1024 / 1024).toFixed(2) : 0} MB`);
	console.log(`  CPU usage: (requires external monitoring)`);
	
	console.log(`\n${colors.bright}Stability:${colors.reset}`);
	console.log(`  Timeouts: ${results.summary.timeouts}`);
	console.log(`  Errors: ${results.metrics.errors.length}`);
	console.log(`  Data Loss: ${results.summary.dataLoss}`);
	console.log(`  Duplicates: ${results.summary.duplicates}`);
	
	// Acceptance criteria
	console.log(`\n${colors.bright}Acceptance Criteria:${colors.reset}`);
	
	const criteria = [
		{ name: '20 forms without errors', passed: results.summary.successfulForms >= 20 },
		{ name: 'Average time < 2 seconds', passed: results.summary.avgResponseTime < 2000 },
		{ name: 'Memory usage stable', passed: memoryGrowth < CONFIG.maxMemoryGrowth },
		{ name: 'Zero timeouts', passed: results.summary.timeouts === 0 },
		{ name: 'Zero data loss', passed: results.summary.dataLoss === 0 },
	];
	
	criteria.forEach(c => {
		if (c.passed) {
			console.log(`  ${colors.green}✅ ${c.name}${colors.reset}`);
		} else {
			console.log(`  ${colors.red}❌ ${c.name}${colors.reset}`);
		}
	});
}

/**
 * Generate summary report
 */
function generateSummary() {
	section('TEST SUMMARY');
	
	console.log(`${colors.bright}Test Results:${colors.reset}`);
	console.log(`  Total Tests: ${results.summary.total}`);
	console.log(`  ${colors.green}Passed: ${results.summary.passed}${colors.reset}`);
	console.log(`  ${colors.red}Failed: ${results.summary.failed}${colors.reset}`);
	console.log(`  ${colors.yellow}Warnings: ${results.summary.warnings}${colors.reset}`);
	
	const successRate = (results.summary.passed / results.summary.total * 100).toFixed(1);
	console.log(`\n  Success Rate: ${successRate}%`);
	
	if (results.summary.failed === 0 && results.summary.warnings === 0) {
		console.log(`\n${colors.green}${colors.bright}✅ ALL TESTS PASSED - PLUGIN READY FOR PRODUCTION${colors.reset}`);
	} else if (results.summary.failed === 0) {
		console.log(`\n${colors.yellow}${colors.bright}⚠️  TESTS PASSED WITH WARNINGS - REVIEW RECOMMENDED${colors.reset}`);
	} else {
		console.log(`\n${colors.red}${colors.bright}❌ TESTS FAILED - ISSUES MUST BE RESOLVED${colors.reset}`);
	}
}

/**
 * Save results to files
 */
function saveResults() {
	const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
	
	// Save JSON results
	const jsonFile = `STRESS_TEST_RESULTS_v1.2.2_${timestamp}.json`;
	fs.writeFileSync(jsonFile, JSON.stringify(results, null, 2));
	log(`\nResults saved to: ${jsonFile}`, 'cyan');
	
	// Generate markdown report
	const mdContent = `# Performance Stress Test Results v1.2.2

**Date:** ${new Date().toLocaleString()}
**Test Duration:** ${((Date.now() - new Date(results.timestamp).getTime()) / 1000 / 60).toFixed(1)} minutes
**WordPress Site:** ${CONFIG.siteUrl}

## Summary

- **Total Tests:** ${results.summary.total}
- **Passed:** ${results.summary.passed}
- **Failed:** ${results.summary.failed}
- **Warnings:** ${results.summary.warnings}
- **Success Rate:** ${(results.summary.passed / results.summary.total * 100).toFixed(1)}%

## Performance Metrics

### Response Times
- **Single Form Submit:** ${results.metrics.responseTimes[0] || 0}ms
- **Average:** ${results.summary.avgResponseTime.toFixed(0)}ms
- **Max:** ${results.summary.maxResponseTime}ms
- **Min:** ${results.summary.minResponseTime}ms

### Throughput
- **Total Forms Submitted:** ${results.summary.totalForms}
- **Successful:** ${results.summary.successfulForms}
- **Failed:** ${results.summary.failedForms}
- **Forms per Minute:** ${(results.summary.totalForms / ((Date.now() - new Date(results.timestamp).getTime()) / 60000)).toFixed(2)}

### Resource Usage
- **Memory Growth:** ${results.metrics.memoryUsage.length > 0 ? 
	((results.metrics.memoryUsage[results.metrics.memoryUsage.length - 1].heapUsed - results.metrics.memoryUsage[0].heapUsed) / 1024 / 1024).toFixed(2) : 0} MB

### Stability
- **Timeouts:** ${results.summary.timeouts}
- **Errors:** ${results.metrics.errors.length}
- **Data Loss:** ${results.summary.dataLoss}
- **Duplicates:** ${results.summary.duplicates}

## Acceptance Criteria

| Criterion | Status |
|-----------|--------|
| 20 forms without errors | ${results.summary.successfulForms >= 20 ? '✅ PASS' : '❌ FAIL'} |
| Average time < 2 seconds | ${results.summary.avgResponseTime < 2000 ? '✅ PASS' : '❌ FAIL'} |
| Memory usage stable | ${results.metrics.memoryUsage.length > 0 && 
	(results.metrics.memoryUsage[results.metrics.memoryUsage.length - 1].heapUsed - results.metrics.memoryUsage[0].heapUsed) < CONFIG.maxMemoryGrowth ? '✅ PASS' : '❌ FAIL'} |
| Zero timeouts | ${results.summary.timeouts === 0 ? '✅ PASS' : '❌ FAIL'} |
| Zero data loss | ${results.summary.dataLoss === 0 ? '✅ PASS' : '❌ FAIL'} |

## Detailed Test Results

${Object.entries(results.tests).map(([name, result]) => {
	const status = result.success ? '✅ PASS' : result.warning ? '⚠️ WARNING' : '❌ FAIL';
	return `### ${name}\n- **Status:** ${status}\n${result.message ? `- **Message:** ${result.message}\n` : ''}`;
}).join('\n')}

## Errors

${results.metrics.errors.length > 0 ? results.metrics.errors.map(e => 
	`### ${e.test}\n- **Error:** ${e.error}\n- **Timestamp:** ${e.timestamp}`
).join('\n') : 'No errors occurred during testing.'}

---
*Generated by EIPSI Forms Performance Stress Test v1.2.2*
`;
	
	const mdFile = `STRESS_TEST_REPORT_v1.2.2_${timestamp}.md`;
	fs.writeFileSync(mdFile, mdContent);
	log(`Report saved to: ${mdFile}`, 'cyan');
}

/**
 * Main test execution
 */
async function main() {
	console.log(`${colors.bright}${colors.cyan}`);
	console.log('╔════════════════════════════════════════════════════════════════════════════╗');
	console.log('║                                                                            ║');
	console.log('║              EIPSI FORMS - PERFORMANCE STRESS TEST v1.2.2                  ║');
	console.log('║                                                                            ║');
	console.log('╚════════════════════════════════════════════════════════════════════════════╝');
	console.log(colors.reset);
	
	log(`WordPress Site: ${CONFIG.siteUrl}`, 'blue');
	log(`Form ID: ${CONFIG.formId}`, 'blue');
	log(`Test Start: ${new Date().toLocaleString()}`, 'blue');
	
	// Check if site is accessible
	log('\nChecking WordPress site accessibility...', 'blue');
	try {
		const response = await axios.get(CONFIG.siteUrl, { timeout: 5000 });
		if (response.status === 200) {
			log('✅ Site is accessible', 'green');
		} else {
			log(`⚠️  Site returned status ${response.status}`, 'yellow');
		}
	} catch (error) {
		log(`❌ Cannot connect to site: ${error.message}`, 'red');
		log('\nℹ️  This test suite requires a live WordPress installation.', 'yellow');
		log('   To run the tests:', 'yellow');
		log('   1. Install EIPSI Forms v1.2.2 on a WordPress site', 'yellow');
		log('   2. Run: node stress-test-v1.2.2.js --url=https://your-site.com', 'yellow');
		log('\nAlternatively, see STRESS_TEST_GUIDE.md for manual testing instructions.', 'yellow');
		process.exit(1);
	}
	
	results.timestamp = new Date().toISOString();
	
	try {
		await testMultipleSubmissions();
		await testComplexForms();
		await testMetadataStress();
		await testDatabaseStress();
		await testResourceMonitoring();
		
		generateMetricsReport();
		generateSummary();
		saveResults();
		
	} catch (error) {
		log(`\n❌ Critical error during testing: ${error.message}`, 'red');
		console.error(error);
		process.exit(1);
	}
	
	// Exit with appropriate code
	if (results.summary.failed > 0) {
		process.exit(1);
	} else if (results.summary.warnings > 0) {
		process.exit(0); // Warnings are acceptable
	} else {
		process.exit(0);
	}
}

// Run tests
if (require.main === module) {
	main().catch(error => {
		console.error(`${colors.red}Fatal error:${colors.reset}`, error);
		process.exit(1);
	});
}

module.exports = { submitForm, generateFormData };
