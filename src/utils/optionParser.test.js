/* eslint-env jest */
import {
	normalizeLineEndings,
	parseCommaSeparated,
	parseOptions,
	stringifyOptions,
	normalizeOptionsInput,
	encodeNewlinesForAttribute,
	decodeNewlinesFromAttribute,
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

	test( 'encodeNewlinesForAttribute encodes newlines as HTML entities', () => {
		const input = 'Muy\nbajo;Bajo;Neutral';
		expect( encodeNewlinesForAttribute( input ) ).toBe(
			'Muy&#10;bajo;Bajo;Neutral'
		);
	} );

	test( 'encodeNewlinesForAttribute handles multiple newlines', () => {
		const input = 'Línea 1\nLínea 2\nLínea 3';
		expect( encodeNewlinesForAttribute( input ) ).toBe(
			'Línea 1&#10;Línea 2&#10;Línea 3'
		);
	} );

	test( 'encodeNewlinesForAttribute returns empty string for null/undefined', () => {
		expect( encodeNewlinesForAttribute( null ) ).toBe( '' );
		expect( encodeNewlinesForAttribute( undefined ) ).toBe( '' );
		expect( encodeNewlinesForAttribute( '' ) ).toBe( '' );
	} );

	test( 'decodeNewlinesFromAttribute decodes &#10; to newlines', () => {
		const input = 'Muy&#10;bajo;Bajo;Neutral';
		expect( decodeNewlinesFromAttribute( input ) ).toBe(
			'Muy\nbajo;Bajo;Neutral'
		);
	} );

	test( 'decodeNewlinesFromAttribute decodes &#x0A; to newlines', () => {
		const input = 'Muy&#x0A;bajo;Bajo;Neutral';
		expect( decodeNewlinesFromAttribute( input ) ).toBe(
			'Muy\nbajo;Bajo;Neutral'
		);
	} );

	test( 'decodeNewlinesFromAttribute handles mixed formats', () => {
		const input = 'Línea 1&#10;Línea 2\nLínea 3&#x0A;Línea 4';
		expect( decodeNewlinesFromAttribute( input ) ).toBe(
			'Línea 1\nLínea 2\nLínea 3\nLínea 4'
		);
	} );

	test( 'decodeNewlinesFromAttribute returns empty string for null/undefined', () => {
		expect( decodeNewlinesFromAttribute( null ) ).toBe( '' );
		expect( decodeNewlinesFromAttribute( undefined ) ).toBe( '' );
		expect( decodeNewlinesFromAttribute( '' ) ).toBe( '' );
	} );

	test( 'round-trip encoding/decoding preserves original newlines', () => {
		const original = 'Muy\nbajo;Bajo;Neutral;Alto;Muy\nalto';
		const encoded = encodeNewlinesForAttribute( original );
		const decoded = decodeNewlinesFromAttribute( encoded );
		expect( decoded ).toBe( original );
	} );
} );
