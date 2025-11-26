# Form Library & Templates - Guía de uso

## Resumen

La librería de formularios EIPSI permite crear formularios reutilizables que pueden ser insertados en cualquier página o entrada mediante:
- Un bloque Gutenberg "Formulario EIPSI"
- Un shortcode `[eipsi_form id="..."]`

## Flujo de trabajo completo

### 1. Crear un formulario reutilizable

1. En el admin de WordPress, ir a **EIPSI Forms → Form Library**
2. Hacer clic en **"Añadir nuevo"**
3. Darle un **nombre descriptivo** al formulario (ej: "PHQ-9 inicial", "Evaluación ansiedad 2025")
4. Usar el bloque **"EIPSI Form Container"** para armar el formulario:
   - Configurar el `formId` (slug único, ej: `phq9-inicial`)
   - Agregar páginas con el bloque **"Form Page"**
   - Agregar campos (texto, radio, likert, VAS slider, etc.)
   - Configurar lógica condicional si es necesario
   - Elegir preset de diseño y modo oscuro
5. **Publicar** el formulario

### 2. Insertar el formulario en una página/entrada

#### Opción A: Usar el bloque Gutenberg (recomendado)

1. En cualquier página o entrada, agregar el bloque **"Formulario EIPSI"**
2. En la configuración del bloque (sidebar derecha), seleccionar el formulario del dropdown
3. El formulario se mostrará automáticamente con todos sus estilos y funcionalidades

#### Opción B: Usar el shortcode

1. En el sidebar de edición de posts/páginas, buscar el widget **"Shortcode de Formularios EIPSI"**
2. Copiar el shortcode del formulario deseado (ej: `[eipsi_form id="123"]`)
3. Pegarlo en cualquier lugar del contenido (funciona en editor clásico y Gutenberg)

### 3. Gestionar formularios existentes

En **EIPSI Forms → Form Library** podrás:
- Ver todos los formularios creados
- Editar un formulario (se actualizará automáticamente en todas las páginas donde esté insertado)
- Ver el shortcode de cada formulario con botón de copia rápida
- Ver cuántas respuestas tiene cada formulario y cuándo fue la última
- Eliminar formularios que ya no se usen

## Ventajas de este sistema

### Para el clínico/investigador
- **Crea una vez, usa muchas veces**: No necesitás armar el PHQ-9 desde cero en cada página
- **Actualización centralizada**: Si modificás el formulario en la librería, se actualiza en todos lados
- **Organización clara**: Todos tus formularios en un solo lugar
- **Shortcodes copiables**: Fácil de compartir con secretarias o colaboradores

### Para el desarrollador
- **Compatibilidad total**: Los `form-container` existentes siguen funcionando igual
- **Zero Data Loss**: Las respuestas se guardan con el `formId` configurado en el container interno
- **Mismo rendering**: Tanto el bloque como el shortcode usan la misma lógica de renderizado

## Compatibilidad con formularios existentes

Los formularios creados directamente en páginas usando **"EIPSI Form Container"** siguen funcionando perfectamente. La librería es una **adición** al sistema, no un reemplazo.

Si querés convertir un formulario existente en plantilla reutilizable:
1. Copiá los bloques del formulario (Form Container completo)
2. Creá un nuevo formulario en Form Library
3. Pegá los bloques
4. Publicá

## Arquitectura técnica

```
┌─────────────────────────────────────────┐
│  Custom Post Type: eipsi_form_template  │
│  (almacena la estructura del formulario) │
└─────────────────────────────────────────┘
                 │
                 │ contiene
                 ▼
┌─────────────────────────────────────────┐
│      Bloque: vas-dinamico/form-container │
│      (con páginas, campos, estilos)      │
└─────────────────────────────────────────┘
                 │
                 │ renderizado por
                 ▼
┌─────────────────────────────────────────┐
│  • Bloque "Formulario EIPSI"             │
│    (vas-dinamico/form-block)             │
│                                          │
│  • Shortcode [eipsi_form id="123"]      │
└─────────────────────────────────────────┘
```

## API de shortcode

```php
[eipsi_form id="123"]
```

**Parámetros:**
- `id` (obligatorio): ID numérico del formulario (se obtiene de la librería)

**Ejemplo de uso:**
```php
// En contenido de página
Este es el cuestionario de evaluación inicial:

[eipsi_form id="42"]

Gracias por completarlo.
```

## Funciones PHP disponibles

### `eipsi_render_form_template_markup( $template_id, $context )`
Renderiza un formulario de la librería programáticamente.

```php
$html = eipsi_render_form_template_markup( 123, 'custom' );
echo $html;
```

### `eipsi_get_form_template( $template_id )`
Obtiene un objeto WP_Post del formulario o WP_Error si no existe.

```php
$template = eipsi_get_form_template( 123 );
if ( is_wp_error( $template ) ) {
    echo $template->get_error_message();
} else {
    echo $template->post_title;
}
```

## Tracking de respuestas

Las respuestas se guardan en la tabla `wp_vas_form_results` usando:
- **`form_name`**: El `formId` configurado en el Form Container interno
- El sistema extrae automáticamente el `form_name` al guardar un template (meta: `_eipsi_form_name`)
- La columna "Total Responses" y "Last Response" en Form Library se basan en este valor

## Notas importantes

1. **No renombrar el `formId` del Form Container interno**: Esto rompería el tracking de respuestas existentes
2. **Permisos**: Solo usuarios con capacidad `manage_options` pueden crear/editar formularios de la librería
3. **Performance**: Los formularios se renderizan usando `do_blocks()` en cada carga (sin caché por ahora)
4. **Gutenberg required**: Los formularios se crean con Gutenberg (no soporta editor clásico para templates)

## Roadmap futuro

Funcionalidades planificadas para próximos tickets:
- **Duplicar formularios** desde la librería (botón "Duplicate")
- **Exportar/importar JSON** para compartir templates entre instalaciones
- **Templates oficiales listos** (PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21)
- **Preview rápido** sin tener que abrir el editor
- **Categorías/tags** para organizar formularios por área clínica

## Soporte

Para reportar bugs o solicitar mejoras, contactar al equipo de desarrollo de EIPSI Forms.

**Versión de la documentación**: 1.3.0  
**Última actualización**: Febrero 2025
