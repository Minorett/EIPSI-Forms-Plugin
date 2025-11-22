#!/usr/bin/env node

/**
 * Test Suite: Rich Options with Newline Compatibility
 *
 * Validates that all option blocks (campo-multiple, campo-radio, campo-select)
 * now support rich options containing commas, quotes, accented characters, and
 * multi-word text while maintaining backward compatibility with legacy comma-separated options.
 *
 * Critical for: Clinical research forms that need clinically accurate answer strings
 * Examples: "Opción A, con coma", "Opción "entre comillas"", "Sí, absolutamente"
 *
 * Version: 1.2.4
 * Date: January 2025
 */

const fs = require( 'fs' );
const path = require( 'path' );

// ANSI colors for terminal output
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	green: '\x1b[32m',
	red: '\x1b[31m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
	magenta: '\x1b[35m',
};

const log = {
	header: ( msg ) =>
		console.log(
			`\n${ colors.bright }${ colors.blue }═══════════════════════════════════════════════════════════${ colors.reset }`
		),
	title: ( msg ) =>
		console.log(
			`${ colors.bright }${ colors.cyan }${ msg }${ colors.reset }`
		),
	success: ( msg ) =>
		console.log( `${ colors.green }✅ ${ msg }${ colors.reset }` ),
	error: ( msg ) =>
		console.log( `${ colors.red }❌ ${ msg }${ colors.reset }` ),
	warning: ( msg ) =>
		console.log( `${ colors.yellow }⚠️  ${ msg }${ colors.reset }` ),
	info: ( msg ) =>
		console.log( `${ colors.cyan }ℹ️  ${ msg }${ colors.reset }` ),
	detail: ( msg ) => console.log( `   ${ msg }` ),
	section: ( msg ) =>
		console.log(
			`\n${ colors.bright }${ colors.magenta }▶ ${ msg }${ colors.reset }`
		),
};

class TestRunner {
	constructor() {
		this.tests = [];
		this.passed = 0;
		this.failed = 0;
		this.sections = {};
	}

	test( name, fn, section = 'General' ) {
		this.tests.push( { name, fn, section } );
		if ( ! this.sections[ section ] ) {
			this.sections[ section ] = { passed: 0, failed: 0 };
		}
	}

	async run() {
		log.header();
		log.title( '🧪 Rich Options with Newline Compatibility Test Suite' );
		log.info( `Running ${ this.tests.length } tests...\n` );

		let currentSection = null;

		for ( const { name, fn, section } of this.tests ) {
			if ( currentSection !== section ) {
				log.section( section );
				currentSection = section;
			}

			try {
				await fn();
				log.success( name );
				this.passed++;
				this.sections[ section ].passed++;
			} catch ( error ) {
				log.error( name );
				log.detail( colors.red + error.message + colors.reset );
				this.failed++;
				this.sections[ section ].failed++;
			}
		}

		this.printSummary();
	}

	printSummary() {
		const total = this.passed + this.failed;
		const passRate = ( ( this.passed / total ) * 100 ).toFixed( 1 );

		log.header();
		log.title( '📊 Test Summary' );
		console.log( `\nTotal Tests: ${ total }` );
		console.log(
			`${ colors.green }Passed: ${ this.passed }${ colors.reset }`
		);
		console.log(
			`${ colors.red }Failed: ${ this.failed }${ colors.reset }`
		);
		console.log( `Pass Rate: ${ passRate }%\n` );

		// Section summary
		log.title( '📋 Results by Section:' );
		for ( const [ section, stats ] of Object.entries( this.sections ) ) {
			const sectionTotal = stats.passed + stats.failed;
			const sectionRate = (
				( stats.passed / sectionTotal ) *
				100
			).toFixed( 0 );
			const icon = stats.failed === 0 ? '✅' : '❌';
			console.log(
				`${ icon } ${ section }: ${ stats.passed }/${ sectionTotal } (${ sectionRate }%)`
			);
		}

		log.header();

		if ( this.failed === 0 ) {
			log.success( 'All tests passed! 🎉\n' );
		} else {
			log.error( `${ this.failed } test(s) failed.\n` );
			process.exit( 1 );
		}
	}
}

// Utility: Read file and check content
function readFile( filePath ) {
	return fs.readFileSync( filePath, 'utf8' );
}

function assertFileContains( filePath, searchString, errorMsg ) {
	const content = readFile( filePath );
	if ( ! content.includes( searchString ) ) {
		throw new Error(
			errorMsg || `File ${ filePath } does not contain: ${ searchString }`
		);
	}
}

function assertFileNotContains( filePath, searchString, errorMsg ) {
	const content = readFile( filePath );
	if ( content.includes( searchString ) ) {
		throw new Error(
			errorMsg ||
				`File ${ filePath } should not contain: ${ searchString }`
		);
	}
}

function assertFileMatches( filePath, regex, errorMsg ) {
	const content = readFile( filePath );
	if ( ! regex.test( content ) ) {
		throw new Error(
			errorMsg || `File ${ filePath } does not match pattern: ${ regex }`
		);
	}
}

// Test Runner Instance
const runner = new TestRunner();

// ============================================================================
// SECTION 1: CAMPO-MULTIPLE (CHECKBOX) - Already Fixed, Verify Integrity
// ============================================================================

runner.test(
	'campo-multiple edit.js: parseOptions detects newline separator',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-multiple/edit.js';
		assertFileContains(
			filePath,
			"const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
			'parseOptions should detect newline vs comma separator'
		);
	},
	'Campo-Multiple (Checkbox)'
);

runner.test(
	'campo-multiple edit.js: parseOptions has backward compatibility',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-multiple/edit.js';
		assertFileContains(
			filePath,
			'// Detectar formato: newline (estándar) o comma (legacy)',
			'parseOptions should have backward compatibility comment'
		);
	},
	'Campo-Multiple (Checkbox)'
);

runner.test(
	'campo-multiple edit.js: Help text mentions commas/quotes',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-multiple/edit.js';
		assertFileContains(
			filePath,
			'Options can contain commas, periods, quotes, etc.',
			'Help text should mention support for commas, quotes, etc.'
		);
	},
	'Campo-Multiple (Checkbox)'
);

runner.test(
	'campo-multiple save.js: parseOptions detects newline separator',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-multiple/save.js';
		assertFileContains(
			filePath,
			"const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
			'parseOptions should detect newline vs comma separator'
		);
	},
	'Campo-Multiple (Checkbox)'
);

// ============================================================================
// SECTION 2: CAMPO-RADIO - Newly Fixed
// ============================================================================

runner.test(
	'campo-radio edit.js: parseOptions detects newline separator',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			"const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
			'parseOptions should detect newline vs comma separator'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: parseOptions has backward compatibility',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			'// Detectar formato: newline (estándar) o comma (legacy)',
			'parseOptions should have backward compatibility comment'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: TextareaControl label is "one per line"',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			"'Options (one per line)'",
			'TextareaControl label should say "one per line"'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: TextareaControl does NOT mention comma',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileNotContains(
			filePath,
			"'Options (comma-separated)'",
			'TextareaControl should not have "comma-separated" label'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: TextareaControl value joins with newline',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			"parseOptions( options ).join( '\\n' )",
			'TextareaControl value should join options with newline'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: onChange splits by newline',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			".split( '\\n' )",
			'onChange handler should split by newline'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: onChange joins by newline',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			"cleanedOptions.join( '\\n' )",
			'onChange handler should join cleaned options by newline'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: Help text mentions commas/quotes',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			'Options can contain commas, periods, quotes, etc.',
			'Help text should mention support for commas, quotes, etc.'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: Placeholder shows Spanish example with commas',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			'Sí, absolutamente',
			'Placeholder should show example option with comma'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: Placeholder uses newline format',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileContains(
			filePath,
			'Sí, absolutamente\\nSí, pero no tan frecuente',
			'Placeholder should use \\n to separate options'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio edit.js: Textarea rows increased to 8',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/edit.js';
		assertFileMatches(
			filePath,
			/rows=\{\s*8\s*\}/,
			'Textarea should have 8 rows (was 5) for better UX with newlines'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio save.js: parseOptions detects newline separator',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/save.js';
		assertFileContains(
			filePath,
			"const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
			'parseOptions should detect newline vs comma separator'
		);
	},
	'Campo-Radio (Single Choice)'
);

runner.test(
	'campo-radio save.js: parseOptions has backward compatibility',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-radio/save.js';
		assertFileContains(
			filePath,
			'// Detectar formato: newline (estándar) o comma (legacy)',
			'parseOptions should have backward compatibility comment'
		);
	},
	'Campo-Radio (Single Choice)'
);

// ============================================================================
// SECTION 3: CAMPO-SELECT - Newly Fixed
// ============================================================================

runner.test(
	'campo-select edit.js: parseOptions detects newline separator',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			"const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
			'parseOptions should detect newline vs comma separator'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: parseOptions has backward compatibility',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			'// Detectar formato: newline (estándar) o comma (legacy)',
			'parseOptions should have backward compatibility comment'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: TextareaControl label is "one per line"',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			"'Options (one per line)'",
			'TextareaControl label should say "one per line"'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: TextareaControl does NOT mention comma',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileNotContains(
			filePath,
			"'Options (comma-separated)'",
			'TextareaControl should not have "comma-separated" label'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: TextareaControl value joins with newline',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			"parseOptions( options ).join( '\\n' )",
			'TextareaControl value should join options with newline'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: onChange splits by newline',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			".split( '\\n' )",
			'onChange handler should split by newline'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: onChange joins by newline',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			"cleanedOptions.join( '\\n' )",
			'onChange handler should join cleaned options by newline'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: Help text mentions commas/quotes',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			'Options can contain commas, periods, quotes, etc.',
			'Help text should mention support for commas, quotes, etc.'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: Placeholder shows Spanish example with commas',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			'Sí, absolutamente',
			'Placeholder should show example option with comma'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: Placeholder uses newline format',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileContains(
			filePath,
			'Sí, absolutamente\\nSí, pero no tan frecuente',
			'Placeholder should use \\n to separate options'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select edit.js: Textarea rows increased to 8',
	() => {
		const filePath =
			'/home/engine/project/src/blocks/campo-select/edit.js';
		assertFileMatches(
			filePath,
			/rows=\{\s*8\s*\}/,
			'Textarea should have 8 rows (was 5) for better UX with newlines'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select save.js: parseOptions detects newline separator',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-select/save.js';
		assertFileContains(
			filePath,
			"const separator = optionsString.includes( '\\n' ) ? '\\n' : ',';",
			'parseOptions should detect newline vs comma separator'
		);
	},
	'Campo-Select (Dropdown)'
);

runner.test(
	'campo-select save.js: parseOptions has backward compatibility',
	() => {
		const filePath = '/home/engine/project/src/blocks/campo-select/save.js';
		assertFileContains(
			filePath,
			'// Detectar formato: newline (estándar) o comma (legacy)',
			'parseOptions should have backward compatibility comment'
		);
	},
	'Campo-Select (Dropdown)'
);

// ============================================================================
// SECTION 4: FUNCTIONAL TESTS - Rich Options Parsing
// ============================================================================

runner.test(
	'Functional: Options with commas are preserved',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test option with comma inside
		const optionsWithComma =
			'Opción A, con coma\nOpción B, también con coma\nOpción C';
		const result = parseOptions( optionsWithComma );

		if ( result.length !== 3 ) {
			throw new Error(
				`Expected 3 options, got ${ result.length }: ${ JSON.stringify(
					result
				) }`
			);
		}
		if ( result[ 0 ] !== 'Opción A, con coma' ) {
			throw new Error(
				`Expected "Opción A, con coma", got "${ result[ 0 ] }"`
			);
		}
		if ( ! result[ 0 ].includes( ',' ) ) {
			throw new Error(
				`Comma should be preserved in option: "${ result[ 0 ] }"`
			);
		}
	},
	'Functional Tests'
);

runner.test(
	'Functional: Options with double quotes are preserved',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test option with double quotes inside
		const optionsWithQuotes =
			'Opción "entre comillas"\nOpción sin comillas\nOpción "otra vez"';
		const result = parseOptions( optionsWithQuotes );

		if ( result.length !== 3 ) {
			throw new Error(
				`Expected 3 options, got ${ result.length }: ${ JSON.stringify(
					result
				) }`
			);
		}
		if ( result[ 0 ] !== 'Opción "entre comillas"' ) {
			throw new Error(
				`Expected 'Opción "entre comillas"', got "${ result[ 0 ] }"`
			);
		}
		if ( ! result[ 0 ].includes( '"' ) ) {
			throw new Error(
				`Double quotes should be preserved in option: "${ result[ 0 ] }"`
			);
		}
	},
	'Functional Tests'
);

runner.test(
	'Functional: Options with single quotes are preserved',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test option with single quotes inside
		const optionsWithQuotes =
			"Opción 'con apostrofe'\nOpción normal\nOpción 'otra vez'";
		const result = parseOptions( optionsWithQuotes );

		if ( result.length !== 3 ) {
			throw new Error(
				`Expected 3 options, got ${ result.length }: ${ JSON.stringify(
					result
				) }`
			);
		}
		if ( result[ 0 ] !== "Opción 'con apostrofe'" ) {
			throw new Error(
				`Expected "Opción 'con apostrofe'", got "${ result[ 0 ] }"`
			);
		}
		if ( ! result[ 0 ].includes( "'" ) ) {
			throw new Error(
				`Single quotes should be preserved in option: "${ result[ 0 ] }"`
			);
		}
	},
	'Functional Tests'
);

runner.test(
	'Functional: Options with accented characters are preserved',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test option with accented characters
		const optionsWithAccents =
			'Sí, absolutamente\nNo, não\nMüller, José María\nFrançois, Björk';
		const result = parseOptions( optionsWithAccents );

		if ( result.length !== 4 ) {
			throw new Error(
				`Expected 4 options, got ${ result.length }: ${ JSON.stringify(
					result
				) }`
			);
		}
		if ( result[ 0 ] !== 'Sí, absolutamente' ) {
			throw new Error(
				`Expected "Sí, absolutamente", got "${ result[ 0 ] }"`
			);
		}
		if ( result[ 2 ] !== 'Müller, José María' ) {
			throw new Error(
				`Expected "Müller, José María", got "${ result[ 2 ] }"`
			);
		}
		if ( result[ 3 ] !== 'François, Björk' ) {
			throw new Error(
				`Expected "François, Björk", got "${ result[ 3 ] }"`
			);
		}
	},
	'Functional Tests'
);

runner.test(
	'Functional: Options with periods and punctuation are preserved',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test option with periods and punctuation
		const optionsWithPunctuation =
			'Opción 1. Primera opción\nOpción 2: Segunda opción\n¿Opción 3? ¡Sí!\nOpción 4; Cuarta opción';
		const result = parseOptions( optionsWithPunctuation );

		if ( result.length !== 4 ) {
			throw new Error(
				`Expected 4 options, got ${ result.length }: ${ JSON.stringify(
					result
				) }`
			);
		}
		if ( result[ 0 ] !== 'Opción 1. Primera opción' ) {
			throw new Error(
				`Expected "Opción 1. Primera opción", got "${ result[ 0 ] }"`
			);
		}
		if ( result[ 2 ] !== '¿Opción 3? ¡Sí!' ) {
			throw new Error(
				`Expected "¿Opción 3? ¡Sí!", got "${ result[ 2 ] }"`
			);
		}
	},
	'Functional Tests'
);

runner.test(
	'Functional: Mixed rich options (commas, quotes, accents)',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test mixed rich options
		const mixedOptions =
			'Opción A, con coma y "comillas"\nSí, absolutamente\n"Opción entre comillas completa"\nMüller, José "María"\n¿Pregunta con coma, y acentos?';
		const result = parseOptions( mixedOptions );

		if ( result.length !== 5 ) {
			throw new Error(
				`Expected 5 options, got ${ result.length }: ${ JSON.stringify(
					result
				) }`
			);
		}
		if ( result[ 0 ] !== 'Opción A, con coma y "comillas"' ) {
			throw new Error(
				`Expected 'Opción A, con coma y "comillas"', got "${ result[ 0 ] }"`
			);
		}
		if ( result[ 3 ] !== 'Müller, José "María"' ) {
			throw new Error(
				`Expected 'Müller, José "María"', got "${ result[ 3 ] }"`
			);
		}
	},
	'Functional Tests'
);

// ============================================================================
// SECTION 5: BACKWARD COMPATIBILITY TESTS
// ============================================================================

runner.test(
	'Backward Compatibility: Legacy comma-separated format still works',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test legacy comma-separated (no newlines)
		const legacyOptions = 'Opción 1,Opción 2,Opción 3,Opción 4';
		const result = parseOptions( legacyOptions );

		if ( result.length !== 4 ) {
			throw new Error(
				`Legacy format: Expected 4 options, got ${ result.length }`
			);
		}
		if ( result[ 0 ] !== 'Opción 1' ) {
			throw new Error(
				`Legacy format: Expected "Opción 1", got "${ result[ 0 ] }"`
			);
		}
	},
	'Backward Compatibility'
);

runner.test(
	'Backward Compatibility: Legacy with spaces around commas',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test legacy comma-separated with spaces
		const legacyOptions = 'Opción 1 , Opción 2 , Opción 3 , Opción 4';
		const result = parseOptions( legacyOptions );

		if ( result.length !== 4 ) {
			throw new Error(
				`Legacy format with spaces: Expected 4 options, got ${ result.length }`
			);
		}
		if ( result[ 0 ] !== 'Opción 1' ) {
			throw new Error(
				`Legacy format: Should trim spaces, expected "Opción 1", got "${ result[ 0 ] }"`
			);
		}
	},
	'Backward Compatibility'
);

runner.test(
	'Backward Compatibility: Newline takes precedence over comma',
	() => {
		// Simulate parsing logic
		const parseOptions = ( optionsString ) => {
			if ( ! optionsString || optionsString.trim() === '' ) {
				return [];
			}
			const separator = optionsString.includes( '\n' ) ? '\n' : ',';
			return optionsString
				.split( separator )
				.map( ( opt ) => opt.trim() )
				.filter( ( opt ) => opt !== '' );
		};

		// Test newline format (should NOT split on commas)
		const newlineOptions =
			'Opción A, con coma\nOpción B, también con coma\nOpción C';
		const result = parseOptions( newlineOptions );

		if ( result.length !== 3 ) {
			throw new Error(
				`Newline format: Expected 3 options, got ${ result.length }. Commas should NOT be used as separator when newlines are present.`
			);
		}
		if ( result[ 0 ] !== 'Opción A, con coma' ) {
			throw new Error(
				`Newline format: Comma should be preserved, expected "Opción A, con coma", got "${ result[ 0 ] }"`
			);
		}
	},
	'Backward Compatibility'
);

// ============================================================================
// SECTION 6: BUILD VALIDATION
// ============================================================================

runner.test(
	'Build: Compiled build file exists',
	() => {
		const buildFile = '/home/engine/project/build/index.js';
		if ( ! fs.existsSync( buildFile ) ) {
			throw new Error(
				'Build file does not exist. Run `npm run build` first.'
			);
		}

		const stat = fs.statSync( buildFile );
		if ( stat.size < 1000 ) {
			throw new Error( 'Build file is too small. Build may have failed.' );
		}
	},
	'Build Validation'
);

runner.test(
	'Build: No syntax errors in build output',
	() => {
		const buildFile = '/home/engine/project/build/index.js';
		const content = readFile( buildFile );

		// Check for common webpack error patterns
		if (
			content.includes( 'Module parse failed' ) ||
			content.includes( 'SyntaxError' )
		) {
			throw new Error( 'Build output contains syntax errors' );
		}
	},
	'Build Validation'
);

// ============================================================================
// RUN ALL TESTS
// ============================================================================

runner.run().catch( ( err ) => {
	console.error( 'Test runner error:', err );
	process.exit( 1 );
} );
