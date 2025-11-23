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
const RICH_OPTIONS_TEXT =
    'Opción A, con coma\nOpción "entre comillas"\nSí – con tilde y guion\nDos  espacios';
const RICH_OPTIONS_ARRAY = [
    'Opción A, con coma',
    'Opción "entre comillas"',
    'Sí – con tilde y guion',
    'Dos  espacios',
];

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
        const input = 'Option 1\nOption  2  (two spaces)\nOption   3   (three)';
        const normalized = normalizeOptionsInput( input );
        expect( normalized ).toBe(
            'Option 1\nOption  2  (two spaces)\nOption   3   (three)'
        );
    } );
} );
