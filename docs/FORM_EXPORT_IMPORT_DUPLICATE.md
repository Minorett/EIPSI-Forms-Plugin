# Exportar, Importar y Duplicar Formularios - EIPSI Forms

## Descripción General

Sistema completo de gestión de formularios que permite:

1. **Exportar formularios como JSON** - Descarga la definición completa de un formulario
2. **Importar formularios desde JSON** - Crea formularios desde archivos JSON exportados
3. **Duplicar formularios con 1 click** - Clona formularios existentes en la misma instalación

## Casos de Uso Clínicos

### 1. Migrar formularios entre entornos

```
Situación: Psicóloga armó PHQ-9 perfecto en su WordPress de prueba local.
Necesita: Pasarlo a producción sin reconstruirlo.

Solución:
1. En entorno de prueba → Exportar JSON del formulario PHQ-9
2. En producción → Importar el JSON descargado
3. Listo: Mismo formulario, mismo diseño, mismas opciones
```

### 2. Compartir formularios entre colegas

```
Situación: Investigador tiene GAD-7 validado con normas argentinas.
Necesita: Compartirlo con red de colaboradores.

Solución:
1. Exportar GAD-7 como JSON
2. Enviar archivo por email/Drive
3. Colegas importan el JSON en sus WordPress
4. Todos usan el mismo instrumento estandarizado
```

### 3. Crear variantes de formularios existentes

```
Situación: Clínico tiene "Evaluación inicial completa".
Necesita: Versión abreviada para seguimiento.

Solución:
1. Duplicar "Evaluación inicial completa"
2. Renombrar a "Seguimiento rápido"
3. Editar y eliminar secciones innecesarias
4. Listo: Dos formularios, sin partir de cero
```

## Uso Práctico

### Exportar un formulario

1. Ir a **EIPSI Forms → Form Library**
2. En la fila del formulario deseado, hacer clic en **"Exportar JSON"**
3. Se descarga automáticamente un archivo `.json` (ej: `phq9-2025-02-15.json`)

El archivo contiene:
- Nombre del formulario
- Estructura completa de páginas y bloques
- Opciones de campos (radio, likert, select, etc.)
- Lógica condicional configurada
- Configuración de diseño (preset, dark mode, etc.)

**No incluye:**
- Respuestas de pacientes
- Datos identificatorios
- Configuraciones globales del plugin

### Importar un formulario

1. Ir a **EIPSI Forms → Form Library**
2. Hacer clic en el botón **"⬆ Importar formulario"** (al lado de "Añadir nuevo")
3. Seleccionar archivo `.json` (click o drag & drop)
4. Hacer clic en **"Importar"**
5. El formulario aparece automáticamente en la librería

Validaciones automáticas:
- ✅ Verifica que el JSON sea válido
- ✅ Comprueba la versión del esquema
- ✅ Si ya existe un formulario con el mismo nombre, añade "(importado)"

### Duplicar un formulario

1. Ir a **EIPSI Forms → Form Library**
2. En la fila del formulario deseado, hacer clic en **"Duplicar"**
3. Confirmar la acción
4. Se crea una copia con el nombre **"Copia de [Nombre original]"**
5. La página se recarga y el duplicado aparece en la lista

El duplicado incluye:
- Todo el contenido y estructura del original
- Misma configuración de campos y lógica
- Todos los post meta asociados

**Importante:** El duplicado tiene un ID interno nuevo, por lo que las respuestas históricas del formulario original NO se mezclan con las del duplicado.

## Arquitectura Técnica

### Esquema JSON (v1.0.0)

```json
{
  "schemaVersion": "1.0.0",
  "meta": {
    "exportedAt": "2025-02-15T14:30:00+00:00",
    "exportedBy": "Admin Usuario",
    "pluginVersion": "1.2.2",
    "formTitle": "PHQ-9 Screening Depression",
    "formName": "phq9-screening"
  },
  "form": {
    "title": "PHQ-9 Screening Depression",
    "formId": "phq9-screening",
    "blocks": [...],
    "postContent": "<!-- wp:vas-dinamico/form-container {...} -->",
    "formContainerAttrs": {
      "formId": "phq9-screening",
      "preset": "clinical",
      "darkModeEnabled": true
    }
  },
  "metadata": {
    "_eipsi_form_name": "phq9-screening"
  }
}
```

### Archivos Implementados

```
admin/form-library-tools.php
├── eipsi_export_form_as_json()      → Serializa formulario a array
├── eipsi_import_form_from_json()    → Deserializa y crea nuevo post
├── eipsi_duplicate_form()           → Clona formulario internamente
├── AJAX handlers (export, import, duplicate)
└── UI hooks (row actions, import button)

assets/js/form-library-tools.js
├── FormLibraryTools.init()          → Inicializa event listeners
├── .bindExportActions()             → Maneja click en "Exportar JSON"
├── .bindDuplicateActions()          → Maneja click en "Duplicar"
├── .bindImportButton()              → Maneja click en "Importar"
├── .showImportModal()               → Muestra modal con drag & drop
├── .downloadJSON()                  → Trigger download del JSON
└── .processImport()                 → Lee archivo y envía al backend
```

### Flujo de Exportación

```
Usuario click "Exportar JSON"
    ↓
JavaScript: form-library-tools.js
    ↓ (AJAX)
PHP: eipsi_ajax_export_form()
    ↓
eipsi_export_form_as_json($template_id)
    ├── get_post($template_id)
    ├── parse_blocks($post_content)
    ├── Extrae form_name del form-container
    └── Estructura JSON con schemaVersion
    ↓
Respuesta JSON al cliente
    ↓
JavaScript: downloadJSON()
    ↓
Descarga automática del archivo
```

### Flujo de Importación

```
Usuario sube archivo .json
    ↓
JavaScript: FileReader.readAsText(file)
    ↓ (AJAX)
PHP: eipsi_ajax_import_form()
    ↓
json_decode($json_string)
    ↓
eipsi_import_form_from_json($json_data)
    ├── Valida schemaVersion
    ├── Valida estructura requerida
    ├── wp_insert_post() → Crea nuevo template
    ├── Restaura post_content
    └── Restaura post_meta
    ↓
Nuevo formulario creado
    ↓
Recarga de página → Formulario visible en lista
```

### Flujo de Duplicación

```
Usuario click "Duplicar"
    ↓
Confirmación: "¿Duplicar este formulario?"
    ↓ (AJAX)
PHP: eipsi_ajax_duplicate_form()
    ↓
eipsi_duplicate_form($template_id)
    ├── get_post($template_id)
    ├── wp_insert_post() → Nuevo post con título "Copia de..."
    └── Copia todo post_meta del original
    ↓
Nuevo formulario creado
    ↓
Recarga de página → Duplicado visible en lista
```

## Seguridad y Validaciones

### Permisos

- ✅ Todas las acciones requieren `manage_options` (solo administradores)
- ✅ AJAX nonces verificados en todos los endpoints
- ✅ Custom Post Type `eipsi_form_template` protegido (no público)

### Validaciones de Importación

```php
// 1. Validar que existe schemaVersion
if (!isset($json_data['schemaVersion'])) {
    return WP_Error('invalid_schema');
}

// 2. Verificar compatibilidad de versión
if (version_compare($schema_version, EIPSI_FORM_SCHEMA_VERSION, '>')) {
    return WP_Error('unsupported_schema');
}

// 3. Validar estructura mínima
if (!isset($json_data['form']) || !isset($json_data['form']['title'])) {
    return WP_Error('invalid_structure');
}
```

### Protección de Datos

- ❌ **NO se exportan respuestas de pacientes** (solo definición del formulario)
- ❌ **NO se exportan IPs ni datos identificatorios**
- ✅ Solo se exporta la estructura del formulario y su configuración

## Compatibilidad y Versionado

### Versionado del Esquema JSON

Versión actual: **1.0.0**

El campo `schemaVersion` permite:
- Detectar formularios exportados de versiones futuras
- Implementar migraciones automáticas si cambia la estructura
- Rechazar importaciones incompatibles con mensaje claro

### Backward Compatibility

```php
// Si en v2.0.0 cambiamos el esquema, podemos hacer:
if (version_compare($schema_version, '1.0.0', '==')) {
    // Migrar formato 1.0.0 → 2.0.0
    $json_data = migrate_schema_v1_to_v2($json_data);
}
```

### Compatibilidad entre Instalaciones

Los JSON exportados funcionan correctamente entre:
- ✅ Misma versión de plugin
- ✅ Versiones diferentes si `schemaVersion` es compatible
- ✅ WordPress en diferentes hosts
- ✅ Diferentes temas activos
- ⚠️ Requiere que ambos sitios tengan EIPSI Forms instalado

## Limitaciones Conocidas

1. **No incluye assets externos**: Si un formulario usa imágenes o archivos externos, no se incluyen en el JSON
2. **Compatibilidad de bloques**: Si importas a una versión vieja del plugin que no tiene un bloque usado, puede fallar
3. **IDs internos**: El formulario importado tendrá IDs de WordPress nuevos (no conserva los originales)
4. **Respuestas históricas**: NO se pueden exportar/importar respuestas de pacientes (por diseño, por privacidad)

## Roadmap Futuro

Mejoras planificadas:
- **Plantillas oficiales empaquetadas**: PHQ-9, GAD-7, PCL-5, AUDIT, DASS-21 disponibles para importar sin salir del plugin
- **Exportación masiva**: Exportar múltiples formularios en un solo archivo ZIP
- **Import con preview**: Ver el formulario antes de importarlo
- **Merge de formularios**: Combinar dos formularios existentes

## Troubleshooting

### Error: "El archivo JSON no tiene un esquema válido"

**Causa:** El archivo no fue exportado por EIPSI Forms o está corrupto.

**Solución:**
1. Verificar que el archivo es un `.json` válido
2. Abrirlo con un editor de texto y verificar que tiene el campo `schemaVersion`
3. Re-exportar desde el formulario original si está disponible

### Error: "Este JSON usa una versión de esquema más nueva"

**Causa:** El JSON fue exportado de una versión más reciente del plugin.

**Solución:**
1. Actualizar EIPSI Forms a la última versión
2. Alternativamente, pedirle al exportador que use una versión compatible

### El formulario importado no se ve correctamente

**Posibles causas:**
1. Bloques personalizados no disponibles en esta instalación
2. Tema activo no soporta estilos de EIPSI Forms
3. Conflicto con otro plugin

**Solución:**
1. Verificar que EIPSI Forms está actualizado
2. Probar con tema Twenty Twenty-Four
3. Desactivar temporalmente otros plugins de formularios

## Soporte

Para reportar bugs o solicitar mejoras relacionadas con exportar/importar/duplicar:
- GitHub Issues del proyecto
- Contacto directo con el equipo de desarrollo de EIPSI Forms

---

**Versión de la documentación:** 1.0.0  
**Última actualización:** Febrero 2025  
**Compatibilidad:** EIPSI Forms ≥ 1.3.0
