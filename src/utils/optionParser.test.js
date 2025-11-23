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

	test( 'stringifyOptions outputs newline-delimited string', () => {
		const arr = [ 'Siempre', 'Sí, a veces', '¿Alguna vez?' ];
		expect( stringifyOptions( arr ) ).toBe(
			'Siempre\nSí, a veces\n¿Alguna vez?'
		);
	} );

	test( 'normalizeOptionsInput trims blank lines and normalizes CRLF', () => {
		const input = '\n\nSí, a veces\r\n\n\tNunca.\n¿Alguna vez?\n';
		expect( normalizeOptionsInput( input ) ).toBe(
			'Sí, a veces\nNunca.\n¿Alguna vez?'
		);
	} );
} );
