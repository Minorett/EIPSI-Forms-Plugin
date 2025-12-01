/* eslint-env jest */
import {
	normalizeLineEndings,
	parseCommaSeparated,
	parseOptions,
	stringifyOptions,
	normalizeOptionsInput,
} from './optionParser';

const CRLF_STRING = 'Sí\r\nNo\r\nTal vez';
const LEGACY_CSV = 'Nunca.,"Sí, a veces","¿Alguna vez?","Dijo ""no"""';
const SEMICOLON_OPTIONS =
	'Ansioso, inquieto; Tranquilo, relajado; Neutral, sin cambios';
const RICH_OPTIONS_TEXT =
	'Opción A, con coma; Opción "entre comillas"; Sí – con tilde y guion; Dos  espacios';
const RICH_OPTIONS_ARRAY = [
	'Opción A, con coma',
	'Opción "entre comillas"',
	'Sí – con tilde y guion',
	'Dos  espacios',
];
const LEGACY_NEWLINE_TEXT =
	'Opción A, con coma\nOpción "entre comillas"\nSí – con tilde y guion\nDos  espacios';

describe( 'optionParser utility', () => {
	test( 'normalizeLineEndings converts CRLF and CR to LF', () => {
		expect( normalizeLineEndings( CRLF_STRING ) ).toBe( 'Sí\nNo\nTal vez' );
		expect( normalizeLineEndings( 'Hola\rMundo' ) ).toBe( 'Hola\nMundo' );
	} );

	test( 'parseCommaSeparated respects quoted commas and escaped quotes', () => {
		const parsed = parseCommaSeparated( LEGACY_CSV );
		expect( parsed ).toEqual( [
			'Nunca.',
			'Sí, a veces',
			'¿Alguna vez?',
			'Dijo "no"',
		] );
	} );

	test( 'parseOptions uses newline when present', () => {
		const value = 'Siempre\n"Nunca."\n¿Alguna vez?\r\n';
		expect( parseOptions( value ) ).toEqual( [
			'Siempre',
			'"Nunca."',
			'¿Alguna vez?',
		] );
	} );

	test( 'parseOptions falls back to legacy comma parsing', () => {
		expect( parseOptions( LEGACY_CSV ) ).toEqual( [
			'Nunca.',
			'Sí, a veces',
			'¿Alguna vez?',
			'Dijo "no"',
		] );
	} );

	test( 'stringifyOptions outputs semicolon-separated string (NEW STANDARD)', () => {
		const arr = [ 'Siempre', 'Sí, a veces', '¿Alguna vez?' ];
		expect( stringifyOptions( arr ) ).toBe(
			'Siempre; Sí, a veces; ¿Alguna vez?'
		);
	} );

	test( 'normalizeOptionsInput trims blank lines and normalizes CRLF', () => {
		const input = '\n\nSí, a veces\r\n\n\tNunca.\n¿Alguna vez?\n';
		expect( normalizeOptionsInput( input ) ).toBe(
			'Sí, a veces\nNunca.\n¿Alguna vez?'
		);
	} );

	test( 'parseOptions preserves commas, quotes, tildes and double spaces', () => {
		const parsed = parseOptions( RICH_OPTIONS_TEXT );
		expect( parsed ).toEqual( RICH_OPTIONS_ARRAY );
	} );

	test( 'stringifyOptions preserves commas, quotes, tildes and double spaces', () => {
		const stringified = stringifyOptions( RICH_OPTIONS_ARRAY );
		expect( stringified ).toBe( RICH_OPTIONS_TEXT );
	} );

	test( 'round-trip preserves rich options exactly', () => {
		const originalText = RICH_OPTIONS_TEXT;
		const parsed = parseOptions( originalText );
		const stringified = stringifyOptions( parsed );
		expect( stringified ).toBe( originalText );
	} );

	test( 'normalizeOptionsInput preserves internal spacing', () => {
		const input = 'Option 1; Option  2  (two spaces); Option   3   (three)';
		const normalized = normalizeOptionsInput( input );
		expect( normalized ).toBe(
			'Option 1; Option  2  (two spaces); Option   3   (three)'
		);
	} );

	test( 'parseOptions prioritizes semicolon over newline (NEW STANDARD)', () => {
		const parsed = parseOptions( SEMICOLON_OPTIONS );
		expect( parsed ).toEqual( [
			'Ansioso, inquieto',
			'Tranquilo, relajado',
			'Neutral, sin cambios',
		] );
	} );

	test( 'parseOptions handles legacy newline format for backwards compatibility', () => {
		const parsed = parseOptions( LEGACY_NEWLINE_TEXT );
		expect( parsed ).toEqual( RICH_OPTIONS_ARRAY );
	} );

	test( 'round-trip with semicolons preserves rich options with commas', () => {
		const original = 'Sí, absolutamente; No, para nada; Tal vez, a veces';
		const parsed = parseOptions( original );
		expect( parsed ).toEqual( [
			'Sí, absolutamente',
			'No, para nada',
			'Tal vez, a veces',
		] );
		const stringified = stringifyOptions( parsed );
		expect( stringified ).toBe( original );
	} );

	test( 'normalizeOptionsInput converts mixed newline input to semicolon format', () => {
		const input = 'Opción 1\nOpción 2\nOpción 3';
		const normalized = normalizeOptionsInput( input );
		expect( normalized ).toBe( 'Opción 1; Opción 2; Opción 3' );
	} );

	test( 'normalizeOptionsInput converts legacy comma input to semicolon format', () => {
		const input = 'Opción 1, Opción 2, Opción 3';
		const normalized = normalizeOptionsInput( input );
		expect( normalized ).toBe( 'Opción 1; Opción 2; Opción 3' );
	} );
} );
