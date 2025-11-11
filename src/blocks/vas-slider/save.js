import { useBlockProps } from '@wordpress/block-editor';

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

export default function Save( { attributes } ) {
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
        conditionalLogic,
    } = attributes;

    const normalizedFieldName =
        fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

    const blockPropsData = {
        className: 'form-group eipsi-field eipsi-vas-slider-field',
        'data-field-name': normalizedFieldName,
        'data-required': required ? 'true' : 'false',
        'data-field-type': 'vas-slider',
    };

    if (
        conditionalLogic &&
        conditionalLogic.enabled &&
        conditionalLogic.rules &&
        conditionalLogic.rules.length > 0
    ) {
        blockPropsData[ 'data-conditional-logic' ] =
            JSON.stringify( conditionalLogic );
    }

    const blockProps = useBlockProps.save( blockPropsData );

    const inputId = getFieldId( normalizedFieldName );
    const currentValue =
        initialValue >= minValue && initialValue <= maxValue
            ? initialValue
            : Math.floor( ( minValue + maxValue ) / 2 );

    const labelArray =
        labels && labels.trim() !== ''
            ? labels
                    .split( ',' )
                    .map( ( l ) => l.trim() )
                    .filter( ( l ) => l !== '' )
            : [];
    const hasMultiLabels = labelArray.length > 0;

    return (
        <div { ...blockProps }>
            { label && (
                <label
                    className={ required ? 'required' : undefined }
                    htmlFor={ inputId }
                >
                    { label }
                </label>
            ) }
            <div
                className={ `vas-slider-container label-style-${
                    labelStyle || 'simple'
                } label-align-${ labelAlignment || 'justified' }` }
                data-scale={ `${ minValue }-${ maxValue }` }
            >
                { ! hasMultiLabels && (
                    <div className="vas-slider-labels">
                        { leftLabel && (
                            <span
                                className="vas-label-left"
                                style={ {
                                    backgroundColor: labelBgColor || undefined,
                                    borderColor: labelBorderColor || undefined,
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
                                { currentValue }
                            </span>
                        ) }
                        { rightLabel && (
                            <span
                                className="vas-label-right"
                                style={ {
                                    backgroundColor: labelBgColor || undefined,
                                    borderColor: labelBorderColor || undefined,
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
                                    backgroundColor: labelBgColor || undefined,
                                    borderColor: labelBorderColor || undefined,
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
                        { currentValue }
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
                    defaultValue={ currentValue }
                    required={ required }
                    data-required={ required ? 'true' : 'false' }
                    data-show-value={ showValue ? 'true' : 'false' }
                    data-touched="false"
                    aria-valuemin={ minValue }
                    aria-valuemax={ maxValue }
                    aria-valuenow={ currentValue }
                    aria-labelledby={ `${ inputId }-value` }
                />
            </div>
            { renderHelperText( helperText ) }
            <div className="form-error" aria-live="polite" />
        </div>
    );
}
