/**
 * Utilidades compartidas para bloques Gutenberg
 * Funciones duplicadas entre edit.js y save.js para evitar dependencias circulares
 */

const { 
    createElement: el, 
    createElement: h 
} = wp.element;

/**
 * Renderiza el cuerpo de descripción
 */
export const renderDescriptionBody = (attributes) => {
    const { description, descriptionTextAlign = 'left' } = attributes;
    
    if (!description) return null;
    
    return (
        el('div', {
            className: 'eipsi-description-body',
            style: { textAlign: descriptionTextAlign }
        },
        description.includes('\n') ? (
            description.split('\n').map((line, index) => 
                el('p', { key: index }, line)
            )
        ) : (
            el('p', null, description)
        ))
    );
};

/**
 * Renderiza texto de ayuda
 */
export const renderHelperText = (helperText, helperTextAlign = 'left') => {
    if (!helperText) return null;
    
    return el('div', {
        className: 'eipsi-helper-text',
        style: { 
            textAlign: helperTextAlign,
            marginTop: '8px',
            fontSize: '14px',
            color: '#666'
        }
    }, helperText);
};

/**
 * Genera ID único para campos
 */
export const getFieldId = (fieldName, prefix = 'eipsi-field') => {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substr(2, 5);
    return `${prefix}-${fieldName}-${timestamp}-${random}`;
};

/**
 * Calcula valor máximo para escalas
 */
export const calculateMaxValue = (scale) => {
    if (!scale) return 100;
    
    const { min = 0, max = 100, step = 1 } = scale;
    return Math.ceil((max - min) / step);
};

/**
 * Renderiza el cuerpo del consentimiento
 */
export const renderConsentBody = (attributes) => {
    const { 
        consentTitle = '', 
        consentText = '', 
        titleTextAlign = 'left',
        consentTextAlign = 'left',
        backgroundColor = '#f8f9fa',
        textColor = '#333'
    } = attributes;
    
    return el('div', {
        className: 'eipsi-consent-body',
        style: { 
            backgroundColor,
            color: textColor,
            padding: '20px',
            borderRadius: '8px',
            border: '1px solid #e0e0e0'
        }
    },
    // Título
    consentTitle && el('h3', {
        key: 'title',
        style: { 
            textAlign: titleTextAlign,
            marginBottom: '15px',
            color: textColor,
            fontSize: '18px',
            fontWeight: 'bold'
        }
    }, consentTitle),
    
    // Texto
    consentText && el('div', {
        key: 'text',
        className: 'eipsi-consent-text',
        style: { 
            textAlign: consentTextAlign,
            lineHeight: '1.6',
            fontSize: '14px'
        }
    },
    consentText.includes('\n') ? (
        consentText.split('\n').map((paragraph, index) => 
            el('p', { key: index, style: { marginBottom: '10px' } }, paragraph)
        )
    ) : (
        el('p', null, consentText)
    )));
};