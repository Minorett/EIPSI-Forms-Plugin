#!/usr/bin/env node
/* eslint-disable no-console */

/**
 * EIPSI Forms Distribution Directory Validator
 * Validates that the dist/eipsi-forms directory is ready for packaging
 */

const fs = require( 'fs' );
const path = require( 'path' );

const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	green: '\x1b[32m',
	red: '\x1b[31m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

class DistValidator {
	constructor( distPath ) {
		this.distPath = distPath;
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

	validate() {
		this.section( 'DIST DIRECTORY VALIDATION' );

		// Check main plugin file
		const mainFile = path.join( this.distPath, 'vas-dinamico-forms.php' );
		if ( fs.existsSync( mainFile ) ) {
			const content = fs.readFileSync( mainFile, 'utf8' );

			// Check for Plugin URI (CRITICAL FIX)
			if ( content.includes( 'Plugin URI:' ) ) {
				this.pass( '✅ Plugin URI header present (FIX APPLIED)' );
			} else {
				this.fail( '❌ Plugin URI header missing' );
			}

			// Check for Author URI (ADDED)
			if ( content.includes( 'Author URI:' ) ) {
				this.pass( '✅ Author URI header present (FIX APPLIED)' );
			} else {
				this.warn( '⚠️  Author URI header missing' );
			}

			const requiredHeaders = [
				'Plugin Name:',
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
		} else {
			this.fail( 'Main plugin file not found' );
		}

		// Check for index.php in languages (CRITICAL FIX)
		const langIndex = path.join( this.distPath, 'languages', 'index.php' );
		if ( fs.existsSync( langIndex ) ) {
			this.pass( '✅ Security index.php in languages/ (FIX APPLIED)' );
		} else {
			this.fail( '❌ Missing security index.php in languages/' );
		}

		// Check required directories
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

		requiredDirs.forEach( ( dir ) => {
			const dirPath = path.join( this.distPath, dir );
			if ( fs.existsSync( dirPath ) ) {
				this.pass( `Required directory exists: ${ dir }` );
			} else {
				this.fail( `Missing required directory: ${ dir }` );
			}
		} );

		// Check for forbidden files
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
			const filePath = path.join( this.distPath, file );
			if ( ! fs.existsSync( filePath ) ) {
				this.pass( `Sensitive file NOT included: ${ file }` );
			} else {
				this.fail( `❌ Sensitive file found: ${ file }` );
			}
		} );

		// Check critical assets
		const criticalFiles = [
			'assets/css/eipsi-forms.css',
			'assets/js/eipsi-forms.js',
			'assets/js/eipsi-tracking.js',
			'build/index.js',
			'build/style-index.css',
			'README.md',
			'LICENSE',
			'CHANGES.md',
		];

		criticalFiles.forEach( ( file ) => {
			const filePath = path.join( this.distPath, file );
			if ( fs.existsSync( filePath ) ) {
				const stats = fs.statSync( filePath );
				if ( stats.size > 0 ) {
					this.pass(
						`Critical file exists: ${ file } (${ stats.size } bytes)`
					);
				} else {
					this.fail( `Critical file is empty: ${ file }` );
				}
			} else {
				this.fail( `Missing critical file: ${ file }` );
			}
		} );

		// Check blocks
		const blocksDir = path.join( this.distPath, 'blocks' );
		if ( fs.existsSync( blocksDir ) ) {
			const blocks = fs.readdirSync( blocksDir ).filter( ( item ) => {
				const itemPath = path.join( blocksDir, item );
				return fs.statSync( itemPath ).isDirectory();
			} );

			this.log( `\nFound ${ blocks.length } blocks`, 'blue' );

			let validBlocks = 0;
			blocks.forEach( ( block ) => {
				const blockJson = path.join( blocksDir, block, 'block.json' );
				const indexPhp = path.join( blocksDir, block, 'index.php' );

				if ( fs.existsSync( blockJson ) && fs.existsSync( indexPhp ) ) {
					validBlocks++;
				}
			} );

			if ( validBlocks === blocks.length ) {
				this.pass(
					`All ${ blocks.length } blocks have required files (block.json, index.php)`
				);
			} else {
				this.fail(
					`Some blocks missing required files: ${ validBlocks }/${ blocks.length } valid`
				);
			}
		}

		return this.generateReport();
	}

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
				'✅ DISTRIBUTION DIRECTORY VALIDATED - Ready for packaging!',
				'bright'
			);
			return 0;
		}
		this.log(
			'❌ VALIDATION FAILED - Fix errors before packaging!',
			'bright'
		);
		return 1;
	}
}

const distPath = path.join( __dirname, 'dist', 'eipsi-forms' );
const validator = new DistValidator( distPath );
const exitCode = validator.validate();
process.exit( exitCode );
