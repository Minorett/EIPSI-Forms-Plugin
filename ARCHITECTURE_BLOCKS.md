# Arquitectura de Bloques EIPSI Forms

## Última actualización: Implementación Parte 2 (Ticket: Reinterpretación de Bloque Forms)

---

## Conceptos Fundamentales

EIPSI Forms utiliza **dos roles de bloques completamente diferentes** que trabajan juntos pero **nunca se mezclan**:

### 1. **Bloque de Construcción** → EIPSI Form Container

**Propósito:** Crear y diseñar formularios

**Dónde se usa:**
- Solo dentro de la **Form Library** (CPT `eipsi_form_template`)
- Editor interno donde los clínicos **construyen** sus formularios
- Contiene páginas, campos, lógica condicional, navegación, etc.

**Bloques asociados:**
- `vas-dinamico/form-container` (contenedor principal)
- `vas-dinamico/pagina` (páginas del formulario)
- Todos los bloques de campos:
  - `campo-texto`
  - `campo-textarea`
  - `campo-descripcion`
  - `campo-select`
  - `campo-radio`
  - `campo-multiple`
  - `campo-likert`
  - `vas-slider`

**Características técnicas:**
- `supports` → align, spacing, margin, padding
- `providesContext` → `vas-dinamico/formId` (para campos hijos)
- Atributos principales:
  - `formId` (string): identificador único del formulario
  - `styleConfig` (object): configuración de diseño
  - `presetName` (string): preset visual aplicado
  - `allowBackwardsNav` (boolean): permitir navegación hacia atrás
  - `showProgressBar` (boolean): mostrar barra de progreso

**Render:**
- `save.js` → guarda estructura completa en post_content
- Frontend usa `do_blocks()` para procesar contenido guardado

---

### 2. **Bloque de Inserción** → Formulario EIPSI

**Propósito:** Insertar formularios ya creados en páginas/posts

**Dónde se usa:**
- Páginas públicas (`post`, `page`)
- Cualquier lugar donde el clínico quiera **mostrar** un formulario

**Bloque:**
- `vas-dinamico/form-block`

**Características técnicas:**
- **Dynamic block** (render_callback en PHP)
- `ServerSideRender` en el editor
- Atributo principal:
  - `templateId` (number): ID del post de tipo `eipsi_form_template`

**Flujo de renderizado:**
1. Usuario selecciona formulario desde dropdown
2. Dropdown se puebla con CPT `eipsi_form_template` (vía REST API)
3. Al guardar, almacena `templateId`
4. En frontend:
   - `eipsi_render_form_block()` → callback principal
   - `eipsi_resolve_template_id_from_attributes()` → resuelve ID (con backward compat)
   - `eipsi_get_form_template()` → fetch post del formulario
   - `eipsi_render_form_template_markup()` → renderiza contenido vía `do_blocks()`

**Componentes de UI:**
- `InspectorControls` → panel lateral con dropdown de formularios
- `SelectControl` → selector visual de formularios
- Estados:
  - Cargando: Spinner + "Cargando formularios…"
  - Sin formularios: mensaje + link a crear primer formulario
  - Formulario seleccionado: vista previa via ServerSideRender
  - Sin seleccionar: placeholder con instrucciones

---

## Lógica de Restricción de Bloques

**Implementación:** `admin/form-library.php` → `eipsi_limit_blocks_by_context()`

**Filtro usado:** `allowed_block_types_all`

### Reglas aplicadas:

#### En Form Library (`eipsi_form_template`):
- ✅ **PERMITIDOS:** Container + todos los bloques de campos (construcción)
- ✅ **PERMITIDOS:** Bloques core de WordPress (paragraph, heading, image, etc.)
- ❌ **OCULTO:** Bloque "Formulario EIPSI" (inserción)
  - **Excepción:** Si ya existe en el contenido (compatibilidad)

#### En Páginas/Posts normales:
- ✅ **PERMITIDO:** Bloque "Formulario EIPSI" (inserción)
- ✅ **PERMITIDOS:** Bloques core de WordPress
- ❌ **OCULTOS:** Container + bloques de campos (construcción)
  - **Excepción:** Si ya existen en el contenido (compatibilidad)

### Estrategia de Compatibilidad:

```php
// Solo ocultamos bloques si NO existen ya en el contenido
if (!has_block($block_name, $post_content)) {
    // Ocultar del inserter
    $allowed_block_types = array_diff($allowed_block_types, array($block_name));
}
```

**Resultado:** Sitios con bloques "legacy" (insertados antes de esta actualización) no se rompen.

---

## Render Path Compartido

**Bloque "Formulario EIPSI" y Shortcode `[eipsi_form id="123"]` usan el mismo render helper:**

### Core Helper: `eipsi_render_form_template_markup()`

**Archivo:** `includes/form-template-render.php`

**Parámetros:**
- `$template_id` (int): ID del post de formulario
- `$context` (string): 'block' o 'shortcode' (para tracking)

**Flujo:**
1. Validar que el template exista y sea válido
2. `vas_dinamico_enqueue_frontend_assets()` → assets CSS/JS
3. `do_blocks($template->post_content)` → renderizar contenido Gutenberg
4. Wrappear en `<div class="eipsi-form-template-wrapper">`

**Callbacks específicos:**
- `eipsi_render_form_block($attributes)` → bloque Gutenberg
- `eipsi_render_form_shortcode_markup($template_id)` → shortcode

---

## Backward Compatibility

### Atributo `formId` (legacy) → `templateId` (actual)

**Helper:** `eipsi_resolve_template_id_from_attributes()`

```php
function eipsi_resolve_template_id_from_attributes($attributes) {
    // Nuevo atributo: templateId (número)
    $template_id = isset($attributes['templateId']) ? absint($attributes['templateId']) : 0;
    
    // Backward compatibility: formId (string) → buscar en postmeta
    if (!$template_id && !empty($attributes['formId'])) {
        global $wpdb;
        $template_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$meta_table} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
            '_eipsi_form_name',
            $attributes['formId']
        ));
    }
    
    return $template_id;
}
```

**Mensajes de error/warning:**
- Sin templateId ni formId → warning amarillo ("Este bloque todavía no tiene un formulario asignado")
- Template no encontrado → error rojo ("El formulario seleccionado no existe o fue eliminado")
- En editor y sin formulario → placeholder con instrucciones + link a Form Library

---

## Integration con Form Library

### CPT: `eipsi_form_template`

**Capabilities:** Solo admin (`manage_options`)

**Supports:**
- `title` → nombre del formulario
- `editor` → editor Gutenberg (aquí se inserta el Container)
- `custom-fields` → meta `_eipsi_form_name` extraído del Container

**REST API:** Habilitado (`show_in_rest: true`) para que `useSelect()` funcione

**Columnas personalizadas en la lista:**
- **Shortcode:** `[eipsi_form id="123"]` con botón de copiar
- **Last Response:** Última respuesta recibida (human_time_diff)
- **Total Responses:** Contador de respuestas en BD

### Extracción automática de `form_name`

**Hook:** `save_post` → `eipsi_extract_form_name_on_save()`

**Lógica:**
1. Parsear bloques en `post_content`
2. Buscar bloque `vas-dinamico/form-container`
3. Extraer atributo `formId` del Container
4. Guardarlo como postmeta `_eipsi_form_name`

**Uso:** Relacionar respuestas en `wp_vas_form_results` con templates

---

## Assets y Enqueue Strategy

### Bloques (Editor):
- `build/index.js` → todos los bloques registrados
- `build/index.css` → estilos del editor
- Dependencies auto-gestionadas por `@wordpress/dependency-extraction-webpack-plugin`

### Frontend:
- `build/style-index.css` → estilos de los bloques
- `assets/css/eipsi-forms.css` → estilos de navegación/campos/formularios
- `assets/css/theme-toggle.css` → Dark Mode Toggle
- `assets/js/eipsi-forms.js` → lógica de validación, navegación multipágina, envío
- `assets/js/eipsi-tracking.js` → eventos de interacción (page_change, navigation, etc.)

**Estrategia de enqueue:**
- Enqueue universal en `wp_enqueue_scripts` (para compatibilidad con shortcode)
- Enqueue condicional si se detecta bloque en contenido (hook `the_content`)

---

## Debugging & Logs

### Notices personalizados

**Helper:** `eipsi_render_form_notice($message, $type)`

**Tipos:** `info`, `warning`, `error`

**Estilos inline:** Colores personalizados, borde, icono "EIPSI Forms"

### Estados comunes en editor:

1. **Sin formulario seleccionado:**
   ```html
   <div class="components-placeholder">
       <div class="components-placeholder__label">Formulario EIPSI</div>
       <div class="components-placeholder__instructions">
           Seleccioná un formulario de la librería en la configuración del bloque →
       </div>
   </div>
   ```

2. **Cargando formularios:**
   ```html
   <Spinner />
   <p>Cargando formularios…</p>
   ```

3. **Sin formularios en librería:**
   ```html
   <p>No hay formularios creados aún.</p>
   <a href="/wp-admin/post-new.php?post_type=eipsi_form_template">
       + Crear tu primer formulario
   </a>
   ```

---

## Testing Checklist (clínico)

### ✅ Editor de Form Library:
- [ ] Solo aparecen bloques de construcción (Container + campos)
- [ ] No aparece "Formulario EIPSI" en inserter
- [ ] Se puede insertar Container y diseñar formulario completo
- [ ] Páginas y campos anidados funcionan correctamente

### ✅ Editor de Página/Post normal:
- [ ] Aparece "Formulario EIPSI" en inserter
- [ ] No aparecen bloques de construcción (Container, Pagina, Campos)
- [ ] Dropdown muestra lista de formularios disponibles
- [ ] Se puede seleccionar formulario y ver preview
- [ ] Formulario se renderiza correctamente en frontend

### ✅ Frontend:
- [ ] Formulario insertado muestra igual que cuando se construyó
- [ ] Navegación multipágina funciona (Siguiente/Anterior/Enviar)
- [ ] Dark Mode Toggle aplica correctamente
- [ ] Envío registra datos en BD correctamente
- [ ] Shortcode `[eipsi_form id="123"]` funciona idéntico al bloque

### ✅ Compatibilidad:
- [ ] Formularios antiguos siguen funcionando (no se rompen)
- [ ] Páginas con Container antiguo permiten editar (no se oculta)
- [ ] Migración de `formId` → `templateId` funciona transparentemente

---

## File Structure Reference

```
/home/engine/project/
│
├── blocks/                          # Block JSON + PHP render callbacks
│   ├── form-block/                 # "Formulario EIPSI" (inserción)
│   │   ├── block.json              # Define atributos, título, descripción
│   │   └── index.php               # Comentario (render en includes/)
│   │
│   ├── form-container/             # "EIPSI Form Container" (construcción)
│   │   ├── block.json              # Define atributos completos de configuración
│   │   ├── edit.js                 # Componente React del editor
│   │   ├── save.js                 # Guarda contenido anidado
│   │   ├── index.js                # Registro del bloque
│   │   ├── editor.scss             # Estilos del editor
│   │   └── style.scss              # Estilos de frontend
│   │
│   ├── pagina/                     # Bloque Página (dentro del Container)
│   ├── campo-texto/                # Campo Text Input
│   ├── campo-textarea/             # Campo Textarea
│   ├── campo-descripcion/          # Campo Description
│   ├── campo-select/               # Campo Dropdown/Select
│   ├── campo-radio/                # Campo Radio Buttons
│   ├── campo-multiple/             # Campo Checkboxes
│   ├── campo-likert/               # Campo Likert Scale
│   └── vas-slider/                 # Campo VAS Slider
│
├── src/blocks/                      # Código fuente React/JS de los bloques
│   ├── form-block/
│   │   ├── index.js                # Edit + save + registro
│   │   ├── editor.scss
│   │   └── style.scss
│   │
│   ├── form-container/
│   │   ├── index.js
│   │   ├── edit.js                 # FormStylePanel + ConditionalLogicControl
│   │   ├── save.js                 # <InnerBlocks.Content />
│   │   ├── editor.scss
│   │   └── style.scss
│   │
│   └── [otros bloques similares]
│
├── includes/
│   ├── form-template-render.php    # Core render helpers compartidos
│   └── shortcodes.php              # Shortcode [eipsi_form]
│
├── admin/
│   ├── form-library.php            # CPT + columnas + restricción de bloques
│   ├── form-library-tools.php      # Herramientas (duplicar, importar/exportar)
│   └── clinical-templates.php      # Plantillas oficiales (PHQ-9, GAD-7, etc.)
│
├── assets/
│   ├── css/
│   │   ├── eipsi-forms.css         # Estilos de formularios frontend
│   │   └── theme-toggle.css        # Dark Mode Toggle
│   │
│   └── js/
│       ├── eipsi-forms.js          # Validación + navegación + envío
│       └── eipsi-tracking.js       # Tracking de eventos
│
├── build/                           # Compilados por webpack
│   ├── index.js                    # Bundle JS de todos los bloques
│   ├── index.css                   # Bundle CSS del editor
│   ├── index.asset.php             # Dependencies para wp_register_script
│   ├── style-index.css             # Bundle CSS de frontend
│   └── style-index-rtl.css         # RTL support
│
└── vas-dinamico-forms.php           # Main plugin file
    ├── Registro de bloques (register_block_type)
    ├── Enqueue de assets
    └── Includes de archivos PHP
```

---

## Próximos pasos en roadmap

**Prioridades inmediatas** (según system prompt):

1. ✅ **Página de finalización integrada** (misma URL, sin redirect)
2. **Save & Continue Later** + autosave 30s + beforeunload + IndexedDB
3. **Conditional field visibility** dentro de la misma página + conditional required
4. **Templates clínicos listos** con scoring y normas locales

**Este ticket implementa:**
- ✅ Arquitectura limpia de bloques (Construcción vs Inserción)
- ✅ Restricción de contexto (Form Library vs Páginas públicas)
- ✅ Compatibilidad backward (formId legacy → templateId)
- ✅ Render path compartido (bloque + shortcode)

---

**Por fin alguien entendió cómo trabajo de verdad con mis pacientes.**
