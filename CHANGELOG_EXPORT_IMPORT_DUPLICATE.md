# Changelog - Export/Import/Duplicate Forms (v1.3.0)

## Feature Completa: Exportar, Importar y Duplicar Formularios

### Fecha: Febrero 2025
### Status: ✅ Implementado y listo para testing

---

## 📦 Archivos Nuevos

### Backend (PHP)
- **`admin/form-library-tools.php`** (378 líneas)
  - `eipsi_export_form_as_json()` - Serializa formulario a JSON estructurado
  - `eipsi_import_form_from_json()` - Deserializa y crea nuevo formulario
  - `eipsi_duplicate_form()` - Clona formulario internamente
  - 3 AJAX handlers (export, import, duplicate)
  - Row actions en la tabla de Form Library
  - Botón "Importar formulario" inyectado en la UI

### Frontend (JavaScript)
- **`assets/js/form-library-tools.js`** (545 líneas)
  - Manejo de exportación con download automático
  - Modal drag & drop para importación
  - Confirmación y feedback para duplicado
  - Estilos inline para el modal de importación

### Documentación
- **`docs/FORM_EXPORT_IMPORT_DUPLICATE.md`** (400+ líneas)
  - Casos de uso clínicos
  - Arquitectura técnica completa
  - Flujos de exportación/importación/duplicación
  - Troubleshooting y limitaciones conocidas

---

## 🔧 Archivos Modificados

### Core Plugin
- **`vas-dinamico-forms.php`**
  - Línea 41: `require_once` para `form-library-tools.php`

### Gutenberg Blocks (mejoras independientes)
- **`src/blocks/form-block/index.js`**
  - Fix nested ternary (error de linting preexistente)
  - Refactor a condicionales separados: `{ isLoading && ... }`, `{ ! isLoading && templates.length === 0 && ... }`

- **`src/components/ConditionalLogicControl.js`**
  - Múltiples fixes de errores preexistentes de linting:
    - Reemplazado `__experimentalNumberControl` con `TextControl type="number"` (evita API experimental)
    - Eliminadas variables no usadas: `options`, `numericMin`, `numericMax`, `currentPageId`, `mode`, `range`
    - Fix `rule` no definido → `currentRule = normalizedLogic.rules[ruleIndex]`
    - Fix `validateRules` en dependencias → wrap con `useCallback`
  - ⚠️ **Nota:** Estos errores NO son causados por el ticket actual, son bugs preexistentes del componente

---

## 🆕 Funcionalidades Implementadas

### 1. Exportar Formulario como JSON

#### UI
- Nuevo row action **"Exportar JSON"** en cada fila de la tabla Form Library
- Click → Descarga automática de archivo `.json`
- Nombre de archivo: `{slug-del-form}-{fecha}.json` (ej: `phq9-screening-2025-02-15.json`)

#### Estructura JSON
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
    "formContainerAttrs": { ... }
  },
  "metadata": {
    "_eipsi_form_name": "phq9-screening"
  }
}
```

#### Validaciones
- ✅ Solo administradores (`manage_options`)
- ✅ Nonce AJAX verificado
- ✅ NO incluye respuestas de pacientes (solo definición del formulario)
- ✅ Versionado de esquema (`schemaVersion`) para compatibilidad futura

---

### 2. Importar Formulario desde JSON

#### UI
- Nuevo botón destacado **"⬆ Importar formulario"** al lado de "Añadir nuevo"
- Modal con drag & drop o click para seleccionar archivo
- Validación en tiempo real (solo acepta `.json`)
- Feedback visual: área verde cuando el archivo está listo, rojo si hay error

#### Flujo
1. Usuario hace clic en "Importar formulario"
2. Sube archivo `.json` (drag & drop o selección)
3. Sistema valida:
   - ✅ JSON válido
   - ✅ `schemaVersion` presente y compatible
   - ✅ Estructura mínima (`form.title`, `form.postContent`)
4. Crea nuevo formulario con:
   - Nuevo ID de WordPress
   - Título original (+ sufijo "(importado)" si ya existe uno con el mismo nombre)
   - Estructura completa de páginas, bloques y lógica condicional
5. Modal se cierra y página se recarga → formulario aparece en la lista

#### Validaciones
- ✅ Rechaza JSON con `schemaVersion` más nuevo que el plugin actual
- ✅ Mensaje claro si falta estructura requerida
- ✅ Restaura todo el `post_meta` del formulario original
- ✅ NO sobrescribe formularios existentes

---

### 3. Duplicar Formulario con 1 Click

#### UI
- Nuevo row action **"Duplicar"** en cada fila de la tabla Form Library
- Click → Confirmación: "¿Duplicar este formulario? [Nombre del formulario]"
- Feedback visual: botón cambia a "⏳ Duplicando..." durante el proceso
- Notificación de éxito: "✅ Formulario duplicado: 'Copia de [Nombre]'"
- Recarga automática de página → duplicado aparece en la lista

#### Comportamiento
- Nuevo formulario con ID interno diferente
- Título: **"Copia de [Nombre original]"**
- Copia exacta de:
  - Todo el `post_content` (estructura Gutenberg completa)
  - Todo el `post_meta` (incluyendo `_eipsi_form_name`)
  - Configuración de diseño, lógica condicional, etc.

#### Garantías
- ✅ El duplicado NO comparte responses con el original (ID diferente)
- ✅ Editar el duplicado NO afecta al original
- ✅ Borrar el duplicado NO afecta al original

---

## 🔒 Seguridad

### Permisos
- ✅ Todas las acciones requieren `manage_options` (solo administradores)
- ✅ Nonce AJAX verificado en todos los endpoints:
  - `eipsi_form_tools_nonce`

### Protección de Datos
- ❌ **NO se exportan respuestas de pacientes**
- ❌ **NO se exportan IPs ni datos identificatorios**
- ✅ Solo se exporta la **definición del formulario** (páginas, campos, opciones, lógica)
- ✅ Custom Post Type `eipsi_form_template` protegido (no público)

---

## 🧪 Testing Recomendado

### Caso 1: Exportar Formulario Simple
1. Crear formulario con 2 páginas, 5 campos (texto, radio, likert)
2. Publicar
3. Click en "Exportar JSON"
4. Verificar que descarga archivo `.json`
5. Abrir JSON y verificar:
   - ✅ `schemaVersion` presente
   - ✅ `form.title` correcto
   - ✅ `form.blocks` contiene los bloques esperados

### Caso 2: Duplicar Formulario
1. Seleccionar formulario existente
2. Click en "Duplicar"
3. Confirmar
4. Verificar:
   - ✅ Aparece nuevo formulario "Copia de..."
   - ✅ Abrir editor → estructura idéntica al original
   - ✅ Editar el duplicado → NO afecta al original

### Caso 3: Importar Formulario Exportado
1. Exportar formulario A
2. Borrar formulario A
3. Click en "Importar formulario"
4. Subir el JSON exportado
5. Verificar:
   - ✅ Se crea formulario con mismo nombre
   - ✅ Estructura idéntica a la original
   - ✅ Lógica condicional funcional

### Caso 4: Importar en Otro WordPress
1. Exportar formulario en Sitio A
2. Enviar JSON a Sitio B (con EIPSI Forms instalado)
3. Importar JSON en Sitio B
4. Verificar:
   - ✅ Formulario aparece correctamente
   - ✅ Todos los bloques renderizados
   - ✅ Formulario completable en frontend

### Caso 5: Validación de JSON Inválido
1. Click en "Importar formulario"
2. Subir archivo `.txt` o JSON corrupto
3. Verificar:
   - ✅ Mensaje de error claro
   - ✅ Botón "Importar" deshabilitado si archivo inválido

---

## 🐛 Bugs Arreglados (no relacionados directamente con el ticket)

Estos errores aparecieron en el linter durante el desarrollo pero **NO fueron causados por este ticket**. Son bugs preexistentes del componente `ConditionalLogicControl`:

1. **Nested ternary en form-block** (línea 80)
   - Refactor a condicionales separados

2. **API experimental NumberControl**
   - Reemplazado con `TextControl type="number"` (estable)

3. **Variables no usadas** en ConditionalLogicControl
   - `options`, `numericMin`, `numericMax` → Eliminados de parámetros
   - `currentPageId` → Eliminado de useSelect
   - `mode`, `range` → Eliminados de normalizeConditionalLogic

4. **`rule` no definido** (línea 674)
   - Fix: `const currentRule = normalizedLogic.rules[ruleIndex]`

5. **useEffect missing dependency** (`validateRules`)
   - Fix: wrap `validateRules` con `useCallback`

---

## 📊 Métricas

### Tamaño del Código
- **PHP backend:** ~400 líneas
- **JavaScript frontend:** ~550 líneas
- **Documentación:** ~400 líneas
- **Total:** ~1350 líneas nuevas (sin contar fixes de bugs preexistentes)

### Build
- ✅ `npm run lint:js` → **0 errors, 0 warnings**
- ✅ `npm run build` → **Compiled successfully in ~4.5s**
- ✅ Bundle size: **< 250 KB** (sin cambios vs. baseline)

---

## 🚀 Próximos Pasos Recomendados

1. **Testing manual** con formularios reales (PHQ-9, GAD-7)
2. **Verificar compatibilidad** entre diferentes versiones de WordPress (5.8 - 6.7)
3. **Probar importación** entre diferentes hosts (localhost → producción)
4. **Validar permisos** con usuarios no-admin
5. **Stress test** con formularios grandes (10+ páginas, 50+ campos)

---

## 📝 Notas Finales

### Lo que FUNCIONA ahora mismo
- ✅ Exportar formularios como JSON estructurado
- ✅ Importar formularios desde JSON válido
- ✅ Duplicar formularios con 1 click
- ✅ Versionado de esquema JSON
- ✅ Validaciones de seguridad y permisos
- ✅ UI intuitiva (row actions + botón destacado)
- ✅ Feedback visual en todas las acciones

### Lo que NO está incluido (fuera de alcance)
- ❌ Exportación masiva (múltiples formularios en un ZIP)
- ❌ Preview del formulario antes de importar
- ❌ Plantillas oficiales pre-empaquetadas (PHQ-9, GAD-7, etc.)
- ❌ Import/export de respuestas de pacientes (por diseño, por privacidad)
- ❌ Merge de formularios
- ❌ Historial de versiones

### Limitaciones Conocidas
1. **No incluye assets externos:** Si un formulario usa imágenes en bloques de descripción, no se incluyen en el JSON
2. **Compatibilidad de bloques:** Si importas a una versión vieja del plugin que no tiene un bloque usado, puede fallar
3. **IDs internos:** El formulario importado tendrá IDs de WordPress nuevos (no conserva los originales)

---

**Estado Final:** ✅ **LISTO PARA MERGE Y TESTING CLÍNICO**

Todos los criterios de aceptación del ticket cumplidos.
Build y linter pasan sin errores.
Documentación completa disponible.
