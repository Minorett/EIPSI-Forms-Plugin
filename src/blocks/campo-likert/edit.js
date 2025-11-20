import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import {
    PanelBody,
    TextControl,
    TextareaControl,
    ToggleControl,
    RangeControl,
    Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const renderHelperText = ( text ) => {
    if ( ! text || text.trim() === '' ) {
        return null;
    }

    const lines = text.split( '\n' );

    return (
        <p className="field-helper">
            { lines.map( ( line, index ) => (
                <span key={ index }>
                    { line }
                    { index < lines.length - 1 && <br /> }
                </span>
            ) ) }
        </p>
    );
};

const getFieldId = ( fieldName ) => {
    if ( ! fieldName || fieldName.trim() === '' ) {
        return undefined;
    }

    const normalized = fieldName.trim().replace( /\s+/g, '-' );
    const sanitized = normalized.replace( /[^a-zA-Z0-9_-]/g, '-' );

    return `field-${ sanitized }`;
};

export default function Edit( { attributes, setAttributes, clientId } ) {
    const {
        fieldKey,
        fieldName,
        label,
        required,
        helperText,
        minValue,
        maxValue,
        labels,
    } = attributes;

    // Generate fieldKey from clientId if not set
    useEffect( () => {
        if ( ! fieldKey ) {
            const generatedKey = `likert-${ clientId.replace(
                /[^a-zA-Z0-9]/g,
                ''
            ) }`;
            setAttributes( { fieldKey: generatedKey } );
        }
    }, [ fieldKey, clientId, setAttributes ] );

    const effectiveFieldName =
        fieldName && fieldName.trim() !== '' ? fieldName.trim() : fieldKey;

    const blockProps = useBlockProps( {
        className: 'form-group eipsi-field eipsi-likert-field',
        'data-field-name': effectiveFieldName,
        'data-required': required ? 'true' : 'false',
        'data-field-type': 'likert',
        'data-min': minValue,
        'data-max': maxValue,
    } );

    const displayLabel =
        label && label.trim() !== ''
            ? label
            : __( 'Escala Likert', 'vas-dinamico-forms' );

    const labelArray =
        labels && labels.trim() !== ''
            ? labels
                    .split( ',' )
                    .map( ( l ) => l.trim() )
                    .filter( ( l ) => l !== '' )
            : [];

    const scale = [];
    for ( let i = minValue; i <= maxValue; i++ ) {
        scale.push( i );
    }

    const hasLabelMismatch =
        labelArray.length > 0 && labelArray.length !== scale.length;

    return (
        <>
            <InspectorControls>
                <PanelBody
                    title={ __( 'Field Settings', 'vas-dinamico-forms' ) }
                    initialOpen={ true }
                >
                    <TextControl
                        label={ __(
                            'Field Name / Slug',
                            'vas-dinamico-forms'
                        ) }
                        value={ fieldName || '' }
                        onChange={ ( value ) =>
                            setAttributes( { fieldName: value } )
                        }
                        help={ __(
                            'Used as the input name attribute (e.g., satisfaction)',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <TextControl
                        label={ __( 'Label', 'vas-dinamico-forms' ) }
                        value={ label || '' }
                        onChange={ ( value ) =>
                            setAttributes( { label: value } )
                        }
                    />
                    <ToggleControl
                        label={ __( 'Required field', 'vas-dinamico-forms' ) }
                        checked={ !! required }
                        onChange={ ( value ) =>
                            setAttributes( { required: !! value } )
                        }
                    />
                    <TextareaControl
                        label={ __( 'Helper text', 'vas-dinamico-forms' ) }
                        value={ helperText || '' }
                        onChange={ ( value ) =>
                            setAttributes( { helperText: value } )
                        }
                        help={ __(
                            'Displayed below the field to provide additional guidance.',
                            'vas-dinamico-forms'
                        ) }
                    />
                </PanelBody>

                <PanelBody
                    title={ __( 'Likert Scale Options', 'vas-dinamico-forms' ) }
                    initialOpen={ true }
                >
                    <RangeControl
                        label={ __( 'Minimum Value', 'vas-dinamico-forms' ) }
                        value={ minValue }
                        onChange={ ( value ) => {
                            if ( value < maxValue ) {
                                setAttributes( { minValue: value } );
                            }
                        } }
                        min={ 0 }
                        max={ 10 }
                        help={ __(
                            'The lowest value in the scale',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <RangeControl
                        label={ __( 'Maximum Value', 'vas-dinamico-forms' ) }
                        value={ maxValue }
                        onChange={ ( value ) => {
                            if ( value > minValue ) {
                                setAttributes( { maxValue: value } );
                            }
                        } }
                        min={ 1 }
                        max={ 10 }
                        help={ __(
                            'The highest value in the scale',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <TextareaControl
                        label={ __(
                            'Labels (comma-separated)',
                            'vas-dinamico-forms'
                        ) }
                        value={ labels || '' }
                        onChange={ ( value ) =>
                            setAttributes( { labels: value } )
                        }
                        help={ __(
                            'Optional labels for each scale point. Must match the number of scale points.',
                            'vas-dinamico-forms'
                        ) }
                    />
                    { hasLabelMismatch && (
                        <Notice status="warning" isDismissible={ false }>
                            {
                                // translators: %1$d: number of labels, %2$d: number of scale points
                                __(
                                    "You have %1$d labels but %2$d scale points. Labels will be ignored if count doesn't match.",
                                    'vas-dinamico-forms'
                                )
                                    .replace( '%1$d', labelArray.length )
                                    .replace( '%2$d', scale.length )
                            }
                        </Notice>
                    ) }
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                { label && (
                    <label
                        htmlFor={
                            effectiveFieldName
                                ? `${ getFieldId( effectiveFieldName ) }-label`
                                : undefined
                        }
                        className={ required ? 'required' : undefined }
                    >
                        { displayLabel }
                    </label>
                ) }
                <div
                    className="likert-scale likert-preview"
                    data-scale={ `${ minValue }-${ maxValue }` }
                >
                    <ul className="likert-list">
                        { scale.map( ( value, index ) => {
                            const optionId = `${ getFieldId(
                                effectiveFieldName
                            ) }-${ value }`;
                            const optionLabel =
                                labelArray[ index ] || value.toString();

                            return (
                                <li key={ value } className="likert-item">
                                    <label
                                        htmlFor={ optionId }
                                        className="likert-label-wrapper"
                                    >
                                        <input
                                            type="radio"
                                            name={ effectiveFieldName }
                                            id={ optionId }
                                            value={ value }
                                            required={ required }
                                            data-required={
                                                required ? 'true' : 'false'
                                            }
                                            disabled
                                        />
                                        <span className="likert-label-text">
                                            { optionLabel }
                                        </span>
                                    </label>
                                </li>
                            );
                        } ) }
                    </ul>
                </div>
                { renderHelperText( helperText ) }
                <div className="form-error" aria-live="polite" />
            </div>
        </>
    );
}
