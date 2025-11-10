#!/usr/bin/env node

/**
 * EIPSI Forms ZIP Installation Validator
 *
 * Validates that the plugin ZIP is ready for distribution and installation
 * in a clean WordPress environment.
 *
 * Tests:
 * 1. ZIP Structure - All required files and directories
 * 2. PHP Syntax - Main plugin file and critical PHP files
 * 3. JavaScript Integrity - Compiled blocks and frontend scripts
 * 4. CSS Integrity - Stylesheets and compiled block styles
 * 5. Block Registration - All blocks have required files
 * 6. Assets - Icons, images, and resources
 * 7. Documentation - README and installation instructions
 * 8. Security - No development files or sensitive data
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { execSync } = require( 'child_process' );

// ANSI color codes for terminal output
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	green: '\x1b[32m',
	red: '\x1b[31m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

class ZipValidator {
	constructor( zipPath, extractPath ) {
		this.zipPath = zipPath;
		this.extractPath = extractPath;
		this.pluginDir = path.join( extractPath, 'eipsi-forms' );
		this.errors = [];
		this.warnings = [];
		this.passed = [];
	}

	log( message, color = 'reset' ) {
		console.log( `${ colors[ color ] }${ message }${ colors.reset }` );
	}

	pass( message ) {
		this.passed.push( message );
		this.log( `✓ ${ message }`, 'green' );
	}

	fail( message ) {
		this.errors.push( message );
		this.log( `✗ ${ message }`, 'red' );
	}

	warn( message ) {
		this.warnings.push( message );
		this.log( `⚠ ${ message }`, 'yellow' );
	}

	section( title ) {
		this.log( `\n${ '='.repeat( 60 ) }`, 'cyan' );
		this.log( `${ title }`, 'bright' );
		this.log( `${ '='.repeat( 60 ) }`, 'cyan' );
	}

	// Test 1: Validate ZIP structure
	validateStructure() {
		this.section( 'TEST 1: ZIP Structure Validation' );

		const requiredFiles = [
			'vas-dinamico-forms.php',
			'README.md',
			'LICENSE',
			'CHANGES.md',
		];

		const requiredDirs = [
			'admin',
			'assets/css',
			'assets/js',
			'blocks',
			'build',
			'languages',
			'lib',
			'src',
		];

		// Check required files
		requiredFiles.forEach( ( file ) => {
			const filePath = path.join( this.pluginDir, file );
			if ( fs.existsSync( filePath ) ) {
				this.pass( `Required file exists: ${ file }` );
			} else {
				this.fail( `Missing required file: ${ file }` );
			}
		} );

		// Check required directories
		requiredDirs.forEach( ( dir ) => {
			const dirPath = path.join( this.pluginDir, dir );
			if (
				fs.existsSync( dirPath ) &&
				fs.statSync( dirPath ).isDirectory()
			) {
				this.pass( `Required directory exists: ${ dir }` );
			} else {
				this.fail( `Missing required directory: ${ dir }` );
			}
		} );

		// Check that sensitive files are NOT included
		const forbiddenFiles = [
			'node_modules',
			'.git',
			'.env',
			'.wp-env.json',
			'phpunit.xml',
			'composer.json',
			'.github',
		];

		forbiddenFiles.forEach( ( file ) => {
			const filePath = path.join( this.pluginDir, file );
			if ( ! fs.existsSync( filePath ) ) {
				this.pass( `Sensitive file NOT included: ${ file }` );
			} else {
				this.warn(
					`Sensitive file found (should be excluded): ${ file }`
				);
			}
		} );
	}

	// Test 2: Validate PHP syntax
	validatePHP() {
		this.section( 'TEST 2: PHP Syntax Validation' );

		const phpFiles = [
			'vas-dinamico-forms.php',
			'admin/ajax-handlers.php',
			'admin/results-page.php',
			'admin/export.php',
			'admin/menu.php',
			'lib/SimpleXLSXGen.php',
		];

		phpFiles.forEach( ( file ) => {
			const filePath = path.join( this.pluginDir, file );
			if ( fs.existsSync( filePath ) ) {
				try {
					execSync( `php -l "${ filePath }"`, {
						encoding: 'utf8',
						stdio: 'pipe',
					} );
					this.pass( `PHP syntax valid: ${ file }` );
				} catch ( error ) {
					this.fail(
						`PHP syntax error in ${ file }: ${ error.message }`
					);
				}
			} else {
				this.warn( `PHP file not found: ${ file }` );
			}
		} );

		// Check main plugin header
		const mainFile = path.join( this.pluginDir, 'vas-dinamico-forms.php' );
		if ( fs.existsSync( mainFile ) ) {
			const content = fs.readFileSync( mainFile, 'utf8' );
			const requiredHeaders = [
				'Plugin Name:',
				'Plugin URI:',
				'Description:',
				'Version:',
				'Author:',
				'License:',
				'Text Domain:',
			];

			requiredHeaders.forEach( ( header ) => {
				if ( content.includes( header ) ) {
					this.pass( `Plugin header present: ${ header }` );
				} else {
					this.fail( `Missing plugin header: ${ header }` );
				}
			} );
		}
	}

	// Test 3: Validate JavaScript
	validateJavaScript() {
		this.section( 'TEST 3: JavaScript Integrity Validation' );

		const jsFiles = [
			'build/index.js',
			'assets/js/eipsi-forms.js',
			'assets/js/eipsi-tracking.js',
			'assets/js/admin-script.js',
		];

		jsFiles.forEach( ( file ) => {
			const filePath = path.join( this.pluginDir, file );
			if ( fs.existsSync( filePath ) ) {
				const stats = fs.statSync( filePath );
				if ( stats.size > 0 ) {
					this.pass(
						`JavaScript file exists and has content: ${ file } (${ stats.size } bytes)`
					);

					// Basic syntax check
					try {
						const content = fs.readFileSync( filePath, 'utf8' );
						// Check for common issues
						if (
							content.includes( 'console.log' ) &&
							! file.includes( 'admin' )
						) {
							this.warn(
								`Console.log found in production file: ${ file }`
							);
						}
					} catch ( error ) {
						this.fail(
							`Error reading ${ file }: ${ error.message }`
						);
					}
				} else {
					this.fail( `JavaScript file is empty: ${ file }` );
				}
			} else {
				this.fail( `Missing JavaScript file: ${ file }` );
			}
		} );

		// Check for source maps (should not be in production)
		const buildDir = path.join( this.pluginDir, 'build' );
		if ( fs.existsSync( buildDir ) ) {
			const files = fs.readdirSync( buildDir );
			const sourceMaps = files.filter( ( f ) => f.endsWith( '.map' ) );
			if ( sourceMaps.length === 0 ) {
				this.pass(
					'No source maps in build directory (production ready)'
				);
			} else {
				this.warn(
					`Source maps found in build: ${ sourceMaps.join( ', ' ) }`
				);
			}
		}
	}

	// Test 4: Validate CSS
	validateCSS() {
		this.section( 'TEST 4: CSS Integrity Validation' );

		const cssFiles = [
			'assets/css/eipsi-forms.css',
			'assets/css/admin-style.css',
			'build/style-index.css',
			'build/index.css',
		];

		cssFiles.forEach( ( file ) => {
			const filePath = path.join( this.pluginDir, file );
			if ( fs.existsSync( filePath ) ) {
				const stats = fs.statSync( filePath );
				if ( stats.size > 0 ) {
					this.pass(
						`CSS file exists and has content: ${ file } (${ stats.size } bytes)`
					);

					// Check for CSS variables (design token system)
					const content = fs.readFileSync( filePath, 'utf8' );
					if ( file.includes( 'eipsi-forms.css' ) ) {
						if ( content.includes( '--eipsi-color-' ) ) {
							this.pass(
								'Design token system (CSS variables) present in main stylesheet'
							);
						} else {
							this.warn(
								'CSS variables not found in main stylesheet'
							);
						}
					}

					// Check for WCAG compliance markers
					if (
						content.includes( 'WCAG' ) ||
						content.includes( 'accessibility' )
					) {
						this.pass(
							`Accessibility considerations documented in ${ file }`
						);
					}
				} else {
					this.fail( `CSS file is empty: ${ file }` );
				}
			} else {
				this.fail( `Missing CSS file: ${ file }` );
			}
		} );
	}

	// Test 5: Validate Block Registration
	validateBlocks() {
		this.section( 'TEST 5: Block Registration Validation' );

		const blocksDir = path.join( this.pluginDir, 'blocks' );
		if ( ! fs.existsSync( blocksDir ) ) {
			this.fail( 'Blocks directory not found' );
			return;
		}

		const blocks = fs.readdirSync( blocksDir ).filter( ( item ) => {
			const itemPath = path.join( blocksDir, item );
			return fs.statSync( itemPath ).isDirectory();
		} );

		this.log( `\nFound ${ blocks.length } blocks:`, 'blue' );
		blocks.forEach( ( block ) => this.log( `  - ${ block }`, 'blue' ) );

		blocks.forEach( ( block ) => {
			const blockPath = path.join( blocksDir, block );

			// Check for required files
			const requiredFiles = [ 'block.json', 'index.php' ];
			requiredFiles.forEach( ( file ) => {
				const filePath = path.join( blockPath, file );
				if ( fs.existsSync( filePath ) ) {
					this.pass( `Block ${ block } has ${ file }` );

					// Validate block.json
					if ( file === 'block.json' ) {
						try {
							const blockJson = JSON.parse(
								fs.readFileSync( filePath, 'utf8' )
							);
							if ( blockJson.name && blockJson.title ) {
								this.pass(
									`Block ${ block } has valid block.json with name and title`
								);
							} else {
								this.fail(
									`Block ${ block } block.json missing name or title`
								);
							}
						} catch ( error ) {
							this.fail(
								`Block ${ block } has invalid block.json: ${ error.message }`
							);
						}
					}
				} else {
					this.fail( `Block ${ block } missing ${ file }` );
				}
			} );
		} );
	}

	// Test 6: Validate Assets
	validateAssets() {
		this.section( 'TEST 6: Assets Validation' );

		const assets = [
			'assets/eipsi-icon.svg',
			'assets/icon-256x256.svg',
			'assets/eipsi-icon-menu.svg',
		];

		assets.forEach( ( asset ) => {
			const assetPath = path.join( this.pluginDir, asset );
			if ( fs.existsSync( assetPath ) ) {
				const stats = fs.statSync( assetPath );
				this.pass( `Asset exists: ${ asset } (${ stats.size } bytes)` );
			} else {
				this.warn( `Asset not found: ${ asset }` );
			}
		} );

		// Check for index.php security files
		const dirsToCheck = [
			'admin',
			'assets',
			'assets/css',
			'assets/js',
			'blocks',
			'languages',
			'lib',
		];
		dirsToCheck.forEach( ( dir ) => {
			const indexPath = path.join( this.pluginDir, dir, 'index.php' );
			if ( fs.existsSync( indexPath ) ) {
				this.pass( `Security index.php present in ${ dir }` );
			} else {
				this.warn( `Missing security index.php in ${ dir }` );
			}
		} );
	}

	// Test 7: Validate Documentation
	validateDocumentation() {
		this.section( 'TEST 7: Documentation Validation' );

		const readme = path.join( this.pluginDir, 'README.md' );
		if ( fs.existsSync( readme ) ) {
			const content = fs.readFileSync( readme, 'utf8' );

			const requiredSections = [ 'Installation', 'Features', 'Usage' ];

			requiredSections.forEach( ( section ) => {
				if ( content.toLowerCase().includes( section.toLowerCase() ) ) {
					this.pass( `README contains ${ section } section` );
				} else {
					this.warn( `README missing ${ section } section` );
				}
			} );

			if ( content.length > 500 ) {
				this.pass( 'README has substantial content' );
			} else {
				this.warn( 'README is quite short' );
			}
		} else {
			this.fail( 'README.md not found' );
		}

		// Check CHANGES.md
		const changes = path.join( this.pluginDir, 'CHANGES.md' );
		if ( fs.existsSync( changes ) ) {
			this.pass( 'CHANGES.md (changelog) present' );
		} else {
			this.warn( 'CHANGES.md (changelog) not found' );
		}
	}

	// Test 8: Validate Translations
	validateTranslations() {
		this.section( 'TEST 8: Translation Files Validation' );

		const langDir = path.join( this.pluginDir, 'languages' );
		if ( fs.existsSync( langDir ) ) {
			const files = fs.readdirSync( langDir );

			const potFiles = files.filter( ( f ) => f.endsWith( '.pot' ) );
			const poFiles = files.filter( ( f ) => f.endsWith( '.po' ) );
			const moFiles = files.filter( ( f ) => f.endsWith( '.mo' ) );

			if ( potFiles.length > 0 ) {
				this.pass(
					`Translation template found: ${ potFiles.join( ', ' ) }`
				);
			} else {
				this.warn( 'No .pot translation template found' );
			}

			if ( poFiles.length > 0 ) {
				this.pass(
					`Translation files found: ${ poFiles.join( ', ' ) }`
				);
			}

			if ( moFiles.length > 0 ) {
				this.pass(
					`Compiled translations found: ${ moFiles.join( ', ' ) }`
				);
			}
		} else {
			this.warn( 'Languages directory not found' );
		}
	}

	// Generate final report
	generateReport() {
		this.section( 'VALIDATION SUMMARY' );

		this.log( `\n✓ Tests Passed: ${ this.passed.length }`, 'green' );
		this.log( `⚠ Warnings: ${ this.warnings.length }`, 'yellow' );
		this.log( `✗ Errors: ${ this.errors.length }`, 'red' );

		if ( this.errors.length > 0 ) {
			this.log( '\n❌ CRITICAL ERRORS:', 'red' );
			this.errors.forEach( ( error ) =>
				this.log( `  - ${ error }`, 'red' )
			);
		}

		if ( this.warnings.length > 0 ) {
			this.log( '\n⚠️  WARNINGS:', 'yellow' );
			this.warnings.forEach( ( warning ) =>
				this.log( `  - ${ warning }`, 'yellow' )
			);
		}

		this.log( '\n' + '='.repeat( 60 ), 'cyan' );

		if ( this.errors.length === 0 ) {
			this.log(
				'✅ VALIDATION PASSED - Plugin ZIP is ready for distribution!',
				'bright'
			);
			this.log( '\nNext Steps:', 'blue' );
			this.log( '1. Install in a clean WordPress environment', 'blue' );
			this.log( '2. Activate the plugin', 'blue' );
			this.log(
				'3. Create a test form with various field types',
				'blue'
			);
			this.log( '4. Test conditional logic and navigation', 'blue' );
			this.log( '5. Test frontend submission and data storage', 'blue' );
			this.log( '6. Verify style customization works', 'blue' );
			return 0;
		}
		this.log(
			'❌ VALIDATION FAILED - Fix errors before distribution!',
			'bright'
		);
		return 1;
	}

	// Run all tests
	async run() {
		this.log( '\n' + '='.repeat( 60 ), 'cyan' );
		this.log( 'EIPSI FORMS ZIP INSTALLATION VALIDATOR', 'bright' );
		this.log( `ZIP File: ${ this.zipPath }`, 'blue' );
		this.log( `Extract Path: ${ this.pluginDir }`, 'blue' );
		this.log( '='.repeat( 60 ) + '\n', 'cyan' );

		// Extract ZIP if needed
		if ( ! fs.existsSync( this.pluginDir ) ) {
			this.log( 'Extracting ZIP file...', 'yellow' );
			try {
				execSync(
					`unzip -q "${ this.zipPath }" -d "${ this.extractPath }"`
				);
				this.pass( 'ZIP extracted successfully' );
			} catch ( error ) {
				this.fail( `Failed to extract ZIP: ${ error.message }` );
				return 1;
			}
		}

		// Run all validation tests
		this.validateStructure();
		this.validatePHP();
		this.validateJavaScript();
		this.validateCSS();
		this.validateBlocks();
		this.validateAssets();
		this.validateDocumentation();
		this.validateTranslations();

		// Generate final report
		return this.generateReport();
	}
}

// Main execution
const zipPath = path.join( __dirname, 'eipsi-forms-1.1.0.zip' );
const extractPath = '/tmp/eipsi-test';

const validator = new ZipValidator( zipPath, extractPath );
validator.run().then( ( exitCode ) => {
	process.exit( exitCode );
} );
