# Completion Configuration Logic

## Overview

EIPSI Forms tiene dos niveles de configuración para la página de finalización (thank-you page):

1. **Configuración Global (Default)**: Se aplica a todos los formularios que no tienen configuración personalizada
2. **Configuración por Formulario (Override)**: Definida en el Form Container block, sobrescribe la configuración global solo para ese formulario específico

## Configuración Global (Default)

### Ubicación
- **Admin**: Results & Experience → Pestaña "Finalización"
- **Storage**: WordPress option `eipsi_global_completion_message`

### Backend
- Handler AJAX: `eipsi_save_completion_message` (`admin/ajax-handlers.php`)
- GET handler: `eipsi_get_completion_config` (devuelve config global)
- Clase: `EIPSI_Completion_Message` (`admin/completion-message-backend.php`)

### Campos disponibles
- `title`: Título de la página de finalización
- `message`: Mensaje principal (acepta HTML)
- `show_logo`: Mostrar logo del sitio (booleano)
- `show_home_button`: Mostrar botón de acción (booleano)
- `button_text`: Texto del botón (ej: "Comenzar de nuevo")
- `button_action`: Acción del botón (`reload`, `close`, `none`)
- `show_animation`: Animación sutil de confetti (booleano)

### Valores por defecto
```php
[
    'title'            => '¡Gracias por completar el formulario!',
    'message'          => 'Sus respuestas han sido registradas correctamente.',
    'show_logo'        => true,
    'show_home_button' => true,
    'button_text'      => 'Comenzar de nuevo',
    'button_action'    => 'reload',
    'show_animation'   => false,
]
```

## Configuración por Formulario (Override)

### Ubicación
- **Editor de bloques**: Seleccionar Form Container → Inspector Controls → "Completion Page"
- **Storage**: Atributos del bloque Form Container

### Atributos del bloque
```json
{
    "completionTitle": {
        "type": "string",
        "default": "¡Gracias por completar el cuestionario!"
    },
    "completionMessage": {
        "type": "string",
        "default": "Sus respuestas han sido registradas correctamente."
    },
    "completionLogoId": {
        "type": "number",
        "default": 0
    },
    "completionLogoUrl": {
        "type": "string",
        "default": ""
    },
    "completionButtonLabel": {
        "type": "string",
        "default": "Comenzar de nuevo"
    }
}
```

### Renderizado en frontend
El Form Container guarda estos atributos como data attributes en el HTML renderizado:

```html
<div class="eipsi-form"
     data-completion-title="¡Gracias!"
     data-completion-message="Tu respuesta fue registrada."
     data-completion-logo="https://..."
     data-completion-button-label="Volver">
    ...
</div>
```

## Lógica de Prioridad (Frontend)

Cuando se completa un formulario, el frontend (`assets/js/eipsi-forms.js`) sigue esta lógica:

```javascript
showIntegratedThankYouPage(form) {
    // 1. ¿Existe un bloque Thank-You Page personalizado?
    const existingThankYouPage = form.querySelector('.eipsi-thank-you-page-block');
    if (existingThankYouPage) {
        this.showExistingThankYouPage(form, existingThankYouPage);
        return;
    }
    
    // 2. ¿El Form Container tiene config personalizada?
    const formContainer = form.closest('.eipsi-form');
    const completionConfig = this.getCompletionConfigFromContainer(formContainer);
    if (completionConfig) {
        this.createThankYouPage(form, completionConfig);
        return;
    }
    
    // 3. Fallback: usar configuración global del backend
    this.fetchCompletionConfigFromBackend(form);
}
```

### Detalles de cada nivel

#### Nivel 1: Bloque Thank-You Page (FUTURO)
- **Máxima flexibilidad**: Editor visual completo de Gutenberg
- **Prioridad**: MÁXIMA (si existe, ignora todo lo demás)
- **Estado actual**: Planificado para versión futura

#### Nivel 2: Form Container Override
- **Flexibilidad**: Campos básicos (título, mensaje, logo, botón)
- **Prioridad**: ALTA (sobrescribe configuración global)
- **Estado actual**: IMPLEMENTADO
- **Cuándo usar**: Cuando un formulario específico necesita personalización

#### Nivel 3: Configuración Global
- **Flexibilidad**: Campos básicos + opciones avanzadas
- **Prioridad**: BAJA (default para todos los formularios)
- **Estado actual**: IMPLEMENTADO
- **Cuándo usar**: Como base para todos los formularios del sitio

## Flujo Clínico Típico

### Caso 1: Consultorio con un solo formulario
- **Configurar**: Solo la Configuración Global
- **Resultado**: Todos los formularios usan la misma página de finalización

### Caso 2: Consultorio con múltiples formularios (PHQ-9, GAD-7, etc.)
- **Configurar**: 
  - Configuración Global con mensaje genérico
  - Override en formularios específicos que necesiten mensajes personalizados
- **Resultado**: Cada formulario puede tener su propio mensaje de gracias

### Caso 3: Investigación con múltiples sitios
- **Configurar**:
  - Configuración Global con logo y mensaje del estudio
  - Override en formularios de consentimiento/cierre con instrucciones específicas
- **Resultado**: Consistencia visual + flexibilidad contextual

## Verificación en Producción

### Admin
1. Ir a Results & Experience → Finalización
2. Cambiar el título a algo único (ej: "TEST GLOBAL 123")
3. Guardar
4. Recargar la página → Debe mostrar "TEST GLOBAL 123"

### Frontend (configuración global)
1. Crear un formulario SIN configuración en el Container
2. Enviarlo
3. Debe mostrar el mensaje de la configuración global

### Frontend (override)
1. Editar un Form Container
2. Cambiar "Completion Page" → Título → "TEST OVERRIDE 456"
3. Publicar
4. Enviar el formulario
5. Debe mostrar "TEST OVERRIDE 456" (no "TEST GLOBAL 123")

## Troubleshooting

### Problema: Guardado de configuración no funciona
- **Verificar nonce**: El handler AJAX verifica `eipsi_admin_nonce`
- **Verificar permisos**: El usuario debe tener `manage_options`
- **Verificar logs**: Buscar errores en Console (DevTools)

### Problema: Override no se aplica
- **Verificar data attributes**: Inspeccionar el HTML del Form Container en frontend
- **Verificar JS**: Console → Buscar errores relacionados con `getCompletionConfigFromContainer`

### Problema: Configuración global no se aplica
- **Verificar AJAX**: Network tab → Buscar la llamada a `eipsi_get_completion_config`
- **Verificar respuesta**: Debe devolver `success: true` con `data.config`

## Migración desde versión antigua

Si actualizas desde una versión anterior a v1.3:
- Los formularios existentes seguirán usando la configuración global por defecto
- Puedes personalizar formularios individuales editando el Form Container
- No se pierde ninguna configuración existente
