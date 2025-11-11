import {
    InspectorControls,
    useBlockProps,
    ColorPalette,
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    TextareaControl,
    ToggleControl,
    RangeControl,
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import ConditionalLogicControl from '../../components/ConditionalLogicControl';

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
        fieldName,
        label,
        required,
        helperText,
        leftLabel,
        rightLabel,
        labels,
        minValue,
        maxValue,
        step,
        initialValue,
        showValue,
        labelStyle,
        labelAlignment,
        labelBgColor,
        labelBorderColor,
        labelTextColor,
    } = attributes;

    const normalizedFieldName =
        fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

    const blockProps = useBlockProps( {
        className: 'form-group eipsi-field eipsi-vas-slider-field',
        'data-field-name': normalizedFieldName,
        'data-required': required ? 'true' : 'false',
        'data-field-type': 'vas-slider',
    } );

    const displayLabel =
        label && label.trim() !== ''
            ? label
            : __( 'VAS Slider', 'vas-dinamico-forms' );
    const inputId = getFieldId( normalizedFieldName );

    const currentValue =
        initialValue >= minValue && initialValue <= maxValue
            ? initialValue
            : Math.floor( ( minValue + maxValue ) / 2 );
    const [ previewValue, setPreviewValue ] = useState( currentValue );

    const labelArray =
        labels && labels.trim() !== ''
            ? labels
                    .split( ',' )
                    .map( ( l ) => l.trim() )
                    .filter( ( l ) => l !== '' )
            : [];
    const hasMultiLabels = labelArray.length > 0;

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
                            'Used as the input name attribute (e.g., pain_level)',
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
                    title={ __( 'VAS Slider Options', 'vas-dinamico-forms' ) }
                    initialOpen={ true }
                >
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
                            'Optional: Multiple labels for scale points (e.g., "Muy mal,Mal,Neutral,Bien,Muy bien")',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <TextControl
                        label={ __( 'Left Label', 'vas-dinamico-forms' ) }
                        value={ leftLabel || '' }
                        onChange={ ( value ) =>
                            setAttributes( { leftLabel: value } )
                        }
                        help={ __(
                            'Label for the minimum value (used when multi-labels are not set)',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <TextControl
                        label={ __( 'Right Label', 'vas-dinamico-forms' ) }
                        value={ rightLabel || '' }
                        onChange={ ( value ) =>
                            setAttributes( { rightLabel: value } )
                        }
                        help={ __(
                            'Label for the maximum value (used when multi-labels are not set)',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <RangeControl
                        label={ __( 'Minimum Value', 'vas-dinamico-forms' ) }
                        value={ minValue }
                        onChange={ ( value ) => {
                            setAttributes( { minValue: value } );
                            if ( maxValue <= value ) {
                                setAttributes( { maxValue: value + 1 } );
                            }
                            if ( initialValue < value ) {
                                const newInitial = Math.floor(
                                    ( value + maxValue ) / 2
                                );
                                setAttributes( { initialValue: newInitial } );
                                setPreviewValue( newInitial );
                            }
                        } }
                        min={ 0 }
                        max={ 100 }
                    />
                    <RangeControl
                        label={ __( 'Maximum Value', 'vas-dinamico-forms' ) }
                        value={ maxValue }
                        onChange={ ( value ) => {
                            setAttributes( { maxValue: value } );
                            if ( minValue >= value ) {
                                setAttributes( { minValue: value - 1 } );
                            }
                            if ( initialValue > value ) {
                                const newInitial = Math.floor(
                                    ( minValue + value ) / 2
                                );
                                setAttributes( { initialValue: newInitial } );
                                setPreviewValue( newInitial );
                            }
                        } }
                        min={ 1 }
                        max={ 1000 }
                    />
                    <RangeControl
                        label={ __( 'Step', 'vas-dinamico-forms' ) }
                        value={ step }
                        onChange={ ( value ) =>
                            setAttributes( { step: value } )
                        }
                        min={ 0.1 }
                        max={ 10 }
                        step={ 0.1 }
                        help={ __(
                            'Increment for each slider movement',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <RangeControl
                        label={ __( 'Initial Value', 'vas-dinamico-forms' ) }
                        value={ initialValue }
                        onChange={ ( value ) => {
                            setAttributes( { initialValue: value } );
                            setPreviewValue( value );
                        } }
                        min={ minValue }
                        max={ maxValue }
                        step={ step }
                        help={ __(
                            'Default slider position',
                            'vas-dinamico-forms'
                        ) }
                    />
                    <ToggleControl
                        label={ __(
                            'Show current value',
                            'vas-dinamico-forms'
                        ) }
                        checked={ !! showValue }
                        onChange={ ( value ) =>
                            setAttributes( { showValue: !! value } )
                        }
                    />
                </PanelBody>

                <PanelBody
                    title={ __( 'Label Styling', 'vas-dinamico-forms' ) }
                    initialOpen={ false }
                >
                    <SelectControl
                        label={ __( 'Label Style', 'vas-dinamico-forms' ) }
                        value={ labelStyle }
                        options={ [
                            {
                                label: __(
                                    'Simple text (no decoration)',
                                    'vas-dinamico-forms'
                                ),
                                value: 'simple',
                            },
                            {
                                label: __(
                                    'Squares (badge style)',
                                    'vas-dinamico-forms'
                                ),
                                value: 'squares',
                            },
                            {
                                label: __(
                                    'Buttons (outlined style)',
                                    'vas-dinamico-forms'
                                ),
                                value: 'buttons',
                            },
                        ] }
                        onChange={ ( value ) =>
                            setAttributes( { labelStyle: value } )
                        }
                    />
                    <SelectControl
                        label={ __( 'Label Alignment', 'vas-dinamico-forms' ) }
                        value={ labelAlignment }
                        options={ [
                            {
                                label: __(
                                    'Justified (full width)',
                                    'vas-dinamico-forms'
                                ),
                                value: 'justified',
                            },
                            {
                                label: __(
                                    'Centered (with spacing)',
                                    'vas-dinamico-forms'
                                ),
                                value: 'centered',
                            },
                        ] }
                        onChange={ ( value ) =>
                            setAttributes( { labelAlignment: value } )
                        }
                    />
                    { labelStyle !== 'simple' && (
                        <>
                            <p
                                style={ {
                                    marginTop: '16px',
                                    marginBottom: '8px',
                                } }
                            >
                                <strong>
                                    { __(
                                        'Background Color',
                                        'vas-dinamico-forms'
                                    ) }
                                </strong>
                            </p>
                            <ColorPalette
                                value={ labelBgColor }
                                onChange={ ( value ) =>
                                    setAttributes( {
                                        labelBgColor: value || '',
                                    } )
                                }
                                clearable={ true }
                            />
                            <p
                                style={ {
                                    marginTop: '16px',
                                    marginBottom: '8px',
                                } }
                            >
                                <strong>
                                    { __(
                                        'Border Color',
                                        'vas-dinamico-forms'
                                    ) }
                                </strong>
                            </p>
                            <ColorPalette
                                value={ labelBorderColor }
                                onChange={ ( value ) =>
                                    setAttributes( {
                                        labelBorderColor: value || '',
                                    } )
                                }
                                clearable={ true }
                            />
                            { labelStyle === 'buttons' && (
                                <>
                                    <p
                                        style={ {
                                            marginTop: '16px',
                                            marginBottom: '8px',
                                        } }
                                    >
                                        <strong>
                                            { __(
                                                'Text Color',
                                                'vas-dinamico-forms'
                                            ) }
                                        </strong>
                                    </p>
                                    <ColorPalette
                                        value={ labelTextColor }
                                        onChange={ ( value ) =>
                                            setAttributes( {
                                                labelTextColor: value || '',
                                            } )
                                        }
                                        clearable={ true }
                                    />
                                </>
                            ) }
                        </>
                    ) }
                </PanelBody>

                <ConditionalLogicControl
                    attributes={ attributes }
                    setAttributes={ setAttributes }
                    clientId={ clientId }
                    mode="numeric"
                />
            </InspectorControls>

            <div { ...blockProps }>
                <label
                    className={ required ? 'required' : undefined }
                    htmlFor={ inputId }
                >
                    { displayLabel }
                </label>
                <div
                    className={ `vas-slider-container vas-slider-preview label-style-${ labelStyle } label-align-${ labelAlignment }` }
                    data-scale={ `${ minValue }-${ maxValue }` }
                >
                    { ! hasMultiLabels && (
                        <div className="vas-slider-labels">
                            { leftLabel && (
                                <span
                                    className="vas-label-left"
                                    style={ {
                                        backgroundColor:
                                            labelBgColor || undefined,
                                        borderColor:
                                            labelBorderColor || undefined,
                                        color:
                                            labelStyle === 'buttons' &&
                                            labelTextColor
                                                ? labelTextColor
                                                : undefined,
                                    } }
                                >
                                    { leftLabel }
                                </span>
                            ) }
                            { showValue && (
                                <span
                                    className="vas-current-value"
                                    id={ `${ inputId }-value` }
                                >
                                    { previewValue }
                                </span>
                            ) }
                            { rightLabel && (
                                <span
                                    className="vas-label-right"
                                    style={ {
                                        backgroundColor:
                                            labelBgColor || undefined,
                                        borderColor:
                                            labelBorderColor || undefined,
                                        color:
                                            labelStyle === 'buttons' &&
                                            labelTextColor
                                                ? labelTextColor
                                                : undefined,
                                    } }
                                >
                                    { rightLabel }
                                </span>
                            ) }
                        </div>
                    ) }
                    { hasMultiLabels && (
                        <div className="vas-multi-labels">
                            { labelArray.map( ( labelText, index ) => (
                                <span
                                    key={ index }
                                    className="vas-multi-label"
                                    style={ {
                                        backgroundColor:
                                            labelBgColor || undefined,
                                        borderColor:
                                            labelBorderColor || undefined,
                                        color:
                                            labelStyle === 'buttons' &&
                                            labelTextColor
                                                ? labelTextColor
                                                : undefined,
                                    } }
                                >
                                    { labelText }
                                </span>
                            ) ) }
                        </div>
                    ) }
                    { hasMultiLabels && showValue && (
                        <div
                            className="vas-current-value-solo"
                            id={ `${ inputId }-value` }
                        >
                            { previewValue }
                        </div>
                    ) }
                    <input
                        type="range"
                        name={ normalizedFieldName }
                        id={ inputId }
                        className="vas-slider"
                        min={ minValue }
                        max={ maxValue }
                        step={ step }
                        value={ previewValue }
                        onChange={ ( e ) =>
                            setPreviewValue( parseFloat( e.target.value ) )
                        }
                        required={ required }
                        data-required={ required ? 'true' : 'false' }
                        data-show-value={ showValue ? 'true' : 'false' }
                        aria-valuemin={ minValue }
                        aria-valuemax={ maxValue }
                        aria-valuenow={ previewValue }
                        aria-labelledby={ `${ inputId }-value` }
                    />
                </div>
                { renderHelperText( helperText ) }
                <div className="form-error" aria-live="polite" />
            </div>
        </>
    );
}
