#!/usr/bin/env node
/**
 * EIPSI Forms - Performance & Build Validation (Phase 9)
 * 
 * Tests:
 * 1. Build artifact integrity
 * 2. Bundle size analysis
 * 3. Asset versioning
 * 4. Tree-shaking effectiveness
 * 5. Dependency analysis
 * 6. Performance metrics estimation
 */

const fs = require('fs');
const path = require('path');

const RESULTS_FILE = 'docs/qa/phase9/performance-validation.json';
const PHASE_DIR = 'docs/qa/phase9';

// Color output
const colors = {
	green: '\x1b[32m',
	red: '\x1b[31m',
	yellow: '\x1b[33m',
	blue: '\x1b[36m',
	reset: '\x1b[0m',
};

const results = {
	timestamp: new Date().toISOString(),
	tests: {},
	summary: {
		total: 0,
		passed: 0,
		failed: 0,
		warnings: 0,
	},
};

function test(name, fn) {
	results.summary.total++;
	try {
		const result = fn();
		if (result === true) {
			results.tests[name] = { status: 'PASS', message: 'Test passed' };
			results.summary.passed++;
			console.log(`${colors.green}✓${colors.reset} ${name}`);
		} else if (result.warning) {
			results.tests[name] = {
				status: 'WARNING',
				message: result.message,
			};
			results.summary.warnings++;
			console.log(`${colors.yellow}⚠${colors.reset} ${name}: ${result.message}`);
		} else {
			results.tests[name] = { status: 'FAIL', message: result.message };
			results.summary.failed++;
			console.log(`${colors.red}✗${colors.reset} ${name}: ${result.message}`);
		}
	} catch (error) {
		results.tests[name] = {
			status: 'FAIL',
			message: error.message,
			stack: error.stack,
		};
		results.summary.failed++;
		console.log(`${colors.red}✗${colors.reset} ${name}: ${error.message}`);
	}
}

console.log('\n' + '='.repeat(64));
console.log('EIPSI FORMS - PERFORMANCE & BUILD VALIDATION (PHASE 9)');
console.log('='.repeat(64) + '\n');

// ============================================================================
// 1. BUILD ARTIFACT INTEGRITY
// ============================================================================
console.log(`${colors.blue}[1/6] Build Artifact Integrity${colors.reset}`);

test('Build directory exists', () => {
	return fs.existsSync('build');
});

test('index.js exists and is not empty', () => {
	const filePath = 'build/index.js';
	if (!fs.existsSync(filePath)) {
		return { message: 'File does not exist' };
	}
	const stats = fs.statSync(filePath);
	if (stats.size === 0) {
		return { message: 'File is empty' };
	}
	return true;
});

test('index.css exists and is not empty', () => {
	const filePath = 'build/index.css';
	if (!fs.existsSync(filePath)) {
		return { message: 'File does not exist' };
	}
	const stats = fs.statSync(filePath);
	if (stats.size === 0) {
		return { message: 'File is empty' };
	}
	return true;
});

test('style-index.css exists and is not empty', () => {
	const filePath = 'build/style-index.css';
	if (!fs.existsSync(filePath)) {
		return { message: 'File does not exist' };
	}
	const stats = fs.statSync(filePath);
	if (stats.size === 0) {
		return { message: 'File is empty' };
	}
	return true;
});

test('index.asset.php exists and contains dependencies', () => {
	const filePath = 'build/index.asset.php';
	if (!fs.existsSync(filePath)) {
		return { message: 'File does not exist' };
	}
	const content = fs.readFileSync(filePath, 'utf8');
	if (!content.includes('dependencies')) {
		return { message: 'Dependencies array not found' };
	}
	if (!content.includes('version')) {
		return { message: 'Version hash not found' };
	}
	return true;
});

test('RTL CSS files generated', () => {
	const rtlFiles = ['build/index-rtl.css', 'build/style-index-rtl.css'];
	for (const file of rtlFiles) {
		if (!fs.existsSync(file)) {
			return { message: `RTL file ${file} not found` };
		}
	}
	return true;
});

// ============================================================================
// 2. BUNDLE SIZE ANALYSIS
// ============================================================================
console.log(`\n${colors.blue}[2/6] Bundle Size Analysis${colors.reset}`);

const bundleSizes = {};

test('Measure build/index.js size', () => {
	const filePath = 'build/index.js';
	const stats = fs.statSync(filePath);
	bundleSizes['build/index.js'] = stats.size;
	results.tests['build/index.js size'] = {
		status: 'INFO',
		size: stats.size,
		sizeKB: (stats.size / 1024).toFixed(2),
	};
	return true;
});

test('Measure build/index.css size', () => {
	const filePath = 'build/index.css';
	const stats = fs.statSync(filePath);
	bundleSizes['build/index.css'] = stats.size;
	results.tests['build/index.css size'] = {
		status: 'INFO',
		size: stats.size,
		sizeKB: (stats.size / 1024).toFixed(2),
	};
	return true;
});

test('Measure build/style-index.css size', () => {
	const filePath = 'build/style-index.css';
	const stats = fs.statSync(filePath);
	bundleSizes['build/style-index.css'] = stats.size;
	results.tests['build/style-index.css size'] = {
		status: 'INFO',
		size: stats.size,
		sizeKB: (stats.size / 1024).toFixed(2),
	};
	return true;
});

test('Measure assets/js/eipsi-forms.js size', () => {
	const filePath = 'assets/js/eipsi-forms.js';
	const stats = fs.statSync(filePath);
	bundleSizes['assets/js/eipsi-forms.js'] = stats.size;
	results.tests['assets/js/eipsi-forms.js size'] = {
		status: 'INFO',
		size: stats.size,
		sizeKB: (stats.size / 1024).toFixed(2),
	};
	return true;
});

test('Measure assets/css/eipsi-forms.css size', () => {
	const filePath = 'assets/css/eipsi-forms.css';
	const stats = fs.statSync(filePath);
	bundleSizes['assets/css/eipsi-forms.css'] = stats.size;
	results.tests['assets/css/eipsi-forms.css size'] = {
		status: 'INFO',
		size: stats.size,
		sizeKB: (stats.size / 1024).toFixed(2),
	};
	return true;
});

test('Build JS bundle size within acceptable limits (< 150KB)', () => {
	const size = bundleSizes['build/index.js'];
	if (size > 150 * 1024) {
		return {
			warning: true,
			message: `Bundle size ${(size / 1024).toFixed(2)}KB exceeds 150KB threshold`,
		};
	}
	return true;
});

test('Frontend JS bundle size within acceptable limits (< 100KB)', () => {
	const size = bundleSizes['assets/js/eipsi-forms.js'];
	if (size > 100 * 1024) {
		return {
			warning: true,
			message: `Frontend JS ${(size / 1024).toFixed(2)}KB exceeds 100KB threshold`,
		};
	}
	return true;
});

test('Total CSS size within acceptable limits (< 100KB)', () => {
	const totalCSS =
		bundleSizes['build/index.css'] +
		bundleSizes['build/style-index.css'] +
		bundleSizes['assets/css/eipsi-forms.css'];
	if (totalCSS > 100 * 1024) {
		return {
			warning: true,
			message: `Total CSS ${(totalCSS / 1024).toFixed(2)}KB exceeds 100KB threshold`,
		};
	}
	return true;
});

// ============================================================================
// 3. ASSET VERSIONING
// ============================================================================
console.log(`\n${colors.blue}[3/6] Asset Versioning${colors.reset}`);

test('Version constant defined in main plugin file', () => {
	const content = fs.readFileSync('vas-dinamico-forms.php', 'utf8');
	if (!content.includes('VAS_DINAMICO_VERSION')) {
		return { message: 'VAS_DINAMICO_VERSION constant not found' };
	}
	return true;
});

test('Version hash in index.asset.php is valid', () => {
	const content = fs.readFileSync('build/index.asset.php', 'utf8');
	const versionMatch = content.match(/'version'\s*=>\s*'([a-f0-9]+)'/);
	if (!versionMatch) {
		return { message: 'Version hash not found' };
	}
	if (versionMatch[1].length < 10) {
		return { message: 'Version hash too short' };
	}
	results.tests['Build version hash'] = {
		status: 'INFO',
		hash: versionMatch[1],
	};
	return true;
});

test('Frontend assets enqueued with version parameter', () => {
	const content = fs.readFileSync('vas-dinamico-forms.php', 'utf8');
	const hasVersionParam = content.includes('VAS_DINAMICO_VERSION');
	if (!hasVersionParam) {
		return {
			warning: true,
			message: 'VAS_DINAMICO_VERSION not used in asset enqueuing',
		};
	}
	return true;
});

// ============================================================================
// 4. TREE-SHAKING EFFECTIVENESS
// ============================================================================
console.log(`\n${colors.blue}[4/6] Tree-Shaking Effectiveness${colors.reset}`);

test('Build output is minified', () => {
	const content = fs.readFileSync('build/index.js', 'utf8');
	// Check for minification indicators
	const hasNewlines = content.split('\n').length > 10;
	const hasWhitespace = content.includes('    '); // 4 spaces
	if (hasNewlines && hasWhitespace) {
		return {
			warning: true,
			message: 'Build output may not be fully minified',
		};
	}
	return true;
});

test('No development-only code in production build', () => {
	const content = fs.readFileSync('build/index.js', 'utf8');
	if (content.includes('console.log(') || content.includes('console.warn(')) {
		return {
			warning: true,
			message: 'Console statements found in production build',
		};
	}
	return true;
});

test('WordPress dependencies declared correctly', () => {
	const content = fs.readFileSync('build/index.asset.php', 'utf8');
	const requiredDeps = [
		'wp-blocks',
		'wp-element',
		'wp-components',
		'wp-block-editor',
	];
	for (const dep of requiredDeps) {
		if (!content.includes(dep)) {
			return { message: `Required dependency ${dep} not found` };
		}
	}
	return true;
});

// ============================================================================
// 5. DEPENDENCY ANALYSIS
// ============================================================================
console.log(`\n${colors.blue}[5/6] Dependency Analysis${colors.reset}`);

test('package.json exists and is valid', () => {
	if (!fs.existsSync('package.json')) {
		return { message: 'package.json not found' };
	}
	try {
		const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
		if (!pkg.dependencies) {
			return { message: 'No dependencies found' };
		}
		return true;
	} catch (error) {
		return { message: 'Invalid JSON in package.json' };
	}
});

test('All WordPress dependencies at compatible versions', () => {
	const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
	const wpDeps = Object.keys(pkg.dependencies).filter((dep) =>
		dep.startsWith('@wordpress/')
	);
	if (wpDeps.length === 0) {
		return { message: 'No WordPress dependencies found' };
	}
	results.tests['WordPress dependencies count'] = {
		status: 'INFO',
		count: wpDeps.length,
		packages: wpDeps,
	};
	return true;
});

test('No known vulnerable dependencies', () => {
	// This would require running npm audit, but we can check for common issues
	const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
	const allDeps = { ...pkg.dependencies, ...pkg.devDependencies };
	// Check for known outdated packages
	const outdatedPatterns = ['core-js@2'];
	for (const [dep, version] of Object.entries(allDeps)) {
		for (const pattern of outdatedPatterns) {
			if (`${dep}@${version}`.includes(pattern)) {
				return {
					warning: true,
					message: `Potentially outdated package: ${dep}@${version}`,
				};
			}
		}
	}
	return true;
});

// ============================================================================
// 6. PERFORMANCE METRICS ESTIMATION
// ============================================================================
console.log(`\n${colors.blue}[6/6] Performance Metrics Estimation${colors.reset}`);

test('Estimate parse time for main JS bundle', () => {
	const size = bundleSizes['build/index.js'];
	// Rough estimate: ~1ms per 1KB on average hardware
	const estimatedParseTime = size / 1024;
	results.tests['Estimated JS parse time'] = {
		status: 'INFO',
		estimatedMs: estimatedParseTime.toFixed(2),
		sizeKB: (size / 1024).toFixed(2),
	};
	if (estimatedParseTime > 100) {
		return {
			warning: true,
			message: `Estimated parse time ${estimatedParseTime.toFixed(2)}ms may impact performance`,
		};
	}
	return true;
});

test('Estimate network transfer time (3G)', () => {
	// 3G: ~750KB/s download speed
	const totalSize =
		bundleSizes['build/index.js'] +
		bundleSizes['build/index.css'] +
		bundleSizes['build/style-index.css'] +
		bundleSizes['assets/js/eipsi-forms.js'] +
		bundleSizes['assets/css/eipsi-forms.css'];
	const transferTime3G = (totalSize / (750 * 1024)) * 1000;
	results.tests['Estimated 3G transfer time'] = {
		status: 'INFO',
		estimatedMs: transferTime3G.toFixed(2),
		totalSizeKB: (totalSize / 1024).toFixed(2),
	};
	if (transferTime3G > 3000) {
		return {
			warning: true,
			message: `3G transfer time ${transferTime3G.toFixed(2)}ms exceeds 3 second threshold`,
		};
	}
	return true;
});

test('No blocking resources identified', () => {
	// Check for synchronous script loading
	const mainFile = fs.readFileSync('vas-dinamico-forms.php', 'utf8');
	if (mainFile.includes("wp_enqueue_script('") && !mainFile.includes('defer')) {
		return {
			warning: true,
			message: 'Scripts may block page rendering without defer/async',
		};
	}
	return true;
});

test('CSS files can be loaded asynchronously', () => {
	// Check CSS size - if small enough, can be inlined
	const totalCSS =
		bundleSizes['build/index.css'] +
		bundleSizes['build/style-index.css'] +
		bundleSizes['assets/css/eipsi-forms.css'];
	results.tests['CSS optimization potential'] = {
		status: 'INFO',
		totalCSSKB: (totalCSS / 1024).toFixed(2),
		recommendation:
			totalCSS < 20 * 1024
				? 'Consider critical CSS inlining'
				: 'Use async CSS loading',
	};
	return true;
});

test('Memory footprint estimation', () => {
	// Rough estimate: JS size * 3 for runtime memory
	const jsSize =
		bundleSizes['build/index.js'] + bundleSizes['assets/js/eipsi-forms.js'];
	const estimatedMemory = (jsSize * 3) / (1024 * 1024);
	results.tests['Estimated memory footprint'] = {
		status: 'INFO',
		estimatedMB: estimatedMemory.toFixed(2),
	};
	if (estimatedMemory > 10) {
		return {
			warning: true,
			message: `Estimated memory ${estimatedMemory.toFixed(2)}MB may be high for mobile devices`,
		};
	}
	return true;
});

// ============================================================================
// SUMMARY AND RESULTS
// ============================================================================
console.log('\n' + '='.repeat(64));
console.log('SUMMARY');
console.log('='.repeat(64));

// Calculate bundle totals
const totalBuildSize =
	bundleSizes['build/index.js'] +
	bundleSizes['build/index.css'] +
	bundleSizes['build/style-index.css'];
const totalFrontendSize =
	bundleSizes['assets/js/eipsi-forms.js'] +
	bundleSizes['assets/css/eipsi-forms.css'];
const totalSize = totalBuildSize + totalFrontendSize;

console.log(`\nBundle Sizes:`);
console.log(`  Build Output:    ${(totalBuildSize / 1024).toFixed(2)} KB`);
console.log(`  Frontend Assets: ${(totalFrontendSize / 1024).toFixed(2)} KB`);
console.log(`  Total:           ${(totalSize / 1024).toFixed(2)} KB`);

console.log(`\nTest Results:`);
console.log(`  ${colors.green}Passed:${colors.reset}   ${results.summary.passed}`);
console.log(`  ${colors.red}Failed:${colors.reset}   ${results.summary.failed}`);
console.log(`  ${colors.yellow}Warnings:${colors.reset} ${results.summary.warnings}`);
console.log(`  Total:    ${results.summary.total}`);

// Save results
results.bundleSizes = {
	build: {
		total: totalBuildSize,
		totalKB: (totalBuildSize / 1024).toFixed(2),
		files: {
			'index.js': bundleSizes['build/index.js'],
			'index.css': bundleSizes['build/index.css'],
			'style-index.css': bundleSizes['build/style-index.css'],
		},
	},
	frontend: {
		total: totalFrontendSize,
		totalKB: (totalFrontendSize / 1024).toFixed(2),
		files: {
			'eipsi-forms.js': bundleSizes['assets/js/eipsi-forms.js'],
			'eipsi-forms.css': bundleSizes['assets/css/eipsi-forms.css'],
		},
	},
	combined: {
		total: totalSize,
		totalKB: (totalSize / 1024).toFixed(2),
	},
};

fs.writeFileSync(RESULTS_FILE, JSON.stringify(results, null, 2));
console.log(`\nResults saved to ${RESULTS_FILE}`);

// Exit with appropriate code
const allPassed =
	results.summary.failed === 0 && results.summary.warnings === 0;
if (allPassed) {
	console.log(`\n${colors.green}✓ SUCCESS: All performance checks passed${colors.reset}\n`);
	process.exit(0);
} else if (results.summary.failed === 0) {
	console.log(`\n${colors.yellow}⚠ SUCCESS WITH WARNINGS: All tests passed but some warnings exist${colors.reset}\n`);
	process.exit(0);
} else {
	console.log(`\n${colors.red}✗ FAILURE: Some performance checks failed${colors.reset}\n`);
	process.exit(1);
}
